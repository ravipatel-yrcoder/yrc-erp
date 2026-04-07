<?php
class Service_So_Order extends Service_Base {


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


    private function normalizeLineItems(array $lineItems, string $source, string $context, array $extra): array {

        if( empty($lineItems) ) return [];

        $normalizedLineItems = [];
        if( $source === "form_request" && $context === "reserve_stock" ) {
            
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
        else if( $source === "sales_order" && (in_array($context, ["reserve_stock", "release_stock"]) ) ) {
        
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




    private function validatePayload(array $payload, int $soId = 0): array {

        $customerId = (int) ($payload['customer_id'] ?? 0);
        $locationId = (int) ($payload['location_id'] ?? 0);
        $orderDate = ($payload['order_date'] ?? '');
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

        // Location
        $location = new Models_Location($locationId);
        if ($location->isEmpty || $location->company_id != $this->context->companyId) {
            $this->addError(validationErrMsg("missing_or_invalid", "Location"), "location_id");
        }

        // Order date
        if (empty($orderDate)) {
            $this->addError(validationErrMsg("required", "Order date"), "order_date");
        } elseif (!strtotime($orderDate)) {
            $this->addError(validationErrMsg("invalid", "Order date"), "order_date");
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


        // Stock ATP warnings for confirmed/delivered orders (returned to caller, not treated as hard errors)
        $intendedStatus = $status;
        if (!in_array($intendedStatus, ['draft', 'confirmed', 'delivered'])) {
            $intendedStatus = 'draft';
        }
        if ($intendedStatus === 'confirmed' || $intendedStatus === 'delivered') {
            return $this->validateStockForItems($locationId, $lineItems);
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

        foreach ($items as $item) {

            $row = $index + 1;
            $productId = (int) ($item['product_id'] ?? 0);
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
                if (!(!$tax->isEmpty && $tax->company_id == $this->context->companyId && $tax->status === 'active' && in_array($tax->apply_on, ['sales', 'both']))) {
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
    private function validateStockForItems(int $locationId, array $items): array {

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
                "SELECT on_hand_qty, reserved_qty FROM inv_product_stock WHERE company_id = ? AND location_id = ? AND product_id = ? LIMIT 1",
                [$companyId, $locationId, $productId]
            );

            $onHand = $stock ? (float) $stock->on_hand_qty  : 0;
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

        $existingByProdId = [];
        foreach ($existingItems as $item) {
            $existingByProdId[$item->product_id] = $item;
        }

        $itemsToCreate = [];
        $itemsToUpdate = [];
        $itemsToDelete = [];
        $usedProdIds = [];

        foreach ($incomingItems as $item) {
            $productId = (int) ($item['product_id'] ?? 0);
            if ($productId && isset($existingByProdId[$productId])) {
                $item['id'] = $existingByProdId[$productId]->id;
                $itemsToUpdate[] = $item;
                $usedProdIds[]   = $productId;
            } else {
                $itemsToCreate[] = $item;
            }
        }

        foreach ($existingByProdId as $prodId => $existingItem) {
            if (!in_array($prodId, $usedProdIds)) {
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

        return [$updateLog, $soSubtotal, $soItemDiscounts, $soTaxTotal];
    }



    /**
     * Update SO totals row (subtotal, item discounts, order discount, tax, total).
     */
    private function updateSOTotals(int $soId, float $soSubtotal, float $soItemDiscounts, float $soTaxTotal, float $orderDiscountAmt): void {

        $total = $soSubtotal - $soItemDiscounts - $orderDiscountAmt + $soTaxTotal;

        $this->db->update("sales_orders", [
            "subtotal" => round($soSubtotal, 4),
            "discount_amount" => round($soItemDiscounts + $orderDiscountAmt, 4),
            "tax_amount" => round($soTaxTotal, 4),
            "total_amount"    => round($total, 4),
        ], "id = {$soId}");
    }



    /**
     * Compute order-level discount amount from discount_info on the SO.
     */
    private function calcOrderDiscount(float $soSubtotal, array $discountInfo): float {

        if (empty($discountInfo)) return 0;

        $type = $discountInfo['type']  ?? 'fixed';
        $value = (float) ($discountInfo['value'] ?? 0);

        if ($value <= 0) return 0;

        if ($type === 'percent') {
            return round($soSubtotal * ($value / 100), 4);
        }
        return (float) $value;
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
            'customer_id' => $so->customer_id,
            'location_id' => $so->location_id,
            'status' => "delivered",
            'fulfilment_type' => 'pickup',
            'instant_delivery' => 1,
            'items' => $finalItems,
        ];

        $delivery = new Service_So_Delivery(new Service_TenantContext($this->context->companyId, $this->context->userId));
        $response = $delivery->create($payload);

        if( $response["success"] !== true ) {
            throw new Service_Exception("Failed to create delivery note");
        }
    }




    public function logHistory(int $soId, array $payload): void {

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

        if (!$history->create()) {
            throw new Service_Exception("Failed to log sales order history");
        }
    }


    public function getFormContext(int $soId = 0): array {

        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $soDetails = [];
        if ($soId > 0) {
            $so = $this->getSalesOrderOrFail($soId);
            $soDetails = array_merge(['id' => $soId, 'line_items' => $so->line_items], $so->toArray());
            // Decode SO-level discount_info for JS
            if (isset($soDetails['discount_info']) && $soDetails['discount_info']) {
                $soDetails['discount_info'] = json_decode($soDetails['discount_info'], true);
            }
        }

        $location = new Models_Location();
        $locations = $location->getAll([], ["company_id" => $companyId, "status" => ["active"]]);

        // Products with sale_price and UOMs
        $sql = "SELECT a.id, a.name, a.sku, a.sale_price,
                       b.id AS uom_id, b.name AS uom_name, c.code AS uom_code, b.is_base AS base_uom
                FROM products AS a
                LEFT JOIN product_uoms AS b ON b.product_id = a.id AND b.status = 'active'
                LEFT JOIN uoms AS c ON c.id = b.base_uom_id
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
                    'uoms' => [],
                ];
            }
            if ($row->uom_id) {
                $products[$id]['uoms'][] = [
                    'uom_id' => $row->uom_id,
                    'name' => $row->uom_name,
                    'code' => $row->uom_code,
                    'is_base_uom' => $row->base_uom,
                ];
            }
        }

        $paymentTerm  = new Models_PaymentTerm();
        $paymentTerms = $paymentTerm->getAll([], ["company_id" => $companyId, "status" => "active"]);

        $tax = new Models_Tax();
        $salesTaxes = $tax->getAll([], ["company_id" => $companyId, "apply_on" => ["sales", "both"], "status" => "active"]);

        $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));

        return [
            'so_details' => $soDetails,
            'locations' => $locations,
            'suggested_so_number' => $seqService->nextPreview("sales_orders"),
            'products' => array_values($products),
            'payment_terms' => $paymentTerms,
            'taxes' => $salesTaxes,
        ];
    }


    public function getDetails(int $soId): array {

        $so = $this->getSalesOrderOrFail($soId);

        $dnCount = (int) $this->db->fetchVar(
            "SELECT COUNT(id) FROM sales_deliveries WHERE company_id = ? AND sales_order_id = ?",
            [$this->context->companyId, $soId]
        );

        $soDetails = array_merge(
            [
                'id' => $soId,
                'customer_name' => $so->customer->display_name,
                'line_items' => $so->line_items,
                'location_name' => $so->location->name,
                'has_deliveries' => $dnCount > 0,
            ],
            $so->toArray()
        );

        // Decode JSON fields for JS
        if ($soDetails['discount_info']) {
            $soDetails['discount_info'] = json_decode($soDetails['discount_info'], true);
        }

        return ['so_details' => $soDetails];
    }


    public function create(array $payload): array {

        $stockWarnings = $this->validatePayload($payload);

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        // Soft gate: return ATP warnings unless user has already acknowledged them
        if (!empty($stockWarnings) && empty($payload['acknowledged_warning'])) {
            return [
                'success'      => false,
                'warning'      => true,
                'warning_type' => 'low_stock',
                'warnings'     => $stockWarnings,
            ];
        }

        $this->db->startTransaction();

        try {

            $companyId = $this->context->companyId;
            $userId = $this->context->userId;

            // SO Number logic
            $soNumberInput = trim($payload['so_number'] ?? '');
            $soNumberSuggested = trim($payload['so_number_suggested'] ?? '');

            $soNumber = $soNumberInput;
            if (empty($soNumberInput) || $soNumberInput === $soNumberSuggested) {
                $seqService = new Service_Sequence(new Service_TenantContext($companyId, $userId));
                $soNumber = $seqService->nextCommit("sales_orders");
            }

            // Address snapshots
            $customerId = (int) ($payload['customer_id'] ?? 0);
            $customer = new Models_Customer($customerId);
            $billingSnapshot = json_encode($customer->getBillingAddress(),  JSON_UNESCAPED_UNICODE);
            $shippingSnapshot = json_encode($customer->getShippingAddress(), JSON_UNESCAPED_UNICODE);

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

            $so = new Models_SalesOrder();
            $so->fillFromArray($payload);
            $so->status = $intendedStatus;
            $so->company_id = $companyId;
            $so->created_by = $userId;
            $so->salesperson_id = $userId;
            $so->so_number = $soNumber;
            $so->billing_address_snapshot = $billingSnapshot;
            $so->shipping_address_snapshot = $shippingSnapshot;
            $so->discount_info = !empty($orderDiscountInfoRaw) ? json_encode($orderDiscountInfoRaw, JSON_UNESCAPED_UNICODE) : null;
            $so->payment_terms = $paymentTermsText;


            $soId = $so->create();
            if (!$soId) {
                throw new Service_Exception("Failed to create sales order");
            }

            // refresh Sales Order Object
            //$so->refreshById($soId);

            // save line items
            $lineItems = (array) ($payload['so_items'] ?? []);
            [$updateLog, $soSubtotal, $soItemDiscounts, $soTaxTotal] = $this->saveLineItems($so, $lineItems);
            
            // calculate Sales Order Total after save line items
            $orderDiscountAmt = $this->calcOrderDiscount($soSubtotal, $orderDiscountInfoRaw);
            $this->updateSOTotals($soId, $soSubtotal, $soItemDiscounts, $soTaxTotal, $orderDiscountAmt);


            // Log SO create event
            $this->logHistory($soId, [
                'log_type' => 'created',
                'title' => 'Order created #' . $soNumber,
                'meta' => [
                    'so_number' => $soNumber,
                    'status' => $intendedStatus,
                    'customer_name' => $customer->display_name,
                    'items_count' => count($lineItems),
                ],
            ]);
            
            
            // refresh Sales Order Object
            $so->refreshById($soId);

            if ($intendedStatus === 'confirmed') {
                
                // reserve stock if created SO is confirmed
                $items = $this->normalizeLineItems($lineItems, "form_request", "reserve_stock", ["location_id" => $so->location_id]);

                $stock = new Service_Inv_Stock(new Service_TenantContext($this->context->companyId, $this->context->userId));
                $stock->reserve($items);
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
                        ];
                    }
                }

                $this->createDelivery($so, $finalLineItems);
            }

            $this->db->commit();

            return ["success" => true, "data" => ["so_id" => $soId, "so_number" => $soNumber]];

        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function update(int $soId, array $payload): array {

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

            $so->fillFromArray($payload, ['id', 'so_number', 'company_id', 'created_at', 'created_by', 'salesperson_id', 'billing_address_snapshot', 'shipping_address_snapshot']);
            $so->discount_info  = !empty($orderDiscountInfoRaw) ? json_encode($orderDiscountInfoRaw, JSON_UNESCAPED_UNICODE) : null;
            $so->payment_terms  = $paymentTermsText;

            if (!$so->update()) {
                throw new Service_Exception("Failed to update sales order");
            }

            $newSODetails = $so->toArray();

            // Log changed header fields
            $trackFields = [
                'customer_id' => 'Customer',
                'location_id' => 'Location',
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
                        $updatedDetails[] = [
                            'field'   => $field,
                            'label'   => $label,
                            'old_val' => $oldVal,
                            'new_val' => $newVal,
                        ];
                    }
                }
            }

            if (!empty($updatedDetails)) {
                $this->logHistory($soId, [
                    'log_type' => 'updated_details',
                    'title' => 'Sales order details updated',
                    'meta' => $updatedDetails,
                ]);
            }

            $lineItems = (array) ($payload['so_items'] ?? []);
            [$updateLog, $soSubtotal, $soItemDiscounts, $soTaxTotal] = $this->saveLineItems($so, $lineItems);

            
            // calculate Sales Order Total after save line items
            $orderDiscountAmt = $this->calcOrderDiscount($soSubtotal, $orderDiscountInfoRaw);
            $this->updateSOTotals($soId, $soSubtotal, $soItemDiscounts, $soTaxTotal, $orderDiscountAmt);


            if (!empty($updateLog)) {
                $this->logHistory($soId, [
                    'log_type' => 'updated_line_items',
                    'title' => 'Line items updated',
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

        $companyId = $this->context->companyId;
        
        $so = $this->getSalesOrderOrFail($soId);

        $status = trim($payload['status'] ?? '');
        $notes = trim($payload['notes']  ?? '');

        $allowedTransitions = [
            'draft' => ['confirmed', 'cancelled', 'delivered'],
            'confirmed' => ['cancelled', 'delivered'],
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

            $stockWarnings = $this->validateStockForItems($so->location_id, $stockItems);

            if (!empty($stockWarnings) && empty($payload['acknowledged_warning'])) {
                return [
                    'success'      => false,
                    'warning'      => true,
                    'warning_type' => 'low_stock',
                    'warnings'     => $stockWarnings,
                ];
            }
        }

        $this->db->startTransaction();

        try {

            $statusLabels = [
                'draft' => 'Draft',
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

                // Reserve stock on confirm SO
                if ($status === 'confirmed') {
                    
                    // reserve stock when SO confirmed
                    $items = $this->normalizeLineItems($so->line_items, "sales_order", "reserve_stock", ["location_id" => $so->location_id]);

                    $stock = new Service_Inv_Stock(new Service_TenantContext($this->context->companyId, $this->context->userId));
                    $stock->reserve($items);
                }


                // Release stock when confirm order cancelled
                if ($status === 'cancelled' && $oldStatus === 'confirmed') {
                    
                    //$this->releaseStock($so);

                    // reserve stock when SO confirmed
                    $items = $this->normalizeLineItems($so->line_items, "sales_order", "release_stock", ["location_id" => $so->location_id]);

                    $stock = new Service_Inv_Stock(new Service_TenantContext($this->context->companyId, $this->context->userId));
                    $stock->release($items);
                }


                $so->status = $status;
                if (!$so->update()) {
                    throw new Service_Exception("Failed to update sales order status");
                }
            }

            $this->logHistory($soId, [
                'log_type' => 'status_changed',
                'title' => 'Status changed to ' . ($statusLabels[$status] ?? $status),
                'meta' => [
                    'old_status' => $oldStatus,
                    'new_status' => $status,
                    'old_status_label' => $statusLabels[$oldStatus] ?? $oldStatus,
                    'new_status_label' => $statusLabels[$status]    ?? $status,
                    'notes' => $notes,
                ],
            ]);

            $this->db->commit();

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


    public function searchCustomers(string $query): array {

        $companyId = $this->context->companyId;
        $like = '%' . $query . '%';

        $sql = "SELECT id, display_name, email, phone
                FROM customers
                WHERE company_id = ? AND status = 'active'
                  AND (
                        display_name LIKE ? OR
                        company_name LIKE ? OR
                        first_name LIKE ? OR
                        last_name LIKE ? OR
                        email LIKE ? OR
                        phone LIKE ? OR
                        customer_code LIKE ?
                  )
                ORDER BY display_name ASC
                LIMIT 25";

        $rows = $this->db->fetchAll($sql, [$companyId, $like, $like, $like, $like, $like, $like, $like]);

        $data = [];
        foreach ($rows as $row) {
            $data[] = [
                'id' => $row->id,
                'display_name' => $row->display_name,
                'email' => $row->email,
                'phone' => $row->phone,
            ];
        }

        return $data;
    }
}
?>
