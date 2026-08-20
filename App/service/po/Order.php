<?php
class Service_Po_Order extends Service_Base {
    
    private function validateEmailList(string $list): bool
    {
        $addrs = array_filter(array_map('trim', explode(',', $list)));
        if (empty($addrs)) return false;
        foreach ($addrs as $addr) {
            if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) return false;
        }
        return true;
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

    private function validatePayload(array $payload) {

        $vendorId = $payload['vendor_id'] ?? 0;
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


        // Receiving warehouse (optional — pre-fills receipt form; falls back to default warehouse if not set)
        $receivingWarehouseId = (int) ($payload['receiving_warehouse_id'] ?? 0);
        if ($receivingWarehouseId) {
            $receivingWarehouse = new Models_InvWarehouse($receivingWarehouseId);
            if ($receivingWarehouse->isEmpty || $receivingWarehouse->company_id != $this->context->companyId) {
                $this->addError(validationErrMsg("missing_or_invalid", "Receiving warehouse"), "receiving_warehouse_id");
            }
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


        // Drop-ship validation — uncomment when drop-ship receiving type is implemented.
        // Receiving warehouse is now the GRN's responsibility (selected + validated on the receipt form).
        // Drop-ship bypasses the GRN entirely and ships to a customer address instead.
        /*
        $receivingType = $payload['receiving_type'] ?? 'inventory';
        if (!in_array($receivingType, ['inventory', 'drop_ship'], true)) {
            $this->addError(validationErrMsg("invalid", "Receiving type"), "receiving_type");
        }

        if ($receivingType === 'drop_ship' && empty(trim($payload['delivery_address_text'] ?? ''))) {
            $this->addError(validationErrMsg("required", "Delivery address for drop-ship"), "delivery_address_text");
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
            $quantity = (float) ($item['qty'] ?? 0);
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
            } elseif (isPositiveNumeric($quantity) && !$productUom->base_uom->isEmpty && !(bool)(int)$productUom->base_uom->allow_decimal && !isWholeNumber($quantity)) {
                $itemLevelErrors["items.{$index}.qty"] = "Quantity must be a whole number for {$productUom->name} at row {$row}";
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
                if( !(!$tax->isEmpty && $tax->company_id == $this->context->companyId && $tax->status == "active") ) {
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


    private function calcItemDiscount(float $lineSubtotal, array $discountInfo): float {
        if (empty($discountInfo)) return 0;
        $type  = $discountInfo['type']  ?? 'fixed';
        $value = (float) ($discountInfo['value'] ?? 0);
        if ($value <= 0) return 0;
        if ($type === 'percent') {
            return round($lineSubtotal * ($value / 100), 4);
        }
        return min($value, $lineSubtotal);
    }

    private function calcOrderDiscount(float $netSubtotal, array $discountInfo): float {
        if (empty($discountInfo)) return 0;
        $type  = $discountInfo['type']  ?? 'fixed';
        $value = (float) ($discountInfo['value'] ?? 0);
        if ($value <= 0) return 0;
        if ($type === 'percent') {
            return round($netSubtotal * ($value / 100), 4);
        }
        return (float) $value;
    }

    /**
     * Canonical line item calculator — single source of truth for all PO-related math.
     *
     * Used by saveLineItems() (form-based PO creation/edit) and by saveVendorPrices()
     * in Service_Po_Inquiry (vendor quote entry). Never duplicate this logic elsewhere.
     *
     * @param  float  $qty            Ordered / quoted quantity
     * @param  float  $unitPrice      Unit price
     * @param  float  $discountAmount Item-level discount amount already resolved by caller
     *                                (percent discounts must be converted to amount before calling)
     * @param  int[]  $taxIds         Tax IDs to apply; caller ensures these are valid and active
     * @return array {
     *   taxable_amount: float,
     *   tax_amount:     float,
     *   tax_info:       string|null  JSON array of tax detail objects, null when no taxes
     *   line_total:     float,
     *   has_taxes:      bool
     * }
     */
    public static function calcLineItem(float $qty, float $unitPrice, float $discountAmount, array $taxIds): array
    {
        $lineSubtotal  = $qty * $unitPrice;
        $taxableAmount = max(0.0, $lineSubtotal - $discountAmount);

        $taxAmount   = 0.0;
        $taxInfo     = null;
        $taxInfoArr  = [];
        $totalTaxPct = 0.0;
        $totalFixed  = 0.0;

        foreach ($taxIds as $taxId) {
            $tax = new Models_Tax((int) $taxId);
            if ($tax->isEmpty) continue;

            if ($tax->tax_type === 'percentage') {
                $totalTaxPct += (float) $tax->rate;
            } elseif ($tax->tax_type === 'fixed') {
                $totalFixed += (float) $tax->rate;
            }

            $taxInfoArr[] = [
                'id'          => (int) $taxId,
                'name'        => $tax->name,
                'code'        => $tax->code,
                'type'        => $tax->tax_type,
                'rate'        => (float) $tax->rate,
                'description' => $tax->description,
            ];
        }

        if (!empty($taxInfoArr)) {
            if ($totalTaxPct > 0) {
                $taxAmount += $taxableAmount * ($totalTaxPct / 100);
            }
            $taxAmount += $totalFixed;
            $taxInfo    = json_encode($taxInfoArr, JSON_UNESCAPED_UNICODE);
        }

        return [
            'taxable_amount' => round($taxableAmount, 4),
            'tax_amount'     => round($taxAmount, 4),
            'tax_info'       => $taxInfo,
            'line_total'     => round($taxableAmount + $taxAmount, 4),
            'has_taxes'      => !empty($taxInfoArr),
        ];
    }

    private function formatDiscountLabel(array $info): string {
        $type  = $info['type']  ?? 'fixed';
        $value = $info['value'] ?? 0;
        return $type === 'percent' ? "{$value}%" : formatCurrency($value);
    }

    private function allocateOrderDiscountToItems(array $savedItemBases, float $orderDiscountAmt): void {
        if (empty($savedItemBases)) return;

        $bases     = [];
        $totalBase = 0;
        foreach ($savedItemBases as $item) {
            $base      = max(0, (float)$item['subtotal'] - (float)$item['item_discount']);
            $bases[]   = $base;
            $totalBase += $base;
        }

        $lastIndex    = count($savedItemBases) - 1;
        $allocatedSum = 0.0;

        foreach ($savedItemBases as $i => $item) {
            if ($orderDiscountAmt <= 0 || $totalBase <= 0) {
                $allocated = 0.0;
            } elseif ($i < $lastIndex) {
                $allocated     = round($orderDiscountAmt * ($bases[$i] / $totalBase), 4);
                $allocatedSum += $allocated;
            } else {
                $allocated = round($orderDiscountAmt - $allocatedSum, 4);
            }

            $itemBase      = $bases[$i];
            $taxableAmount = $item['has_taxes'] ? max(0, $itemBase - $allocated) : 0.0;

            $this->db->update("purchase_order_items", [
                'order_discount_allocated' => round($allocated, 4),
                'taxable_amount'           => round($taxableAmount, 4),
            ], "id = {$item['id']}");
        }
    }

    private function updatePOTotals(
        int   $poId,
        float $poSubtotal,
        float $poItemDiscounts,
        float $poTaxTotal,
        float $orderDiscountAmt,
        float $roundOffAmt,
        float $adjustmentAmt,
        ?string $adjustmentLabel
    ): void {
        $subtotal         = round($poSubtotal, 4);
        $itemDiscTotal    = round($poItemDiscounts, 4);
        $subAfterItemDisc = round($subtotal - $itemDiscTotal, 4);
        $orderDiscAmt     = round($orderDiscountAmt, 4);
        $discountTotal    = round($itemDiscTotal + $orderDiscAmt, 4);

        $discountRatio = $subAfterItemDisc > 0 ? $orderDiscAmt / $subAfterItemDisc : 0;
        $adjustedTax   = round(max(0, $poTaxTotal * (1 - $discountRatio)), 4);

        $preAdjust  = round($subAfterItemDisc - $orderDiscAmt + $adjustedTax, 4);
        $adjustment = round($adjustmentAmt, 4);
        $roundOff   = round($roundOffAmt, 4);
        $grandTotal = round($preAdjust + $adjustment + $roundOff, 4);

        $this->db->update("purchase_orders", [
            "subtotal"                     => $subtotal,
            "item_discount_total"          => $itemDiscTotal,
            "subtotal_after_item_discount" => $subAfterItemDisc,
            "order_discount_amount"        => $orderDiscAmt,
            "discount_total"               => $discountTotal,
            "tax_amount"                   => $adjustedTax,
            "adjustment_label"             => $adjustmentLabel ?: null,
            "adjustment_amount"            => $adjustment,
            "round_off_amount"             => $roundOff,
            "grand_total"                  => $grandTotal,
        ], "id = {$poId}");
    }


    /**
     * Create, Update or Delete Line Items
     */
    private function saveLineItems(Models_PurchaseOrder $purchaseOrder, array $lineItems) : array {

        $updateLog       = [];
        $poSubtotal      = 0.0;
        $poItemDiscounts = 0.0;
        $poTaxTotal      = 0.0;
        $savedItemBases  = [];

        if( $purchaseOrder->isEmpty ) {
            throw new Service_Exception("Failed to save line items");
        }

        $savedLineItems = $purchaseOrder->line_items;

        [$itemsToCreate, $itemsToUpdate, $itemsToDelete] = $this->getLineItemsDiff($savedLineItems, $lineItems);

        $failedErrorMsg = "Unable to save the purchase order due to an issue with one or more line items";

        // Create && Update
        foreach (array_merge($itemsToCreate, $itemsToUpdate) as $item) {

            $itemId      = (int) ($item['id'] ?? 0);
            $productId   = (int) $item['product_id'];
            $uomId       = (int) $item['uom_id'];
            $description = $item['description'] ?: null;
            $unitCost    = (float) ($item['unit_cost'] ?? 0);
            $qty         = (float) ($item['qty'] ?? 0);
            $lineSubtotal = $qty * $unitCost;

            // Item-level discount
            $discountInfoRaw = $item['discount_info'] ?? [];
            if (is_string($discountInfoRaw)) {
                $discountInfoRaw = json_decode($discountInfoRaw, true) ?: [];
            }
            $itemDiscountAmt = $this->calcItemDiscount($lineSubtotal, $discountInfoRaw);

            // All tax / total computation delegated to the shared canonical engine
            $taxes = $item['tax'] ?? [];
            $calc  = self::calcLineItem($qty, $unitCost, $itemDiscountAmt, $taxes);

            $taxableAmount = $calc['taxable_amount'];
            $taxAmount     = $calc['tax_amount'];
            $taxInfo       = $calc['tax_info'];
            $hasTaxes      = $calc['has_taxes'];

            $product    = new Models_Product($productId);
            $productUom = new Models_ProductUom($uomId);
            $poi        = new Models_PurchaseOrderItem($itemId);
            $oldPOIDetails = $poi->toArray();
            $oldTaxesInfo  = json_decode($oldPOIDetails["tax_info"] ?? '[]', true) ?: [];
            $oldTaxes      = [];
            foreach ($oldTaxesInfo as $oldTaxRow) {
                $oldTaxes[] = $oldTaxRow["id"];
            }

            $lineTotal = $calc['line_total'];

            $poi->purchase_order_id          = $purchaseOrder->id;
            $poi->product_id                 = $productId;
            $poi->product_name               = $product->name;
            $poi->product_sku                = $product->sku;
            $poi->tax_classification_type    = $product->master->tax_classification_type;
            $poi->tax_classification_code    = $product->master->tax_classification_code;
            $poi->product_uom_id             = $productUom->id;
            $poi->conversion_factor_snapshot = $productUom->conversion_factor;
            $poi->uom_code                   = $productUom->base_uom->code;
            $poi->description                = $description;
            $poi->ordered_qty                = $qty;
            $poi->unit_price                 = $unitCost;
            $poi->discount_amount            = round($itemDiscountAmt, 4);
            $poi->discount_info              = !empty($discountInfoRaw) ? json_encode($discountInfoRaw, JSON_UNESCAPED_UNICODE) : null;
            $poi->taxable_amount             = round($taxableAmount, 4);
            $poi->tax_amount                 = round($taxAmount, 4);
            $poi->tax_info                   = $taxInfo;
            $poi->line_total                 = $lineTotal;

            if ($poi->isEmpty) {

                $poi->created_by = $this->context->userId;
                if (!$poi->create()) {
                    throw new Service_Exception($failedErrorMsg);
                }

                $updateLog[] = [
                    'event'         => 'created',
                    'prod_id'       => $productId,
                    'prod_name'     => $product->name,
                    'old_qty'       => 0,
                    'old_uom'       => '',
                    'new_qty'       => formatQty($qty),
                    'new_uom'       => $productUom->base_uom->code,
                    'old_unit_cost' => 0,
                    'new_unit_cost' => formatCurrency($unitCost, ['currency' => $purchaseOrder->currency_code]),
                ];
            } else {

                if (
                    $oldPOIDetails["product_id"]    != $productId ||
                    $oldPOIDetails["description"]   != $description ||
                    $oldPOIDetails["ordered_qty"]   != $qty ||
                    array_diff($oldTaxes, $taxes) ||
                    $oldPOIDetails["line_total"]    != $lineTotal
                ) {
                    if (!$poi->update()) {
                        throw new Service_Exception($failedErrorMsg);
                    }

                    $oldProductUomId = $oldPOIDetails["product_uom_id"];
                    $oldProductUom   = new Models_ProductUom($oldProductUomId);

                    $updateLog[] = [
                        'event'         => 'updated',
                        'prod_id'       => $productId,
                        'prod_name'     => $product->name,
                        'old_qty'       => formatQty($oldPOIDetails["ordered_qty"]),
                        'old_uom'       => $oldProductUom->base_uom->code,
                        'new_qty'       => formatQty($qty),
                        'new_uom'       => $productUom->base_uom->code,
                        'old_unit_cost' => formatCurrency($oldPOIDetails["unit_price"], ['currency' => $purchaseOrder->currency_code]),
                        'new_unit_cost' => formatCurrency($unitCost, ['currency' => $purchaseOrder->currency_code]),
                    ];
                }
            }

            $poSubtotal      += $lineSubtotal;
            $poItemDiscounts += $itemDiscountAmt;
            $poTaxTotal      += $taxAmount;
            $savedItemBases[] = [
                'id'           => $poi->id,
                'subtotal'     => $lineSubtotal,
                'item_discount'=> $itemDiscountAmt,
                'has_taxes'    => $hasTaxes,
            ];
        }

        // Delete
        foreach ($itemsToDelete as $itemToDelete) {
            $poi = new Models_PurchaseOrderItem($itemToDelete->id);
            $poi->delete();
            if ($poi->getDeletedRows() <= 0) {
                throw new Service_Exception($failedErrorMsg);
            }

            $updateLog[] = [
                'event'         => 'deleted',
                'prod_id'       => $itemToDelete->product_id,
                'prod_name'     => $itemToDelete->product_name,
                'old_qty'       => formatQty($itemToDelete->ordered_qty),
                'old_uom'       => $poi->uom_code,
                'new_qty'       => 0,
                'new_uom'       => '',
                'old_unit_cost' => formatCurrency($itemToDelete->unit_price, ['currency' => $purchaseOrder->currency_code]),
                'new_unit_cost' => formatCurrency(0, ['currency' => $purchaseOrder->currency_code]),
            ];
        }

        return [$updateLog, $poSubtotal, $poItemDiscounts, $poTaxTotal, $savedItemBases];
    }

    /**
     * Insert pre-computed line items (all tax/discount values already calculated by the caller).
     * Used by award() in Service_Po_Inquiry so item-insertion logic is not duplicated.
     * Each $row must contain: product_id, product_name, product_sku, description,
     *   product_uom_id, conversion_factor_snapshot, uom_code, ordered_qty,
     *   unit_price, discount_amount, discount_info, taxable_amount, tax_amount, tax_info, line_total
     */
    public function insertLineItemsPrecomputed(int $poId, int $userId, array $rows): void
    {
        foreach ($rows as $row) {
            $productId = (int) $row['product_id'];
            $product   = new Models_Product($productId);

            $poi = new Models_PurchaseOrderItem();
            $poi->purchase_order_id          = $poId;
            $poi->product_id                 = $productId;
            $poi->product_name               = $row['product_name'];
            $poi->product_sku                = $row['product_sku'];
            $poi->tax_classification_type    = !$product->isEmpty ? ($product->master->tax_classification_type ?? null) : null;
            $poi->tax_classification_code    = !$product->isEmpty ? ($product->master->tax_classification_code ?? null) : null;
            $poi->description                = $row['description'] ?? null;
            $poi->product_uom_id             = $row['product_uom_id'];
            $poi->conversion_factor_snapshot = $row['conversion_factor_snapshot'] ?? 1;
            $poi->uom_code                   = $row['uom_code'];
            $poi->ordered_qty                = $row['ordered_qty'];
            $poi->unit_price                 = $row['unit_price'];
            $poi->discount_amount            = $row['discount_amount'] ?? 0;
            $poi->discount_info              = $row['discount_info'] ?? null;
            $poi->taxable_amount             = $row['taxable_amount'];
            $poi->tax_amount                 = $row['tax_amount'];
            $poi->tax_info                   = $row['tax_info'];
            $poi->line_total                 = $row['line_total'];
            $poi->received_qty               = 0;
            $poi->created_by                 = $userId;

            if (!$poi->create()) {
                throw new Service_Exception("Failed to save purchase order item");
            }
        }
    }


    public function getEmailDefaults(int $poId): array
    {
        if (!$this->context->canDo('purchase_orders', 'send_email')) {
            throw new Service_Exception('You do not have permission to send purchase order emails', 403);
        }
        $po = $this->db->fetchOne(
            "SELECT status FROM purchase_orders WHERE id = ? AND company_id = ?",
            [$poId, $this->context->companyId]
        );
        if (!$po) {
            throw new Service_Exception('Purchase order not found', 404);
        }
        $emailSvc = new Service_EmailConfig($this->context);
        return $emailSvc->getEmailDefaults('purchase_order', $poId);
    }


    public function sendEmail(int $poId, array $payload): array {

        if (!$this->context->canDo('purchase_orders', 'send_email')) {
            throw new Service_Exception('You do not have permission to send purchase order emails', 403);
        }

        $purchaseOrder = $this->getPurchaseOrderOrFail($poId);

        $to      = trim($payload['to'] ?? '');
        $cc      = trim($payload['cc'] ?? '');
        $subject = trim($payload['subject'] ?? '');
        $body    = trim($payload['body'] ?? '');

        if (empty($to)) {
            $this->addError(validationErrMsg('required', 'Recipient email'), 'to');
        } elseif (!$this->validateEmailList($to)) {
            $this->addError(validationErrMsg('invalid', 'Recipient email'), 'to');
        }

        if (!empty($cc) && !$this->validateEmailList($cc)) {
            $this->addError(validationErrMsg('invalid', 'CC email'), 'cc');
        }

        $bcc = trim($payload['bcc'] ?? '');
        if (!empty($bcc) && !$this->validateEmailList($bcc)) {
            $this->addError(validationErrMsg('invalid', 'BCC email'), 'bcc');
        }

        if (empty($subject)) {
            $this->addError(validationErrMsg('required', 'Subject'), 'subject');
        }

        if (empty($body)) {
            $this->addError(validationErrMsg('required', 'Message body'), 'body');
        }

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $emailConfig = new Service_EmailConfig($this->context);
        $smtpConfig  = $emailConfig->getSMTPConfig();
        $docConfig   = $emailConfig->getDocConfig('purchase_order');
        $resolved    = $emailConfig->resolveFrom($docConfig, $this->context->userId);
        $from        = "{$resolved['name']}<{$resolved['email']}>";

        $mailer = new Helpers_Mailer();

        if (!empty($cc)) {
            $mailer->addCC($cc);
        }

        if (!empty($bcc)) {
            $mailer->addBCC($bcc);
        }

        $attachments = (array) ($payload['attachments'] ?? []);
        foreach ($attachments as $att) {
            $name     = $att['name'] ?? 'attachment';
            $mimeType = $att['mime_type'] ?? 'application/octet-stream';
            $content  = $att['content'] ?? '';
            if (!empty($content)) {
                $mailer->addStringAttachment(base64_decode($content), $name, $mimeType);
            }
        }

        $db = $this->db;
        $db->startTransaction();
        try {
            if (empty($purchaseOrder->declaration_snapshot)) {
                $declSvc = new Service_CompanySettings($this->context);
                $decl = (string) $declSvc->get('doc_declaration.purchase_order', '');
                if ($decl !== '') {
                    $db->update('purchase_orders', ['declaration_snapshot' => $decl], "id = {$poId}");
                }
            }

            $historyMeta = ['from' => $resolved['email'], 'to' => $to, 'cc' => $cc, 'bcc' => $bcc, 'subject' => $subject, 'attachments' => []];
            $historyId = $this->logHistory($poId, [
                'log_type' => 'email_sent',
                'title'    => 'Purchase Order ' . $purchaseOrder->po_number . ' emailed to ' . $to,
                'meta'     => $historyMeta,
            ]);

            if (!empty($attachments)) {
                $attachSvc = new Service_Attachment($this->context);
                $attachSvc->saveFromBase64($attachments, 'purchase_order_history', $historyId);
                $historyMeta['attachments'] = $attachSvc->listFor('purchase_order_history', $historyId);
                $db->update('purchase_order_history', ['meta' => json_encode($historyMeta)], "id = {$historyId}");
            }

            $sent = $mailer->sendMail($from, $to, $subject, $body, $smtpConfig);
            if (!$sent) {
                $detail = implode('; ', $mailer->getErrors()) ?: 'Unknown SMTP error';
                throw new Service_Exception("Failed to send email: {$detail}", 500);
            }

            $db->commit();
        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }

        return ["success" => true];
    }


    public function logHistory(int $poId, array $payload): int {

        $meta = empty($payload["meta"]) ? null : json_encode($payload["meta"], JSON_UNESCAPED_UNICODE);

        $history = new Models_PurchaseOrderHistory();
        $history->company_id = $this->context->companyId;
        $history->purchase_order_id = $poId;
        $history->log_type = $payload["log_type"];
        $history->title = $payload["title"];
        $history->reference_type = $payload["reference_type"] ?? null;
        $history->reference_id = $payload["reference_id"] ?? null;
        $history->meta = $meta;
        $history->created_by = $this->context->userId;

        $historyId = $history->create();

        if (!$historyId) {
            throw new Service_Exception("Failed to log purchase order history");
        }

        return (int) $historyId;
    }



    /**
     * Retrive add/edit form context data
     */
    public function getRecentVendors(int $companyId, int $limit = 10, int $includeVendorId = 0): array {

        $rows = $this->db->fetchAll(
            "SELECT v.id, v.display_name, v.email, v.phone, v.currency_code, v.payment_term_id
             FROM vendors v
             INNER JOIN (
                 SELECT vendor_id, MAX(created_at) AS last_used
                 FROM purchase_orders
                 WHERE company_id = ?
                 GROUP BY vendor_id
             ) po ON po.vendor_id = v.id
             WHERE v.company_id = ? AND v.status = 'active'
             ORDER BY po.last_used DESC
             LIMIT ?",
            [$companyId, $companyId, $limit]
        );

        $list = array_map(fn($r) => (array) $r, $rows);

        if ($includeVendorId > 0) {
            $ids = array_column($list, 'id');
            if (!in_array($includeVendorId, $ids)) {
                $vendor = $this->db->fetchOne(
                    "SELECT id, display_name, email, phone, currency_code, payment_term_id FROM vendors WHERE id = ? AND company_id = ?",
                    [$includeVendorId, $companyId]
                );
                if ($vendor) {
                    array_unshift($list, (array) $vendor);
                }
            }
        }

        return $list;
    }


    public function searchVendors(string $query): array {

        $companyId = $this->context->companyId;
        $like = '%' . $query . '%';

        $sql = "SELECT id, display_name, email, phone, currency_code, payment_term_id
                FROM vendors
                WHERE company_id = ? AND status = 'active'
                  AND (
                        display_name LIKE ? OR
                        legal_name LIKE ? OR
                        email LIKE ? OR
                        phone LIKE ? OR
                        vendor_code LIKE ?
                  )
                ORDER BY display_name ASC
                LIMIT 25";

        $rows = $this->db->fetchAll($sql, [$companyId, $like, $like, $like, $like, $like]);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id'              => $row->id,
                'display_name'    => $row->display_name,
                'email'           => $row->email,
                'phone'           => $row->phone,
                'currency_code'   => $row->currency_code,
                'payment_term_id' => $row->payment_term_id,
            ];
        }

        return $data;
    }


    public function getFormContext(int $poId) : array {
        
        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $poDetails = [];
        if ($poId) {
            $purchaseOrder = $this->getPurchaseOrderOrFail($poId);
            $poDetails = array_merge(['id' => $poId, "line_items" => $purchaseOrder->line_items], $purchaseOrder->toArray());
            if (!empty($poDetails['discount_info'])) {
                $poDetails['discount_info'] = json_decode($poDetails['discount_info'], true);
            }
        }

        $warehouses = Service_Company::getActiveWarehouses($companyId);

        $selectedVendorId = (int) ($poDetails['vendor_id'] ?? 0);
        $recentVendors = $this->getRecentVendors($companyId, 10, $selectedVendorId);

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

        // Attach purchase-side default tax IDs per product
        $taxMapSql  = "SELECT product_id, GROUP_CONCAT(tax_id ORDER BY tax_id SEPARATOR ',') AS tax_ids
                       FROM product_default_taxes
                       WHERE company_id = ? AND apply_on = 'purchase'
                       GROUP BY product_id";
        $taxMapRows = $this->db->fetchAll($taxMapSql, [$companyId]);
        $productTaxMap = [];
        foreach ($taxMapRows as $trow) {
            $productTaxMap[(int)$trow->product_id] = array_map('intval', array_filter(explode(',', $trow->tax_ids)));
        }
        foreach ($products as &$prod) {
            $prod['purchase_tax_ids'] = $productTaxMap[$prod['id']] ?? [];
        }
        unset($prod);

        $paymentTerm = new Models_PaymentTerm();
        $paymentTerms = $paymentTerm->getAll([], ["company_id" => $companyId, "status" => "active"]);

        $tax = new Models_Tax();
        $poTaxes = $tax->getAll([], ["company_id" => $companyId, "status" => "active"]);

        $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

        $data = [
            'po_details'          => $poDetails,
            'recent_vendors'      => $recentVendors,
            'warehouses'          => $warehouses,
            'suggested_po_number' => $seqService->nextPreview("purchase_orders"),
            'products' => array_values($products),
            'payment_terms' => $paymentTerms,
            'taxes' => $poTaxes,
        ];

        return $data;
    }


    public function getStatus(int $poId): array {
        
        $purchaseOrder = $this->getPurchaseOrderOrFail($poId);

        return ["status" => $purchaseOrder->status];
    }


    /**
     * Retrive purchase order details
     */
    public function getDetails(int $poId) : array {

        $purchaseOrder = $this->getPurchaseOrderOrFail($poId);

        $poDetails = array_merge([
            'id'           => $poId,
            'vendor_name'  => $purchaseOrder->vendor->display_name,
            'vendor_email' => $purchaseOrder->vendor->email,
            'line_items'   => $purchaseOrder->line_items,
        ], $purchaseOrder->toArray());

        if (!empty($poDetails['discount_info'])) {
            $poDetails['discount_info'] = json_decode($poDetails['discount_info'], true);
        }

        if (!empty($poDetails['vendor_address_snapshot'])) {
            $poDetails['vendor_address_snapshot'] = json_decode($poDetails['vendor_address_snapshot'], true);
        }

        if (!empty($poDetails['inquiry_id'])) {
            $inqRow = $this->db->fetchOne(
                "SELECT inquiry_number FROM purchase_inquiries WHERE id = ? LIMIT 1",
                [(int) $poDetails['inquiry_id']]
            );
            $poDetails['inquiry_number'] = $inqRow ? $inqRow->inquiry_number : null;
        }

        $data = ['po_details' => $poDetails];

        return $data;
    }



    /**
     * Create PO
     */
    public function create(array $payload) {

        if (!$this->context->canDo('purchase_orders', 'write')) {
            throw new Service_Exception('You do not have permission to create purchase orders', 403);
        }

        // Validate incoming data
        $this->validatePayload($payload);

        // PO Number — validate uniqueness if user edited the suggested value
        $poNumberInput     = trim($payload['po_number'] ?? '');
        $poNumberSuggested = trim($payload['po_number_suggested'] ?? '');
        if (!empty($poNumberInput) && $poNumberInput !== $poNumberSuggested) {
            $exists = $this->db->fetchOne(
                "SELECT id FROM purchase_orders WHERE company_id = ? AND po_number = ? LIMIT 1",
                [$this->context->companyId, $poNumberInput]
            );
            if ($exists) {
                $this->addError(validationErrMsg("duplicate", "PO number"), "po_number");
            }
        }

        // Resolve company location (auto-filled; not user-supplied)
        $defaultLocationId = Service_Company::getDefaultLocationId($this->context->companyId);
        if (!$defaultLocationId) {
            $this->addError("Company location is not configured. Please contact support.", "company_location_id");
        }

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
            $userId    = $this->context->userId;

            // Order-level discount
            $orderDiscountInfoRaw = $payload['order_discount_info'] ?? [];
            if (is_string($orderDiscountInfoRaw)) {
                $orderDiscountInfoRaw = json_decode($orderDiscountInfoRaw, true) ?: [];
            }

            // Payment term snapshot
            $paymentTermId    = (int) ($payload['payment_term_id'] ?? 0);
            $paymentTermsText = null;
            if ($paymentTermId) {
                $termObj          = new Models_PaymentTerm($paymentTermId);
                $paymentTermsText = !$termObj->isEmpty ? $termObj->name : null;
            }

            // PO Number — auto-generate unless user provided a custom value
            $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));
            if (empty($poNumberInput) || $poNumberInput === $poNumberSuggested) {
                $poNumber = $seqService->nextCommit("purchase_orders");
            } else {
                $poNumber = $poNumberInput;
                $seqService->advanceCounter("purchase_orders", $poNumber);
            }

            $poStatus           = $payload["status"];
            $poConfirmationDate = $payload["confirmation_date"] ?? "";

            $purchaseOrder = new Models_PurchaseOrder();
            $purchaseOrder->fillFromArray($payload, ['id', 'po_number', 'company_id', 'company_location_id', 'created_at', 'created_by', 'vendor_address_snapshot', 'discount_info', 'payment_terms', 'payment_term_id']);
            if (!Service_CompanySettings::isMultiWarehouseEnabled($companyId)) {
                $purchaseOrder->receiving_warehouse_id = Service_Company::getDefaultWarehouseId($companyId) ?? null;
            }
            $purchaseOrder->company_id          = $companyId;
            $purchaseOrder->company_location_id = $defaultLocationId;
            $purchaseOrder->created_by          = $userId;
            $purchaseOrder->po_number           = $poNumber;
            $purchaseOrder->payment_term_id = $paymentTermId ?: null;
            $purchaseOrder->payment_terms   = $paymentTermsText;
            $purchaseOrder->discount_info   = !empty($orderDiscountInfoRaw) ? json_encode($orderDiscountInfoRaw, JSON_UNESCAPED_UNICODE) : null;

            if ($poStatus === "confirmed" && empty($poConfirmationDate)) {
                $purchaseOrder->confirmation_date = date("Y-m-d");
            }

            $poId = $purchaseOrder->create();
            if (!$poId) {
                throw new Service_Exception("Failed to create purchase order");
            }

            // Snapshot vendor billing address
            $vendor = new Models_Vendor($purchaseOrder->vendor_id);
            if (!$vendor->isEmpty) {
                $this->db->update('purchase_orders', [
                    'vendor_address_snapshot' => json_encode($vendor->getBillingAddress(), JSON_UNESCAPED_UNICODE),
                ], "id = {$poId}");
            }

            // Refresh object after create
            $purchaseOrder->refreshById($poId);

            // Line items
            $lineItems = $payload['po_items'] ?? [];
            [$updateLog, $poSubtotal, $poItemDiscounts, $poTaxTotal, $savedItemBases] = $this->saveLineItems($purchaseOrder, $lineItems);

            // Order discount allocation
            $netSubtotal      = $poSubtotal - $poItemDiscounts;
            $orderDiscountAmt = $this->calcOrderDiscount($netSubtotal, $orderDiscountInfoRaw);
            $this->allocateOrderDiscountToItems($savedItemBases, $orderDiscountAmt);

            $roundOffAmt     = round((float) ($payload['round_off_amount']  ?? 0), 4);
            $adjustmentAmt   = round((float) ($payload['adjustment_amount'] ?? 0), 4);
            $adjustmentLabel = trim($payload['adjustment_label'] ?? '');

            $this->updatePOTotals($poId, $poSubtotal, $poItemDiscounts, $poTaxTotal, $orderDiscountAmt, $roundOffAmt, $adjustmentAmt, $adjustmentLabel ?: null);

            // History
            $this->logHistory($poId, [
                'log_type' => 'created',
                'title'    => 'Order created #' . $poNumber,
                'meta'     => [
                    'status'      => $poStatus,
                    'item_count' => count($lineItems),
                ],
            ]);

            $this->db->commit();

            return [
                "success" => true,
                "data"    => [
                    "po_id"     => $poId,
                    "po_number" => $poNumber,
                ],
            ];

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }


    /**
     * Update PO
     */
    public function update(int $poId, array $payload)
    {
        if (!$this->context->canDo('purchase_orders', 'write')) {
            throw new Service_Exception('You do not have permission to update purchase orders', 403);
        }

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

            // Order-level discount
            $orderDiscountInfoRaw = $payload['order_discount_info'] ?? [];
            if (is_string($orderDiscountInfoRaw)) {
                $orderDiscountInfoRaw = json_decode($orderDiscountInfoRaw, true) ?: [];
            }

            // Payment term snapshot
            $paymentTermId    = (int) ($payload['payment_term_id'] ?? 0);
            $paymentTermsText = null;
            if ($paymentTermId) {
                $termObj          = new Models_PaymentTerm($paymentTermId);
                $paymentTermsText = !$termObj->isEmpty ? $termObj->name : null;
            }

            $poEditableFields = [
                'po_number'              => 'PO number',
                'reference'              => 'Ref.',
                'order_date'             => 'Order date',
                'expected_delivery_date' => 'Exp. delivery date',
                'payment_terms'          => 'Payment terms',
                'currency_code'          => 'Currency',
                'status'                 => 'Status',
                'notes'                  => 'Notes',
                'internal_notes'         => 'Internal notes',
                'order_discount_amount'  => 'Order discount',
                'grand_total'            => 'Grand total',
                'adjustment_label'       => 'Adjustment label',
                'adjustment_amount'      => 'Adjustment amount',
                'round_off_amount'       => 'Round-off',
                'discount_info'          => 'Order discount info',
            ];

            $oldPODetails = $purchaseOrder->toArray();

            $poStatus           = $payload["status"];
            $poConfirmationDate = $payload["confirmation_date"] ?? "";

            $purchaseOrder->fillFromArray($payload, ['id', 'po_number', 'company_id', 'company_location_id', 'created_at', 'created_by', 'vendor_address_snapshot', 'discount_info', 'payment_terms', 'payment_term_id']);
            if (!Service_CompanySettings::isMultiWarehouseEnabled($this->context->companyId)) {
                $purchaseOrder->receiving_warehouse_id = Service_Company::getDefaultWarehouseId($this->context->companyId) ?? null;
            }
            $purchaseOrder->payment_term_id = $paymentTermId ?: null;
            $purchaseOrder->payment_terms   = $paymentTermsText;
            $purchaseOrder->discount_info   = !empty($orderDiscountInfoRaw) ? json_encode($orderDiscountInfoRaw, JSON_UNESCAPED_UNICODE) : null;

            if ($poStatus === "confirmed" && empty($poConfirmationDate)) {
                $purchaseOrder->confirmation_date = date("Y-m-d");
            }

            if (!$purchaseOrder->update()) {
                throw new Service_Exception("Failed to update purchase order");
            }

            $newPODetails = $purchaseOrder->toArray();

            $updatedDetails = [];
            foreach ($poEditableFields as $fieldName => $fieldLabel) {
                if ($fieldName === 'discount_info') {
                    // intentionally skipped — JSON comparison unreliable for history
                    continue;
                }
                $oldValue = $oldPODetails[$fieldName] ?? "";
                $newValue = $newPODetails[$fieldName] ?? "";
                if ($oldValue != $newValue) {
                    $updatedDetails[] = [
                        'field'   => $fieldName,
                        'label'   => $fieldLabel,
                        'old_val' => $oldValue,
                        'new_val' => $newValue,
                    ];
                }
            }

            if (!empty($updatedDetails)) {
                $this->logHistory($poId, [
                    'log_type' => 'updated_details',
                    'title'    => 'Purchase order has been updated',
                    'meta'     => $updatedDetails,
                ]);
            }

            // Line items
            $incomingItems = $payload['po_items'] ?? [];
            [$lineItemUpdateLogs, $poSubtotal, $poItemDiscounts, $poTaxTotal, $savedItemBases] = $this->saveLineItems($purchaseOrder, $incomingItems);

            // Order discount allocation
            $netSubtotal      = $poSubtotal - $poItemDiscounts;
            $orderDiscountAmt = $this->calcOrderDiscount($netSubtotal, $orderDiscountInfoRaw);
            $this->allocateOrderDiscountToItems($savedItemBases, $orderDiscountAmt);

            $roundOffAmt     = round((float) ($payload['round_off_amount']  ?? 0), 4);
            $adjustmentAmt   = round((float) ($payload['adjustment_amount'] ?? 0), 4);
            $adjustmentLabel = trim($payload['adjustment_label'] ?? '');

            $this->updatePOTotals($poId, $poSubtotal, $poItemDiscounts, $poTaxTotal, $orderDiscountAmt, $roundOffAmt, $adjustmentAmt, $adjustmentLabel ?: null);

            if (!empty($lineItemUpdateLogs)) {
                $this->logHistory($poId, [
                    'log_type' => 'updated_line_items',
                    'title'    => 'Line items has been updated',
                    'meta'     => $lineItemUpdateLogs,
                ]);
            }

            $this->db->commit();

            return [
                "success" => true,
                "data"    => [
                    "po_id"     => $poId,
                    "po_number" => $purchaseOrder->po_number,
                ],
            ];

        } catch (Exception $e) {
            $this->db->rollback();
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
        $status = $payload['status'] ?? '';

        $allowedTargetStatuses = ['confirmed', 'cancelled'];
        if (!in_array($status, $allowedTargetStatuses)) {
            throw new Service_Exception("Invalid status transition", 422);
        }

        $requiredAction = match($status) {
            'cancelled' => 'cancel',
            'confirmed' => 'confirm',
            default     => 'write',
        };
        if (!$this->context->canDo('purchase_orders', $requiredAction)) {
            throw new Service_Exception('You do not have permission to perform this action on purchase orders', 403);
        }

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

                $purchaseOrder->confirmation_date = dateNow('Y-m-d');

                // Snapshot declaration at confirmation (snapshot-once)
                if (empty($purchaseOrder->declaration_snapshot)) {
                    $declSvc = new Service_CompanySettings($this->context);
                    $decl = (string) $declSvc->get('doc_declaration.purchase_order', '');
                    if ($decl !== '') {
                        $purchaseOrder->declaration_snapshot = $decl;
                    }
                }
            }

            $purchaseOrder->status = $status;
            
            if (!$purchaseOrder->update()) {
                throw new Service_Exception("Failed to update purchase order status");
            }

            // purchase order history            
            $logPayload = [
                'log_type' => 'status_changed',
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
            
            $this->db->rollback();
            throw $e;
        }

    }


    public function cancel(int $poId): array
    {
        if (!$this->context->canDo('purchase_orders', 'cancel')) {
            throw new Service_Exception('You do not have permission to cancel purchase orders', 403);
        }

        $companyId = $this->context->companyId;
        $purchaseOrder = $this->getPurchaseOrderOrFail($poId);

        if (!in_array($purchaseOrder->status, ['draft', 'confirmed'])) {
            throw new Service_Exception("This purchase order cannot be cancelled", 422);
        }

        $receivedGrnCount = (int) $this->db->fetchVar(
            "SELECT COUNT(id) FROM purchase_order_grns
             WHERE purchase_order_id = ? AND company_id = ? AND status = 'received'",
            [$poId, $companyId]
        );
        if ($receivedGrnCount > 0) {
            throw new Service_Exception("Cannot cancel — items have already been received against this order", 422);
        }

        $pendingGrns = $this->db->fetchAll(
            "SELECT id FROM purchase_order_grns
             WHERE purchase_order_id = ? AND company_id = ? AND status IN ('draft', 'in_transit')",
            [$poId, $companyId]
        );

        $oldStatus = $purchaseOrder->status;
        $now       = date('Y-m-d H:i:s');

        $this->db->startTransaction();
        try {
            foreach ($pendingGrns as $grn) {
                $grnId = (int) $grn->id;
                $this->db->update('purchase_order_grns', ['status' => 'cancelled'], "id = {$grnId}");
                $this->db->insert('purchase_order_grn_history', [
                    'company_id'            => $companyId,
                    'purchase_order_grn_id' => $grnId,
                    'log_type'              => 'cancelled',
                    'title'                 => 'GRN cancelled — parent PO was cancelled',
                    'created_by'            => $this->context->userId,
                    'created_at'            => $now,
                ]);
            }

            $purchaseOrder->status = 'cancelled';
            if (!$purchaseOrder->update()) {
                throw new Service_Exception("Failed to cancel purchase order");
            }

            $this->logHistory($poId, [
                'log_type' => 'status_changed',
                'title'    => 'Purchase order cancelled',
                'meta'     => [
                    'old_status' => $oldStatus,
                    'new_status' => 'cancelled',
                ],
            ]);

            $this->db->commit();
            return ["success" => true, "data" => ["po_id" => $poId, "status" => "cancelled"]];

        } catch (Exception $e) {
            $this->db->rollback();
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
            $meta = !empty($row->meta) ? (json_decode($row->meta, true) ?: (object)[]) : (object)[];
            $formattedData[] = [
                'log_type' => $row->log_type,
                'title' => $row->title,
                'reference_type' => $row->reference_type,
                'reference_id' => $row->reference_id,
                'meta' => $meta,
                'performed_by' => $row->performed_by,
                'date_time' => formatMySqlDate($row->created_at),
            ];
        }
        
        return $formattedData;
    }


    public function buildPrintData(int $poId): array
    {
        $po = new Models_PurchaseOrder($poId);
        if ($po->isEmpty || $po->company_id != $this->context->companyId) {
            throw new Service_Exception("Purchase order not found", 404);
        }

        $company = $this->db->fetchOne(
            "SELECT name, legal_name, email, phone, website, address, city, state, country, zipcode, gstin, pan, tan, cin, logo_path, signature_path FROM companies WHERE id = ?",
            [$this->context->companyId]
        );

        $vendor = new Models_Vendor($po->vendor_id);

        // Use historical snapshot for vendor address; fall back to live address when snapshot is absent or empty
        $vendorAddress = [];
        if (!empty($po->vendor_address_snapshot)) {
            $vendorAddress = json_decode($po->vendor_address_snapshot, true) ?: [];
        }
        if (empty($vendorAddress) && !$vendor->isEmpty) {
            $vendorAddress = $vendor->getBillingAddress();
        }

        $deliveryAddress = [];
        if ($po->receiving_type === 'delivery' && !empty($po->delivery_address_snapshot)) {
            $deliveryAddress = json_decode($po->delivery_address_snapshot, true) ?: [];
        }

        $lineItems = [];
        foreach ($po->line_items as $item) {
            $taxes    = is_array($item->tax_info) ? $item->tax_info : [];
            $taxLabel = '';
            if (!empty($taxes)) {
                $taxParts = array_map(fn($t) => $t->name ?? '', $taxes);
                $taxLabel = implode(', ', array_filter($taxParts));
            }

            $lineItems[] = [
                'product_name'    => $item->product_name,
                'description'     => $item->description,
                'qty'             => $item->ordered_qty,
                'uom_code'        => $item->uom_code,
                'unit_price'      => (float) $item->unit_price,
                'discount_amount' => (float) $item->discount_amount,
                'discount_info'   => $item->discount_info,
                'tax_info'        => $taxes,
                'tax_label'       => $taxLabel,
                'tax_amount'      => (float) $item->tax_amount,
                'line_total'      => (float) $item->line_total,
            ];
        }

        $settingsSvc = new Service_CompanySettings($this->context);
        $snapshotDecl = $po->declaration_snapshot ?? '';
        $settings = [
            'show_amount_in_words' => (bool)(int) $settingsSvc->get('doc_config.purchase_order.show_amount_in_words', 1),
            'show_signature'       => (bool)(int) $settingsSvc->get('doc_config.purchase_order.show_signature', 1),
            'declaration'          => ($snapshotDecl !== '' && $snapshotDecl !== null)
                                        ? $snapshotDecl
                                        : (string) $settingsSvc->get('doc_declaration.purchase_order', ''),
            'terms'                => (string) $settingsSvc->get('doc_terms.purchase_order', ''),
        ];

        return [
            'company'          => $company ? (array) $company : [],
            'po'               => [
                'id'                          => $po->id,
                'po_number'                   => $po->po_number,
                'status'                      => $po->status,
                'receiving_type'              => $po->receiving_type,
                'order_date'                  => $po->order_date,
                'expected_delivery_date'      => $po->expected_delivery_date,
                'payment_terms'               => $po->payment_terms,
                'reference'                   => $po->reference,
                'notes'                       => $po->notes,
                'subtotal'                    => (float) $po->subtotal,
                'item_discount_total'         => (float) $po->item_discount_total,
                'subtotal_after_item_discount'=> (float) $po->subtotal_after_item_discount,
                'order_discount_amount'       => (float) $po->order_discount_amount,
                'discount_total'              => (float) $po->discount_total,
                'tax_amount'                  => (float) $po->tax_amount,
                'adjustment_label'            => $po->adjustment_label,
                'adjustment_amount'           => (float) $po->adjustment_amount,
                'round_off_amount'            => (float) $po->round_off_amount,
                'grand_total'                 => (float) $po->grand_total,
                'currency_code'               => $po->currency_code,
            ],
            'vendor'           => [
                'name'  => $vendor->display_name ?? '',
                'phone' => $vendor->phone  ?? '',
                'email' => $vendor->email  ?? '',
                'gstin' => $vendor->gstin  ?? '',
            ],
            'vendor_address'   => $vendorAddress,
            'delivery_address' => $deliveryAddress,
            'line_items'       => $lineItems,
            'settings'         => $settings,
        ];
    }


    public function renderPdf(int $poId): string
    {
        $data = $this->buildPrintData($poId);

        $status      = $data['po']['status'] ?? '';
        $watermark   = null;
        $emailConfig = new Service_EmailConfig($this->context);
        $settingsSvc = new Service_CompanySettings($this->context);

        if ($status === 'draft') {
            $watermark = 'DRAFT';
        } elseif ($status === 'cancelled') {
            $watermark = 'CANCELLED';
        }

        $templateKey = $emailConfig->getPdfTemplate('purchase_order');
        $registry    = config('pdf_templates.purchase_order', []);
        $view        = $registry[$templateKey]['view'] ?? $registry['template_1']['view'] ?? 'pdf.purchase-order';

        return Helpers_Pdf::render($view, ['printData' => $data], ['watermark' => $watermark]);
    }


    public function buildPdf(int $poId): array
    {
        $po = $this->getPurchaseOrderOrFail($poId);

        $scope  = (new Service_Scope($this->context))->getCondition('purchase_orders', ['po.created_by']);
        $sql    = "SELECT po.id FROM purchase_orders po WHERE po.id = ? AND po.company_id = ?";
        $params = [$poId, $this->context->companyId];
        if ($scope['sql']) {
            $sql   .= " AND (" . $scope['sql'] . ")";
            $params = array_merge($params, $scope['bindings']);
        }
        if (!$this->db->fetchOne($sql, $params)) {
            throw new Service_Exception("You do not have permission to access this purchase order", 403);
        }

        $filename = $po->po_number . '.pdf';

        return [
            'bytes'    => $this->renderPdf($poId),
            'filename' => $filename,
        ];
    }

    public function generateEmailPdf(int $poId): array
    {
        $pdf = $this->buildPdf($poId);
        return [
            'name'      => $pdf['filename'],
            'mime_type' => 'application/pdf',
            'content'   => base64_encode($pdf['bytes']),
        ];
    }


    public function getProductCostHistory(int $productId, int $vendorId = 0): array {

        $companyId = $this->context->companyId;
        $statuses  = "('confirmed','partially_received','received','closed')";

        // Last 5 confirmed POs for this product, optionally filtered to one vendor
        $historyWhere  = "poi.product_id = ? AND po.company_id = ? AND po.status IN {$statuses}";
        $historyBinds  = [$productId, $companyId];
        if ($vendorId) {
            $historyWhere  .= " AND po.vendor_id = ?";
            $historyBinds[] = $vendorId;
        }

        $history = $this->db->fetchAll(
            "SELECT po.po_number, po.order_date, poi.ordered_qty, poi.unit_price,
                    poi.discount_amount, v.display_name AS vendor_name
             FROM purchase_order_items poi
             JOIN purchase_orders po ON po.id = poi.purchase_order_id
             JOIN vendors v ON v.id = po.vendor_id
             WHERE {$historyWhere}
             ORDER BY po.order_date DESC, po.id DESC
             LIMIT 5",
            $historyBinds
        );

        // Most recent confirmed price per vendor for this product, sorted cheapest first
        $comparison = $this->db->fetchAll(
            "SELECT v.display_name AS vendor_name, poi.unit_price, po.order_date, po.po_number
             FROM purchase_orders po
             JOIN purchase_order_items poi ON poi.purchase_order_id = po.id AND poi.product_id = ?
             JOIN vendors v ON v.id = po.vendor_id
             WHERE po.company_id = ? AND po.status IN {$statuses}
               AND po.id IN (
                   SELECT MAX(po2.id)
                   FROM purchase_orders po2
                   JOIN purchase_order_items poi2 ON poi2.purchase_order_id = po2.id AND poi2.product_id = ?
                   WHERE po2.company_id = ? AND po2.status IN {$statuses}
                   GROUP BY po2.vendor_id
               )
             ORDER BY poi.unit_price ASC",
            [$productId, $companyId, $productId, $companyId]
        );

        return ['history' => $history, 'vendor_comparison' => $comparison];
    }
}