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

            $itemPlannedQty = round(($plannedQty / $outputQty) * (float) $bomItem->qty, 4);

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
                $warnings[]     = "{$productName} - required " . formatQty($plannedQty) . ", on hand " . formatQty($onHand) . $reservedSuffix;
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

    private function decrementAllocationReservation(int $productId, int $locationId, int $moId, int $miId, float $qty, float $softReserved): void
    {
        $companyId = $this->context->companyId;

        $reservedRelease = min($qty, $softReserved);
        if ($reservedRelease > 0) {
            $this->db->query(
                "UPDATE inv_product_stock
                 SET reserved_qty = GREATEST(0, reserved_qty - ?)
                 WHERE company_id = ? AND location_id = ? AND product_id = ?",
                [$reservedRelease, $companyId, $locationId, $productId]
            );
        }

        $now = date('Y-m-d H:i:s');
        $this->db->query(
            "UPDATE inv_stock_reservations
             SET reserved_qty = GREATEST(0, reserved_qty - ?), updated_at = ?
             WHERE company_id = ? AND document_type = 'manufacturing_order'
               AND document_id = ? AND document_line_id = ?",
            [$qty, $now, $companyId, $moId, $miId]
        );

        $this->db->query(
            "DELETE FROM inv_stock_reservations
             WHERE company_id = ? AND document_type = 'manufacturing_order'
               AND document_id = ? AND document_line_id = ? AND reserved_qty <= 0",
            [$companyId, $moId, $miId]
        );
    }

    private function incrementReturnReservation(int $productId, int $locationId, int $moId, int $miId, float $qty): void
    {
        $companyId = $this->context->companyId;

        $this->db->query(
            "UPDATE inv_product_stock
             SET reserved_qty = reserved_qty + ?
             WHERE company_id = ? AND location_id = ? AND product_id = ?",
            [$qty, $companyId, $locationId, $productId]
        );

        $now = date('Y-m-d H:i:s');
        $this->db->query(
            "INSERT INTO inv_stock_reservations
                 (company_id, product_id, location_id, document_type, document_id, document_number, document_line_id, reserved_qty)
             VALUES (?, ?, ?, 'manufacturing_order', ?, (SELECT mo_number FROM manufacturing_orders WHERE id = ?), ?, ?)
             ON DUPLICATE KEY UPDATE
                 reserved_qty = LEAST(
                     (SELECT planned_qty FROM manufacturing_order_material_items WHERE id = ?),
                     reserved_qty + VALUES(reserved_qty)
                 ),
                 updated_at = ?",
            [$companyId, $productId, $locationId, $moId, $moId, $miId, $qty, $miId, $now]
        );
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
                'id' => (int) $row->bom_id,
                'name' => $row->bom_name,
                'output_qty' => (float) $row->output_qty,
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

        // Allocated qty per material item from ACTIVE allocations  -  single query, no UNION
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

        // Returned qty per material item  -  subtracted from allocated to get net allocated
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

        // Consumed qty per non-serial material item - reduces returnable qty
        $consumedByItem = [];
        foreach ($this->db->fetchAll(
            "SELECT material_item_id, SUM(consumed_qty) AS qty_consumed
             FROM manufacturing_order_output_consumptions
             WHERE manufacturing_order_id = ? AND serial_id IS NULL
             GROUP BY material_item_id",
            [$id]
        ) as $r) {
            $consumedByItem[(int) $r->material_item_id] = (float) $r->qty_consumed;
        }

        // Consumed serial count per serial material item (status = 'consumed')
        $consumedSerialsByItem = [];
        foreach ($this->db->fetchAll(
            "SELECT ooc.material_item_id, COUNT(*) AS consumed_count
             FROM manufacturing_order_output_consumptions AS ooc
             WHERE ooc.manufacturing_order_id = ? AND ooc.serial_id IS NOT NULL
             GROUP BY ooc.material_item_id",
            [$id]
        ) as $r) {
            $consumedSerialsByItem[(int) $r->material_item_id] = (int) $r->consumed_count;
        }

        $companyId   = $this->context->companyId;
        $sourceLocId = (int) $mo->source_location_id;

        $materialItems = $mo->material_items;

        // Picked serials per material item (for Record Production drawer - status = 'picked' after allocation)
        $pickedSerialsByItem = [];
        $pickedSerialRows = $this->db->fetchAll(
            "SELECT DISTINCT ami.material_item_id, ams.serial_id, s.serial_number
             FROM manufacturing_order_material_allocation_serials AS ams
             INNER JOIN manufacturing_order_material_allocation_items AS ami ON ami.id = ams.allocation_item_id
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             INNER JOIN inv_serials AS s ON s.id = ams.serial_id AND s.status = 'picked'
             WHERE ami.manufacturing_order_id = ?
             ORDER BY ami.material_item_id ASC, ams.serial_id ASC",
            [$id]
        );
        foreach ($pickedSerialRows as $r) {
            $pickedSerialsByItem[(int) $r->material_item_id][] = [
                'serial_id'     => (int) $r->serial_id,
                'serial_number' => $r->serial_number,
            ];
        }

        foreach ($materialItems as &$item) {
            $miId = (int) $item->id;
            if ($item->stock_tracking_method === 'serial') {
                // For serials, count picked serials directly — gross SUM overcounts
                // across multiple allocate/return cycles on the same material item
                $picked        = count($pickedSerialsByItem[$miId] ?? []);
                $consumedCount = $consumedSerialsByItem[$miId] ?? 0;
                $item->allocated_qty = $picked + $consumedCount; // gross - returned (picked still on floor + consumed)
                $item->on_floor_qty  = $picked;                   // physically on floor (picked only)
            } else {
                $gross    = $allocByItem[$miId] ?? 0.0;
                $returned = $returnedByItem[$miId] ?? 0.0;
                $consumed = $consumedByItem[$miId] ?? 0.0;
                $item->allocated_qty = max(0.0, round($gross - $returned, 4));
                $item->on_floor_qty  = max(0.0, round($gross - $returned - $consumed, 4));
            }
            $item->picked_serials = $pickedSerialsByItem[$miId] ?? [];
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

        // Serial numbers per allocation_item  -  include id and status so UI can show consumed vs reserved and identify for returns
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

        foreach ($allocations as &$alloc) {
            $alloc->items = $itemsByAlloc[(int) $alloc->id] ?? [];
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
            $returnedQtyByItem[$miId] = round(($returnedQtyByItem[$miId] ?? 0.0) + (float) $ri->returned_qty, 4);
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
            // so net_consumed equals total_consumed  -  returns do not reduce actual consumption.
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

        // Returned qty per item  -  reduces effective allocation
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
            $allocated = max(0.0, round(($allocSumMap[$miId] ?? 0.0) - ($returnSumMap[$miId] ?? 0.0), 4));

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

        $moItemRows = $this->db->fetchAll(
            "SELECT mi.id, mi.product_id, mi.planned_qty, p.stock_tracking_method,
                    p.name AS product_name,
                    u.allow_decimal AS uom_allow_decimal, u.name AS uom_name,
                    COALESCE(s.on_hand_qty, 0)  AS on_hand_qty,
                    COALESCE(s.reserved_qty, 0) AS reserved_qty
             FROM manufacturing_order_material_items AS mi
             INNER JOIN products AS p ON p.id = mi.product_id AND p.company_id = ?
             LEFT JOIN uoms AS u ON u.id = p.base_uom_id
             LEFT JOIN inv_product_stock AS s
                   ON s.product_id = p.id AND s.company_id = p.company_id AND s.location_id = ?
             WHERE mi.manufacturing_order_id = ?",
            [$companyId, $locationId, $moId]
        );
        $moItemMap = [];
        foreach ($moItemRows as $r) {
            $moItemMap[(int) $r->id] = $r;
        }

        // Remaining soft reservation per material item for reservation decrement math at issue time
        $softReservationRows = $this->db->fetchAll(
            "SELECT document_line_id AS material_item_id, reserved_qty
             FROM inv_stock_reservations
             WHERE company_id = ? AND document_type = 'manufacturing_order' AND document_id = ?",
            [$companyId, $moId]
        );
        $softReservationMap = [];
        foreach ($softReservationRows as $r) {
            $softReservationMap[(int) $r->material_item_id] = (float) $r->reserved_qty;
        }

        $serialItems      = [];  // miId => ['item' => row, 'serial_numbers' => []]
        $nonSerialItems   = [];  // miId => ['item' => row, 'qty' => float]
        $allSerialNumbers = [];

        foreach ($items as $item) {
            $miId = (int) ($item['material_item_id'] ?? 0);
            if (!$miId || !isset($moItemMap[$miId])) {
                $this->addError("Invalid material item", "items");
                return ['success' => false, 'errors' => $this->getErrors()];
            }

            $miRow  = $moItemMap[$miId];
            $method = $miRow->stock_tracking_method;

            if ($method === 'none') {
                $qty = (float) ($item['qty'] ?? 0);
                if ($qty > 0) {
                    $nonSerialItems[$miId] = ['item' => $miRow, 'qty' => $qty];
                }

            } elseif ($method === 'serial') {
                $serials = array_values(array_filter(array_map('trim', (array) ($item['serial_numbers'] ?? []))));
                if (!empty($serials)) {
                    $serialItems[$miId] = ['item' => $miRow, 'serial_numbers' => $serials];
                    foreach ($serials as $sn) { $allSerialNumbers[] = $sn; }
                }

            } else {
                $qty     = (float) ($item['qty'] ?? 0);
                $onHand  = (float) $miRow->on_hand_qty;
                if ($qty <= 0) continue;
                if ($qty > $onHand + 0.0001) {
                    $this->addError(
                        "Insufficient stock for {$miRow->product_name}: trying to allocate " . formatQty($qty) . ", on hand " . formatQty($onHand),
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

        $serialValidMap = [];
        if (!empty($allSerialNumbers)) {
            $ph   = implode(',', array_fill(0, count($allSerialNumbers), '?'));
            $rows = $this->db->fetchAll(
                "SELECT s.id, s.serial_number, s.product_id, s.status
                 FROM inv_serials AS s
                 INNER JOIN inv_serial_stock AS ss ON ss.serial_id = s.id AND ss.location_id = ?
                 WHERE s.company_id = ? AND s.serial_number IN ($ph)",
                array_merge([$locationId, $companyId], $allSerialNumbers)
            );
            foreach ($rows as $r) {
                $serialValidMap[$r->serial_number] = $r;
            }

            foreach ($serialItems as $miId => $entry) {
                foreach ($entry['serial_numbers'] as $sn) {
                    if (!isset($serialValidMap[$sn])) {
                        $this->addError("Serial '$sn' is not available at the source warehouse", "items");
                        return ['success' => false, 'errors' => $this->getErrors()];
                    }
                    $vs = $serialValidMap[$sn];
                    if ($vs->status !== 'in_stock') {
                        $this->addError("Serial '$sn' is not available (status: {$vs->status})", "items");
                        return ['success' => false, 'errors' => $this->getErrors()];
                    }
                    if ((int) $vs->product_id !== (int) $entry['item']->product_id) {
                        $this->addError("Serial '$sn' does not belong to the expected product", "items");
                        return ['success' => false, 'errors' => $this->getErrors()];
                    }
                }
            }
        }

        try {
            
            $this->db->startTransaction();

            $alloc                         = new Models_ManufacturingOrderMaterialAllocation();
            $alloc->company_id             = $companyId;
            $alloc->manufacturing_order_id = $moId;
            $alloc->notes                  = trim($payload['notes'] ?? '') ?: null;
            $alloc->created_by             = $this->context->userId;
            if (!$alloc->create()) {
                throw new Service_Exception("Failed to save allocation");
            }

            $movement = new Service_Inv_Movement($this->context);

            foreach ($serialItems as $miId => $entry) {
                $productId   = (int) $entry['item']->product_id;
                $serialCount = count($entry['serial_numbers']);

                $allocItem = $this->createAllocationItem($alloc->id, $moId, $companyId, $miId, $productId, $serialCount);
                foreach ($entry['serial_numbers'] as $sn) {
                    $vs  = $serialValidMap[$sn];
                    $row = new Models_ManufacturingOrderMaterialAllocationSerial();
                    $row->company_id             = $companyId;
                    $row->allocation_item_id     = $allocItem->id;
                    $row->manufacturing_order_id = $moId;
                    $row->product_id             = $productId;
                    $row->serial_id              = (int) $vs->id;
                    if (!$row->create()) {
                        throw new Service_Exception("Failed to save allocation serial");
                    }
                }

                // Flip serials in_stock to picked; clear doc fields (ownership tracked via allocation tables)
                $serialIds = array_map(fn($sn) => (int) $serialValidMap[$sn]->id, $entry['serial_numbers']);
                $ph        = implode(',', array_fill(0, count($serialIds), '?'));
                $this->db->query(
                    "UPDATE inv_serials SET status = 'picked'
                     WHERE id IN ($ph) AND company_id = ?",
                    array_merge($serialIds, [$companyId])
                );
                $this->db->query(
                    "UPDATE inv_serial_stock SET reserved_doc_type = NULL, reserved_doc_id = NULL
                     WHERE serial_id IN ($ph) AND company_id = ?",
                    array_merge($serialIds, [$companyId])
                );

                $result = $movement->record([
                    'movement_type'  => 'mo_issue',
                    'location_id'    => $locationId,
                    'product_id'     => $productId,
                    'quantity'       => $serialCount,
                    'reference_type' => 'mo_allocation',
                    'reference_id'   => $alloc->id,
                ]);
                if ($result['success'] === false) {
                    throw new Service_Exception("Failed to record issue movement for {$entry['item']->product_name}");
                }

                $this->decrementAllocationReservation($productId, $locationId, $moId, $miId, (float) $serialCount, $softReservationMap[$miId] ?? 0.0);
            }

            foreach ($nonSerialItems as $miId => $entry) {
                $productId = (int) $entry['item']->product_id;
                $qty       = $entry['qty'];

                $this->createAllocationItem($alloc->id, $moId, $companyId, $miId, $productId, $qty);

                if ($entry['item']->stock_tracking_method !== 'none') {
                    $result = $movement->record([
                        'movement_type'  => 'mo_issue',
                        'location_id'    => $locationId,
                        'product_id'     => $productId,
                        'quantity'       => $qty,
                        'reference_type' => 'mo_allocation',
                        'reference_id'   => $alloc->id,
                    ]);
                    if ($result['success'] === false) {
                        throw new Service_Exception("Failed to record issue movement for {$entry['item']->product_name}");
                    }

                    $this->decrementAllocationReservation($productId, $locationId, $moId, $miId, $qty, $softReservationMap[$miId] ?? 0.0);
                }
            }

            $this->recalcAllocationStatus($moId);

            if ($mo->status === 'confirmed') {
                $this->db->query(
                    "UPDATE manufacturing_orders SET status = 'in_production', updated_at = NOW() WHERE id = ?",
                    [$moId]
                );
                $this->addHistory($moId, 'status_changed', 'Status changed to In Production', null, null);
            }

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

        $companyId        = $this->context->companyId;
        $outputQty        = (float) ($payload['output_qty'] ?? 0);
        $destinationLocId = (int)   ($payload['destination_location_id'] ?? $mo->destination_location_id);
        $notes            = trim($payload['notes'] ?? '') ?: null;
        $plannedQty       = (float) $mo->planned_qty;
        $producedSoFar    = (float) $mo->produced_qty;
        $remaining        = round($plannedQty - $producedSoFar, 4);

        $consumptionMap = [];
        foreach (($payload['material_consumption'] ?? []) as $c) {
            $miId = (int) ($c['material_item_id'] ?? 0);
            if (!$miId) continue;
            $isSerial = array_key_exists('serial_ids', $c);
            $consumptionMap[$miId] = [
                'actual_qty' => $isSerial ? count((array) $c['serial_ids']) : (float) ($c['actual_qty'] ?? 0),
                'serial_ids' => $isSerial ? array_map('intval', (array) $c['serial_ids']) : [],
                'is_serial'  => $isSerial,
            ];
        }

        $materialItems = $this->db->fetchAll(
            "SELECT mi.id, mi.product_id, mi.planned_qty, p.stock_tracking_method, p.name AS product_name
             FROM manufacturing_order_material_items AS mi
             LEFT JOIN products AS p ON p.id = mi.product_id
             WHERE mi.manufacturing_order_id = ?",
            [$moId]
        );

        $pickedSerialRows = $this->db->fetchAll(
            "SELECT DISTINCT ami.material_item_id, ams.serial_id
             FROM manufacturing_order_material_allocation_serials AS ams
             INNER JOIN manufacturing_order_material_allocation_items AS ami ON ami.id = ams.allocation_item_id
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             INNER JOIN inv_serials AS s ON s.id = ams.serial_id AND s.status = 'picked'
             WHERE ami.manufacturing_order_id = ?",
            [$moId]
        );
        $pickedSerialsByItem = [];
        foreach ($pickedSerialRows as $r) {
            $pickedSerialsByItem[(int) $r->material_item_id][] = (int) $r->serial_id;
        }

        $totalAllocated = [];
        foreach ($this->db->fetchAll(
            "SELECT ami.material_item_id, SUM(ami.allocated_qty) AS total
             FROM manufacturing_order_material_allocation_items AS ami
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             WHERE ami.manufacturing_order_id = ?
             GROUP BY ami.material_item_id",
            [$moId]
        ) as $r) {
            $totalAllocated[(int) $r->material_item_id] = (float) $r->total;
        }

        $priorConsumed = [];
        foreach ($this->db->fetchAll(
            "SELECT material_item_id, COALESCE(SUM(consumed_qty), 0) AS total
             FROM manufacturing_order_output_consumptions
             WHERE manufacturing_order_id = ? AND serial_id IS NULL
             GROUP BY material_item_id",
            [$moId]
        ) as $r) {
            $priorConsumed[(int) $r->material_item_id] = (float) $r->total;
        }

        $totalReturned = [];
        foreach ($this->db->fetchAll(
            "SELECT material_item_id, SUM(returned_qty) AS total
             FROM manufacturing_order_material_return_items
             WHERE manufacturing_order_id = ?
             GROUP BY material_item_id",
            [$moId]
        ) as $r) {
            $totalReturned[(int) $r->material_item_id] = (float) $r->total;
        }

        // -- Validation --------------------------------------------------------
        $fieldErrors   = [];
        $shortageItems = [];

        if ($outputQty <= 0) {
            $fieldErrors['output_qty'][] = "Output quantity must be greater than zero";
        } elseif ($outputQty > $remaining + 0.0001) {
            $fieldErrors['output_qty'][] = "Output quantity exceeds remaining quantity (" . number_format($remaining, 4) . ")";
        }

        $destLoc = new Models_Location($destinationLocId);
        if ($destLoc->isEmpty || (int) $destLoc->company_id !== $companyId || $destLoc->status !== 'active') {
            $fieldErrors['destination_location_id'][] = validationErrMsg("missing_or_invalid", "Destination warehouse");
        }

        $productRow = $this->db->fetchOne(
            "SELECT p.stock_tracking_method, u.allow_decimal AS uom_allow_decimal, u.name AS uom_name
             FROM products p LEFT JOIN uoms u ON u.id = p.base_uom_id
             WHERE p.id = ? AND p.company_id = ?",
            [(int) $mo->product_id, $companyId]
        );
        $isFgSerial    = $productRow && $productRow->stock_tracking_method === 'serial';
        $outputSerials = [];

        if ($isFgSerial) {
            if ($outputQty > 0 && $outputQty != (int) $outputQty) {
                $fieldErrors['output_qty'][] = "Output quantity must be a whole number for serial-tracked products";
            }
            $rawSerials    = array_map('trim', (array) ($payload['serial_numbers'] ?? []));
            $outputSerials = array_values(array_filter($rawSerials, fn($s) => $s !== ''));
            $required      = (int) $outputQty;
            if (count($outputSerials) !== $required) {
                $fieldErrors['serial_numbers'][] = "You must provide exactly $required serial number(s) for this output (" . count($outputSerials) . " provided)";
            } elseif (count($outputSerials) !== count(array_unique($outputSerials))) {
                $fieldErrors['serial_numbers'][] = "Duplicate serial numbers in the submission";
            } elseif (!empty($outputSerials)) {
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
        } elseif ($productRow && !(bool)(int)$productRow->uom_allow_decimal && $outputQty > 0 && !isWholeNumber($outputQty)) {
            $fieldErrors['output_qty'][] = "Output quantity must be a whole number for {$productRow->uom_name}";
        }

        $materialErrors = [];
        foreach ($materialItems as $mi) {
            $miId     = (int) $mi->id;
            $itemName = $mi->product_name ?? 'Unknown';

            if ($mi->stock_tracking_method === 'serial') {
                $specifiedIds = $consumptionMap[$miId]['serial_ids'] ?? [];
                $pickedPool   = $pickedSerialsByItem[$miId] ?? [];
                if (empty($specifiedIds)) continue;
                foreach ($specifiedIds as $sid) {
                    if (!in_array($sid, $pickedPool)) {
                        $materialErrors[] = "A specified serial is not picked for component: $itemName";
                        break;
                    }
                }
            } else {
                $actualQty      = $consumptionMap[$miId]['actual_qty'] ?? 0.0;
                $allocated      = $totalAllocated[$miId] ?? 0.0;
                $returned       = $totalReturned[$miId]  ?? 0.0;
                $alreadyUsed    = $priorConsumed[$miId]  ?? 0.0;
                $remainingAlloc = $allocated - $returned - $alreadyUsed;
                if ($actualQty > 0 && $actualQty > $remainingAlloc + 0.0001) {
                    $shortageItems[] = [
                        'name'      => $itemName,
                        'required'  => round($actualQty, 4),
                        'allocated' => round(max(0.0, $remainingAlloc), 4),
                        'shortage'  => round($actualQty - max(0.0, $remainingAlloc), 4),
                        'is_serial' => false,
                    ];
                }
            }
        }

        if (!empty($materialErrors)) {
            $fieldErrors['material_consumption'][] = implode('. ', $materialErrors);
        }

        if (!empty($fieldErrors) || !empty($shortageItems)) {
            $errors = [];
            foreach ($fieldErrors as $key => $msgs) {
                $errors[$key] = implode('. ', $msgs);
            }
            return ['success' => false, 'errors' => $errors, 'shortage_items' => $shortageItems];
        }

        // -- Transaction -------------------------------------------------------
        try {
            $this->db->startTransaction();

            $moLock        = $this->db->fetchOne("SELECT produced_qty FROM manufacturing_orders WHERE id = ? FOR UPDATE", [$moId]);
            $producedSoFar = (float) ($moLock->produced_qty ?? 0);
            $remaining     = $plannedQty - $producedSoFar;
            if ($outputQty > $remaining + 0.0001) {
                throw new Service_Exception("Output quantity exceeds remaining planned quantity (" . number_format($remaining, 4) . ")", 422);
            }
            $newProducedQty = $producedSoFar + $outputQty;
            $isCompleted    = $newProducedQty >= $plannedQty - 0.0001;
            $newStatus      = $isCompleted ? 'completed' : $mo->status;

            // -- Step 1: Create output record ----------------------------------
            $output = new Models_ManufacturingOrderOutput();
            $output->company_id              = $companyId;
            $output->manufacturing_order_id  = $moId;
            $output->output_qty              = $outputQty;
            $output->destination_location_id = $destinationLocId;
            $output->notes                   = $notes;
            $output->created_by              = $this->context->userId;
            if (!$output->create()) {
                throw new Service_Exception("Failed to save output record");
            }

            // -- Step 2: Save consumption records; flip picked -> consumed for serials --
            foreach ($materialItems as $mi) {
                $miId      = (int) $mi->id;
                $productId = (int) $mi->product_id;

                if ($mi->stock_tracking_method === 'serial') {
                    $specifiedIds = $consumptionMap[$miId]['serial_ids'] ?? [];
                    if (empty($specifiedIds)) continue;

                    foreach ($specifiedIds as $serialId) {
                        $consumption = new Models_ManufacturingOrderOutputConsumption();
                        $consumption->company_id              = $companyId;
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

                    $ph = implode(',', array_fill(0, count($specifiedIds), '?'));
                    $this->db->query(
                        "UPDATE inv_serials SET status = 'consumed' WHERE company_id = ? AND id IN ($ph)",
                        array_merge([$companyId], $specifiedIds)
                    );

                } else {
                    $actualQty = $consumptionMap[$miId]['actual_qty'] ?? 0.0;
                    if ($actualQty <= 0) continue;

                    $consumption = new Models_ManufacturingOrderOutputConsumption();
                    $consumption->company_id              = $companyId;
                    $consumption->output_id              = $output->id;
                    $consumption->manufacturing_order_id = $moId;
                    $consumption->material_item_id       = $miId;
                    $consumption->product_id             = $productId;
                    $consumption->consumed_qty           = $actualQty;
                    if (!$consumption->create()) {
                        throw new Service_Exception("Failed to save consumption record");
                    }
                }
            }

            // -- Step 3: Advance produced_qty and status -----------------------
            $this->db->query(
                "UPDATE manufacturing_orders SET produced_qty = ?, status = ? WHERE id = ?",
                [$newProducedQty, $newStatus, $moId]
            );

            // -- Step 4: Release remaining soft reservations on completion -----
            if ($isCompleted) {
                (new Service_Inv_Stock($this->context))->releaseForDocument('manufacturing_order', $moId);
            }

            // -- Step 5: Record mo_produce movement for finished goods ---------
            $movement      = new Service_Inv_Movement($this->context);
            $produceResult = $movement->record([
                'movement_type'         => 'mo_produce',
                'location_id'           => $destinationLocId,
                'product_id'            => (int) $mo->product_id,
                'quantity'              => $outputQty,
                'serial_or_lot_numbers' => $outputSerials,
                'reference_type'        => 'mo_output',
                'reference_id'          => $output->id,
            ]);
            if (($produceResult['success'] ?? false) === false) {
                throw new Service_Exception("Failed to record production movement for finished goods");
            }

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

            // -- Step 6: History -----------------------------------------------
            $this->addHistory($moId, 'output_recorded', 'Output recorded - ' . number_format($outputQty, 2) . ' units', 'output', $output->id);
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
            throw new Service_Exception("You do not have permission to mark manufacturing orders as complete", 403);
        }

        $mo = $this->getOrFail($id);

        if ($mo->status !== 'in_production') {
            throw new Service_Exception("Only in-production orders can be force completed", 422);
        }

        try {
            $this->db->startTransaction();

            // Release all remaining reservations (qty + serials)
            (new Service_Inv_Stock($this->context))->releaseForDocument('manufacturing_order', $id);

            $this->db->query(
                "UPDATE manufacturing_orders SET status = 'completed' WHERE id = ?",
                [$id]
            );

            $producedQty = (float) $mo->produced_qty;
            $plannedQty  = (float) $mo->planned_qty;
            $this->addHistory(
                $id,
                'force_completed',
                'Order force completed - ' . number_format($producedQty, 2) . ' of ' . number_format($plannedQty, 2) . ' units produced'
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
        if (in_array($mo->status, ['draft', 'cancelled'])) {
            throw new Service_Exception("Material returns cannot be recorded for draft or cancelled orders", 422);
        }

        $companyId   = $this->context->companyId;
        $sourceLocId = (int) $mo->source_location_id;
        $items       = $payload['items'] ?? [];
        $notes       = trim($payload['notes'] ?? '') ?: null;

        if (empty($items)) {
            $this->addError("No items provided for return", "items");
            return ['success' => false, 'errors' => $this->getErrors()];
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

        // Total active allocated qty per material item
        $totalAllocated = [];
        foreach ($this->db->fetchAll(
            "SELECT ami.material_item_id, SUM(ami.allocated_qty) AS total
             FROM manufacturing_order_material_allocation_items AS ami
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             WHERE ami.manufacturing_order_id = ?
             GROUP BY ami.material_item_id",
            [$moId]
        ) as $r) {
            $totalAllocated[(int) $r->material_item_id] = (float) $r->total;
        }

        if (empty($totalAllocated)) {
            throw new Service_Exception("No active allocations found - there is nothing to return", 422);
        }

        // Total already returned qty per material item
        $alreadyReturned = [];
        foreach ($this->db->fetchAll(
            "SELECT material_item_id, SUM(returned_qty) AS total
             FROM manufacturing_order_material_return_items
             WHERE manufacturing_order_id = ?
             GROUP BY material_item_id",
            [$moId]
        ) as $r) {
            $alreadyReturned[(int) $r->material_item_id] = (float) $r->total;
        }

        // Already consumed qty per non-serial material item - cannot be returned
        $alreadyConsumed = [];
        foreach ($this->db->fetchAll(
            "SELECT material_item_id, SUM(consumed_qty) AS total
             FROM manufacturing_order_output_consumptions
             WHERE manufacturing_order_id = ? AND serial_id IS NULL
             GROUP BY material_item_id",
            [$moId]
        ) as $r) {
            $alreadyConsumed[(int) $r->material_item_id] = (float) $r->total;
        }

        // Picked serials per material item (validate return candidates)
        $pickedSerialRows = $this->db->fetchAll(
            "SELECT ami.material_item_id, ams.serial_id
             FROM manufacturing_order_material_allocation_serials AS ams
             INNER JOIN manufacturing_order_material_allocation_items AS ami ON ami.id = ams.allocation_item_id
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             INNER JOIN inv_serials AS s ON s.id = ams.serial_id AND s.status = 'picked'
             WHERE ami.manufacturing_order_id = ?",
            [$moId]
        );
        $pickedPoolByItem = [];
        foreach ($pickedSerialRows as $r) {
            $pickedPoolByItem[(int) $r->material_item_id][(int) $r->serial_id] = true;
        }

        // Already-returned serial IDs (prevent double-return)
        $returnedSerialIds = array_flip(array_map(
            fn($r) => (int) $r->serial_id,
            $this->db->fetchAll(
                "SELECT rs.serial_id FROM manufacturing_order_material_return_serials AS rs WHERE rs.manufacturing_order_id = ?",
                [$moId]
            )
        ));

        // -- Validation --------------------------------------------------------
        $serialItemsToProcess    = [];
        $nonSerialItemsToProcess = [];

        foreach ($items as $item) {
            $miId = (int) ($item['material_item_id'] ?? 0);
            if (!$miId || !isset($moItemMap[$miId])) continue;

            $mi   = $moItemMap[$miId];
            $type = $item['type'] ?? 'regular';
            if (!in_array($type, ['regular', 'scrap'], true)) {
                $this->addError("Invalid return type '{$type}' for item: {$mi->product_name}", "items");
                continue;
            }

            if ($mi->stock_tracking_method === 'serial') {
                $serialIds = array_map('intval', (array) ($item['serial_ids'] ?? []));
                if (empty($serialIds)) continue;

                $pickedPool = $pickedPoolByItem[$miId] ?? [];
                foreach ($serialIds as $sid) {
                    if (!isset($pickedPool[$sid])) {
                        $this->addError("Serial ID {$sid} is not in picked status for component: {$mi->product_name}", "items");
                        return ['success' => false, 'errors' => $this->getErrors()];
                    }
                    if (isset($returnedSerialIds[$sid])) {
                        $this->addError("Serial ID {$sid} has already been returned for component: {$mi->product_name}", "items");
                        return ['success' => false, 'errors' => $this->getErrors()];
                    }
                }
                $serialItemsToProcess[$miId] = ['mi' => $mi, 'serial_ids' => $serialIds, 'type' => $type];

            } else {
                $returnQty   = (float) ($item['qty'] ?? 0);
                if ($returnQty <= 0) continue;

                $maxReturn = max(0.0, ($totalAllocated[$miId] ?? 0.0) - ($alreadyReturned[$miId] ?? 0.0) - ($alreadyConsumed[$miId] ?? 0.0));
                if ($returnQty > $maxReturn + 0.0001) {
                    $this->addError("Return qty for '{$mi->product_name}' exceeds returnable qty (" . number_format($maxReturn, 4) . ")", "items");
                    continue;
                }
                $nonSerialItemsToProcess[$miId] = ['mi' => $mi, 'qty' => $returnQty, 'type' => $type];
            }
        }

        if ($this->hasErrors()) {
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        if (empty($serialItemsToProcess) && empty($nonSerialItemsToProcess)) {
            $this->addError("No valid items provided for return", "items");
            return ['success' => false, 'errors' => $this->getErrors()];
        }

        // -- Transaction -------------------------------------------------------
        try {
            $this->db->startTransaction();

            $ret = new Models_ManufacturingOrderMaterialReturn();
            $ret->company_id              = $companyId;
            $ret->manufacturing_order_id  = $moId;
            $ret->notes                   = $notes;
            $ret->created_by              = $this->context->userId;
            if (!$ret->create()) {
                throw new Service_Exception("Failed to save material return");
            }

            $movement = new Service_Inv_Movement($this->context);

            foreach ($serialItemsToProcess as $miId => $entry) {
                $mi        = $entry['mi'];
                $serialIds = $entry['serial_ids'];
                $type      = $entry['type'];
                $count     = count($serialIds);
                $productId = (int) $mi->product_id;

                $retItem = new Models_ManufacturingOrderMaterialReturnItem();
                $retItem->company_id              = $companyId;
                $retItem->return_id               = $ret->id;
                $retItem->manufacturing_order_id  = $moId;
                $retItem->material_item_id        = $miId;
                $retItem->product_id              = $productId;
                $retItem->returned_qty            = $count;
                $retItem->type                    = $type;
                if (!$retItem->create()) {
                    throw new Service_Exception("Failed to save return item");
                }

                foreach ($serialIds as $serialId) {
                    $retSerial = new Models_ManufacturingOrderMaterialReturnSerial();
                    $retSerial->company_id              = $companyId;
                    $retSerial->return_item_id          = $retItem->id;
                    $retSerial->manufacturing_order_id  = $moId;
                    $retSerial->material_item_id        = $miId;
                    $retSerial->product_id              = $productId;
                    $retSerial->serial_id               = $serialId;
                    if (!$retSerial->create()) {
                        throw new Service_Exception("Failed to save return serial");
                    }
                }

                if ($type === 'regular') {
                    $ph = implode(',', array_fill(0, $count, '?'));
                    $this->db->query(
                        "UPDATE inv_serials SET status = 'in_stock' WHERE company_id = ? AND id IN ($ph)",
                        array_merge([$companyId], $serialIds)
                    );
                    $retResult = $movement->record([
                        'movement_type'  => 'mo_return',
                        'location_id'    => $sourceLocId,
                        'product_id'     => $productId,
                        'quantity'       => $count,
                        'reference_type' => 'mo_return',
                        'reference_id'   => $ret->id,
                    ]);
                    if (($retResult['success'] ?? false) === false) {
                        throw new Service_Exception("Failed to record return movement for {$mi->product_name}");
                    }
                    if ($mo->status !== 'completed') {
                        $this->incrementReturnReservation($productId, $sourceLocId, $moId, $miId, (float) $count);
                    }
                } else {
                    $ph = implode(',', array_fill(0, $count, '?'));
                    $this->db->query(
                        "UPDATE inv_serials SET status = 'scrapped' WHERE company_id = ? AND id IN ($ph)",
                        array_merge([$companyId], $serialIds)
                    );
                    $this->db->query(
                        "DELETE FROM inv_serial_stock WHERE company_id = ? AND serial_id IN ($ph)",
                        array_merge([$companyId], $serialIds)
                    );
                }
            }

            foreach ($nonSerialItemsToProcess as $miId => $entry) {
                $mi        = $entry['mi'];
                $qty       = $entry['qty'];
                $type      = $entry['type'];
                $productId = (int) $mi->product_id;

                $retItem = new Models_ManufacturingOrderMaterialReturnItem();
                $retItem->company_id              = $companyId;
                $retItem->return_id               = $ret->id;
                $retItem->manufacturing_order_id  = $moId;
                $retItem->material_item_id        = $miId;
                $retItem->product_id              = $productId;
                $retItem->returned_qty            = $qty;
                $retItem->type                    = $type;
                if (!$retItem->create()) {
                    throw new Service_Exception("Failed to save return item");
                }

                if ($type === 'regular') {
                    $retResult = $movement->record([
                        'movement_type'  => 'mo_return',
                        'location_id'    => $sourceLocId,
                        'product_id'     => $productId,
                        'quantity'       => $qty,
                        'reference_type' => 'mo_return',
                        'reference_id'   => $ret->id,
                    ]);
                    if (($retResult['success'] ?? false) === false) {
                        throw new Service_Exception("Failed to record return movement for {$mi->product_name}");
                    }
                    if ($mo->status !== 'completed') {
                        $this->incrementReturnReservation($productId, $sourceLocId, $moId, $miId, $qty);
                    }
                }
            }

            $this->recalcAllocationStatus($moId);

            $this->addHistory($moId, 'material_returned', 'Materials returned', 'mo_return', $ret->id);

            $this->db->commit();

            return ['success' => true, 'data' => ['return_id' => $ret->id]];

        } catch (Service_Exception $e) {
            $this->db->rollback();
            throw $e;
        } catch (Exception $e) {
            $this->db->rollback();
            throw new Service_Exception("Failed to record material return".$e->getMessage());
        }
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

        // Block cancel if any materials have been issued but not fully returned
        $outstandingRows = $this->db->fetchAll(
            "SELECT ami.material_item_id,
                    SUM(ami.allocated_qty) AS total_allocated,
                    COALESCE((
                        SELECT SUM(ri.returned_qty)
                        FROM manufacturing_order_material_return_items AS ri
                        WHERE ri.manufacturing_order_id = ami.manufacturing_order_id
                          AND ri.material_item_id = ami.material_item_id
                    ), 0) AS total_returned
             FROM manufacturing_order_material_allocation_items AS ami
             INNER JOIN manufacturing_order_material_allocations AS a ON a.id = ami.allocation_id AND a.status = 'active'
             WHERE ami.manufacturing_order_id = ?
             GROUP BY ami.material_item_id
             HAVING total_allocated > total_returned",
            [$id]
        );
        if (!empty($outstandingRows)) {
            throw new Service_Exception(
                "Materials have been issued to this order. Please record a Return Material (regular or scrap) for all issued items before cancelling.",
                422
            );
        }

        try {

            $this->db->startTransaction();

            $mo->status = 'cancelled';

            if (!$mo->update()) {
                throw new Service_Exception("Failed to cancel manufacturing order");
            }

            // Release all remaining reservations (qty + serials)
            (new Service_Inv_Stock($this->context))->releaseForDocument('manufacturing_order', $id);

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