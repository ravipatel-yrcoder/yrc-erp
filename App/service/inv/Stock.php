<?php
class Service_Inv_Stock extends Service_Base {
    
    public function reserve(array $items) {

        $companyId  = $this->context->companyId;
        foreach ($items as $item) {

            $prodId = $item["prod_id"];
            $locationId = $item["location_id"];
            $qty = $item["qty"];

            $product = new Models_Product($prodId);
            if (empty($product->stock_tracking_method) || $product->stock_tracking_method === 'none') {
                continue;
            }

            $stock = new Models_InvProductStock();
            $stock->fetchByProperty(["company_id", "location_id", "product_id"], [$companyId, $locationId, $prodId]);

            if ($stock->isEmpty) {

                // create stock entry
                $stock->company_id = $companyId;
                $stock->location_id = $locationId;
                $stock->product_id = $prodId;
                $stock->reserved_qty = (float) $qty;
                if( !$stock->create() ) {
                    throw new Service_Exception("Failed to reserve stock for product: " . $product->name);
                }

                //throw new Service_Exception("Stock record not found for product: " . $product->name, 422);
            } else {

                $stock->reserved_qty = (float) $stock->reserved_qty + (float) $qty;
                if (!$stock->update()) {
                    throw new Service_Exception("Failed to reserve stock for product: " . $product->name);
                }
            }            
        }
    }



    public function release(array $items) {

        $companyId  = $this->context->companyId;

        foreach ($items as $item) {

            $prodId = $item["prod_id"];
            $locationId = $item["location_id"];
            $qty = $item["qty"];

            $product = new Models_Product($prodId);
            if (empty($product->stock_tracking_method) || $product->stock_tracking_method === 'none') {
                continue;
            }

            $stock = new Models_InvProductStock();
            $stock->fetchByProperty(
                ["company_id", "location_id", "product_id"],
                [$companyId, $locationId, $prodId]
            );

            if ($stock->isEmpty) {
                continue; // nothing to release
            }

            $stock->reserved_qty = max(0, (float) $stock->reserved_qty - (float) $qty);
            if (!$stock->update()) {
                throw new Service_Exception("Failed to release reserved stock for product: " . $product->name);
            }
        }
    }


    // -------------------------------------------------------------------------
    // Document-aware reservation methods
    // Item format: ['product_id' => int, 'location_id' => int, 'qty' => float, 'line_id' => int]
    // -------------------------------------------------------------------------

    /**
     * Reserve stock for a document.
     * Updates inv_product_stock.reserved_qty and upserts into inv_stock_reservations.
     * Item must include 'line_id' (so_item.id or mo_material_item.id).
     */
    public function reserveForDocument(array $items, string $docType, int $docId, string $docNumber): void
    {
        $companyId = $this->context->companyId;

        foreach ($items as $item) {
            $productId  = (int)   ($item['product_id']  ?? 0);
            $locationId = (int)   ($item['location_id'] ?? 0);
            $qty        = (float) ($item['qty']          ?? 0);
            $lineId     = (int)   ($item['line_id']      ?? 0);

            if ($qty <= 0 || !$productId || !$locationId) continue;

            $product = new Models_Product($productId);
            if (empty($product->stock_tracking_method) || $product->stock_tracking_method === 'none') {
                continue;
            }

            $this->db->query(
                "INSERT INTO inv_product_stock (company_id, location_id, product_id, on_hand_qty, reserved_qty)
                 VALUES (?, ?, ?, 0, ?)
                 ON DUPLICATE KEY UPDATE reserved_qty = reserved_qty + ?",
                [$companyId, $locationId, $productId, $qty, $qty]
            );

            $this->db->query(
                "INSERT INTO inv_stock_reservations
                     (company_id, product_id, location_id, document_type, document_id, document_number, document_line_id, reserved_qty)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                 ON DUPLICATE KEY UPDATE
                     reserved_qty    = reserved_qty + VALUES(reserved_qty),
                     document_number = VALUES(document_number),
                     updated_at      = NOW()",
                [$companyId, $productId, $locationId, $docType, $docId, $docNumber, $lineId, $qty]
            );
        }
    }


    /**
     * Release all reserved stock for a document.
     * Reads amounts from inv_stock_reservations, updates inv_product_stock, then deletes reservation rows.
     */
    public function releaseForDocument(string $docType, int $docId): void
    {
        $companyId = $this->context->companyId;

        $rows = $this->db->fetchAll(
            "SELECT product_id, location_id, reserved_qty
             FROM inv_stock_reservations
             WHERE company_id = ? AND document_type = ? AND document_id = ?",
            [$companyId, $docType, $docId]
        );

        foreach ($rows as $row) {
            $this->db->query(
                "UPDATE inv_product_stock
                 SET reserved_qty = GREATEST(0, reserved_qty - ?)
                 WHERE company_id = ? AND location_id = ? AND product_id = ?",
                [(float) $row->reserved_qty, $companyId, (int) $row->location_id, (int) $row->product_id]
            );
        }

        $this->db->query(
            "DELETE FROM inv_stock_reservations
             WHERE company_id = ? AND document_type = ? AND document_id = ?",
            [$companyId, $docType, $docId]
        );
    }


    /**
     * Apply a signed delta to reserved_qty for a specific product/location.
     * Positive = reserve more, negative = reduce.
     * Also updates the matching inv_stock_reservations row when lineId > 0.
     */
    public function adjustReservation(int $productId, int $locationId, float $delta, string $docType, int $docId, string $docNumber, int $lineId = 0): void
    {
        if (abs($delta) < 0.0001) return;

        $companyId = $this->context->companyId;

        $this->db->query(
            "UPDATE inv_product_stock
             SET reserved_qty = GREATEST(0, reserved_qty + ?)
             WHERE company_id = ? AND location_id = ? AND product_id = ?",
            [$delta, $companyId, $locationId, $productId]
        );

        if ($lineId > 0) {
            $this->db->query(
                "UPDATE inv_stock_reservations
                 SET reserved_qty = GREATEST(0, reserved_qty + ?),
                     updated_at   = NOW()
                 WHERE company_id = ? AND product_id = ? AND location_id = ?
                   AND document_type = ? AND document_id = ? AND document_line_id = ?",
                [$delta, $companyId, $productId, $locationId, $docType, $docId, $lineId]
            );
        }
    }


    /**
     * Mark specific serials as reserved for a document.
     * Sets inv_serials.status = 'reserved' and inv_serial_stock.reserved_doc_type/reserved_doc_id.
     * This is a RESERVATION, not a movement — on_hand_qty does not change.
     */
    public function reserveSerials(int $productId, int $locationId, array $serialIds, string $docType, int $docId): void
    {
        if (empty($serialIds)) return;

        $now        = date('Y-m-d H:i:s');
        $invService = new Service_Inv_Movement($this->context);
        foreach ($serialIds as $serialId) {
            $this->db->query("UPDATE inv_serials SET status = 'reserved' WHERE id = ?", [$serialId]);
            $this->db->query("UPDATE inv_serial_stock SET reserved_doc_type = ?, reserved_doc_id = ?, updated_at = ? WHERE serial_id = ?", [$docType, $docId, $now, $serialId]);
            $invService->logSerialHistory($serialId, $productId, 'reserved', 'Reserved for ' . $docType . ' #' . $docId, null, null, ['to_status' => 'reserved', 'doc_type' => $docType, 'doc_id' => $docId]);
        }
    }


    /**
     * Release specific serials back to in_stock.
     * Sets inv_serials.status = 'in_stock' and clears reserved_doc_type/reserved_doc_id.
     * Only touches serials that are currently 'reserved' (safety guard).
     */
    public function releaseSerials(int $productId, int $locationId, array $serialIds): void
    {
        if (empty($serialIds)) return;

        $now        = date('Y-m-d H:i:s');
        $invService = new Service_Inv_Movement($this->context);
        foreach ($serialIds as $serialId) {
            $this->db->query("UPDATE inv_serials SET status = 'in_stock' WHERE id = ? AND status = 'reserved'", [$serialId]);
            $this->db->query("UPDATE inv_serial_stock SET reserved_doc_type = NULL, reserved_doc_id = NULL, updated_at = ? WHERE serial_id = ?", [$now, $serialId]);
            $invService->logSerialHistory($serialId, $productId, 'reservation_released', 'Reservation released from MO material', null, null, ['to_status' => 'in_stock']);
        }
    }


    /**
     * Restore serials to in_stock after a material return.
     * Handles both cases:
     *   - Reserved serials: still have an inv_serial_stock row → clear reserved_doc_type/reserved_doc_id
     *   - Consumed serials: inv_serial_stock row was deleted → re-create it via INSERT IGNORE
     * No status guard — works for both 'reserved' and 'consumed' serials.
     */
    public function restoreSerials(int $productId, int $locationId, array $serialIds): void
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
                "UPDATE inv_serial_stock SET reserved_doc_type = NULL, reserved_doc_id = NULL, updated_at = ? WHERE serial_id = ? AND company_id = ?",
                [$now, $serialId, $companyId]
            );
            $this->db->query(
                "INSERT IGNORE INTO inv_serial_stock (company_id, location_id, product_id, serial_id, reserved_doc_type, reserved_doc_id, created_at, updated_at)
                 VALUES (?, ?, ?, ?, NULL, NULL, ?, ?)",
                [$companyId, $locationId, $productId, $serialId, $now, $now]
            );
            $invService->logSerialHistory($serialId, $productId, 'returned_to_stock', 'Returned to stock from MO material return', null, null, ['to_status' => 'in_stock']);
        }
    }
}