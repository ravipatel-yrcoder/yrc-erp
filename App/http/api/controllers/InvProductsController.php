<?php
class Api_InvProductsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function stockLocationsAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            $this->handleGet($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }



    private function handleGet(TinyPHP_Request $request) {

        $companyId = auth()->getCompanyId();
        $productId = $request->getInput("id", "Int", 0);

        $product = new Models_Product($productId);
        $trackingMethod = $product->stock_tracking_method;

        if( $trackingMethod == "serial" )
        {
            $columns = [
                "location" => "l.name", 
                "location_code" => "l.code",
                "prod_name" => "p.name",
                "serial_number" => "ins.serial_number",
                "serial_status" => "ins.status",
                "available_qty" => "1",
                "reserved_qty" => "IF(ins.status='reserved',1,0)",
            ];

            $where = "iss.company_id=? AND iss.product_id=?";
            $whereBinding = [$companyId, $productId];
            
            $dataFetch = new TinyPHP_DataFetch($request);
            $results = $dataFetch
            ->table("inv_serial_stock AS iss")
            ->joins("INNER JOIN inv_serials AS ins ON iss.serial_id=ins.id
            INNER JOIN products p ON iss.product_id=p.id
            LEFT JOIN company_locations AS l ON iss.location_id=l.id")
            ->columns($columns)        
            ->where($where, $whereBinding)
            ->fetch();
        }
        else
        {
            $columns = [
                "location" => "l.name", 
                "location_code" => "l.code",
                "prod_name" => "p.name",
                "available_qty" => "ips.available_qty", 
                "reserved_qty" => "ips.reserved_qty",
            ];

            $where = "ips.company_id=? AND ips.product_id=?";
            $whereBinding = [$companyId, $productId];
            
            $dataFetch = new TinyPHP_DataFetch($request);
            $results = $dataFetch
            ->table("inv_product_stock AS ips")
            ->joins("INNER JOIN products p ON ips.product_id=p.id
            LEFT JOIN company_locations AS l ON ips.location_id=l.id")
            ->columns($columns)        
            ->where($where, $whereBinding)
            ->fetch();
        }
        
        response($results)->sendJson();
    }



    public function adjustFormContextAction(TinyPHP_Request $request) {

        global $db;

        $productId = $request->getInput("id", "Int", 0); // product
                
        $companyId = auth()->getCompanyId();

        $product = new Models_Product($productId);
        if( $product->isEmpty || $product->company_id != $companyId ) {
            response([], "You do not have permission to access this resource", 403)->sendJson();
        }

        $productDetails = [
            'id' => $product->id,
            'name' => $product->name,
            'master_id' => $product->master_id,
            'master_name' => $product->master->name,
            'stock_tracking_method' => $product->stock_tracking_method,
        ];

        // company locations
        $location = new Models_Location();
        $companyLocations = $location->getAll(["id", "name", "code", "type", "is_main"], ["company_id" => $companyId, "status" => "active"]);

        
        // stock by locations
        $prodStock = new Models_InvProductStock();
        $stockByLocation = $prodStock->getAll(["location_id", "available_qty", "reserved_qty"], ["company_id" => $companyId, "product_id" => $productId]);

        $totalStock = 0;
        foreach($stockByLocation as $locStock) {
            $totalStock += ((float) $locStock->available_qty + (float) $locStock->reserved_qty);
        }

        $stockDetails = [
            'total_stock' => $totalStock,
            'stock_by_location' => $stockByLocation,
        ];

        $data = [
            'locations' => $companyLocations,
            'product' => $productDetails,
            'stock_details' => $stockDetails,
        ];

        response($data)->sendJson();
    }


    public function adjustStockAction(TinyPHP_Request $request) {

        try {

            if( !$request->isMethod("post") ) {
                response([], "Method not allowed", 405)->sendJson();
            }

            $companyId = auth()->getCompanyId();
            $quantity = $request->getInput("quantity", "Int", 0); // quantity            
            $movementType = "adjust_out";
            if( $quantity > 0 ) {
                $movementType = "adjust_in";
            }

            $payload = [
                'location_id' => $request->getInput("location_id", "Int", 0),
                'product_id' => $request->getInput("id", "Int", 0),
                'quantity' => $quantity,
                'serial_or_lot_numbers' => $request->getInput("serial_or_lot_numbers", "Array", []),
                'movement_type' => $movementType,
                'notes' =>  $request->getInput("notes", "String", NULL),
            ];

            $movement = new Service_Inv_Movement($companyId);
            $response = $movement->record($payload);
            if( $response["success"] )
            {
                response($response["data"], "Stock adjusted successfully", 200)->sendJson();
            }
            else
            {
                response([], "Failed to adjust stock", 422)->errors($response["errors"])->sendJson();
            }

        } catch(Exception $e) {

            $error = $e->getMessage();
            response([], "Failed to adjust stock", 500)->errors([$error])->sendJson();
        }

    }


    /*public function addStockAction(TinyPHP_Request $request) {

        if( !$request->isMethod("post") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $productId = $request->getInput("id", "Int", 0); // product
                
        $companyId = auth()->getCompanyId();

        $product = new Models_Product($productId);
        if( $product->isEmpty || $product->company_id != $companyId ) {
            response([], "You do not have permission to access this resource", 403)->sendJson();
        }

        $action = "create";
        $invProdStock = new Models_InvProductStock();
        $invProdStock->fillFromRequest($request);

        $id = $invProdStock->create();
        if( $id )
        {
            $responseMessage = $action === "update" ? "Stock updated successfully" : "Stock added successfully";
            $responseCode = $action === "update" ? 200 : 201;
            response([], $responseMessage, $responseCode)->sendJson();
        }
        else
        {
            $errorCode = $invProdStock->getErrorCode();
            $errorMessage = $invProdStock->getErrorMessage();
            $errors = $invProdStock->getErrors();

            $responseCode = $errorCode ?: 422;
            $responseMessage = $action === "update" ? ($errorMessage ?: "Failed to update stock") : ( $errorMessage ?: "Failed to add stock");
            response([], $responseMessage, $responseCode)->errors($errors)->sendJson();
        }

    }*/


    public function serialOrLotNumbersAction(TinyPHP_Request $request) {

        global $db;

        $productId = $request->getInput("id", "Int", 0); // product
                
        $companyId = auth()->getCompanyId();

        $product = new Models_Product($productId);
        if( $product->isEmpty || $product->company_id != $companyId ) {
            response([], "You do not have permission to access this resource", 403)->sendJson();
        }

        if( !in_array($product->stock_tracking_method, ["serial", "lot"]) ) {
            response([], "This product does not use serial or lot tracking", 400)->sendJson();
        }

        $serialOrLotNumbers = [];
        if( $product->stock_tracking_method === "serial" ) {
            
            // fetch available serial numbers
            $sql = "SELECT serial_number FROM inv_serials WHERE product_id=? AND status=?";
            $serialOrLotNumbers = $db->fetchCol($sql, [$productId, 'in_stock']);            
        } else if( $product->stock_tracking_method === "lot" ) {
            // yet to implement logic
        }

        response($serialOrLotNumbers)->sendJson();
    }




}