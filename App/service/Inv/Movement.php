<?php
class Service_Inv_Movement extends Service_Base {
    
    protected $companyId;

    public function __construct($companyId) {
        $this->companyId = $companyId;
    }


    /**
     * Public entry point for all inventory movements
     */
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

        global $db;


        // Begin transaction
        $db->startTransaction();

        try {

            // Dispatch movement to correct handler
            $result = $this->dispatchMovement($payload);


            // Commit
            $db->commit();


            return [
                "success" => true,
                "data"    => $result,
            ];

        } catch (Exception $e) {

            $db->rollBack();
            throw $e; // SYSTEM ERROR → Controller will return 500
        }
    }


    protected function validatePayload(array $payload)  {

        global $db;

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
        if( $location->isEmpty || $location->company_id != $this->companyId ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Location"), "location_id");            
        }

        $product = new Models_Product($productId);
        if( $product->isEmpty || $product->company_id != $this->companyId ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Product"), "product_id");
            $isProductValid = false;
        }

        if(!is_numeric($quantity)) {
            $this->addError(validationErrMsg("number", "Quantity"), "quantity");
        }


        if( $isProductValid === true ) {

            $stockTrackingMethod = strtoupper($product->stock_tracking_method);
            if( $stockTrackingMethod === "SERIAL" ) {

                if (count($serialOrLotNumbers) !== abs($quantity)) {
                    $this->addError(validationErrMsg("does_not_match_qty", "Serial numbers"), "serial_or_lot_numbers");
                }
                else {
                    
                    if( $movementType === "adjust_in" )
                    {
                        $placeholders = rtrim(str_repeat('?,', count($serialOrLotNumbers)), ',');
                        $sql = "SELECT serial_number FROM inv_serials WHERE company_id = ? AND serial_number IN ($placeholders)";
                        $existingSerialNumbers = $db->fetchCol($sql, array_merge([$this->companyId], $serialOrLotNumbers));
                        if( count($existingSerialNumbers) ) {
                            $this->addError(validationErrMsg("duplicate", implode(",", $existingSerialNumbers)." serial numbers"), "serial_or_lot_numbers");
                        }
                    }
                    else if( $movementType === "adjust_out" )
                    {
                        $placeholders = rtrim(str_repeat('?,', count($serialOrLotNumbers)), ',');
                        $sql = "SELECT serial_number, status FROM inv_serials WHERE company_id = ? AND serial_number IN ($placeholders)";
                        $existingSerialNumbers = $db->fetchAll($sql, array_merge([$this->companyId], $serialOrLotNumbers));

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
                            $stock->fetchByProperty(["company_id", "location_id", "product_id"], [$this->companyId, $locationId, $productId]);

                            if( $stock->isEmpty ) {
                                $this->addError(validationErrMsg("no_stock_adjusted",""), "location_id");
                            } else {

                                if( $stock->available_qty < abs($quantity) ) {
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

            case "purchase":
                //return $this->adjustIn($payload);

            case "sale":
                //return $this->adjustOut($payload);

            case "transfer_in":
                //return $this->adjustIn($payload);

            case "transfer_out":
                //return $this->adjustOut($payload);

            case "consume":
                //return $this->adjustOut($payload);

            case "produce":
                //return $this->adjustIn($payload);

            default:
                throw new Exception("Unknown movement type: ".$payload["movement_type"]);
        }
    }


    protected function adjustIn(array $payload) {

        $companyId = $this->companyId;
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
        if( $stock->isEmpty ) {

            // create
            $stock->location_id = $locationId;
            $stock->product_id = $productId;
            $stock->available_qty = $quantity;
            $id = $stock->create();

            if( !$id ) {
                throw new Exception("Failed to adjust stock");
            }

            $oldQty = 0;
            $newQty = $quantity;

        } else {

            // update
            $oldQty = $stock->available_qty;
            $newQty = $oldQty + $quantity;
            
            $stock->available_qty = $newQty;
            $saved = $stock->update();
            if( !$saved ) {
                throw new Exception("Failed to adjust stock");
            }
        }

        // increaes Lot or Serial
        $this->increaseLotOrSerial($payload);


        // Log movement
        $this->logMovement($payload, $oldQty, $newQty);


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
        $productId = $payload["product_id"];
        $product = new Models_Product($productId);

        // SAFETY GUARD — prevent misuse
        if ($product->stock_tracking_method !== "serial") {
            throw new Exception("Serial tracking is not enabled for this product.");
        }

        $location_id = $payload["location_id"];
        $serialNumbers = $payload["serial_or_lot_numbers"];

        // Insert serials
        foreach ($serialNumbers as $sn) {
            
            $serial = new Models_InvSerial();
            $serial->product_id = $productId;
            $serial->serial_number = trim($sn);
            $serial->status = "in_stock";
            $serialId = $serial->create();        
            if( !$serialId ) {
                throw new Exception("Failed to create serial #{$sn}");
            }
            
            $serialStock = new Models_InvSerialStock();
            $serialStock->product_id = $productId;
            $serialStock->location_id = $location_id;
            $serialStock->serial_id = $serialId;
            if( !$serialStock->create() ) {
                throw new Exception("Failed to add serial #{$sn} to stock");
            }

            $history = new Models_InvSerialHistory();
            $history->product_id = $productId;
            $history->serial_id = $serialId;
            $history->changed_field = "status";
            $history->old_value = null;
            $history->new_value = "in_stock";
            $history->reason = "Added to inventory";
            $history->reference_type = $payload["reference_type"] ?? null;
            $history->reference_id = $payload["reference_id"] ?? null;
            if( !$history->create() ) {
                throw new Exception("Failed to record history");
            }
        }
    }


    protected function adjustOut(array $payload) {
        
        $companyId = $this->companyId;
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

        $oldQty = $stock->available_qty;
        $newQty = $oldQty - abs($quantity);

        $stock->available_qty = $newQty;
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
        $companyId = $this->companyId;
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
            

            $history = new Models_InvSerialHistory();
            $history->product_id = $productId;
            $history->serial_id = $serialId;
            $history->changed_field = "status";
            $history->old_value = $serialoldStatus;
            $history->new_value = $serialNewStatus;
            $history->reason = "Removed from inventory";
            $history->reference_type = $payload["reference_type"] ?? null;
            $history->reference_id = $payload["reference_id"] ?? null;
            if( !$history->create() ) {
                throw new Exception("Failed to record history");
            }
        }
    }


    protected function logAdjustment(array $payload) {
        
        $adjustment = new Models_InvAdjustment();
        $adjustment->adjustment_type =
            $payload["movement_type"] === "adjust_in"
                ? "increase"
                : "decrease";

        $adjustment->location_id = $payload["location_id"];
        $adjustment->product_id = $payload["product_id"];
        $adjustment->quantity = abs($payload["quantity"]);
        $adjustment->reason = $payload["reason"] ?? null;
        $adjustment->notes = $payload["notes"] ?? null;
        $adjustmentId = $adjustment->create();
        if ( !$adjustmentId ) {
            throw new Exception("Failed to create inventory adjustment");
        }

        return $adjustmentId;
    }



    protected function logMovement(array $payload, $oldQty, $newQty)
    {
        $movement = new Models_InvStockMovement();
        $movement->location_id = $payload["location_id"];
        $movement->product_id = $payload["product_id"];
        $movement->movement_type = $payload["movement_type"];
        $movement->old_qty = $oldQty;
        $movement->qty_change = $payload["quantity"];
        $movement->new_qty = $newQty;
        $movement->reference_type = $payload["reference_type"] ?? null;
        $movement->reference_id = $payload["reference_id"] ?? null;
        $movement->notes = $payload["notes"] ?? null;

        if (!$movement->create()) {
            throw new Exception("Movement logging failed");
        }
    }

}