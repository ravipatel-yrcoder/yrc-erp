<?php
class Service_So_Order extends Service_Base {


    private function validateEmailList(string $list): bool
    {
        $addrs = array_filter(array_map('trim', explode(',', $list)));
        if (empty($addrs)) return false;
        foreach ($addrs as $addr) {
            if (!filter_var($addr, FILTER_VALIDATE_EMAIL)) return false;
        }
        return true;
    }

    private function getSalesOrderOrFail(int $soId): Models_SalesOrder {

        $so = new Models_SalesOrder($soId);
        if ($so->isEmpty) {
            throw new Service_Exception("The requested sales order was not found", 404);
        }
        if ($so->company_id != $this->context->companyId) {
            throw new Service_Exception("You do not have permission to access this sales order", 403);
        }
        return $so;
    }




    private function validatePayload(array $payload, int $soId = 0): array {

        $customerId = (int) ($payload['customer_id'] ?? 0);
        $multiWarehouse = Service_CompanySettings::isMultiWarehouseEnabled($this->context->companyId);
        $warehouseId = $multiWarehouse
            ? (int) ($payload['source_warehouse_id'] ?? 0)
            : (Service_Company::getDefaultWarehouseId($this->context->companyId) ?? 0);
        $originType = ($payload['origin_type'] ?? 'order');
        $isQuotation = ($originType === 'quotation');
        $orderDate = ($payload['order_date'] ?? '');
        $quoteDate = ($payload['quote_date'] ?? '');
        $expectedDate = ($payload['expected_delivery_date'] ?? '');
        $paymentTermId = ($payload['payment_term_id'] ?? '');
        $status = ($payload['status'] ?? '');
        $lineItems = (array) ($payload['so_items'] ?? []);
        $soNumberInput = trim($payload['so_number'] ?? '');
        $soNumberSuggested = trim($payload['so_number_suggested'] ?? '');

        
        /*
        if (!empty($soNumberInput) && !empty($soNumberSuggested) && $soNumberInput !== $soNumberSuggested ) {
                
            // User-provided custom number — validate uniqueness
            $this->validateCustomSoNumber($soNumberInput);                
        }
        */

        // Validate SO Number if editted by user, otherwise system will always regenerate at the time of order creation
        if( !empty($soNumberInput) && $soNumberInput !== $soNumberSuggested ) {
            if( !$this->isUniqueSONumber($soNumberInput, $soId) ) {
                $this->addError(validationErrMsg("duplicate", "SO number"), "so_number");
            }
        }


        // Customer
        $customer = new Models_Customer($customerId);
        if ($customer->isEmpty || $customer->company_id != $this->context->companyId) {
            $this->addError(validationErrMsg("missing_or_invalid", "Customer"), "customer_id");
        }

        // Source warehouse
        $warehouse = new Models_InvWarehouse($warehouseId);
        if ($warehouse->isEmpty || $warehouse->company_id != $this->context->companyId || $warehouse->status !== 'active') {
            $this->addError(validationErrMsg("missing_or_invalid", "Source warehouse"), "source_warehouse_id");
        }

        // Shipping address — required when delivery type is shipment
        $deliveryType = $payload['delivery_type'] ?? 'pickup';
        $shippingAddressJson = trim($payload['shipping_address_json'] ?? '');
        if ($deliveryType === 'ship' && empty($shippingAddressJson)) {
            $this->addError(validationErrMsg("required", "Shipping address"), "delivery_address_id");
        }

        // Date validation: quote_date for quotations, order_date for orders
        if ($isQuotation) {
            if (empty($quoteDate)) {
                $this->addError(validationErrMsg("required", "Quote date"), "quote_date");
            } elseif (!strtotime($quoteDate)) {
                $this->addError(validationErrMsg("invalid", "Quote date"), "quote_date");
            }
        } else {
            if (empty($orderDate)) {
                $this->addError(validationErrMsg("required", "Order date"), "order_date");
            } elseif (!strtotime($orderDate)) {
                $this->addError(validationErrMsg("invalid", "Order date"), "order_date");
            }
        }

        // Optional date
        if (!empty($expectedDate) && !strtotime($expectedDate)) {
            $this->addError(validationErrMsg("invalid", "Expected delivery date"), "expected_delivery_date");
        }

        // Payment term (optional)
        if ($paymentTermId) {
            $paymentTerm = new Models_PaymentTerm($paymentTermId);
            if ($paymentTerm->isEmpty || $paymentTerm->company_id != $this->context->companyId) {
                $this->addError(validationErrMsg("invalid", "Payment terms"), "payment_term_id");
            }
        }

        // Status
        if (empty($status)) {
            $this->addError(validationErrMsg("required", "Sales order status"), "status");
        }

        // Line items
        $this->validateItems($lineItems);

        // Serial number validation when saving as delivered
        if ($status === 'delivered' && !$this->hasErrors() && $warehouseId > 0) {
            $this->validateSerialNumbersForDelivery($warehouseId, $lineItems);
        }

        // Stock ATP warnings for confirmed/delivered orders (returned to caller, not treated as hard errors)
        $intendedStatus = $status;
        if (!in_array($intendedStatus, ['draft', 'confirmed', 'delivered'])) {
            $intendedStatus = 'draft';
        }
        if ($intendedStatus === 'confirmed' || $intendedStatus === 'delivered') {
            return $this->validateStockForItems($warehouseId, $lineItems);
        }

        return [];
    }


    private function validateItems(array $items): void {

        if (empty($items) || !is_array($items)) {
            $this->addError(validationErrMsg("one_item_required", "line item"), "items");
            return;
        }

        $hasMissingProduct = false;
        $hasInvalidQty = false;
        $hasInvalidPrice = false;
        $productIds = [];
        $itemLevelErrors = [];
        $index = 0;

        $inputUomIds = array_values(array_filter(array_map(fn($i) => (int)($i['uom_id'] ?? 0), $items)));
        $uomDecimalMap = [];
        if ($inputUomIds) {
            $ph = implode(',', array_fill(0, count($inputUomIds), '?'));
            $rows = $this->db->fetchAll(
                "SELECT pu.id, u.allow_decimal, u.name AS uom_name FROM product_uoms pu JOIN uoms u ON u.id = pu.base_uom_id WHERE pu.id IN ($ph)",
                $inputUomIds
            );
            foreach ($rows as $r) {
                $uomDecimalMap[(int)$r->id] = ['allow_decimal' => (bool)(int)$r->allow_decimal, 'name' => $r->uom_name];
            }
        }

        foreach ($items as $item) {

            $row = $index + 1;
            $productId = (int) ($item['product_id'] ?? 0);
            $uomId = (int) ($item['uom_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $taxes = (array) ($item['tax'] ?? []);
            $isProductValid = true;

            if (!$productId) {
                $hasMissingProduct = true;
            } else {
                $product = new Models_Product($productId);
                if ($product->isEmpty || $product->company_id != $this->context->companyId || $product->status !== 'active') {
                    $itemLevelErrors["items.{$index}.invalid_prod"] = validationErrMsg("invalid", "Product at row {$row}");
                    $isProductValid = false;
                }
            }

            if (!isPositiveNumeric($qty)) {
                $hasInvalidQty = true;
            } elseif ($uomId && isset($uomDecimalMap[$uomId]) && !$uomDecimalMap[$uomId]['allow_decimal'] && !isWholeNumber($qty)) {
                $itemLevelErrors["items.{$index}.qty"] = "Quantity must be a whole number for {$uomDecimalMap[$uomId]['name']} at row {$row}";
            }

            if (!isValidPrice($unitPrice)) {
                $hasInvalidPrice = true;
            }

            if ($isProductValid && $productId) {

                if (in_array($productId, $productIds)) {
                    $itemLevelErrors["items.{$index}.duplicate_prod"] = "Duplicate product detected at row {$row}";
                }
                
                $productIds[] = $productId;
            }

            // Validate taxes (sales or both)
            foreach ($taxes as $taxId) {

                $tax = new Models_Tax($taxId);
                if (!(!$tax->isEmpty && $tax->company_id == $this->context->companyId && $tax->status === 'active')) {
                    $itemLevelErrors["items.{$index}.invalid_taxes"] = "One or more taxes are invalid at row {$row}";
                    break;
                }
            }

            $index++;
        }

        if ($hasMissingProduct) {
            $this->addError(validationErrMsg("required", "Each item must have a product selected"), "items.product_id");
        }
        
        if ($hasInvalidQty) {
            $this->addError("Quantity must be greater than zero for all items", "items.qty");
        }
        
        if ($hasInvalidPrice) {
            $this->addError("Unit price cannot be negative for any item", "items.unit_price");
        }

        foreach ($itemLevelErrors as $key => $msg) {
            $this->addError($msg, $key);
        }
    }


    /**
     * Check available stock (on_hand - reserved) for a list of items at a given location.
     * Returns an array of human-readable warning strings for any item with insufficient ATP.
     * Empty array means all items have sufficient stock.
     */
    private function validateStockForItems(int $warehouseId, array $items): array {

        $companyId = $this->context->companyId;
        $warnings  = [];

        foreach ($items as $item) {

            $productId = (int) ($item['product_id'] ?? 0);
            $qty = (float) ($item['qty'] ?? 0);

            $product = new Models_Product($productId);
            if (empty($product->stock_tracking_method) || $product->stock_tracking_method === 'none') {
                continue;
            }

            $productName = $product->name ?: "Product #{$productId}";

            $stock = $this->db->fetchOne(
                "SELECT unrestricted_qty, reserved_qty FROM inv_product_stock WHERE company_id = ? AND warehouse_id = ? AND product_id = ? LIMIT 1",
                [$companyId, $warehouseId, $productId]
            );

            $onHand = $stock ? (float) $stock->unrestricted_qty  : 0;
            $reserved = $stock ? (float) $stock->reserved_qty : 0;
            $availableToSell = $onHand - $reserved;

            if ($availableToSell < $qty) {
                $orderedFormatted = formatQty($qty);
                $onHandFormatted = formatQty($onHand);
                $reservedSuffix = $reserved > 0 ? ' (' . formatQty($reserved) . ' reserved)' : '';
                $warnings[] = "{$productName} — ordered {$orderedFormatted}, on hand {$onHandFormatted}{$reservedSuffix}";
            }
        }

        return $warnings;
    }


    private function validateSerialNumbersForDelivery(int $warehouseId, array $items): void {

        $companyId = $this->context->companyId;
        $index = 0;

        foreach ($items as $item) {

            $productId = (int) ($item['product_id'] ?? 0);
            if (!$productId) { $index++; continue; }

            $product = new Models_Product($productId);
            if ($product->isEmpty || $product->stock_tracking_method !== 'serial') { $index++; continue; }

            $qty = (int) round((float) ($item['qty'] ?? 0));
            $serialNumbers = array_values(array_filter(array_map('trim', (array) ($item['serial_numbers'] ?? []))));

            if (empty($serialNumbers)) {
                $this->addError("Serial numbers are required for {$product->name} when delivering", "so_items.{$index}.serial_numbers");
                $index++;
                continue;
            }

            if (count($serialNumbers) !== $qty) {
                $cnt = count($serialNumbers);
                $this->addError("{$product->name} requires {$qty} serial number(s), got {$cnt}", "so_items.{$index}.serial_numbers");
                $index++;
                continue;
            }

            $placeholders = rtrim(str_repeat('?,', count($serialNumbers)), ',');
            $validSerials = $this->db->fetchCol(
                "SELECT ins.serial_number
                 FROM inv_serials AS ins
                 INNER JOIN inv_serial_stock AS iss ON iss.serial_id = ins.id
                 WHERE ins.company_id = ? AND ins.product_id = ? AND iss.warehouse_id = ?
                   AND ins.serial_number IN ({$placeholders}) AND ins.status = 'in_stock'",
                array_merge([$companyId, $productId, $warehouseId], $serialNumbers)
            );

            $invalid = array_diff($serialNumbers, $validSerials);
            if (!empty($invalid)) {
                $this->addError("Serial(s) not available at selected location: " . implode(', ', $invalid), "so_items.{$index}.serial_numbers");
            }

            $index++;
        }
    }


    private function isUniqueSONumber(string $soNumber, int $soId = 0): bool {

        $companyId = $this->context->companyId;
        $sql = "SELECT count(id) FROM sales_orders WHERE company_id = ? AND so_number = ?";
        $bindings  = [$companyId, trim($soNumber)];
        if ($soId > 0) {
            $sql .= " AND id != ?";
            $bindings[] = $soId;
        }

        $count = $this->db->fetchVar($sql, $bindings);
        
        if ( $count > 0) {
            return false;
        }

        return true;
    }


    /*
    private function validateCustomSoNumber(string $soNumber, int $soId = 0): void {

        if (empty(trim($soNumber))) {
            $this->addError(validationErrMsg("required", "SO number"), "so_number");
            return;
        }

        $companyId = $this->context->companyId;
        $sql = "SELECT count(id) FROM sales_orders WHERE company_id = ? AND so_number = ?";
        $bindings  = [$companyId, trim($soNumber)];
        if ($soId > 0) {
            $sql .= " AND id != ?";
            $bindings[] = $soId;
        }
        
        if ($this->db->fetchVar($sql, $bindings) > 0) {
            $this->addError(validationErrMsg("duplicate", "SO number"), "so_number");
        }
    }
    */


    /**
     * Calculate item-level discount amount from discount_info.
     */
    private function calcItemDiscount(float $lineSubtotal, array $discountInfo): float {

        if (empty($discountInfo)) return 0;

        $type  = $discountInfo['type']  ?? 'fixed';
        $value = (float) ($discountInfo['value'] ?? 0);

        if ($value <= 0) return 0;

        if ($type === 'percent') {
            return round($lineSubtotal * ($value / 100), 4);
        }
        // fixed — cap at line subtotal
        return min($value, $lineSubtotal);
    }


    private function getLineItemsDiff(array $existingItems, array $incomingItems): array {

        $existingById = [];
        foreach ($existingItems as $item) {
            $existingById[(int) $item->id] = $item;
        }

        $itemsToCreate = [];
        $itemsToUpdate = [];
        $itemsToDelete = [];
        $usedIds = [];

        foreach ($incomingItems as $item) {
            $itemId = (int) ($item['id'] ?? 0);
            if ($itemId && isset($existingById[$itemId])) {
                $itemsToUpdate[] = $item;
                $usedIds[]       = $itemId;
            } else {
                $item['id'] = 0;
                $itemsToCreate[] = $item;
            }
        }

        foreach ($existingById as $id => $existingItem) {
            if (!in_array($id, $usedIds)) {
                $itemsToDelete[] = $existingItem;
            }
        }

        return [$itemsToCreate, $itemsToUpdate, $itemsToDelete];
    }


    /**
     * Save line items and return update log + computed SO totals.
     */
    private function saveLineItems(Models_SalesOrder $so, array $lineItems): array {

        if (!$so->id) {
            throw new Service_Exception("Failed to save line items");
        }

        $savedItems = $so->line_items;
        [$itemsToCreate, $itemsToUpdate, $itemsToDelete] = $this->getLineItemsDiff($savedItems, $lineItems);

        $failMsg = "Unable to save the sales order due to an issue with one or more line items";
        $updateLog = [];
        $soSubtotal = 0;
        $soItemDiscounts = 0;
        $soTaxTotal = 0;
        $savedItemBases = [];

        foreach (array_merge($itemsToCreate, $itemsToUpdate) as $item) {

            $itemId = (int) ($item['id'] ?? 0);
            $productId = (int) $item['product_id'];
            $qty = (float) ($item['qty'] ?? 0);
            $unitPrice = (float) ($item['unit_price']  ?? 0);
            $taxes = (array) ($item['tax'] ?? []);
            $description = isset($item['description']) && $item['description'] !== '' ? $item['description'] : null;

            // Discount
            $discountInfoRaw = $item['discount_info'] ?? [];
            if (is_string($discountInfoRaw)) {
                $discountInfoRaw = json_decode($discountInfoRaw, true) ?: [];
            }

            $lineSubtotal = $qty * $unitPrice;
            $itemDiscountAmt = $this->calcItemDiscount($lineSubtotal, $discountInfoRaw);
            $taxableAmount = $lineSubtotal - $itemDiscountAmt;

            // Tax calculation (identical to PO logic)
            $taxAmount  = 0;
            $taxInfo = null;
            if ($taxes) {
                $totalTaxPct = 0;
                $totalFixedTax = 0;
                $taxInfoArr = [];
                foreach ($taxes as $taxId) {
                    $tax = new Models_Tax($taxId);
                    if ($tax->tax_type === 'percentage') {
                        $totalTaxPct += (float) $tax->rate;
                    } else if ($tax->tax_type === 'fixed') {
                        $totalFixedTax += (float) $tax->rate;
                    }
                    $taxInfoArr[] = [
                        'id' => $taxId,
                        'name' => $tax->name,
                        'code' => $tax->code,
                        'type' => $tax->tax_type,
                        'rate' => $tax->rate,
                        'description' => $tax->description,
                    ];
                }
                if ($totalTaxPct) {
                    $taxAmount = $taxableAmount * ($totalTaxPct / 100);
                }
                $taxAmount += $totalFixedTax;
                $taxInfo = json_encode($taxInfoArr, JSON_UNESCAPED_UNICODE);
            }

            $lineTotal = $taxableAmount + $taxAmount;
            $soSubtotal += $lineSubtotal;
            $soItemDiscounts += $itemDiscountAmt;
            $soTaxTotal += $taxAmount;

            // Resolve base UOM
            $uomCode = null;
            $uomId = null;
            $uomRow = $this->db->fetchOne(
                "SELECT b.id, c.code FROM product_uoms AS b
                LEFT JOIN uoms AS c ON c.id = b.base_uom_id
                WHERE 
                    b.company_id = ? AND 
                    b.product_id = ? AND 
                    b.is_base = ? AND 
                    b.status = ? 
                LIMIT 1",
                [$this->context->companyId, $productId, 1, 'active']
            );
            if ($uomRow) {
                $uomId   = $uomRow->id;
                $uomCode = $uomRow->code;
            }

            $product = new Models_Product($productId);
            $discountJson = !empty($discountInfoRaw) ? json_encode($discountInfoRaw, JSON_UNESCAPED_UNICODE) : null;

            $soi = new Models_SalesOrderItem($itemId);
            $oldDetails = $soi->toArray();

            $soi->sales_order_id = $so->id;
            $soi->product_id = $productId;
            $soi->product_name = $product->name;
            $soi->product_sku  = $product->sku;
            $soi->tax_classification_type = $product->master->tax_classification_type;
            $soi->tax_classification_code = $product->master->tax_classification_code;
            $soi->product_uom_id = $uomId;
            $soi->uom_code = $uomCode;
            $soi->description = $description;
            $soi->ordered_qty = $qty;
            $soi->unit_price = $unitPrice;
            $soi->discount_amount = round($itemDiscountAmt, 4);
            $soi->discount_info = $discountJson;
            $soi->tax_amount = round($taxAmount, 4);
            $soi->tax_info = $taxInfo;
            $soi->line_total = round($lineTotal, 4);

            if ($soi->isEmpty) {

                $soi->created_by = $this->context->userId;
                if (!$soi->create()) {
                    throw new Service_Exception($failMsg);
                }

                $updateLog[] = [
                    'event' => 'created',
                    'so_item_id' => $soi->id,
                    'prod_id' => $productId,
                    'prod_name' => $product->name,
                    'new_qty' => formatQty($qty),
                    'new_uom' => $uomCode ?? '',
                    'new_unit_price' => formatCurrency($unitPrice),
                    'new_discount' => $discountJson ? $this->formatDiscountLabel($discountInfoRaw) : null,
                ];

            } else {

                $oldTaxIds = array_column(json_decode($oldDetails['tax_info'] ?? '[]', true) ?: [], 'id');
                $changed = (
                    $oldDetails['product_id'] != $productId ||
                    $oldDetails['description'] != $description ||
                    $oldDetails['ordered_qty'] != $qty ||
                    $oldDetails['unit_price'] != $unitPrice ||
                    $oldDetails['discount_amount'] != $itemDiscountAmt ||
                    array_diff($oldTaxIds, $taxes) ||
                    $oldDetails['line_total'] != $lineTotal
                );
                if ($changed) {

                    if (!$soi->update()) {
                        throw new Service_Exception($failMsg);
                    }

                    $oldDiscountInfo = $oldDetails['discount_info'] ? json_decode($oldDetails['discount_info'], true) : null;

                    $updateLog[] = [
                        'event' => 'updated',
                        'so_item_id' => $soi->id,
                        'prod_id' => $productId,
                        'prod_name' => $product->name,
                        'old_qty' => formatQty($oldDetails['ordered_qty']),
                        'new_qty' => formatQty($qty),
                        'old_uom' => $oldDetails['uom_code'] ?? '',
                        'new_uom' => $uomCode ?? '',
                        'old_unit_price' => formatCurrency($oldDetails['unit_price']),
                        'new_unit_price' => formatCurrency($unitPrice),
                        'old_discount' => $oldDiscountInfo ? $this->formatDiscountLabel($oldDiscountInfo) : null,
                        'new_discount' => $discountInfoRaw ? $this->formatDiscountLabel($discountInfoRaw) : null,
                    ];
                }
            }

            // Track per-item base data for order discount allocation (runs after all items saved)
            $savedItemBases[] = [
                'id'            => $soi->id,
                'subtotal'      => $lineSubtotal,
                'item_discount' => $itemDiscountAmt,
                'has_taxes'     => !empty($taxes),
            ];
        }

        // Delete removed items
        foreach ($itemsToDelete as $del) {

            $soi = new Models_SalesOrderItem($del->id);
            $soi->delete();
            if ($soi->getDeletedRows() <= 0) {
                throw new Service_Exception($failMsg);
            }
            $updateLog[] = [
                'event' => 'deleted',
                'so_item_id' => $del->id,
                'prod_id' => $del->product_id,
                'prod_name' => $del->product_name,
                'old_qty' => formatQty($del->ordered_qty),
                'old_uom' => $del->uom_code ?? '',
                'old_unit_price' => formatCurrency($del->unit_price),
            ];
        }

        return [$updateLog, $soSubtotal, $soItemDiscounts, $soTaxTotal, $savedItemBases];
    }



    /**
     * Update SO totals row (subtotal, discounts, tax, grand total).
     * Each field is independently rounded to 4dp before storage.
     * grand_total is computed from the already-rounded stored values, preventing float drift.
     */
    private function updateSOTotals(int $soId, float $soSubtotal, float $soItemDiscounts, float $soTaxTotal, float $orderDiscountAmt, float $roundOffAmt = 0.0): void {

        $soSubtotal       = round($soSubtotal, 4);
        $itemDiscTotal    = round($soItemDiscounts, 4);
        $subAfterItemDisc = round($soSubtotal - $itemDiscTotal, 4);
        $orderDiscAmt     = round($orderDiscountAmt, 4);
        $discountTotal    = round($itemDiscTotal + $orderDiscAmt, 4);

        // Tax: proportionally reduce by order discount applied on post-item-discount base
        $discountRatio = $subAfterItemDisc > 0 ? $orderDiscAmt / $subAfterItemDisc : 0;
        $adjustedTax   = round(max(0, $soTaxTotal * (1 - $discountRatio)), 4);

        $preRound   = round($subAfterItemDisc - $orderDiscAmt + $adjustedTax, 4);
        $roundOff   = round($roundOffAmt, 4);
        $grandTotal = round($preRound + $roundOff, 4);

        $this->db->update("sales_orders", [
            "subtotal"                     => $soSubtotal,
            "item_discount_total"          => $itemDiscTotal,
            "subtotal_after_item_discount" => $subAfterItemDisc,
            "order_discount_amount"        => $orderDiscAmt,
            "discount_total"               => $discountTotal,
            "tax_amount"                   => $adjustedTax,
            "round_off_amount"             => $roundOff,
            "grand_total"                  => $grandTotal,
        ], "id = {$soId}");
        // Note: adjustment_amount column is kept in DB but intentionally not written here — feature suspended
    }


    /**
     * Compute round-off amount for auto mode on the backend.
     * Returns 0 when mode is 'off' or 'manual' (manual is frontend-driven).
     */
    private function computeAutoRoundOff(float $amount): float {
        $settings = new Service_CompanySettings($this->context);
        $cfg      = $settings->getRoundOffConfig();
        return Service_CompanySettings::computeRoundOff(
            $amount,
            $cfg['mode'],
            (float) $cfg['round_to'],
            $cfg['method']
        );
    }



    /**
     * Compute order-level discount amount from discount_info on the SO.
     * $netSubtotal must be the post-item-discount subtotal so that percentage
     * order discounts are applied on the correct (reduced) base.
     */
    private function calcOrderDiscount(float $netSubtotal, array $discountInfo): float {

        if (empty($discountInfo)) return 0;

        $type = $discountInfo['type']  ?? 'fixed';
        $value = (float) ($discountInfo['value'] ?? 0);

        if ($value <= 0) return 0;

        if ($type === 'percent') {
            return round($netSubtotal * ($value / 100), 4);
        }
        return (float) $value;
    }



    /**
     * Distribute the order-level discount across line items proportionally (residual-to-last method).
     * Updates order_discount_allocated and taxable_amount on every active sales_order_item row.
     *
     * taxable_amount = effective base for tax after all discounts; 0 for non-taxable items.
     * Residual penny goes to the last item so SUM(order_discount_allocated) == $orderDiscountAmt exactly.
     */
    private function allocateOrderDiscountToItems(array $savedItemBases, float $orderDiscountAmt): void {

        if (empty($savedItemBases)) return;

        // Per-item taxable base (subtotal - item discount)
        $bases = [];
        $totalBase = 0;
        foreach ($savedItemBases as $item) {
            $base = max(0, (float)$item['subtotal'] - (float)$item['item_discount']);
            $bases[] = $base;
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
                // Last item absorbs rounding residual — guarantees exact sum
                $allocated = round($orderDiscountAmt - $allocatedSum, 4);
            }

            $itemBase      = $bases[$i];
            $taxableAmount = $item['has_taxes'] ? max(0, $itemBase - $allocated) : 0.0;

            $this->db->update("sales_order_items", [
                'order_discount_allocated' => round($allocated, 4),
                'taxable_amount'           => round($taxableAmount, 4),
            ], "id = {$item['id']}");
        }
    }


    /**
     * Human-readable discount label for history display.
     * e.g. "10%" or "₹500.00"
     */
    private function formatDiscountLabel(array $info): string {

        $type  = $info['type']  ?? 'fixed';
        $value = $info['value'] ?? 0;
        return $type === 'percent' ? "{$value}%" : formatCurrency($value);
    }    


    
    private function createDelivery(Models_SalesOrder $so, $lineItems = []): void {

        $finalItems = $lineItems;
        if( empty($lineItems) ) {

            foreach ($so->line_items as $soItem) {
                $finalItems[] = [
                    'sales_order_item_id' => $soItem->id,
                    'product_id' => $soItem->product_id,
                    'dispatched_qty' => $soItem->ordered_qty,
                    'uom_code' => $soItem->uom_code,
                    'description' => $soItem->description,
                ];
            }
        }        

        if( empty($finalItems) )  {
            throw new Service_Exception("Failed to create delivery note, missing line items");
        }

        $payload = [
            'sales_order_id' => $so->id,
            'customer_id'    => $so->customer_id,
            'warehouse_id'    => $so->source_warehouse_id,
            'status'         => "delivered",
            'fulfilment_type'=> $so->delivery_type ?: 'pickup',
            'instant_delivery' => 1,
            'items'          => $finalItems,
        ];

        $delivery = new Service_So_Delivery($this->context);
        $response = $delivery->create($payload);

        if( $response["success"] !== true ) {
            throw new Service_Exception("Failed to create delivery note");
        }
    }




    public function logHistory(int $soId, array $payload): int {

        $meta = empty($payload['meta']) ? null : json_encode($payload['meta'], JSON_UNESCAPED_UNICODE);

        $history = new Models_SalesOrderHistory();
        $history->company_id = $this->context->companyId;
        $history->sales_order_id = $soId;
        $history->log_type = $payload['log_type'];
        $history->title = $payload['title'];
        $history->reference_type = $payload['reference_type'] ?? null;
        $history->reference_id = $payload['reference_id'] ?? null;
        $history->meta = $meta;
        $history->created_by = $this->context->userId;

        $historyId = $history->create();
        if (!$historyId) {
            throw new Service_Exception("Failed to log sales order history");
        }

        return (int) $historyId;
    }


    public function getFormContext(int $soId = 0, int $leadId = 0): array {

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $soDetails = [];
        $customerShippingAddresses = [];
        if ($soId > 0) {

            $so = $this->getSalesOrderOrFail($soId);
            $soDetails = array_merge(['id' => $soId, 'customer_name' => $so->customer->display_name, 'line_items' => $so->line_items], $so->toArray());

            // Decode SO-level discount_info for JS
            if (isset($soDetails['discount_info']) && $soDetails['discount_info']) {
                $soDetails['discount_info'] = json_decode($soDetails['discount_info'], true);
            }

            // Decode shipping_address_snapshot for JS
            if (!empty($soDetails['shipping_address_snapshot'])) {
                $soDetails['shipping_address_snapshot'] = json_decode($soDetails['shipping_address_snapshot'], true);
            }

            // Customer shipping addresses for address picker
            if ($so->customer_id) {
                $addrRows = $this->db->fetchAll(
                    "SELECT id, address_line1, address_line2, city, state, country, postal_code, attention, phone
                     FROM customer_addresses
                     WHERE company_id = ? AND customer_id = ? AND address_type = 'shipping'
                     ORDER BY is_default DESC, id ASC",
                    [$companyId, $so->customer_id]
                );
                foreach ($addrRows as $addr) {
                    $parts = array_filter([$addr->address_line1, $addr->address_line2, $addr->city, $addr->state, $addr->country]);
                    $customerShippingAddresses[] = [
                        'id'           => $addr->id,
                        'label'        => implode(', ', $parts),
                        'attention'    => $addr->attention,
                        'phone'        => $addr->phone,
                        'address_line1'=> $addr->address_line1,
                        'address_line2'=> $addr->address_line2,
                        'city'         => $addr->city,
                        'state'        => $addr->state,
                        'postal_code'  => $addr->postal_code,
                        'country'      => $addr->country,
                    ];
                }
            }
        }

        // Lead prefill — populate customer from linked lead when creating a quotation from a lead
        $leadPrefill = [];
        if ($leadId > 0 && $soId === 0) {

            $row = $this->db->fetchOne(
                "SELECT l.id, l.customer_id, c.display_name AS customer_name
                 FROM crm_leads AS l
                 LEFT JOIN customers AS c ON c.id = l.customer_id
                 WHERE l.id = ? AND l.company_id = ?",
                [$leadId, $companyId]
            );

            if ($row) {

                $leadPrefill = [
                    'lead_id' => (int) $row->id,
                    'customer_id' => $row->customer_id ? (int) $row->customer_id : null,
                    'customer_name' => $row->customer_name ?: null,                    
                ];
            }
        }

        $warehouses = Service_Company::getActiveWarehouses($companyId);

        // Products with sale_price, UOMs and Taxes
        $sql = "SELECT a.id, a.name, a.sku, a.sale_price, a.stock_tracking_method,
                       b.id AS uom_id, b.name AS uom_name, c.code AS uom_code, b.is_base AS base_uom,
                       e.id AS tax_id, e.rate AS tax_rate, e.tax_type
                FROM products AS a
                LEFT JOIN product_uoms AS b ON b.product_id = a.id AND b.status = 'active'
                LEFT JOIN uoms AS c ON c.id = b.base_uom_id
                LEFT JOIN product_default_taxes as d ON d.product_id = a.id AND d.apply_on = 'sale'
                LEFT JOIN taxes AS e ON e.id = d.tax_id AND e.status = 'active'
                WHERE a.company_id = ? AND a.status = ?";
        $rows = $this->db->fetchAll($sql, [$companyId, 'active']);

        $products = [];
        foreach ($rows as $row) {

            $id = $row->id;
            if (!isset($products[$id])) {
                $products[$id] = [
                    'id' => $id,
                    'name' => $row->name,
                    'sku' => $row->sku,
                    'sale_price' => $row->sale_price,
                    'stock_tracking_method' => $row->stock_tracking_method,
                    'uoms' => [],
                    'taxes' => [],
                ];
            }
            
            if ($row->uom_id) {

                if( !isset($products[$id]['uoms'][$row->uom_id]) ) {
                    $products[$id]['uoms'][$row->uom_id] = [
                        'uom_id' => $row->uom_id,
                        'name' => $row->uom_name,
                        'code' => $row->uom_code,
                        'is_base_uom' => $row->base_uom,
                    ];
                }                
            }

            if ($row->tax_id) {
                if( !isset($products[$id]['uoms'][$row->tax_id]) ) {
                    $products[$id]['taxes'][$row->tax_id] = [
                        'tax_id' => $row->tax_id,
                        'tax_rate' => $row->tax_rate,
                        'tax_type' => $row->tax_type,                    
                    ];
                }                
            }
        }

        $paymentTerm = new Models_PaymentTerm();
        $paymentTerms = $paymentTerm->getAll([], ["company_id" => $companyId, "status" => "active"]);

        $tax = new Models_Tax();
        $salesTaxes = $tax->getAll([], ["company_id" => $companyId, "status" => "active"]);

        $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

        $selectedCustomerId = (int) ($soDetails['customer_id'] ?? ($leadPrefill['customer_id'] ?? 0));
        $customerService = new Service_Customer($this->context);
        $recentCustomers = $customerService->getRecentForOrders($companyId, 10, $selectedCustomerId);

        $settingsSvc = new Service_CompanySettings($this->context);

        return [
            'so_details'                  => $soDetails,
            'customer_shipping_addresses' => $customerShippingAddresses,
            'lead_prefill'                => $leadPrefill,
            'warehouses'                  => $warehouses,
            'suggested_so_number'         => $seqService->nextPreview("sales_orders"),
            'products'                    => array_values($products),
            'payment_terms'               => $paymentTerms,
            'taxes'                       => $salesTaxes,
            'recent_customers'            => $recentCustomers,
            'doc_terms_defaults'          => [
                'quotation'   => (string) $settingsSvc->get('doc_terms.quotation', ''),
                'sales_order' => (string) $settingsSvc->get('doc_terms.sales_order', ''),
            ],
            'quote_validity_days'         => (int) $settingsSvc->get('sales.quote_validity_days', 15),
        ];
    }

    /**
     * Resolve a terms & conditions value for a document: sanitized payload
     * input when the key is present, otherwise the company default.
     */
    private function resolveTermsInput(array $payload, string $key, string $settingKey): ?string {
        $html = array_key_exists($key, $payload)
            ? (string) $payload[$key]
            : (string) (new Service_CompanySettings($this->context))->get($settingKey, '');
        $clean = Helpers_Html::sanitize($html);
        return $clean !== '' ? $clean : null;
    }

    /**
     * Attach a generated PDF to a history event and mirror the attachment
     * list into the event meta (same pattern as PI rfq_sent). Archiving must
     * never break the main action — failures are swallowed.
     */
    private function archivePdfToHistory(int $historyId, string $filename, string $pdfBytes): void {
        try {
            $attachSvc = new Service_Attachment($this->context);
            $attachSvc->saveFromBase64([[
                'name'      => $filename,
                'mime_type' => 'application/pdf',
                'content'   => base64_encode($pdfBytes),
            ]], 'sales_order_history', $historyId);

            $history = $this->db->fetchOne("SELECT meta FROM sales_order_history WHERE id = ?", [$historyId]);
            $meta = ($history && $history->meta) ? (json_decode($history->meta, true) ?: []) : [];
            $meta['attachments'] = $attachSvc->listFor('sales_order_history', $historyId);
            $this->db->update('sales_order_history', ['meta' => json_encode($meta, JSON_UNESCAPED_UNICODE)], "id = {$historyId}");
        } catch (Throwable $e) {
            // swallowed — the document action itself succeeded
        }
    }



    /**
     * Terms-only edit carve-out for confirmed orders: touches no pricing,
     * inventory or status logic, so it stays editable after confirmation.
     * Draft documents edit terms through the normal update() flow.
     */
    public function updateTerms(int $soId, string $soTermsHtml): array {

        if (!$this->context->canDo('sales_orders', 'write')) {
            throw new Service_Exception('You do not have permission to update sales orders', 403);
        }

        $so = $this->getSalesOrderOrFail($soId);

        if (!in_array($so->status, ['confirmed', 'in_progress'])) {
            throw new Service_Exception('Terms can only be edited on confirmed orders. Use Edit for draft documents.', 422);
        }

        $clean = Helpers_Html::sanitize($soTermsHtml);

        $this->db->startTransaction();
        try {
            $so->so_terms = $clean !== '' ? $clean : null;
            if (!$so->update()) {
                throw new Service_Exception('Failed to update terms');
            }

            $this->logHistory($soId, [
                'log_type' => 'updated_details',
                'title'    => 'Terms & Conditions updated',
                'meta'     => [],
            ]);

            $this->db->commit();
        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        return ['so_id' => $soId, 'so_terms' => $so->so_terms];
    }


    public function getDetails(int $soId): array {

        $so = $this->getSalesOrderOrFail($soId);

        $dnCount = (int) $this->db->fetchVar(
            "SELECT COUNT(id) FROM sales_deliveries WHERE company_id = ? AND sales_order_id = ?",
            [$this->context->companyId, $soId]
        );

        // Fetch lead name if linked
        $leadName = null;
        if ($so->lead_id) {            
            $leadRow = $this->db->fetchOne("SELECT display_name FROM crm_leads WHERE id = ? AND company_id = ?", [$so->lead_id, $this->context->companyId]);
            $leadName = $leadRow ? $leadRow->display_name : null;
        }

        $company = new Models_Company($so->company_id);

        $soDetails = array_merge(
            [
                'id' => $soId,
                'customer_name' => $so->customer->display_name,
                'customer_email' => $so->customer->email ?? '',
                'line_items' => $so->line_items,
                'source_warehouse_name' => $so->warehouse->name ?? '',
                'has_deliveries' => $dnCount > 0,
                'lead_name' => $leadName,
                'sender_company_name' => $company->name,
            ],
            $so->toArray()
        );

        // Decode JSON fields for JS
        if ($soDetails['discount_info']) {
            $soDetails['discount_info'] = json_decode($soDetails['discount_info'], true);
        }

        // Decode tax_info on each line item so JS receives a parsed array
        foreach ($soDetails['line_items'] as $lineItem) {
            if (isset($lineItem->tax_info) && is_string($lineItem->tax_info)) {
                $lineItem->tax_info = json_decode($lineItem->tax_info, true) ?: [];
            }
        }

        // Live-compute delivered_qty per SO item from delivered DNs
        $deliveredRows = $this->db->fetchAll(
            "SELECT sdi.sales_order_item_id, SUM(sdi.dispatched_qty) AS delivered_qty
             FROM sales_delivery_items sdi
             JOIN sales_deliveries sd ON sd.id = sdi.sales_delivery_id
             WHERE sd.sales_order_id = ? AND sd.company_id = ? AND sd.status = 'delivered'
             GROUP BY sdi.sales_order_item_id",
            [$soId, $this->context->companyId]
        );
        $deliveredMap = [];
        foreach ($deliveredRows as $row) {
            $deliveredMap[(int) $row->sales_order_item_id] = (float) $row->delivered_qty;
        }

        // Live-compute returned_qty per SO item from active returns
        $returnedRows = $this->db->fetchAll(
            "SELECT ri.reference_item_id AS so_item_id, SUM(ri.return_qty) AS returned_qty
             FROM return_items ri
             JOIN returns r ON r.id = ri.return_id
             WHERE r.reference_type = 'sales_order' AND r.reference_id = ?
               AND r.company_id = ? AND r.status IN ('draft','in_transit','received')
             GROUP BY ri.reference_item_id",
            [$soId, $this->context->companyId]
        );
        $returnedMap = [];
        foreach ($returnedRows as $row) {
            $returnedMap[(int) $row->so_item_id] = (float) $row->returned_qty;
        }

        foreach ($soDetails['line_items'] as $lineItem) {
            $lineItem->delivered_qty = $deliveredMap[(int) $lineItem->id] ?? 0.0;
            $lineItem->returned_qty  = $returnedMap[(int) $lineItem->id]  ?? 0.0;
        }

        return ['so_details' => $soDetails];
    }


    public function create(array $payload): array {

        if (!$this->context->canDo('sales_orders', 'write')) {
            throw new Service_Exception('You do not have permission to create sales orders', 403);
        }

        $stockWarnings = $this->validatePayload($payload);

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        // Resolve default company location before opening the transaction — no writes needed
        $defaultLocationId = Service_Company::getDefaultLocationId($this->context->companyId);
        if (!$defaultLocationId) {
            $this->addError("No default company location is configured. Please set a default location in company settings before creating a sales order.", "company_location_id");
            return ["success" => false, "errors" => $this->getErrors()];
        }

        // Soft gate: return ATP warnings unless user has already acknowledged them
        if (!empty($stockWarnings) && empty($payload['acknowledged_warning'])) {

            return [
                'success' => false,
                'warning' => true,
                'warning_type' => 'low_stock',
                'warnings' => $stockWarnings,
            ];
        }

        $this->db->startTransaction();

        try {

            $companyId = $this->context->companyId;
            $userId = $this->context->userId;

            // SO Number logic
            $soNumberInput = trim($payload['so_number'] ?? '');
            $soNumberSuggested = trim($payload['so_number_suggested'] ?? '');

            $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));
            if (empty($soNumberInput) || $soNumberInput === $soNumberSuggested) {
                $soNumber = $seqService->nextCommit("sales_orders");
            } else {
                $soNumber = $soNumberInput;
                $seqService->advanceCounter("sales_orders", $soNumber);
            }

            // Address snapshots
            $customerId = (int) ($payload['customer_id'] ?? 0);
            $customer = new Models_Customer($customerId);
            $billingSnapshot = json_encode($customer->getBillingAddress(), JSON_UNESCAPED_UNICODE);

            // Delivery type + shipping address snapshot
            $deliveryType = trim($payload['delivery_type'] ?? 'pickup');
            if (!in_array($deliveryType, ['pickup', 'ship'])) $deliveryType = 'pickup';

            $shippingSnapshot = null;
            if ($deliveryType === 'ship') {
                $shippingAddressJson = $payload['shipping_address_json'] ?? null;
                if (!empty($shippingAddressJson)) {
                    $addr = is_string($shippingAddressJson) ? json_decode($shippingAddressJson, true) : (array) $shippingAddressJson;
                    if (is_array($addr) && !empty(array_filter($addr))) {
                        $shippingSnapshot = json_encode($addr, JSON_UNESCAPED_UNICODE);
                    }
                }
            }

            // Order-level discount
            $orderDiscountInfoRaw = $payload['order_discount_info'] ?? [];
            if (is_string($orderDiscountInfoRaw)) {
                $orderDiscountInfoRaw = json_decode($orderDiscountInfoRaw, true) ?: [];
            }

            // Payment terms snapshot
            $paymentTermId = (int) ($payload['payment_term_id'] ?? 0);
            $paymentTermsText = null;
            if ($paymentTermId) {
                $termObj = new Models_PaymentTerm($paymentTermId);
                $paymentTermsText = !$termObj->isEmpty ? $termObj->name : null;
            }

            // SO Status
            $intendedStatus = trim($payload['status'] ?? 'draft');
            if (!in_array($intendedStatus, ['draft', 'confirmed', 'delivered'])) {
                $intendedStatus = 'draft';
            }

            $originType = trim($payload['origin_type'] ?? 'order');
            if (!in_array($originType, ['quotation', 'order'])) $originType = 'order';

            $so = new Models_SalesOrder();
            $so->fillFromArray($payload, ['company_location_id', 'quotation_terms', 'so_terms']);
            $so->company_location_id = $defaultLocationId;
            $so->status = $intendedStatus;
            $so->origin_type = $originType;
            $so->company_id = $companyId;
            $so->created_by = $userId;
            $so->salesperson_id = $userId;
            $so->so_number = $soNumber;
            $so->delivery_type = $deliveryType;
            $so->billing_address_snapshot = $billingSnapshot;
            $so->shipping_address_snapshot = $shippingSnapshot;
            $so->discount_info = !empty($orderDiscountInfoRaw) ? json_encode($orderDiscountInfoRaw, JSON_UNESCAPED_UNICODE) : null;
            $so->payment_terms = $paymentTermsText;

            // Terms & conditions snapshot — sanitized input, or company default when absent
            if ($originType === 'quotation') {
                $so->quotation_terms = $this->resolveTermsInput($payload, 'quotation_terms', 'doc_terms.quotation');
            } else {
                $so->so_terms = $this->resolveTermsInput($payload, 'so_terms', 'doc_terms.sales_order');
            }

            if (!Service_CompanySettings::isMultiWarehouseEnabled($companyId)) {
                $so->source_warehouse_id = Service_Company::getDefaultWarehouseId($companyId) ?? 0;
            }

            // Enforce date separation: quotations use quote_date, orders use order_date
            if ($originType === 'quotation') {
                $so->order_date = null;
            } else {
                $so->quote_date = null;
            }


            $soId = $so->create();
            if (!$soId) {
                throw new Service_Exception("Failed to create sales order");
            }

            // refresh Sales Order Object
            //$so->refreshById($soId);

            // save line items
            $lineItems = (array) ($payload['so_items'] ?? []);
            [$updateLog, $soSubtotal, $soItemDiscounts, $soTaxTotal, $savedItemBases] = $this->saveLineItems($so, $lineItems);

            // Order discount % is applied on post-item-discount subtotal (accounting standard)
            $netSubtotal      = $soSubtotal - $soItemDiscounts;
            $orderDiscountAmt = $this->calcOrderDiscount($netSubtotal, $orderDiscountInfoRaw);
            $this->allocateOrderDiscountToItems($savedItemBases, $orderDiscountAmt);

            // Round-off: auto mode computed on backend; manual mode trusts frontend-submitted value
            $roCfg        = (new Service_CompanySettings($this->context))->getRoundOffConfig();
            $preRoundTotal = ($soSubtotal - $soItemDiscounts) - $orderDiscountAmt
                             + (($soSubtotal - $soItemDiscounts) > 0
                                ? max(0, $soTaxTotal * (1 - ($orderDiscountAmt / ($soSubtotal - $soItemDiscounts))))
                                : $soTaxTotal);
            if ($roCfg['mode'] === 'auto') {
                $roundOffAmt = Service_CompanySettings::computeRoundOff($preRoundTotal, $roCfg['mode'], (float) $roCfg['round_to'], $roCfg['method']);
            } else {
                $roundOffAmt = round((float) ($payload['round_off_amount'] ?? 0), 4);
            }

            $this->updateSOTotals($soId, $soSubtotal, $soItemDiscounts, $soTaxTotal, $orderDiscountAmt, $roundOffAmt);


            // Log SO create event
            $soStatusForLog = $intendedStatus === 'draft'
                ? ($originType === 'quotation' ? 'quotation' : 'draft')
                : $intendedStatus;
            $createTitle = $originType === 'quotation' ? 'Quotation created #' . $soNumber : 'Order created #' . $soNumber;
            $this->logHistory($soId, [
                'log_type' => 'created',
                'title' => $createTitle,
                'meta' => [
                    'so_number' => $soNumber,
                    'status' => $soStatusForLog,
                    'customer_name' => $customer->display_name,
                    'items_count' => count($lineItems),
                ],
            ]);
            
            
            // refresh Sales Order Object
            $so->refreshById($soId);

            if ($intendedStatus === 'confirmed') {

                // reserve stock if created SO is confirmed
                $savedLineItems = $this->db->fetchAll(
                    "SELECT id, product_id, ordered_qty FROM sales_order_items WHERE sales_order_id = ?",
                    [$soId]
                );
                $reserveItems = array_map(fn($item) => [
                    'product_id'  => (int) $item->product_id,
                    'warehouse_id' => (int) $so->source_warehouse_id,
                    'qty'         => (float) $item->ordered_qty,
                    'line_id'     => (int) $item->id,
                ], $savedLineItems);
                (new Service_Inv_Stock($this->context))->reserveForDocument(
                    $reserveItems, 'sales_order', (int) $soId, $soNumber
                );

                $this->stampPlannedCost((int) $soId);
            }
            else if ($intendedStatus === 'delivered') {                

                // prepare line items array with uncommited line items
                $savedItemsByProdId = [];
                foreach($updateLog as $row) {
                    $savedItemsByProdId[$row["prod_id"]] = $row;
                }

                $finalLineItems = [];
                foreach($lineItems as $row) {
                    $prodId = $row["product_id"] ?? 0;
                    if( $prodId && isset($savedItemsByProdId[$prodId]) && $savedItemsByProdId[$prodId] ) {
                        $finalLineItems[] = [
                            'sales_order_item_id' => $savedItemsByProdId[$prodId]["so_item_id"],
                            'product_id' => $prodId,
                            'dispatched_qty' => $row["qty"],
                            'uom_code' => $savedItemsByProdId[$prodId]["new_uom"] ?? null,
                            'description' => $row["description"],
                            'serial_numbers' => array_values(array_filter((array) ($row['serial_numbers'] ?? []))),
                        ];
                    }
                }

                $this->stampPlannedCost((int) $soId);
                $this->createDelivery($so, $finalLineItems);
            }

            // If created from a lead, log the quotation event on the lead
            $soLeadId = (int) $so->lead_id;
            if ($soLeadId > 0) {

                $crmLeadService = new Service_Crm_Lead($this->context);
                $crmLeadService->logHistory($soLeadId, [
                    'log_type' => 'quotation_created',
                    'title' => 'Quotation created #' . $soNumber,
                    'meta' => ['so_id' => $soId, 'so_number' => $soNumber],
                ]);                
            }

            $this->db->commit();

            return ["success" => true, "data" => ["so_id" => $soId, "so_number" => $soNumber]];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function update(int $soId, array $payload): array {

        if (!$this->context->canDo('sales_orders', 'write')) {
            throw new Service_Exception('You do not have permission to update sales orders', 403);
        }

        $so = $this->getSalesOrderOrFail($soId);

        if ($so->status !== 'draft') {
            throw new Service_Exception("This sales order cannot be edited because it is no longer in draft status", 422);
        }

        $stockWarnings = $this->validatePayload($payload, $soId);

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        // Soft gate: return ATP warnings unless user has already acknowledged them
        if (!empty($stockWarnings) && empty($payload['acknowledged_warning'])) {
            return [
                'success' => false,
                'warning' => true,
                'warning_type' => 'low_stock',
                'warnings' => $stockWarnings,
            ];
        }

        $this->db->startTransaction();

        try {

            $oldSODetails = $so->toArray();

            // Order-level discount
            $orderDiscountInfoRaw = $payload['order_discount_info'] ?? [];
            if (is_string($orderDiscountInfoRaw)) {
                $orderDiscountInfoRaw = json_decode($orderDiscountInfoRaw, true) ?: [];
            }

            // Payment terms snapshot
            $paymentTermId = (int) ($payload['payment_term_id'] ?? 0);
            $paymentTermsText = null;
            if ($paymentTermId) {
                $termObj = new Models_PaymentTerm($paymentTermId);
                $paymentTermsText = !$termObj->isEmpty ? $termObj->name : null;
            }

            // Delivery type + shipping address snapshot
            $deliveryType = trim($payload['delivery_type'] ?? $so->delivery_type ?? 'pickup');
            if (!in_array($deliveryType, ['pickup', 'ship'])) $deliveryType = 'pickup';

            $shippingSnapshot = null;
            if ($deliveryType === 'ship') {
                $shippingAddressJson = $payload['shipping_address_json'] ?? null;
                if (!empty($shippingAddressJson)) {
                    $addr = is_string($shippingAddressJson) ? json_decode($shippingAddressJson, true) : (array) $shippingAddressJson;
                    if (is_array($addr) && !empty(array_filter($addr))) {
                        $shippingSnapshot = json_encode($addr, JSON_UNESCAPED_UNICODE);
                    }
                }
            }

            $so->fillFromArray($payload, ['id', 'so_number', 'company_id', 'company_location_id', 'created_at', 'created_by', 'salesperson_id', 'billing_address_snapshot', 'shipping_address_snapshot', 'delivery_type', 'origin_type', 'converted_at', 'quote_sent', 'quote_sent_at', 'lead_id', 'quotation_terms', 'so_terms']);
            $so->delivery_type = $deliveryType;
            $so->shipping_address_snapshot = $shippingSnapshot;
            $so->discount_info  = !empty($orderDiscountInfoRaw) ? json_encode($orderDiscountInfoRaw, JSON_UNESCAPED_UNICODE) : null;
            $so->payment_terms  = $paymentTermsText;

            // Terms & conditions — sanitized, column depends on document phase
            // (status is guaranteed draft here, so origin quotation == open quotation)
            if ($so->origin_type === 'quotation') {
                if (array_key_exists('quotation_terms', $payload)) {
                    $so->quotation_terms = $this->resolveTermsInput($payload, 'quotation_terms', 'doc_terms.quotation');
                }
            } elseif (array_key_exists('so_terms', $payload)) {
                $so->so_terms = $this->resolveTermsInput($payload, 'so_terms', 'doc_terms.sales_order');
            }

            if (!Service_CompanySettings::isMultiWarehouseEnabled($this->context->companyId)) {
                $so->source_warehouse_id = Service_Company::getDefaultWarehouseId($this->context->companyId) ?? 0;
            }

            if (!$so->update()) {
                throw new Service_Exception("Failed to update sales order");
            }

            $newSODetails = $so->toArray();

            // Log changed header fields
            $trackFields = [
                'customer_id' => 'Customer',
                'source_warehouse_id' => 'Source Warehouse',
                'quote_date' => 'Quote date',
                'order_date' => 'Order date',
                'expected_delivery_date' => 'Expected delivery date',
                'reference' => 'Reference',
                'payment_terms' => 'Payment terms',
                'notes' => 'Notes',
                'internal_notes' => 'Internal notes',
                'discount_info' => 'Order discount',
            ];

            $updatedDetails = [];
            foreach ($trackFields as $field => $label) {
                
                $oldVal = $oldSODetails[$field] ?? '';
                $newVal = $newSODetails[$field] ?? '';

                if( $field === 'discount_info' ) {

                } else {
                    if ($oldVal != $newVal) {

                        if( $field === "source_warehouse_id" ) {

                            $oldLocation = new Models_InvWarehouse($oldVal);
                            $oldVal = $oldLocation->name ?: $oldVal;

                            $newLocation = new Models_InvWarehouse($newVal);
                            $newVal = $newLocation->name ?: $newVal;
                        }
                        else if( $field === "customer_id" ) {
                            
                            $oldCustomer = new Models_Customer($oldVal);
                            $oldVal = $oldCustomer->display_name ?: $oldVal;

                            $newCustomer = new Models_Customer($newVal);
                            $newVal = $newCustomer->display_name ?: $newVal;
                        }

                        $updatedDetails[] = [
                            'field'   => $field,
                            'label'   => $label,
                            'old_val' => $oldVal,
                            'new_val' => $newVal,
                        ];
                    }
                }
            }

            $isOpenQuotation = ($so->origin_type === 'quotation' && $so->status === 'draft');

            if (!empty($updatedDetails)) {
                $this->logHistory($soId, [
                    'log_type' => 'updated_details',
                    'title' => $isOpenQuotation ? 'Quotation details updated' : 'Sales order details updated',
                    'meta' => $updatedDetails,
                ]);
            }

            $lineItems = (array) ($payload['so_items'] ?? []);
            [$updateLog, $soSubtotal, $soItemDiscounts, $soTaxTotal, $savedItemBases] = $this->saveLineItems($so, $lineItems);

            // Order discount % is applied on post-item-discount subtotal (accounting standard)
            $netSubtotal      = $soSubtotal - $soItemDiscounts;
            $orderDiscountAmt = $this->calcOrderDiscount($netSubtotal, $orderDiscountInfoRaw);
            $this->allocateOrderDiscountToItems($savedItemBases, $orderDiscountAmt);

            // Round-off: auto mode computed on backend; manual mode trusts frontend-submitted value
            $roCfg        = (new Service_CompanySettings($this->context))->getRoundOffConfig();
            $preRoundTotal = ($soSubtotal - $soItemDiscounts) - $orderDiscountAmt
                             + (($soSubtotal - $soItemDiscounts) > 0
                                ? max(0, $soTaxTotal * (1 - ($orderDiscountAmt / ($soSubtotal - $soItemDiscounts))))
                                : $soTaxTotal);
            if ($roCfg['mode'] === 'auto') {
                $roundOffAmt = Service_CompanySettings::computeRoundOff($preRoundTotal, $roCfg['mode'], (float) $roCfg['round_to'], $roCfg['method']);
            } else {
                $roundOffAmt = round((float) ($payload['round_off_amount'] ?? 0), 4);
            }

            $this->updateSOTotals($soId, $soSubtotal, $soItemDiscounts, $soTaxTotal, $orderDiscountAmt, $roundOffAmt);


            if (!empty($updateLog)) {
                $this->logHistory($soId, [
                    'log_type' => 'updated_line_items',
                    'title' => $isOpenQuotation ? 'Quotation line items updated' : 'Line items updated',
                    'meta' => $updateLog,
                ]);
            }

            $this->db->commit();

            return ["success" => true, "data" => ["so_id" => $soId, "so_number" => $so->so_number]];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function updateStatus(int $soId, array $payload): array {

        $status = trim($payload['status'] ?? '');

        $requiredAction = match($status) {
            'cancelled' => 'cancel',
            'confirmed' => 'confirm',
            default     => 'write',
        };
        if (!$this->context->canDo('sales_orders', $requiredAction)) {
            throw new Service_Exception('You do not have permission to perform this action on sales orders', 403);
        }

        $companyId = $this->context->companyId;

        $so = $this->getSalesOrderOrFail($soId);
        $notes = trim($payload['notes']  ?? '');

        $allowedTransitions = [
            'draft'       => ['confirmed', 'cancelled', 'delivered'],
            'confirmed'   => ['in_progress', 'cancelled', 'delivered'],
            'in_progress' => ['cancelled', 'delivered'],
        ];

        $oldStatus = $so->status;

        if (!isset($allowedTransitions[$oldStatus]) || !in_array($status, $allowedTransitions[$oldStatus])) {
            throw new Service_Exception("Cannot transition sales order from '{$oldStatus}' to '{$status}'", 422);
        }        

        // ATP soft gate before opening transaction (confirm or instant deliver)
        if (in_array($status, ['confirmed', 'delivered'])) {

            $stockItems = array_map(fn($i) => [
                'product_id' => $i->product_id,
                'qty'        => $i->ordered_qty,
            ], $so->line_items);

            $stockWarnings = $this->validateStockForItems($so->source_warehouse_id, $stockItems);

            if (!empty($stockWarnings) && empty($payload['acknowledged_warning'])) {
                return [
                    'success' => false,
                    'warning' => true,
                    'warning_type' => 'low_stock',
                    'warnings' => $stockWarnings,
                ];
            }
        }

        // Guard: check for received returns before cancellation
        if ($status === 'cancelled') {
            $receivedReturnCount = $this->db->fetchOne(
                "SELECT COUNT(id) AS cnt FROM returns
                 WHERE company_id = ? AND reference_id = ? AND reference_type = 'sales_order' AND status = 'received'",
                [$companyId, $soId]
            );
            if ($receivedReturnCount && (int) $receivedReturnCount->cnt > 0) {
                throw new Service_Exception(
                    "Cannot cancel: this order has received returns. Reverse the returns before cancelling.",
                    422
                );
            }
        }

        // Guard: check for active DNs before cancellation
        $draftDnIds = [];
        if ($status === 'cancelled') {
            $activeDns = $this->db->fetchAll(
                "SELECT id, dn_number, status FROM sales_deliveries
                 WHERE company_id = ? AND sales_order_id = ? AND status NOT IN ('cancelled', 'returned', 'lost')",
                [$companyId, $soId]
            );

            $hasBlocker = false;
            $draftDns   = [];
            foreach ($activeDns as $dn) {
                if (in_array($dn->status, ['dispatched', 'delivered'])) {
                    $hasBlocker = true;
                } elseif ($dn->status === 'draft') {
                    $draftDns[]   = ['id' => (int) $dn->id, 'dn_number' => $dn->dn_number];
                    $draftDnIds[] = (int) $dn->id;
                }
            }

            if ($hasBlocker) {
                throw new Service_Exception(
                    "Cannot cancel: one or more delivery notes have already been dispatched or delivered.",
                    422
                );
            }

            if (!empty($draftDns) && empty($payload['acknowledged_draft_dns'])) {
                return [
                    'success'      => false,
                    'warning'      => true,
                    'warning_type' => 'draft_dns',
                    'draft_dns'    => $draftDns,
                ];
            }
        }

        // Pre-render the quotation PDF while the row is still an open quotation —
        // after conversion it can no longer be produced. Archived after commit.
        $conversionPdf = null;
        if ($status === 'confirmed' && $so->origin_type === 'quotation' && $oldStatus === 'draft') {
            try {
                $conversionPdf = [
                    'bytes'    => $this->renderPdf($soId),
                    'filename' => $so->so_number . '-Quotation.pdf',
                ];
            } catch (Throwable $e) {
                $conversionPdf = null; // never block confirmation on a PDF issue
            }
        }

        $this->db->startTransaction();

        try {

            $statusLabels = [
                'draft' => 'Quotation',
                'confirmed' => 'Confirmed',
                'cancelled' => 'Cancelled',
                'partially_dispatched' => 'Partially Dispatched',
                'dispatched' => 'Dispatched',
                'partially_delivered'  => 'Partially Delivered',
                'delivered' => 'Delivered',
            ];

            // --- Instant deliver ---
            if ($status === 'delivered') {

                $dnCount = (int) $this->db->fetchVar("SELECT COUNT(id) FROM sales_deliveries WHERE company_id = ? AND sales_order_id = ?",[$companyId, $soId]);
                if ($dnCount > 0) {
                    throw new Service_Exception("This order already has delivery notes. Manage delivery through the Deliveries module.", 422);
                }

                // create delivery and it will recalculate SO status automatically    
                $this->createDelivery($so);
            }
            else {

                // On quotation conversion: set order_date and converted_at
                if ($status === 'confirmed' && $so->origin_type === 'quotation') {
                    $so->order_date   = dateNow('Y-m-d');
                    $so->converted_at = date('Y-m-d H:i:s');
                    // SO-phase terms from company default; quotation_terms stays frozen
                    $so->so_terms = $this->resolveTermsInput([], 'so_terms', 'doc_terms.sales_order');
                }

                // Reserve stock on confirm SO
                if ($status === 'confirmed') {

                    $reserveItems = array_map(fn($item) => [
                        'product_id'  => (int) $item->product_id,
                        'warehouse_id' => (int) $so->source_warehouse_id,
                        'qty'         => (float) $item->ordered_qty,
                        'line_id'     => (int) $item->id,
                    ], $so->line_items);
                    (new Service_Inv_Stock($this->context))->reserveForDocument(
                        $reserveItems, 'sales_order', $soId, $so->so_number
                    );

                    $this->stampPlannedCost($soId);
                }

                // Release stock when confirmed order is cancelled
                if ($status === 'cancelled' && $oldStatus === 'confirmed') {
                    (new Service_Inv_Stock($this->context))->releaseForDocument('sales_order', $soId);
                }

                // Cancel any draft DNs within the same transaction
                if ($status === 'cancelled' && !empty($draftDnIds)) {
                    $deliveryService = new Service_So_Delivery($this->context);
                    foreach ($draftDnIds as $dnId) {
                        $deliveryService->cancelDraftForSoCancel($dnId);
                    }
                }

                $so->status = $status;
                if (!$so->update()) {
                    throw new Service_Exception("Failed to update sales order status");
                }
            }

            $isQuotationConversion = ($status === 'confirmed' && $oldStatus === 'draft' && $so->origin_type === 'quotation');
            $statusChangeTitle = $isQuotationConversion
                ? 'Quote converted to Order #' . $so->so_number
                : 'Status changed to ' . ($statusLabels[$status] ?? $status);

            $statusHistoryId = $this->logHistory($soId, [
                'log_type' => 'status_changed',
                'title' => $statusChangeTitle,
                'meta' => [
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                    'old_status_label' => $statusLabels[$oldStatus] ?? $oldStatus,
                    'new_status_label' => $statusLabels[$status] ?? $status,
                    'notes' => $notes,
                ],
            ]);


            
            // If created from a lead, log order confirmation & cancelled status update
            $soLeadId = (int) $so->lead_id;
            if ( $soLeadId && in_array($status, ['confirmed', 'cancelled']) ) {

                $leadLogTitle = "Sales Order {$status} #" . $so->so_number;
                if( $oldStatus === 'draft' && $status === 'cancelled' ) {
                    $leadLogTitle = "Quotation cancelled #" . $so->so_number;
                }

                $crmLeadService = new Service_Crm_Lead($this->context);
                $crmLeadService->logHistory($soLeadId, [
                    'log_type' => "quotation_{$status}",
                    'title' => $leadLogTitle,
                    'meta' => ['so_id' => $soId, 'so_number' =>$so->so_number],
                ]);                
            }
            
            $this->db->commit();

            // Archive the pre-conversion quotation PDF on the status-change event
            if ($conversionPdf !== null) {
                $this->archivePdfToHistory($statusHistoryId, $conversionPdf['filename'], $conversionPdf['bytes']);
            }

            return ["success" => true, "data" => ["so_id" => $soId, "status" => $status, "old_status" => $oldStatus]];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function getHistory(int $soId): array {

        $this->getSalesOrderOrFail($soId);

        $sql = "SELECT a.*, b.name AS performed_by
                FROM sales_order_history AS a
                LEFT JOIN users AS b ON b.id = a.created_by
                WHERE a.company_id = ? AND a.sales_order_id = ?
                ORDER BY a.id DESC";
        $rows = $this->db->fetchAll($sql, [$this->context->companyId, $soId]);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'log_type'       => $row->log_type,
                'title'          => $row->title,
                'reference_type' => $row->reference_type,
                'reference_id'   => $row->reference_id,
                'meta'           => json_decode($row->meta ?? '[]', true) ?: [],
                'performed_by'   => $row->performed_by,
                'date_time'      => formatMySqlDate($row->created_at),
            ];
        }

        return $data;
    }


    /**
     * Stamps planned_cost on all SO line items at the moment of SO confirmation.
     * Uses COALESCE(current_cost, cost_price) so AVCO products use live WAC
     * and Standard Price products fall back to their manually-set cost.
     */
    private function stampPlannedCost(int $soId): void {
        $this->db->query(
            "UPDATE sales_order_items soi
             JOIN products p ON p.id = soi.product_id
             SET soi.planned_cost = COALESCE(p.current_cost, p.cost_price, 0)
             WHERE soi.sales_order_id = ?",
            [$soId]
        );
    }


    /**
     * Recalculates actual_cost on each SO line item as the weighted-average unit cost
     * across all active (non-cancelled / non-returned) delivery lines for that item.
     * Called by Service_So_Delivery after any dispatch or revert-to-draft.
     */
    public function recalculateSoItemActualCosts(int $soId): void {
        $this->db->query(
            "UPDATE sales_order_items soi
             LEFT JOIN (
                 SELECT sdi.sales_order_item_id,
                        SUM(sdi.unit_cost * sdi.dispatched_qty) / NULLIF(SUM(sdi.dispatched_qty), 0) AS wac
                 FROM sales_delivery_items sdi
                 INNER JOIN sales_deliveries sd ON sd.id = sdi.sales_delivery_id
                 WHERE sd.sales_order_id = ?
                   AND sd.status IN ('dispatched', 'delivered', 'lost')
                   AND sdi.unit_cost IS NOT NULL
                 GROUP BY sdi.sales_order_item_id
             ) AS cost_agg ON cost_agg.sales_order_item_id = soi.id
             SET soi.actual_cost = cost_agg.wac
             WHERE soi.sales_order_id = ?",
            [$soId, $soId]
        );
    }


    public function searchCustomers(string $query): array {

        $companyId = $this->context->companyId;
        $like = '%' . $query . '%';

        $settingsSvc = new Service_CompanySettings($this->context);
        $searchBy = json_decode($settingsSvc->get('sales.customer_search_by', '["name","gstin"]'), true) ?: ['name', 'gstin'];

        // Map setting keys to actual DB columns
        $fieldMap = [
            'name'  => ['display_name', 'company_name', 'first_name', 'last_name', 'customer_code'],
            'gstin' => ['gstin'],
            'email' => ['email'],
            'phone' => ['phone'],
        ];

        $conditions = [];
        $bindings = [$companyId];

        foreach ($searchBy as $field) {
            foreach ($fieldMap[$field] ?? [] as $col) {
                $conditions[] = "{$col} LIKE ?";
                $bindings[] = $like;
            }
        }

        if (empty($conditions)) {
            $conditions = ['display_name LIKE ?'];
            $bindings[] = $like;
        }

        $sql = "SELECT id, display_name, gstin, email, phone
                FROM customers
                WHERE company_id = ? AND status = 'active'
                  AND (" . implode(' OR ', $conditions) . ")
                ORDER BY display_name ASC
                LIMIT 25";

        $rows = $this->db->fetchAll($sql, $bindings);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id'           => $row->id,
                'display_name' => $row->display_name,
                'gstin'        => $row->gstin,
                'email'        => $row->email,
                'phone'        => $row->phone,
            ];
        }

        return $data;
    }


    public function buildPrintData(int $soId): array {

        $so = new Models_SalesOrder($soId);
        if ($so->isEmpty || $so->company_id != $this->context->companyId) {
            throw new Service_Exception("Sales order not found", 404);
        }

        // Company profile
        $company = $this->db->fetchOne("SELECT name, legal_name, email, phone, website, address, city, state, country, zipcode, gstin, pan, tan, cin, logo_path, signature_path FROM companies WHERE id = ?", [$this->context->companyId]);

        // Salesperson name
        $salesperson = null;
        if ($so->salesperson_id) {
            $spRow = $this->db->fetchOne("SELECT name AS full_name FROM users WHERE id = ? AND company_id = ?", [$so->salesperson_id, $this->context->companyId]);
            $salesperson = $spRow ? trim($spRow->full_name) : null;
        }

        // Billing address — prefer snapshot stored on SO, fallback to live customer address
        $billingAddress = null;
        if (!empty($so->billing_address_snapshot)) {
            $billingAddress = json_decode($so->billing_address_snapshot, true);
        }
        if (empty($billingAddress)) {
            $customer = new Models_Customer($so->customer_id);
            $billingAddress = !$customer->isEmpty ? $customer->getBillingAddress() : [];
        }

        // Line items
        $lineItems = [];
        foreach ($so->line_items as $item) {
            $taxes = is_array($item->tax_info) ? $item->tax_info : [];
            $taxLabel = '';
            if (!empty($taxes)) {
                $taxParts = array_map(fn($t) => $t->name ?? '', $taxes);
                $taxLabel = implode(', ', array_filter($taxParts));
            }

            $discountInfo = is_array($item->discount_info)
                ? $item->discount_info
                : (is_object($item->discount_info) ? (array) $item->discount_info : []);

            $lineItems[] = [
                'product_name'             => $item->product_name,
                'description'              => $item->description,
                'qty'                      => $item->ordered_qty,
                'uom_code'                 => $item->uom_code,
                'unit_price'               => $item->unit_price,
                'discount_info'            => $discountInfo,
                'discount'                 => $item->discount_amount,
                'order_discount_allocated' => $item->order_discount_allocated,
                'taxable_amount'           => $item->taxable_amount,
                'tax_info'                 => $taxes,
                'tax_label'                => $taxLabel,
                'tax_amount'               => $item->tax_amount,
                'line_total'               => $item->line_total,
            ];
        }

        // Shipping address
        $shippingAddress = [];
        if ($so->delivery_type === 'ship' && !empty($so->shipping_address_snapshot)) {
            $shippingAddress = json_decode($so->shipping_address_snapshot, true) ?: [];
        }

        return [
            'company'          => $company ? (array) $company : [],
            'so'               => [
                'id'                          => $so->id,
                'so_number'                   => $so->so_number,
                'origin_type'                 => $so->origin_type,
                'status'                      => $so->status,
                'delivery_type'               => $so->delivery_type,
                'quote_date'                  => $so->quote_date,
                'valid_until'                 => $so->valid_until,
                'order_date'                  => $so->order_date,
                'converted_at'                => $so->converted_at,
                'expected_delivery_date'      => $so->expected_delivery_date,
                'payment_terms'               => $so->payment_terms,
                'reference'                   => $so->reference,
                'notes'                       => $so->notes,
                'quotation_terms'             => $so->quotation_terms ?? null,
                'so_terms'                    => $so->so_terms ?? null,
                'subtotal'                    => $so->subtotal,
                'item_discount_total'         => $so->item_discount_total,
                'subtotal_after_item_discount'=> $so->subtotal_after_item_discount,
                'order_discount_amount'       => $so->order_discount_amount,
                'discount_total'              => $so->discount_total,
                'tax_amount'                  => $so->tax_amount,
                'round_off_amount'            => $so->round_off_amount,
                // 'adjustment_label'         => $so->adjustment_label,   // suspended
                // 'adjustment_amount'        => $so->adjustment_amount,  // suspended
                'grand_total'                 => $so->grand_total,
            ],
            'customer'         => ['name' => $so->customer->display_name ?? ''],
            'billing_address'  => $billingAddress,
            'shipping_address' => $shippingAddress,
            'salesperson'      => $salesperson,
            'line_items'       => $lineItems,
        ];
    }


    public function renderPdf(int $soId): string
    {
        $data = $this->buildPrintData($soId);

        $status     = $data['so']['status'] ?? '';
        $originType = $data['so']['origin_type'] ?? '';
        $watermark  = null;
        if ($status === 'draft' && $originType !== 'quotation') {
            $watermark = 'DRAFT';
        } elseif ($status === 'cancelled') {
            $watermark = 'CANCELLED';
        }

        $isQuotation = ($data['so']['origin_type'] ?? 'order') === 'quotation' && ($data['so']['status'] ?? '') === 'draft';
        $docType     = $isQuotation ? 'quotation' : 'sales_order';
        $templateKey = (new Service_EmailConfig($this->context))->getPdfTemplate($docType, new Service_CompanySettings($this->context));
        $registry    = config("pdf_templates.{$docType}", []);
        $view        = $registry[$templateKey]['view'] ?? $registry['template_1']['view'] ?? ($isQuotation ? 'pdf.quotation' : 'pdf.sales-order');

        return Helpers_Pdf::render($view, ['printData' => $data], ['watermark' => $watermark]);
    }


    public function buildPdf(int $soId): array
    {
        $so = $this->getSalesOrderOrFail($soId);

        $scope  = (new Service_Scope($this->context))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by']);
        $sql    = "SELECT so.id FROM sales_orders so WHERE so.id = ? AND so.company_id = ?";
        $params = [$soId, $this->context->companyId];
        if ($scope['sql']) {
            $sql   .= " AND (" . $scope['sql'] . ")";
            $params = array_merge($params, $scope['bindings']);
        }
        if (!$this->db->fetchOne($sql, $params)) {
            throw new Service_Exception("You do not have access to this sales order.", 403);
        }

        $isQuotation = $so->origin_type === 'quotation' && $so->status === 'draft';

        return [
            'bytes'    => $this->renderPdf($soId),
            'filename' => $isQuotation ? $so->so_number . '-Quotation.pdf' : $so->so_number . '.pdf',
        ];
    }


    public function getEmailDefaults(int $soId): array
    {
        if (!$this->context->canDo('sales_orders', 'send_email')) {
            throw new Service_Exception('You do not have permission to send sales order emails', 403);
        }
        $so = $this->db->fetchOne(
            "SELECT status, origin_type FROM sales_orders WHERE id = ? AND company_id = ?",
            [$soId, $this->context->companyId]
        );
        if (!$so) {
            throw new Service_Exception('Sales order not found', 404);
        }
        $docType  = ($so->origin_type === 'quotation' && $so->status === 'draft') ? 'quotation' : 'sales_order';
        $emailSvc = new Service_EmailConfig($this->context);
        return $emailSvc->getEmailDefaults($docType, $soId);
    }


    public function generateEmailPdf(int $soId): array
    {
        $pdf = $this->buildPdf($soId);

        return [
            'name' => $pdf['filename'],
            'mime_type' => 'application/pdf',
            'content' => base64_encode($pdf['bytes']),
        ];
    }


    public function sendEmail(int $soId, array $payload): array {

        if (!$this->context->canDo('sales_orders', 'send_email')) {
            throw new Service_Exception('You do not have permission to send sales order emails', 403);
        }

        $salesOrder = $this->getSalesOrderOrFail($soId);

        $company = new Models_Company($salesOrder->company_id);

        $to = trim($payload['to'] ?? '');
        $cc = trim($payload['cc'] ?? '');
        $subject = trim($payload['subject'] ?? '');
        $body = trim($payload['body'] ?? '');

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
        $docConfig   = $emailConfig->getDocConfig('sales_order');
        $resolved    = $emailConfig->resolveFrom($docConfig, $this->context->userId);
        $from        = "{$resolved['name']}<{$resolved['email']}>";

        $mailer = new Helpers_Mailer();

        if (!empty($cc)) {
            $mailer->addCC($cc);
        }

        if (!empty($bcc)) {
            $mailer->addBCC($bcc);
        }

        // Attach uploaded files (each item: {name, mime_type, content} with base64 content)
        $attachments = (array) ($payload['attachments'] ?? []);
        foreach ($attachments as $att) {
            $name     = $att['name'] ?? 'attachment';
            $mimeType = $att['mime_type'] ?? 'application/octet-stream';
            $content  = $att['content'] ?? '';
            if (!empty($content)) {
                $mailer->addStringAttachment(base64_decode($content), $name, $mimeType);
            }
        }

        $sent = $mailer->sendMail($from, $to, $subject, $body, $smtpConfig);

        if (!$sent) {
            $mailerErrors = $mailer->getErrors();
            $detail = !empty($mailerErrors) ? implode('; ', $mailerErrors) : 'Unknown SMTP error';
            throw new Service_Exception("Failed to send email: {$detail}", 500);
        }

        // Mark quote as sent when emailing a quotation (update on resend too — tracks latest send time)
        if ($salesOrder->origin_type === 'quotation') {
            $salesOrder->quote_sent    = 1;
            $salesOrder->quote_sent_at = date('Y-m-d H:i:s');
            $salesOrder->update();
        }

        $isOpenQuotation = ($salesOrder->origin_type === 'quotation' && $salesOrder->status === 'draft');
        $emailTitle = $isOpenQuotation ? 'Quotation sent to ' . $to : 'Email sent to ' . $to;

        $historyMeta = ['from' => $resolved['email'], 'to' => $to, 'cc' => $cc, 'bcc' => $bcc, 'subject' => $subject, 'attachments' => []];
        $historyId = $this->logHistory($soId, [
            'log_type' => 'email_sent',
            'title'    => $emailTitle,
            'meta'     => $historyMeta,
        ]);

        if (!empty($attachments)) {
            $attachSvc = new Service_Attachment($this->context);
            $attachSvc->saveFromBase64($attachments, 'sales_order_history', $historyId);
            $historyMeta['attachments'] = $attachSvc->listFor('sales_order_history', $historyId);
            $this->db->update('sales_order_history', ['meta' => json_encode($historyMeta)], "id = {$historyId}");
        }

        return ["success" => true];
    }
}
?>
