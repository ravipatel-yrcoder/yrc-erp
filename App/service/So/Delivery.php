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


    /*
    private function normalizeLineItems(array $lineItems, string $source, string $context, array $extra): array {

        if( empty($lineItems) ) return [];

        $normalizedLineItems = [];
        if( $source === "form_request" && $context === "reduce_stock" ) {
            
            $locationId = $extra["location_id"] ?? 0;
            foreach($lineItems as $item) {
                $normalizedLineItems[] = [
                    'prod_id' => $item["product_id"],
                    'location_id' => $locationId,
                    'qty' => $item["qty"],
                ];
            }

            return $normalizedLineItems;
        }
        else if( $source === "delivery" && (in_array($context, ["reduce_stock", "reverse_stock"]) ) ) {
        
            $locationId = $extra["location_id"] ?? 0;
            foreach($lineItems as $item) {
                $normalizedLineItems[] = [
                    'prod_id' => $item->product_id,
                    'location_id' => $locationId,
                    'qty' => $item->ordered_qty,
                ];
            }

            return $normalizedLineItems;
        }


        return [];
    }
    */


    private function validatePayload(array $payload, int $dnId = 0): void {

        $soId = (int) ($payload['sales_order_id'] ?? 0);
        $locationId = (int) ($payload['location_id'] ?? 0);
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
        $location = new Models_Location($locationId);
        if ($location->isEmpty || $location->company_id != $this->context->companyId) {
            $this->addError(validationErrMsg("missing_or_invalid", "Location"), "location_id");
        }

        // Status
        if (empty($status)) {
            $this->addError(validationErrMsg("required", "Status"), "status");
        } else if( !in_array($status, ["draft", "dispatched", "delivered", "cancelled"])) {
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
    }


    private function validateItems(array $items, array $remainingQty): void {

        if (empty($items)) {
            $this->addError(validationErrMsg("one_item_required", "line item"), "items");
            return;
        }

        //$companyId = $this->context->companyId;
        $index = 0;
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
            }
            elseif (isset($remainingQty[$soItemId]) && $dispatchedQty > $remainingQty[$soItemId]) {
                
                $remaining = formatQty($remainingQty[$soItemId]);
                $this->addError("Dispatched quantity exceeds remaining quantity ({$remaining}) at row {$row}", "items.{$index}.dispatched_qty");
            }

            $index++;
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
                LEFT JOIN sales_deliveries AS sd ON sd.id = sdi.sales_delivery_id AND sd.company_id = ? AND sd.status NOT IN ('cancelled', 'returned') {$excludeSql}
                WHERE so.id = ?
                GROUP BY soi.id";
                        
        $rows = $this->db->fetchAll($sql, $binding);

        $result = [];
        foreach ($rows as $row) {
            $result[$row->so_item_id] = max(0, (float) $row->ordered_qty - (float) $row->dispatched_total);
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

                $changed = (
                    (float) $dni->dispatched_qty !== $dispatchedQty ||
                    $dni->description !== $description ||
                    $dni->uom_code !== $uomCode
                );

                if ($changed) {

                    $oldQty = (float) $dni->dispatched_qty;
                    $oldUom = $dni->uom_code ?? '';

                    if (!$dni->update()) {
                        throw new Service_Exception($failMsg);
                    }

                    $updateLog[] = [
                        'event' => 'updated',
                        'dn_item_id' => $dni->id,
                        'prod_id' => $productId,
                        'prod_name' => $product->name,
                        'old_qty' => formatQty($oldQty),
                        'new_qty' => formatQty($dispatchedQty),
                        'old_uom' => $oldUom,
                        'new_uom' => $uomCode ?? '',
                    ];
                }
            }
        }

        foreach ($itemsToDelete as $del) {

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
     * @param Models_SalesDelivery $delivery        The DN being dispatched.
     * @param bool $releaseReservedQty  True when the SO was confirmed (stock was reserved at SO location).
     * @param int $soLocationId    The SO location where reservation was originally created.
     *                                              Required when $releaseReservedQty is true.
     *
     * Two separate operations per item:
     *   1. Deduct on_hand_qty from the delivery location  (via Service_Inv_Movement::saleOut)
     *   2. Release reserved_qty from the SO location       (via Service_Inv_Movement::releaseReservation)
     * These are independent because in multi-location scenarios the two locations may differ.
     */
    private function reduceStock(Models_SalesDelivery $delivery, bool $releaseReservedQty = false, int $soLocationId = 0): void {

        $invService = new Service_Inv_Movement($this->context);

        foreach ($delivery->items as $item) {

            $productId = $item->product_id;
            $deliveryItemQty = $item->dispatched_qty;
            $product = new Models_Product($productId);
            $trackingMethod = strtolower($product->stock_tracking_method ?? '');

            if (empty($trackingMethod) || $trackingMethod === 'none') {
                continue;
            }

            /**
             * Yet to implement this
             */
            /*
            // Assign serials/lots FIFO — writes to sales_delivery_item_serials/lots + updates serial status
            if ($trackingMethod === 'serial') {
                $this->assignSerialsFifo($delivery, $item, $companyId, $delivery->location_id);
            }
            if ($trackingMethod === 'lot') {
                $this->assignLotsFifo($delivery, $item, $companyId, $delivery->location_id);
            }
            */

            // 1. Deduct on_hand_qty from the DELIVERY location
            $result = $invService->record([
                'movement_type' => 'sale',
                'location_id' => $delivery->location_id,
                'product_id' => $productId,
                'quantity' => $deliveryItemQty,
                'reference_type' => 'sales_delivery',
                'reference_id' => $delivery->id,
                'notes' => 'Dispatched via ' . $delivery->dn_number,
            ]);

            if (!$result['success']) {
                throw new Service_Exception("Failed to record stock movement for product: " . $item->product_name);
            }

            // 2. Release reserved_qty from the SO location (may differ from delivery location)
            if ($releaseReservedQty && $soLocationId > 0) {

                $invService->releaseReservation([
                    'location_id' => $soLocationId,
                    'product_id' => $productId,
                    'product_name' => $product->name,
                    'quantity' => $deliveryItemQty,
                ]);
            }
        }
    }


    /**
     * Restore stock on return/cancel/revert.
     * Always restores on_hand_qty at the delivery location.
     * Optionally restores reserved_qty at the SO location (revert-to-draft flow only).
     *
     * Two separate operations per item:
     *   1. Restore on_hand_qty at the delivery location  (via Service_Inv_Movement::record)
     *   2. Restore reserved_qty at the SO location       (via Service_Inv_Movement::restoreReservation)
     * These are independent because in multi-location scenarios the two locations may differ.
     */
    private function restoreStock(Models_SalesDelivery $delivery, bool $restoreReservedQty = false, int $soLocationId = 0): void {

        $locationId = $delivery->location_id;
        $invService = new Service_Inv_Movement($this->context);

        foreach ($delivery->items as $item) {

            $productId = $item->product_id;
            $deliveryItemQty = $item->dispatched_qty;

            $product = new Models_Product($productId);
            $trackingMethod = strtolower($product->stock_tracking_method ?? '');

            if (empty($trackingMethod) || $trackingMethod === 'none') {
                continue;
            }

            /**
             * Yet to implement
             */
            /*
            // Restore serial statuses — direct (sales-specific, sets back to 'available')
            if ($trackingMethod === 'serial') {
                $this->restoreSerials($dn->id, $item->id, $companyId);
            }
            */

            // 1. Restore on_hand_qty at the delivery location
            $result = $invService->record([
                'movement_type'  => 'return_from_customer',
                'location_id'    => $locationId,
                'product_id'     => $productId,
                'quantity'       => $deliveryItemQty,
                'reference_type' => 'sales_delivery',
                'reference_id'   => $delivery->id,
                'notes'          => 'Returned via ' . $delivery->dn_number,
            ]);

            if (!$result['success']) {
                throw new Service_Exception("Failed to record stock movement for product: " . $product->name);
            }

            // 2. Restore reserved_qty at the SO location (revert-to-draft flow only)
            if ($restoreReservedQty && $soLocationId > 0) {
                $invService->restoreReservation([
                    'location_id'  => $soLocationId,
                    'product_id'   => $productId,
                    'product_name' => $product->name,
                    'quantity'     => $deliveryItemQty,
                ]);
            }
        }
    }


    /**
     * Auto-assign serial numbers FIFO from available stock at location.
     */

    /*
    private function assignSerialsFifo(Models_SalesDelivery $dn, object $item, int $companyId, int $locationId): void {

        $needed = (int) $item->dispatched_qty;

        // Fetch available serials at this location FIFO
        $sql = "SELECT a.id AS serial_id, a.serial_number
                FROM inv_serials AS a
                INNER JOIN inv_serial_stock AS b ON b.serial_id = a.id
                WHERE a.company_id = ? AND a.product_id = ? AND b.location_id = ?
                  AND a.status = 'available'
                ORDER BY a.created_at ASC
                LIMIT {$needed}
                FOR UPDATE";

        $serials = $this->db->fetchAll($sql, [$companyId, $item->product_id, $locationId]);

        if (count($serials) < $needed) {
            throw new Service_Exception(
                "Not enough available serial numbers for product: " . $item->product_name .
                " (needed {$needed}, found " . count($serials) . ")",
                422
            );
        }

        foreach ($serials as $serial) {

            // Mark serial as dispatched
            $this->db->update("inv_serials", ["status" => "dispatched"], "id = {$serial->serial_id}");

            // Record assignment
            $sdis = new Models_SalesDeliveryItemSerial();
            $sdis->company_id              = $companyId;
            $sdis->sales_delivery_id       = $dn->id;
            $sdis->sales_delivery_item_id  = $item->id;
            $sdis->serial_id               = $serial->serial_id;
            $sdis->serial_number           = $serial->serial_number;
            $sdis->create();
        }
    }
    */


    /**
     * Assign lot quantities FIFO from available lot stock.
     */
    
    /*
    private function assignLotsFifo(Models_SalesDelivery $dn, object $item, int $companyId, int $locationId): void {

        $needed = (float) $item->dispatched_qty;

        // Fetch lot stock at this location FIFO
        $sql = "SELECT a.id AS lot_id, a.lot_number, b.qty AS lot_qty
                FROM inv_serials AS a
                INNER JOIN inv_serial_stock AS b ON b.serial_id = a.id
                WHERE a.company_id = ? AND a.product_id = ? AND b.location_id = ?
                  AND a.lot_id IS NOT NULL AND b.qty > 0
                ORDER BY a.created_at ASC
                FOR UPDATE";

        $lots = $this->db->fetchAll($sql, [$companyId, $item->product_id, $locationId]);

        $remaining = $needed;
        foreach ($lots as $lot) {

            if ($remaining <= 0) break;

            $useQty = min($remaining, (float) $lot->lot_qty);

            // Deduct from lot stock
            $this->db->update(
                "inv_serial_stock",
                ["qty" => (float) $lot->lot_qty - $useQty],
                "serial_id = {$lot->lot_id} AND location_id = {$locationId}"
            );

            // Record lot assignment
            $sdil = new Models_SalesDeliveryItemLot();
            $sdil->company_id              = $companyId;
            $sdil->sales_delivery_id       = $dn->id;
            $sdil->sales_delivery_item_id  = $item->id;
            $sdil->lot_number              = $lot->lot_number;
            $sdil->qty                     = $useQty;
            $sdil->create();

            $remaining -= $useQty;
        }

        if ($remaining > 0) {
            throw new Service_Exception(
                "Not enough lot stock for product: " . $item->product_name,
                422
            );
        }
    }
    */


    /**
     * Restore serial statuses to 'available' when a DN is returned.
     */
    
    /*
    private function restoreSerials(int $dnId, int $dnItemId, int $companyId): void {

        $serials = $this->db->fetchAll(
            "SELECT serial_id FROM sales_delivery_item_serials WHERE sales_delivery_id = ? AND sales_delivery_item_id = ? AND company_id = ?",
            [$dnId, $dnItemId, $companyId]
        );

        foreach ($serials as $s) {
            $this->db->update("inv_serials", ["status" => "available"], "id = {$s->serial_id}");
        }
    }
    */


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

        // Get dispatched totals per SO item (dispatched + delivered + lost count as dispatched)
        $sql = "SELECT 
                    sdi.sales_order_item_id, SUM(sdi.dispatched_qty) AS total
                FROM sales_deliveries sd
                INNER JOIN sales_delivery_items sdi ON sdi.sales_delivery_id = sd.id
                WHERE 
                    sd.company_id = ? AND 
                    sd.sales_order_id = ? AND 
                    sd.status IN ('dispatched', 'delivered', 'lost')
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



    public function logHistory(int $dnId, array $payload): void {

        $meta = empty($payload['meta']) ? null : json_encode($payload['meta'], JSON_UNESCAPED_UNICODE);

        $history = new Models_SalesDeliveryHistory();
        $history->company_id = $this->context->companyId;
        $history->sales_delivery_id = $dnId;
        $history->activity_type = $payload['activity_type'];
        $history->title = $payload['title'];
        $history->meta = $meta;
        $history->created_by = $this->context->userId;

        if (!$history->create()) {
            throw new Service_Exception("Failed to log delivery history");
        }
    }


    public function getFormContext(int $dnId = 0, int $soId = 0): array {

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $dnDetails = [];
        if ($dnId > 0) {

            $dn = $this->getDeliveryOrFail($dnId);
            $dnDetails = array_merge(['id' => $dnId, 'items' => $dn->items], $dn->toArray());
            $soId = $dn->sales_order_id;
        }

        // SO items with remaining qty
        $soDetails = [];
        $soInfo = [];
        $soItems = [];
        $customerAddresses = [];
        if ( $soId > 0 ) {

            $remainingQty = $this->getRemainingQtyBySoItem($soId, $dnId);

            $sql = "SELECT so.id AS so_id, so.so_number, so.customer_id, so.location_id,
                        c.display_name AS customer_disp_name,
                        loc.name AS location_name,
                        soi.*, p.name AS product_name, p.stock_tracking_method
                    FROM sales_orders AS so
                    INNER JOIN sales_order_items AS soi ON soi.sales_order_id=so.id
                    LEFT JOIN products AS p ON p.id=soi.product_id
                    LEFT JOIN customers AS c ON c.id=so.customer_id
                    LEFT JOIN company_locations AS loc ON loc.id=so.location_id
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
                    'id' => $row->so_id,
                    'so_number' => $row->so_number,
                    'customer_id' => $row->customer_id,
                    'customer_name' => $row->customer_disp_name,
                    'location_id' => $row->location_id,
                    'location_name' => $row->location_name,
                ];
            }

            // Customer shipping addresses
            if (!empty($soInfo['customer_id'])) {

                $sql = "SELECT id, address_type, address_line1, address_line2, city, state, country, postal_code
                        FROM customer_addresses
                        WHERE company_id = ? AND customer_id = ? AND address_type = ?
                        ORDER BY id ASC";
                $addrRows = $this->db->fetchAll($sql, [$companyId, $soInfo['customer_id'], 'shipping']);

                foreach ($addrRows as $addr) {
                    
                    $parts = array_filter([$addr->address_line1, $addr->address_line2, $addr->city, $addr->state, $addr->country]);
                    $customerAddresses[] = [
                        'id' => $addr->id,
                        'label' => implode(', ', $parts),
                    ];
                }
            }
        }

        $location = new Models_Location();
        $locations = $location->getAll([], ["company_id" => $companyId, "status" => ["active"]]);

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

            $dispatchDate = $payload['dispatch_date'] ?? null;
            $deliveryDate = $payload['delivery_date'] ?? null;
            
            if ($status === 'dispatched' && empty($dispatchDate) ) {
                $dispatchDate = date("Y-m-d");
            }
            else if ($status === 'delivered' && empty($deliveryDate) ) {
                $deliveryDate = date("Y-m-d");
            }    

            $delivery = new Models_SalesDelivery();
            $delivery->fillFromArray($payload);
            $delivery->company_id = $companyId;
            $delivery->dn_number = $dnNumber;
            $delivery->sales_order_id = $soId ?: null;
            $delivery->back_order_of = $backOrderOf;
            $delivery->status = $status;
            $delivery->dispatch_date = $dispatchDate;
            $delivery->delivery_date = $deliveryDate;
            $delivery->created_by = $userId;

            //$delivery->carrier = $payload['carrier'] ?? null;
            //$delivery->tracking_number = $payload['tracking_number'] ?? null;
            //$delivery->shipping_address_snapshot= $shippingSnapshot;
            //$delivery->notes = $payload['notes'] ?? null;
            

            $dnId = $delivery->create();
            if (!$dnId) {
                throw new Service_Exception("Failed to create delivery note");
            }

            // save line items
            $this->saveLineItems($delivery, $lineItems);

            // log event
            $this->logHistory($dnId, [
                'activity_type' => 'created',
                'title' => 'Delivery note created #' . $dnNumber,
                'meta' => array_merge(['dn_number' => $dnNumber, "status" => $status], $soMetaData),
            ]);

            if ($status === 'dispatched' || $status === 'delivered') {

                $delivery->refreshById($dnId);

                $soLocationId = $soId ? $salesOrder->location_id : 0;

                // Determine if SO had confirmed status (stock was reserved at SO location)
                $releaseReservedQty = in_array($salesOrderStatus, ['confirmed', 'partially_dispatched', 'partially_delivered']);
                $this->reduceStock($delivery, $releaseReservedQty, (int) $soLocationId);

                // Recalculate SO status → delivered
                if( $soId ) {
                    $this->recalculateSoStatus($soId);
                }
            }

            // create sales order log about delivery note created
            if( $soId ) {

                $order = new Service_So_Order(new Service_TenantContext($this->context->companyId, $this->context->userId));
                $order->logHistory($soId, [
                    'activity_type' => 'dn_created',
                    'title' => 'Delivery note created #'.$dnNumber,
                    'meta' => [
                        'dn_id' => $dnId,
                        'dn_number' => $dnNumber,
                        'dn_status' => $status,
                    ]
                ]);
            }
            
            $this->db->commit();

            return ["success" => true, "data" => ["dn_id" => $dnId, "dn_number" => $dnNumber]];

        } catch (Exception $e) {
            
            $this->db->rollBack();
            throw $e;
        }
    }


    public function update(int $dnId, array $payload): array {

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

            if ($status === 'dispatched' && empty($dispatchDate)) {
                $dispatchDate = date("Y-m-d");
            } elseif ($status === 'delivered' && empty($deliveryDate)) {
                $deliveryDate = date("Y-m-d");
            }

            /*
            $delivery->location_id = (int) ($payload['location_id'] ?? $delivery->location_id);
            $delivery->customer_id = (int) ($payload['customer_id'] ?? $delivery->customer_id);
            $delivery->fulfilment_type = $payload['fulfilment_type'] ?? $delivery->fulfilment_type;
            $delivery->status = $status;
            $delivery->dispatch_date = $dispatchDate;
            $delivery->delivery_date = $deliveryDate;
            $delivery->carrier = $payload['carrier'] ?? null;
            $delivery->tracking_number = $payload['tracking_number'] ?? null;
            $delivery->notes = $payload['notes'] ?? null;
            */

            $delivery->fillFromArray($payload, ['id', 'dn_number', 'company_id', 'sales_order_id', 'customer_id', 'created_at', 'created_by']);
            $delivery->status = $status;
            $delivery->dispatch_date = $dispatchDate;
            $delivery->delivery_date = $deliveryDate;

            if (!$delivery->update()) {
                throw new Service_Exception("Failed to update delivery note");
            }

            $newDeliveryDetails = $delivery->toArray();

            // Log changed header fields
            $trackFields = [
                'location_id' => 'Location',
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
                    'activity_type' => 'updated_details',
                    'title' => 'Delivery note updated',
                    'meta' => $updatedDetails,
                ]);
            }


            $itemUpdateLog = $this->saveLineItems($delivery, $lineItems);

            if (!empty($itemUpdateLog)) {
                
                $this->logHistory($dnId, [
                    'activity_type' => 'updated_items',
                    'title' => 'Line items updated',
                    'meta' => $itemUpdateLog,
                ]);
            }

            if ($status === 'dispatched' || $status === 'delivered') {

                $delivery->refreshById($dnId);

                $soLocationId = $soId ? (int) ($salesOrder->location_id ?? 0) : 0;
                $releaseReservedQty = in_array($salesOrderStatus, ['confirmed', 'partially_dispatched', 'partially_delivered']);
                $this->reduceStock($delivery, $releaseReservedQty, $soLocationId);

                if ($soId) {
                    $this->recalculateSoStatus($soId);
                }
            }

            if ($soId) {

                $order = new Service_So_Order(new Service_TenantContext($companyId, $userId));
                $order->logHistory($soId, [
                    'activity_type' => 'dn_updated',
                    'title' => 'Delivery note updated #' . $delivery->dn_number,
                    'meta' => array_merge([
                        'dn_id' => $dnId,
                        'dn_number' => $delivery->dn_number,
                        'dn_status' => $status,
                    ], $soMetaData),
                ]);
            }

            $this->db->commit();

            return ["success" => true, "data" => ["dn_id" => $dnId, "dn_number" => $delivery->dn_number]];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function updateStatus(int $dnId, array $payload): array {

        $delivery = $this->getDeliveryOrFail($dnId);        

        $status = trim($payload['status'] ?? '');
        $notes = trim($payload['notes']  ?? '');

        $allowedTransitions = [
            'draft'      => ['dispatched', 'cancelled'],
            'dispatched' => ['delivered', 'returned', 'lost', 'draft'],
            'delivered'  => ['draft'],
        ];
        $oldStatus = $delivery->status;

        if (!isset($allowedTransitions[$oldStatus]) || !in_array($status, $allowedTransitions[$oldStatus])) {
            throw new Service_Exception("Cannot transition delivery note from '{$oldStatus}' to '{$status}'", 422);
        }

        $this->db->startTransaction();

        try {

            $soId = $delivery->sales_order_id;
            $updateFields = ["status", "updated_at"];

            // Stock actions
            if ($status === 'dispatched' ) {

                // Determine if SO had confirmed status (stock was reserved at SO location)
                $so = new Models_SalesOrder($soId);
                $wasReserved = in_array($so->status, ['confirmed', 'partially_dispatched', 'partially_delivered']);

                $this->reduceStock($delivery, $wasReserved, (int) $so->location_id);

                if( empty($delivery->dispatch_date) ) {
                    $delivery->dispatch_date = date("Y-m-d");
                    $updateFields[] = "dispatch_date";
                }
            }

            if( $status === 'delivered' ) {
                if( empty($delivery->delivery_date) ) {
                    $delivery->delivery_date = date("Y-m-d");
                    $updateFields[] = "delivery_date";
                }
            }


            // ($status === 'cancelled' && in_array($oldStatus, ["dispatched", "delivered"]))
            // Removed this condition from below as current only DN can cancelled and if DN is draft it means Stock is not reduced yet(we only reduce stock in Dispatched or Delivered action)
            // Will add this condition back if allow to cancell DN for Dispatched OR Delivered, it will also require to update allowedTransitions array above
            if ($status === 'returned') {
                
                $this->restoreStock($delivery);

                $delivery->dispatch_date = null;
                $delivery->delivery_date = null;
                $updateFields[] = "dispatch_date";
                $updateFields[] = "delivery_date";
            }

            $reOpenDn = $status === 'draft' && in_array($oldStatus, ['dispatched', 'delivered']);


            // Revert to Open (Dispatched => Draft or Delivered => Draft)
            if ($reOpenDn) {

                $so = new Models_SalesOrder($soId);
                $shouldRestoreReservation = in_array($so->status, ['confirmed', 'partially_dispatched', 'partially_delivered']);
                $this->restoreStock($delivery, $shouldRestoreReservation, (int) ($so->location_id ?? 0));

                $delivery->dispatch_date = null;
                $delivery->delivery_date = null;
                $updateFields[] = "dispatch_date";
                $updateFields[] = "delivery_date";
            }

            $delivery->status = $status;
            if (!$delivery->update($updateFields)) {
                throw new Service_Exception("Failed to update delivery note status");
            }

            // Recalculate SO status after any transition that changes dispatched/delivered totals
            if ($soId && in_array($status, ['dispatched', 'delivered', 'returned', 'lost', 'cancelled', 'draft'])) {
                $this->recalculateSoStatus($soId);
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
                'activity_type' => 'status_changed',
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
                    'activity_type' => 'dn_status_changed',
                    'title' => 'Delivery note #'.$delivery->dn_number.' '.$dnActionTitle,
                    'meta' => [
                        'dn_id' => $dnId,
                        'dn_number' => $delivery->dn_number,
                        'old_status' => $statusLabels[$oldStatus] ?? $oldStatus,
                        'new_status' => $statusLabels[$status] ?? $status,
                    ]
                ]);
            }

            $this->db->commit();

            return ["success" => true, "data" => ["dn_id" => $dnId, "status" => $status, "old_status" => $oldStatus]];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function getDetails(int $dnId): array {

        $dn = $this->getDeliveryOrFail($dnId);
        $companyId = $this->context->companyId;

        // Fetch items enriched with product_name and ordered_qty
        $enrichedItems = $this->db->fetchAll(
            "SELECT
                sdi.*,
                p.name AS product_name,
                soi.ordered_qty
            FROM sales_delivery_items sdi
            LEFT JOIN products p ON p.id = sdi.product_id
            LEFT JOIN sales_order_items soi ON soi.id = sdi.sales_order_item_id
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
        $location = $dn->location_id    ? new Models_Location($dn->location_id)     : null;

        $dnDetails = array_merge(
            [
                'id'            => $dnId,
                'customer_name' => $dn->customer->display_name,
                'so_number'     => $so       ? $so->so_number : null,
                'location_name' => $location ? $location->name : null,
                'items'         => $itemsWithTracking,
            ],
            $dn->toArray()
        );

        return ['dn_details' => $dnDetails];
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
                'activity_type' => $row->activity_type,
                'title' => $row->title,
                'meta' => json_decode($row->meta ?? '[]', true) ?: [],
                'performed_by' => $row->performed_by,
                'date_time' => formatMySqlDate($row->created_at),
            ];
        }

        return $data;
    }
}
?>