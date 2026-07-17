<?php
class Api_WarehousesController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function indexAction(TinyPHP_Request $request) {

        if( $request->isMethod("get") ) {
            return $this->handleGet($request);
        }
        else if( $request->isMethod("post") ) {
            return $this->handlePost($request);
        }
        else if( $request->isMethod("delete") ) {
            return $this->handleDelete($request);
        }
    }



    private function handleGet(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = ["id" => "w.id","name" => "w.name","code" => "w.code","type" => "w.type","address" => "w.address_line1", "address_line2" => "w.address_line2", "city" => "w.city", "state" => "w.state", "country" => "w.country", "zip" => "w.zip", "status" => "w.status"];
        
        $results = $dataFetch
        ->table("inv_warehouses AS w")
        ->columns($columns)
        ->virtualColumns(['address' => ['address_line1', 'address_line2', 'city', 'state', 'country', 'zip']])
        ->where("w.company_id = ?", [$companyId])
        ->fetch();

        return response($results)->sendJson();    
    }


    private function handlePost(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $companyId = tenantContext()->companyId;

        $action = "create";
        if( $id ) {
            $action = "update";
        }

        $warehouse = new Models_InvWarehouse($id);
        $warehouse->fillFromRequest($request);
        $warehouse->is_default = $request->getInput("is_default", "int", 0);
        $warehouse->status = $request->getInput("status", "string", "inactive");

        // Deactivation guard
        if ($action === 'update') {
            $existing = new Models_InvWarehouse($id);
            if ($existing->status === 'active' && $warehouse->status === 'inactive') {

                if ($existing->is_default) {
                    return response([], 'Cannot deactivate the default warehouse. Set another warehouse as default first.', 422)->sendJson();
                }

                $db = db();

                $hasStock = $db->fetchOne(
                    "SELECT 1 FROM inv_product_stock
                     WHERE warehouse_id = ? AND company_id = ?
                       AND (unrestricted_qty + reserved_qty + blocked_qty + quality_qty) > 0
                     LIMIT 1",
                    [$id, $companyId]
                );
                if ($hasStock) {
                    return response([], 'Cannot deactivate warehouse: it has stock on hand. Transfer or adjust all stock to zero first.', 422)->sendJson();
                }

                $hasActiveSO = $db->fetchOne(
                    "SELECT 1 FROM sales_orders
                     WHERE source_warehouse_id = ? AND company_id = ?
                       AND status NOT IN ('cancelled', 'delivered')
                     LIMIT 1",
                    [$id, $companyId]
                );
                if ($hasActiveSO) {
                    return response([], 'Cannot deactivate warehouse: it is referenced by active sales orders.', 422)->sendJson();
                }

                $hasActivePO = $db->fetchOne(
                    "SELECT 1 FROM purchase_orders
                     WHERE company_id = ? AND status NOT IN ('cancelled', 'received')
                       AND receiving_warehouse_id = ?
                     LIMIT 1",
                    [$companyId, $id]
                );
                if ($hasActivePO) {
                    return response([], 'Cannot deactivate warehouse: it is referenced by active purchase orders.', 422)->sendJson();
                }

                $hasActiveMO = $db->fetchOne(
                    "SELECT 1 FROM manufacturing_orders
                     WHERE company_id = ? AND status NOT IN ('cancelled', 'completed')
                       AND (source_warehouse_id = ? OR destination_warehouse_id = ?)
                     LIMIT 1",
                    [$companyId, $id, $id]
                );
                if ($hasActiveMO) {
                    return response([], 'Cannot deactivate warehouse: it is referenced by active manufacturing orders.', 422)->sendJson();
                }
            }
        }

        if( $action === "update" ) {
            $id = $warehouse->update();
        } else {
            $warehouse->company_id = $companyId;
            $id = $warehouse->create();
        }

        if( $id )
        {
            $responseMessage = $action === "update" ? "Warehouse updated successfully" : "Warehouse added successfully";
            $responseCode = $action === "update" ? 200 : 201;

            return response([], $responseMessage, $responseCode)->sendJson();
        }
        else
        {
            $errorCode = $warehouse->getErrorCode();
            $errorMessage = $warehouse->getErrorMessage();
            $errors = $warehouse->getErrors();

            $responseCode = $errorCode ?: 422;
            $responseMessage = $action === "update" ? ($errorMessage ?: "Warehouse updated successfully") : ( $errorMessage ?: "Warehouse added successfully");
            return response([], $responseMessage, $responseCode)->errors($errors)->sendJson();
        }
    }



    private function handleDelete(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $companyId = tenantContext()->companyId;

        $warehouse = new Models_InvWarehouse($id);
        if( $warehouse->isEmpty ) {
            return response([], "The requested resource could not be found", 404)->sendJson();
        }

        if( $warehouse->company_id !== $companyId ) {
            return response([], "You do not have permission to perform this action", 403)->sendJson();
        }

        if( $warehouse->is_default ) {
            return response([], "Cannot delete the default warehouse. Set another warehouse as default first.", 422)->sendJson();
        }

        $db = db();

        $hasStock = $db->fetchOne(
            "SELECT 1 FROM inv_product_stock
             WHERE warehouse_id = ? AND company_id = ?
               AND (unrestricted_qty + reserved_qty + blocked_qty + quality_qty) > 0
             LIMIT 1",
            [$id, $companyId]
        );
        if( $hasStock ) {
            return response([], "Cannot delete warehouse: it has stock on hand. Transfer or adjust all stock to zero first.", 422)->sendJson();
        }

        $hasActiveSO = $db->fetchOne(
            "SELECT 1 FROM sales_orders
             WHERE source_warehouse_id = ? AND company_id = ?
               AND status NOT IN ('cancelled', 'delivered')
             LIMIT 1",
            [$id, $companyId]
        );
        if( $hasActiveSO ) {
            return response([], "Cannot delete warehouse: it is referenced by active sales orders.", 422)->sendJson();
        }

        $hasActivePO = $db->fetchOne(
            "SELECT 1 FROM purchase_orders
             WHERE company_id = ? AND status NOT IN ('cancelled', 'received')
               AND receiving_warehouse_id = ?
             LIMIT 1",
            [$companyId, $id]
        );
        if( $hasActivePO ) {
            return response([], "Cannot delete warehouse: it is referenced by active purchase orders.", 422)->sendJson();
        }

        $hasActiveMO = $db->fetchOne(
            "SELECT 1 FROM manufacturing_orders
             WHERE company_id = ? AND status NOT IN ('cancelled', 'completed')
               AND (source_warehouse_id = ? OR destination_warehouse_id = ?)
             LIMIT 1",
            [$companyId, $id, $id]
        );
        if( $hasActiveMO ) {
            return response([], "Cannot delete warehouse: it is referenced by active manufacturing orders.", 422)->sendJson();
        }

        $hasMovements = $db->fetchOne(
            "SELECT 1 FROM inv_stock_movements
             WHERE warehouse_id = ? AND company_id = ?
             LIMIT 1",
            [$id, $companyId]
        );
        if( $hasMovements ) {
            return response([], "Cannot delete warehouse: it has stock movement history. Deactivate it instead to preserve the audit trail.", 422)->sendJson();
        }

        $warehouse->delete();

        if( $warehouse->getDeletedRows() > 0 ) {
            return response([], "Warehouse deleted successfully", 200)->sendJson();
        }
        else
        {
            $errorCode = $warehouse->getErrorCode();
            $errorMessage = $warehouse->getErrorMessage();
            $errors = $warehouse->getErrors();

            $responseCode = $errorCode ?: 422;
            $responseMessage = $errorMessage ?: "Failed to delete warehouse";

            return response([], $responseMessage, $responseCode)->errors($errors)->sendJson();
        }
    }


    public function formContextAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $companyId = tenantContext()->companyId;

        $warehouseDetails = [];
        if( $id )
        {
            $warehouse = new Models_InvWarehouse($id);
            if( $warehouse->isEmpty ) {
                return response([], "The requested resource could not be found", 404)->sendJson();
            }

            if( $warehouse->company_id != $companyId ) {
                return response([], "You do not have permission to access this resource", 403)->sendJson();
            }

            $warehouseDetails = $warehouse->toArray();
            $warehouseDetails['id'] = $id;
        }

        $data = ['warehouse_details' => $warehouseDetails];

        return response($data)->sendJson();
    }
}
