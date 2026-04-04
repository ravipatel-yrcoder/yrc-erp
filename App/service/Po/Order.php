<?php
class Service_Po_Order extends Service_Base {
    
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
        if( $vendor->isEmpty || $vendor->company_id != $this->context->companyId ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Vendor"), "vendor_id");
        }


        // Location
        $location = new Models_Location($locationId);
        if( $location->isEmpty || $location->company_id != $this->context->companyId ) {
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
            if( $paymentTerm->isEmpty || $paymentTerm->company_id != $this->context->companyId ) {
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
                if ($location->isEmpty || $location->company_id != $this->context->companyId) {
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
        $hasInvalidUom = false;
        $hasInvalidCost = false;
        $hasMissingProduct = false;

        $productIds = [];
        $index = 0;

        $itemLevelErrors = [];
        foreach ($items as $item) {

            $row = $index + 1;
            $isProductValid = true;
            
            $productId = (int) ($item['product_id'] ?? 0);
            $quantity = (int) ($item['qty'] ?? 0);
            $uomId = (int) ($item['uom_id'] ?? 0);
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $taxes = $item['tax'] ?? [];
            
            if( !$productId ) {
                $hasMissingProduct = true;
            }
            else 
            {
                $product = new Models_Product($productId);
                if ( $product->isEmpty || $product->company_id != $this->context->companyId || $product->status != "active" ) {

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

            $productUom = new Models_ProductUom($uomId);
            if( !(!$productUom->isEmpty && $productUom->product_id == $productId && $productUom->company_id == $this->context->companyId) ) {
                $hasInvalidUom = true;
            }

            // Duplicate product check
            if ($isProductValid) {
                
                if (in_array($productId, $productIds)) {
                    $itemLevelErrors["items.{$index}.duplicate_prod"] = "Duplicate product detected at row {$row}";
                }
                
                $productIds[] = $productId;
            }

            $hasValidTaxes = true;
            foreach($taxes as $taxId) {
                $tax = new Models_Tax($taxId);
                if( !(!$tax->isEmpty && $tax->company_id == $this->context->companyId && $tax->status == "active" && in_array($tax->apply_on, ["purchase", "both"])) ) {
                    $hasValidTaxes = false;
                }
            }

            if( $hasValidTaxes === false ) {
                $itemLevelErrors["items.{$index}.invalid_taxes"] = "One or more tax is invalid at row {$row}";
            }

            /*
            if (!empty($item['tax_id'])) {
                $tax = new Models_Tax($item['tax_id']);
                if (
                    $tax->isEmpty ||
                    $tax->company_id != $this->context->companyId ||
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
        } else {
            if( $hasInvalidUom ) {
                $this->addError(validationErrMsg("required", "Each item must have a UOM"), "items.uom_id");
            }
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


    private function getLineItemsDiff(array $existingItems, array $incomingItems) {

        $existingItemsByProdId = [];
        foreach($existingItems as $existingItem) {
            $existingItemsByProdId[$existingItem->product_id] = $existingItem; 
        }

        $itemsToCreate = [];
        $itemsToUpdate = [];
        $itemsToDelete = [];
        
        $existingPayloadProdIds = [];
        foreach ($incomingItems as $item) {

            //$itemId = (int) ($item['id'] ?? 0);
            $productId = (int) ($item['product_id'] ?? 0);

            if( $productId && isset($existingItemsByProdId[$productId]) ) {
                $item["id"] = $existingItemsByProdId[$productId]->id;
                $itemsToUpdate[] = $item;
                $existingPayloadProdIds[] = $productId;
            } else {
                $itemsToCreate[] = $item;
            }
        }

        foreach($existingItemsByProdId as $existingItemProdId => $existingItem) {
            if( !in_array($existingItemProdId, $existingPayloadProdIds) ) {
                $itemsToDelete[] = $existingItem;
            }
        }

        return [$itemsToCreate, $itemsToUpdate, $itemsToDelete];
    }


    /**
     * Create, Update or Delete Line Items
     */
    private function saveLineItems(Models_PurchaseOrder $purchaseOrder, array $lineItems) : array {

        $updateLog = [];    

        if( $purchaseOrder->isEmpty ) {
            throw new Service_Exception("Failed to save line items");
        }

        $savedLineItems = $purchaseOrder->line_items;

        [$itemsToCreate, $itemsToUpdate, $itemsToDelete] = $this->getLineItemsDiff($savedLineItems, $lineItems);
        
        $failedErrorMsg = "Unable to save the purchase order due to an issue with one or more line items";
        
        // Create && Update
        foreach (array_merge($itemsToCreate, $itemsToUpdate) as $item) {

            $itemId = (int) ($item['id'] ?? 0);
            $productId = (int) $item['product_id'];
            $uomId = (int) $item['uom_id'];
            $description = $item['description'] ?: null;            
            $unitCost = (float) ($item['unit_cost'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            $subTotal = $qty * $unitCost;            
            
            
            // Tax calculation (optional)
            $taxAmount = 0;
            $taxInfo = null;
            $taxes = $item['tax'] ?? [];

            if ( $taxes ) {

                $totalTaxPercentage = 0;
                $totalFixedTax = 0;
                $taxInfo = [];
                foreach($taxes as $taxId) {
                    $tax = new Models_Tax($taxId);
                    if( $tax->tax_type == "percentage" ) {
                        $totalTaxPercentage += (float) $tax->rate;
                    }
                    else if( $tax->tax_type == "fixed" ) {
                        $totalFixedTax += (float) $tax->rate;
                    }

                    $taxInfo[] = [
                        'id' => $taxId,
                        'name' => $tax->name,
                        'code' => $tax->code,
                        'type' => $tax->tax_type,
                        'rate' => $tax->rate,
                        'description' => $tax->description,
                    ];
                }

                if( $totalTaxPercentage ) {
                    $taxAmount = $subTotal * ($totalTaxPercentage / 100);
                }             
                
                $taxAmount += $totalFixedTax;                
                $taxInfo   = json_encode($taxInfo, JSON_UNESCAPED_UNICODE);                
            }


            $product = new Models_Product($productId);
            $productUom = new Models_ProductUom($uomId);
            $poi = new Models_PurchaseOrderItem($itemId);
            $oldPOIDetails = $poi->toArray();
            $oldTaxesInfo = json_decode($oldPOIDetails["tax_info"] ?? '[]', true) ?: [];
            $oldTaxes = [];
            foreach($oldTaxesInfo as $oldTaxRow) {
                $oldTaxes[] = $oldTaxRow["id"];
            }

            $poi->purchase_order_id = $purchaseOrder->id;
            $poi->product_id = $productId;
            $poi->product_uom_id = $productUom->id;
            $poi->conversion_factor_snapshot = $productUom->conversion_factor;
            $poi->uom_code = $productUom->base_uom->code;
            $poi->description = $description;
            $poi->ordered_qty = $qty;
            $poi->unit_price = $unitCost;

            $poi->tax_amount = $taxAmount;
            $poi->tax_info = $taxInfo;

            $lineTotal = ($qty * $unitCost) + $taxAmount;

            $poi->line_total = $lineTotal;

            if( $poi->isEmpty ) {

                $poi->created_by = $this->context->userId;
                if (!$poi->create()) {
                    throw new Service_Exception($failedErrorMsg);
                }

                $updateLog[] = [
                    'event' => 'created',
                    'prod_id' => $productId,
                    'prod_name' => $product->name,
                    'old_qty' => 0,
                    'old_uom' => '',
                    'new_qty' => formatQty($qty),
                    'new_uom' => $productUom->base_uom->code,
                    'old_unit_cost' => 0,
                    'new_unit_cost' => formatCurrency($unitCost)
                ];
            }
            else {

                // Only update po item if something is changed
                if( 
                    $oldPOIDetails["product_id"] != $productId || 
                    $oldPOIDetails["description"] != $description || 
                    $oldPOIDetails["ordered_qty"] != $qty || 
                    array_diff($oldTaxes, $taxes) || 
                    $oldPOIDetails["line_total"] != $lineTotal ) {
                    if (!$poi->update()) {
                        throw new Service_Exception($failedErrorMsg);
                    }

                    $oldProductUomId = $oldPOIDetails["product_uom_id"];
                    $oldProductUom = new Models_ProductUom($oldProductUomId);

                    $updateLog[] = [
                        'event' => 'updated',
                        'prod_id' => $productId,
                        'prod_name' => $product->name,
                        'old_qty' => formatQty($oldPOIDetails["ordered_qty"]),
                        'old_uom' => $oldProductUom->base_uom->code,
                        'new_qty' => formatQty($qty),
                        'new_uom' => $productUom->base_uom->code,
                        'old_unit_cost' => formatCurrency($oldPOIDetails["unit_price"]),
                        'new_unit_cost' => formatCurrency($unitCost)
                    ];
                }                
            }
        }

        // Delete
         foreach($itemsToDelete as $itemToDelete) {
            $poi = new Models_PurchaseOrderItem($itemToDelete->id);
            $poi->delete();
            if( $poi->getDeletedRows() <= 0 ) {
                throw new Service_Exception($failedErrorMsg);
            }            

            $updateLog[] = [
                'event' => 'deleted',
                'prod_id' => $itemToDelete->product_id,
                'prod_name' => $itemToDelete->product_name,
                'old_qty' => formatQty($itemToDelete->ordered_qty),
                'old_uom' => $poi->uom_code,
                'new_qty' => 0,
                'new_uom' => '',
                'old_unit_cost' => formatCurrency($itemToDelete->unit_price),
                'new_unit_cost' => formatCurrency(0)
            ];
        }

        return $updateLog;
    }



    public function logHistory($poId, $payload) {

        $activityType = $payload["activity_type"];
        $title = $payload["title"];
        $description = $payload["description"] ?? null;
        $refType = $payload["reference_type"] ?? null;
        $refId = $payload["reference_id"] ?? null;
        $meta = empty($payload["meta"]) ? null : json_encode($payload["meta"], JSON_UNESCAPED_UNICODE);
        
        $history = new Models_PurchaseOrderHistory();
        $history->company_id = $this->context->companyId;
        $history->purchase_order_id = $poId;
        $history->activity_type = $activityType;
        $history->title = $title;
        $history->description = $description;
        $history->reference_type = $refType;
        $history->reference_id = $refId;
        $history->meta = $meta;        
        $history->created_by = $this->context->userId;

        if( !$history->create() ) {
            throw new Service_Exception("Failed to log purchase order history");
        }
    }



    /**
     * Retrive add/edit form context data
     */
    public function getFormContext(int $poId) : array {
        
        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $poDetails = [];
        if( $poId ) {

            $purchaseOrder = $this->getPurchaseOrderOrFail($poId);
            $poDetails = array_merge(['id' => $poId, "line_items" => $purchaseOrder->line_items], $purchaseOrder->toArray());
        }

        $location = new Models_Location();
        $locations = $location->getAll([], ["company_id" => $companyId, "status" => ["active"]]);

        $vendor = new Models_Vendor();
        $vendors = $vendor->getAll([], ["company_id" => $companyId, "status" => ["active"]]);

        //$product = new Models_Product();
        //$products = $product->getAll([], ["company_id" => $companyId, "status" => "active"]);
        $sql = "SELECT 
                    a.id, 
                    a.name, 
                    a.sku, 
                    a.cost_price, 
                    b.id AS uom_id,
                    b.name AS uom_name,
                    c.code AS uom_code,
                    b.is_base AS base_uom
                FROM products AS a
                LEFT JOIN product_uoms AS b ON b.product_id=a.id AND b.status='active'
                LEFT JOIN uoms AS c ON c.id=b.base_uom_id
                WHERE
                a.company_id=? AND a.status=?";
        $results = $this->db->fetchAll($sql, [$companyId, 'active']);

        $products = [];
        foreach($results as $row) {
            $id = $row->id;
            $uomId = $row->uom_id;
            if( !isset($products[$id]) ) {
                $products[$id] = [
                    'id' => $id,
                    'name' => $row->name,
                    'sku' => $row->sku,
                    'cost_price' => $row->cost_price,
                    'uoms' => [],                    
                ];
            }

            if( $uomId ) {
                $products[$id]["uoms"][] = [
                    'uom_id' => $uomId,
                    'name' => $row->uom_name,
                    'code' => $row->uom_code,
                    'is_base_uom' => $row->base_uom,
                ];
            }
        }

        $paymentTerm = new Models_PaymentTerm();
        $paymentTerms = $paymentTerm->getAll([], ["company_id" => $companyId, "status" => "active"]);

        $tax = new Models_Tax();
        $poTaxes = $tax->getAll([], ["company_id" => $companyId, "apply_on" => ["purchase", "both"], "status" => "active"]);

        $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

        $data = [
            'po_details' => $poDetails,
            'vendors' => $vendors,
            'locations' => $locations,
            'suggested_po_number' => $seqService->nextPreview("purchase_orders"),
            'products' => array_values($products),
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

        
        // Begin transaction
        $this->db->startTransaction();

        try {

            $companyId = $this->context->companyId;
            $userId = $this->context->userId;

            // generate PO Number
            $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));
            $poNumber = $seqService->nextCommit("purchase_orders");

            // create purchase order
            $poStatus = $payload["status"];
            $poConfirmationDate = $payload["confirmation_date"] ?? "";

            $purchaseOrder = new Models_PurchaseOrder();
            $purchaseOrder->fillFromArray($payload);            
            $purchaseOrder->company_id = $companyId;
            $purchaseOrder->created_by = $userId;
            $purchaseOrder->po_number = $poNumber;

            if( $poStatus === "confirmed" && empty($poConfirmationDate) ) {
                $purchaseOrder->confirmation_date = date("Y-m-d");
            }

            $poId = $purchaseOrder->create();
            if( !$poId ) {
                throw new Service_Exception("Failed to create purchase order");
            }

            // refresh object with newly created PO ID
            $purchaseOrder->refreshById($poId);

            // Purchase order line items
            $lineItems = $payload['po_items'] ?? [];

            
            $this->saveLineItems($purchaseOrder, $lineItems);
            
            
            // purchase order history
            $logPayload = [
                'activity_type' => 'created',
                'title' => 'Purchase order created #'.$poNumber,
                'meta' => [
                    'status' => $poStatus,
                    'items_count' => count($lineItems),
                ],
            ];
            $this->logHistory($poId, $logPayload);

            // Commit
            $this->db->commit();

            return [
                "success" => true,
                "data" => [
                    "po_id" => $poId,
                    "po_number" => $poNumber
                ],
            ];

        } catch (Exception $e) {

            $this->db->rollBack();
            throw $e; // SYSTEM ERROR → Controller will return 500
        }
    }


    /**
     * Update PO
     */
    public function update(int $poId, array $payload)
    {
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

        $this->db->startTransaction();

        try {

            $poEditableFields = ['po_number' => 'PO number', 'reference' => 'Ref.', 'order_date' => 'Order date', 'expected_delivery_date' => 'Exp. delivery date', 'payment_terms' => 'Payment terms', 'status' => 'Status', 'notes' => 'Notes'];
            $oldPODetails = $purchaseOrder->toArray();

            // update purchase order
            $poStatus = $payload["status"];
            $poConfirmationDate = $payload["confirmation_date"] ?? "";

            $purchaseOrder->fillFromArray($payload, ['id', 'po_number', 'company_id', 'created_at', 'created_by']);
            
            if( $poStatus === "confirmed" && empty($poConfirmationDate) ) {
                $purchaseOrder->confirmation_date = date("Y-m-d");
            }
            
            if( !$purchaseOrder->update() ) {
                throw new Service_Exception("Failed to update purchase order");
            }

            $newPODetails = $purchaseOrder->toArray();


            $updatedDetails = [];
            foreach($poEditableFields as $fieldName => $fieldLabel) {
                $oldValue = $oldPODetails[$fieldName] ?? "";
                $newValue = $newPODetails[$fieldName] ?? "";
                if( $oldValue != $newValue ) {
                    $updatedDetails[] = [
                        'field' => $fieldName,
                        'label' => $fieldLabel,
                        'old_val' => $oldValue,
                        'new_val' => $newValue,
                    ];
                }
            }

            // Log change history
            if( !empty($updatedDetails) )
            {
                $logPayload = [
                    'activity_type' => 'updated_details',
                    'title' => 'Purchase order has been updated',
                    'meta' => $updatedDetails,
                ];
                $this->logHistory($poId, $logPayload);
            }

            
            // Purchase order line items
            $incomingItems = $payload['po_items'] ?? [];
            $lineItemUpdateLogs = $this->saveLineItems($purchaseOrder, $incomingItems);

            // Log line items changes/updates
            if( $lineItemUpdateLogs ) {

                $logPayload = [
                    'activity_type' => 'updated_line_items',
                    'title' => 'Line items has been updated',
                    'meta' => $lineItemUpdateLogs,
                ];
                $this->logHistory($poId, $logPayload);
            }
            

            $this->db->commit();

            return [
                "success" => true,
                "data" => [
                    "po_id" => $poId,
                    "po_number" => $purchaseOrder->po_number
                ]
            ];

        } catch (Exception $e) {
            
            $this->db->rollBack();
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

        $this->db->startTransaction();

        try {            


            $status = $payload["status"] ?? "";
            $notes = $payload["notes"] ?? "";
            $oldStatus = $purchaseOrder->status;

            if( $status === "confirmed" ) {

                if ($oldStatus !== 'draft') {
                    throw new Service_Exception("Only draft purchase orders can be confirmed");
                }

                $purchaseOrder->confirmation_date = date('Y-m-d');
            }

            $purchaseOrder->status = $status;
            
            if (!$purchaseOrder->update()) {
                throw new Service_Exception("Failed to update purchase order status");
            }

            // purchase order history            
            $logPayload = [
                'activity_type' => 'status_changed',
                'title' => 'Purchase order status changed',
                'meta' => [
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                    'notes' => $notes,
                ]
            ];
            $this->logHistory($poId, $logPayload);                    

            $this->db->commit();

            return [
                "success" => true,
                "data" => [
                    "po_id" => $poId,
                    "status" => $status,
                    "old_status" => $oldStatus
                ]
            ];

        } catch(Exception $e) {
            
            $this->db->rollBack();
            throw $e;
        }

    }


    public function getHistory(int $poId) {
        
        // guard
        $this->getPurchaseOrderOrFail($poId);

        $sql = "SELECT a.*, b.name AS performed_by FROM purchase_order_history AS a
                LEFT JOIN users AS b ON b.id=a.created_by
                WHERE
                a.company_id=? AND
                a.purchase_order_id=?
                ORDER BY a.id DESC";
        $results = $this->db->fetchAll($sql, [$this->context->companyId, $poId]);

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