<?php

class Service_Po_Grn extends Service_Base
{
    protected int $companyId;
    protected int $userId;

    public function __construct(int $companyId)
    {
        $this->companyId = $companyId;
        $this->userId    = auth()->user()->id;
    }


    private function getPurchaseOrderOrFail(int $poId) : Models_PurchaseOrder {

        // validate purchase order and permissions
        $purchaseOrder = new Models_PurchaseOrder($poId);
        if( $purchaseOrder->isEmpty ) {
            throw new Service_Exception("The requested purchase order was not found", 404);
        }

        if( $purchaseOrder->company_id != $this->companyId ) {
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

        $receiveDate = $payload['posted_date'] ?? ""; 
        $receiveItems = $payload['receive_items'] ?? ""; 

        // Order date
        if (empty($receiveDate) || !strtotime($receiveDate)) {
            $this->addError(validationErrMsg("missing_or_invalid", "Receive date"), "posted_date");
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

                $poItemIds[] = $poItemId;
            }

            foreach($itemLevelErrors as $errKey => $errMsg) {
                $this->addError($errMsg, $errKey);
            }
        }
    }


    
    public function getCreateFormContext(int $poId) {

        $po = $this->getPurchaseOrderOrFail($poId);
        
        $this->guardPurchaseOrderReceivable($po);

        $receivableItems = $po->getReceivableItems();
        if (empty($receivableItems)) {
            throw new Service_Exception("All items in this purchase order have already been fully received", 422);
        }

        $poId = $po->id;
        $poNumber = $po->po_number;
        $vendorId = $po->vendor_id;
        $vendorName = $po->vendor->display_name;
        $grnNumberPreview = Service_Sequence::nextPreview($this->companyId, "purchase_order_grns");

        return [
            'receivable_items' => $receivableItems,
            'po_id' => $poId,
            'po_number' => $poNumber,
            'vendor_id' => $vendorId,
            'vendor_name' => $vendorName,
            'grn_number_preview' => $grnNumberPreview,
            'receipt' => [],
        ];
    }



    /**
     * Create GRN (Purchase Order Receive - Draft)
     */
    public function create(int $poId, array $payload): array
    {        
        $po = $this->getPurchaseOrderOrFail($poId);
            
        $this->guardPurchaseOrderReceivable($po);

        $this->validateReceivePayload($payload, $po);

        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }


        global $db;

        $db->startTransaction();

        try {
        
            $grn = new Models_PurchaseOrderGrn();
            $grn->purchase_order_id = $poId;
            $grn->grn_number = Service_Sequence::nextCommit($this->companyId, 'purchase_order_grns');
            $grn->status = 'draft';
            $grn->location_id = $po->receiving_location_id ?? $po->location_id;
            $grn->notes = $payload['notes'] ?? null;

            $grnId = $grn->create();
            if (!$grnId) {
                throw new Service_Exception("Failed to create purchase receive", 500);
            }

            
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

            
            // GRN History
            $history = new Models_PurchaseOrderGrnHistory();
            $history->purchase_order_grn_id = $grnId;
            $history->event_type = 'created';
            $history->notes = 'Purchase receive created';
            if( !$history->create() ) {
                throw new Service_Exception("Purchase receive creation failed: history record could not be created", 500);
            }

            $db->commit();

            return [
                "success" => true,
                "data" => [
                    "receipt_id"     => $grnId,
                    "grn_number" => $grn->grn_number,
                    "status"     => $grn->status
                ]
            ];

        } catch (Exception $e) {
            $db->rollBack();
            throw $e;
        }
    }
}