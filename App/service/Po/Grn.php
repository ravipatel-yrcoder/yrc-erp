<?php

class Service_Po_Grn extends Service_Base
{
    private const ALLOWED_STATUSES = ['draft', 'in_transit', 'received'];

    private function getGrnOrFail(int $grnId): Models_PurchaseOrderGrn {
        
        // validate purchase order receive(Receipt/Grn) and permissions
        $poGrn = new Models_PurchaseOrderGrn($grnId);
        if( $poGrn->isEmpty ) {
            throw new Service_Exception("The requested purchase receipt was not found", 404);
        }

        if( $poGrn->company_id != $this->context->companyId ) {
            throw new Service_Exception("You do not have permission to access this purchase receipt", 403);
        }

        return $poGrn;
    }


    private function getPurchaseOrderOrFail(int $poId) : Models_PurchaseOrder {

        // validate purchase order and permissions
        $purchaseOrder = new Models_PurchaseOrder($poId);
        if( $purchaseOrder->isEmpty ) {
            throw new Service_Exception("The requested purchase order was not found", 404);
        }

        if( $purchaseOrder->company_id != $this->context->companyId ) {
            throw new Service_Exception("You do not have permission to access this purchase order", 403);
        }

        return $purchaseOrder;
    }


    private function guardPurchaseOrderReceivable(Models_PurchaseOrder $po) {

        $allowedStatuses = ['confirmed', 'partially_received'];
        if (!in_array($po->status, $allowedStatuses, true)) {
            throw new Service_Exception('This purchase order cannot be received in its current status', 422);
        }
    }


    private function validateReceivePayload(array $payload, Models_PurchaseOrder $po) {

        $status = $payload['status'] ?? ""; 
        $receiveDate = $payload['received_date'] ?? ""; 
        $receiveItems = $payload['receive_items'] ?? ""; 

        
        if( !in_array($status, self::ALLOWED_STATUSES) ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Receive status"), "status");
        }

        if (in_array($status, ['received']) && (empty($receiveDate) || !strtotime($receiveDate)) ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Receive date"), "received_date");
        }
        
        if (empty($receiveItems) || !is_array($receiveItems)) {
            $this->addError(validationErrMsg("one_item_required", "receive item"), "receive_items");            
        }
        else
        {
            // Purchase order receivable items to validate receive items in payload
            $poReceivableItemsByPoItemId = [];
            foreach($po->getReceivableItems() as $item) {
                $poReceivableItemsByPoItemId[$item['po_item_id']] = $item;
            }

            $itemLevelErrors = [];
            $poItemIds = [];
            $index = 0;            
            foreach($receiveItems as $receiveItem)
            {
                $row = $index + 1;
                $poItemId = $receiveItem["po_item_id"] ?? 0;
                $receiveQty = $receiveItem["receive_qty"] ?? 0;

                $poReceivableItem = $poReceivableItemsByPoItemId[$poItemId] ?? [];
                $poItemProdTrackingMethod = $poReceivableItem["stock_tracking_method"] ?? "none";

                if( empty($poReceivableItem) )  {
                    $itemLevelErrors["receive_items.{$index}.invalid_po_item"] = validationErrMsg("invalid", "Item at row {$row}");
                    continue;
                }                

                if( !isPositiveNumeric($receiveQty) ) {
                    $itemLevelErrors["receive_items.{$index}.invalid_receive_qty"] = validationErrMsg("invalid", "Receive quantity at row {$row}");
                }
                else {
                    $remainingQty = (float) $poReceivableItem["remaining_qty"];
                    if( $receiveQty > $remainingQty ) {
                        $itemLevelErrors["receive_items.{$index}.invalid_receive_qty"] = validationErrMsg("invalid", "Receive quantity at row {$row}");
                    }
                }

                if (in_array($poItemId, $poItemIds)) {
                    $itemLevelErrors["receive_items.{$index}.duplicate_po_item"] = "Duplicate item detected at row {$row}";
                }

                // lot_or_serial_numbers are required when posting
                if( $status === "received" && in_array($poItemProdTrackingMethod, ["serial", "lot"]) ) {
                    $serialOrLotNumbers = $receiveItem["serial_or_lot_numbers"] ?? [];
                    if( empty($serialOrLotNumbers) ) {
                        $itemLevelErrors["receive_items.{$index}.missing_lot_serial"] = "Missing {$poItemProdTrackingMethod} numbers at row {$row}";
                    }
                    else {
                        if( count($serialOrLotNumbers) != $receiveQty ) {
                            $itemLevelErrors["receive_items.{$index}.missing_lot_serial"] = ucfirst($poItemProdTrackingMethod)." numbers count must match with receive qty at row {$row}";
                        }
                    }
                }

                $poItemIds[] = $poItemId;
            }

            foreach($itemLevelErrors as $errKey => $errMsg) {
                $this->addError($errMsg, $errKey);
            }
        }

    }


    public function logHistory($grnId, $payload) {

        $activityType = $payload["activity_type"];
        $title = $payload["title"];
        $description = $payload["description"] ?? null;
        $refType = $payload["reference_type"] ?? null;
        $refId = $payload["reference_id"] ?? null;
        $meta = empty($payload["meta"]) ? null : json_encode($payload["meta"], JSON_UNESCAPED_UNICODE);
        
        $history = new Models_PurchaseOrderGrnHistory();
        $history->company_id = $this->context->companyId;
        $history->purchase_order_grn_id = $grnId;
        $history->activity_type = $activityType;
        $history->title = $title;
        $history->description = $description;
        $history->reference_type = $refType;
        $history->reference_id = $refId;
        $history->meta = $meta;
        $history->created_by = $this->context->userId;

        if( !$history->create() ) {
            throw new Service_Exception("Failed to log receive history");
        }
    }


    public function getCreateFormContext(int $poId) {

        $po = $this->getPurchaseOrderOrFail($poId);

        $this->guardPurchaseOrderReceivable($po);

        $receivableItems = $po->getReceivableItems();
        if (empty($receivableItems)) {
            throw new Service_Exception("All items in this purchase order have already been fully received", 422);
        }

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

        $poId = $po->id;
        $poNumber = $po->po_number;
        $vendorId = $po->vendor_id;
        $vendorName = $po->vendor->display_name;
        $grnNumberPreview = $seqService->nextPreview("purchase_order_grns");

        return [
            'receivable_items' => $receivableItems,
            'po_id' => $poId,
            'po_number' => $poNumber,
            'vendor_id' => $vendorId,
            'vendor_name' => $vendorName,
            'receipt_number_preview' => $grnNumberPreview,
            'receipt' => [],
        ];
    }

    public function getEditFormContext(int $grnId) {

        $grn = $this->getGrnOrFail($grnId);
        
        // guard grn edit(Only draft and in_transit is allowed)
        if( !in_array($grn->status, ["draft", "in_transit"], true) ) {

            $msg = "This receipt can not be edited in current status";
            if( $grn->status === "received" ) {
                $msg = "This receipt already marked as received, can not edit";
            }
            else if( $grn->status === "cancelled" ) {
                $msg = "Cancelled receipt can not be edited";
            }
            throw new Service_Exception($msg, 422);
        }

        $poId = $grn->purchase_order_id;
        $poNumber = $grn->purchase_order->po_number;
        $vendorId = $grn->purchase_order->vendor_id;
        $vendorName = $grn->purchase_order->vendor->display_name;
        $grnNumberPreview = "";

        $poReceivableItems = $grn->purchase_order->getReceivableItems(true);
        $grnLineItems = $grn->line_items;

        $poReceivableItemsById = [];
        foreach ($poReceivableItems as $poReceivableItem) {
            $poReceivableItemsById[$poReceivableItem['po_item_id']] = $poReceivableItem;
        }


        $finalReceivableItems = [];
        foreach($grnLineItems as $grnLineItem) {
            $poItemId = $grnLineItem->purchase_order_item_id;
            if( isset($poReceivableItemsById[$poItemId]) ) {
                $item = $poReceivableItemsById[$poItemId];
                $item["received_qty"] = 0;
                $item["in_transit_qty"] = $grnLineItem->received_qty;
                $item["remaining_qty"] = $grnLineItem->received_qty;
                $finalReceivableItems[] = $item;
            }
        }
        
        return [
            'receivable_items' => $finalReceivableItems,
            'po_id' => $poId,
            'po_number' => $poNumber,
            'vendor_id' => $vendorId,
            'vendor_name' => $vendorName,
            'receipt_number_preview' => $grnNumberPreview,
            'receipt_id' => $grnId,
            'receipt_number' => $grn->grn_number,            
        ];
    }


    /**
     * Create GRN
     */
    public function create(int $poId, array $payload): array
    {

        // validate and get purchase order
        $po = $this->getPurchaseOrderOrFail($poId);

        
        // Validate purchase order receive state
        $this->guardPurchaseOrderReceivable($po);


        // Validate receive items payload
        $this->validateReceivePayload($payload, $po);        


        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        
        $this->db->startTransaction();

        try {

            $companyId = $this->context->companyId;
            $userId = $this->context->userId;

            $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

            $grnStatus = $payload['status'] ?? 'draft';
        
            // Create PO GRN
            $grnNumber = $seqService->nextCommit('purchase_order_grns');
            $grn = new Models_PurchaseOrderGrn();
            $grn->company_id = $companyId;
            $grn->purchase_order_id = $poId;
            $grn->grn_number = $grnNumber;
            $grn->status = $grnStatus;
            $grn->location_id = $po->receiving_location_id ?? $po->location_id;
            $grn->notes = $payload['notes'] ?? null;
            $grn->created_by = $userId;

            $grnId = $grn->create();
            if (!$grnId) {
                throw new Service_Exception("Failed to create purchase receive", 500);
            }
            
            
            // Create PO GRN Items
            $receiveItems = $payload['receive_items'] ?? [];
            foreach ($receiveItems as $item) {

                $poItemId = $item['po_item_id'];
                $poItem = new Models_PurchaseOrderItem($poItemId);

                $grnItem = new Models_PurchaseOrderGrnItem();
                $grnItem->purchase_order_grn_id = $grnId;
                $grnItem->purchase_order_item_id = $item['po_item_id'];
                $grnItem->product_id = $poItem->product_id;
                $grnItem->ordered_qty = $poItem->ordered_qty;
                $grnItem->received_qty = $item['receive_qty'];

                if (!$grnItem->create()) {
                    throw new Service_Exception("Purchase receive creation failed: receive item record could not be created", 500);
                }
            }

            // PO GRN History
            $logPayload = [
                'activity_type' => 'created',
                'title' => 'Receipt created #'.$grnNumber,
                'meta' => [
                    'status' => $grnStatus,
                    'items_count' => count($receiveItems),
                ],
            ];
            $this->logHistory($grnId, $logPayload);

            /*
            $history = new Models_PurchaseOrderGrnHistory();
            $history->company_id = $companyId;
            $history->purchase_order_grn_id = $grnId;
            $history->event_type = "created";            
            $history->notes = "Purchase receive created";
            $history->created_by = $userId;

            if( !$history->create() ) {
                throw new Service_Exception("Purchase receive update failed: history record could not be created", 500);
            }
            */

            if( $grnStatus === "received" ) {
                $this->markReceived($grn, $receiveItems);
            }

            $this->db->commit();

            return [
                "success" => true,
                "data" => [
                    "receipt_id" => $grnId,
                    "po_id" => $poId,
                    "grn_number" => $grn->grn_number,
                    "status" => $grn->status
                ]
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    /**
     * Update GRN (Draft or In transit only)
     */
    public function update(int $grnId, array $payload) {
    }


    // mark as received
    private function markReceived(Models_PurchaseOrderGrn $poGrn, $grnItems) {
        
        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        // Guard state
        if ($poGrn->status !== 'received') {
            throw new Service_Exception("This receive is not posted, can't mark as received", 422);
        }

        if (!empty($poGrn->received_date)) {
            throw new Service_Exception("This is already marked as received", 422);
        }

        if( empty($grnItems) ) {
            throw new Service_Exception("No items found to receive", 422);
        }

        $receivedAt = date('Y-m-d H:i:s');
        $invService = new Service_Inv_Movement(new Service_TenantContext($companyId, $userId));

        
        // Process each GRN item
        $grnItemsForLog = [];
        $receivedQuantities = 0;
        foreach ($grnItems as $grnItem) {

            $poItemId = $grnItem['po_item_id'];
            $receiveQty = (float) $grnItem['receive_qty'];
            
            $poItem = new Models_PurchaseOrderItem($poItemId);
            $productId = $poItem->product_id;
            
            $product = new Models_Product($productId);
            


            // Update inventory if product inventory tracking is enabled
            $prodStockTrackMethods = strtolower($product->stock_tracking_method);
            if( $prodStockTrackMethods && $prodStockTrackMethods !== "none" ) {

                // Inventory movement
                $recordResult = $invService->record([
                    'movement_type' => 'purchase_receipt',
                    'location_id' => $poGrn->location_id,
                    'product_id' => $productId,
                    'quantity' => $receiveQty,
                    'serial_or_lot_numbers' => $grnItem['serial_or_lot_numbers'] ?? [],
                    'reference_type'=> 'po_grn',
                    'reference_id' => $poGrn->id,
                    'notes' => 'Purchase received',                
                ]);

                if( $recordResult["success"] !== true ) {
                    throw new Service_Exception("Failed to receive purchase order due to an inventory update failure", 500);
                }
            }
            

            // Update PO item received qty(Update using query to prevent race condition)
            try {
                $sql = "UPDATE purchase_order_items SET received_qty = received_qty + ? WHERE id = ?";
                $updatedReceivedQty = $this->db->query($sql, [$receiveQty, $poItemId]);
                if( $updatedReceivedQty === false ) {
                    throw new Service_Exception("Failed to update purchase order item received quantity", 500);
                }
            } catch(Exception $e) {
                throw new Service_Exception("Failed to update purchase order item received quantity", 500);
            }            

            $productUom = new Models_ProductUom($poItem->product_uom_id);
            $grnItemsForLog[] = [
                'prod_id' => $productId,
                'prod_name' => $product->name,
                'received_qty' => formatQty($receiveQty ),
                'uom' => $productUom->base_uom->code,                
            ];

            $receivedQuantities += $receiveQty;
        }

        // Update purchase order status
        $po = new Models_PurchaseOrder($poGrn->purchase_order_id);

        $allReceived = true;
        foreach ($po->line_items as $item) {
            if ((float) $item->received_qty < (float) $item->ordered_qty) {
                $allReceived = false;
                break;
            }
        }

        $poOldStatus = $po->status;
        $po->status = $allReceived ? 'received' : 'partially_received';
        if (!$po->update()) {
            throw new Service_Exception("Failed to update purchase order status", 500);
        }

        // Finalize GRN
        $poGrn->received_date = $receivedAt;
        $poGrn->received_by = $userId;
        if (!$poGrn->update()) {
            throw new Service_Exception("Failed to update purchase receive", 500);
        }

        // GRN history
        $logPayload = [
            'activity_type' => 'received',
            'title' => 'Mark received',
            'meta' => [
                'po_status' => $po->status,
                'items_count' => count($grnItems),
                'quantities' => $receivedQuantities,
                'items' => $grnItemsForLog,
            ],
        ];
        $this->logHistory($poGrn->id, $logPayload);

        
        // Purchase Order History
        $logTitle = $po->status === "received" ? "Purchase order fully received" : "Items received against purchase order";
        //$poService = new Service_Po_Order($this->context->companyId);
        $poService = new Service_Po_Order(new Service_TenantContext($companyId, $userId));
        $poService->logHistory($poGrn->purchase_order_id, [
            'activity_type' => 'received',
            'title' => $logTitle,
            'reference_type' => 'po_grn',
            'reference_id' => $poGrn->id,
            'meta' => [
                'receipt_id' => $poGrn->id,
                'receipt_number' => $poGrn->grn_number,
                'old_status' => $poOldStatus,
                'new_status' => $po->status,
                'items_count' => count($grnItems),
                'quantities' => $receivedQuantities,
            ],
        ]);
    }


    /**
     * Retrive receipt details
     */
    public function getDetails(int $grnId) : array {

        $poGrn = $this->getGrnOrFail($grnId);

        $mapping = ['grn_number' => 'receipt_number', 'received_date' => 'received_date', 'received_by' => 'received_by'];
        $grnDetails = $poGrn->toArray();
        $finalDetails = [];
        foreach($grnDetails as $key => $val) {
            $finalKey = $mapping[$key] ?? $key;
            $finalDetails[$finalKey] = $val;
        }    

        $receiptDetails = array_merge(['id' => $grnId, "po_number" => $poGrn->purchase_order->po_number, "vendor_name" => $poGrn->purchase_order->vendor->display_name, "line_items" => $poGrn->line_items], $finalDetails);

        $data = ['receipt_details' => $receiptDetails];

        return $data;
    }   



    /**
     * Update Status
     */
    private function validateUpdateStatusPayload(array $payload) {

        $status = $payload["status"] ?? "";
        if( empty($status) ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Status"), "status");
        }
    }

    public function updateStatus(int $grnId, array $payload) 
    {
        $grn = $this->getGrnOrFail($grnId);
        
        // Validate payload
        $this->validateUpdateStatusPayload($payload);

        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        $this->db->startTransaction();

        try {

            $status = $payload["status"] ?? "";
            $notes = $payload["notes"] ?? "";
            
            // Need to implement logic in front-end to generate the Serial or Lot Numbers for each receving items when
            // try to marked received
            $serialOrLotNumbersByItem = $payload["serial_or_lot_numbers"] ?? [];
            
            $oldStatus = $grn->status;

            if( $status === "in_transit" ) {
                if ($oldStatus == 'received') {
                    throw new Service_Exception("This is already marked as received, can not update to in transit");
                }

                $grn->in_transit_date = date("Y-m-d H:i:s");
            }

            if( $status === "received" && $oldStatus == 'received' ) {
                throw new Service_Exception("This is already marked as received");
            }

            // Update GRN Status, then conditional run execute logic to update relevant records
            $grn->status = $status;
            if (!$grn->update()) {
                throw new Service_Exception("Failed to update receive status");
            }

            if( $status === "received" ) {
                
                $lineItems = $grn->line_items;
                $receiveItems = [];
                foreach($lineItems as $lineItem) {
                    
                    $poItemId = $lineItem->purchase_order_item_id;

                    $serialOrLotNumbers = $serialOrLotNumbersByItem[$poItemId] ?? [];
                    $receiveItems[] = [
                        'po_item_id' => $lineItem->purchase_order_item_id,
                        'receive_qty' => $lineItem->received_qty,
                        'serial_or_lot_numbers' => $serialOrLotNumbers,
                    ];
                }

                $this->markReceived($grn, $receiveItems);

            } else {

                // GRN update status log
                $logPayload = [
                    'activity_type' => "status_changed",
                    'title' => 'Status changed',
                    'meta' => [
                        'old_status' => $oldStatus,
                        'new_status' => $status,
                        'notes' => $notes,
                    ]
                ];
                $this->logHistory($grnId, $logPayload);
            }

            $this->db->commit();

            return [
                "success" => true,
                "data" => [
                    "receipt_id" => $grnId,
                    "status" => $status,
                    "old_status" => $oldStatus
                ]
            ];

        } catch(Exception $e) {
            
            $this->db->rollBack();
            throw $e;
        }

    }


    public function getHistory(int $grnId) {
        
        // guard
        $this->getGrnOrFail($grnId);

        $sql = "SELECT a.*, b.name AS performed_by FROM purchase_order_grn_history AS a
                LEFT JOIN users AS b ON b.id=a.created_by
                WHERE
                a.company_id=? AND
                a.purchase_order_grn_id=?
                ORDER BY a.id DESC";
        $results = $this->db->fetchAll($sql, [$this->context->companyId, $grnId]);

        $formattedData = [];
        foreach($results as $row)
        {
            $meta = json_decode($row->meta ?? '[]', true) ?: [];
            $formattedData[] = [
                'activity_type' => $row->activity_type,
                'title' => $row->title,
                'description' => $row->description,
                'reference_type' => $row->reference_type,
                'reference_id' => $row->reference_id,
                'meta' => $meta,
                'performed_by' => $row->performed_by,
                'date_time' => formatMySqlDate($row->created_at),
            ];
        }
        
        return $formattedData;
    }

}