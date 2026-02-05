<?php
class Service_Po_Order extends Service_Base {
    
    protected $companyId;

    public function __construct($companyId) {
        $this->companyId = $companyId;
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

    private function validatePayload(array $payload) {

        $vendorId = $payload['vendor_id'] ?? 0; 
        $locationId = $payload['location_id'] ?? 0; 
        $orderDate = $payload['order_date'] ?? ""; 
        $expectedDeliveryDate = $payload['expected_delivery_date'] ?? "";
        $paymentTermId = $payload['payment_term_id'] ?? "";
        $lineItems = $payload['po_items'] ?? [];
        $status = $payload['status'] ?? "";


        // Vendor
        $vendor = new Models_Vendor($vendorId);
        if( $vendor->isEmpty || $vendor->company_id != $this->companyId ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Vendor"), "vendor_id");
        }


        // Location
        $location = new Models_Location($locationId);
        if( $location->isEmpty || $location->company_id != $this->companyId ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Location"), "location_id");
        }


        // Order date
        if (!empty($orderDate) && !strtotime($orderDate)) {
            $this->addError(validationErrMsg("invalid", "Order date"), "order_date");
        }


        // Expected delivery date
        if (!empty($expectedDeliveryDate) && !strtotime($expectedDeliveryDate)) {
            $this->addError(validationErrMsg("invalid", "Expected delivery date"), "expected_delivery_date");
        }


        // Payment term
        if( $paymentTermId ) {
            $paymentTerm = new Models_PaymentTerm($paymentTermId);
            if( $paymentTerm->isEmpty || $paymentTerm->company_id != $this->companyId ) {
                $this->addError(validationErrMsg("invalid", "Payment terms"), "payment_term_id");
            }
        }


        if( empty($status) ) {
            $this->addError(validationErrMsg("required", "Purchase order status"), "status");
        }


        /*
        // Receiving type
        $receivingType = $payload['receiving_type'] ?? 'inventory';
        if (!in_array($receivingType, ['inventory', 'drop_ship'], true)) {
            $this->addError(
                validationErrMsg("invalid", "Receiving type"),
                "receiving_type"
            );
        }

        // Receiving location (only if inventory)
        if ($receivingType === 'inventory') {
            if (empty($payload['receiving_location_id'])) {
                $this->addError(
                    validationErrMsg("missing_or_invalid", "Receiving location"),
                    "receiving_location_id"
                );
            } else {
                $location = new Models_Location($payload['receiving_location_id']);
                if ($location->isEmpty || $location->company_id != $this->companyId) {
                    $this->addError(
                        validationErrMsg("missing_or_invalid", "Receiving location"),
                        "receiving_location_id"
                    );
                }
            }
        }
        */

        // validate line items
        $this->validateItems($lineItems);
    }


    private function validateItems(array $items): void
    {
        if (empty($items) || !is_array($items)) {
            $this->addError(validationErrMsg("one_item_required", "line item"), "items");
            return;
        }

        $hasInvalidQty = false;
        $hasInvalidCost = false;
        $hasMissingProduct = false;

        $productIds = [];
        $index = 0;

        $itemLevelErrors = [];
        foreach ($items as $item) {

            $row = $index + 1;
            $isProductValid = true;
            
            $productId = (int) $item['product_id'] ?? 0;
            $quantity = (int) $item['qty'] ?? 0;
            $unitCost = (float) $item['unit_cost'] ?? 0;
            
            if( !$productId ) {
                $hasMissingProduct = true;
            }
            else 
            {
                $product = new Models_Product($productId);
                if ( $product->isEmpty || $product->company_id != $this->companyId || $product->status != "active" ) {

                    //$this->addError(validationErrMsg("missing_or_invalid", "Product at row {$row}"), "items.{$index}.product_id");
                    //$isProductValid = false;

                    $itemLevelErrors["items.{$index}.invalid_prod"] = validationErrMsg("invalid", "Product at row {$row}");
                    $isProductValid = false;
                }
            }

            if( !isPositiveNumeric($quantity) ) {
                $hasInvalidQty = true;
            }

            if( !isValidPrice($unitCost) ) {
                $hasInvalidCost = true;
            }

            // Duplicate product check
            if ($isProductValid) {
                
                if (in_array($productId, $productIds)) {
                    $itemLevelErrors["items.{$index}.duplicate_prod"] = "Duplicate product detected at row {$row}";
                }
                
                $productIds[] = $productId;
            }


            /*
            if (!empty($item['tax_id'])) {
                $tax = new Models_Tax($item['tax_id']);
                if (
                    $tax->isEmpty ||
                    $tax->company_id != $this->companyId ||
                    !$tax->is_active
                ) {
                    $this->addError(
                        validationErrMsg("missing_or_invalid", "Tax at row {$row}"),
                        "items.{$index}.tax_id"
                    );
                } elseif (!in_array($tax->apply_on, ['purchase', 'both'], true)) {
                    $this->addError(
                        validationErrMsg("not_applicable", "Tax at row {$row}"),
                        "items.{$index}.tax_id"
                    );
                }
            }
            */

            $index++;
        }


        if ($hasMissingProduct) {
            $this->addError(validationErrMsg("required", "Each item must have a product selected"), "items.product_id");
        }

        if ($hasInvalidQty) {
            $this->addError("Quantity must be greater than zero for all items", "items.qty");
        }

        if ($hasInvalidCost) {
            $this->addError("Unit price cannot be negative for any item", "items.unit_cost");
        }

        foreach($itemLevelErrors as $errKey => $errMsg) {
            $this->addError($errMsg, $errKey);
        }
    }


    /**
     * Create, Update or Delete Line Items
     */
    private function saveLineItems(Models_PurchaseOrder $purchaseOrder, array $lineItems) : void {

        if( $purchaseOrder->isEmpty ) {
            throw new Exception("Failed to save line items");
        }

        $savedLineItems = $purchaseOrder->line_items;
        
        $savedItemsById = [];
        foreach($savedLineItems as $savedLineItem) {
            $savedItemsById[$savedLineItem->id] = $savedLineItem; 
        }

        $itemsToCreate = [];
        $itemsToUpdate = [];
        $itemsToDelete = [];

        $existingPayloadIds = [];
        foreach ($lineItems as $item) {

            $itemId = (int) ($item['id'] ?? 0);
            if( $itemId && isset($savedItemsById[$itemId] ) ) {
                $itemsToUpdate[] = $item;
                $existingPayloadIds[] = $itemId;
            } else {
                $itemsToCreate[] = $item;
            }
        }

        foreach($savedItemsById as $savedItemsId => $savedItem) {
            if( !in_array($savedItemsId, $existingPayloadIds) ) {
                $itemsToDelete[] = $savedItem;
            }
        }

        $failedErrorMsg = "Unable to save the purchase order due to an issue with one or more line items";


        // Create && Update
        foreach (array_merge($itemsToCreate, $itemsToUpdate) as $item) {

            $itemId = (int) ($item['id'] ?? 0);
            
            // Tax calculation (optional)
            $taxAmount = 0;
            $taxInfo = null;
            $itemTaxId = $item['tax_id'] ?? 0;

            if ( $itemTaxId ) {

                /*
                $taxService = new Service_Tax($this->companyId);
                $taxResult = $taxService->calculateLine([
                    'qty'         => $item['qty'],
                    'unit_price'  => $item['unit_cost'],
                    'tax_id'      => $item['tax_id'],
                    'context'     => 'purchase'
                ]);

                $taxAmount = $taxResult['tax_amount'];
                $taxInfo   = json_encode($taxResult['tax_info']);
                */
            }

            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);

            $poi = new Models_PurchaseOrderItem($itemId);
            $poi->purchase_order_id = $purchaseOrder->id;
            $poi->product_id = (int) $item['product_id'];
            $poi->description = $item['description'] ?: null;
            $poi->ordered_qty = $qty;
            $poi->unit_price = $unitCost;

            $poi->tax_amount = $taxAmount;
            $poi->tax_info = $taxInfo;

            $poi->line_total = ($qty * $unitCost) + $taxAmount;

            if( $poi->isEmpty ) {
                if (!$poi->create()) {
                    throw new Exception($failedErrorMsg);
                }
            }
            else {
                if (!$poi->update()) {
                    throw new Exception($failedErrorMsg);
                }
            }
        }

        // Delete
         foreach($itemsToDelete as $itemToDelete) {
            $poi = new Models_PurchaseOrderItem($itemToDelete->id);
            $poi->delete();
            if( $poi->getDeletedRows() <= 0 ) {
                throw new Exception($failedErrorMsg);
            }
        }
    }


    /**
     * Retrive add/edit form context data
     */
    public function getFormContext(int $poId) : array {
        
        $companyId = $this->companyId;

        $poDetails = [];
        if( $poId ) {

            $purchaseOrder = $this->getPurchaseOrderOrFail($poId);
            $poDetails = array_merge(['id' => $poId, "line_items" => $purchaseOrder->line_items], $purchaseOrder->toArray());
        }

        $location = new Models_Location();
        $locations = $location->getAll([], ["company_id" => $companyId, "status" => ["active"]]);

        $vendor = new Models_Vendor();
        $vendors = $vendor->getAll([], ["company_id" => $companyId, "status" => ["active"]]);

        $product = new Models_Product();
        $products = $product->getAll([], ["company_id" => $companyId, "status" => "active"]);

        $paymentTerm = new Models_PaymentTerm();
        $paymentTerms = $paymentTerm->getAll([], ["company_id" => $companyId, "status" => "active"]);

        $tax = new Models_Tax();
        $poTaxes = $tax->getAll([], ["company_id" => $companyId, "apply_on" => ["purchase", "both"], "status" => "active"]);

        $data = [
            'po_details' => $poDetails,
            'vendors' => $vendors,
            'locations' => $locations,
            'suggested_po_number' => Service_Sequence::nextPreview($companyId, "purchase_orders"),
            'products' => $products,
            'payment_terms' => $paymentTerms,
            'taxes' => $poTaxes,
        ];

        return $data;
    }


    /**
     * Retrive purchase order details
     */
    public function getDetails(int $poId) : array {

        $purchaseOrder = $this->getPurchaseOrderOrFail($poId);
        
        $poDetails = array_merge(['id' => $poId, "vendor_name" => $purchaseOrder->vendor->display_name, "line_items" => $purchaseOrder->line_items], $purchaseOrder->toArray());                    

        $data = ['po_details' => $poDetails];

        return $data;
    }   



    /**
     * Create PO
     */
    public function create(array $payload) {

        // Validate incoming data
        $this->validatePayload($payload);

        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        
        global $db;


        // Begin transaction
        $db->startTransaction();

        try {

            // generate PO Number
            $poNumber = Service_Sequence::nextCommit($this->companyId, "purchase_orders");

            // create purchase order
            $poStatus = $payload["status"];
            $poConfirmationDate = $payload["confirmation_date"] ?? "";

            $purchaseOrder = new Models_PurchaseOrder();
            $purchaseOrder->fillFromArray($payload);
            $purchaseOrder->company_id = $this->companyId;
            $purchaseOrder->po_number = $poNumber;
            if( $poStatus === "confirmed" && empty($poConfirmationDate) ) {
                $purchaseOrder->confirmation_date = date("Y-m-d");
            }

            $poId = $purchaseOrder->create();
            if( !$poId ) {
                throw new Exception("Failed to create purchase order");
            }

            // refresh object with newly created PO ID
            $purchaseOrder->refreshById($poId);

            // Purchase order line items
            $lineItems = $payload['po_items'] ?? [];
            $this->saveLineItems($purchaseOrder, $lineItems);
            
            
            // purchase order history
            $poHistory = new Models_PurchaseOrderHistory();
            $poHistory->purchase_order_id = $poId;
            $poHistory->event_type = 'created';
            $poHistory->notes = 'Purchase order created as draft';
            if( !$poHistory->create() ) {
                throw new Exception("Purchase order creation failed: order history record could not be created");
            }

            // Commit
            $db->commit();

            return [
                "success" => true,
                "data" => [
                    "po_id" => $poId,
                    "po_number" => $poNumber
                ],
            ];

        } catch (Exception $e) {

            $db->rollBack();
            throw $e; // SYSTEM ERROR → Controller will return 500
        }
    }


    /**
     * Update PO
     */
    public function update(int $poId, array $payload)
    {
        global $db;

        $purchaseOrder = $this->getPurchaseOrderOrFail($poId);

        $editAllowedStatuses = ["draft"];
        if( !in_array($purchaseOrder->status, $editAllowedStatuses) ) {
            throw new Service_Exception("This purchase order can no longer be edited because it has progressed beyond the draft stage", 422);
        }

        // Validate payload
        $this->validatePayload($payload);


        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        $db->startTransaction();

        try {

            // update purchase order
            $poStatus = $payload["status"];
            $poConfirmationDate = $payload["confirmation_date"] ?? "";

            $purchaseOrder->fillFromArray($payload, ['id', 'po_number', 'company_id', 'created_at', 'created_by']);
            
            if( $poStatus === "confirmed" && empty($poConfirmationDate) ) {
                $purchaseOrder->confirmation_date = date("Y-m-d");
            }
            
            if( !$purchaseOrder->update() ) {
                throw new Exception("Failed to update purchase order");
            }

            
            // Purchase order line items
            $lineItems = $payload['po_items'] ?? [];
            $this->saveLineItems($purchaseOrder, $lineItems);
            

            // purchase order history
            $poHistory = new Models_PurchaseOrderHistory();
            $poHistory->purchase_order_id = $poId;
            $poHistory->event_type = 'updated';
            $poHistory->notes = 'Purchase order updated';
            if( !$poHistory->create() ) {
                throw new Exception("Purchase order updation failed: order history record could not be created");
            }

            $db->commit();

            return [
                "success" => true,
                "data" => [
                    "po_id" => $poId,
                    "po_number" => $purchaseOrder->po_number
                ]
            ];

        } catch (Exception $e) {
            
            $db->rollBack();
            throw $e;
        }
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

    public function updateStatus(int $poId, array $payload) 
    {
        $purchaseOrder = $this->getPurchaseOrderOrFail($poId);
        
        // Validate payload
        $this->validateUpdateStatusPayload($payload);

        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }


        global $db;

        $db->startTransaction();

        try {

            $status = $payload["status"] ?? "";
            $notes = $payload["notes"] ?? "";
            $oldStatus = $purchaseOrder->status;

            if( $status === "confirmed" ) {

                if ($oldStatus !== 'draft') {
                    throw new Exception("Only draft purchase orders can be confirmed");
                }

                $purchaseOrder->confirmation_date = date('Y-m-d');
            }

            $purchaseOrder->status = $status;
            
            if (!$purchaseOrder->update()) {
                throw new Exception("Failed to update purchase order status");
            }

            // purchase order history
            $poHistory = new Models_PurchaseOrderHistory();
            $poHistory->purchase_order_id = $poId;
            $poHistory->event_type = 'confirmed';
            $poHistory->changed_field = 'status';
            $poHistory->old_value = $oldStatus;
            $poHistory->new_value = $status;
            $poHistory->notes = $notes;
            if( !$poHistory->create() ) {
                throw new Exception("Purchase order updation failed: order history record could not be created");
            }

            $db->commit();

            return [
                "success" => true,
                "data" => [
                    "po_id" => $poId,
                    "status" => $status,
                    "old_status" => $oldStatus
                ]
            ];

        } catch(Exception $e) {
            
            $db->rollBack();
            throw $e;
        }

    }

}