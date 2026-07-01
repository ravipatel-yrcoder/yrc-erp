<?php
class Service_Inv_Movement extends Service_Base {
    
    /*
    protected $companyId;

    public function __construct($companyId) {
        $this->companyId = $companyId;
    }
    */


    /**
     * Public entry point for all inventory movements
     */
    public function importAdjustments(array $rows): array
    {
        $companyId = $this->context->companyId;

        // Build lookup maps
        $productRows = $this->db->fetchAll(
            "SELECT p.id, p.name, p.stock_tracking_method
             FROM products p
             INNER JOIN product_masters pm ON pm.id = p.master_id
             WHERE pm.company_id = ? AND p.stock_tracking_method IN ('quantity','lot','serial') AND pm.status <> 'archived'",
            [$companyId]
        );
        $productMap = [];
        foreach ($productRows as $p) {
            $productMap[(int) $p->id] = $p;
        }

        $locationRows = $this->db->fetchAll(
            "SELECT id, name FROM company_locations WHERE company_id = ? AND status = 'active'",
            [$companyId]
        );
        $locationMap = [];
        foreach ($locationRows as $l) {
            $locationMap[strtolower(trim($l->name))] = (int) $l->id;
        }

        // Phase 1 — validate all rows
        $errors            = [];
        $seenSerials       = [];
        $serialsToCheckIn  = []; // serial_number => rowNum (adjust_in)
        $serialsToCheckOut = []; // serial_number => rowNum (adjust_out)
        $stockCache        = []; // "{productId}_{locationId}" => available qty (for decrease validation)

        foreach ($rows as $i => $row) {
            $rowNum    = $i + 2;
            $productId = (int) trim($row[0]);
            $location  = trim($row[4]);
            $qtyRaw    = trim(str_replace(',', '', $row[5]));
            $serialLot = trim($row[6]);

            $product        = $productMap[$productId] ?? null;
            $trackingMethod = $product ? $product->stock_tracking_method : null;

            if (!$productId || !$product) {
                $errors[] = ['row' => $rowNum, 'column' => 'Product ID', 'message' => 'Product not found or not inventory-tracked'];
            }

            $locationKey = strtolower($location);
            if ($location === '') {
                $errors[] = ['row' => $rowNum, 'column' => 'Location', 'message' => 'Location is required'];
            } elseif (!isset($locationMap[$locationKey])) {
                $errors[] = ['row' => $rowNum, 'column' => 'Location', 'message' => "Location \"{$location}\" not found"];
            }

            if ($qtyRaw === '' || !is_numeric($qtyRaw)) {
                $errors[] = ['row' => $rowNum, 'column' => 'Adjust Qty (+/-)', 'message' => 'Quantity must be a number'];
            } elseif ((float) $qtyRaw == 0) {
                $errors[] = ['row' => $rowNum, 'column' => 'Adjust Qty (+/-)', 'message' => 'Quantity cannot be zero'];
            } elseif ($trackingMethod === 'serial' && abs((float) $qtyRaw) != 1) {
                $errors[] = ['row' => $rowNum, 'column' => 'Adjust Qty (+/-)', 'message' => 'Quantity must be 1 or -1 for serial-tracked products'];
            }

            if (in_array($trackingMethod, ['serial', 'lot'], true) && $serialLot === '') {
                $errors[] = ['row' => $rowNum, 'column' => 'Serial/Lot Number', 'message' => 'Serial/Lot Number is required for this product'];
            }

            if ($trackingMethod === 'serial' && $serialLot !== '') {
                $serialKey = strtolower($serialLot);
                if (isset($seenSerials[$serialKey])) {
                    $errors[] = ['row' => $rowNum, 'column' => 'Serial/Lot Number', 'message' => "Duplicate serial \"{$serialLot}\" (already on row {$seenSerials[$serialKey]})"];
                } else {
                    $seenSerials[$serialKey] = $rowNum;
                    if (is_numeric($qtyRaw) && (float) $qtyRaw > 0) {
                        $serialsToCheckIn[$serialLot] = $rowNum;
                    } elseif (is_numeric($qtyRaw) && (float) $qtyRaw < 0) {
                        $serialsToCheckOut[$serialLot] = $rowNum;
                    }
                }
            }

            // Stock level check for decrease adjustments on non-serial products
            if (
                $product &&
                $trackingMethod !== 'serial' &&
                $location !== '' &&
                isset($locationMap[$locationKey]) &&
                is_numeric($qtyRaw) &&
                (float) $qtyRaw < 0
            ) {
                $locId    = $locationMap[$locationKey];
                $cacheKey = "{$productId}_{$locId}";
                if (!isset($stockCache[$cacheKey])) {
                    $stockRow = $this->db->fetchOne(
                        "SELECT unrestricted_qty FROM inv_product_stock WHERE company_id = ? AND location_id = ? AND product_id = ?",
                        [$companyId, $locId, $productId]
                    );
                    $stockCache[$cacheKey] = $stockRow ? (float) $stockRow->unrestricted_qty : 0.0;
                }
                $needed    = abs((float) $qtyRaw);
                $available = $stockCache[$cacheKey];
                if ($available < $needed) {
                    $errors[] = ['row' => $rowNum, 'column' => 'Adjust Qty (+/-)', 'message' => "Insufficient stock — {$available} available, {$needed} requested"];
                } else {
                    $stockCache[$cacheKey] -= $needed;
                }
            }
        }

        // Bulk serial checks
        if (!empty($serialsToCheckIn)) {
            $sns          = array_keys($serialsToCheckIn);
            $placeholders = rtrim(str_repeat('?,', count($sns)), ',');
            $existing     = $this->db->fetchCol(
                "SELECT serial_number FROM inv_serials WHERE company_id = ? AND serial_number IN ($placeholders)",
                array_merge([$companyId], $sns)
            );
            foreach ($existing as $sn) {
                $errors[] = ['row' => $serialsToCheckIn[$sn], 'column' => 'Serial/Lot Number', 'message' => "Serial \"{$sn}\" already exists in the system"];
            }
        }

        if (!empty($serialsToCheckOut)) {
            $sns          = array_keys($serialsToCheckOut);
            $placeholders = rtrim(str_repeat('?,', count($sns)), ',');
            $existing     = $this->db->fetchAll(
                "SELECT serial_number, status FROM inv_serials WHERE company_id = ? AND serial_number IN ($placeholders)",
                array_merge([$companyId], $sns)
            );
            $existingMap = [];
            foreach ($existing as $row) {
                $existingMap[$row->serial_number] = $row->status;
            }
            foreach ($sns as $sn) {
                if (!isset($existingMap[$sn])) {
                    $errors[] = ['row' => $serialsToCheckOut[$sn], 'column' => 'Serial/Lot Number', 'message' => "Serial \"{$sn}\" does not exist in the system"];
                } elseif ($existingMap[$sn] !== 'in_stock') {
                    $errors[] = ['row' => $serialsToCheckOut[$sn], 'column' => 'Serial/Lot Number', 'message' => "Serial \"{$sn}\" is not in stock (status: {$existingMap[$sn]})"];
                }
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        // Phase 2 — build dispatch list, grouping serial rows by product+location+direction
        $serialGroups = []; // key: "{productId}_{locationId}_{movementType}"
        $dispatches   = []; // final ordered list of payloads to dispatch

        foreach ($rows as $row) {
            $productId = (int) trim($row[0]);
            $trackingMethod = $productMap[$productId]->stock_tracking_method;
            $qty = (float) str_replace(',', '', trim($row[5]));
            $locId = $locationMap[strtolower(trim($row[4]))];
            $movementType = $qty > 0 ? 'adjust_in' : 'adjust_out';
            $serialLot = trim($row[6]);
            $note = trim($row[7]);

            if ($trackingMethod === 'serial') {
                $groupKey = "{$productId}_{$locId}_{$movementType}";
                if (!isset($serialGroups[$groupKey])) {
                    $serialGroups[$groupKey] = [
                        'product_id' => $productId,
                        'location_id' => $locId,
                        'quantity' => 0,
                        'movement_type' => $movementType,
                        'notes' => $note,
                        'serial_or_lot_numbers' => [],
                    ];
                }
                $serialGroups[$groupKey]['quantity']++;
                $serialGroups[$groupKey]['serial_or_lot_numbers'][] = $serialLot;
            } else {
                $dispatches[] = [
                    'product_id' => $productId,
                    'location_id' => $locId,
                    'quantity' => abs($qty),
                    'movement_type' => $movementType,
                    'notes' => $note,
                    'serial_or_lot_numbers' => $serialLot !== '' ? [$serialLot] : [],
                ];
            }
        }

        foreach ($serialGroups as $payload) {
            $dispatches[] = $payload;
        }

        // Single transaction
        $this->db->startTransaction();
        try {
            foreach ($dispatches as $payload) {
                $this->dispatchMovement($payload);
            }
            $this->db->commit();
            return ['success' => true, 'data' => ['imported' => count($rows)]];
        } catch (Exception $e) {
            $this->db->rollBack();
            throw $e;
        }
    }


    public function record(array $payload)
    {
        // Validate incoming data
        $this->validatePayload($payload);

        if ($this->hasErrors()) {
            return [
                "success" => false,
                "errors"  => $this->getErrors()
            ];
        }

        
        $this->db->startTransaction();

        try {

            $result = $this->dispatchMovement($payload);

            $this->db->commit();

            return [
                "success" => true,
                "data"    => $result,
            ];

        } catch (Exception $e) {

            $this->db->rollBack();
            throw $e;
        }
    }


    protected function validatePayload(array $payload)  {

        $isProductValid = true;

        $movementType = $payload["movement_type"] ?? "";
        $locationId = $payload["location_id"] ?? 0;
        $productId = $payload["product_id"] ?? 0;
        $quantity = $payload["quantity"] ?? 0;
        $serialOrLotNumbers = $payload["serial_or_lot_numbers"] ?? [];

        if(!in_array($movementType, array_keys(config("constants.inventory.stock_movement_type")))) {
            $this->addError(validationErrMsg("missing_or_invalid", "Movement type"), "movement_type");
        }        

        $location = new Models_Location($locationId);
        if( $location->isEmpty || $location->company_id != $this->context->companyId ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Location"), "location_id");            
        }

        $product = new Models_Product($productId);
        if( $product->isEmpty || $product->company_id != $this->context->companyId ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Product"), "product_id");
            $isProductValid = false;
        } elseif (!in_array($product->stock_tracking_method, ['quantity', 'lot', 'serial'])) {
            $this->addError(validationErrMsg("missing_or_invalid", "Product"), "product_id");
            $isProductValid = false;
        }

        if(!is_numeric($quantity)) {
            $this->addError(validationErrMsg("number", "Quantity"), "quantity");
        } elseif ((float)$quantity == 0) {
            $this->addError(validationErrMsg("missing_or_invalid", "Quantity"), "quantity");
        }

        $notes = trim($payload["notes"] ?? "");
        if (in_array($movementType, ['adjust_in', 'adjust_out'], true) && $notes === "") {
            $this->addError(validationErrMsg("required", "Note"), "notes");
        }

        if (in_array($movementType, ['purchase_receipt', 'sale', 'dn_cancelled', 'dn_returned', 'cust_return', 'mo_issue', 'mo_produce', 'mo_return'], true)) {
            if (empty($payload['reference_type']) || empty($payload['reference_id'])) {
                $this->addError("Reference document is required for this movement","reference_id");
            }
        }

        if( $isProductValid === true ) {

            $stockTrackingMethod = strtoupper($product->stock_tracking_method);
            // Serial/lot validation is handled externally for sale, return, and MO movements
            if (!in_array($movementType, ['sale', 'dn_cancelled', 'dn_returned', 'cust_return', 'mo_issue', 'mo_produce', 'mo_return'], true) && $stockTrackingMethod === "SERIAL") {

                if (count($serialOrLotNumbers) !== (int) abs($quantity)) {
                    $this->addError(validationErrMsg("does_not_match_qty", "Serial numbers"), "serial_or_lot_numbers");
                }
                else {
                    
                    if( in_array($movementType, ["adjust_in", "purchase_receipt"], true) )
                    {
                        $placeholders = rtrim(str_repeat('?,', count($serialOrLotNumbers)), ',');
                        $sql = "SELECT serial_number FROM inv_serials WHERE company_id = ? AND serial_number IN ($placeholders)";
                        $existingSerialNumbers = $this->db->fetchCol($sql, array_merge([$this->context->companyId], $serialOrLotNumbers));
                        if( count($existingSerialNumbers) ) {
                            $this->addError(validationErrMsg("duplicate", implode(",", $existingSerialNumbers)." serial numbers"), "serial_or_lot_numbers");
                        }
                    }
                    else if( $movementType === "adjust_out" )
                    {
                        $placeholders = rtrim(str_repeat('?,', count($serialOrLotNumbers)), ',');
                        $sql = "SELECT serial_number, status FROM inv_serials WHERE company_id = ? AND serial_number IN ($placeholders)";
                        $existingSerialNumbers = $this->db->fetchAll($sql, array_merge([$this->context->companyId], $serialOrLotNumbers));

                        $notAdjustableSerialNumbers = [];
                        foreach($existingSerialNumbers as $serialNumber) {
                            if( !in_array($serialNumber->status, ["in_stock"]) ) {
                                $notAdjustableSerialNumbers[] = $serialNumber->serial_number;
                            }
                        }
                        
                        if( count($notAdjustableSerialNumbers) ) {
                            $this->addError(validationErrMsg("can_not_adjusted", implode(",", $notAdjustableSerialNumbers)." serial numbers"), "serial_or_lot_numbers");
                        }


                        if( !$this->hasErrors() )
                        {
                            $stock = new Models_InvProductStock();
                            $stock->fetchByProperty(["company_id", "location_id", "product_id"], [$this->context->companyId, $locationId, $productId]);

                            if( $stock->isEmpty ) {
                                $this->addError(validationErrMsg("no_stock_adjusted",""), "location_id");
                            } else {

                                if( $stock->unrestricted_qty < abs($quantity) ) {
                                    $this->addError("Can not remove more than available stock", "quantity");
                                }
                            }
                        }
                    }
                }
            }
        }
    }


    // ---------------------------------------------------------------------
    // MOVEMENT ROUTER
    // ---------------------------------------------------------------------

    protected function dispatchMovement(array $payload)
    {
        switch ($payload["movement_type"]) {

            case "adjust_in":
                return $this->adjustIn($payload);

            case "adjust_out":
                return $this->adjustOut($payload);

            case "purchase_receipt":
                return $this->adjustIn($payload);

            case "sale":
                return $this->saleOut($payload);

            case "dn_cancelled":
            case "dn_returned":
            case "cust_return":
                return $this->customerReturn($payload);

            case "mo_issue":
                return $this->moIssue($payload);

            case "mo_produce":
                return $this->moProduce($payload);

            case "mo_return":
                return $this->moReturn($payload);

            case "transfer_in":
                //return $this->adjustIn($payload);

            case "transfer_out":
                //return $this->adjustOut($payload);

            default:
                throw new Exception("Unknown movement type: ".$payload["movement_type"]);
        }
    }


    protected function adjustIn(array $payload) {

        $companyId = $this->context->companyId;
        $locationId = $payload["location_id"];
        $productId = $payload["product_id"];
        $quantity = $payload["quantity"];
        $adjustmentType = $payload["movement_type"];

        if( $adjustmentType === "adjust_in" ){            
            
            // create adjustment(manual)
            $adjustmentId = $this->logAdjustment($payload);

            $payload["reference_type"] = "inv_adjustment";
            $payload["reference_id"] = $adjustmentId;
        }


        // Create or Update Inventory Product
        $stock = new Models_InvProductStock();
        $stock->fetchByProperty(["company_id", "location_id", "product_id"], [$companyId, $locationId, $productId]);
        if( $stock->isEmpty ) {

            // create
            $stock->company_id = $this->context->companyId;
            $stock->location_id = $locationId;
            $stock->product_id = $productId;
            $stock->unrestricted_qty = $quantity;            
            $id = $stock->create();

            if( !$id ) {
                throw new Exception("Failed to adjust stock");
            }

            $oldQty = 0;
            $newQty = $quantity;

        } else {

            // update
            $oldQty = $stock->unrestricted_qty;
            $newQty = $oldQty + $quantity;
            
            $stock->unrestricted_qty = $newQty;
            $saved = $stock->update();
            if( !$saved ) {
                throw new Exception("Failed to adjust stock");
            }
        }

        // increaes Lot or Serial
        $this->increaseLotOrSerial($payload);


        // Log movement
        $this->logMovement($payload, $oldQty, $newQty);


        // Update product cost based on company cost method (AVCO recalculates WAC; Standard is a no-op)
        if (in_array($adjustmentType, ['adjust_in', 'purchase_receipt'])) {
            $unitCost = (float) ($payload['unit_cost'] ?? 0);
            (new Service_Product($this->context))->updateCurrentCost((int) $productId, (float) $quantity, $unitCost);
        }


        return ["new_qty" => $newQty];
    }


    protected function increaseLotOrSerial(array $payload)
    {
        $product = new Models_Product($payload["product_id"]);
        $tracking = $product->stock_tracking_method;

        if ($tracking === "lot") {
            //$this->increaseLots($payload);
        }

        if ($tracking === "serial") {
            $this->increaseSerials($payload);
        }
    }


    protected function increaseSerials(array $payload)
    {
        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $productId = $payload["product_id"];
        $product = new Models_Product($productId);

        // SAFETY GUARD — prevent misuse
        if ($product->stock_tracking_method !== "serial") {
            throw new Exception("Serial tracking is not enabled for this product");
        }

        $location_id = $payload["location_id"];
        $serialNumbers = $payload["serial_or_lot_numbers"];

        // Insert serials
        foreach ($serialNumbers as $sn) {

            $serial = new Models_InvSerial();
            $serial->company_id = $companyId;
            $serial->product_id = $productId;
            $serial->serial_number = trim($sn);
            $serial->status = "in_stock";
            $serialId = $serial->create();
            if( !$serialId ) {
                throw new Exception("Failed to create serial #{$sn}");
            }

            $serialStock = new Models_InvSerialStock();
            $serialStock->company_id = $companyId;
            $serialStock->product_id = $productId;
            $serialStock->location_id = $location_id;
            $serialStock->serial_id = $serialId;
            if( !$serialStock->create() ) {
                throw new Exception("Failed to add serial #{$sn} to stock");
            }

            $this->logSerialHistory($serialId, $productId, 'adjustment_in', 'Serial added to inventory', $payload['reference_type'] ?? null, isset($payload['reference_id']) ? (int)$payload['reference_id'] : null, ['to_status' => 'in_stock']);
        }

        // Advance sequence counter so the next generation starts after these serials
        $seqService = new Service_Inv_Sequence(new Service_TenantContext($companyId, $userId));
        $seqService->updateLastNumber((int) $productId, $serialNumbers);
    }


    protected function adjustOut(array $payload) {
        
        $companyId = $this->context->companyId;
        $locationId = $payload["location_id"];
        $productId = $payload["product_id"];
        $quantity = $payload["quantity"];

        
        // create adjustment
        $adjustmentId = $this->logAdjustment($payload);

        $payload["reference_type"] = "inv_adjustment";
        $payload["reference_id"] = $adjustmentId;


        // Create or Update Inventory Product
        $stock = new Models_InvProductStock();
        $stock->fetchByProperty(["company_id", "location_id", "product_id"], [$companyId, $locationId, $productId]);

        $oldQty = $stock->unrestricted_qty;
        $newQty = $oldQty - abs($quantity);

        $stock->unrestricted_qty = $newQty;
        $saved = $stock->update();
        if( !$saved ) {
            throw new Exception("Failed to adjust stock");
        }

        
        // decrease Lot or Serial
        $this->decreaseLotOrSerial($payload);


        // Log movement
        $this->logMovement($payload, $oldQty, $newQty);


        return ["new_qty" => $newQty];
    }


    protected function decreaseLotOrSerial(array $payload)
    {
        $product = new Models_Product($payload["product_id"]);
        $tracking = $product->stock_tracking_method;

        if ($tracking === "lot") {
            //$this->decreaseLots($payload);
        }

        if ($tracking === "serial") {
            $this->decreaseSerials($payload);
        }
    }

    protected function decreaseSerials(array $payload)
    {
        $companyId = $this->context->companyId;
        $userId = $this->context->userId;

        $productId = $payload["product_id"];
        $product = new Models_Product($productId);

        // SAFETY GUARD — prevent misuse
        if ($product->stock_tracking_method !== "serial") {
            throw new Exception("Serial tracking is not enabled for this product.");
        }

        $serialNumbers = $payload["serial_or_lot_numbers"];

        // Insert serials
        foreach ($serialNumbers as $sn) {
            
            $serial = new Models_InvSerial();
            $serial->fetchByProperty(["company_id", "product_id", "serial_number"], [$companyId, $productId, $sn]);
            if( $serial->isEmpty ) {
                throw new Exception(validationErrMsg("does_not_exist", "#{$sn} serial number"));
            }

            if( $serial->status != "in_stock" ) {
                throw new Exception("#{$sn} serial can not adjust. Current status: {$serial->status}");
            }

            $serialId = $serial->id;
            $serialoldStatus = $serial->status;
            $serialNewStatus = "scrapped";

            $serial->status = $serialNewStatus;
            $saved = $serial->update();        
            if( !$saved ) {
                throw new Exception("Failed to update serial #{$sn}");
            }
            
            $serialStock = new Models_InvSerialStock();
            $serialStock->fetchByProperty(["company_id", "serial_id"], [$companyId, $serialId]);
            if( $serialStock->isEmpty ) {
                throw new Exception("#{$sn} serial does not exist in location's stock");
            }
            $serialStock->delete();

            if( !$serialStock->getDeletedRows() ) {
                throw new Exception("Failed to remove #{$sn} serial from stock");
            }            

            $this->logSerialHistory($serialId, $productId, 'adjustment_out', 'Serial removed from inventory', $payload['reference_type'] ?? null, isset($payload['reference_id']) ? (int)$payload['reference_id'] : null, ['from_status' => $serialoldStatus, 'to_status' => $serialNewStatus]);
        }
    }


    /**
     * Outbound movement for a sales delivery dispatch.
     * Deducts unrestricted_qty at the delivery location and logs to inv_stock_movements.
     * Reserved qty release is handled separately via releaseReservation().
     * Serial/lot status changes are managed by Service_So_Delivery (assignSerialsFifo / assignLotsFifo).
     */
    protected function saleOut(array $payload): array
    {
        $companyId  = $this->context->companyId;
        $locationId = $payload['location_id'];
        $productId  = $payload['product_id'];
        $quantity   = abs((float) $payload['quantity']);

        $stock = $this->db->fetchOne(
            "SELECT * FROM inv_product_stock
             WHERE company_id = ? AND location_id = ? AND product_id = ?
             FOR UPDATE",
            [$companyId, $locationId, $productId]
        );

        if (!$stock) {
            throw new Service_Exception("Failed to record sale out stock movement");
        }

        $oldQty = (float) $stock->unrestricted_qty;
        $newQty = max(0, $oldQty - $quantity);

        $this->db->update(
            "inv_product_stock",
            ['unrestricted_qty' => $newQty, 'updated_at' => date("Y-m-d H:i:s")],
            "company_id = $companyId AND location_id = $locationId AND product_id = $productId"
        );

        $payload['quantity'] = -$quantity; // negative qty_change in movement log
        $this->logMovement($payload, $oldQty, $newQty);

        return ['new_qty' => $newQty];
    }


    /**
     * Restore reserved_qty at the Sales Order location.
     * Called when a dispatched DN is reverted to draft — re-applies the reservation that was
     * released at dispatch time.
     */
    public function restoreReservation(array $payload): void
    {
        $companyId   = $this->context->companyId;
        $locationId  = $payload['location_id'];
        $productId   = $payload['product_id'];
        $productName = $payload['product_name'];
        $quantity    = abs((float) $payload['quantity']);

        $product = new Models_Product($productId);
        if (empty($product->stock_tracking_method) || strtolower($product->stock_tracking_method) === 'none') {
            return;
        }

        $stock = $this->db->fetchOne(
            "SELECT * FROM inv_product_stock
             WHERE company_id = ? AND location_id = ? AND product_id = ?
             FOR UPDATE",
            [$companyId, $locationId, $productId]
        );

        if (!$stock) {
            return;
        }

        $newReservedQty = (float) $stock->reserved_qty + $quantity;

        $this->db->update(
            "inv_product_stock",
            ['reserved_qty' => $newReservedQty, 'updated_at' => date("Y-m-d H:i:s")],
            "company_id = $companyId AND location_id = $locationId AND product_id = $productId"
        );
    }


    /**
     * Release reserved_qty at the Sales Order location.
     * Called after a dispatch to unwind the reservation that was created when the SO was confirmed.
     * This is separate from saleOut() because the reservation location (SO location) may differ
     * from the delivery location.
     */
    public function releaseReservation(array $payload): void
    {
        $companyId = $this->context->companyId;
        $locationId = $payload['location_id'];  // SO location
        $productId = $payload['product_id'];
        $productName = $payload['product_name'];
        $quantity = abs((float) $payload['quantity']);

        $stock = $this->db->fetchOne(
            "SELECT * FROM inv_product_stock
             WHERE company_id = ? AND location_id = ? AND product_id = ?
             FOR UPDATE",
            [$companyId, $locationId, $productId]
        );

        if (!$stock) {
            // No stock record at SO location — reservation may have never been written; skip silently.
            return;
        }

        $newReservedQty = max(0, (float) $stock->reserved_qty - $quantity);

        $this->db->update(
            "inv_product_stock",
            ['reserved_qty' => $newReservedQty, 'updated_at' => date("Y-m-d H:i:s")],
            "company_id = $companyId AND location_id = $locationId AND product_id = $productId"
        );
    }


    /**
     * Inbound movement when a sales delivery is returned by the customer.
     * Restores unrestricted_qty and logs to inv_stock_movements.
     * Serial statuses are restored by Service_So_Delivery (restoreSerials).
     */
    protected function customerReturn(array $payload): array
    {
        $companyId  = $this->context->companyId;
        $locationId = $payload['location_id'];
        $productId  = $payload['product_id'];
        $quantity   = abs((float) $payload['quantity']);

        $stock = new Models_InvProductStock();
        $stock->fetchByProperty(
            ['company_id', 'location_id', 'product_id'],
            [$companyId, $locationId, $productId]
        );

        if ($stock->isEmpty) {
            $stock->company_id   = $companyId;
            $stock->location_id  = $locationId;
            $stock->product_id   = $productId;
            $stock->unrestricted_qty  = $quantity;
            $stock->reserved_qty = 0;
            if (!$stock->create()) {
                throw new Exception("Failed to restore stock for customer return");
            }
            $oldQty = 0;
            $newQty = $quantity;
        } else {
            $oldQty = (float) $stock->unrestricted_qty;
            $newQty = $oldQty + $quantity;
            $stock->unrestricted_qty = $newQty;
            if (!$stock->update()) {
                throw new Exception("Failed to restore stock for customer return");
            }
        }

        $this->logMovement($payload, $oldQty, $newQty);

        return ['new_qty' => $newQty];
    }


    // -------------------------------------------------------------------------
    // Manufacturing movement methods — called directly by Service_Manufacturing_Order
    // within its own transaction; do NOT start a nested transaction here.
    // -------------------------------------------------------------------------

    /**
     * Issue materials from warehouse to shop floor at allocation time.
     * Decrements unrestricted_qty only — reserved_qty and serial status are handled
     * by the caller (saveAllocation) since it has full context for reservation math.
     *
     * Payload keys: location_id, product_id, quantity (positive), reference_type, reference_id, notes
     */
    protected function moIssue(array $payload): void
    {
        $companyId  = $this->context->companyId;
        $locationId = (int)   $payload['location_id'];
        $productId  = (int)   $payload['product_id'];
        $qty        = (float) $payload['quantity'];

        $stockRow = $this->db->fetchOne(
            "SELECT unrestricted_qty FROM inv_product_stock
             WHERE company_id = ? AND location_id = ? AND product_id = ? FOR UPDATE",
            [$companyId, $locationId, $productId]
        );
        $oldQty = $stockRow ? (float) $stockRow->unrestricted_qty : 0.0;
        $newQty = max(0.0, $oldQty - $qty);

        $this->db->query(
            "UPDATE inv_product_stock SET unrestricted_qty = ?
             WHERE company_id = ? AND location_id = ? AND product_id = ?",
            [$newQty, $companyId, $locationId, $productId]
        );

        $this->logMovement([
            'location_id'    => $locationId,
            'product_id'     => $productId,
            'movement_type'  => 'mo_issue',
            'quantity'       => -$qty,
            'reference_type' => $payload['reference_type'] ?? null,
            'reference_id'   => $payload['reference_id']   ?? null,
            'notes'          => $payload['notes']           ?? null,
        ], $oldQty, $newQty);
    }


    /**
     * Produce finished goods during MO production output.
     * Increments unrestricted_qty; creates inv_serials + inv_serial_stock for serial-tracked
     * finished goods. Logs to inv_stock_movements.
     * Returns created serial IDs so the caller can link them to mo_output_serials.
     *
     * Payload keys:
     *   location_id, product_id, quantity (positive), serial_numbers (string[], serial FG only),
     *   reference_type, reference_id, notes
     */
    protected function moProduce(array $payload): array
    {
        $companyId     = $this->context->companyId;
        $locationId    = (int)   $payload['location_id'];
        $productId     = (int)   $payload['product_id'];
        $qty           = (float) $payload['quantity'];
        $serialNumbers = (array) ($payload['serial_or_lot_numbers'] ?? []);

        $createdSerialIds = [];
        foreach ($serialNumbers as $sn) {
            $serial = new Models_InvSerial();
            $serial->company_id    = $companyId;
            $serial->product_id    = $productId;
            $serial->serial_number = $sn;
            $serial->status        = 'in_stock';
            $serialId = $serial->create();
            if (!$serialId) {
                throw new Service_Exception("Failed to create finished goods serial: $sn");
            }

            $serialStock = new Models_InvSerialStock();
            $serialStock->company_id  = $companyId;
            $serialStock->product_id  = $productId;
            $serialStock->location_id = $locationId;
            $serialStock->serial_id   = $serialId;
            if (!$serialStock->create()) {
                throw new Service_Exception("Failed to add finished goods serial to stock: $sn");
            }

            $this->logSerialHistory($serialId, $productId, 'produced', 'Produced via MO', $payload['reference_type'] ?? null, isset($payload['reference_id']) ? (int)$payload['reference_id'] : null, ['to_status' => 'in_stock']);
            $createdSerialIds[] = $serialId;
        }

        $stockRow = $this->db->fetchOne(
            "SELECT id, unrestricted_qty FROM inv_product_stock
             WHERE company_id = ? AND location_id = ? AND product_id = ? FOR UPDATE",
            [$companyId, $locationId, $productId]
        );
        $oldQty = $stockRow ? (float) $stockRow->unrestricted_qty : 0.0;
        $newQty = $oldQty + $qty;

        if ($stockRow) {
            $this->db->query(
                "UPDATE inv_product_stock SET unrestricted_qty = ? WHERE id = ?",
                [$newQty, $stockRow->id]
            );
        } else {
            $this->db->query(
                "INSERT INTO inv_product_stock (company_id, location_id, product_id, unrestricted_qty, reserved_qty)
                 VALUES (?, ?, ?, ?, 0)",
                [$companyId, $locationId, $productId, $newQty]
            );
        }

        $this->logMovement([
            'location_id'    => $locationId,
            'product_id'     => $productId,
            'movement_type'  => 'mo_produce',
            'quantity'       => $qty,
            'reference_type' => $payload['reference_type'] ?? null,
            'reference_id'   => $payload['reference_id']   ?? null,
            'notes'          => $payload['notes']           ?? null,
        ], $oldQty, $newQty);

        return ['created_serial_ids' => $createdSerialIds];
    }


    /**
     * Log an on_hand increase when material is returned from an MO back to stock.
     * Serial status restoration and inv_serial_stock repair are handled by the
     * caller (Service_Manufacturing_Order) since consumed-serial returns need
     * INSERT IGNORE logic beyond a simple status flip.
     *
     * Payload keys:
     *   location_id, product_id, quantity (positive), reference_type, reference_id, notes
     */
    protected function moReturn(array $payload): void
    {
        $companyId  = $this->context->companyId;
        $locationId = (int)   $payload['location_id'];
        $productId  = (int)   $payload['product_id'];
        $qty        = (float) $payload['quantity'];

        $stockRow = $this->db->fetchOne(
            "SELECT unrestricted_qty FROM inv_product_stock
             WHERE company_id = ? AND location_id = ? AND product_id = ? FOR UPDATE",
            [$companyId, $locationId, $productId]
        );
        $oldQty = $stockRow ? (float) $stockRow->unrestricted_qty : 0.0;
        $newQty = $oldQty + $qty;

        $this->db->query(
            "UPDATE inv_product_stock SET unrestricted_qty = unrestricted_qty + ?
             WHERE company_id = ? AND location_id = ? AND product_id = ?",
            [$qty, $companyId, $locationId, $productId]
        );

        $this->logMovement([
            'location_id'    => $locationId,
            'product_id'     => $productId,
            'movement_type'  => 'mo_return',
            'quantity'       => $qty,
            'reference_type' => $payload['reference_type'] ?? null,
            'reference_id'   => $payload['reference_id']   ?? null,
            'notes'          => $payload['notes']           ?? null,
        ], $oldQty, $newQty);
    }


    protected function logAdjustment(array $payload) {
        
        $adjustment = new Models_InvAdjustment();
        $adjustment->adjustment_type = $payload["movement_type"] === "adjust_in" ? "increase" : "decrease";
        $adjustment->location_id = $payload["location_id"];
        $adjustment->product_id = $payload["product_id"];
        $adjustment->quantity = abs($payload["quantity"]);
        $adjustment->unit_cost = isset($payload["unit_cost"]) ? (float) $payload["unit_cost"] : null;
        $adjustment->reason = $payload["reason"] ?? null;
        $adjustment->notes = $payload["notes"] ?? null;
        
        $adjustment->company_id = $this->context->companyId;
        $adjustment->created_by = $this->context->userId;

        $adjustmentId = $adjustment->create();
        if ( !$adjustmentId ) {
            throw new Exception("Failed to create inventory adjustment");
        }

        return $adjustmentId;
    }



    public function getFormContext(): array
    {
        $companyId = $this->context->companyId;

        $movementTypeConfig = config('constants.inventory.stock_movement_type');
        $movementTypes = array_map(
            fn($k, $v) => ['id' => $k, 'name' => $v],
            array_keys($movementTypeConfig),
            $movementTypeConfig
        );

        $locations = $this->db->fetchAll(
            "SELECT id, name, code FROM company_locations WHERE company_id = ? AND status = 'active' ORDER BY name ASC",
            [$companyId]
        );

        $users = $this->db->fetchAll(
            "SELECT id, name FROM users WHERE company_id = ? AND status = 'active' ORDER BY name ASC",
            [$companyId]
        );

        $products = $this->db->fetchAll(
            "SELECT id, name FROM products WHERE company_id = ? AND status = 'active' AND stock_tracking_method IN ('quantity', 'lot', 'serial') ORDER BY name ASC",
            [$companyId]
        );

        return [
            'movement_types' => $movementTypes,
            'locations'      => array_map('get_object_vars', $locations),
            'users'          => array_map('get_object_vars', $users),
            'products'       => array_map('get_object_vars', $products),
        ];
    }


    public function list(TinyPHP_Request $request): array
    {
        $companyId    = $this->context->companyId;

        $columns = [
            'id'               => 'm.id',
            'created_at'       => 'm.created_at',
            'product_name'     => 'p.name',
            'uom_code'         => 'uom.code',
            'location'         => 'CASE WHEN l.code IS NOT NULL AND l.code <> "" THEN CONCAT(l.code, " / ", l.name) ELSE l.name END',
            'movement_type'    => 'm.movement_type',
            'qty_change'       => 'm.qty_change',
            'reference_type'   => 'm.reference_type',
            'reference_id'     => 'm.reference_id',
            'reference_number' => "CASE
                WHEN m.reference_type = 'po_grn'         THEN ref_grn.grn_number
                WHEN m.reference_type = 'sales_delivery' THEN ref_sd.dn_number
                WHEN m.reference_type = 'inv_adjustment' THEN CONCAT('ADJ-', LPAD(m.reference_id, 5, '0'))
                WHEN m.reference_type = 'mo_output'      THEN ref_mo_out_mo.mo_number
                WHEN m.reference_type = 'mo_allocation'  THEN ref_mo_alloc_mo.mo_number
                WHEN m.reference_type = 'mo_return'      THEN ref_mo_ret_mo.mo_number
                WHEN m.reference_type = 'return'          THEN ref_ret.return_number
                ELSE NULL
            END",
            'notes'            => 'm.notes',
            'created_by'       => 'u.name',
            'reference_mo_id'  => "CASE
                WHEN m.reference_type = 'mo_output'     THEN ref_mo_out_mo.id
                WHEN m.reference_type = 'mo_allocation' THEN ref_mo_alloc_mo.id
                WHEN m.reference_type = 'mo_return'     THEN ref_mo_ret_mo.id
                ELSE NULL
            END",
        ];

        $df = (new TinyPHP_DataFetch($request))
            ->table('inv_stock_movements AS m')
            ->joins(
                "LEFT JOIN products AS p ON p.id = m.product_id
                 LEFT JOIN uoms AS uom ON uom.id = p.base_uom_id
                 LEFT JOIN company_locations AS l ON l.id = m.location_id
                 LEFT JOIN users AS u ON u.id = m.created_by
                 LEFT JOIN purchase_order_grns AS ref_grn
                     ON m.reference_type = 'po_grn' AND ref_grn.id = m.reference_id
                 LEFT JOIN sales_deliveries AS ref_sd
                     ON m.reference_type = 'sales_delivery' AND ref_sd.id = m.reference_id
                 LEFT JOIN manufacturing_order_outputs AS ref_mo_out
                     ON m.reference_type = 'mo_output' AND ref_mo_out.id = m.reference_id
                 LEFT JOIN manufacturing_orders AS ref_mo_out_mo
                     ON ref_mo_out_mo.id = ref_mo_out.manufacturing_order_id
                 LEFT JOIN manufacturing_order_material_allocations AS ref_mo_alloc
                     ON m.reference_type = 'mo_allocation' AND ref_mo_alloc.id = m.reference_id
                 LEFT JOIN manufacturing_orders AS ref_mo_alloc_mo
                     ON ref_mo_alloc_mo.id = ref_mo_alloc.manufacturing_order_id
                 LEFT JOIN manufacturing_order_material_returns AS ref_mo_ret
                     ON m.reference_type = 'mo_return' AND ref_mo_ret.id = m.reference_id
                 LEFT JOIN manufacturing_orders AS ref_mo_ret_mo
                     ON ref_mo_ret_mo.id = ref_mo_ret.manufacturing_order_id
                 LEFT JOIN returns AS ref_ret
                     ON m.reference_type = 'return' AND ref_ret.id = m.reference_id"
            )
            ->columns($columns)
            ->where('m.company_id = ?', [$companyId]);

        $filterMovementTypes = $request->getInput('movement_types', 'array', []);
        if (!empty($filterMovementTypes)) {
            $placeholders = rtrim(str_repeat('?,', count($filterMovementTypes)), ',');
            $df->where("m.movement_type IN ($placeholders)", $filterMovementTypes);
        }

        $filterProductId = $request->getInput('product_id', 'Int', 0);
        if ($filterProductId) {
            $df->where('m.product_id = ?', [$filterProductId]);
        }

        $filterLocationId = $request->getInput('location_id', 'Int', 0);
        if ($filterLocationId) {
            $df->where('m.location_id = ?', [$filterLocationId]);
        }

        $filterPerformedBy = $request->getInput('performed_by', 'Int', 0);
        if ($filterPerformedBy) {
            $df->where('m.created_by = ?', [$filterPerformedBy]);
        }

        $filterDateFrom = $request->getInput('date_from', 'String', '');
        if ($filterDateFrom && strtotime($filterDateFrom)) {
            $df->where('m.created_at >= ?', [localToUtc($filterDateFrom . ' 00:00:00')]);
        }

        $filterDateTo = $request->getInput('date_to', 'String', '');
        if ($filterDateTo && strtotime($filterDateTo)) {
            $df->where('m.created_at <= ?', [localToUtc($filterDateTo . ' 23:59:59')]);
        }

        return $df->fetch();
    }


    protected function logMovement(array $payload, $oldQty, $newQty)
    {
        $movement = new Models_InvStockMovement();
        $movement->company_id = $this->context->companyId;
        $movement->location_id = $payload["location_id"];
        $movement->product_id = $payload["product_id"];
        $movement->movement_type = $payload["movement_type"];
        $movement->old_qty = $oldQty;
        $movement->qty_change = $payload["quantity"];
        $movement->new_qty = $newQty;
        $movement->reference_type = $payload["reference_type"] ?? null;
        $movement->reference_id = $payload["reference_id"] ?? null;
        $movement->notes = $payload["notes"] ?? null;
        $movement->created_by = $this->context->userId;

        if (!$movement->create()) {
            throw new Exception("Movement logging failed");
        }
    }


    public function logSerialHistory(int $serialId, int $productId, string $logType, string $title, ?string $refType = null, ?int $refId = null, array $meta = []): void
    {
        $this->db->insert('inv_serial_history', [
            'company_id'     => $this->context->companyId,
            'serial_id'      => $serialId,
            'product_id'     => $productId,
            'log_type'       => $logType,
            'title'          => $title,
            'reference_type' => $refType,
            'reference_id'   => $refId,
            'meta'           => !empty($meta) ? json_encode($meta) : null,
            'created_by'     => $this->context->userId,
            'created_at'     => date('Y-m-d H:i:s'),
        ]);
    }

}