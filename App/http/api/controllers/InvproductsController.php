<?php
class Api_InvProductsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceInvMovement(): Service_Inv_Movement {
        return new Service_Inv_Movement(tenantContext());
    }


    public function stockLocationsAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            return $this->handleGet($request);
        }
    }


    private function handleGet(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        $productId = $request->getInput("id", "Int", 0);

        $product = new Models_Product($productId);
        $trackingMethod = $product->stock_tracking_method;

        if( $trackingMethod == "serial" ) {
            $columns = [
                "location" => "l.name",
                "location_code" => "l.code",
                "prod_name" => "p.name",
                "serial_number" => "ins.serial_number",
                "serial_status" => "ins.status",
                "on_hand_qty" => "1",
                "reserved_qty" => "IF(ins.status='reserved',1,0)",
                "uom_code" => "uom.code",
                "uom_name" => "uom.name",
            ];

            $dataFetch = new TinyPHP_DataFetch($request);
            $results = $dataFetch
            ->table("inv_serial_stock AS iss")
            ->joins("INNER JOIN inv_serials AS ins ON iss.serial_id=ins.id
            INNER JOIN products p ON iss.product_id=p.id
            LEFT JOIN uoms AS uom ON uom.id=base_uom_id
            LEFT JOIN company_locations AS l ON iss.location_id=l.id")
            ->columns($columns)
            ->where("iss.company_id=? AND iss.product_id=?", [$companyId, $productId])
            ->fetch();
        } else {
            $columns = [
                "location" => "l.name",
                "location_code" => "l.code",
                "prod_name" => "p.name",
                "on_hand_qty" => "ips.on_hand_qty",
                "reserved_qty" => "ips.reserved_qty",
                "uom_code" => "uom.code",
                "uom_name" => "uom.name",
            ];

            $dataFetch = new TinyPHP_DataFetch($request);
            $results = $dataFetch
            ->table("inv_product_stock AS ips")
            ->joins("INNER JOIN products p ON ips.product_id=p.id
            LEFT JOIN uoms AS uom ON uom.id=base_uom_id
            LEFT JOIN company_locations AS l ON ips.location_id=l.id")
            ->columns($columns)
            ->where("ips.company_id=? AND ips.product_id=?", [$companyId, $productId])
            ->fetch();
        }

        return response($results)->sendJson();
    }


    public function adjustFormContextAction(TinyPHP_Request $request) {
        
        
        $productId = $request->getInput("id", "Int", 0);

        $companyId = tenantContext()->companyId;

        $product = new Models_Product($productId);
        if( $product->isEmpty || $product->company_id != $companyId ) {
            return response([], "You do not have permission to access this resource", 403)->sendJson();
        }

        $masterProd = $product->master;
        $prodBaseUom = $product->base_uom;
        $productDetails = [
            'id' => $product->id,
            'name' => $product->name,
            'master_id' => $masterProd->id,
            'master_name' => $masterProd->name,
            'stock_tracking_method' => $product->stock_tracking_method,
            'uom_name' => $prodBaseUom->name,
            'uom_code' => $prodBaseUom->code,
        ];

        $location = new Models_Location();
        $companyLocations = $location->getAll(["id", "name", "code", "type", "is_main"], ["company_id" => $companyId, "status" => "active"]);

        $prodStock = new Models_InvProductStock();
        $stockByLocation = $prodStock->getAll(["location_id", "on_hand_qty", "reserved_qty"], ["company_id" => $companyId, "product_id" => $productId]);

        $totalStock = 0;
        foreach($stockByLocation as $locStock) {
            $totalStock += (float) $locStock->on_hand_qty;
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

        return response($data)->sendJson();
    }


    public function adjustStockAction(TinyPHP_Request $request) {        

        $quantity = $request->getInput("quantity", "Int", 0);
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

        $movement = $this->serviceInvMovement();
        $response = $movement->record($payload);
        if( $response["success"] ) {
            return response($response["data"], "Stock adjusted successfully", 200)->sendJson();
        } else {
            return response([], "Failed to adjust stock", 422)->errors($response["errors"])->sendJson();
        }
    }


    public function serialOrLotNumbersAction(TinyPHP_Request $request) {
        
        $companyId = tenantContext()->companyId;
        
        $db = Service_TenantDBResolver::resolve($companyId);

        $productId = $request->getInput("id", "Int", 0);

        $product = new Models_Product($productId);
        if( $product->isEmpty || $product->company_id != $companyId ) {
            return response([], "You do not have permission to access this resource", 403)->sendJson();
        }

        if( !in_array($product->stock_tracking_method, ["serial", "lot"]) ) {
            return response([], "This product does not use serial or lot tracking", 400)->sendJson();
        }

        $serialOrLotNumbers = [];
        if( $product->stock_tracking_method === "serial" ) {
            $sql = "SELECT serial_number FROM inv_serials WHERE product_id=? AND status=?";
            $serialOrLotNumbers = $db->fetchCol($sql, [$productId, 'in_stock']);
        } else if( $product->stock_tracking_method === "lot" ) {
            // yet to implement logic
        }

        return response($serialOrLotNumbers)->sendJson();
    }
}
