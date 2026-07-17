<?php
class Service_Inv_Stock extends Service_Base {
    
    // -------------------------------------------------------------------------
    // Document-aware reservation methods
    // Item format: ['product_id' => int, 'warehouse_id' => int, 'qty' => float, 'line_id' => int]
    // -------------------------------------------------------------------------

    /**
     * Reserve stock for a document.
     * Updates inv_product_stock.reserved_qty and upserts into inv_stock_allocations.
     * Item must include 'line_id' (so_item.id or mo_material_item.id).
     */
    public function reserveForDocument(array $items, string $docType, int $docId, string $docNumber): void
    {
        $companyId = $this->context->companyId;

        foreach ($items as $item) {
            $productId  = (int)   ($item['product_id']  ?? 0);
            $warehouseId = (int)   ($item['warehouse_id'] ?? 0);
            $qty        = (float) ($item['qty']          ?? 0);
            $lineId     = (int)   ($item['line_id']      ?? 0);

            if ($qty <= 0 || !$productId || !$warehouseId) continue;

            $product = new Models_Product($productId);
            if (empty($product->stock_tracking_method) || $product->stock_tracking_method === 'none') {
                continue;
            }

            $this->db->query(
                "INSERT INTO inv_product_stock (company_id, warehouse_id, product_id, unrestricted_qty, reserved_qty)
                 VALUES (?, ?, ?, 0, ?)
                 ON DUPLICATE KEY UPDATE reserved_qty = reserved_qty + ?",
                [$companyId, $warehouseId, $productId, $qty, $qty]
            );

            $this->db->query(
                "INSERT INTO inv_stock_allocations
                     (company_id, product_id, warehouse_id, document_type, document_id, document_number, document_line_id, allocation_type, quantity)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'reservation', ?)
                 ON DUPLICATE KEY UPDATE
                     quantity        = quantity + VALUES(quantity),
                     document_number = VALUES(document_number),
                     updated_at      = NOW()",
                [$companyId, $productId, $warehouseId, $docType, $docId, $docNumber, $lineId, $qty]
            );
        }
    }


    /**
     * Release all reserved stock for a document — handles both serial and qty-tracked items.
     * Flips remaining reserved serials back to in_stock, decrements inv_product_stock.reserved_qty,
     * then deletes all inv_stock_allocations rows for the document.
     */
    public function releaseForDocument(string $docType, int $docId): void
    {
        $companyId = $this->context->companyId;

        // Release any remaining reserved serials tied to this document
        $reservedSerials = $this->db->fetchAll(
            "SELECT ss.serial_id, s.product_id, ss.warehouse_id
             FROM inv_serial_stock AS ss
             INNER JOIN inv_serials AS s ON s.id = ss.serial_id AND s.status = 'reserved'
             WHERE ss.company_id = ? AND ss.state_doc_type = ? AND ss.state_doc_id = ?",
            [$companyId, $docType, $docId]
        );

        $grouped = [];
        foreach ($reservedSerials as $row) {
            $key = $row->product_id . '_' . $row->warehouse_id;
            $grouped[$key]['product_id']    = (int) $row->product_id;
            $grouped[$key]['warehouse_id']   = (int) $row->warehouse_id;
            $grouped[$key]['serial_ids'][]  = (int) $row->serial_id;
        }
        foreach ($grouped as $group) {
            $this->releaseSerials($group['product_id'], $group['warehouse_id'], $group['serial_ids']);
        }

        // Decrement inv_product_stock.reserved_qty for all remaining reservation rows
        $rows = $this->db->fetchAll(
            "SELECT product_id, warehouse_id, quantity
             FROM inv_stock_allocations
             WHERE company_id = ? AND document_type = ? AND document_id = ? AND allocation_type = 'reservation'",
            [$companyId, $docType, $docId]
        );

        foreach ($rows as $row) {
            $this->db->query(
                "UPDATE inv_product_stock
                 SET reserved_qty = GREATEST(0, reserved_qty - ?)
                 WHERE company_id = ? AND warehouse_id = ? AND product_id = ?",
                [(float) $row->quantity, $companyId, (int) $row->warehouse_id, (int) $row->product_id]
            );
        }

        $this->db->query(
            "DELETE FROM inv_stock_allocations
             WHERE company_id = ? AND document_type = ? AND document_id = ? AND allocation_type = 'reservation'",
            [$companyId, $docType, $docId]
        );
    }


    /**
     * Apply a signed delta to reserved_qty for a specific product/location.
     * Positive = reserve more, negative = reduce.
     * Also updates the matching inv_stock_allocations row when lineId > 0.
     */
    public function adjustReservation(int $productId, int $warehouseId, float $delta, string $docType, int $docId, string $docNumber, int $lineId = 0): void
    {
        if (abs($delta) < 0.0001) return;

        $companyId = $this->context->companyId;

        $this->db->query(
            "UPDATE inv_product_stock
             SET reserved_qty = GREATEST(0, reserved_qty + ?)
             WHERE company_id = ? AND warehouse_id = ? AND product_id = ?",
            [$delta, $companyId, $warehouseId, $productId]
        );

        if ($lineId > 0) {
            $this->db->query(
                "UPDATE inv_stock_allocations
                 SET quantity   = GREATEST(0, quantity + ?),
                     updated_at = NOW()
                 WHERE company_id = ? AND product_id = ? AND warehouse_id = ?
                   AND document_type = ? AND document_id = ? AND document_line_id = ?
                   AND allocation_type = 'reservation'",
                [$delta, $companyId, $productId, $warehouseId, $docType, $docId, $lineId]
            );
        }
    }


    /**
     * Mark specific serials as reserved for a document.
     * Sets inv_serials.status = 'reserved' and inv_serial_stock.state_doc_type/state_doc_id.
     * This is a RESERVATION, not a movement — unrestricted_qty does not change.
     */
    public function reserveSerials(int $productId, int $warehouseId, array $serialIds, string $docType, int $docId): void
    {
        if (empty($serialIds)) return;

        $now        = date('Y-m-d H:i:s');
        $invService = new Service_Inv_Movement($this->context);
        foreach ($serialIds as $serialId) {
            $this->db->query("UPDATE inv_serials SET status = 'reserved' WHERE id = ?", [$serialId]);
            $this->db->query("UPDATE inv_serial_stock SET state_doc_type = ?, state_doc_id = ?, updated_at = ? WHERE serial_id = ?", [$docType, $docId, $now, $serialId]);
            $invService->logSerialHistory($serialId, $productId, 'reserved', 'Reserved for ' . $docType . ' #' . $docId, null, null, ['to_status' => 'reserved', 'doc_type' => $docType, 'doc_id' => $docId]);
        }
    }


    /**
     * Release specific serials back to in_stock.
     * Sets inv_serials.status = 'in_stock' and clears state_doc_type/state_doc_id.
     * Only touches serials that are currently 'reserved' (safety guard).
     */
    public function releaseSerials(int $productId, int $warehouseId, array $serialIds): void
    {
        if (empty($serialIds)) return;

        $now        = date('Y-m-d H:i:s');
        $invService = new Service_Inv_Movement($this->context);
        foreach ($serialIds as $serialId) {
            $this->db->query("UPDATE inv_serials SET status = 'in_stock' WHERE id = ? AND status = 'reserved'", [$serialId]);
            $this->db->query("UPDATE inv_serial_stock SET state_doc_type = NULL, state_doc_id = NULL, updated_at = ? WHERE serial_id = ?", [$now, $serialId]);
            $invService->logSerialHistory($serialId, $productId, 'reservation_released', 'Reservation released from MO material', null, null, ['to_status' => 'in_stock']);
        }
    }


    /**
     * Restore serials to in_stock after a material return.
     * Handles both cases:
     *   - Reserved serials: still have an inv_serial_stock row → clear state_doc_type/state_doc_id
     *   - Consumed serials: inv_serial_stock row was deleted → re-create it via INSERT IGNORE
     * No status guard — works for both 'reserved' and 'consumed' serials.
     */
    public function restoreSerials(int $productId, int $warehouseId, array $serialIds): void
    {
        if (empty($serialIds)) return;

        $companyId  = $this->context->companyId;
        $now        = date('Y-m-d H:i:s');
        $ph         = implode(',', array_fill(0, count($serialIds), '?'));
        $invService = new Service_Inv_Movement($this->context);

        $this->db->query(
            "UPDATE inv_serials SET status = 'in_stock', updated_at = ? WHERE id IN ($ph)",
            array_merge([$now], $serialIds)
        );

        foreach ($serialIds as $serialId) {
            $this->db->query(
                "UPDATE inv_serial_stock SET state_doc_type = NULL, state_doc_id = NULL, updated_at = ? WHERE serial_id = ? AND company_id = ?",
                [$now, $serialId, $companyId]
            );
            $this->db->query(
                "INSERT IGNORE INTO inv_serial_stock (company_id, warehouse_id, product_id, serial_id, state_doc_type, state_doc_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, NULL, NULL, ?, ?)",
                [$companyId, $warehouseId, $productId, $serialId, $now, $now]
            );
            $invService->logSerialHistory($serialId, $productId, 'returned_to_stock', 'Returned to stock from MO material return', null, null, ['to_status' => 'in_stock']);
        }
    }
}