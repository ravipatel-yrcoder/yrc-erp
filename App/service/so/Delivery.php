<?php
class Service_So_Delivery extends Service_Base {


    private function getDeliveryOrFail(int $dnId): Models_SalesDelivery {

        $dn = new Models_SalesDelivery($dnId);
        if ($dn->isEmpty) {
            throw new Service_Exception("The requested delivery note was not found", 404);
        }
        if ($dn->company_id != $this->context->companyId) {
            throw new Service_Exception("You do not have permission to access this delivery note", 403);
        }
        return $dn;
    }


    private function normalizeLineItems(array $lineItems, string $source, string $context, array $extra): array {

        if( empty($lineItems) ) return [];

        $normalizedLineItems = [];
        if( $source === "form_request" && $context === "reduce_stock" ) {

            $warehouseId = $extra["warehouse_id"] ?? 0;
            foreach($lineItems as $item) {
                $normalizedLineItems[] = [
                    'prod_id'     => $item["product_id"],
                    'warehouse_id' => $warehouseId,
                    'qty'         => $item["dispatched_qty"],
                    'so_item_id'  => (int) ($item["sales_order_item_id"] ?? 0),
                ];
            }

            return $normalizedLineItems;
        }
        else if( $source === "delivery" && (in_array($context, ["reduce_stock", "reverse_stock"]) ) ) {

            $warehouseId = $extra["warehouse_id"] ?? 0;
            foreach($lineItems as $item) {
                $normalizedLineItems[] = [
                    'prod_id'     => $item->product_id,
                    'warehouse_id' => $warehouseId,
                    'qty'         => $item->dispatched_qty,
                    'so_item_id'  => (int) ($item->sales_order_item_id ?? 0),
                ];
            }

            return $normalizedLineItems;
        }


        return [];
    }    


    private function validatePayload(array $payload, int $dnId = 0): void {

        $soId = (int) ($payload['sales_order_id'] ?? 0);
        $multiWarehouse = Service_CompanySettings::isMultiWarehouseEnabled($this->context->companyId);
        $warehouseId = $multiWarehouse
            ? (int) ($payload['warehouse_id'] ?? 0)
            : (Service_Company::getDefaultWarehouseId($this->context->companyId) ?? 0);
        $customerId = (int) ($payload['customer_id'] ?? 0);
        $status = $payload['status'] ?? "";
        $dispatchDate = trim($payload['dispatch_date'] ?? '');
        $dnNumber = trim($payload['dn_number'] ?? '');
        $dnNumberSuggested = trim($payload['dn_number_suggested'] ?? '');
        $items = (array) ($payload['items'] ?? []);
        $fulfilmentType = $payload['fulfilment_type'] ?? "";
        $instantDelivery = $payload['instant_delivery'] ?? 0;


        // DN number uniqueness if user customised it
        if (!empty($dnNumber) && $dnNumber !== $dnNumberSuggested) {
            if (!$this->isUniqueDnNumber($dnNumber, $dnId)) {
                $this->addError(validationErrMsg("duplicate", "DN number"), "dn_number");
            }
        }


        // Sales order must exist, belong to company, and be in a dispatchable status
        if( $instantDelivery == 0 && $soId ) {

            $so = new Models_SalesOrder($soId);
            if ($so->isEmpty || $so->company_id != $this->context->companyId) {
                $this->addError(validationErrMsg("missing_or_invalid", "Sales order"), "sales_order_id");
            } elseif (!in_array($so->status, ['confirmed', 'partially_dispatched', 'partially_delivered'])) {
                $this->addError("Only confirmed or partially dispatched/delivered sales orders can have delivery notes created", "sales_order_id");
            }
        }


        $customer = new Models_Customer($customerId);
        if ($customer->isEmpty || $customer->company_id != $this->context->companyId) {
            $this->addError(validationErrMsg("missing_or_invalid", "Customer"), "customer_id");
        }


        // Location
        $location = new Models_InvWarehouse($warehouseId);
        if ($location->isEmpty || $location->company_id != $this->context->companyId || $location->status !== 'active') {
            $this->addError(validationErrMsg("missing_or_invalid", "Location"), "warehouse_id");
        }

        // Status
        if (empty($status)) {
            $this->addError(validationErrMsg("required", "Status"), "status");
        } else if (!in_array($status, ["draft", "dispatched", "delivered"])) {
            $this->addError(validationErrMsg("invalid", "Status"), "status");
        }

        // Fulfilment Type(Delivery method: pickup, ship, drop_ship, etc)
        if( empty($fulfilmentType) ) {
            $this->addError(validationErrMsg("required", "Fulfilment Type"), "fulfilment_type");
        }

        
        // Dispatch date
        if ( !empty($dispatchDate) && !strtotime($dispatchDate) ) {
            $this->addError(validationErrMsg("invalid", "Dispatch date"), "dispatch_date");
        }

        /*
        if (empty($dispatchDate)) {
            $this->addError(validationErrMsg("required", "Dispatch date"), "dispatch_date");
        } elseif (!strtotime($dispatchDate)) {
            $this->addError(validationErrMsg("invalid", "Dispatch date"), "dispatch_date");
        }
        */

        // Items
        $remainingQty = 0;
        if( $soId ) {
            $remainingQty = $this->getRemainingQtyBySoItem($soId, $dnId);
        }
        $this->validateItems($items, $remainingQty);

        // When dispatching/delivering, check physical stock and serial completeness
        if (in_array($status, ['dispatched', 'delivered']) && $warehouseId > 0 && !empty($items)) {
            $this->validateStockForDispatch($warehouseId, $items, false, $dnId);
        }
    }


    private function validateItems(array $items, array $remainingQty): void {

        if (empty($items)) {
            $this->addError(validationErrMsg("one_item_required", "line item"), "items");
            return;
        }

        //$companyId = $this->context->companyId;
        $index = 0;

        $soItemIds = array_values(array_filter(array_map(fn($i) => (int)($i['sales_order_item_id'] ?? 0), $items)));
        $soItemUomMap = [];
        if ($soItemIds) {
            $ph = implode(',', array_fill(0, count($soItemIds), '?'));
            $rows = $this->db->fetchAll(
                "SELECT soi.id, u.allow_decimal, u.name AS uom_name FROM sales_order_items soi JOIN uoms u ON u.code = soi.uom_code WHERE soi.id IN ($ph)",
                $soItemIds
            );
            foreach ($rows as $r) {
                $soItemUomMap[(int)$r->id] = ['allow_decimal' => (bool)(int)$r->allow_decimal, 'name' => $r->uom_name];
            }
        }

        foreach ($items as $item) {

            $row = $index + 1;
            $soItemId = (int) ($item['sales_order_item_id'] ?? 0);
            $productId = (int) ($item['product_id'] ?? 0);
            $dispatchedQty = (float) ($item['dispatched_qty'] ?? 0);

            if (!$soItemId || !isset($remainingQty[$soItemId])) {
                $this->addError("Invalid sales order item at row {$row}", "items.{$index}.sales_order_item_id");
            }

            if (!$productId) {
                $this->addError(validationErrMsg("required", "Product at row {$row}"), "items.{$index}.product_id");
            }

            if ($dispatchedQty <= 0) {
                $this->addError("Dispatched quantity must be greater than zero at row {$row}", "items.{$index}.dispatched_qty");
            } elseif (isset($remainingQty[$soItemId]) && $dispatchedQty > $remainingQty[$soItemId]) {
                $remaining = formatQty($remainingQty[$soItemId]);
                $this->addError("Dispatched quantity exceeds remaining quantity ({$remaining}) at row {$row}", "items.{$index}.dispatched_qty");
            } elseif ($soItemId && isset($soItemUomMap[$soItemId]) && !$soItemUomMap[$soItemId]['allow_decimal'] && !isWholeNumber($dispatchedQty)) {
                $this->addError("Quantity must be a whole number for {$soItemUomMap[$soItemId]['name']} at row {$row}", "items.{$index}.dispatched_qty");
            }

            $index++;
        }
    }


    private function validateStockForDispatch(int $warehouseId, array $items, bool $throwOnError = false, int $dnId = 0): void {

        $companyId = $this->context->companyId;
        $index = 0;
        $stockErrors = [];

        foreach ($items as $item) {

            $row = $index + 1;
            $productId = (int) ($item['product_id'] ?? 0);
            $dispatchedQty = (float) ($item['dispatched_qty'] ?? 0);

            $product = new Models_Product($productId);
            $trackingMethod = strtolower($product->stock_tracking_method ?? '');

            if (empty($trackingMethod) || $trackingMethod === 'none') {
                $index++;
                continue;
            }

            $stock = $this->db->fetchOne(
                "SELECT unrestricted_qty FROM inv_product_stock WHERE company_id = ? AND warehouse_id = ? AND product_id = ? LIMIT 1",
                [$companyId, $warehouseId, $productId]
            );
            $onHand = $stock ? (float) $stock->unrestricted_qty : 0;

            if ($onHand < $dispatchedQty) {
                $neededFormatted    = formatQty($dispatchedQty);
                $availableFormatted = formatQty(max(0, $onHand));
                $msg = "Insufficient stock at row {$row}: required {$neededFormatted}, available {$availableFormatted}";
                $this->addError($msg, "items.{$index}.dispatched_qty");
                $stockErrors[] = $product->name . ": required {$neededFormatted}, available {$availableFormatted}";
            }

            // Serial number validation for dispatch
            if ($trackingMethod === 'serial') {
                $dnItemId      = (int) ($item['dn_item_id'] ?? 0);
                $requiredCount = (int) round($dispatchedQty);

                if ($dnId > 0 && $dnItemId > 0) {
                    // Completeness: count assigned serials against dispatched qty
                    $assignedCount = (int) $this->db->fetchVar(
                        "SELECT COUNT(*) FROM sales_delivery_item_serials WHERE company_id = ? AND sales_delivery_item_id = ?",
                        [$companyId, $dnItemId]
                    );
                    if ($assignedCount !== $requiredCount) {
                        $msg = "{$product->name}: {$requiredCount} serial number(s) required but {$assignedCount} assigned. Edit the delivery and assign all serials before dispatching.";
                        if ($throwOnError) throw new Service_Exception($msg, 422);
                        $this->addError($msg, "items.{$index}.serial_numbers");
                        $stockErrors[] = $msg;
                    } else {
                        // Reservation status: verify all assigned serials are still reserved for this DN
                        $notReserved = $this->db->fetchAll(
                            "SELECT sdis.serial_number
                             FROM sales_delivery_item_serials AS sdis
                             INNER JOIN inv_serials AS ins ON ins.id = sdis.serial_id
                             WHERE sdis.company_id = ? AND sdis.sales_delivery_item_id = ? AND ins.status != 'reserved'",
                            [$companyId, $dnItemId]
                        );
                        if (!empty($notReserved)) {
                            $sns = implode(', ', array_column((array) $notReserved, 'serial_number'));
                            $msg = "Serial number(s) [{$sns}] are no longer reserved for {$product->name}. Please edit the delivery and re-assign.";
                            if ($throwOnError) throw new Service_Exception($msg, 422);
                            $this->addError($msg, "items.{$index}.serial_numbers");
                            $stockErrors[] = $msg;
                        }
                    }
                } elseif ($dnId === 0) {
                    // New DN saved directly as dispatched/delivered: validate serial count from payload
                    $payloadSerials = array_values(array_filter(array_map('trim', (array) ($item['serial_numbers'] ?? []))));
                    $providedCount  = count($payloadSerials);
                    if ($providedCount !== $requiredCount) {
                        $msg = "{$product->name}: {$requiredCount} serial number(s) required but {$providedCount} provided.";
                        if ($throwOnError) throw new Service_Exception($msg, 422);
                        $this->addError($msg, "items.{$index}.serial_numbers");
                        $stockErrors[] = $msg;
                    }
                }
            }

            $index++;
        }

        if ($throwOnError && !empty($stockErrors)) {
            throw new Service_Exception("Cannot dispatch: " . implode("; ", $stockErrors), 422);
        }
    }


    private function isUniqueDnNumber(string $dnNumber, int $dnId = 0): bool {

        $companyId = $this->context->companyId;
        $sql = "SELECT COUNT(id) FROM sales_deliveries WHERE company_id = ? AND dn_number = ?";
        $bindings = [$companyId, trim($dnNumber)];
        
        if ($dnId > 0) {
            $sql .= " AND id != ?";
            $bindings[] = $dnId;
        }

        return (int) $this->db->fetchVar($sql, $bindings) === 0;
    }


    /**
     * Returns [so_item_id => remaining_qty] for a given SO.
     * Counts dispatched_qty from all DNs except cancelled/returned status.
     * If $excludeDnId is provided (edit mode), that DN's quantities are excluded from the sum.
     */
    private function getRemainingQtyBySoItem(int $soId, int $excludeDnId = 0): array {

        $companyId = $this->context->companyId;

        $binding = [$companyId];
        $excludeSql = "";
        if( $excludeDnId > 0 ) {
            $excludeSql = "AND sd.id != ?";
            $binding[] = $excludeDnId;
        }
        $binding[] = $soId;

        $sql = "SELECT
                    soi.id AS so_item_id,
                    soi.ordered_qty,
                    COALESCE(SUM(CASE WHEN sd.id IS NOT NULL THEN sdi.dispatched_qty ELSE 0 END), 0) AS dispatched_total
                FROM sales_orders AS so
                INNER JOIN sales_order_items AS soi ON soi.sales_order_id=so.id
                LEFT JOIN sales_delivery_items AS sdi ON sdi.sales_order_item_id = soi.id
                LEFT JOIN sales_deliveries AS sd ON sd.id = sdi.sales_delivery_id AND sd.company_id = ? AND sd.status NOT IN ('cancelled', 'returned', 'lost') {$excludeSql}
                WHERE so.id = ?
                GROUP BY soi.id";
                        
        $rows = $this->db->fetchAll($sql, $binding);

        $result = [];
        foreach ($rows as $row) {
            $result[$row->so_item_id] = max(0.0, round((float) $row->ordered_qty - (float) $row->dispatched_total, 4));
        }

        return $result;
    }


    private function getLineItemsDiff(array $existingItems, array $incomingItems): array {

        $existingBySoItemId = [];
        foreach ($existingItems as $item) {
            $existingBySoItemId[$item->sales_order_item_id] = $item;
        }

        $itemsToCreate = [];
        $itemsToUpdate = [];
        $itemsToDelete = [];
        $usedSoItemIds = [];

        foreach ($incomingItems as $item) {
            $soItemId = (int) ($item['sales_order_item_id'] ?? 0);
            if ($soItemId && isset($existingBySoItemId[$soItemId])) {
                $item['id'] = $existingBySoItemId[$soItemId]->id;
                $itemsToUpdate[] = $item;
                $usedSoItemIds[] = $soItemId;
            } else {
                $itemsToCreate[] = $item;
            }
        }

        foreach ($existingBySoItemId as $soItemId => $existingItem) {
            if (!in_array($soItemId, $usedSoItemIds)) {
                $itemsToDelete[] = $existingItem;
            }
        }

        return [$itemsToCreate, $itemsToUpdate, $itemsToDelete];
    }


    private function saveLineItems(Models_SalesDelivery $delivery, array $items): array {

        if (!$delivery->id) {
            throw new Service_Exception("Failed to save line items");
        }

        $failMsg  = "Unable to save delivery note due to an issue with one or more line items";
        $updateLog = [];

        $existingItems = $delivery->items;
        [$itemsToCreate, $itemsToUpdate, $itemsToDelete] = $this->getLineItemsDiff($existingItems, $items);

        foreach (array_merge($itemsToCreate, $itemsToUpdate) as $item) {

            $itemId = (int) ($item['id'] ?? 0);
            $soItemId = (int) ($item['sales_order_item_id'] ?? 0);
            $productId = (int) ($item['product_id'] ?? 0);
            $dispatchedQty = (float) ($item['dispatched_qty'] ?? 0);
            $uomCode = $item['uom_code'] ?? null;
            $description = isset($item['description']) && $item['description'] !== '' ? $item['description'] : null;
            $product = new Models_Product($productId);

            $dni = new Models_SalesDeliveryItem($itemId);

            $oldDispatchedQty = (float) $dni->dispatched_qty;
            $oldUom = $dni->uom_code;
            $oldDescription = $dni->description;

            $dni->sales_delivery_id = $delivery->id;
            $dni->sales_order_item_id  = $soItemId;
            $dni->product_id = $productId;
            $dni->description = $description;
            $dni->dispatched_qty = $dispatchedQty;
            $dni->uom_code = $uomCode;

            if ($dni->isEmpty) {

                $dni->created_by = $this->context->userId;
                if (!$dni->create()) {
                    throw new Service_Exception($failMsg);
                }

                $updateLog[] = [
                    'event' => 'created',
                    'dn_item_id' => $dni->id,
                    'prod_id' => $productId,
                    'prod_name' => $product->name,
                    'new_qty' => formatQty($dispatchedQty),
                    'new_uom' => $uomCode ?? '',
                ];

            } else {

                $changed = ($oldDispatchedQty !== $dispatchedQty || $oldDescription !== $description || $oldUom !== $uomCode);
                if ($changed) {

                    if (!$dni->update()) {
                        throw new Service_Exception($failMsg);
                    }

                    $updateLog[] = [
                        'event' => 'updated',
                        'dn_item_id' => $dni->id,
                        'prod_id' => $productId,
                        'prod_name' => $product->name,
                        'old_qty' => formatQty($oldDispatchedQty),
                        'new_qty' => formatQty($dispatchedQty),
                        'old_uom' => $oldUom,
                        'new_uom' => $uomCode ?? '',
                    ];
                }
            }

            // Save serial assignments when the payload contains serial_numbers
            if (array_key_exists('serial_numbers', $item) && $product->stock_tracking_method === 'serial') {
                $err = $this->saveSerialAssignments(
                    $this->context->companyId,
                    $delivery->id,
                    $dni->id,
                    $productId,
                    (array) ($item['serial_numbers'] ?? [])
                );
                if ($err !== null) {
                    throw new Service_Exception($err, 422);
                }
            }
        }

        foreach ($itemsToDelete as $del) {

            // Remove serial assignments before deleting the item
            $this->db->query(
                "DELETE FROM sales_delivery_item_serials WHERE company_id = ? AND sales_delivery_item_id = ?",
                [$this->context->companyId, $del->id]
            );

            $dni = new Models_SalesDeliveryItem($del->id);
            $dni->delete();
            if ($dni->getDeletedRows() <= 0) {
                throw new Service_Exception($failMsg);
            }

            $updateLog[] = [
                'event' => 'deleted',
                'dn_item_id' => $del->id,
                'prod_id' => $del->product_id,
                'prod_name' => $del->product_name,
                'old_qty' => formatQty($del->dispatched_qty),
                'old_uom' => $del->uom_code ?? '',
            ];
        }

        return $updateLog;
    }



    /**
     * Reduce stock on dispatch.
     *
     * @param Models_SalesDelivery $delivery The DN being dispatched.
     * @param bool $releaseReservedQty  True when the SO was confirmed (stock was reserved at SO location).
     * @param int $soSourceWarehouseId  The SO source warehouse where reservation was originally created.
     * Required when $releaseReservedQty is true.
     *
     * Two separate operations per item:
     *   1. Deduct unrestricted_qty from the delivery location  (via Service_Inv_Movement::saleOut)
     *   2. Release reserved_qty from the SO location       (via Service_Inv_Movement::releaseReservation)
     * These are independent because in multi-location scenarios the two locations may differ.
     */
    private function reduceStock(Models_SalesDelivery $delivery, array $items, bool $releaseReservedQty = false, int $soSourceWarehouseId = 0): void {

        $companyId  = $this->context->companyId;
        $soId       = (int) ($delivery->sales_order_id ?? 0);
        $invService = new Service_Inv_Movement($this->context);

        foreach ($items as $item) {

            $prodId    = $item['prod_id'];
            $qty       = $item['qty'];
            $warehouseId = $item['warehouse_id'];
            $soItemId  = (int) ($item['so_item_id'] ?? 0);

            $product = new Models_Product($prodId);
            $trackingMethod = strtolower($product->stock_tracking_method ?? '');

            if (empty($trackingMethod) || $trackingMethod === 'none') {
                continue;
            }

            // 1. Deduct unrestricted_qty from the DELIVERY location
            $result = $invService->record([
                'movement_type' => 'sale',
                'warehouse_id' => $warehouseId,
                'product_id' => $prodId,
                'quantity' => $qty,
                'reference_type' => 'sales_delivery',
                'reference_id' => $delivery->id,
                'notes' => 'Dispatched via ' . $delivery->dn_number,
            ]);

            if (!$result['success']) {
                throw new Service_Exception("Failed to record stock movement for product: " . $product->name);
            }

            // 2. Release reserved_qty from the SO source warehouse (may differ from delivery location)
            if ($releaseReservedQty && $soSourceWarehouseId > 0) {

                $invService->releaseReservation([
                    'warehouse_id' => $soSourceWarehouseId,
                    'product_id' => $prodId,
                    'product_name' => $product->name,
                    'quantity' => $qty,
                ]);

                // 3. Reduce inv_stock_allocations for this SO line by the dispatched qty
                if ($soId && $soItemId) {
                    $this->db->query(
                        "UPDATE inv_stock_allocations
                         SET quantity = GREATEST(0, quantity - ?)
                         WHERE company_id = ? AND document_type = 'sales_order'
                           AND document_id = ? AND document_line_id = ?
                           AND product_id = ? AND warehouse_id = ?
                           AND allocation_type = 'reservation'",
                        [$qty, $companyId, $soId, $soItemId, $prodId, $soSourceWarehouseId]
                    );
                    $this->db->query(
                        "DELETE FROM inv_stock_allocations
                         WHERE company_id = ? AND document_type = 'sales_order'
                           AND document_id = ? AND document_line_id = ?
                           AND quantity <= 0 AND allocation_type = 'reservation'",
                        [$companyId, $soId, $soItemId]
                    );
                }
            }
        }

        // Update inv_serials status → sold and remove from inv_serial_stock for all assigned serials
        $this->dispatchSerials($delivery);
    }


    /**
     * Restore stock on return/cancel/revert.
     * Always restores unrestricted_qty at the delivery location.
     * Optionally restores reserved_qty at the SO location (revert-to-draft flow only).
     *
     * Two separate operations per item:
     *   1. Restore unrestricted_qty at the delivery location  (via Service_Inv_Movement::record)
     *   2. Restore reserved_qty at the SO location       (via Service_Inv_Movement::restoreReservation)
     * These are independent because in multi-location scenarios the two locations may differ.
     */
    private function restoreStock(Models_SalesDelivery $delivery, bool $restoreReservedQty = false, int $soSourceWarehouseId = 0, bool $keepSerialAssignments = false, bool $isReopen = false, string $notes = ''): void {

        $companyId  = $this->context->companyId;
        $warehouseId = $delivery->warehouse_id;
        $invService = new Service_Inv_Movement($this->context);
        $soId       = (int) ($delivery->sales_order_id ?? 0);

        $soNumber = '';
        if ($soId && $restoreReservedQty) {
            $soRow    = $this->db->fetchOne("SELECT so_number FROM sales_orders WHERE id = ? AND company_id = ? LIMIT 1", [$soId, $companyId]);
            $soNumber = $soRow ? $soRow->so_number : '';
        }

        foreach ($delivery->items as $item) {

            $productId       = $item->product_id;
            $deliveryItemQty = $item->dispatched_qty;
            $soItemId        = (int) ($item->sales_order_item_id ?? 0);

            $product = new Models_Product($productId);
            $trackingMethod = strtolower($product->stock_tracking_method ?? '');

            if (empty($trackingMethod) || $trackingMethod === 'none') {
                continue;
            }

            // 1. Restore unrestricted_qty at the delivery location
            $defaultNote = $isReopen ? 'DN cancelled, stock restored via ' . $delivery->dn_number : 'Delivery returned via ' . $delivery->dn_number;
            $result = $invService->record([
                'movement_type' => $isReopen ? 'dn_cancelled' : 'dn_returned',
                'warehouse_id'   => $warehouseId,
                'product_id'    => $productId,
                'quantity'      => $deliveryItemQty,
                'reference_type'=> 'sales_delivery',
                'reference_id'  => $delivery->id,
                'notes'         => $notes !== '' ? $notes : $defaultNote,
            ]);

            if (!$result['success']) {
                throw new Service_Exception("Failed to record stock movement for product: " . $product->name);
            }

            // 2. Restore reserved_qty at the SO source warehouse (revert-to-draft flow only)
            if ($restoreReservedQty && $soSourceWarehouseId > 0) {
                $invService->restoreReservation([
                    'warehouse_id'  => $soSourceWarehouseId,
                    'product_id'   => $productId,
                    'product_name' => $product->name,
                    'quantity'     => $deliveryItemQty,
                ]);

                // Keep inv_stock_allocations in sync with inv_product_stock.reserved_qty
                if ($soId && $soItemId && $soNumber) {
                    $this->db->query(
                        "INSERT INTO inv_stock_allocations
                             (company_id, product_id, warehouse_id, document_type, document_id, document_number, document_line_id, allocation_type, quantity, created_at, updated_at)
                         VALUES (?, ?, ?, 'sales_order', ?, ?, ?, 'reservation', ?, NOW(), NOW())
                         ON DUPLICATE KEY UPDATE quantity = quantity + ?, updated_at = NOW()",
                        [$companyId, $productId, $soSourceWarehouseId, $soId, $soNumber, $soItemId, $deliveryItemQty, $deliveryItemQty]
                    );
                }
            }
        }

        // Restore inv_serials — behaviour differs for reopen vs returned
        $this->restoreSerials($delivery, $keepSerialAssignments, $isReopen);
    }


    /**
     * Save serial assignments for a single delivery item with reservation.
     * - Computes added/removed vs current saved set
     * - Validates newly added serials are in_stock (not reserved/sold by another DN)
     * - Reserves newly added serials (in_stock → reserved)
     * - Releases removed serials back to in_stock (reserved → in_stock)
     * Returns error string on conflict, null on success.
     */
    private function saveSerialAssignments(int $companyId, int $dnId, int $dniId, int $productId, array $newSerialNumbers): ?string {

        $now        = date('Y-m-d H:i:s');
        $invService = new Service_Inv_Movement($this->context);

        // Current saved assignments for this item
        $existing = $this->db->fetchAll("SELECT serial_id, serial_number FROM sales_delivery_item_serials WHERE company_id = ? AND sales_delivery_item_id = ?", [$companyId, $dniId]);
        
        $existingMap = [];
        foreach ($existing as $row) {
            $existingMap[$row->serial_number] = $row->serial_id;
        }

        $newSet = [];
        foreach ($newSerialNumbers as $sn) {
            $sn = trim((string) $sn);
            if ($sn !== '') $newSet[] = $sn;
        }

        $toAdd = array_diff($newSet, array_keys($existingMap));
        $toRemove = array_diff(array_keys($existingMap), $newSet);

        // Validate all newly added serials are in_stock (not taken by another DN)
        foreach ($toAdd as $sn) {
            
            $serial = $this->db->fetchOne("SELECT id, status FROM inv_serials WHERE company_id = ? AND product_id = ? AND serial_number = ? LIMIT 1", [$companyId, $productId, $sn]);
            
            if (!$serial) {
                return "Serial number '{$sn}' was not found.";
            }
            if ($serial->status !== 'in_stock') {
                return "Serial number '{$sn}' is no longer available (it has been reserved or dispatched by another delivery).";
            }
        }

        // Release removed serials back to in_stock
        foreach ($toRemove as $sn) {

            $serialId = $existingMap[$sn];

            $this->db->query("UPDATE inv_serials SET status = 'in_stock', updated_at = ? WHERE id = ? AND company_id = ? AND status = 'reserved'", [$now, $serialId, $companyId]);
            $this->db->query("UPDATE inv_serial_stock SET state_doc_type = NULL, state_doc_id = NULL, updated_at = ? WHERE serial_id = ? AND company_id = ?", [$now, $serialId, $companyId]);
            $this->db->query("DELETE FROM sales_delivery_item_serials WHERE company_id = ? AND sales_delivery_item_id = ? AND serial_id = ?", [$companyId, $dniId, $serialId]);
            $invService->logSerialHistory($serialId, $productId, 'reservation_released', 'Removed from DN #' . $dnId, 'sales_delivery', $dnId, ['to_status' => 'in_stock']);
        }

        // Reserve and insert newly added serials
        foreach ($toAdd as $sn) {

            $serial = $this->db->fetchOne("SELECT id FROM inv_serials WHERE company_id = ? AND product_id = ? AND serial_number = ? LIMIT 1", [$companyId, $productId, $sn]);

            if (!$serial) continue;

            $this->db->query("UPDATE inv_serials SET status = 'reserved', updated_at = ? WHERE id = ? AND company_id = ?", [$now, $serial->id, $companyId]);
            $this->db->query("UPDATE inv_serial_stock SET state_doc_type = 'sales_delivery', state_doc_id = ?, updated_at = ? WHERE serial_id = ? AND company_id = ?", [$dnId, $now, $serial->id, $companyId]);
            $this->db->insert("sales_delivery_item_serials", [
                'company_id'             => $companyId,
                'sales_delivery_id'      => $dnId,
                'sales_delivery_item_id' => $dniId,
                'serial_id'              => $serial->id,
                'serial_number'          => $sn,
                'created_at'             => date("Y-m-d H:i:s"),
            ]);
            $invService->logSerialHistory($serial->id, $productId, 'reserved', 'Assigned to DN #' . $dnId, 'sales_delivery', $dnId, ['to_status' => 'reserved']);
        }

        return null;
    }


    /**
     * Mark assigned serials as sold and remove them from location stock.
     * Asserts each serial is still 'reserved' for this DN — throws if any was
     * stolen between reservation and dispatch (should not happen in normal flow).
     */
    private function dispatchSerials(Models_SalesDelivery $delivery): void {

        $companyId  = $this->context->companyId;
        $warehouseId = (int) $delivery->warehouse_id;
        $now        = date('Y-m-d H:i:s');
        $invService = new Service_Inv_Movement($this->context);

        $assignments = $this->db->fetchAll(
            "SELECT sdis.serial_id, sdis.serial_number, sdi.product_id, ins.status AS serial_status
             FROM sales_delivery_item_serials AS sdis
             INNER JOIN sales_delivery_items AS sdi ON sdi.id = sdis.sales_delivery_item_id
             INNER JOIN inv_serials AS ins ON ins.id = sdis.serial_id
             WHERE sdis.company_id = ? AND sdis.sales_delivery_id = ?",
            [$companyId, $delivery->id]
        );

        foreach ($assignments as $a) {

            if ($a->serial_status !== 'reserved') {
                throw new Service_Exception("Serial number '{$a->serial_number}' is no longer reserved for this delivery. It may have been dispatched by another delivery.", 422);
            }

            $this->db->query("UPDATE inv_serials SET status = 'sold', updated_at = ? WHERE id = ? AND company_id = ?", [$now, $a->serial_id, $companyId]);
            $this->db->query("DELETE FROM inv_serial_stock WHERE serial_id = ? AND warehouse_id = ?", [$a->serial_id, $warehouseId]);
            $invService->logSerialHistory($a->serial_id, $a->product_id, 'dispatched', 'Dispatched via DN #' . $delivery->dn_number, 'sales_delivery', (int)$delivery->id, ['to_status' => 'sold']);
        }
    }


    /**
     * Restore assigned serials after a DN is reversed.
     *
     * $isReopen = true  (draft reopen): serials → reserved, state_doc_type/id restored, assignments kept
     * $isReopen = false (returned/cancel): serials → in_stock, state_doc_type/id cleared
     * $keepAssignments controls whether sales_delivery_item_serials rows are preserved
     */
    private function restoreSerials(Models_SalesDelivery $delivery, bool $keepAssignments = false, bool $isReopen = false): void {

        $companyId  = $this->context->companyId;
        $warehouseId = (int) $delivery->warehouse_id;
        $dnId       = (int) $delivery->id;
        $soId       = (int) ($delivery->sales_order_id ?? 0);
        $now        = date('Y-m-d H:i:s');
        $invService = new Service_Inv_Movement($this->context);

        $assignments = $this->db->fetchAll(
            "SELECT sdis.serial_id, sdi.product_id, ins.status AS serial_status
             FROM sales_delivery_item_serials AS sdis
             INNER JOIN sales_delivery_items AS sdi ON sdi.id = sdis.sales_delivery_item_id
             INNER JOIN inv_serials AS ins ON ins.id = sdis.serial_id
             WHERE sdis.company_id = ? AND sdis.sales_delivery_id = ?",
            [$companyId, $dnId]
        );

        foreach ($assignments as $a) {

            if ($isReopen) {
                // Reopen: restore soft reservation pointing back to the SO (not the DN).
                // The DN's claim is already in sales_delivery_item_serials; the stock-level
                // reference must match the SO so that releaseForDocument('sales_order') can
                // find and release serials correctly if the SO is later cancelled.
                $this->db->query(
                    "UPDATE inv_serials SET status = 'reserved', updated_at = ? WHERE id = ? AND company_id = ? AND status IN ('reserved', 'sold')",
                    [$now, $a->serial_id, $companyId]
                );
                $this->db->query(
                    "UPDATE inv_serial_stock SET state_doc_type = 'sales_order', state_doc_id = ?, updated_at = ? WHERE serial_id = ? AND company_id = ?",
                    [$soId, $now, $a->serial_id, $companyId]
                );
                $invService->logSerialHistory($a->serial_id, $a->product_id, 'reserved', 'Re-reserved on DN reopen #' . $dnId, 'sales_delivery', $dnId, ['to_status' => 'reserved']);
            } else {
                // Returned / cancel: restore to in_stock
                $this->db->query(
                    "UPDATE inv_serials SET status = 'in_stock', updated_at = ? WHERE id = ? AND company_id = ? AND status IN ('reserved', 'sold')",
                    [$now, $a->serial_id, $companyId]
                );
                $this->db->query(
                    "UPDATE inv_serial_stock SET state_doc_type = NULL, state_doc_id = NULL, updated_at = ? WHERE serial_id = ? AND company_id = ?",
                    [$now, $a->serial_id, $companyId]
                );
                $invService->logSerialHistory($a->serial_id, $a->product_id, 'returned_to_stock', 'Returned to stock via DN #' . $dnId, 'sales_delivery', $dnId, ['to_status' => 'in_stock']);
            }

            // Only restore inv_serial_stock row for serials that were physically dispatched (sold)
            // Reserved serials were never removed from inv_serial_stock
            if ($a->serial_status === 'sold') {

                $exists = $this->db->fetchOne("SELECT id FROM inv_serial_stock WHERE serial_id = ? AND warehouse_id = ? LIMIT 1", [$a->serial_id, $warehouseId]);

                if (!$exists) {
                    $this->db->insert("inv_serial_stock", [
                        'company_id'            => $companyId,
                        'product_id'            => $a->product_id,
                        'serial_id'             => $a->serial_id,
                        'warehouse_id'           => $warehouseId,
                        'state_doc_type' => $isReopen ? 'sales_order' : null,
                        'state_doc_id'   => $isReopen ? $soId : null,
                        'created_at'            => $now,
                    ]);
                }
            }
        }

        if (!$keepAssignments) {
            $this->db->query("DELETE FROM sales_delivery_item_serials WHERE company_id = ? AND sales_delivery_id = ?", [$companyId, $dnId]);
        }
    }


    /**
     * Recalculate and update SO status based on all DNs.
     */
    private function recalculateSoStatus(int $soId): void {

        $companyId = $this->context->companyId;

        // Get ordered qty per SO item
        $sql = "SELECT soi.id, soi.ordered_qty  FROM sales_orders AS so
                INNER JOIN sales_order_items AS soi ON soi.sales_order_id = so.id
                WHERE
                so.company_id = ? AND so.id = ?";
        $soItems = $this->db->fetchAll($sql, [$companyId, $soId]);
    
        if (empty($soItems)) {
            return;
        }

        // Get dispatched totals per SO item (dispatched + delivered only; lost frees the qty)
        $sql = "SELECT
                    sdi.sales_order_item_id, SUM(sdi.dispatched_qty) AS total
                FROM sales_deliveries sd
                INNER JOIN sales_delivery_items sdi ON sdi.sales_delivery_id = sd.id
                WHERE
                    sd.company_id = ? AND
                    sd.sales_order_id = ? AND
                    sd.status IN ('dispatched', 'delivered')
                GROUP BY sdi.sales_order_item_id";
        $dispatchedRows = $this->db->fetchAll($sql, [$companyId, $soId]);

        $dispatchedMap = [];
        foreach ($dispatchedRows as $row) {
            $dispatchedMap[$row->sales_order_item_id] = (float) $row->total;
        }

        // Get delivered totals per SO item
        $sql = "SELECT 
                    sdi.sales_order_item_id,
                    COALESCE(SUM(sdi.dispatched_qty), 0) AS total
                FROM sales_deliveries AS sd
                INNER JOIN sales_delivery_items AS sdi ON sdi.sales_delivery_id = sd.id
                WHERE 
                    sd.company_id = ? AND 
                    sd.sales_order_id = ? AND 
                    sd.status = ?
                GROUP BY sdi.sales_order_item_id";
        $deliveredRows = $this->db->fetchAll($sql, [$companyId, $soId, "delivered"]);

        $deliveredMap = [];
        foreach ($deliveredRows as $row) {
            $deliveredMap[$row->sales_order_item_id] = (float) $row->total;
        }

        $anyDispatched = false;
        $allDispatched = true;
        $anyDelivered = false;
        $allDelivered = true;

        foreach ($soItems as $soItem) {
            
            $ordered = (float) $soItem->ordered_qty;
            $dispatched = $dispatchedMap[$soItem->id] ?? 0;
            $delivered = $deliveredMap[$soItem->id]  ?? 0;

            if ($dispatched > 0) $anyDispatched = true;
            if ($dispatched < $ordered) $allDispatched = false;

            if ($delivered > 0) $anyDelivered = true;
            if ($delivered < $ordered) $allDelivered = false;
        }

        if ($allDelivered && $anyDelivered) {
            $newStatus = 'delivered';
        } elseif ($anyDelivered) {
            $newStatus = 'partially_delivered';
        } elseif ($allDispatched && $anyDispatched) {
            $newStatus = 'dispatched';
        } elseif ($anyDispatched) {
            $newStatus = 'partially_dispatched';
        } else {
            $newStatus = 'confirmed';
        }

        $this->db->update("sales_orders", ["status" => $newStatus], "id = {$soId} AND company_id = {$companyId}");
    }



    public function logHistory(int $dnId, array $payload): int {

        $meta = empty($payload['meta']) ? null : json_encode($payload['meta'], JSON_UNESCAPED_UNICODE);

        $history = new Models_SalesDeliveryHistory();
        $history->company_id = $this->context->companyId;
        $history->sales_delivery_id = $dnId;
        $history->log_type = $payload['log_type'];
        $history->title = $payload['title'];
        $history->reference_type = $payload['reference_type'] ?? null;
        $history->reference_id = $payload['reference_id'] ?? null;
        $history->meta = $meta;
        $history->created_by = $this->context->userId;

        $historyId = $history->create();
        if (!$historyId) {
            throw new Service_Exception("Failed to log delivery history");
        }
        return (int) $historyId;
    }


    public function getFormContext(int $dnId = 0, int $soId = 0): array {

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $dnDetails = [];
        if ($dnId > 0) {

            $dn = $this->getDeliveryOrFail($dnId);
            $dnItems = $dn->items;

            // Attach serial assignments to each DN item so the edit form can pre-fill them
            if (!empty($dnItems)) {
                $serialRows = $this->db->fetchAll(
                    "SELECT sales_delivery_item_id, serial_number
                     FROM sales_delivery_item_serials
                     WHERE company_id = ? AND sales_delivery_id = ?
                     ORDER BY id ASC",
                    [$companyId, $dnId]
                );
                $serialsByItemId = [];
                foreach ($serialRows as $sr) {
                    $serialsByItemId[$sr->sales_delivery_item_id][] = $sr->serial_number;
                }
                foreach ($dnItems as $dnItem) {
                    $dnItem->serials = $serialsByItemId[$dnItem->id] ?? [];
                }
            }

            $dnDetails = array_merge(['id' => $dnId, 'items' => $dnItems], $dn->toArray());
            $soId = $dn->sales_order_id;

            // Decode snapshot so JS can restore address fields
            if (!empty($dnDetails['shipping_address_snapshot'])) {
                $dnDetails['shipping_address_snapshot'] = json_decode($dnDetails['shipping_address_snapshot'], true);
            }
        }

        // SO items with remaining qty
        $soDetails = [];
        $soInfo = [];
        $soItems = [];
        $customerAddresses = [];
        if ( $soId > 0 ) {

            $remainingQty = $this->getRemainingQtyBySoItem($soId, $dnId);

            $sql = "SELECT so.id AS so_id, so.so_number, so.customer_id, so.source_warehouse_id,
                        c.display_name AS customer_disp_name,
                        loc.name AS source_warehouse_name,
                        soi.*, p.stock_tracking_method
                    FROM sales_orders AS so
                    INNER JOIN sales_order_items AS soi ON soi.sales_order_id=so.id
                    LEFT JOIN products AS p ON p.id=soi.product_id
                    LEFT JOIN customers AS c ON c.id=so.customer_id
                    LEFT JOIN inv_warehouses AS loc ON loc.id=so.source_warehouse_id
                    WHERE 
                        so.id = ? AND 
                        so.company_id = ?
                    ORDER BY soi.id ASC";
            $rows = $this->db->fetchAll($sql, [$soId, $companyId]);

            foreach ($rows as $row) {

                $soItems[] = [
                    'id' => $row->id,
                    'product_id' => $row->product_id,
                    'product_name' => $row->product_name,
                    'stock_tracking_method'=> $row->stock_tracking_method,
                    'ordered_qty' => $row->ordered_qty,
                    'uom_code' => $row->uom_code,
                    'description' => $row->description,
                    'remaining_qty' => $remainingQty[$row->id] ?? 0,
                ];

                $soDetails = [
                    'id' => $row->so_id,
                    'so_number' => $row->so_number,
                    'customer_id' => $row->customer_id,
                    'customer_disp_name' => $row->customer_disp_name,
                ];

                $soInfo = [
                    'id'                   => $row->so_id,
                    'so_number'            => $row->so_number,
                    'customer_id'          => $row->customer_id,
                    'customer_name'        => $row->customer_disp_name,
                    'source_warehouse_id'  => $row->source_warehouse_id,
                    'source_warehouse_name'=> $row->source_warehouse_name,
                ];
            }

            // SO delivery type and shipping address snapshot
            if (!empty($soInfo['id'])) {
                $soRow = $this->db->fetchOne(
                    "SELECT delivery_type, shipping_address_snapshot FROM sales_orders WHERE id = ? LIMIT 1",
                    [$soInfo['id']]
                );
                if ($soRow) {
                    $soInfo['delivery_type']    = $soRow->delivery_type;
                    $soInfo['shipping_address'] = !empty($soRow->shipping_address_snapshot)
                        ? json_decode($soRow->shipping_address_snapshot, true)
                        : null;
                }
            }

            // Customer shipping addresses — full objects for edit/snapshot
            if (!empty($soInfo['customer_id'])) {

                $sql = "SELECT id, address_line1, address_line2, city, state, country, postal_code, attention, phone
                        FROM customer_addresses
                        WHERE company_id = ? AND customer_id = ? AND address_type = 'shipping'
                        ORDER BY is_default DESC, id ASC";
                $addrRows = $this->db->fetchAll($sql, [$companyId, $soInfo['customer_id']]);

                foreach ($addrRows as $addr) {
                    $parts = array_filter([$addr->address_line1, $addr->address_line2, $addr->city, $addr->state, $addr->country]);
                    $customerAddresses[] = [
                        'id'            => $addr->id,
                        'label'         => implode(', ', $parts),
                        'attention'     => $addr->attention,
                        'phone'         => $addr->phone,
                        'address_line1' => $addr->address_line1,
                        'address_line2' => $addr->address_line2,
                        'city'          => $addr->city,
                        'state'         => $addr->state,
                        'postal_code'   => $addr->postal_code,
                        'country'       => $addr->country,
                    ];
                }
            }
        }

        $locations = Service_Company::getActiveWarehouses($companyId);

        $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

        return [
            'so_details' => $soDetails,
            'so_info' => $soInfo,
            'dn_details' => $dnDetails,
            'so_items' => $soItems,
            'locations' => $locations,
            'customer_addresses' => $customerAddresses,
            'suggested_dn_number' => $seqService->nextPreview("sales_deliveries"),
        ];
    }


    public function searchSalesOrders(string $query): array {

        $companyId = $this->context->companyId;
        $like = '%' . $query . '%';

        $sql = "SELECT so.id, so.so_number, so.status, c.display_name AS customer_name
                FROM sales_orders AS so
                LEFT JOIN customers AS c ON c.id = so.customer_id
                WHERE 
                    so.company_id = ? AND 
                    so.status IN ('confirmed', 'partially_dispatched', 'partially_delivered') AND 
                    (so.so_number LIKE ? OR c.display_name LIKE ?)
                ORDER BY so.id DESC
                LIMIT 25";
        $rows = $this->db->fetchAll($sql, [$companyId, $like, $like]);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row->id,
                'so_number' => $row->so_number,
                'status' => $row->status,
                'customer_name' => $row->customer_name,
            ];
        }
        return $data;
    }


    public function create(array $payload): array {

        if (!$this->context->canDo('sales_deliveries', 'write')) {
            throw new Service_Exception('You do not have permission to create sales deliveries', 403);
        }

        $this->validatePayload($payload);

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $this->db->startTransaction();

        try {

            $companyId = $this->context->companyId;
            $userId = $this->context->userId;
            $soId = (int) ($payload['sales_order_id'] ?? 0);
            $status = ($payload["status"] ?? "") ?: "draft";
            $lineItems = $payload["items"] ?? [];

            // DN Number
            $dnNumberInput = trim($payload['dn_number'] ?? '');
            $dnNumberSuggested = trim($payload['dn_number_suggested'] ?? '');


            if (empty($dnNumberInput) || $dnNumberInput === $dnNumberSuggested) {
                
                $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));
                $dnNumber = $seqService->nextCommit("sales_deliveries");
            } else {
                
                $dnNumber = $dnNumberInput;
            }
            
            // Shipping address snapshot from SO
            //$so = new Models_SalesOrder($soId);
            //$shippingSnapshot = $so->shipping_address_snapshot;

            $soMetaData = [];
            $salesOrderStatus = "";
            if( $soId ) {

                $salesOrder = new Models_SalesOrder($soId);
                $salesOrderStatus = $salesOrder->status;
                $soMetaData = [
                    'so_id' => $salesOrder->id,
                    'so_number' => $salesOrder->so_number,
                ];
            }



            /**
             * Skip this logic for now, we need to update this logic to update linking when DN Status changed
             */
            /*
            // Back-order detection — find the most first dispatched DN for this SO
            $backOrderOf = null;
            $lastDn = $this->db->fetchOne("SELECT id FROM sales_deliveries WHERE company_id = ? AND sales_order_id = ? AND status IN ('dispatched','delivered','lost') ORDER BY id DESC LIMIT 1", [$companyId, $soId]);
            if ($lastDn) {
                $backOrderOf = $lastDn->id;
            }
            */
            $backOrderOf = null;

            $dispatchDate = !empty($payload['dispatch_date']) ? $payload['dispatch_date'] : null;
            $deliveryDate = !empty($payload['delivery_date']) ? $payload['delivery_date'] : null;
            
            if ($status === 'dispatched' ) {
                if( empty($dispatchDate) ) {
                    $dispatchDate = dateNow("Y-m-d");
                }                
            }
            else if ($status === 'delivered' ) {

                if( empty($deliveryDate) ) {
                    $deliveryDate = dateNow("Y-m-d");
                }

                if( empty($dispatchDate) ) {
                    $dispatchDate = $deliveryDate;
                }
            }    

            // Shipping address snapshot
            $shippingSnapshot = null;
            $shippingAddressJson = $payload['shipping_address_json'] ?? null;
            if (!empty($shippingAddressJson)) {
                $addr = is_string($shippingAddressJson) ? json_decode($shippingAddressJson, true) : (array) $shippingAddressJson;
                if (is_array($addr) && !empty(array_filter($addr))) {
                    $shippingSnapshot = json_encode($addr, JSON_UNESCAPED_UNICODE);
                }
            }

            $delivery = new Models_SalesDelivery();
            $delivery->fillFromArray($payload);
            if (!Service_CompanySettings::isMultiWarehouseEnabled($companyId)) {
                $delivery->warehouse_id = Service_Company::getDefaultWarehouseId($companyId) ?? 0;
            }
            $delivery->company_id = $companyId;
            $delivery->dn_number = $dnNumber;
            $delivery->sales_order_id = $soId ?: null;
            $delivery->back_order_of = $backOrderOf;
            $delivery->status = $status;
            $delivery->dispatch_date = $dispatchDate;
            $delivery->delivery_date = $deliveryDate;
            $delivery->shipping_address_snapshot = $shippingSnapshot;
            $delivery->created_by = $userId;

            
            $dnId = $delivery->create();
            if (!$dnId) {
                throw new Service_Exception("Failed to create delivery note");
            }

            // save line items
            $this->saveLineItems($delivery, $lineItems);

            // log event
            $this->logHistory($dnId, [
                'log_type' => 'created',
                'title' => 'Delivery note created #' . $dnNumber,
                'meta' => array_merge(['dn_number' => $dnNumber, "status" => $status], $soMetaData),
            ]);

            if ($status === 'dispatched' || $status === 'delivered') {

                $delivery->refreshById($dnId);

                // Determine if SO had confirmed status (stock was reserved at SO location)
                $releaseReservedQty = in_array($salesOrderStatus, ['confirmed', 'partially_dispatched', 'partially_delivered']);

                // Sales Order Location Id
                $soSourceWarehouseId = $soId ? $salesOrder->source_warehouse_id : 0;

                $deliveryItems = $this->normalizeLineItems($lineItems, "form_request", "reduce_stock", ["warehouse_id" => $delivery->warehouse_id]);
                $this->reduceStock($delivery, $deliveryItems, $releaseReservedQty, (int) $soSourceWarehouseId);

                $this->stampDeliveryItemCosts($dnId);

                if( $soId ) {
                    // Recalculate SO status → delivered
                    $this->recalculateSoStatus($soId);
                    // Cache weighted-average cost on each SO line item
                    (new Service_So_Order($this->context))->recalculateSoItemActualCosts($soId);
                }
            }

            // create sales order log about delivery note created
            if( $soId ) {

                $order = new Service_So_Order(new Service_TenantContext($this->context->companyId, $this->context->userId));
                $order->logHistory($soId, [
                    'log_type' => 'dn_created',
                    'title' => 'Delivery note created #'.$dnNumber,
                    'reference_type' => 'sales_delivery',
                    'reference_id' => $dnId,
                    'meta' => [
                        'dn_number' => $dnNumber,
                        'dn_status' => $status,
                    ]
                ]);
            }
            
            $this->db->commit();

            return ["success" => true, "data" => ["dn_id" => $dnId, "dn_number" => $dnNumber]];

        } catch (Exception $e) {
            
            $this->db->rollback();
            throw $e;
        }
    }


    public function update(int $dnId, array $payload): array {

        if (!$this->context->canDo('sales_deliveries', 'write')) {
            throw new Service_Exception('You do not have permission to update sales deliveries', 403);
        }

        $delivery = $this->getDeliveryOrFail($dnId);

        if ($delivery->status !== 'draft') {
            throw new Service_Exception("Only draft delivery notes can be edited. Cancel the delivery note and create new one for changes.", 422);
        }        

        $this->validatePayload($payload, $dnId);

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $this->db->startTransaction();

        try {

            $oldDeliveryDetails = $delivery->toArray();

            $companyId = $this->context->companyId;
            $userId = $this->context->userId;
            $soId = (int) ($payload['sales_order_id'] ?? $delivery->sales_order_id ?? 0);
            $lineItems = $payload["items"] ?? [];
            $status = ($payload["status"] ?? "") ?: "draft";

            $salesOrder = null;
            $salesOrderStatus = "";
            $soMetaData = [];

            if ($soId) {
                
                $salesOrder = new Models_SalesOrder($soId);
                $salesOrderStatus = $salesOrder->status;
                $soMetaData = [
                    'so_id' => $salesOrder->id,
                    'so_number' => $salesOrder->so_number,
                ];
            }

            $dispatchDate = $payload['dispatch_date'] ?? null;
            $deliveryDate = $payload['delivery_date'] ?? null;

            if ($status === 'dispatched') {

                if( empty($dispatchDate) ) {
                    $dispatchDate = date("Y-m-d");
                }
                
            } elseif ($status === 'delivered') {

                if( empty($deliveryDate) ) {
                    $deliveryDate = date("Y-m-d");
                }

                if( empty($dispatchDate) ) {
                    $dispatchDate = $deliveryDate;
                }                
            }

            /*
            $delivery->warehouse_id = (int) ($payload['warehouse_id'] ?? $delivery->warehouse_id);
            $delivery->customer_id = (int) ($payload['customer_id'] ?? $delivery->customer_id);
            $delivery->fulfilment_type = $payload['fulfilment_type'] ?? $delivery->fulfilment_type;
            $delivery->status = $status;
            $delivery->dispatch_date = $dispatchDate;
            $delivery->delivery_date = $deliveryDate;
            $delivery->carrier = $payload['carrier'] ?? null;
            $delivery->tracking_number = $payload['tracking_number'] ?? null;
            $delivery->notes = $payload['notes'] ?? null;
            */

            // Shipping address snapshot
            $shippingSnapshot = $delivery->shipping_address_snapshot;
            $shippingAddressJson = $payload['shipping_address_json'] ?? null;
            if (!empty($shippingAddressJson)) {
                $addr = is_string($shippingAddressJson) ? json_decode($shippingAddressJson, true) : (array) $shippingAddressJson;
                if (is_array($addr) && !empty(array_filter($addr))) {
                    $shippingSnapshot = json_encode($addr, JSON_UNESCAPED_UNICODE);
                }
            } elseif (array_key_exists('shipping_address_json', $payload)) {
                // Explicit empty string means clear the snapshot (delivery_type changed to pickup)
                $shippingSnapshot = null;
            }

            $delivery->fillFromArray($payload, ['id', 'dn_number', 'company_id', 'sales_order_id', 'customer_id', 'created_at', 'created_by', 'shipping_address_snapshot']);
            if (!Service_CompanySettings::isMultiWarehouseEnabled($this->context->companyId)) {
                $delivery->warehouse_id = Service_Company::getDefaultWarehouseId($this->context->companyId) ?? 0;
            }
            $delivery->status = $status;
            $delivery->dispatch_date = $dispatchDate;
            $delivery->delivery_date = $deliveryDate;
            $delivery->shipping_address_snapshot = $shippingSnapshot;

            if (!$delivery->update()) {
                throw new Service_Exception("Failed to update delivery note");
            }

            $newDeliveryDetails = $delivery->toArray();

            // Log changed header fields
            $trackFields = [
                'warehouse_id' => 'Location',
                'fulfilment_type' => 'Delivery method',
                'status' => 'Status',
                'dispatch_date' => 'Dispatch date',
                'delivery_date' => 'Delivery date',
                'carrier' => 'Carrier',
                'tracking_number' => 'Tracking number',
                'notes' => 'Notes',
            ];

            $updatedDetails = [];
            foreach ($trackFields as $field => $label) {
                
                $oldVal = $oldDeliveryDetails[$field] ?? '';
                $newVal = $newDeliveryDetails[$field] ?? '';

                if ($oldVal != $newVal) {
                    $updatedDetails[] = [
                        'field'   => $field,
                        'label'   => $label,
                        'old_val' => $oldVal,
                        'new_val' => $newVal,
                    ];
                }
            }

            if (!empty($updatedDetails)) {
                
                $this->logHistory($dnId, [
                    'log_type' => 'updated_details',
                    'title' => 'Delivery note updated',
                    'meta' => $updatedDetails,
                ]);
            }


            $itemUpdateLog = $this->saveLineItems($delivery, $lineItems);

            if (!empty($itemUpdateLog)) {
                
                $this->logHistory($dnId, [
                    'log_type' => 'updated_items',
                    'title' => 'Line items updated',
                    'meta' => $itemUpdateLog,
                ]);
            }

            if ($status === 'dispatched' || $status === 'delivered') {

                $delivery->refreshById($dnId);

                $releaseReservedQty = in_array($salesOrderStatus, ['confirmed', 'partially_dispatched', 'partially_delivered']);

                // Sales Order Location Id
                $soSourceWarehouseId = $soId ? $salesOrder->source_warehouse_id : 0;
                
                $deliveryItems = $this->normalizeLineItems($lineItems, "form_request", "reduce_stock", ["warehouse_id" => $delivery->warehouse_id]);
                $this->reduceStock($delivery, $deliveryItems, $releaseReservedQty, (int) $soSourceWarehouseId);

                $this->stampDeliveryItemCosts($dnId);

                if ($soId) {
                    $this->recalculateSoStatus($soId);
                    (new Service_So_Order($this->context))->recalculateSoItemActualCosts($soId);
                }
            }

            if ($soId) {

                $order = new Service_So_Order(new Service_TenantContext($companyId, $userId));
                $order->logHistory($soId, [
                    'log_type' => 'dn_updated',
                    'title' => 'Delivery note updated #' . $delivery->dn_number,
                    'reference_type' => 'sales_delivery',
                    'reference_id' => $dnId,
                    'meta' => array_merge([
                        'dn_number' => $delivery->dn_number,
                        'dn_status' => $status,
                    ], $soMetaData),
                ]);
            }

            $this->db->commit();

            return ["success" => true, "data" => ["dn_id" => $dnId, "dn_number" => $delivery->dn_number]];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    /**
     * Cancel a draft DN as part of a parent SO cancellation.
     * Runs inside the caller's transaction — no own transaction management.
     * Does not recalculate SO status; the caller is cancelling the SO itself.
     */
    public function cancelDraftForSoCancel(int $dnId): void
    {
        $delivery = new Models_SalesDelivery($dnId);
        if ($delivery->isEmpty || $delivery->status !== 'draft') return;

        $this->restoreSerials($delivery);

        $delivery->status = 'cancelled';
        $delivery->update(['status', 'updated_at']);

        $this->logHistory($dnId, [
            'log_type' => 'status_changed',
            'title'    => 'Cancelled via Sales Order cancellation',
            'meta'     => [
                'old_status' => 'Draft',
                'new_status' => 'Cancelled',
                'notes'      => '',
            ],
        ]);
    }


    public function updateStatus(int $dnId, array $payload): array {

        $status = trim($payload['status'] ?? '');

        $requiredAction = ($status === 'cancelled') ? 'cancel' : 'write';
        if (!$this->context->canDo('sales_deliveries', $requiredAction)) {
            throw new Service_Exception('You do not have permission to perform this action on sales deliveries', 403);
        }

        $delivery = $this->getDeliveryOrFail($dnId);
        $notes = trim($payload['notes']  ?? '');
        

        $allowedTransitions = [
            'draft' => ['dispatched', 'cancelled'],
            'dispatched' => ['delivered', 'returned', 'lost', 'draft'],
            'delivered' => ['draft'],
        ];
        $oldStatus = $delivery->status;

        if (!isset($allowedTransitions[$oldStatus]) || !in_array($status, $allowedTransitions[$oldStatus])) {
            throw new Service_Exception("Cannot transition delivery note from '{$oldStatus}' to '{$status}'", 422);
        }

        // Reopen eligibility check — must pass before opening the transaction
        if ($status === 'draft' && in_array($oldStatus, ['dispatched', 'delivered'])) {
            $reopenCheck = $this->checkCanReopen($dnId, $this->context->companyId);
            if (!$reopenCheck['can_reopen']) {
                throw new Service_Exception($reopenCheck['reason'], 422);
            }
        }

        $this->db->startTransaction();

        try {

            $soId = $delivery->sales_order_id;
            $updateFields = ["status", "updated_at"];

            $salesOrderStatus = "";
            if( $soId ) {
                $salesOrder = new Models_SalesOrder($soId);
                $salesOrderStatus = $salesOrder->status;
            }


            // Stock actions
            if ($status === 'dispatched' ) {

                // Resolve warehouse — fall back to default for single-warehouse mode (covers DNs saved with warehouse_id=0)
                $companyId = $this->context->companyId;
                if (!(int) $delivery->warehouse_id && !Service_CompanySettings::isMultiWarehouseEnabled($companyId)) {
                    $delivery->warehouse_id = Service_Company::getDefaultWarehouseId($companyId) ?? 0;
                    $delivery->update(['warehouse_id']);
                }

                // Validate physical stock availability before deducting (throws on insufficient stock)
                $dispatchItems = array_map(fn($i) => [
                    'product_id'     => $i->product_id,
                    'dispatched_qty' => $i->dispatched_qty,
                    'dn_item_id'     => $i->id,
                ], $delivery->items);
                $this->validateStockForDispatch((int) $delivery->warehouse_id, $dispatchItems, true, $delivery->id);


                // Sales Order Location Id
                $soSourceWarehouseId = $soId ? $salesOrder->source_warehouse_id : 0;

                $releaseReservedQty = in_array($salesOrderStatus, ['confirmed', 'partially_dispatched', 'partially_delivered']);

                $deliveryItems = $this->normalizeLineItems($delivery->items, "delivery", "reduce_stock", ["warehouse_id" => $delivery->warehouse_id]);
                $this->reduceStock($delivery, $deliveryItems, $releaseReservedQty, (int) $soSourceWarehouseId);

                $this->stampDeliveryItemCosts($dnId);

                if( empty($delivery->dispatch_date) ) {
                    $delivery->dispatch_date = dateNow("Y-m-d");
                    $updateFields[] = "dispatch_date";
                }
            }

            if( $status === 'delivered' ) {
                
                if( empty($delivery->delivery_date) ) {
                    $delivery->delivery_date = dateNow("Y-m-d");
                    $updateFields[] = "delivery_date";
                }
            }


            // Release reserved serials when a draft DN is cancelled
            // (Stock was never reduced for draft, but serials may have been reserved on assignment)
            if ($status === 'cancelled' && $oldStatus === 'draft') {
                $this->restoreSerials($delivery);
            }

            // Revert to Open (Dispatched => Draft or Delivered => Draft)
            $reOpenDn = $status === 'draft' && in_array($oldStatus, ['dispatched', 'delivered']);
            
            if ($status === 'returned' || $reOpenDn) {

                // Sales Order Location Id
                $soSourceWarehouseId = $soId ? $salesOrder->source_warehouse_id : 0;

                $restoreReservationAllowedSOStatus = ['confirmed', 'partially_dispatched', 'dispatched', 'partially_delivered'];
                if( $reOpenDn ) {
                    $restoreReservationAllowedSOStatus[] = "delivered";
                }
                
                $shouldRestoreReservation = in_array($salesOrderStatus, $restoreReservationAllowedSOStatus);
                // Keep serial assignments in both cases: reopen (so draft DN retains them) and returned (historical display)
                $this->restoreStock($delivery, $shouldRestoreReservation, (int) $soSourceWarehouseId, true, $reOpenDn, $notes);

                $delivery->dispatch_date = null;
                $delivery->delivery_date = null;
                $updateFields[] = "dispatch_date";
                $updateFields[] = "delivery_date";
            }

            if ($status === 'lost') {

                // Goods are physically gone — unrestricted_qty stays reduced from dispatch, no reservation to restore.
                // SO status is recalculated below via recalculateSoStatus().
                // Mark assigned serials as lost-in-transit so they are distinguishable from properly-delivered serials.
                $now        = date('Y-m-d H:i:s');
                $companyId  = $this->context->companyId;
                $invService = new Service_Inv_Movement($this->context);
                $assignments = $this->db->fetchAll(
                    "SELECT sdis.serial_id, sdi.product_id
                     FROM sales_delivery_item_serials AS sdis
                     INNER JOIN sales_delivery_items AS sdi ON sdi.id = sdis.sales_delivery_item_id
                     WHERE sdis.company_id = ? AND sdis.sales_delivery_id = ?",
                    [$companyId, $delivery->id]
                );
                foreach ($assignments as $a) {
                    $this->db->query(
                        "UPDATE inv_serials SET status = 'lost', updated_at = ? WHERE id = ? AND company_id = ? AND status = 'sold'",
                        [$now, $a->serial_id, $companyId]
                    );
                    $invService->logSerialHistory($a->serial_id, $a->product_id, 'lost', 'Marked lost-in-transit via DN #' . $delivery->dn_number, 'sales_delivery', (int)$delivery->id, ['from_status' => 'sold', 'to_status' => 'lost']);
                }
            }

            
            $delivery->status = $status;
            if (!$delivery->update($updateFields)) {
                throw new Service_Exception("Failed to update delivery note status");
            }

            // Recalculate SO status after any transition that changes dispatched/delivered totals
            if ($soId && in_array($status, ['dispatched', 'delivered', 'returned', 'lost', 'cancelled', 'draft'])) {
                $this->recalculateSoStatus($soId);
                // On revert-to-draft or return, actual_cost may change because fewer dispatches count
                if (in_array($status, ['returned', 'draft'])) {
                    (new Service_So_Order($this->context))->recalculateSoItemActualCosts($soId);
                }
            }

            $statusLabels = [
                'draft' => 'Draft',
                'dispatched' => 'Dispatched',
                'delivered' => 'Delivered',
                'returned' => 'Returned',
                'lost' => 'Lost',
                'cancelled'  => 'Cancelled',
            ];

            $logTitle = ($reOpenDn) ? 'Delivery note Reopen' : 'Marked as ' . ($statusLabels[$status] ?? $status);
            $this->logHistory($dnId, [
                'log_type' => 'status_changed',
                'title' => $logTitle,
                'meta' => [
                    'old_status' => $statusLabels[$oldStatus] ?? $oldStatus,
                    'new_status' => $statusLabels[$status] ?? $status,
                    'notes' => $notes,
                ],
            ]);


            // create sales order log about delivery note created
            if( $delivery->sales_order_id ) {

                $orderUpdateLogTitleMapping = [
                    'dispatched' => 'Dispatched',
                    'delivered' => 'Delivered',
                    'returned' => 'Returned',
                    'lost' => 'Marked as Lost',
                    'cancelled' => 'Cancelled',
                    'draft' => 'Dispatch Reverted to Draft',
                ];

                $dnActionTitle = $orderUpdateLogTitleMapping[$status] ?? "updated";
                $dnActionTitle = $reOpenDn ? "Reopen" : $dnActionTitle;

                $order = new Service_So_Order(new Service_TenantContext($this->context->companyId, $this->context->userId));
                $order->logHistory($delivery->sales_order_id, [
                    'log_type' => 'dn_status_changed',
                    'title' => 'Delivery note #'.$delivery->dn_number.' '.$dnActionTitle,
                    'reference_type' => 'sales_delivery',
                    'reference_id' => $dnId,
                    'meta' => [
                        'dn_number' => $delivery->dn_number,
                        'old_status' => $statusLabels[$oldStatus] ?? $oldStatus,
                        'new_status' => $statusLabels[$status] ?? $status,
                    ]
                ]);
            }

            $this->db->commit();

            return ["success" => true, "data" => ["dn_id" => $dnId, "status" => $status, "old_status" => $oldStatus]];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    /**
     * Check whether a delivered/dispatched DN can be reverted to draft.
     * Blocked if: any serial from the DN exists in a non-cancelled return,
     * or if reverting would leave fewer net deliveries than returns for any item.
     */
    private function checkCanReopen(int $dnId, int $companyId): array
    {
        // Serial check: any serial dispatched in this DN present in a non-cancelled return
        $serialRow = $this->db->fetchOne(
            "SELECT COUNT(*) AS cnt
             FROM sales_delivery_item_serials sdis
             JOIN return_item_serials ris ON ris.serial_id = sdis.serial_id
             JOIN returns r ON r.id = ris.return_id
             WHERE sdis.sales_delivery_id = ?
               AND sdis.company_id = ?
               AND r.company_id = ?
               AND r.status != 'cancelled'",
            [$dnId, $companyId, $companyId]
        );
        if ((int) ($serialRow->cnt ?? 0) > 0) {
            return [
                'can_reopen' => false,
                'reason'     => 'Cannot revert: one or more serials from this delivery have been returned.',
            ];
        }

        // Qty/lot/non-stocked check: (total_delivered - total_returned) >= this_dn_qty for each item
        $items = $this->db->fetchAll(
            "SELECT sdi.sales_order_item_id, sdi.dispatched_qty,
                    COALESCE(soi.product_name, p.name) AS product_name,
                    p.stock_tracking_method
             FROM sales_delivery_items sdi
             JOIN products p ON p.id = sdi.product_id
             LEFT JOIN sales_order_items soi ON soi.id = sdi.sales_order_item_id
             WHERE sdi.sales_delivery_id = ?
               AND p.stock_tracking_method != 'serial'",
            [$dnId]
        );

        foreach ($items as $item) {
            if (!(int) $item->sales_order_item_id) continue;

            $delivered = $this->db->fetchOne(
                "SELECT COALESCE(SUM(sdi2.dispatched_qty), 0) AS total
                 FROM sales_delivery_items sdi2
                 JOIN sales_deliveries sd ON sd.id = sdi2.sales_delivery_id
                 WHERE sdi2.sales_order_item_id = ?
                   AND sd.company_id = ?
                   AND sd.status IN ('dispatched', 'delivered')",
                [(int) $item->sales_order_item_id, $companyId]
            );

            $returned = $this->db->fetchOne(
                "SELECT COALESCE(SUM(ri.return_qty), 0) AS total
                 FROM return_items ri
                 JOIN returns r ON r.id = ri.return_id
                 WHERE ri.reference_item_id = ?
                   AND r.company_id = ?
                   AND r.status != 'cancelled'",
                [(int) $item->sales_order_item_id, $companyId]
            );

            $netRemaining = (float) ($delivered->total ?? 0) - (float) ($returned->total ?? 0);
            if ($netRemaining < (float) $item->dispatched_qty) {
                return [
                    'can_reopen' => false,
                    'reason'     => 'Cannot revert: "' . $item->product_name . '" has returns that cannot be covered by remaining deliveries if this note is reopened.',
                ];
            }
        }

        return ['can_reopen' => true, 'reason' => null];
    }


    public function getDetails(int $dnId): array {

        $dn = $this->getDeliveryOrFail($dnId);
        $companyId = $this->context->companyId;

        // Fetch items enriched with product_name, tracking method and ordered_qty
        $enrichedItems = $this->db->fetchAll(
            "SELECT
                sdi.*,
                COALESCE(soi.product_name, p.name) AS product_name,
                p.stock_tracking_method,
                soi.ordered_qty
            FROM sales_delivery_items sdi
            LEFT JOIN sales_order_items soi ON soi.id = sdi.sales_order_item_id
            LEFT JOIN products p ON p.id = sdi.product_id
            WHERE sdi.sales_delivery_id = ?",
            [$dnId]
        );

        // Load serials and lots per item
        $itemsWithTracking = [];
        foreach ($enrichedItems as $item) {

            $serials = $this->db->fetchAll(
                "SELECT serial_number FROM sales_delivery_item_serials WHERE sales_delivery_item_id = ? AND company_id = ?",
                [$item->id, $companyId]
            );

            $lots = $this->db->fetchAll(
                "SELECT lot_number, qty FROM sales_delivery_item_lots WHERE sales_delivery_item_id = ? AND company_id = ?",
                [$item->id, $companyId]
            );

            $itemArr = (array) $item;
            $itemArr['ordered_qty'] = (float) $item->ordered_qty;
            $itemArr['serials'] = array_column((array) $serials, 'serial_number');
            $itemArr['lots']    = $lots;
            $itemsWithTracking[] = $itemArr;
        }

        $so       = $dn->sales_order_id ? new Models_SalesOrder($dn->sales_order_id) : null;
        $location = $dn->warehouse_id    ? new Models_InvWarehouse($dn->warehouse_id)     : null;

        $reopenCheck = in_array($dn->status, ['dispatched', 'delivered'])
            ? $this->checkCanReopen($dnId, $companyId)
            : ['can_reopen' => false, 'reason' => null];

        $dnDetails = array_merge(
            [
                'id'                    => $dnId,
                'customer_name'         => $dn->customer->display_name,
                'so_number'             => $so       ? $so->so_number : null,
                'warehouse_name'         => $location ? $location->name : null,
                'items'                 => $itemsWithTracking,
                'can_reopen'            => $reopenCheck['can_reopen'],
                'reopen_blocked_reason' => $reopenCheck['reason'],
            ],
            $dn->toArray()
        );

        return ['dn_details' => $dnDetails];
    }


    /**
     * Stamps unit_cost on delivery line items that don't yet have a cost recorded.
     * Called immediately after reduceStock() so the cost snapshot reflects the WAC
     * at the exact moment of dispatch. The IS NULL guard makes it safe to call multiple times.
     */
    private function stampDeliveryItemCosts(int $dnId): void {
        $this->db->query(
            "UPDATE sales_delivery_items sdi
             JOIN products p ON p.id = sdi.product_id
             SET sdi.unit_cost = COALESCE(p.current_cost, p.cost_price, 0)
             WHERE sdi.sales_delivery_id = ? AND sdi.unit_cost IS NULL",
            [$dnId]
        );
    }


    public function getHistory(int $dnId): array {

        $this->getDeliveryOrFail($dnId);

        $sql = "SELECT a.*, b.name AS performed_by
                FROM sales_delivery_history AS a
                LEFT JOIN users AS b ON b.id = a.created_by
                WHERE a.company_id = ? AND a.sales_delivery_id = ?
                ORDER BY a.id DESC";

        $rows = $this->db->fetchAll($sql, [$this->context->companyId, $dnId]);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'log_type' => $row->log_type,
                'title' => $row->title,
                'reference_type' => $row->reference_type,
                'reference_id' => $row->reference_id,
                'meta' => json_decode($row->meta ?? '[]', true) ?: [],
                'performed_by' => $row->performed_by,
                'date_time' => formatMySqlDate($row->created_at),
            ];
        }

        return $data;
    }
}
?>