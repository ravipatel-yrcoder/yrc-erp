<?php
class Service_So_Return extends Service_Base {


    private function getReturnOrFail(int $returnId): Models_Return {

        $return = new Models_Return($returnId);
        if ($return->isEmpty) {
            throw new Service_Exception("The requested return was not found", 404);
        }
        if ($return->company_id != $this->context->companyId) {
            throw new Service_Exception("You do not have permission to access this return", 403);
        }
        return $return;
    }


    private function writeHistory(int $returnId, string $logType, string $title, string $referenceType = '', int $referenceId = 0, array $meta = []): void {

        $history = new Models_ReturnHistory();
        $history->company_id     = $this->context->companyId;
        $history->return_id      = $returnId;
        $history->log_type       = $logType;
        $history->title          = $title;
        $history->reference_type = $referenceType ?: null;
        $history->reference_id   = $referenceId > 0 ? $referenceId : null;
        $history->meta           = !empty($meta) ? json_encode($meta) : null;
        $history->created_by     = $this->context->userId;
        $history->create();
    }


    private function getDeliveredQty(string $referenceType, int $referenceItemId, int $companyId): float {

        if ($referenceType === 'sales_order') {
            // Compute live from delivered DNs — soi.delivered_qty column is never written
            $qty = $this->db->fetchVar(
                "SELECT COALESCE(SUM(sdi.dispatched_qty), 0)
                 FROM sales_delivery_items sdi
                 JOIN sales_deliveries sd ON sd.id = sdi.sales_delivery_id
                 WHERE sdi.sales_order_item_id = ? AND sd.company_id = ? AND sd.status = 'delivered'",
                [$referenceItemId, $companyId]
            );
            return (float) ($qty ?? 0);
        }

        return 0;
    }


    private function getAlreadyReturnedQty(int $referenceItemId, int $companyId, int $excludeReturnId = 0): float {

        $received = (float) $this->db->fetchVar(
            "SELECT COALESCE(SUM(ri.return_qty), 0)
             FROM return_items ri
             JOIN returns r ON r.id = ri.return_id
             WHERE ri.reference_item_id = ? AND r.company_id = ? AND r.status = 'received'",
            [$referenceItemId, $companyId]
        );

        $sql = "SELECT COALESCE(SUM(ri.return_qty), 0)
                FROM return_items ri
                JOIN returns r ON r.id = ri.return_id
                WHERE ri.reference_item_id = ?
                  AND r.company_id = ?
                  AND r.status IN ('draft', 'in_transit')";
        $bindings = [$referenceItemId, $companyId];

        if ($excludeReturnId > 0) {
            $sql .= " AND r.id != ?";
            $bindings[] = $excludeReturnId;
        }

        $active = (float) $this->db->fetchVar($sql, $bindings);

        return $received + $active;
    }


    private function bucketToStockColumn(string $bucket): string {
        $map = [
            'unrestricted' => 'unrestricted_qty',
            'blocked'      => 'blocked_qty',
            'quality'      => 'quality_qty',
        ];
        return $map[$bucket] ?? 'unrestricted_qty';
    }


    private function bucketToMovementType(string $bucket, string $returnType): string {
        if ($returnType === 'vendor') return 'return_to_supplier';
        $map = [
            'unrestricted' => 'cust_return',
            'blocked'      => 'cust_return_blocked',
            'quality'      => 'cust_return_quality',
            'scrap'        => 'cust_return_scrap',
        ];
        return $map[$bucket] ?? 'cust_return';
    }


    private function bucketToSerialStatus(string $bucket): string {
        $map = [
            'unrestricted' => 'in_stock',
            'blocked'      => 'blocked',
            'quality'      => 'quality',
            'scrap'        => 'scrapped',
        ];
        return $map[$bucket] ?? 'in_stock';
    }


    private function bucketToSerialLogType(string $bucket, string $returnType): string {
        if ($returnType === 'vendor') return 'return_to_supplier';
        $map = [
            'unrestricted' => 'cust_returned',
            'blocked'      => 'cust_returned_blocked',
            'quality'      => 'cust_returned_quality',
            'scrap'        => 'cust_returned_scrap',
        ];
        return $map[$bucket] ?? 'cust_returned';
    }


    private function processItemInventory(object $item, object $disposition, string $returnType, int $locationId, int $companyId, int $userId, int $returnId): void {

        $productId    = (int) $item->product_id;
        $returnQty    = (float) $item->return_qty;
        $bucket       = $disposition->bucket;
        $movementType = $this->bucketToMovementType($bucket, $returnType);

        $product = $this->db->fetchOne(
            "SELECT stock_tracking_method FROM products WHERE id = ? AND company_id = ?",
            [$productId, $companyId]
        );

        $trackingMethod = $product ? $product->stock_tracking_method : 'quantity';

        // Non-stock products have no inventory to update
        if ($trackingMethod === 'none') {
            return;
        }

        if ($bucket !== 'scrap') {
            $stockCol = $this->bucketToStockColumn($bucket);

            $stockRow = $this->db->fetchOne(
                "SELECT id, {$stockCol} AS current_qty FROM inv_product_stock
                 WHERE company_id = ? AND location_id = ? AND product_id = ? FOR UPDATE",
                [$companyId, $locationId, $productId]
            );

            $oldQty = $stockRow ? (float) $stockRow->current_qty : 0.0;
            $newQty = $oldQty + $returnQty;

            if ($stockRow) {
                $this->db->query(
                    "UPDATE inv_product_stock SET {$stockCol} = ? WHERE id = ?",
                    [$newQty, $stockRow->id]
                );
            } else {
                $this->db->query(
                    "INSERT INTO inv_product_stock (company_id, location_id, product_id, unrestricted_qty, blocked_qty, quality_qty, reserved_qty)
                     VALUES (?, ?, ?, ?, ?, ?, 0)",
                    [
                        $companyId, $locationId, $productId,
                        $bucket === 'unrestricted' ? $returnQty : 0,
                        $bucket === 'blocked'      ? $returnQty : 0,
                        $bucket === 'quality'      ? $returnQty : 0,
                    ]
                );
                $oldQty = 0.0;
                $newQty = $returnQty;
            }

            $this->db->query(
                "INSERT INTO inv_stock_movements (company_id, location_id, product_id, movement_type, old_qty, qty_change, new_qty, reference_type, reference_id, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'return', ?, ?)",
                [$companyId, $locationId, $productId, $movementType, $oldQty, $returnQty, $newQty, $returnId, $userId]
            );
        } else {
            // Scrap: record movement with no stock change
            $stockRow = $this->db->fetchOne(
                "SELECT COALESCE(unrestricted_qty, 0) AS current_qty FROM inv_product_stock
                 WHERE company_id = ? AND location_id = ? AND product_id = ?",
                [$companyId, $locationId, $productId]
            );
            $oldQty = $stockRow ? (float) $stockRow->current_qty : 0.0;

            $this->db->query(
                "INSERT INTO inv_stock_movements (company_id, location_id, product_id, movement_type, old_qty, qty_change, new_qty, reference_type, reference_id, created_by)
                 VALUES (?, ?, ?, ?, ?, ?, ?, 'return', ?, ?)",
                [$companyId, $locationId, $productId, $movementType, $oldQty, 0, $oldQty, $returnId, $userId]
            );
        }

        // Serial-tracked: update each serial status and log history
        if ($trackingMethod === 'serial') {
            $serials = $this->db->fetchAll(
                "SELECT serial_id FROM return_item_serials WHERE return_item_id = ? AND company_id = ?",
                [$item->id, $companyId]
            );

            $newSerialStatus = $this->bucketToSerialStatus($bucket);
            $serialLogType   = $this->bucketToSerialLogType($bucket, $returnType);
            $title           = ucfirst(str_replace('_', ' ', $serialLogType));

            foreach ($serials as $s) {
                $this->db->query(
                    "UPDATE inv_serials SET status = ?, updated_at = NOW() WHERE id = ? AND company_id = ?",
                    [$newSerialStatus, $s->serial_id, $companyId]
                );

                $this->db->query(
                    "INSERT INTO inv_serial_history (company_id, product_id, serial_id, log_type, title, reference_type, reference_id, created_by)
                     VALUES (?, ?, ?, ?, ?, 'return', ?, ?)",
                    [$companyId, $productId, $s->serial_id, $serialLogType, $title, $returnId, $userId]
                );

                // Restore inv_serial_stock at the return location (not for scrap — scrapped serials have no location)
                if ($bucket !== 'scrap') {
                    $exists = $this->db->fetchOne(
                        "SELECT id FROM inv_serial_stock WHERE serial_id = ? AND location_id = ? LIMIT 1",
                        [$s->serial_id, $locationId]
                    );
                    if (!$exists) {
                        $this->db->insert("inv_serial_stock", [
                            'company_id'     => $companyId,
                            'product_id'     => $productId,
                            'serial_id'      => $s->serial_id,
                            'location_id'    => $locationId,
                            'state_doc_type' => null,
                            'state_doc_id'   => null,
                            'created_at'     => date('Y-m-d H:i:s'),
                        ]);
                    }
                }
            }
        }

        // Lot-tracked: update inv_lot_stock and log history
        if ($trackingMethod === 'lot' && $bucket !== 'scrap') {
            $lots = $this->db->fetchAll(
                "SELECT lot_id, quantity FROM return_item_lots WHERE return_item_id = ? AND company_id = ?",
                [$item->id, $companyId]
            );

            foreach ($lots as $l) {
                $this->db->query(
                    "INSERT INTO inv_lot_stock (company_id, location_id, product_id, lot_id, quantity, reserved_qty, picked_qty, updated_at)
                     VALUES (?, ?, ?, ?, ?, 0, 0, NOW())
                     ON DUPLICATE KEY UPDATE quantity = quantity + ?, updated_at = NOW()",
                    [$companyId, $locationId, $productId, $l->lot_id, (float) $l->quantity, (float) $l->quantity]
                );

                $this->db->query(
                    "INSERT INTO inv_lot_history (company_id, lot_id, product_id, log_type, title, reference_type, reference_id, created_by)
                     VALUES (?, ?, ?, 'returned_to_stock', 'Returned to stock', 'return', ?, ?)",
                    [$companyId, $l->lot_id, $productId, $returnId, $userId]
                );
            }
        }
    }


    public function getFormContext(int $returnId = 0, int $soId = 0): array {

        $companyId      = $this->context->companyId;
        $userId         = $this->context->userId;
        $existingReturn = null;
        $returnDetails  = null;

        if ($returnId > 0) {
            $existingReturn = $this->getReturnOrFail($returnId);
            if ($existingReturn->status !== 'draft') {
                throw new Service_Exception("Only draft returns can be edited", 422);
            }
            $soId = (int) $existingReturn->reference_id;
        }

        if ($soId <= 0) {
            throw new Service_Exception("A sales order is required", 422);
        }

        $so = $this->db->fetchOne(
            "SELECT so.id, so.so_number, so.status, so.customer_id, so.location_id,
                    c.display_name AS customer_name,
                    l.name AS location_name
             FROM sales_orders so
             JOIN customers c ON c.id = so.customer_id
             LEFT JOIN company_locations l ON l.id = so.location_id
             WHERE so.id = ? AND so.company_id = ?",
            [$soId, $companyId]
        );

        if (!$so) {
            throw new Service_Exception("Sales order not found", 404);
        }

        // In edit mode, exclude this return from already_returned_qty so available shows full pool
        $subExcludeClause = '';
        $subParams = [$companyId];
        if ($returnId > 0) {
            $subExcludeClause = " AND r.id != ?";
            $subParams[] = $returnId;
        }
        $subParams[] = $soId;

        $soItems = $this->db->fetchAll(
            "SELECT soi.id AS so_item_id, soi.product_id, soi.ordered_qty, soi.uom_code,
                    soi.product_name, soi.unit_price, soi.line_total, soi.tax_amount,
                    p.stock_tracking_method,
                    COALESCE(
                        (SELECT SUM(ri.return_qty)
                         FROM return_items ri
                         JOIN returns r ON r.id = ri.return_id
                         WHERE ri.reference_item_id = soi.id
                           AND r.company_id = ?
                           AND r.status IN ('draft','in_transit','received')
                           {$subExcludeClause}
                        ), 0
                    ) AS already_returned_qty
             FROM sales_order_items soi
             JOIN products p ON p.id = soi.product_id
             WHERE soi.sales_order_id = ?
             ORDER BY soi.id ASC",
            $subParams
        );

        // Compute live delivered_qty from delivered DNs (soi.delivered_qty column is never written)
        $soItemIds = array_map(fn($i) => (int) $i->so_item_id, $soItems);
        $deliveredMap = [];
        if (!empty($soItemIds)) {
            $ph = implode(',', array_fill(0, count($soItemIds), '?'));
            $deliveredRows = $this->db->fetchAll(
                "SELECT sdi.sales_order_item_id, SUM(sdi.dispatched_qty) AS delivered_qty
                 FROM sales_delivery_items sdi
                 JOIN sales_deliveries sd ON sd.id = sdi.sales_delivery_id
                 WHERE sdi.sales_order_item_id IN ({$ph}) AND sd.company_id = ? AND sd.status = 'delivered'
                 GROUP BY sdi.sales_order_item_id",
                array_merge($soItemIds, [$companyId])
            );
            foreach ($deliveredRows as $row) {
                $deliveredMap[(int) $row->sales_order_item_id] = (float) $row->delivered_qty;
            }
        }

        foreach ($soItems as $item) {
            $item->delivered_qty        = $deliveredMap[(int) $item->so_item_id] ?? 0.0;
            $item->already_returned_qty = (float) $item->already_returned_qty;
            $item->available_return_qty = max(0.0, $item->delivered_qty - $item->already_returned_qty);
        }

        // For serial-tracked items, build available_serials: sold serials dispatched for each SO item
        // that are not already locked by another active/received return
        $availableSerialsMap = [];
        if (!empty($soItemIds)) {
            $ph = implode(',', array_fill(0, count($soItemIds), '?'));

            $excludeSql = "SELECT DISTINCT ris.serial_id
                           FROM return_item_serials ris
                           JOIN return_items ri ON ri.id = ris.return_item_id
                           JOIN returns r ON r.id = ris.return_id
                           WHERE r.company_id = ? AND r.status IN ('draft','in_transit')
                             AND ri.reference_item_id IN ({$ph})";
            $excludeParams = array_merge([$companyId], $soItemIds);
            if ($returnId > 0) {
                $excludeSql .= " AND r.id != ?";
                $excludeParams[] = $returnId;
            }
            $excludedRows      = $this->db->fetchAll($excludeSql, $excludeParams);
            $excludedSerialIds = array_map(fn($r) => (int) $r->serial_id, $excludedRows);

            $serialRows = $this->db->fetchAll(
                "SELECT DISTINCT sdis.serial_id AS id, ins.serial_number, sdi.sales_order_item_id
                 FROM sales_delivery_item_serials sdis
                 JOIN sales_delivery_items sdi ON sdi.id = sdis.sales_delivery_item_id
                 JOIN inv_serials ins ON ins.id = sdis.serial_id
                 WHERE sdi.sales_order_item_id IN ({$ph})
                   AND sdis.company_id = ?
                   AND ins.status = 'sold'",
                array_merge($soItemIds, [$companyId])
            );

            foreach ($serialRows as $row) {
                if (!in_array((int) $row->id, $excludedSerialIds)) {
                    $availableSerialsMap[(int) $row->sales_order_item_id][] = [
                        'id'            => (int) $row->id,
                        'serial_number' => $row->serial_number,
                    ];
                }
            }
        }

        foreach ($soItems as $item) {
            $item->available_serials = $availableSerialsMap[(int) $item->so_item_id] ?? [];
        }

        $dispositions = $this->db->fetchAll(
            "SELECT id, name, bucket, is_default FROM return_dispositions
             WHERE company_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC",
            [$companyId]
        );

        $reasons = $this->db->fetchAll(
            "SELECT id, name, is_default FROM return_reasons
             WHERE company_id = ? AND is_active = 1 ORDER BY sort_order ASC, id ASC",
            [$companyId]
        );

        $locations = $this->db->fetchAll(
            "SELECT id, name FROM company_locations WHERE company_id = ? AND status = 'active' ORDER BY name ASC",
            [$companyId]
        );

        // Build return_details for edit mode pre-fill
        if ($returnId > 0 && $existingReturn) {
            $existingItems = $this->db->fetchAll(
                "SELECT ri.id, ri.reference_item_id AS so_item_id, ri.return_qty,
                        ri.return_disposition_id, ri.return_reason_id, ri.notes
                 FROM return_items ri
                 WHERE ri.return_id = ?
                 ORDER BY ri.id ASC",
                [$returnId]
            );

            $serialRows = $this->db->fetchAll(
                "SELECT ris.return_item_id, ris.serial_id, ins.serial_number
                 FROM return_item_serials ris
                 JOIN inv_serials ins ON ins.id = ris.serial_id
                 WHERE ris.return_id = ?",
                [$returnId]
            );
            $serialsByItem = [];
            foreach ($serialRows as $s) {
                $serialsByItem[$s->return_item_id][] = ['serial_id' => (int) $s->serial_id, 'serial_number' => $s->serial_number];
            }
            foreach ($existingItems as $ei) {
                $ei->serials = $serialsByItem[$ei->id] ?? [];
            }

            $returnDetails = [
                'id'                   => $returnId,
                'return_number'        => $existingReturn->return_number,
                'return_date'          => $existingReturn->return_date,
                'received_location_id' => (int) $existingReturn->received_location_id,
                'notes'                => $existingReturn->notes,
                'items'                => $existingItems,
            ];
        }

        $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

        return [
            'so'                       => $so,
            'items'                    => $soItems,
            'dispositions'             => $dispositions,
            'reasons'                  => $reasons,
            'locations'                => $locations,
            'suggested_return_number'  => $returnId > 0 ? null : $seqService->nextPreview('sales_returns'),
            'return_details'           => $returnDetails,
        ];
    }


    public function update(int $returnId, array $data): void {

        if (!$this->context->canDo('sales_returns', 'write')) {
            throw new Service_Exception("You do not have permission to update returns", 403);
        }

        $companyId = $this->context->companyId;

        // --- 1. Load and validate the return ---
        $return = $this->getReturnOrFail($returnId);
        if ($return->status !== 'draft') {
            throw new Service_Exception("Only draft returns can be edited", 422);
        }

        $soId = (int) $return->reference_id;

        // --- 2. Validate header ---
        $returnDate = trim($data['return_date'] ?? '');
        if (empty($returnDate)) {
            throw new Service_Exception("Return date is required", 422, ['return_date' => 'Return date is required']);
        }

        $locationId = (int) ($data['received_location_id'] ?? 0);
        if ($locationId <= 0) {
            throw new Service_Exception("Received location is required", 422, ['received_location_id' => 'Received location is required']);
        }

        $returnNumber = trim($data['return_number'] ?? '') ?: $return->return_number;
        $notes        = trim($data['notes'] ?? '');

        // --- 3. Validate items ---
        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new Service_Exception("At least one return item is required", 422, ['items' => 'At least one return item is required']);
        }

        $soItemRows = $this->db->fetchAll(
            "SELECT soi.id, soi.product_id, soi.product_uom_id, soi.uom_code,
                    soi.unit_price, soi.line_total, soi.tax_amount, soi.ordered_qty,
                    soi.product_name, soi.product_sku, p.stock_tracking_method
             FROM sales_order_items soi
             JOIN products p ON p.id = soi.product_id
             WHERE soi.sales_order_id = ?",
            [$soId]
        );
        $soItemMap = [];
        foreach ($soItemRows as $row) {
            $soItemMap[(int) $row->id] = $row;
        }

        $soItemIds = array_keys($soItemMap);
        if (!empty($soItemIds)) {
            $ph = implode(',', array_fill(0, count($soItemIds), '?'));
            $deliveredRows = $this->db->fetchAll(
                "SELECT sdi.sales_order_item_id, SUM(sdi.dispatched_qty) AS delivered_qty
                 FROM sales_delivery_items sdi
                 JOIN sales_deliveries sd ON sd.id = sdi.sales_delivery_id
                 WHERE sdi.sales_order_item_id IN ({$ph}) AND sd.company_id = ? AND sd.status = 'delivered'
                 GROUP BY sdi.sales_order_item_id",
                array_merge($soItemIds, [$companyId])
            );
            foreach ($deliveredRows as $row) {
                $soItemMap[(int) $row->sales_order_item_id]->delivered_qty = (float) $row->delivered_qty;
            }
            foreach ($soItemMap as $row) {
                if (!isset($row->delivered_qty)) {
                    $row->delivered_qty = 0.0;
                }
            }
        }

        foreach ($items as $idx => $item) {
            $soItemId      = (int) ($item['so_item_id'] ?? 0);
            $returnQty     = (float) ($item['return_qty'] ?? 0);
            $dispositionId = (int) ($item['return_disposition_id'] ?? 0);

            if ($soItemId <= 0 || !isset($soItemMap[$soItemId])) {
                throw new Service_Exception("Item at position " . ($idx + 1) . " does not belong to this sales order", 422, ['items' => "Item at position " . ($idx + 1) . " does not belong to this sales order"]);
            }
            if ($returnQty <= 0) {
                throw new Service_Exception("Return quantity must be greater than zero for all items", 422, ['items' => 'Return quantity must be greater than zero for all items']);
            }
            if ($soItemMap[$soItemId]->stock_tracking_method !== 'none' && $dispositionId <= 0) {
                throw new Service_Exception("Disposition is required for all items", 422, ['items' => 'Disposition is required for all items']);
            }

            // Exclude current return so available reflects what others have taken
            $alreadyReturned = $this->getAlreadyReturnedQty($soItemId, $companyId, $returnId);
            $availableQty    = (float) $soItemMap[$soItemId]->delivered_qty - $alreadyReturned;

            if ($returnQty > $availableQty + 0.0001) {
                $msg = "Return quantity for '{$soItemMap[$soItemId]->product_name}' ({$returnQty}) exceeds available qty ({$availableQty})";
                throw new Service_Exception($msg, 422, ['items' => $msg]);
            }

            if ($soItemMap[$soItemId]->stock_tracking_method === 'serial') {
                $serialIds = $item['serial_ids'] ?? [];
                if (count($serialIds) !== (int) $returnQty) {
                    throw new Service_Exception("Serial count must match return quantity for serial-tracked items", 422, ['items' => 'Serial count must match return quantity for serial-tracked items']);
                }
                if (!empty($serialIds)) {
                    $placeholders = implode(',', array_fill(0, count($serialIds), '?'));
                    $validSerials = $this->db->fetchAll(
                        "SELECT sdis.serial_id
                         FROM sales_delivery_item_serials sdis
                         JOIN sales_delivery_items sdi ON sdi.id = sdis.sales_delivery_item_id
                         WHERE sdi.sales_order_item_id = ? AND sdis.serial_id IN ({$placeholders})",
                        array_merge([$soItemId], $serialIds)
                    );
                    if (count($validSerials) !== count($serialIds)) {
                        throw new Service_Exception("One or more serials were not dispatched for this item", 422, ['items' => 'One or more serials were not dispatched for this item']);
                    }
                }
            }
        }

        $dispositionIds = array_unique(array_column($items, 'return_disposition_id'));
        $dispositionIds = array_filter(array_map('intval', $dispositionIds));
        $placeholders   = implode(',', array_fill(0, count($dispositionIds), '?'));
        $validDisps     = $this->db->fetchAll(
            "SELECT id, bucket FROM return_dispositions WHERE id IN ({$placeholders}) AND company_id = ? AND is_active = 1",
            array_merge($dispositionIds, [$companyId])
        );
        if (count($validDisps) !== count($dispositionIds)) {
            throw new Service_Exception("One or more dispositions are invalid", 422, ['items' => 'One or more dispositions are invalid']);
        }
        $dispositionBucketMap = [];
        foreach ($validDisps as $d) {
            $dispositionBucketMap[(int) $d->id] = $d->bucket;
        }

        // --- 4. Update ---
        $this->db->startTransaction();

        try {
            $return->return_number        = $returnNumber;
            $return->return_date          = $returnDate;
            $return->received_location_id = $locationId;
            $return->notes                = $notes ?: null;
            $return->save();

            $this->db->query("DELETE FROM return_item_serials WHERE return_id = ?", [$returnId]);
            $this->db->query("DELETE FROM return_items WHERE return_id = ?", [$returnId]);

            foreach ($items as $item) {
                $soItemId      = (int) $item['so_item_id'];
                $soItem        = $soItemMap[$soItemId];
                $returnQty     = (float) $item['return_qty'];
                $dispositionId = (int) $item['return_disposition_id'];
                $reasonId      = !empty($item['return_reason_id']) ? (int) $item['return_reason_id'] : null;
                $itemNotes     = trim($item['notes'] ?? '') ?: null;

                $orderedQty    = max((float) $soItem->ordered_qty, 0.0001);
                $effectiveUnit = round(((float)$soItem->line_total - (float)$soItem->tax_amount) / $orderedQty, 4);
                $retTaxable    = round($effectiveUnit * $returnQty, 4);
                $retTax        = round((float)$soItem->tax_amount / $orderedQty * $returnQty, 4);
                $retLineTotal  = round($retTaxable + $retTax, 4);

                $returnItem = new Models_ReturnItem();
                $returnItem->company_id            = $companyId;
                $returnItem->return_id             = $returnId;
                $returnItem->reference_item_id     = $soItemId;
                $returnItem->product_id            = (int) $soItem->product_id;
                $returnItem->product_name          = $soItem->product_name;
                $returnItem->product_sku           = $soItem->product_sku;
                $returnItem->product_uom_id        = !empty($soItem->product_uom_id) ? (int) $soItem->product_uom_id : null;
                $returnItem->uom_code              = $soItem->uom_code;
                $returnItem->unit_price            = $effectiveUnit;
                $returnItem->taxable_amount        = $retTaxable;
                $returnItem->tax_amount            = $retTax;
                $returnItem->line_total            = $retLineTotal;
                $returnItem->return_qty            = $returnQty;
                $returnItem->return_disposition_id = $soItem->stock_tracking_method !== 'none' ? $dispositionId : null;
                $itemBucket = $soItem->stock_tracking_method !== 'none' ? ($dispositionBucketMap[$dispositionId] ?? 'unrestricted') : null;
                $returnItem->follow_up_status      = in_array($itemBucket, ['blocked', 'quality']) ? 'pending' : 'not_required';
                $returnItem->return_reason_id      = $reasonId;
                $returnItem->notes                 = $itemNotes;

                $returnItemId = $returnItem->create();
                if (!$returnItemId) {
                    throw new Service_Exception("Failed to update return item");
                }

                $serialIds = $item['serial_ids'] ?? [];
                foreach ($serialIds as $serialId) {
                    $this->db->query(
                        "INSERT INTO return_item_serials (company_id, return_id, return_item_id, serial_id) VALUES (?, ?, ?, ?)",
                        [$companyId, $returnId, $returnItemId, (int) $serialId]
                    );
                }
            }

            $this->writeHistory($returnId, 'updated', "Return {$returnNumber} updated", 'sales_order', $soId);

            if ($soId > 0) {
                $order = new Service_So_Order(new Service_TenantContext($this->context->companyId, $this->context->userId));
                $order->logHistory($soId, [
                    'log_type'       => 'return_updated',
                    'title'          => 'Customer return updated #' . $returnNumber,
                    'reference_type' => 'sales_return',
                    'reference_id'   => $returnId,
                    'meta'           => ['return_number' => $returnNumber],
                ]);
            }

            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function create(array $data): int {

        if (!$this->context->canDo('sales_returns', 'write')) {
            throw new Service_Exception("You do not have permission to create returns", 403);
        }

        $companyId = $this->context->companyId;
        $userId    = $this->context->userId;

        // --- 1. Validate header ---
        $status = (isset($data['status']) && $data['status'] === 'received') ? 'received' : 'draft';

        $soId = (int) ($data['so_id'] ?? 0);
        if ($soId <= 0) {
            throw new Service_Exception("A sales order is required", 422);
        }

        $returnDate = trim($data['return_date'] ?? '');
        if (empty($returnDate)) {
            throw new Service_Exception("Return date is required", 422, ['return_date' => 'Return date is required']);
        }

        $locationId = (int) ($data['received_location_id'] ?? 0);
        if ($locationId <= 0) {
            throw new Service_Exception("Received location is required", 422, ['received_location_id' => 'Received location is required']);
        }

        $notes = trim($data['notes'] ?? '');

        // --- 2. Load and validate sales order ---
        $so = $this->db->fetchOne(
            "SELECT so.id, so.so_number, so.status, so.customer_id,
                    c.display_name AS customer_name
             FROM sales_orders so
             JOIN customers c ON c.id = so.customer_id
             WHERE so.id = ? AND so.company_id = ?",
            [$soId, $companyId]
        );

        if (!$so) {
            throw new Service_Exception("Sales order not found", 404);
        }

        // --- 3. Validate items ---
        $items = $data['items'] ?? [];
        if (empty($items)) {
            throw new Service_Exception("At least one return item is required", 422, ['items' => 'At least one return item is required']);
        }

        // Load all SO item IDs for this order to verify membership
        $soItemRows = $this->db->fetchAll(
            "SELECT soi.id, soi.product_id, soi.product_uom_id, soi.uom_code,
                    soi.unit_price, soi.line_total, soi.tax_amount, soi.ordered_qty,
                    soi.product_name, soi.product_sku, p.stock_tracking_method
             FROM sales_order_items soi
             JOIN products p ON p.id = soi.product_id
             WHERE soi.sales_order_id = ?",
            [$soId]
        );
        $soItemMap = [];
        foreach ($soItemRows as $row) {
            $soItemMap[(int) $row->id] = $row;
        }

        // Compute live delivered_qty per SO item (soi.delivered_qty column is never written)
        $soItemIds = array_keys($soItemMap);
        if (!empty($soItemIds)) {
            $ph = implode(',', array_fill(0, count($soItemIds), '?'));
            $deliveredRows = $this->db->fetchAll(
                "SELECT sdi.sales_order_item_id, SUM(sdi.dispatched_qty) AS delivered_qty
                 FROM sales_delivery_items sdi
                 JOIN sales_deliveries sd ON sd.id = sdi.sales_delivery_id
                 WHERE sdi.sales_order_item_id IN ({$ph}) AND sd.company_id = ? AND sd.status = 'delivered'
                 GROUP BY sdi.sales_order_item_id",
                array_merge($soItemIds, [$companyId])
            );
            foreach ($deliveredRows as $row) {
                $soItemMap[(int) $row->sales_order_item_id]->delivered_qty = (float) $row->delivered_qty;
            }
            foreach ($soItemMap as $row) {
                if (!isset($row->delivered_qty)) {
                    $row->delivered_qty = 0.0;
                }
            }
        }

        foreach ($items as $idx => $item) {
            $soItemId      = (int) ($item['so_item_id'] ?? 0);
            $returnQty     = (float) ($item['return_qty'] ?? 0);
            $dispositionId = (int) ($item['return_disposition_id'] ?? 0);

            if ($soItemId <= 0 || !isset($soItemMap[$soItemId])) {
                throw new Service_Exception("Item at position " . ($idx + 1) . " does not belong to this sales order", 422, ['items' => "Item at position " . ($idx + 1) . " does not belong to this sales order"]);
            }

            if ($returnQty <= 0) {
                throw new Service_Exception("Return quantity must be greater than zero for all items", 422, ['items' => 'Return quantity must be greater than zero for all items']);
            }

            if ($soItemMap[$soItemId]->stock_tracking_method !== 'none' && $dispositionId <= 0) {
                throw new Service_Exception("Disposition is required for all items", 422, ['items' => 'Disposition is required for all items']);
            }

            $alreadyReturned = $this->getAlreadyReturnedQty($soItemId, $companyId);
            $availableQty    = (float) $soItemMap[$soItemId]->delivered_qty - $alreadyReturned;

            if ($returnQty > $availableQty + 0.0001) {
                $msg = "Return quantity for '{$soItemMap[$soItemId]->product_name}' ({$returnQty}) exceeds available qty ({$availableQty})";
                throw new Service_Exception($msg, 422, ['items' => $msg]);
            }

            // Serial validation: serials must have been dispatched for this SO item
            if ($soItemMap[$soItemId]->stock_tracking_method === 'serial') {
                $serialIds = $item['serial_ids'] ?? [];
                if (count($serialIds) !== (int) $returnQty) {
                    throw new Service_Exception(
                        "Serial count must match return quantity for serial-tracked items",
                        422,
                        ['items' => 'Serial count must match return quantity for serial-tracked items']
                    );
                }

                if (!empty($serialIds)) {
                    $placeholders = implode(',', array_fill(0, count($serialIds), '?'));
                    $validSerials = $this->db->fetchAll(
                        "SELECT sdis.serial_id
                         FROM sales_delivery_item_serials sdis
                         JOIN sales_delivery_items sdi ON sdi.id = sdis.sales_delivery_item_id
                         WHERE sdi.sales_order_item_id = ? AND sdis.serial_id IN ({$placeholders})",
                        array_merge([$soItemId], $serialIds)
                    );

                    if (count($validSerials) !== count($serialIds)) {
                        throw new Service_Exception(
                            "One or more serials were not dispatched for this item",
                            422,
                            ['items' => 'One or more serials were not dispatched for this item']
                        );
                    }
                }
            }
        }

        // Validate disposition IDs belong to this company
        $dispositionIds = array_unique(array_column($items, 'return_disposition_id'));
        $dispositionIds = array_filter(array_map('intval', $dispositionIds));
        $placeholders   = implode(',', array_fill(0, count($dispositionIds), '?'));
        $validDisps     = $this->db->fetchAll(
            "SELECT id, bucket FROM return_dispositions WHERE id IN ({$placeholders}) AND company_id = ? AND is_active = 1",
            array_merge($dispositionIds, [$companyId])
        );
        if (count($validDisps) !== count($dispositionIds)) {
            throw new Service_Exception("One or more dispositions are invalid", 422, ['items' => 'One or more dispositions are invalid']);
        }
        $dispositionBucketMap = [];
        foreach ($validDisps as $d) {
            $dispositionBucketMap[(int) $d->id] = $d->bucket;
        }

        // --- 4. Resolve return number ---
        $returnNumberInput     = trim($data['return_number'] ?? '');
        $returnNumberSuggested = trim($data['return_number_suggested'] ?? '');

        $this->db->startTransaction();

        try {

            if (empty($returnNumberInput) || $returnNumberInput === $returnNumberSuggested) {
                $seqService   = new Service_Sequence(new Service_TenantContext($companyId, $userId));
                $returnNumber = $seqService->nextCommit('sales_returns');
            } else {
                $returnNumber = $returnNumberInput;
            }

            // --- 5. Insert return header ---
            $return = new Models_Return();
            $return->company_id           = $companyId;
            $return->received_location_id = $locationId;
            $return->return_number        = $returnNumber;
            $return->return_type          = 'customer';
            $return->reference_type       = 'sales_order';
            $return->reference_id         = $soId;
            $return->party_type           = 'customer';
            $return->party_id             = (int) $so->customer_id;
            $return->return_date          = $returnDate;
            $return->status               = 'draft';
            $return->notes                = $notes ?: null;
            $return->created_by           = $userId;

            $returnId = $return->create();
            if (!$returnId) {
                throw new Service_Exception("Failed to create return record");
            }

            // --- 6. Insert items and serials ---
            $insertedItems = [];
            foreach ($items as $item) {
                $soItemId      = (int) $item['so_item_id'];
                $soItem        = $soItemMap[$soItemId];
                $returnQty     = (float) $item['return_qty'];
                $dispositionId = (int) $item['return_disposition_id'];
                $reasonId      = !empty($item['return_reason_id']) ? (int) $item['return_reason_id'] : null;
                $itemNotes     = trim($item['notes'] ?? '') ?: null;

                $orderedQty    = max((float) $soItem->ordered_qty, 0.0001);
                $effectiveUnit = round(((float)$soItem->line_total - (float)$soItem->tax_amount) / $orderedQty, 4);
                $retTaxable    = round($effectiveUnit * $returnQty, 4);
                $retTax        = round((float)$soItem->tax_amount / $orderedQty * $returnQty, 4);
                $retLineTotal  = round($retTaxable + $retTax, 4);

                $returnItem = new Models_ReturnItem();
                $returnItem->company_id             = $companyId;
                $returnItem->return_id              = $returnId;
                $returnItem->reference_item_id      = $soItemId;
                $returnItem->product_id             = (int) $soItem->product_id;
                $returnItem->product_name           = $soItem->product_name;
                $returnItem->product_sku            = $soItem->product_sku;
                $returnItem->product_uom_id         = !empty($soItem->product_uom_id) ? (int) $soItem->product_uom_id : null;
                $returnItem->uom_code               = $soItem->uom_code;
                $returnItem->unit_price             = $effectiveUnit;
                $returnItem->taxable_amount         = $retTaxable;
                $returnItem->tax_amount             = $retTax;
                $returnItem->line_total             = $retLineTotal;
                $returnItem->return_qty             = $returnQty;
                $returnItem->return_disposition_id  = $soItem->stock_tracking_method !== 'none' ? $dispositionId : null;
                $itemBucket = $soItem->stock_tracking_method !== 'none' ? ($dispositionBucketMap[$dispositionId] ?? 'unrestricted') : null;
                $returnItem->follow_up_status       = in_array($itemBucket, ['blocked', 'quality']) ? 'pending' : 'not_required';
                $returnItem->return_reason_id       = $reasonId;
                $returnItem->notes                  = $itemNotes;

                $returnItemId = $returnItem->create();
                if (!$returnItemId) {
                    throw new Service_Exception("Failed to create return item");
                }

                $insertedItems[] = (object) [
                    'id'                    => $returnItemId,
                    'product_id'            => (int) $soItem->product_id,
                    'return_qty'            => $returnQty,
                    'return_disposition_id' => $soItem->stock_tracking_method !== 'none' ? $dispositionId : null,
                    'reference_item_id'     => $soItemId,
                    'stock_tracking_method' => $soItem->stock_tracking_method,
                    'taxable_amount'        => $retTaxable,
                    'tax_amount'            => $retTax,
                    'line_total'            => $retLineTotal,
                ];

                $serialIds = $item['serial_ids'] ?? [];
                foreach ($serialIds as $serialId) {
                    $this->db->query(
                        "INSERT INTO return_item_serials (company_id, return_id, return_item_id, serial_id) VALUES (?, ?, ?, ?)",
                        [$companyId, $returnId, $returnItemId, (int) $serialId]
                    );
                }
            }

            // --- 7. Inventory processing if saving directly as received ---
            if ($status === 'received') {
                $dispIds = array_values(array_unique(array_filter(array_map(fn($i) => (int) $i->return_disposition_id, $insertedItems))));
                $dispMap = [];
                if (!empty($dispIds)) {
                    $ph2   = implode(',', array_fill(0, count($dispIds), '?'));
                    $disps = $this->db->fetchAll(
                        "SELECT id, bucket FROM return_dispositions WHERE id IN ({$ph2}) AND company_id = ?",
                        array_merge($dispIds, [$companyId])
                    );
                    foreach ($disps as $d) { $dispMap[(int) $d->id] = $d; }
                }

                foreach ($insertedItems as $insertedItem) {
                    if (($insertedItem->stock_tracking_method ?? '') === 'none') continue;
                    $disposition = $dispMap[(int) $insertedItem->return_disposition_id] ?? null;
                    if (!$disposition) {
                        throw new Service_Exception("Disposition not found for an item");
                    }
                    $this->processItemInventory($insertedItem, $disposition, 'customer', $locationId, $companyId, $userId, $returnId);
                }

                foreach ($insertedItems as $ins) {
                    if (!$ins->reference_item_id) continue;
                    $this->db->query(
                        "UPDATE sales_order_items SET returned_qty = returned_qty + ? WHERE id = ? AND sales_order_id = ?",
                        [(float) $ins->return_qty, (int) $ins->reference_item_id, $soId]
                    );
                }
                $retSubTotal  = array_sum(array_column((array) $insertedItems, 'taxable_amount'));
                $retTaxTotal  = array_sum(array_column((array) $insertedItems, 'tax_amount'));
                $retGrandTotal = array_sum(array_column((array) $insertedItems, 'line_total'));
                $this->db->query(
                    "UPDATE sales_orders SET
                        returned_subtotal    = returned_subtotal    + ?,
                        returned_tax_amount  = returned_tax_amount  + ?,
                        returned_grand_total = returned_grand_total + ?
                     WHERE id = ? AND company_id = ?",
                    [round($retSubTotal, 4), round($retTaxTotal, 4), round($retGrandTotal, 4), $soId, $companyId]
                );

                $this->db->query(
                    "UPDATE returns SET status = 'received', received_by = ?, received_at = NOW() WHERE id = ?",
                    [$userId, $returnId]
                );
                $this->writeHistory($returnId, 'received', "Return {$returnNumber} created and received — inventory updated", 'sales_order', $soId);
            } else {
                $this->writeHistory($returnId, 'created', "Return {$returnNumber} created from sales order {$so->so_number}", 'sales_order', $soId);
            }

            if ($soId > 0) {
                $order = new Service_So_Order(new Service_TenantContext($this->context->companyId, $this->context->userId));
                $order->logHistory($soId, [
                    'log_type'       => 'return_created',
                    'title'          => 'Customer return created #' . $returnNumber,
                    'reference_type' => 'sales_return',
                    'reference_id'   => $returnId,
                    'meta'           => ['return_number' => $returnNumber, 'return_status' => $status],
                ]);
            }

            $this->db->commit();

            return $returnId;

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function get(int $returnId): array {

        $companyId = $this->context->companyId;

        $return = $this->db->fetchOne(
            "SELECT r.*,
                    l.name AS received_location_name,
                    CASE WHEN r.party_type = 'customer' THEN c.display_name
                         WHEN r.party_type = 'vendor' THEN v.display_name
                    END AS party_name,
                    so.so_number,
                    u1.name AS created_by_name,
                    u2.name AS received_by_name,
                    u3.name AS cancelled_by_name
             FROM returns r
             LEFT JOIN company_locations l ON l.id = r.received_location_id
             LEFT JOIN customers c ON c.id = r.party_id AND r.party_type = 'customer'
             LEFT JOIN vendors v ON v.id = r.party_id AND r.party_type = 'vendor'
             LEFT JOIN sales_orders so ON so.id = r.reference_id AND r.reference_type = 'sales_order'
             LEFT JOIN users u1 ON u1.id = r.created_by
             LEFT JOIN users u2 ON u2.id = r.received_by
             LEFT JOIN users u3 ON u3.id = r.cancelled_by
             WHERE r.id = ? AND r.company_id = ?",
            [$returnId, $companyId]
        );

        if (!$return) {
            throw new Service_Exception("The requested return was not found", 404);
        }

        $items = $this->db->fetchAll(
            "SELECT ri.*,
                    rd.name AS disposition_name, rd.bucket AS disposition_bucket,
                    rr.name AS reason_name
             FROM return_items ri
             LEFT JOIN return_dispositions rd ON rd.id = ri.return_disposition_id
             LEFT JOIN return_reasons rr ON rr.id = ri.return_reason_id
             WHERE ri.return_id = ? ORDER BY ri.id ASC",
            [$returnId]
        );

        $serials = $this->db->fetchAll(
            "SELECT ris.return_item_id, ris.serial_id, ins.serial_number
             FROM return_item_serials ris
             JOIN inv_serials ins ON ins.id = ris.serial_id
             WHERE ris.return_id = ?",
            [$returnId]
        );

        $lots = $this->db->fetchAll(
            "SELECT ril.return_item_id, ril.lot_id, ril.quantity, il.lot_number
             FROM return_item_lots ril
             JOIN inv_lots il ON il.id = ril.lot_id
             WHERE ril.return_id = ?",
            [$returnId]
        );

        $history = $this->db->fetchAll(
            "SELECT rh.*, u.name AS created_by_name
             FROM return_history rh
             LEFT JOIN users u ON u.id = rh.created_by
             WHERE rh.return_id = ? ORDER BY rh.id DESC",
            [$returnId]
        );

        $serialsByItem = [];
        foreach ($serials as $s) {
            $serialsByItem[$s->return_item_id][] = $s;
        }

        $lotsByItem = [];
        foreach ($lots as $l) {
            $lotsByItem[$l->return_item_id][] = $l;
        }

        foreach ($items as $item) {
            $item->serials = $serialsByItem[$item->id] ?? [];
            $item->lots    = $lotsByItem[$item->id] ?? [];
        }

        return [
            'return'  => $return,
            'items'   => $items,
            'history' => $history,
        ];
    }


    public function markInTransit(int $returnId): array {

        if (!$this->context->canDo('sales_returns', 'write')) {
            throw new Service_Exception("You do not have permission to update returns", 403);
        }

        $return = $this->getReturnOrFail($returnId);

        if ($return->status !== 'draft') {
            throw new Service_Exception("Only draft returns can be marked as in transit", 422);
        }

        $this->db->startTransaction();

        try {

            $return->status = 'in_transit';
            if (!$return->update()) {
                throw new Service_Exception("Failed to update return status");
            }

            $this->writeHistory($returnId, 'status_changed', "Return {$return->return_number} marked as in transit", (string) $return->reference_type, (int) $return->reference_id);

            if ($return->reference_type === 'sales_order' && (int) $return->reference_id > 0) {
                $order = new Service_So_Order(new Service_TenantContext($this->context->companyId, $this->context->userId));
                $order->logHistory((int) $return->reference_id, [
                    'log_type'       => 'return_status_changed',
                    'title'          => 'Customer return #' . $return->return_number . ' marked as in transit',
                    'reference_type' => 'sales_return',
                    'reference_id'   => $returnId,
                    'meta'           => ['return_number' => $return->return_number, 'new_status' => 'in_transit'],
                ]);
            }

            $this->db->commit();

            return ["success" => true, "data" => []];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function receive(int $returnId): array {

        if (!$this->context->canDo('sales_returns', 'write')) {
            throw new Service_Exception("You do not have permission to receive returns", 403);
        }

        $return    = $this->getReturnOrFail($returnId);
        $companyId = $this->context->companyId;
        $userId    = $this->context->userId;

        if (!in_array($return->status, ['draft', 'in_transit'])) {
            throw new Service_Exception("Only draft or in-transit returns can be received", 422);
        }

        $items = $this->db->fetchAll(
            "SELECT * FROM return_items WHERE return_id = ? AND company_id = ?",
            [$returnId, $companyId]
        );

        if (empty($items)) {
            throw new Service_Exception("Cannot receive a return with no items", 422);
        }

        foreach ($items as $item) {
            if ($item->reference_item_id) {
                $deliveredQty    = $this->getDeliveredQty($return->reference_type, (int) $item->reference_item_id, $companyId);
                $alreadyReturned = $this->getAlreadyReturnedQty((int) $item->reference_item_id, $companyId, $returnId);
                $availableQty    = $deliveredQty - $alreadyReturned;

                if ((float) $item->return_qty > $availableQty + 0.0001) {
                    throw new Service_Exception(
                        "Return quantity for product ID {$item->product_id} ({$item->return_qty}) exceeds available quantity ({$availableQty}). Another return may have been processed.",
                        422
                    );
                }
            }

            if ($return->return_type === 'customer') {
                $product = $this->db->fetchOne(
                    "SELECT stock_tracking_method FROM products WHERE id = ? AND company_id = ?",
                    [(int) $item->product_id, $companyId]
                );

                if ($product && $product->stock_tracking_method === 'serial') {
                    $serials = $this->db->fetchAll(
                        "SELECT ris.serial_id, ins.serial_number, ins.status
                         FROM return_item_serials ris
                         JOIN inv_serials ins ON ins.id = ris.serial_id
                         WHERE ris.return_item_id = ? AND ris.company_id = ?",
                        [$item->id, $companyId]
                    );

                    foreach ($serials as $s) {
                        if ($s->status !== 'sold') {
                            throw new Service_Exception(
                                "Serial {$s->serial_number} has status '{$s->status}' and cannot be returned. Only serials with status 'sold' are eligible for customer returns.",
                                422
                            );
                        }
                    }
                }
            }
        }

        $dispositionIds = array_values(array_unique(array_filter(array_map('intval', array_column($items, 'return_disposition_id')))));
        $dispositionMap = [];
        if (!empty($dispositionIds)) {
            $placeholders = implode(',', array_fill(0, count($dispositionIds), '?'));
            $dispositions = $this->db->fetchAll(
                "SELECT id, bucket FROM return_dispositions WHERE id IN ({$placeholders}) AND company_id = ?",
                array_merge($dispositionIds, [$companyId])
            );
            foreach ($dispositions as $d) {
                $dispositionMap[(int) $d->id] = $d;
            }
        }

        $locationId = (int) $return->received_location_id;

        $this->db->startTransaction();

        try {

            foreach ($items as $item) {
                if (empty($item->return_disposition_id)) continue; // non-stock tracked item
                $disposition = $dispositionMap[(int) $item->return_disposition_id] ?? null;
                if (!$disposition) {
                    throw new Service_Exception("Disposition not found for item ID {$item->id}");
                }

                $this->processItemInventory($item, $disposition, $return->return_type, $locationId, $companyId, $userId, $returnId);
            }

            // Update returned_qty on each SO item and SO header aggregates
            if ($return->reference_type === 'sales_order') {
                foreach ($items as $item) {
                    if (!$item->reference_item_id) continue;
                    $this->db->query(
                        "UPDATE sales_order_items SET returned_qty = returned_qty + ? WHERE id = ? AND sales_order_id = ?",
                        [(float) $item->return_qty, (int) $item->reference_item_id, (int) $return->reference_id]
                    );
                }
                $this->db->query(
                    "UPDATE sales_orders SET
                        returned_subtotal    = returned_subtotal    + COALESCE((SELECT SUM(taxable_amount) FROM return_items WHERE return_id = ? AND company_id = ?), 0),
                        returned_tax_amount  = returned_tax_amount  + COALESCE((SELECT SUM(tax_amount)     FROM return_items WHERE return_id = ? AND company_id = ?), 0),
                        returned_grand_total = returned_grand_total + COALESCE((SELECT SUM(line_total)     FROM return_items WHERE return_id = ? AND company_id = ?), 0)
                     WHERE id = ? AND company_id = ?",
                    [$returnId, $companyId, $returnId, $companyId, $returnId, $companyId, (int) $return->reference_id, $companyId]
                );
            }

            $return->status      = 'received';
            $return->received_by = $userId;
            $return->received_at = date("Y-m-d H:i:s");

            if (!$return->update()) {
                throw new Service_Exception("Failed to update return status");
            }

            $this->writeHistory($returnId, 'received', "Return {$return->return_number} received — inventory updated", (string) $return->reference_type, (int) $return->reference_id);

            if ($return->reference_type === 'sales_order' && (int) $return->reference_id > 0) {
                $order = new Service_So_Order(new Service_TenantContext($this->context->companyId, $this->context->userId));
                $order->logHistory((int) $return->reference_id, [
                    'log_type'       => 'return_status_changed',
                    'title'          => 'Customer return #' . $return->return_number . ' received — inventory updated',
                    'reference_type' => 'sales_return',
                    'reference_id'   => $returnId,
                    'meta'           => ['return_number' => $return->return_number, 'new_status' => 'received'],
                ]);
            }

            $this->db->commit();

            return ["success" => true, "data" => []];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function cancel(int $returnId): array {

        if (!$this->context->canDo('sales_returns', 'write')) {
            throw new Service_Exception("You do not have permission to cancel returns", 403);
        }

        $return = $this->getReturnOrFail($returnId);

        if (!in_array($return->status, ['draft', 'in_transit'])) {
            throw new Service_Exception("Only draft or in-transit returns can be cancelled", 422);
        }

        $this->db->startTransaction();

        try {

            $return->status       = 'cancelled';
            $return->cancelled_by = $this->context->userId;
            $return->cancelled_at = date("Y-m-d H:i:s");

            if (!$return->update()) {
                throw new Service_Exception("Failed to cancel return");
            }

            $this->writeHistory($returnId, 'cancelled', "Return {$return->return_number} cancelled", (string) $return->reference_type, (int) $return->reference_id);

            if ($return->reference_type === 'sales_order' && (int) $return->reference_id > 0) {
                $order = new Service_So_Order(new Service_TenantContext($this->context->companyId, $this->context->userId));
                $order->logHistory((int) $return->reference_id, [
                    'log_type'       => 'return_status_changed',
                    'title'          => 'Customer return #' . $return->return_number . ' cancelled',
                    'reference_type' => 'sales_return',
                    'reference_id'   => $returnId,
                    'meta'           => ['return_number' => $return->return_number, 'new_status' => 'cancelled'],
                ]);
            }

            $this->db->commit();

            return ["success" => true, "data" => []];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function processFollowUp(int $returnItemId, string $action, float $qty, ?string $notes): void {

        if (!$this->context->canDo('sales_returns', 'write')) {
            throw new Service_Exception("You do not have permission to process follow-ups", 403);
        }

        $companyId = $this->context->companyId;
        $userId    = $this->context->userId;

        // --- 1. Load item ---
        $item = $this->db->fetchOne(
            "SELECT ri.*, r.status AS return_status, r.received_location_id,
                    r.return_number, rd.bucket AS disposition_bucket,
                    p.stock_tracking_method
             FROM return_items ri
             JOIN returns r ON r.id = ri.return_id
             LEFT JOIN return_dispositions rd ON rd.id = ri.return_disposition_id
             JOIN products p ON p.id = ri.product_id
             WHERE ri.id = ? AND ri.company_id = ?",
            [$returnItemId, $companyId]
        );

        // --- 2. Validate ---
        if (!$item) {
            throw new Service_Exception("Return item not found", 404);
        }
        if ($item->return_status !== 'received') {
            throw new Service_Exception("Follow-up can only be processed on received returns", 422);
        }
        if ($item->follow_up_status !== 'pending') {
            throw new Service_Exception("This item does not require follow-up or has already been processed", 422);
        }
        if (!in_array($action, ['restock', 'scrap'], true)) {
            throw new Service_Exception("Invalid action", 422);
        }
        if (!in_array($item->disposition_bucket, ['blocked', 'quality'], true)) {
            throw new Service_Exception("Follow-up is only applicable for blocked or quality items", 422);
        }

        $remainingQty = round((float) $item->return_qty - (float) $item->follow_up_processed_qty, 4);
        if ($qty <= 0 || $qty > $remainingQty + 0.0001) {
            throw new Service_Exception("Quantity must be greater than 0 and at most {$remainingQty}", 422);
        }

        // Serial items must process all remaining at once — no picker to choose specific serials
        if ($item->stock_tracking_method === 'serial' && abs($qty - $remainingQty) > 0.0001) {
            throw new Service_Exception("Serial-tracked items must process the full remaining quantity at once", 422);
        }

        $locationId  = (int) $item->received_location_id;
        $productId   = (int) $item->product_id;
        $returnId    = (int) $item->return_id;
        $productName = $item->product_name ?? "Product #{$productId}";
        $bucket      = $item->disposition_bucket;
        $stockCol    = $bucket === 'blocked' ? 'blocked_qty' : 'quality_qty';

        // --- 3. Write ---
        $this->db->startTransaction();
        try {

            $stock = $this->db->fetchOne(
                "SELECT id, unrestricted_qty, {$stockCol} AS bucket_qty
                 FROM inv_product_stock
                 WHERE company_id = ? AND location_id = ? AND product_id = ?",
                [$companyId, $locationId, $productId]
            );

            if ($action === 'restock') {
                $movementType    = $bucket === 'blocked' ? 'from_blocked' : 'from_quality';
                $oldUnrestricted = $stock ? (float) $stock->unrestricted_qty : 0.0;
                $newUnrestricted = $oldUnrestricted + $qty;
                $newBucketQty    = $stock ? max(0, (float) $stock->bucket_qty - $qty) : 0.0;

                if ($stock) {
                    $this->db->query(
                        "UPDATE inv_product_stock SET unrestricted_qty = ?, {$stockCol} = ? WHERE id = ?",
                        [$newUnrestricted, $newBucketQty, $stock->id]
                    );
                }

                $this->db->query(
                    "INSERT INTO inv_stock_movements (company_id, location_id, product_id, movement_type, old_qty, qty_change, new_qty, reference_type, reference_id, notes, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'return', ?, ?, ?)",
                    [$companyId, $locationId, $productId, $movementType, $oldUnrestricted, $qty, $newUnrestricted, $returnId, $notes, $userId]
                );

                if ($item->stock_tracking_method === 'serial') {
                    $serials = $this->db->fetchAll(
                        "SELECT serial_id FROM return_item_serials WHERE return_item_id = ? AND company_id = ?",
                        [$returnItemId, $companyId]
                    );
                    foreach ($serials as $s) {
                        $this->db->query(
                            "UPDATE inv_serials SET status = 'in_stock', updated_at = NOW() WHERE id = ? AND company_id = ?",
                            [$s->serial_id, $companyId]
                        );
                    }
                }

            } else {
                $oldBucketQty = $stock ? (float) $stock->bucket_qty : 0.0;
                $newBucketQty = max(0, $oldBucketQty - $qty);

                if ($stock) {
                    $this->db->query(
                        "UPDATE inv_product_stock SET {$stockCol} = ? WHERE id = ?",
                        [$newBucketQty, $stock->id]
                    );
                }

                $this->db->query(
                    "INSERT INTO inv_stock_movements (company_id, location_id, product_id, movement_type, old_qty, qty_change, new_qty, reference_type, reference_id, notes, created_by)
                     VALUES (?, ?, ?, ?, ?, ?, ?, 'return', ?, ?, ?)",
                    [$companyId, $locationId, $productId, 'scrap', $oldBucketQty, -$qty, $newBucketQty, $returnId, $notes, $userId]
                );

                if ($item->stock_tracking_method === 'serial') {
                    $serials = $this->db->fetchAll(
                        "SELECT serial_id FROM return_item_serials WHERE return_item_id = ? AND company_id = ?",
                        [$returnItemId, $companyId]
                    );
                    foreach ($serials as $s) {
                        $this->db->query(
                            "UPDATE inv_serials SET status = 'scrapped', updated_at = NOW() WHERE id = ? AND company_id = ?",
                            [$s->serial_id, $companyId]
                        );
                    }
                }
            }

            $newProcessedQty = round((float) $item->follow_up_processed_qty + $qty, 4);
            $newStatus       = $newProcessedQty >= $remainingQty + (float) $item->follow_up_processed_qty - 0.0001 ? 'completed' : 'pending';

            $this->db->query(
                "UPDATE return_items SET follow_up_status = ?, follow_up_processed_qty = ?, updated_at = NOW() WHERE id = ? AND company_id = ?",
                [$newStatus, $newProcessedQty, $returnItemId, $companyId]
            );

            $actionLabel = $action === 'restock' ? 'restocked to stock' : 'scrapped';
            $this->writeHistory($returnId, 'follow_up', "Follow-up: {$qty} × {$productName} {$actionLabel}", 'return', $returnId);

            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }
}
?>
