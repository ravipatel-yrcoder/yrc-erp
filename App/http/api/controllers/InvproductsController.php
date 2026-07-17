<?php
class Api_InvProductsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceInvMovement(): Service_Inv_Movement {
        return new Service_Inv_Movement(tenantContext());
    }


    public function stockWarehousesAction(TinyPHP_Request $request) {

        if( $request->isMethod("get") ) {
            return $this->handleGet($request);
        }
    }


    private function handleGet(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        $productId = $request->getInput("id", "Int", 0);

        $product = new Models_Product($productId);
        if (!in_array($product->stock_tracking_method, ['quantity', 'lot', 'serial'])) {
            return response([], "This product does not track inventory", 400)->sendJson();
        }

        $trackingMethod = $product->stock_tracking_method;

        if( $trackingMethod == "serial" ) {
            $columns = [
                "warehouse" => "w.name",
                "warehouse_code" => "w.code",
                "prod_name" => "p.name",
                "serial_number" => "ins.serial_number",
                "serial_status" => "ins.status",
                "unrestricted_qty" => "1",
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
            LEFT JOIN inv_warehouses AS w ON iss.warehouse_id=w.id")
            ->columns($columns)
            ->where("iss.company_id=? AND iss.product_id=?", [$companyId, $productId])
            ->fetch();
        } else {
            $columns = [
                "warehouse" => "w.name",
                "warehouse_code" => "w.code",
                "prod_name" => "p.name",
                "unrestricted_qty" => "ips.unrestricted_qty",
                "reserved_qty" => "ips.reserved_qty",
                "uom_code" => "uom.code",
                "uom_name" => "uom.name",
            ];

            $dataFetch = new TinyPHP_DataFetch($request);
            $results = $dataFetch
            ->table("inv_product_stock AS ips")
            ->joins("INNER JOIN products p ON ips.product_id=p.id
            LEFT JOIN uoms AS uom ON uom.id=base_uom_id
            LEFT JOIN inv_warehouses AS w ON ips.warehouse_id=w.id")
            ->columns($columns)
            ->where("ips.company_id=? AND ips.product_id=?", [$companyId, $productId])
            ->fetch();
        }

        return response($results)->sendJson();
    }


    public function adjustFormContextAction(TinyPHP_Request $request) {

        if (!tenantContext()->canDo('inventory_adjustments', 'write')) {
            return response([], "You do not have permission to adjust stock", 403)->sendJson();
        }

        $productId = $request->getInput("id", "Int", 0);

        $companyId = tenantContext()->companyId;

        $product = new Models_Product($productId);
        if( $product->isEmpty || $product->company_id != $companyId ) {
            return response([], "You do not have permission to access this resource", 403)->sendJson();
        }

        if (!in_array($product->stock_tracking_method, ['quantity', 'lot', 'serial'])) {
            return response([], "This product does not track inventory", 400)->sendJson();
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

        $warehouse = new Models_InvWarehouse();
        $companyWarehouses = $warehouse->getAll(["id", "name", "code", "type", "is_default"], ["company_id" => $companyId, "status" => "active"]);

        $prodStock = new Models_InvProductStock();
        $stockByWarehouse = $prodStock->getAll(["warehouse_id", "unrestricted_qty", "reserved_qty"], ["company_id" => $companyId, "product_id" => $productId]);

        $totalStock = 0;
        foreach($stockByWarehouse as $whStock) {
            $totalStock += (float) $whStock->unrestricted_qty;
        }

        $stockDetails = [
            'total_stock' => $totalStock,
            'stock_by_warehouse' => $stockByWarehouse,
        ];

        $settingsSvc = new Service_CompanySettings(tenantContext());

        $data = [
            'warehouses'    => $companyWarehouses,
            'product'       => $productDetails,
            'stock_details' => $stockDetails,
            'cost_method'   => $settingsSvc->get('inventory.cost_method', 'standard'),
        ];

        return response($data)->sendJson();
    }


    public function adjustStockAction(TinyPHP_Request $request) {

        if (!tenantContext()->canDo('inventory_adjustments', 'write')) {
            return response([], "You do not have permission to adjust stock", 403)->sendJson();
        }

        $quantity = $request->getInput("quantity", "Int", 0);
        $movementType = "adjust_out";
        if( $quantity > 0 ) {
            $movementType = "adjust_in";
        }

        $unitCostRaw = $request->getInput("unit_cost", "String", null);
        $unitCost    = ($unitCostRaw !== null && $unitCostRaw !== '') ? (float) $unitCostRaw : null;

        $companyId = tenantContext()->companyId;
        $multiWarehouse = Service_CompanySettings::isMultiWarehouseEnabled($companyId);
        $warehouseId = $multiWarehouse
            ? $request->getInput("warehouse_id", "Int", 0)
            : (Service_Company::getDefaultWarehouseId($companyId) ?? 0);

        $payload = [
            'warehouse_id'          => $warehouseId,
            'product_id'            => $request->getInput("id", "Int", 0),
            'quantity'              => $quantity,
            'unit_cost'             => $unitCost,
            'serial_or_lot_numbers' => $request->getInput("serial_or_lot_numbers", "Array", []),
            'movement_type'         => $movementType,
            'notes'                 => $request->getInput("notes", "String", NULL),
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

        $warehouseId = $request->getInput("warehouse_id", "Int", 0);
        $dnItemId    = $request->getInput("dn_item_id", "Int", 0);

        $serialOrLotNumbers = [];
        if( $product->stock_tracking_method === "serial" ) {
            if ($warehouseId > 0) {
                $sql = "SELECT ins.serial_number
                        FROM inv_serials AS ins
                        INNER JOIN inv_serial_stock AS iss ON iss.serial_id = ins.id AND iss.warehouse_id = ?
                        WHERE ins.company_id = ? AND ins.product_id = ? AND ins.status = 'in_stock'
                        ORDER BY ins.serial_number ASC";
                $inStock = $db->fetchCol($sql, [$warehouseId, $companyId, $productId]);
            } else {
                $sql = "SELECT serial_number FROM inv_serials WHERE company_id = ? AND product_id = ? AND status = 'in_stock' ORDER BY serial_number ASC";
                $inStock = $db->fetchCol($sql, [$companyId, $productId]);
            }

            // When editing an existing DN item, also include serials already reserved for it
            $reserved = [];
            if ($dnItemId > 0) {
                $rSql = "SELECT sdis.serial_number
                         FROM sales_delivery_item_serials AS sdis
                         INNER JOIN inv_serials AS ins ON ins.id = sdis.serial_id
                         WHERE sdis.company_id = ? AND sdis.sales_delivery_item_id = ? AND ins.status = 'reserved'
                         ORDER BY sdis.serial_number ASC";
                $reserved = $db->fetchCol($rSql, [$companyId, $dnItemId]);
            }

            // Merge: reserved first (pre-selected), then in_stock, no duplicates
            $serialOrLotNumbers = array_values(array_unique(array_merge($reserved, $inStock)));

        } else if( $product->stock_tracking_method === "lot" ) {
            // yet to implement logic
        }

        return response($serialOrLotNumbers)->sendJson();
    }
}
