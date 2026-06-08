<?php
class Service_Manufacturing_Order extends Service_Base
{
    private function getOrFail(int $id): Models_ManufacturingOrder
    {
        $mo = new Models_ManufacturingOrder($id);
        if ($mo->isEmpty) {
            throw new Service_Exception("The requested manufacturing order was not found", 404);
        }
        if ($mo->company_id != $this->context->companyId) {
            throw new Service_Exception("You do not have permission to access this manufacturing order", 403);
        }
        return $mo;
    }

    private function addHistory(int $moId, string $logType, string $title, ?string $referenceType = null, ?int $referenceId = null, ?array $meta = null): void
    {
        $h = new Models_ManufacturingOrderHistory();
        $h->manufacturing_order_id = $moId;
        $h->company_id = $this->context->companyId;
        $h->log_type = $logType;
        $h->title = $title;
        $h->reference_type = $referenceType;
        $h->reference_id = $referenceId;
        $h->meta = $meta ? json_encode($meta) : null;
        $h->created_by = $this->context->userId;
        if (!$h->create()) {
            throw new Service_Exception("Failed to log manufacturing order history");
        }
    }


    private function validateProductAndBom(array $payload): void
    {
        $companyId = $this->context->companyId;
        $productId = (int) ($payload['product_id'] ?? 0);
        $bomId = (int) ($payload['bom_id'] ?? 0);

        if (!$productId) {
            $this->addError(validationErrMsg("required", "Finished product"), "product_id");
        } else {
            $product = new Models_Product($productId);
            if ($product->isEmpty || $product->company_id != $companyId || $product->status != 'active') {
                $this->addError(validationErrMsg("missing_or_invalid", "Finished product"), "product_id");
            }
        }

        if (!$bomId) {
            $this->addError(validationErrMsg("required", "Bill of Materials"), "bom_id");
        } else {
            $bom = new Models_ManufacturingBom($bomId);
            if ($bom->isEmpty || $bom->company_id != $companyId || $bom->status != 'active') {
                $this->addError(validationErrMsg("missing_or_invalid", "Bill of Materials"), "bom_id");
            } elseif ($productId && $bom->product_id != $productId) {
                $this->addError("The selected BOM does not belong to the chosen product", "bom_id");
            }
        }
    }

    private function validateCommonFields(array $payload): void
    {
        $companyId = $this->context->companyId;
        $locationId = (int) ($payload['source_location_id'] ?? 0);
        $destLocationId = (int) ($payload['destination_location_id'] ?? 0);
        $plannedQty = (float) ($payload['planned_qty'] ?? 0);
        $plannedDate = trim($payload['planned_date'] ?? '');

        if (!$locationId) {
            $this->addError(validationErrMsg("required", "Source warehouse"), "source_location_id");
        } else {
            $location = new Models_Location($locationId);
            if ($location->isEmpty || $location->company_id != $companyId || $location->status != 'active') {
                $this->addError(validationErrMsg("missing_or_invalid", "Source warehouse"), "source_location_id");
            }
        }

        if (!$destLocationId) {
            $this->addError(validationErrMsg("required", "Destination warehouse"), "destination_location_id");
        } else {
            $destLocation = new Models_Location($destLocationId);
            if ($destLocation->isEmpty || $destLocation->company_id != $companyId || $destLocation->status != 'active') {
                $this->addError(validationErrMsg("missing_or_invalid", "Destination warehouse"), "destination_location_id");
            }
        }

        if (!isPositiveNumeric($plannedQty)) {
            $this->addError("Planned quantity must be greater than zero", "planned_qty");
        } else {
            $productId = (int) ($payload['product_id'] ?? 0);
            if ($productId) {
                $uomRow = $this->db->fetchOne(
                    "SELECT u.allow_decimal, u.name FROM products p JOIN uoms u ON u.id = p.base_uom_id WHERE p.id = ? AND p.company_id = ?",
                    [$productId, $companyId]
                );
                if ($uomRow && !(bool)(int)$uomRow->allow_decimal && !isWholeNumber($plannedQty)) {
                    $this->addError("Planned quantity must be a whole number for {$uomRow->name}", "planned_qty");
                }
            }
        }

        if (!empty($plannedDate) && !strtotime($plannedDate)) {
            $this->addError(validationErrMsg("invalid", "Scheduled date"), "planned_date");
        }
    }

    private function snapshotMaterialItems(Models_ManufacturingOrder $mo, Models_ManufacturingBom $bom): void
    {
        $plannedQty = (float) $mo->planned_qty;
        $outputQty = (float) $bom->output_qty ?: 1;
        $sort = 0;

        foreach ($bom->items as $bomItem) {

            $itemPlannedQty = ($plannedQty / $outputQty) * (float) $bomItem->qty;

            $mi = new Models_ManufacturingOrderMaterialItem();
            $mi->company_id = $mo->company_id;
            $mi->manufacturing_order_id = $mo->id;
            $mi->product_id = (int) $bomItem->product_id;
            $mi->planned_qty = $itemPlannedQty;
            $mi->product_uom_id = $bomItem->product_uom_id ?: null;
            $mi->uom_code = $bomItem->uom_code ?: null;
            $mi->notes = $bomItem->notes ?: null;
            $mi->sort_order = $sort;

            if (!$mi->create()) {
                throw new Service_Exception("Failed to save material item");
            }

            $sort++;
        }
    }

    private function getStockWarningsForMaterialItems(Models_ManufacturingOrder $mo): array
    {
        $companyId  = $this->context->companyId;
        $locationId = (int) $mo->source_location_id;
        $warnings   = [];

        foreach ($mo->material_items as $mi) {
            $productId  = (int) $mi->product_id;
            $plannedQty = (float) $mi->planned_qty;

            $product = new Models_Product($productId);
            if ($product->isEmpty || $product->stock_tracking_method === 'none') {
                continue;
            }

            $stock = $this->db->fetchOne(
                "SELECT on_hand_qty, reserved_qty FROM inv_product_stock
                 WHERE company_id = ? AND location_id = ? AND product_id = ? LIMIT 1",
                [$companyId, $locationId, $productId]
            );

            $onHand    = $stock ? (float) $stock->on_hand_qty  : 0.0;
            $reserved  = $stock ? (float) $stock->reserved_qty : 0.0;
            $available = $onHand - $reserved;

            if ($available < $plannedQty) {
                $productName    = $product->name ?: "Product #{$productId}";
                $reservedSuffix = $reserved > 0 ? ' (' . formatQty($reserved) . ' reserved)' : '';
                $warnings[]     = "{$productName} — required " . formatQty($plannedQty) . ", on hand " . formatQty($onHand) . $reservedSuffix;
            }
        }

        return $warnings;
    }


    private function calcItemAllocatedQty(int $materialItemId): float
    {
        $row = $this->db->fetchOne(
            "SELECT COALESCE(SUM(ami.allocated_qty), 0) AS total
             FROM manufacturing_order_material_allocation_items AS ami
             JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             WHERE ami.material_item_id = ?",
            [$materialItemId]
        );
        return (float) ($row->total ?? 0);
    }

    private function calcItemEffectiveAllocatedQty(int $materialItemId, int $moId): float
    {
        $allocated = $this->calcItemAllocatedQty($materialItemId);

        // How many units were consumed in production for this material item
        $cRow = $this->db->fetchOne(
            "SELECT COALESCE(SUM(consumed_qty), 0) + COUNT(CASE WHEN serial_id IS NOT NULL THEN 1 END) AS total
             FROM manufacturing_order_output_consumptions
             WHERE manufacturing_order_id = ? AND material_item_id = ?",
            [$moId, $materialItemId]
        );
        $totalConsumed = (float) ($cRow->total ?? 0);

        // How many units have been returned in total
        $rRow = $this->db->fetchOne(
            "SELECT COALESCE(SUM(returned_qty), 0) AS total
             FROM manufacturing_order_material_return_items
             WHERE manufacturing_order_id = ? AND material_item_id = ?",
            [$moId, $materialItemId]
        );
        $totalReturned = (float) ($rRow->total ?? 0);

        // Reserved-type returns already decremented reserved_qty directly;
        // subtract them from allocated so delta calculations don't double-count.
        $reservedReturns = max(0.0, $totalReturned - $totalConsumed);
        return max(0.0, $allocated - $reservedReturns);
    }

    private function calcItemsEffectiveAllocatedQty(array $materialItemIds, int $moId): array
    {
        if (empty($materialItemIds)) return [];

        $ph = implode(',', array_fill(0, count($materialItemIds), '?'));

        $allocRows = $this->db->fetchAll(
            "SELECT ami.material_item_id, COALESCE(SUM(ami.allocated_qty), 0) AS total
             FROM manufacturing_order_material_allocation_items AS ami
             JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             WHERE ami.material_item_id IN ($ph)
             GROUP BY ami.material_item_id",
            $materialItemIds
        );
        $allocMap = [];
        foreach ($allocRows as $r) {
            $allocMap[(int) $r->material_item_id] = (float) $r->total;
        }

        $consumedRows = $this->db->fetchAll(
            "SELECT material_item_id,
                    COALESCE(SUM(consumed_qty), 0) + COUNT(CASE WHEN serial_id IS NOT NULL THEN 1 END) AS total
             FROM manufacturing_order_output_consumptions
             WHERE manufacturing_order_id = ? AND material_item_id IN ($ph)
             GROUP BY material_item_id",
            array_merge([$moId], $materialItemIds)
        );
        $consumedMap = [];
        foreach ($consumedRows as $r) {
            $consumedMap[(int) $r->material_item_id] = (float) $r->total;
        }

        $returnedRows = $this->db->fetchAll(
            "SELECT material_item_id, COALESCE(SUM(returned_qty), 0) AS total
             FROM manufacturing_order_material_return_items
             WHERE manufacturing_order_id = ? AND material_item_id IN ($ph)
             GROUP BY material_item_id",
            array_merge([$moId], $materialItemIds)
        );
        $returnedMap = [];
        foreach ($returnedRows as $r) {
            $returnedMap[(int) $r->material_item_id] = (float) $r->total;
        }

        $result = [];
        foreach ($materialItemIds as $miId) {
            $miId            = (int) $miId;
            $allocated       = $allocMap[$miId] ?? 0.0;
            $consumed        = $consumedMap[$miId] ?? 0.0;
            $returned        = $returnedMap[$miId] ?? 0.0;
            $reservedReturns = max(0.0, $returned - $consumed);
            $result[$miId]   = max(0.0, $allocated - $reservedReturns);
        }

        return $result;
    }


    private function createAllocationItem(int $allocId, int $moId, int $companyId, int $miId, int $productId, float $qty): Models_ManufacturingOrderMaterialAllocationItem
    {
        $item = new Models_ManufacturingOrderMaterialAllocationItem();
        $item->company_id             = $companyId;
        $item->allocation_id          = $allocId;
        $item->manufacturing_order_id = $moId;
        $item->material_item_id       = $miId;
        $item->product_id             = $productId;
        $item->allocated_qty          = $qty;
        if (!$item->create()) {
            throw new Service_Exception("Failed to save allocation item");
        }
        return $item;
    }

    // ----------------------------------------------------------------
    // Public API
    // ----------------------------------------------------------------

    public function getFormContext(): array
    {
        if (!$this->context->canDo('manufacturing_orders', 'read')) {
            throw new Service_Exception("You do not have permission to view manufacturing orders", 403);
        }

        $companyId = $this->context->companyId;

        $sql = "SELECT
                    p.id AS product_id, p.name AS product_name, p.sku,
                    b.id AS bom_id, b.name AS bom_name, b.output_qty,
                    COUNT(bi.id) AS component_count
                FROM manufacturing_boms AS b
                JOIN products AS p ON p.id = b.product_id
                LEFT JOIN manufacturing_bom_items AS bi ON bi.bom_id = b.id
                WHERE b.company_id = ? AND b.status = 'active' AND p.status = 'active'
                GROUP BY b.id
                ORDER BY p.name ASC, b.name ASC";
        $rows = $this->db->fetchAll($sql, [$companyId]);

        $products = [];
        foreach ($rows as $row) {
            $pid = (int) $row->product_id;
            if (!isset($products[$pid])) {
                $products[$pid] = [
                    'id'   => $pid,
                    'name' => $row->product_name,
                    'sku'  => $row->sku,
                    'boms' => [],
                ];
            }
            $products[$pid]['boms'][] = [
                'id'              => (int) $row->bom_id,
                'name'            => $row->bom_name,
                'output_qty'      => (float) $row->output_qty,
                'component_count' => (int) $row->component_count,
            ];
        }

        $locationSql = "SELECT id, name, code, type FROM company_locations WHERE company_id = ? AND status = 'active' ORDER BY name ASC";
        $locationRows = $this->db->fetchAll($locationSql, [$companyId]);

        $locations = array_map(function($loc) {
            return [
                'id'   => (int) $loc->id,
                'name' => $loc->name . ($loc->code ? ' (' . $loc->code . ')' : ''),
                'type' => $loc->type,
            ];
        }, $locationRows);

        return ['products' => array_values($products), 'locations' => $locations];
    }

    public function getDetails(int $id): array
    {
        if (!$this->context->canDo('manufacturing_orders', 'read')) {
            throw new Service_Exception("You do not have permission to view manufacturing orders", 403);
        }

        $mo = $this->getOrFail($id);

        $meta = $this->db->fetchOne(
            "SELECT
                src_loc.name  AS source_location_name,
                dest_loc.name AS destination_location_name,
                u.name        AS created_by_name,
                p.stock_tracking_method AS product_stock_tracking_method
             FROM manufacturing_orders AS mo
             LEFT JOIN company_locations AS src_loc  ON src_loc.id  = mo.source_location_id
             LEFT JOIN company_locations AS dest_loc ON dest_loc.id = mo.destination_location_id
             LEFT JOIN users             AS u         ON u.id        = mo.created_by
             LEFT JOIN products          AS p         ON p.id        = mo.product_id
             WHERE mo.id = ?",
            [$id]
        );

        // Allocated qty per material item from ACTIVE allocations — single query, no UNION
        $allocSummaryRows = $this->db->fetchAll(
            "SELECT ami.material_item_id, SUM(ami.allocated_qty) AS qty_allocated
             FROM manufacturing_order_material_allocation_items AS ami
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             WHERE ami.manufacturing_order_id = ?
             GROUP BY ami.material_item_id",
            [$id]
        );
        $allocByItem = [];
        foreach ($allocSummaryRows as $r) {
            $allocByItem[(int) $r->material_item_id] = (float) $r->qty_allocated;
        }

        // Returned qty per material item — subtracted from allocated to get net allocated
        $returnSummaryRows = $this->db->fetchAll(
            "SELECT ri.material_item_id, SUM(ri.returned_qty) AS qty_returned
             FROM manufacturing_order_material_return_items AS ri
             WHERE ri.manufacturing_order_id = ?
             GROUP BY ri.material_item_id",
            [$id]
        );
        $returnedByItem = [];
        foreach ($returnSummaryRows as $r) {
            $returnedByItem[(int) $r->material_item_id] = (float) $r->qty_returned;
        }

        $materialItems = $mo->material_items;

        // Reserved serials per material item (for Record Production drawer)
        $reservedSerialsByItem = [];
        $rsvSerialRows = $this->db->fetchAll(
            "SELECT ami.material_item_id, ams.serial_id, s.serial_number
             FROM manufacturing_order_material_allocation_serials AS ams
             INNER JOIN manufacturing_order_material_allocation_items AS ami ON ami.id = ams.allocation_item_id
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             INNER JOIN inv_serials AS s ON s.id = ams.serial_id AND s.status = 'reserved'
             WHERE ami.manufacturing_order_id = ?
             ORDER BY ami.material_item_id ASC, ams.id ASC",
            [$id]
        );
        foreach ($rsvSerialRows as $r) {
            $reservedSerialsByItem[(int) $r->material_item_id][] = [
                'serial_id'     => (int) $r->serial_id,
                'serial_number' => $r->serial_number,
            ];
        }

        foreach ($materialItems as &$item) {
            $gross = $allocByItem[(int) $item->id] ?? 0.0;
            $returned = $returnedByItem[(int) $item->id] ?? 0.0;
            $item->allocated_qty    = max(0.0, $gross - $returned);
            $item->reserved_serials = $reservedSerialsByItem[(int) $item->id] ?? [];
        }
        unset($item);

        // Allocation event headers
        $allocations = $this->db->fetchAll(
            "SELECT a.id, a.status, a.notes,
                    u.name  AS created_by_name,  a.created_at,
                    cu.name AS cancelled_by_name, a.cancelled_at
             FROM manufacturing_order_material_allocations AS a
             LEFT JOIN users AS u  ON u.id  = a.created_by
             LEFT JOIN users AS cu ON cu.id = a.cancelled_by
             WHERE a.manufacturing_order_id = ?
             ORDER BY a.created_at ASC",
            [$id]
        );
        foreach ($allocations as $alloc) {
            $alloc->date_time        = formatMySqlDate($alloc->created_at);
            $alloc->cancelled_at_time = formatMySqlDate($alloc->cancelled_at ?? null);
        }

        // All allocation line items (covers both serial and non-serial)
        $allocItems = $this->db->fetchAll(
            "SELECT ami.id, ami.allocation_id, ami.material_item_id, ami.product_id,
                    ami.allocated_qty, p.name AS product_name, p.stock_tracking_method
             FROM manufacturing_order_material_allocation_items AS ami
             LEFT JOIN products AS p ON p.id = ami.product_id
             WHERE ami.manufacturing_order_id = ?
             ORDER BY ami.allocation_id, ami.material_item_id",
            [$id]
        );

        // Serial numbers per allocation_item — include id and status so UI can show consumed vs reserved and identify for returns
        $allocSerials = $this->db->fetchAll(
            "SELECT ams.allocation_item_id, ams.serial_id, s.serial_number, s.status AS serial_status
             FROM manufacturing_order_material_allocation_serials AS ams
             INNER JOIN inv_serials AS s ON s.id = ams.serial_id
             WHERE ams.manufacturing_order_id = ?
             ORDER BY ams.allocation_item_id, ams.id",
            [$id]
        );
        $serialsByAllocItem = [];
        foreach ($allocSerials as $s) {
            $serialsByAllocItem[(int) $s->allocation_item_id][] = [
                'serial_id'     => (int) $s->serial_id,
                'serial_number' => $s->serial_number,
                'status'        => $s->serial_status,
            ];
        }

        // Attach serials and group items by allocation_id
        $itemsByAlloc = [];
        foreach ($allocItems as $item) {
            $item->serials = $serialsByAllocItem[(int) $item->id] ?? [];
            $itemsByAlloc[(int) $item->allocation_id][] = $item;
        }

        // Total consumed qty per material item — used for cancellability check only
        $consumedByItem = [];
        $consumedRows = $this->db->fetchAll(
            "SELECT material_item_id, COALESCE(SUM(consumed_qty), 0) AS total_consumed
             FROM manufacturing_order_output_consumptions
             WHERE manufacturing_order_id = ?
             GROUP BY material_item_id",
            [$id]
        );
        foreach ($consumedRows as $r) {
            $consumedByItem[(int) $r->material_item_id] = (float) $r->total_consumed;
        }

        foreach ($allocations as &$alloc) {
            $items = $itemsByAlloc[(int) $alloc->id] ?? [];

            $alloc->items = $items;

            if ($alloc->status === 'cancelled') {
                $alloc->is_cancellable = false;
            } else {
                $cancellable = true;
                foreach ($items as $item) {
                    if ($item->stock_tracking_method === 'serial') {
                        foreach ($item->serials as $s) {
                            if (($s['status'] ?? '') === 'consumed') { $cancellable = false; break 2; }
                        }
                    } else {
                        $miId      = (int) $item->material_item_id;
                        $remaining = ($allocByItem[$miId] ?? 0.0) - (float) $item->allocated_qty;
                        if ($remaining < ($consumedByItem[$miId] ?? 0.0) - 0.0001) {
                            $cancellable = false; break;
                        }
                    }
                }
                $alloc->is_cancellable = $cancellable;
            }
        }
        unset($alloc);

        // Output events
        $outputs = $this->db->fetchAll(
            "SELECT o.id, o.output_qty, o.created_at,
                    loc.name AS destination_location_name,
                    u.name   AS created_by_name
             FROM manufacturing_order_outputs AS o
             LEFT JOIN company_locations AS loc ON loc.id = o.destination_location_id
             LEFT JOIN users            AS u   ON u.id   = o.created_by
             WHERE o.manufacturing_order_id = ?
             ORDER BY o.created_at ASC",
            [$id]
        );
        foreach ($outputs as $output) {
            $output->date_time = formatMySqlDate($output->created_at);
        }

        // Finished goods serials produced per output
        $outputSerialRows = $this->db->fetchAll(
            "SELECT os.output_id, s.serial_number
             FROM manufacturing_order_output_serials AS os
             INNER JOIN inv_serials AS s ON s.id = os.serial_id
             WHERE os.manufacturing_order_id = ?
             ORDER BY os.output_id, os.id ASC",
            [$id]
        );
        $serialsByOutput = [];
        foreach ($outputSerialRows as $r) {
            $serialsByOutput[(int) $r->output_id][] = $r->serial_number;
        }
        foreach ($outputs as &$output) {
            $output->serials = $serialsByOutput[(int) $output->id] ?? [];
        }
        unset($output);

        // Material returns
        $returns = $this->db->fetchAll(
            "SELECT r.id, r.notes, r.created_at,
                    u.name AS created_by_name
             FROM manufacturing_order_material_returns AS r
             LEFT JOIN users AS u ON u.id = r.created_by
             WHERE r.manufacturing_order_id = ?
             ORDER BY r.created_at ASC",
            [$id]
        );
        foreach ($returns as $ret) {
            $ret->date_time = formatMySqlDate($ret->created_at);
        }

        // Return items per return header
        $returnItemRows = $this->db->fetchAll(
            "SELECT ri.id, ri.return_id, ri.material_item_id, ri.product_id,
                    ri.returned_qty, p.name AS product_name, p.stock_tracking_method
             FROM manufacturing_order_material_return_items AS ri
             LEFT JOIN products AS p ON p.id = ri.product_id
             WHERE ri.manufacturing_order_id = ?
             ORDER BY ri.return_id, ri.material_item_id",
            [$id]
        );

        // Returned serials per return item
        $returnSerialRows = $this->db->fetchAll(
            "SELECT rs.return_item_id, s.serial_number
             FROM manufacturing_order_material_return_serials AS rs
             INNER JOIN inv_serials AS s ON s.id = rs.serial_id
             WHERE rs.manufacturing_order_id = ?
             ORDER BY rs.return_item_id, rs.id",
            [$id]
        );
        $returnSerialsByItem = [];
        foreach ($returnSerialRows as $r) {
            $returnSerialsByItem[(int) $r->return_item_id][] = $r->serial_number;
        }
        $returnItemsByReturn = [];
        foreach ($returnItemRows as $ri) {
            $ri->serials = $returnSerialsByItem[(int) $ri->id] ?? [];
            $returnItemsByReturn[(int) $ri->return_id][] = $ri;
        }
        foreach ($returns as &$ret) {
            $ret->items = $returnItemsByReturn[(int) $ret->id] ?? [];
        }
        unset($ret);

        // Net consumed per material item = consumed - returned (for UI display)
        $returnedQtyByItem = [];
        foreach ($returnItemRows as $ri) {
            $miId = (int) $ri->material_item_id;
            $returnedQtyByItem[$miId] = ($returnedQtyByItem[$miId] ?? 0.0) + (float) $ri->returned_qty;
        }
        // Consumed qty (from output consumptions) per material item
        $consumedQtyRows = $this->db->fetchAll(
            "SELECT material_item_id,
                    COUNT(CASE WHEN serial_id IS NOT NULL THEN 1 END) AS serial_consumed,
                    COALESCE(SUM(CASE WHEN serial_id IS NULL THEN consumed_qty END), 0) AS qty_consumed
             FROM manufacturing_order_output_consumptions
             WHERE manufacturing_order_id = ?
             GROUP BY material_item_id",
            [$id]
        );
        $consumedQtyByItem = [];
        foreach ($consumedQtyRows as $r) {
            $isSerial = ($r->serial_consumed > 0);
            $consumedQtyByItem[(int) $r->material_item_id] = $isSerial
                ? (int) $r->serial_consumed
                : (float) $r->qty_consumed;
        }
        foreach ($materialItems as &$item) {
            $miId = (int) $item->id;
            $item->total_consumed = $consumedQtyByItem[$miId] ?? 0;
            $item->total_returned = $returnedQtyByItem[$miId] ?? 0.0;
            // Returns come from the reserved (unproduced) pool, not the consumed pool,
            // so net_consumed equals total_consumed — returns do not reduce actual consumption.
            $item->net_consumed   = (float) $item->total_consumed;
        }
        unset($item);

        $details = array_merge(
            [
                'id'                             => $id,
                'product_name'                   => $mo->product->name,
                'product_stock_tracking_method'  => $meta->product_stock_tracking_method ?? null,
                'source_location_name'           => $meta->source_location_name      ?? null,
                'destination_location_name'      => $meta->destination_location_name ?? null,
                'created_by_name'                => $meta->created_by_name           ?? null,
                'material_items'                 => $materialItems,
                'allocations'                    => $allocations,
                'outputs'                        => $outputs,
                'returns'                        => $returns,
                'history'                        => $mo->history,
            ],
            $mo->toArray()
        );
        return ['mo_details' => $details];
    }

    private function recalcAllocationStatus(int $moId): void
    {
        $rows = $this->db->fetchAll(
            "SELECT mi.id, mi.planned_qty, p.stock_tracking_method
             FROM manufacturing_order_material_items AS mi
             LEFT JOIN products AS p ON p.id = mi.product_id
             WHERE mi.manufacturing_order_id = ?",
            [$moId]
        );

        if (empty($rows)) {
            $this->db->query("UPDATE manufacturing_orders SET allocation_status = 'not_allocated' WHERE id = ?", [$moId]);
            return;
        }

        // Allocated qty per item from active allocations
        $allocSums = $this->db->fetchAll(
            "SELECT ami.material_item_id, SUM(ami.allocated_qty) AS total_qty
             FROM manufacturing_order_material_allocation_items AS ami
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             WHERE ami.manufacturing_order_id = ?
             GROUP BY ami.material_item_id",
            [$moId]
        );
        $allocSumMap = [];
        foreach ($allocSums as $r) {
            $allocSumMap[(int) $r->material_item_id] = (float) $r->total_qty;
        }

        // Returned qty per item — reduces effective allocation
        $returnSums = $this->db->fetchAll(
            "SELECT material_item_id, SUM(returned_qty) AS total_returned
             FROM manufacturing_order_material_return_items
             WHERE manufacturing_order_id = ?
             GROUP BY material_item_id",
            [$moId]
        );
        $returnSumMap = [];
        foreach ($returnSums as $r) {
            $returnSumMap[(int) $r->material_item_id] = (float) $r->total_returned;
        }

        $anyAllocated = false;
        $allFull      = true;

        foreach ($rows as $item) {
            $miId      = (int) $item->id;
            $needed    = (float) $item->planned_qty;
            $allocated = max(0.0, ($allocSumMap[$miId] ?? 0.0) - ($returnSumMap[$miId] ?? 0.0));

            // For serial items allocated_qty stores serial count (integer), use ceil for planned_qty safety
            $full = $item->stock_tracking_method === 'serial'
                ? $allocated >= (int) ceil($needed)
                : $allocated >= $needed;

            if ($allocated > 0) $anyAllocated = true;
            if (!$full)         $allFull      = false;
        }

        if ($allFull) {
            $status = 'fully_allocated';
        } elseif ($anyAllocated) {
            $status = 'partially_allocated';
        } else {
            $status = 'not_allocated';
        }

        $this->db->query(
            "UPDATE manufacturing_orders SET allocation_status = ? WHERE id = ?",
            [$status, $moId]
        );
    }

    public function saveAllocation(int $moId, array $payload): array
    {
        if (!$this->context->canDo('manufacturing_orders', 'material_allocation')) {
            throw new Service_Exception("You do not have permission to allocate materials", 403);
        }

        $mo = $this->getOrFail($moId);

        if (!in_array($mo->status, ['confirmed', 'in_production'])) {
            throw new Service_Exception("Materials can only be allocated for confirmed or in-production orders", 422);
        }

        $companyId  = $this->context->companyId;
        $locationId = (int) $mo->source_location_id;
        $items      = is_array($payload['items'] ?? null) ? $payload['items'] : [];

        // Load material items for this MO, scoped to company via product join
        $moItemRows = $this->db->fetchAll(
            "SELECT mi.id, mi.product_id, mi.planned_qty, p.stock_tracking_method,
                    u.allow_decimal AS uom_allow_decimal, u.name AS uom_name,
                    p.name AS product_name,
                    COALESCE(s.on_hand_qty, 0) AS on_hand_qty
             FROM manufacturing_order_material_items AS mi
             INNER JOIN products AS p ON p.id = mi.product_id AND p.company_id = ?
             LEFT JOIN uoms AS u ON u.id = p.base_uom_id
             LEFT JOIN inv_product_stock AS s ON s.product_id = p.id AND s.company_id = p.company_id AND s.location_id = ?
             WHERE mi.manufacturing_order_id = ?",
            [$companyId, $locationId, $moId]
        );
        $moItemMap = [];
        foreach ($moItemRows as $r) {
            $moItemMap[(int) $r->id] = $r;
        }

        // Already-allocated qty per material item from active allocations (used for over-allocation guard)
        $existingQtyRows = $this->db->fetchAll(
            "SELECT ami.material_item_id, SUM(ami.allocated_qty) AS total_qty
             FROM manufacturing_order_material_allocation_items AS ami
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             WHERE ami.manufacturing_order_id = ?
             GROUP BY ami.material_item_id",
            [$moId]
        );
        $existingQtyMap = [];
        foreach ($existingQtyRows as $r) {
            $existingQtyMap[(int) $r->material_item_id] = (float) $r->total_qty;
        }

        // Classify payload items into serial vs non-serial; validate and trim serial numbers
        $serialItems      = [];  // miId => ['item' => row, 'serial_numbers' => []]
        $nonSerialItems   = [];  // miId => ['item' => row, 'qty' => float]
        $allSerialNumbers = [];

        foreach ($items as $item) {
            $miId = (int) ($item['material_item_id'] ?? 0);
            if (!$miId) continue;

            if (!isset($moItemMap[$miId])) {
                $this->addError("Invalid material item id: $miId", "items");
                return ['success' => false, 'errors' => $this->getErrors()];
            }

            $miRow            = $moItemMap[$miId];
            $alreadyAllocated = $existingQtyMap[$miId] ?? 0.0;

            if ($miRow->stock_tracking_method === 'none') {
                $qty = (float) ($item['qty'] ?? 0);
                if ($qty <= 0) continue;
                $nonSerialItems[$miId] = ['item' => $miRow, 'qty' => $qty];

            } elseif ($miRow->stock_tracking_method === 'serial') {
                $serials = array_filter(array_map('trim', array_values((array) ($item['serial_numbers'] ?? []))));
                if (empty($serials)) continue;
                // Serial on_hand is enforced physically: each serial must exist in inv_serial_stock at the
                // source location with status = 'in_stock' (validated below). No separate qty guard needed.
                $serialItems[$miId] = ['item' => $miRow, 'serial_numbers' => $serials];
                foreach ($serials as $sn) { $allSerialNumbers[] = $sn; }

            } else {
                $qty = (float) ($item['qty'] ?? 0);
                if ($qty <= 0) continue;
                $onHand = (float) ($miRow->on_hand_qty ?? 0);
                $needed = $alreadyAllocated + $qty;
                if ($needed > $onHand + 0.0001) {
                    $productName = $miRow->product_name ?? 'Unknown';
                    $this->addError(
                        "Insufficient stock for {$productName}: required " . formatQty($needed) . ", available " . formatQty($onHand),
                        "items"
                    );
                    return ['success' => false, 'errors' => $this->getErrors()];
                }
                if (!(bool)(int)($miRow->uom_allow_decimal ?? 1) && !isWholeNumber($qty)) {
                    $this->addError("Allocation quantity must be a whole number for {$miRow->uom_name}", "items");
                    return ['success' => false, 'errors' => $this->getErrors()];
                }
                $nonSerialItems[$miId] = ['item' => $miRow, 'qty' => $qty];
            }
        }

        if (empty($serialItems) && empty($nonSerialItems)) {
            $this->addError("No items were provided for allocation", "items");
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        if (count($allSerialNumbers) !== count(array_unique($allSerialNumbers))) {
            $this->addError("Duplicate serial numbers in allocation", "items");
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        // Validate serials: exist at source location, in_stock, belong to correct company + product
        $serialValidMap = [];
        if (!empty($allSerialNumbers)) {
            $ph = implode(',', array_fill(0, count($allSerialNumbers), '?'));
            $validSerials = $this->db->fetchAll(
                "SELECT s.id, s.serial_number, s.product_id, s.status
                 FROM inv_serials AS s
                 INNER JOIN inv_serial_stock AS ss ON ss.serial_id = s.id AND ss.location_id = ?
                 WHERE s.company_id = ? AND s.serial_number IN ($ph)",
                array_merge([$locationId, $companyId], $allSerialNumbers)
            );
            foreach ($validSerials as $vs) {
                $serialValidMap[$vs->serial_number] = $vs;
            }

            foreach ($serialItems as $miId => $entry) {
                foreach ($entry['serial_numbers'] as $sn) {
                    if (!isset($serialValidMap[$sn])) {
                        $this->addError("Serial '$sn' is not available at the source warehouse", "items");
                        return ['success' => false, 'errors' => $this->getErrors()];
                    }
                    $vs = $serialValidMap[$sn];
                    if ($vs->status !== 'in_stock') {
                        $this->addError("Serial '$sn' is not in stock (current status: {$vs->status})", "items");
                        return ['success' => false, 'errors' => $this->getErrors()];
                    }
                    if ((int) $vs->product_id !== (int) $entry['item']->product_id) {
                        $this->addError("Serial '$sn' does not belong to the expected product", "items");
                        return ['success' => false, 'errors' => $this->getErrors()];
                    }
                }
            }
        }

        $allItems = $serialItems + $nonSerialItems;

        try {
            $this->db->startTransaction();

            $miIds       = array_keys($allItems);
            $oldAllocQty = $this->calcItemsEffectiveAllocatedQty($miIds, $moId);

            $alloc = new Models_ManufacturingOrderMaterialAllocation();
            $alloc->company_id             = $companyId;
            $alloc->manufacturing_order_id = $moId;
            $alloc->notes                  = trim($payload['notes'] ?? '') ?: null;
            $alloc->created_by             = $this->context->userId;
            if (!$alloc->create()) {
                throw new Service_Exception("Failed to save allocation");
            }

            $serialIds = [];

            foreach ($serialItems as $miId => $entry) {
                $allocItem = $this->createAllocationItem($alloc->id, $moId, $companyId, $miId, (int) $entry['item']->product_id, count($entry['serial_numbers']));

                foreach ($entry['serial_numbers'] as $sn) {
                    $vs  = $serialValidMap[$sn];
                    $row = new Models_ManufacturingOrderMaterialAllocationSerial();
                    $row->company_id             = $companyId;
                    $row->allocation_item_id     = $allocItem->id;
                    $row->manufacturing_order_id = $moId;
                    $row->product_id             = (int) $entry['item']->product_id;
                    $row->serial_id              = (int) $vs->id;
                    if (!$row->create()) {
                        throw new Service_Exception("Failed to save allocation serial");
                    }
                    $serialIds[] = (int) $vs->id;
                }
            }

            $stock = new Service_Inv_Stock($this->context);

            if (!empty($serialIds)) {
                $stock->reserveSerials(0, $locationId, $serialIds, 'manufacturing_order', $moId);
            }

            foreach ($nonSerialItems as $miId => $entry) {
                $this->createAllocationItem($alloc->id, $moId, $companyId, $miId, (int) $entry['item']->product_id, $entry['qty']);
            }

            $newAllocQty = $this->calcItemsEffectiveAllocatedQty($miIds, $moId);
            foreach ($allItems as $miId => $entry) {
                if ($entry['item']->stock_tracking_method === 'none') continue;
                $newQty  = $newAllocQty[$miId] ?? 0.0;
                $planned = (float) $entry['item']->planned_qty;
                $delta   = max($planned, $newQty) - max($planned, $oldAllocQty[$miId] ?? 0.0);
                $stock->adjustReservation((int) $entry['item']->product_id, $locationId, $delta, 'manufacturing_order', $moId, $mo->mo_number, $miId);
            }

            $this->recalcAllocationStatus($moId);

            $this->addHistory($moId, 'allocated', 'Materials allocated', 'allocation', $alloc->id);

            $this->db->commit();

            return ['success' => true, 'data' => ['allocation_id' => $alloc->id]];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to save allocation");
        }
    }

    public function cancelAllocation(int $moId, int $allocationId): array
    {
        if (!$this->context->canDo('manufacturing_orders', 'material_allocation')) {
            throw new Service_Exception("You do not have permission to manage allocations", 403);
        }

        $mo = $this->getOrFail($moId);

        if (!in_array($mo->status, ['confirmed', 'in_production'])) {
            throw new Service_Exception("Allocations can only be cancelled for confirmed or in-production orders", 422);
        }

        $alloc = new Models_ManufacturingOrderMaterialAllocation($allocationId);
        if ($alloc->isEmpty || (int) $alloc->manufacturing_order_id !== $moId || (int) $alloc->company_id !== (int) $mo->company_id) {
            throw new Service_Exception("Allocation not found", 404);
        }

        if ($alloc->status === 'cancelled') {
            throw new Service_Exception("This allocation has already been cancelled", 422);
        }

        // Validate: block cancel if any material in this allocation has been consumed in production
        $allocItems = $this->db->fetchAll(
            "SELECT ami.id AS alloc_item_id, ami.material_item_id, ami.product_id, ami.allocated_qty,
                    p.stock_tracking_method, mi.planned_qty
             FROM manufacturing_order_material_allocation_items AS ami
             LEFT JOIN products AS p ON p.id = ami.product_id
             LEFT JOIN manufacturing_order_material_items AS mi ON mi.id = ami.material_item_id
             WHERE ami.allocation_id = ?",
            [$allocationId]
        );

        foreach ($allocItems as $allocItem) {
            if ($allocItem->stock_tracking_method === 'serial') {
                $consumedCount = (int) $this->db->fetchOne(
                    "SELECT COUNT(*) AS cnt
                     FROM manufacturing_order_material_allocation_serials AS ams
                     INNER JOIN inv_serials AS s ON s.id = ams.serial_id AND s.status = 'consumed'
                     WHERE ams.allocation_item_id = ?",
                    [$allocItem->alloc_item_id]
                )->cnt;
                if ($consumedCount > 0) {
                    throw new Service_Exception(
                        "{$consumedCount} serial(s) from this allocation have already been consumed in production and cannot be cancelled. Use the post-production return flow to correct over-allocations.",
                        422
                    );
                }
            } else {
                $miId = (int) $allocItem->material_item_id;
                $totalAllocated = (float) $this->db->fetchOne(
                    "SELECT COALESCE(SUM(ami2.allocated_qty), 0) AS total
                     FROM manufacturing_order_material_allocation_items AS ami2
                     INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami2.allocation_id AND a.status = 'active'
                     WHERE ami2.manufacturing_order_id = ? AND ami2.material_item_id = ?",
                    [$moId, $miId]
                )->total;
                $totalConsumed = (float) $this->db->fetchOne(
                    "SELECT COALESCE(SUM(consumed_qty), 0) AS total
                     FROM manufacturing_order_output_consumptions
                     WHERE manufacturing_order_id = ? AND material_item_id = ? AND serial_id IS NULL",
                    [$moId, $miId]
                )->total;
                $remainingAfterCancel = $totalAllocated - (float) $allocItem->allocated_qty;
                if ($remainingAfterCancel < $totalConsumed - 0.0001) {
                    throw new Service_Exception(
                        "This allocation cannot be cancelled because its materials have already been partially consumed in production.",
                        422
                    );
                }
            }
        }

        $serialRows = $this->db->fetchAll(
            "SELECT ams.serial_id
             FROM manufacturing_order_material_allocation_serials AS ams
             INNER JOIN manufacturing_order_material_allocation_items AS ami ON ami.id = ams.allocation_item_id
             WHERE ami.allocation_id = ?",
            [$allocationId]
        );
        $serialIds = array_map(fn($r) => (int) $r->serial_id, $serialRows);

        try {
            $this->db->startTransaction();

            $cancelCompanyId = (int) $mo->company_id;
            $cancelLocId     = (int) $mo->source_location_id;

            // Capture old qty inside transaction so concurrent allocations don't skew the delta
            $allocMiIds  = array_unique(array_map(fn($a) => (int) $a->material_item_id, $allocItems));
            $oldAllocQty = $this->calcItemsEffectiveAllocatedQty($allocMiIds, $moId);

            $stock = new Service_Inv_Stock($this->context);

            // Revert only still-reserved serials (safety net — consumed ones must not be touched)
            if (!empty($serialIds)) {
                $stock->releaseSerials(0, $cancelLocId, $serialIds);
            }

            // Soft-cancel the allocation
            $this->db->query(
                "UPDATE manufacturing_order_material_allocations SET status = 'cancelled', cancelled_by = ?, cancelled_at = ? WHERE id = ?",
                [$this->context->userId, date('Y-m-d H:i:s'), $allocationId]
            );

            $newAllocQty = $this->calcItemsEffectiveAllocatedQty($allocMiIds, $moId);
            foreach ($allocItems as $allocItem) {
                $miId      = (int) $allocItem->material_item_id;
                $productId = (int) $allocItem->product_id;
                $planned   = (float) $allocItem->planned_qty;
                $delta     = max($planned, $newAllocQty[$miId] ?? 0.0) - max($planned, $oldAllocQty[$miId] ?? 0.0);
                $stock->adjustReservation($productId, $cancelLocId, $delta, 'manufacturing_order', $moId, $mo->mo_number, $miId);
            }

            $this->recalcAllocationStatus($moId);

            $this->addHistory($moId, 'allocation_cancelled', 'Allocation cancelled', 'allocation', $allocationId);

            $this->db->commit();

            return ['success' => true, 'data' => []];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to cancel allocation");
        }
    }

    public function create(array $payload): array
    {
        if (!$this->context->canDo('manufacturing_orders', 'write')) {
            throw new Service_Exception("You do not have permission to create manufacturing orders", 403);
        }

        $this->validateProductAndBom($payload);
        $this->validateCommonFields($payload);

        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $companyId = $this->context->companyId;
        $productId = (int) $payload['product_id'];
        $bomId = (int) $payload['bom_id'];
        $locationId = (int) $payload['source_location_id'];
        $destLocationId = (int) $payload['destination_location_id'];
        $plannedQty = (float) $payload['planned_qty'];
        $plannedDate = trim($payload['planned_date'] ?? '') ?: null;

        try {

            $this->db->startTransaction();

            $bom = new Models_ManufacturingBom($bomId);

            $moNumber = (new Service_Sequence($this->context))->nextCommit('manufacturing_orders');

            $mo = new Models_ManufacturingOrder();
            $mo->company_id = $companyId;
            $mo->mo_number = $moNumber;
            $mo->product_id = $productId;
            $mo->bom_id = $bomId;
            $mo->bom_name = $bom->name;
            $mo->source_location_id = $locationId;
            $mo->destination_location_id = $destLocationId;
            $mo->origin_type = 'manual';
            $mo->planned_qty = $plannedQty;
            $mo->planned_date = $plannedDate;
            $mo->notes = trim($payload['notes'] ?? '') ?: null;
            $mo->track_serial_genealogy = 0;
            $mo->created_by = $this->context->userId;

            if (!$mo->create()) {
                throw new Service_Exception("Failed to create manufacturing order");
            }

            $this->snapshotMaterialItems($mo, $bom);

            $this->addHistory($mo->id, 'created', 'Manufacturing order created');

            $this->db->commit();

            return ['success' => true, 'data' => ['mo_id' => $mo->id, 'mo_number' => $moNumber]];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to create manufacturing order");
        }
    }

    public function confirm(int $id, array $payload = []): array
    {
        if (!$this->context->canDo('manufacturing_orders', 'confirm')) {
            throw new Service_Exception("You do not have permission to confirm manufacturing orders", 403);
        }

        $mo = $this->getOrFail($id);

        if ($mo->status !== 'draft') {
            throw new Service_Exception("Only draft orders can be confirmed", 422);
        }

        $stockWarnings = $this->getStockWarningsForMaterialItems($mo);
        if (!empty($stockWarnings) && empty($payload['acknowledged_warning'])) {
            return [
                'success'      => false,
                'warning'      => true,
                'warning_type' => 'low_stock',
                'warnings'     => $stockWarnings,
            ];
        }

        try {

            $this->db->startTransaction();

            $mo->status = 'confirmed';
            $mo->confirmed_by = $this->context->userId;
            $mo->confirmed_at = date('Y-m-d H:i:s');

            if (!$mo->update()) {
                throw new Service_Exception("Failed to confirm manufacturing order");
            }

            $sourceLocId  = (int) $mo->source_location_id;
            $reserveItems = array_map(fn($mi) => [
                'product_id'  => (int)   $mi->product_id,
                'location_id' => $sourceLocId,
                'qty'         => (float) $mi->planned_qty,
                'line_id'     => (int)   $mi->id,
            ], $mo->material_items);
            (new Service_Inv_Stock($this->context))->reserveForDocument(
                $reserveItems, 'manufacturing_order', $id, $mo->mo_number
            );

            $this->addHistory($id, 'confirmed', 'Order confirmed');

            $this->db->commit();

            return ['success' => true, 'data' => []];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to confirm manufacturing order");
        }
    }

    public function update(int $id, array $payload): array
    {
        if (!$this->context->canDo('manufacturing_orders', 'write')) {
            throw new Service_Exception("You do not have permission to edit manufacturing orders", 403);
        }

        $mo = $this->getOrFail($id);

        if ($mo->status !== 'draft') {
            throw new Service_Exception("Only draft orders can be edited", 422);
        }

        $newPlannedQty = (float) ($payload['planned_qty'] ?? 0);
        $plannedDate = trim($payload['planned_date'] ?? '') ?: null;
        $notes = trim($payload['notes'] ?? '') ?: null;
        $locationId = (int) ($payload['source_location_id'] ?? 0);
        $destLocationId = (int) ($payload['destination_location_id'] ?? 0);

        $this->validateCommonFields($payload);

        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        try {

            $this->db->startTransaction();

            $oldPlannedQty = (float) $mo->planned_qty;
            $ratio = $oldPlannedQty > 0 ? ($newPlannedQty / $oldPlannedQty) : 1;

            // Rescale each material item's planned_qty proportionally
            foreach ($mo->material_items as $mi) {
                $newItemQty = round((float) $mi->planned_qty * $ratio, 6);
                $this->db->query("UPDATE manufacturing_order_material_items SET planned_qty = ? WHERE id = ?", [$newItemQty, (int) $mi->id]);
            }

            $mo->planned_qty = $newPlannedQty;
            $mo->planned_date = $plannedDate;
            $mo->notes = $notes;
            $mo->source_location_id = $locationId;
            $mo->destination_location_id = $destLocationId;

            if (!$mo->update()) {
                throw new Service_Exception("Failed to update manufacturing order");
            }

            $this->addHistory($id, 'updated', 'Order updated');

            $this->db->commit();

            return ['success' => true, 'data' => ['mo_id' => $id, 'mo_number' => $mo->mo_number]];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to update manufacturing order");
        }
    }

    public function recordOutput(int $moId, array $payload): array
    {
        if (!$this->context->canDo('manufacturing_orders', 'produce')) {
            throw new Service_Exception("You do not have permission to record output", 403);
        }

        $mo = $this->getOrFail($moId);

        if (!in_array($mo->status, ['confirmed', 'in_production'])) {
            throw new Service_Exception("Output can only be recorded for confirmed or in-production orders", 422);
        }

        $companyId     = $this->context->companyId;
        $outputQty     = (float) ($payload['output_qty'] ?? 0);
        $destLocId     = (int)   ($payload['destination_location_id'] ?? (int) $mo->destination_location_id);
        $notes         = trim($payload['notes'] ?? '') ?: null;
        $plannedQty    = (float) $mo->planned_qty;

        // Parse per-material actual consumption from payload
        $consumptionMap = []; // miId => ['actual_qty' => float, 'serial_ids' => [int], 'is_serial' => bool]
        foreach (($payload['material_consumption'] ?? []) as $c) {
            $miId = (int) ($c['material_item_id'] ?? 0);
            if (!$miId) continue;
            $isSerial  = array_key_exists('serial_ids', $c);
            $serialIds = array_map('intval', (array) ($c['serial_ids'] ?? []));
            $consumptionMap[$miId] = [
                'actual_qty' => $isSerial ? count($serialIds) : (float) ($c['actual_qty'] ?? 0),
                'serial_ids' => $serialIds,
                'is_serial'  => $isSerial,
            ];
        }
        $producedSoFar = (float) $mo->produced_qty;
        $remaining     = $plannedQty - $producedSoFar;

        if ($outputQty <= 0) {
            $this->addError("Output quantity must be greater than zero", "output_qty");
            return ['success' => false, 'errors' => $this->getErrors()];
        }
        if ($outputQty > $remaining + 0.0001) {
            $this->addError("Output quantity exceeds remaining planned quantity (" . number_format($remaining, 4) . ")", "output_qty");
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $destLoc = new Models_Location($destLocId);
        if ($destLoc->isEmpty || (int) $destLoc->company_id !== $companyId || $destLoc->status !== 'active') {
            $this->addError(validationErrMsg("missing_or_invalid", "Destination warehouse"), "destination_location_id");
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $sourceLocId = (int) $mo->source_location_id;

        // Load material items (needed for allocation check and consumption)
        $materialItems = $this->db->fetchAll(
            "SELECT mi.id, mi.product_id, mi.planned_qty, p.stock_tracking_method, p.name AS product_name
             FROM manufacturing_order_material_items AS mi
             LEFT JOIN products AS p ON p.id = mi.product_id
             WHERE mi.manufacturing_order_id = ?",
            [$moId]
        );

        // Load reserved serial IDs per material item (to validate user-specified consumption)
        $rsvSerialIdRows = $this->db->fetchAll(
            "SELECT ami.material_item_id, ams.serial_id
             FROM manufacturing_order_material_allocation_serials AS ams
             INNER JOIN manufacturing_order_material_allocation_items AS ami ON ami.id = ams.allocation_item_id
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             INNER JOIN inv_serials AS s ON s.id = ams.serial_id AND s.status = 'reserved'
             WHERE ami.manufacturing_order_id = ?",
            [$moId]
        );
        $reservedSerialIdsByItem = [];
        foreach ($rsvSerialIdRows as $r) {
            $reservedSerialIdsByItem[(int) $r->material_item_id][] = (int) $r->serial_id;
        }

        // Total allocated qty per non-serial material item
        $allocSumRows = $this->db->fetchAll(
            "SELECT ami.material_item_id, SUM(ami.allocated_qty) AS total_allocated
             FROM manufacturing_order_material_allocation_items AS ami
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             WHERE ami.manufacturing_order_id = ?
             GROUP BY ami.material_item_id",
            [$moId]
        );
        $totalAllocated = [];
        foreach ($allocSumRows as $r) {
            $totalAllocated[(int) $r->material_item_id] = (float) $r->total_allocated;
        }

        // Prior consumed qty per non-serial material item
        $priorConsumedRows = $this->db->fetchAll(
            "SELECT material_item_id, COALESCE(SUM(consumed_qty), 0) AS total_consumed
             FROM manufacturing_order_output_consumptions
             WHERE manufacturing_order_id = ? AND serial_id IS NULL
             GROUP BY material_item_id",
            [$moId]
        );
        $priorConsumed = [];
        foreach ($priorConsumedRows as $r) {
            $priorConsumed[(int) $r->material_item_id] = (float) $r->total_consumed;
        }

        // ── Phase 2: Collect all field errors (no early returns) ─────────────────
        $fieldErrors   = []; // key => string[]
        $shortageItems = [];

        // ── Finished goods serial validation ─────────────────────────────────────
        $productRow = $this->db->fetchOne(
            "SELECT p.stock_tracking_method, u.allow_decimal AS uom_allow_decimal, u.name AS uom_name FROM products p LEFT JOIN uoms u ON u.id = p.base_uom_id WHERE p.id = ? AND p.company_id = ?",
            [(int) $mo->product_id, $companyId]
        );
        $isSerialFinishedProduct = ($productRow && $productRow->stock_tracking_method === 'serial');
        $outputSerials           = [];

        if ($isSerialFinishedProduct) {
            if ($outputQty != (int) $outputQty || (int) $outputQty <= 0) {
                $fieldErrors['output_qty'][] = "Output quantity must be a whole number for serial-tracked products";
            }
        } elseif ($productRow && !(bool)(int)$productRow->uom_allow_decimal && !isWholeNumber($outputQty)) {
            $fieldErrors['output_qty'][] = "Output quantity must be a whole number for {$productRow->uom_name}";
        }

        if ($isSerialFinishedProduct) {
            $rawSerials    = array_map('trim', (array) ($payload['serial_numbers'] ?? []));
            $outputSerials = array_values(array_filter($rawSerials, fn($s) => $s !== ''));
            $required      = (int) $outputQty;

            if (count($outputSerials) !== $required) {
                $fieldErrors['serial_numbers'][] = "You must provide exactly $required serial number(s) for this output (" . count($outputSerials) . " provided)";
            } else {
                // Only check duplicates and DB existence when count is correct
                if (count($outputSerials) !== count(array_unique($outputSerials))) {
                    $fieldErrors['serial_numbers'][] = "Duplicate serial numbers in the submission";
                } else if (!empty($outputSerials)) {
                    $ph       = implode(',', array_fill(0, count($outputSerials), '?'));
                    $existing = $this->db->fetchAll(
                        "SELECT serial_number FROM inv_serials WHERE company_id = ? AND serial_number IN ($ph)",
                        array_merge([$companyId], $outputSerials)
                    );
                    if (!empty($existing)) {
                        $dupes = implode(', ', array_map(fn($r) => $r->serial_number, $existing));
                        $fieldErrors['serial_numbers'][] = "Serial number(s) already exist in inventory: $dupes";
                    }
                }
            }
        }

        // ── Material consumption validation ───────────────────────────────────────
        $materialErrors = [];

        foreach ($materialItems as $mi) {
            $miId     = (int) $mi->id;
            $itemName = $mi->product_name ?? 'Unknown';

            if ($mi->stock_tracking_method === 'serial') {
                $specifiedSerialIds = $consumptionMap[$miId]['serial_ids'] ?? [];
                $reservedPool       = $reservedSerialIdsByItem[$miId] ?? [];
                if (empty($specifiedSerialIds)) {
                    if (!empty($reservedPool)) {
                        $materialErrors[] = "Serial numbers must be specified for: $itemName";
                    }
                    continue;
                }
                foreach ($specifiedSerialIds as $sid) {
                    if (!in_array($sid, $reservedPool)) {
                        $materialErrors[] = "A specified serial is not reserved for component: $itemName";
                        break; // one error per component is enough
                    }
                }
            } else {
                $fallbackQty      = round(($outputQty / $plannedQty) * (float) $mi->planned_qty, 4);
                $actualConsumeQty = $consumptionMap[$miId]['actual_qty'] ?? $fallbackQty;
                $allocated        = $totalAllocated[$miId] ?? 0.0;
                $alreadyUsed      = $priorConsumed[$miId]  ?? 0.0;
                $remainingAlloc   = $allocated - $alreadyUsed;
                if ($actualConsumeQty > 0 && $actualConsumeQty > $remainingAlloc + 0.0001) {
                    $shortageItems[] = [
                        'name'      => $itemName,
                        'required'  => round($actualConsumeQty, 4),
                        'allocated' => round(max(0.0, $remainingAlloc), 4),
                        'shortage'  => round($actualConsumeQty - max(0.0, $remainingAlloc), 4),
                        'is_serial' => false,
                    ];
                }
            }
        }

        if (!empty($materialErrors)) {
            $fieldErrors['material_consumption'][] = implode('. ', $materialErrors);
        }

        // ── Return all collected errors at once ───────────────────────────────────
        if (!empty($fieldErrors) || !empty($shortageItems)) {
            $errors = [];
            foreach ($fieldErrors as $key => $messages) {
                $errors[$key] = implode('. ', $messages);
            }
            return [
                'success'        => false,
                'errors'         => $errors,
                'shortage_items' => $shortageItems,
            ];
        }

        try {
            $this->db->startTransaction();

            // Lock MO row and re-read produced_qty to prevent concurrent over-production
            $moLock        = $this->db->fetchOne("SELECT produced_qty FROM manufacturing_orders WHERE id = ? FOR UPDATE", [$moId]);
            $producedSoFar = (float) ($moLock->produced_qty ?? 0);
            $remaining     = $plannedQty - $producedSoFar;
            if ($outputQty > $remaining + 0.0001) {
                throw new Service_Exception("Output quantity exceeds remaining planned quantity (" . number_format($remaining, 4) . ")", 422);
            }
            $newProducedQty = $producedSoFar + $outputQty;
            $isCompleted    = $newProducedQty >= $plannedQty - 0.0001;
            $newStatus      = $mo->status === 'confirmed' ? 'in_production' : $mo->status;
            if ($isCompleted) $newStatus = 'completed';

            // ── Step 1: Create output record (needed as FK for consumption rows) ──────
            $output = new Models_ManufacturingOrderOutput();
            $output->company_id              = $companyId;
            $output->manufacturing_order_id  = $moId;
            $output->output_qty              = $outputQty;
            $output->destination_location_id = $destLocId;
            $output->notes                   = $notes;
            $output->created_by              = $this->context->userId;
            if (!$output->create()) {
                throw new Service_Exception("Failed to save output record");
            }

            // ── Step 2: Consume raw materials → log consume movements ─────────────────
            // All consume movements are logged here so they get lower IDs than the
            // produce movement logged in Step 4, ensuring correct order in Stock Movements.
            $movement = new Service_Inv_Movement($this->context);

            // Batch-resolve serial IDs → serial numbers for all serial-tracked items
            $allConsumeSerialIds = [];
            foreach ($consumptionMap as $c) {
                if (!empty($c['serial_ids'])) {
                    $allConsumeSerialIds = array_merge($allConsumeSerialIds, $c['serial_ids']);
                }
            }
            $serialNumberById = [];
            if (!empty($allConsumeSerialIds)) {
                $ph = implode(',', array_fill(0, count($allConsumeSerialIds), '?'));
                $snRows = $this->db->fetchAll(
                    "SELECT id, serial_number FROM inv_serials WHERE id IN ($ph)",
                    $allConsumeSerialIds
                );
                foreach ($snRows as $snRow) {
                    $serialNumberById[(int) $snRow->id] = $snRow->serial_number;
                }
            }

            foreach ($materialItems as $mi) {
                $miId      = (int) $mi->id;
                $productId = (int) $mi->product_id;

                if ($mi->stock_tracking_method === 'serial') {
                    $specifiedSerialIds = $consumptionMap[$miId]['serial_ids'] ?? [];
                    if (empty($specifiedSerialIds)) continue;
                    $actualConsumed = count($specifiedSerialIds);

                    foreach ($specifiedSerialIds as $serialId) {
                        $consumption = new Models_ManufacturingOrderOutputConsumption();
                        $consumption->company_id             = $companyId;
                        $consumption->output_id              = $output->id;
                        $consumption->manufacturing_order_id = $moId;
                        $consumption->material_item_id       = $miId;
                        $consumption->product_id             = $productId;
                        $consumption->consumed_qty           = 1;
                        $consumption->serial_id              = $serialId;
                        if (!$consumption->create()) {
                            throw new Service_Exception("Failed to save serial consumption record");
                        }
                    }

                    $specifiedSerialNumbers = array_values(array_map(fn($sid) => $serialNumberById[$sid] ?? '', $specifiedSerialIds));
                    $movement->record([
                        'movement_type'         => 'mo_consume',
                        'location_id'           => $sourceLocId,
                        'product_id'            => $productId,
                        'quantity'              => $actualConsumed,
                        'serial_or_lot_numbers' => $specifiedSerialNumbers,
                        'reference_type'        => 'mo_output',
                        'reference_id'          => $output->id,
                        'mo_id'                 => $moId,
                        'mo_material_item_id'   => $miId,
                    ]);

                } else {

                    $fallbackQty = round(($outputQty / $plannedQty) * (float) $mi->planned_qty, 4);
                    $consumedQty = $consumptionMap[$miId]['actual_qty'] ?? $fallbackQty;
                    if ($consumedQty <= 0) continue;

                    $consumption = new Models_ManufacturingOrderOutputConsumption();
                    $consumption->company_id             = $companyId;
                    $consumption->output_id              = $output->id;
                    $consumption->manufacturing_order_id = $moId;
                    $consumption->material_item_id       = $miId;
                    $consumption->product_id             = $productId;
                    $consumption->consumed_qty           = $consumedQty;
                    if (!$consumption->create()) {
                        throw new Service_Exception("Failed to save consumption record");
                    }

                    $movement->record([
                        'movement_type'       => 'mo_consume',
                        'location_id'         => $sourceLocId,
                        'product_id'          => $productId,
                        'quantity'            => $consumedQty,
                        'reference_type'      => 'mo_output',
                        'reference_id'        => $output->id,
                        'mo_id'               => $moId,
                        'mo_material_item_id' => $miId,
                    ]);
                }
            }

            // ── Step 3: Advance produced_qty and status ───────────────────────────────
            // $newProducedQty, $newStatus, $isCompleted already computed from locked MO row
            $this->db->query(
                "UPDATE manufacturing_orders SET produced_qty = ?, status = ? WHERE id = ?",
                [$newProducedQty, $newStatus, $moId]
            );

            if ($isCompleted) {
                $stock = new Service_Inv_Stock($this->context);

                foreach ($materialItems as $mi) {
                    $miId      = (int) $mi->id;
                    $productId = (int) $mi->product_id;

                    if ($mi->stock_tracking_method === 'serial') {
                        // Revert any remaining reserved serials (over-allocated or unused) back to in_stock
                        $remainingSerials = $this->db->fetchAll(
                            "SELECT ams.serial_id
                             FROM manufacturing_order_material_allocation_serials AS ams
                             INNER JOIN manufacturing_order_material_allocation_items AS ami ON ami.id = ams.allocation_item_id
                             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
                             INNER JOIN inv_serials AS s ON s.id = ams.serial_id AND s.status = 'reserved'
                             WHERE ami.manufacturing_order_id = ? AND ami.material_item_id = ?",
                            [$moId, $miId]
                        );
                        if (!empty($remainingSerials)) {
                            $serialIds = array_map(fn($r) => (int) $r->serial_id, $remainingSerials);
                            $count     = count($serialIds);
                            $stock->releaseSerials($productId, $sourceLocId, $serialIds);
                            $stock->adjustReservation($productId, $sourceLocId, -$count, 'manufacturing_order', $moId, $mo->mo_number, $miId);
                        }
                    } else {
                        $totalConsumed = (float) $this->db->fetchOne(
                            "SELECT COALESCE(SUM(consumed_qty), 0) AS total
                             FROM manufacturing_order_output_consumptions
                             WHERE manufacturing_order_id = ? AND material_item_id = ? AND serial_id IS NULL",
                            [$moId, $miId]
                        )->total;
                        // Release remaining: use max(planned, allocated) so under-allocation doesn't leave
                        // reserved_qty stranded — matches forceComplete() formula.
                        $itemAllocated     = max((float) $mi->planned_qty, (float) ($totalAllocated[$miId] ?? 0));
                        $remainingReserved = max(0.0, $itemAllocated - $totalConsumed);
                        if ($remainingReserved > 0) {
                            $stock->adjustReservation($productId, $sourceLocId, -$remainingReserved, 'manufacturing_order', $moId, $mo->mo_number, $miId);
                        }
                    }
                }

                // All reservations released — hard-delete any remaining inv_stock_reservations rows
                $this->db->query(
                    "DELETE FROM inv_stock_reservations
                     WHERE company_id = ? AND document_type = 'manufacturing_order' AND document_id = ?",
                    [$companyId, $moId]
                );
            }

            // ── Step 4: Produce finished goods → log produce movement ─────────────────
            // FG serials created here (after consume) so produce movement always gets a
            // higher ID than all consume movements logged in Step 2.
            $produceResult = $movement->record([
                'movement_type'         => 'mo_produce',
                'location_id'           => $destLocId,
                'product_id'            => (int) $mo->product_id,
                'quantity'              => $outputQty,
                'serial_or_lot_numbers' => $outputSerials,
                'reference_type'        => 'mo_output',
                'reference_id'          => $output->id,
            ]);

            // Link created FG serial IDs to the output record (MO-specific)
            foreach (($produceResult['data']['created_serial_ids'] ?? []) as $fgSerialId) {
                $outputSerial = new Models_ManufacturingOrderOutputSerial();
                $outputSerial->company_id             = $companyId;
                $outputSerial->output_id              = $output->id;
                $outputSerial->manufacturing_order_id = $moId;
                $outputSerial->serial_id              = $fgSerialId;
                if (!$outputSerial->create()) {
                    throw new Service_Exception("Failed to link finished goods serial to output");
                }
            }

            // ── Step 5: History ───────────────────────────────────────────────────────
            $this->addHistory($moId, 'output_recorded', 'Output recorded — ' . number_format($outputQty, 2) . ' units', 'output', $output->id);
            if ($isCompleted) {
                $this->addHistory($moId, 'completed', 'Order completed');
            }

            $this->db->commit();

            return ['success' => true, 'data' => ['output_id' => $output->id, 'status' => $newStatus]];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to record output");
        }
    }

    public function forceComplete(int $id): array
    {
        if (!$this->context->canDo('manufacturing_orders', 'produce')) {
            throw new Service_Exception("You do not have permission to force complete manufacturing orders", 403);
        }

        $mo = $this->getOrFail($id);

        if ($mo->status !== 'in_production') {
            throw new Service_Exception("Only in-production orders can be force completed", 422);
        }

        $companyId   = $this->context->companyId;
        $sourceLocId = (int) $mo->source_location_id;

        $materialItems = $this->db->fetchAll(
            "SELECT mi.id, mi.product_id, mi.planned_qty, p.stock_tracking_method
             FROM manufacturing_order_material_items AS mi
             LEFT JOIN products AS p ON p.id = mi.product_id
             WHERE mi.manufacturing_order_id = ?",
            [$id]
        );

        try {
            $this->db->startTransaction();

            $stock = new Service_Inv_Stock($this->context);

            // Pre-fetch consumed + effective allocation for non-serial items in batch (avoids N×3 queries)
            $nonSerialMiIds = array_values(array_map(
                fn($mi) => (int) $mi->id,
                array_filter($materialItems, fn($mi) => $mi->stock_tracking_method !== 'serial')
            ));

            $consumedByItem       = [];
            $effectiveAllocByItem = [];
            if (!empty($nonSerialMiIds)) {
                $ph2 = implode(',', array_fill(0, count($nonSerialMiIds), '?'));
                $consumedRows = $this->db->fetchAll(
                    "SELECT material_item_id, COALESCE(SUM(consumed_qty), 0) AS total
                     FROM manufacturing_order_output_consumptions
                     WHERE manufacturing_order_id = ? AND serial_id IS NULL AND material_item_id IN ($ph2)
                     GROUP BY material_item_id",
                    array_merge([$id], $nonSerialMiIds)
                );
                foreach ($consumedRows as $r) {
                    $consumedByItem[(int) $r->material_item_id] = (float) $r->total;
                }
                $effectiveAllocByItem = $this->calcItemsEffectiveAllocatedQty($nonSerialMiIds, $id);
            }

            foreach ($materialItems as $mi) {
                $miId      = (int) $mi->id;
                $productId = (int) $mi->product_id;

                if ($mi->stock_tracking_method === 'serial') {
                    // Find remaining reserved serials for this MO item
                    $reservedSerials = $this->db->fetchAll(
                        "SELECT ams.serial_id
                         FROM manufacturing_order_material_allocation_serials AS ams
                         INNER JOIN manufacturing_order_material_allocation_items AS ami ON ami.id = ams.allocation_item_id
                         INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
                         INNER JOIN inv_serials AS s ON s.id = ams.serial_id AND s.status = 'reserved'
                         WHERE ami.manufacturing_order_id = ? AND ami.material_item_id = ?",
                        [$id, $miId]
                    );

                    $reservedCount = count($reservedSerials);

                    if ($reservedCount > 0) {
                        $serialIds = array_map(fn($r) => (int) $r->serial_id, $reservedSerials);
                        $stock->releaseSerials($productId, $sourceLocId, $serialIds);
                    }

                    // Release reserved_qty: reserved serials + any phantom from under-allocation.
                    // On confirm, planned_qty is always reserved. Allocation only adds delta above planned,
                    // so when allocated < planned the gap is never reflected in allocation_serials.
                    $consumedCount  = (int) $this->db->fetchOne(
                        "SELECT COUNT(*) AS total FROM manufacturing_order_output_consumptions
                         WHERE manufacturing_order_id = ? AND material_item_id = ? AND serial_id IS NOT NULL",
                        [$id, $miId]
                    )->total;
                    $totalAllocated = $reservedCount + $consumedCount;
                    $releaseQty     = $reservedCount + max(0, (int) $mi->planned_qty - $totalAllocated);
                    if ($releaseQty > 0) {
                        $stock->adjustReservation($productId, $sourceLocId, -$releaseQty, 'manufacturing_order', $id, $mo->mo_number, $miId);
                    }
                } else {
                    $totalConsumed     = $consumedByItem[$miId] ?? 0.0;
                    $effectiveAlloc    = $effectiveAllocByItem[$miId] ?? 0.0;
                    $itemAllocated     = max((float) $mi->planned_qty, $effectiveAlloc);
                    $remainingReserved = max(0.0, $itemAllocated - $totalConsumed);

                    if ($remainingReserved > 0) {
                        $stock->adjustReservation($productId, $sourceLocId, -$remainingReserved, 'manufacturing_order', $id, $mo->mo_number, $miId);
                    }
                }
            }

            // All reservations released — hard-delete any remaining inv_stock_reservations rows
            $this->db->query(
                "DELETE FROM inv_stock_reservations
                 WHERE company_id = ? AND document_type = 'manufacturing_order' AND document_id = ?",
                [$companyId, $id]
            );

            $this->db->query(
                "UPDATE manufacturing_orders SET status = 'completed' WHERE id = ?",
                [$id]
            );

            $producedQty = (float) $mo->produced_qty;
            $plannedQty  = (float) $mo->planned_qty;
            $this->addHistory(
                $id,
                'force_completed',
                'Order force completed — ' . number_format($producedQty, 2) . ' of ' . number_format($plannedQty, 2) . ' units produced'
            );

            $this->db->commit();

            return ['success' => true, 'data' => []];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to force complete manufacturing order");
        }
    }

    public function recordMaterialReturn(int $moId, array $payload): array
    {
        if (!$this->context->canDo('manufacturing_orders', 'material_return')) {
            throw new Service_Exception("You do not have permission to record material returns", 403);
        }

        $mo = $this->getOrFail($moId);

        $companyId = $this->context->companyId;
        $sourceLocId = (int) $mo->source_location_id;
        $items = $payload['items'] ?? [];
        $notes = trim($payload['notes'] ?? '') ?: null;

        if (empty($items)) {
            $this->addError("No items provided for return", "items");
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        
        // material allocated
        $activeMaterialAllocation = $this->db->fetchAll(
            "SELECT a.id AS allocation_id, b.material_item_id, b.allocated_qty, c.serial_id, d.serial_number FROM manufacturing_order_material_allocations AS a
            INNER JOIN manufacturing_order_material_allocation_items AS b ON b.allocation_id = a.id
            LEFT JOIN manufacturing_order_material_allocation_serials AS c ON c.allocation_item_id = b.id
            LEFT JOIN inv_serials AS d ON d.id = c.serial_id
            WHERE
            a.manufacturing_order_id = ? AND a.status = ?", [$moId, "active"]
        );

        if( empty($activeMaterialAllocation) ) {
            $this->addError("Material is not allocated yet, can not process material return", "items");
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        $allocationsByMaterialItem = [];
        foreach($activeMaterialAllocation as $row) {
            
            $materialItemId = $row->material_item_id;
            $serialId = $row->serial_id;
            $serialNumber = $row->serial_number;            
            $allocatedQty = (float) $row->allocated_qty;

            if( !isset($allocationsByMaterialItem[$materialItemId]) ) {
                $allocationsByMaterialItem[$materialItemId] = [
                    'allocated' => 0,
                    'serials' => []
                ];
            }
            
            if( $serialId ) {
                $allocationsByMaterialItem[$materialItemId]['allocated'] += 1;
                $allocationsByMaterialItem[$materialItemId]['serials'][$serialId] = $serialNumber;
            } else {
                $allocationsByMaterialItem[$materialItemId]['allocated'] += $allocatedQty;
            }
        }


        // materials consumed
        $materialConsumed = $this->db->fetchAll(
            "SELECT a.id AS output_id, b.material_item_id, b.consumed_qty, b.serial_id, c.serial_number FROM manufacturing_order_outputs AS a
            INNER JOIN manufacturing_order_output_consumptions AS b ON b.output_id = a.id
            LEFT JOIN inv_serials AS c ON c.id = b.serial_id
            WHERE
            a.manufacturing_order_id = ?", [$moId]
        );

        $consumptionByMaterialItem = [];        
        foreach($materialConsumed as $row) {
            
            $materialItemId = $row->material_item_id;
            $serialId = $row->serial_id;
            $serialNumber = $row->serial_number;
            $consumedQty = (float) $row->consumed_qty;   

            if( !isset($consumptionByMaterialItem[$materialItemId]) ) {
                $consumptionByMaterialItem[$materialItemId] = [
                    'consumed' => 0,
                    'serials' => [],
                ];
            }

            $consumptionByMaterialItem[$materialItemId]['consumed'] += $consumedQty;
            if( $serialId ) {
                $consumptionByMaterialItem[$materialItemId]['serials'][$serialId] = $serialNumber;
            }
        }

        
        // material returned
        $materialReturned = $this->db->fetchAll(
            "SELECT a.id AS return_id, b.id AS return_item_id, b.material_item_id, b.returned_qty, c.serial_id, d.serial_number FROM manufacturing_order_material_returns AS a
            INNER JOIN manufacturing_order_material_return_items AS b ON b.return_id = a.id
            LEFT JOIN manufacturing_order_material_return_serials AS c ON c.return_item_id = b.id
            LEFT JOIN inv_serials AS d ON d.id = c.serial_id
            WHERE
            a.manufacturing_order_id = ?", [$moId]
        );

        $returnedByMaterialItem = [];
        foreach($materialReturned as $row) {
            
            $materialItemId = $row->material_item_id;
            $serialId = $row->serial_id;
            $serialNumber = $row->serial_number;            
            $returnedQty = (float) $row->returned_qty;

            if( !isset($returnedByMaterialItem[$materialItemId]) ) {
                $returnedByMaterialItem[$materialItemId] = [
                    'returned' => 0,
                    'serials' => []
                ];
            }
            
            if( $serialId ) {
                $returnedByMaterialItem[$materialItemId]['returned'] += 1;
                $returnedByMaterialItem[$materialItemId]['serials'][$serialId] = $serialNumber;
            } else {
                $returnedByMaterialItem[$materialItemId]['returned'] += $returnedQty;
            }
        }

        
        // manufacturning order material items
        $manufacturningOrderDetails = $this->db->fetchAll(
            "SELECT a.id, b.id AS mo_material_id, b.planned_qty, b.actual_qty, c.id AS product_id, c.name AS product_name, c.stock_tracking_method, d.on_hand_qty, d.reserved_qty, e.allow_decimal AS uom_allow_decimal, e.name AS uom_name,
            CASE WHEN a.planned_qty > 0 THEN ROUND((b.planned_qty / a.planned_qty) * a.produced_qty, 6) ELSE 0 END AS expected_consumed
            FROM manufacturing_orders AS a
            INNER JOIN manufacturing_order_material_items AS b ON b.manufacturing_order_id=a.id
            LEFT JOIN products AS c ON c.id=b.product_id
            LEFT JOIN inv_product_stock AS d ON d.product_id=c.id AND d.location_id=a.source_location_id
            LEFT JOIN uoms AS e ON e.id = c.base_uom_id
            WHERE
            a.id = ?", [$moId]
        );


        $moMaterialItemsByMaterialItem = [];
        foreach($manufacturningOrderDetails as $row) {
            $moMaterialItemsByMaterialItem[$row->mo_material_id] = $row;
        }

        
        // validate return material items
        //$invalidReturnItemsErrors = [];
        $updateItems = [];
        foreach($items as $item) {
            
            $returnMaterialItemId = $item["material_item_id"];
            $serialItemIds = $item["serial_ids"] ?? [];
            $returnQty = $item["returned_qty"] ?? count($serialItemIds);

            if( !isset($moMaterialItemsByMaterialItem[$returnMaterialItemId]) ) {
                throw new Service_Exception("Invalid request. Trying to return material items those are not allocated for this manufacturing order", 422);
            }

            $moMaterialItem = (array) $moMaterialItemsByMaterialItem[$returnMaterialItemId];

            $itemProductId = $moMaterialItem["product_id"];
            $itemName = $moMaterialItem["product_name"];
            $itemStockTrackingMethod = $moMaterialItem['stock_tracking_method'];
            $savedOnHandQty = (float) $moMaterialItem['on_hand_qty'];

            $itemAllocation = $allocationsByMaterialItem[$returnMaterialItemId] ?? [];
            $itemAllocatedQty = (float) ($itemAllocation["allocated"] ?? 0);
            $itemAllocatedSerials = $itemAllocation["serials"] ?? [];

            //$returnSerialNumbers = [];
            foreach($serialItemIds as $serialItemId) {
                if( !isset($itemAllocatedSerials[$serialItemId]) ) {
                    throw new Service_Exception("Invalid request. Trying to return material items those are not allocated for this manufacturing order", 422);
                } else {
                    //$returnSerialNumbers[$serialItemId] = $itemAllocatedSerials[$serialItemId];
                }
            }

            $itemReturned = $returnedByMaterialItem[$returnMaterialItemId] ?? [];

            $itemReturnedQty = (float) ($itemReturned["returned"] ?? 0);
            $itemReturnedSerials = $itemReturned["serials"] ?? [];

            $itemConsumed = $consumptionByMaterialItem[$returnMaterialItemId] ?? [];
            $itemConsumedQty = (float) ($itemConsumed["consumed"] ?? 0);
            $itemConsumedSerials = $itemConsumed["serials"] ?? [];

            $plannedQty = (float) $moMaterialItem["planned_qty"]; // MO Planned Qty
            $productionExpectedConsumed = (float) $moMaterialItem["expected_consumed"]; // Expected Consumed for Produced Items as per planned Qty
            $allowedRetunQty = max(0, ($itemAllocatedQty - $productionExpectedConsumed - $itemReturnedQty));
            $extraConsumed = max(0, ($itemConsumedQty - $productionExpectedConsumed));

            if( $itemStockTrackingMethod === "serial" ) {

                $returnSerialFromConsumed = [];
                foreach($itemConsumedSerials as $consumedSerialId => $consumedSeriallNumber) {
                    if( in_array($consumedSerialId, $serialItemIds) ) {
                        $returnSerialFromConsumed[] = $consumedSeriallNumber;
                    }
                }
                $returnConsumedQty = count($returnSerialFromConsumed);

                $alreadyReturnedSerialExist = false;
                foreach($serialItemIds as $serialItemId) {
                    if( isset($itemReturnedSerials[$serialItemId]) ) {
                        $alreadyReturnedSerialExist = true;
                        break;
                    }
                }

                if( $alreadyReturnedSerialExist === true ) {
                    $this->addError("One or more selected serial already returned for item {$itemName}", "item_{$returnMaterialItemId}");
                }
                else if( $returnConsumedQty >  $extraConsumed ) {

                    $extraConsumedReturn = $returnConsumedQty - $extraConsumed;

                    $errMsg = $extraConsumedReturn > 1 ? "Returned {$extraConsumedReturn} serials are already consumed. Remove them from the return list for item {$itemName}" : "Returned {$extraConsumedReturn} serial is already consumed. Remove it from the return list for item {$itemName}";
                    $this->addError($errMsg, "item_{$returnMaterialItemId}");
                
                } else {

                    if( $returnQty > $allowedRetunQty ) {
                        $this->addError("Returned qty is more than allowed for item ".$itemName, "item_{$returnMaterialItemId}");
                    } else {


                        $moReserved = max($plannedQty, $itemAllocatedQty - $itemReturnedQty); // #MO level reserved
                        $remainingReservedQty = $moReserved - $itemConsumedQty; // remaining reservation

                        
                        $onHandQtyChange = $reservedQtyChange = 0;
                        $newReservedQty = $moReserved;
                        if( 0 >= $itemConsumedQty ) {
                            $newReservedQty = max($plannedQty, $remainingReservedQty - $returnQty);
                        } else {

                            $newReservedQty = max($plannedQty, $remainingReservedQty - $returnQty);
                            if( $returnConsumedQty > 0 ) {
                                $onHandQtyChange  = $returnConsumedQty;
                                $newReservedQty -= $returnConsumedQty;
                            }                            
                        }

                        $reservedQtyChange = $newReservedQty - $remainingReservedQty;
                        

                        //$serialItemIds
                        $updateItems[$returnMaterialItemId] = [
                            'material_item_name' => $itemName,
                            'product_id' => $itemProductId,
                            'return_qty' => $returnQty,
                            'saved_on_hand_qty' => $savedOnHandQty,
                            'on_hand_change' => $onHandQtyChange,
                            'reserved_change' => $reservedQtyChange,
                            'saved_reserved' => $remainingReservedQty,
                            'new_reserved' => $newReservedQty,
                            'serial_ids' => $serialItemIds,
                            'stock_tracking_method' => $itemStockTrackingMethod
                        ];
                    }
                }

            } else {

                if ($returnQty > 0 && !(bool)(int)($moMaterialItem['uom_allow_decimal'] ?? 1) && !isWholeNumber((float)$returnQty)) {
                    $this->addError("Return quantity must be a whole number for {$moMaterialItem['uom_name']} for item {$itemName}", "item_{$returnMaterialItemId}");
                } elseif( $returnQty > $allowedRetunQty ) {
                    $this->addError("Returned qty is more than allowed for item ".$itemName, "item_{$returnMaterialItemId}");
                }
                else {

                    $moReserved = max($plannedQty, $itemAllocatedQty - $itemReturnedQty); // #MO level reserved
                    $remainingReservedQty = $moReserved - $itemConsumedQty; // remaining reservation



                    $ogReserved = max($plannedQty, $itemAllocatedQty); // #MO Initial Reserved
                    $tempNewReserved = $ogReserved - $itemConsumedQty; // Reserved after consumption(May be saved reserved qty ????)                    
                    
                    $onHandQtyChange = 0;
                    $newReservedQty = $tempNewReserved;

                    if( 0 >= $itemConsumedQty ) {
                        $newReservedQty = max($plannedQty, $tempNewReserved - $returnQty);
                    } 
                    else {
                        // Items are consumed, logic yet to implement
                        $newReservedQty = max(0, $tempNewReserved - $returnQty);
                    }
                    $reservedQtyChange = $newReservedQty - $tempNewReserved;

                    if( $returnQty > $tempNewReserved ) {
                        
                        $returnFromConsumed = $returnQty - $tempNewReserved;
                        if( $extraConsumed >= $returnFromConsumed ) {
                            $onHandQtyChange = $returnFromConsumed;
                        } else {
                            $this->addError("Trying to return more qty then expected. There should be less available items as per the recorded production for item ".$itemName, "item_{$returnMaterialItemId}");
                        }
                    }

                    $updateItems[$returnMaterialItemId] = [
                        'material_item_name' => $itemName,
                        'product_id' => $itemProductId,
                        'return_qty' => $returnQty,
                        'saved_on_hand_qty' => $savedOnHandQty,
                        'on_hand_change' => $onHandQtyChange,
                        'reserved_change' => $reservedQtyChange,
                        'saved_reserved' => $tempNewReserved,
                        'new_reserved' => $newReservedQty,
                        'stock_tracking_method' => $itemStockTrackingMethod
                    ];
                }
            }
        }    

        if( $this->hasErrors() ) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }


        try {

            $this->db->startTransaction();

            $materialReturn = new Models_ManufacturingOrderMaterialReturn();
            $materialReturn->company_id = $companyId;
            $materialReturn->manufacturing_order_id = $moId;
            $materialReturn->notes = $notes;
            $materialReturn->created_by = $this->context->userId;
            if (!$materialReturn->create()) {
                throw new Service_Exception("Failed to save material return");
            }

            // process return items
            foreach($updateItems as $materialItemId => $updateItem) {
                            
                $returnQty = $updateItem["return_qty"];
                $productId = $updateItem["product_id"];
                $onHandChange = $updateItem["on_hand_change"];
                $reservedChange = $updateItem["reserved_change"];
                $returnSerialIds = $updateItem["serial_ids"] ?? [];
                $itemStockTrackingMethod = $updateItem["stock_tracking_method"];

                // save return item
                $retItem = new Models_ManufacturingOrderMaterialReturnItem();
                $retItem->company_id = $companyId;
                $retItem->return_id = $materialReturn->id;
                $retItem->manufacturing_order_id = $moId;
                $retItem->material_item_id = $materialItemId;
                $retItem->product_id = $productId;
                $retItem->returned_qty = $returnQty;
                if (!$retItem->create()) {
                    throw new Service_Exception("Failed to save return item");
                }


                // save return item serial numbers when item is serial
                if( $itemStockTrackingMethod === "serial" ) {

                    foreach ($returnSerialIds as $returnSerialId) {

                        $retItemSerial = new Models_ManufacturingOrderMaterialReturnSerial();
                        $retItemSerial->company_id = $companyId;
                        $retItemSerial->return_item_id = $retItem->id;
                        $retItemSerial->manufacturing_order_id = $moId;
                        $retItemSerial->material_item_id = $materialItemId;
                        $retItemSerial->product_id = $productId;
                        $retItemSerial->serial_id = $returnSerialId;
                        if (!$retItemSerial->create()) {
                            throw new Service_Exception("Failed to save return item");
                        }
                    }

                    // Restore serials to in_stock — handles both reserved and consumed serials
                    (new Service_Inv_Stock($this->context))->restoreSerials($productId, $sourceLocId, $returnSerialIds);
                }


                // update item inventory - on_hand_qty
                $onHandChange = (float) $updateItem["on_hand_change"];
                $reservedChange = (float) $updateItem["reserved_change"];
                if( $onHandChange > 0 || $onHandChange < 0 ) {
                    (new Service_Inv_Movement($this->context))->record([
                        'movement_type'  => 'mo_return',
                        'location_id'    => $sourceLocId,
                        'product_id'     => $productId,
                        'quantity'       => abs($onHandChange),
                        'reference_type' => 'mo_return',
                        'reference_id'   => $materialReturn->id,
                    ]);
                }

                // update item inventory - reserved_qty
                if( $reservedChange > 0 || $reservedChange < 0 ) {
                    (new Service_Inv_Stock($this->context))->adjustReservation($productId, $sourceLocId, $reservedChange, 'manufacturing_order', $moId, $mo->mo_number, (int) $materialItemId);
                }
            }

            $this->addHistory($moId, 'material_returned', 'Materials returned', 'mo_return', $materialReturn->id);

            $this->db->commit();

            return ['success' => true, 'data' => ['return_id' => $materialReturn->id]];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to record material return");
        }




        /*
        echo "<pre>";        
        echo "MO Details<br>";
        print_r($manufacturningOrderDetails);
        echo "Material Allocation<br>";
        print_r($allocationsByMaterialItem);
        echo "Consumed Details<br>";
        print_r($consumptionByMaterialItem);
        echo "</pre>";
        die;        



        if (!in_array($mo->status, ['confirmed', 'in_production', 'completed'])) {
            throw new Service_Exception("Material returns can only be recorded for confirmed, in-production, or completed orders", 422);
        }

        // Load material items for this MO
        $moItemRows = $this->db->fetchAll(
            "SELECT mi.id, mi.product_id, mi.planned_qty, p.stock_tracking_method, p.name AS product_name
             FROM manufacturing_order_material_items AS mi
             LEFT JOIN products AS p ON p.id = mi.product_id
             WHERE mi.manufacturing_order_id = ?",
            [$moId]
        );

        $moItemMap = [];
        foreach ($moItemRows as $r) {
            $moItemMap[(int) $r->id] = $r;
        }

        // Total consumed qty per material item (all outputs)
        $consumedRows = $this->db->fetchAll(
            "SELECT material_item_id,
                    COUNT(CASE WHEN serial_id IS NOT NULL THEN 1 END) AS serial_consumed,
                    COALESCE(SUM(CASE WHEN serial_id IS NULL THEN consumed_qty END), 0) AS qty_consumed
             FROM manufacturing_order_output_consumptions
             WHERE manufacturing_order_id = ?
             GROUP BY material_item_id",
            [$moId]
        );

        $consumedByItem = [];
        foreach ($consumedRows as $r) {
            $consumedByItem[(int) $r->material_item_id] = [
                'serial_consumed' => (int) $r->serial_consumed,
                'qty_consumed'    => (float) $r->qty_consumed,
            ];
        }

        // Total already returned per material item
        $returnedRows = $this->db->fetchAll(
            "SELECT ri.material_item_id,
                    COUNT(rs.id) AS serial_returned,
                    COALESCE(SUM(CASE WHEN rs.id IS NULL THEN ri.returned_qty END), 0) AS qty_returned
             FROM manufacturing_order_material_return_items AS ri
             LEFT JOIN manufacturing_order_material_return_serials AS rs ON rs.return_item_id = ri.id
             WHERE ri.manufacturing_order_id = ?
             GROUP BY ri.material_item_id",
            [$moId]
        );
        $returnedByItem = [];
        foreach ($returnedRows as $r) {
            $returnedByItem[(int) $r->material_item_id] = [
                'serial_returned' => (int) $r->serial_returned,
                'qty_returned'    => (float) $r->qty_returned,
            ];
        }

        // Total active allocated qty per material item (used for maxReturnable validation)
        $allocatedRows = $this->db->fetchAll(
            "SELECT ami.material_item_id, COALESCE(SUM(ami.allocated_qty), 0) AS qty_allocated
             FROM manufacturing_order_material_allocation_items AS ami
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             WHERE ami.manufacturing_order_id = ?
             GROUP BY ami.material_item_id",
            [$moId]
        );
        $allocatedByItem = [];
        foreach ($allocatedRows as $r) {
            $allocatedByItem[(int) $r->material_item_id] = (float) $r->qty_allocated;
        }

        // Expected consumed per material item based on BOM proportion × produced qty
        $expectedRows = $this->db->fetchAll(
            "SELECT mi.id AS material_item_id,
                    CASE WHEN mo.planned_qty > 0
                        THEN ROUND((mi.planned_qty / mo.planned_qty) * mo.produced_qty, 6)
                        ELSE 0 END AS expected_consumed
             FROM manufacturing_order_material_items mi
             JOIN manufacturing_orders mo ON mo.id = mi.manufacturing_order_id
             WHERE mi.manufacturing_order_id = ?",
            [$moId]
        );
        $expectedByItem = [];
        foreach ($expectedRows as $r) {
            $expectedByItem[(int) $r->material_item_id] = (float) $r->expected_consumed;
        }

        // All serials allocated to this MO (consumed or still reserved) — used for return validation and stock split
        $allocSerialRows = $this->db->fetchAll(
            "SELECT ams.serial_id, ami.material_item_id, s.serial_number, s.status AS serial_status
             FROM manufacturing_order_material_allocation_serials AS ams
             INNER JOIN manufacturing_order_material_allocation_items AS ami ON ami.id = ams.allocation_item_id
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             INNER JOIN inv_serials AS s ON s.id = ams.serial_id
             WHERE ami.manufacturing_order_id = ?",
            [$moId]
        );
        $allocSerialsByItem = []; // [miId][serial_id] => ['serial_number' => ..., 'status' => ...]
        foreach ($allocSerialRows as $r) {
            $allocSerialsByItem[(int) $r->material_item_id][(int) $r->serial_id] = [
                'serial_number' => $r->serial_number,
                'status'        => $r->serial_status,
            ];
        }

        // Already-returned serial IDs (to prevent double-return)
        $alreadyReturnedSerialRows = $this->db->fetchAll(
            "SELECT rs.serial_id
             FROM manufacturing_order_material_return_serials AS rs
             WHERE rs.manufacturing_order_id = ?",
            [$moId]
        );
        $alreadyReturnedSerialIds = array_flip(array_map(fn($r) => (int) $r->serial_id, $alreadyReturnedSerialRows));

        // Parse and validate payload items
        $serialItemsToProcess    = [];  // miId => [serial_id, ...]
        $nonSerialItemsToProcess = [];  // miId => qty

        foreach ($items as $item) {

            
            $miId = (int) ($item['material_item_id'] ?? 0);
            if (!$miId || !isset($moItemMap[$miId])) continue;

            $mi = $moItemMap[$miId];

            if ($mi->stock_tracking_method === 'serial') {

                $serialIds = array_map('intval', (array) ($item['serial_ids'] ?? []));
                if (empty($serialIds)) continue;

                $allocPool = $allocSerialsByItem[$miId] ?? [];
                foreach ($serialIds as $sid) {
                    if (!isset($allocPool[$sid])) {
                        $this->addError("Serial ID {$sid} is not allocated to this manufacturing order", "items");
                        return ['success' => false, 'errors' => $this->getErrors()];
                    }
                    if (isset($alreadyReturnedSerialIds[$sid])) {
                        $sn = $allocPool[$sid]['serial_number'] ?? $sid;
                        $this->addError("Serial '{$sn}' has already been returned", "items");
                        return ['success' => false, 'errors' => $this->getErrors()];
                    }
                }
                $serialItemsToProcess[$miId] = ['item' => $mi, 'serial_ids' => $serialIds];

            } else {
                $returnQty     = (float) ($item['returned_qty'] ?? 0);
                if ($returnQty <= 0) continue;

                $allocatedQty = $allocatedByItem[$miId] ?? 0.0;
                $actualConsumed = $consumedByItem[$miId]['qty_consumed'] ?? 0.0;
                $expectedConsumed = $expectedByItem[$miId] ?? 0.0;
                $lockedConsumed = min($actualConsumed, $expectedConsumed);
                $alreadyReturned = $returnedByItem[$miId]['qty_returned'] ?? 0.0;
                $maxReturnable = max(0.0, $allocatedQty - $lockedConsumed - $alreadyReturned);

                if ($returnQty > $maxReturnable + 0.0001) {
                    $name = $mi->product_name ?? 'Unknown';
                    $this->addError("Return qty for '{$name}' exceeds max returnable qty (" . number_format($maxReturnable, 4) . ")", "items");
                    return ['success' => false, 'errors' => $this->getErrors()];
                }
                $nonSerialItemsToProcess[$miId] = ['item' => $mi, 'qty' => $returnQty];
            }
        }

        if (empty($serialItemsToProcess) && empty($nonSerialItemsToProcess)) {
            $this->addError("No valid items provided for return", "items");
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        try {

            $this->db->startTransaction();

            $ret = new Models_ManufacturingOrderMaterialReturn();
            $ret->company_id = $companyId;
            $ret->manufacturing_order_id = $moId;
            $ret->notes = $notes;
            $ret->created_by = $this->context->userId;
            if (!$ret->create()) {
                throw new Service_Exception("Failed to save material return");
            }

            // Serial items
            foreach ($serialItemsToProcess as $miId => $entry) {
                
                $mi = $entry['item'];
                $serialIds = $entry['serial_ids'];
                $count = count($serialIds);

                $retItem = new Models_ManufacturingOrderMaterialReturnItem();
                $retItem->company_id = $companyId;
                $retItem->return_id = $ret->id;
                $retItem->manufacturing_order_id = $moId;
                $retItem->material_item_id = $miId;
                $retItem->product_id = (int) $mi->product_id;
                $retItem->returned_qty = $count;
                if (!$retItem->create()) {
                    throw new Service_Exception("Failed to save return item");
                }

                foreach ($serialIds as $serialId) {

                    $retSerial = new Models_ManufacturingOrderMaterialReturnSerial();
                    $retSerial->company_id = $companyId;
                    $retSerial->return_item_id = $retItem->id;
                    $retSerial->manufacturing_order_id = $moId;
                    $retSerial->material_item_id = $miId;
                    $retSerial->product_id = (int) $mi->product_id;
                    $retSerial->serial_id = $serialId;
                    if (!$retSerial->create()) {
                        throw new Service_Exception("Failed to save return serial");
                    }
                }

                // Split serials by current status — determines which stock bucket to restore
                $consumedSerialIds = [];
                $reservedSerialIds = [];
                $allocPool = $allocSerialsByItem[$miId] ?? [];
                foreach ($serialIds as $serialId) {
                    if (($allocPool[$serialId]['status'] ?? '') === 'reserved') {
                        $reservedSerialIds[] = $serialId;
                    } else {
                        $consumedSerialIds[] = $serialId;
                    }
                }


                #Ravi - Here is the error, its blindly putting returned items to in-stock. This should only put items to stock those were excessed used in production(PRODUCTION CONSUMMED > PLANNED)
                // Revert all returned serials to in_stock
                $ph = implode(',', array_fill(0, $count, '?'));
                $this->db->query("UPDATE inv_serials SET status = 'in_stock' WHERE id IN ($ph)", $serialIds);

                // Restore inv_serial_stock — INSERT IGNORE so duplicate is safe
                foreach ($serialIds as $serialId) {
                    $this->db->query(
                        "INSERT IGNORE INTO inv_serial_stock (company_id, serial_id, location_id, product_id) VALUES (?, ?, ?, ?)",
                        [$companyId, $serialId, $sourceLocId, (int) $mi->product_id]
                    );
                }

                // Consumed returns → restore on_hand_qty
                $consumedCount = count($consumedSerialIds);
                if ($consumedCount > 0) {
                    $stockRow = $this->db->fetchOne(
                        "SELECT on_hand_qty FROM inv_product_stock WHERE company_id = ? AND location_id = ? AND product_id = ?",
                        [$companyId, $sourceLocId, (int) $mi->product_id]
                    );
                    $oldQty = $stockRow ? (float) $stockRow->on_hand_qty : 0.0;
                    $newQty = $oldQty + $consumedCount;
                    $this->db->query(
                        "UPDATE inv_product_stock SET on_hand_qty = ? WHERE company_id = ? AND location_id = ? AND product_id = ?",
                        [$newQty, $companyId, $sourceLocId, (int) $mi->product_id]
                    );
                    $this->logMoStockMovement($sourceLocId, (int) $mi->product_id, 'production_return', $consumedCount, $oldQty, $newQty, 'mo_return', $ret->id);
                }

                // Reserved returns → release reserved_qty only
                $reservedCount = count($reservedSerialIds);
                if ($reservedCount > 0) {
                    $this->applyMoReservedDelta($companyId, $sourceLocId, (int) $mi->product_id, -$reservedCount);
                }
            }

            // Non-serial items
            foreach ($nonSerialItemsToProcess as $miId => $entry) {
                
                $mi = $entry['item'];
                $qty = $entry['qty'];

                $retItem = new Models_ManufacturingOrderMaterialReturnItem();
                $retItem->company_id = $companyId;
                $retItem->return_id = $ret->id;
                $retItem->manufacturing_order_id = $moId;
                $retItem->material_item_id = $miId;
                $retItem->product_id = (int) $mi->product_id;
                $retItem->returned_qty = $qty;
                if (!$retItem->create()) {
                    throw new Service_Exception("Failed to save return item");
                }

                // Split return qty: reserved portion (still in warehouse) vs over-consumed portion (waste/scrap)
                $actualConsumed   = $consumedByItem[$miId]['qty_consumed'] ?? 0.0;
                $expectedConsumed = $expectedByItem[$miId] ?? 0.0;
                $allocatedQty  = $allocatedByItem[$miId] ?? 0.0;
                $stillReserved = max(0.0, $allocatedQty - $actualConsumed);
                $fromReserved = min($qty, $stillReserved);
                $fromOverConsumed = max(0.0, $qty - $fromReserved);

                // Over-consumed (scrap) portion → restore on_hand_qty (these went beyond what finished goods locked)
                if ($fromOverConsumed > 0.0001) {
                    
                    $stockRow = $this->db->fetchOne(
                        "SELECT on_hand_qty FROM inv_product_stock WHERE company_id = ? AND location_id = ? AND product_id = ?",
                        [$companyId, $sourceLocId, (int) $mi->product_id]
                    );
                    
                    $oldQty = $stockRow ? (float) $stockRow->on_hand_qty : 0.0;
                    $newQty = $oldQty + $fromOverConsumed;
                    
                    $this->db->query(
                        "UPDATE inv_product_stock SET on_hand_qty = ? WHERE company_id = ? AND location_id = ? AND product_id = ?",
                        [$newQty, $companyId, $sourceLocId, (int) $mi->product_id]
                    );
                    
                    $this->logMoStockMovement($sourceLocId, (int) $mi->product_id, 'production_return', $fromOverConsumed, $oldQty, $newQty, 'mo_return', $ret->id);
                }

                // Reserved portion → release reserved_qty only (materials still in warehouse, on_hand unchanged)
                if ($fromReserved > 0.0001) {
                    $this->applyMoReservedDelta($companyId, $sourceLocId, (int) $mi->product_id, -$fromReserved);
                }
            }

            $this->addHistory($moId, 'material_returned', 'Materials returned to warehouse', 'mo_return', $ret->id);

            $this->db->commit();

            return ['success' => true, 'data' => ['return_id' => $ret->id]];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to record material return");
        }
        */

    }

    public function cancel(int $id): array
    {
        if (!$this->context->canDo('manufacturing_orders', 'cancel')) {
            throw new Service_Exception("You do not have permission to cancel manufacturing orders", 403);
        }

        $mo = $this->getOrFail($id);

        if (!in_array($mo->status, ['draft', 'confirmed'])) {
            throw new Service_Exception("Only draft or confirmed orders can be cancelled", 422);
        }

        try {

            $this->db->startTransaction();

            $cancelLocId = (int) $mo->source_location_id;
            $stock       = new Service_Inv_Stock($this->context);

            // Revert serials from ACTIVE allocations back to in_stock
            $allocatedSerials = $this->db->fetchAll(
                "SELECT ams.serial_id
                 FROM manufacturing_order_material_allocation_serials AS ams
                 INNER JOIN manufacturing_order_material_allocation_items AS ami ON ami.id = ams.allocation_item_id
                 INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
                 WHERE ami.manufacturing_order_id = ?",
                [$id]
            );
            if (!empty($allocatedSerials)) {
                $ids = array_map(fn($r) => (int) $r->serial_id, $allocatedSerials);
                $stock->releaseSerials(0, $cancelLocId, $ids);
            }

            $mo->status = 'cancelled';

            if (!$mo->update()) {
                throw new Service_Exception("Failed to cancel manufacturing order");
            }

            // Release reserved_qty — reads amounts from inv_stock_reservations and clears rows
            $stock->releaseForDocument('manufacturing_order', $id);

            $this->addHistory($id, 'cancelled', 'Order cancelled');

            $this->db->commit();

            return ['success' => true, 'data' => []];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to cancel manufacturing order");
        }
    }
}
