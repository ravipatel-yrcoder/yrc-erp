<?php
class Api_PurchaseReceiptsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function servicePoGrn(): Service_Po_Grn {
        return new Service_Po_Grn(tenantContext());
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            return $this->list($request);
        }
        else if( $request->isMethod("post") ) {
            return $this->save($request);
        }
    }


    public function formContextAction(TinyPHP_Request $request) {
        
        $receiptId = $request->getInput("id", "Int", 0);

        $service = $this->servicePoGrn();
        $data = $service->getEditFormContext($receiptId);

        return response($data)->sendJson();
    }


    public function entityAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            return $this->show($request);
        }
        else if( $request->isMethod("post") ) {
            return $this->save($request);
        }
    }


    public function statusAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            // yet to implement logic
        }
        else if( $request->isMethod("post") ) {
            return $this->updateStatus($request);
        }        
    }


    public function historyAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->servicePoGrn();
        $data = $service->getHistory($id);

        return response($data)->sendJson();
    }


    private function list(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        $poId = $request->getInput("po_id", "Int", 0);

        $whereClause = "grn.company_id = ?";
        $whereBind = [$companyId];
        if( $poId ) {
            $whereClause .= " AND grn.purchase_order_id = ?";
            $whereBind[] = $poId;
        }

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = ["id" => "grn.id", "receipt_number" => "grn.grn_number", "create_date" => "grn.created_at", "received_date" => "grn.received_date", "vendor" => "v.display_name", "purchase_order_id" => "po.id", "po_number" => "po.po_number", "status" => "grn.status", "items_count" => "count(grni.id)"];

        $results = $dataFetch
        ->table("purchase_order_grns AS grn")
        ->joins("
        LEFT JOIN purchase_orders AS po ON grn.purchase_order_id=po.id
        LEFT JOIN vendors AS v ON po.vendor_id=v.id
        LEFT JOIN purchase_order_grn_items AS grni ON grn.id=grni.purchase_order_grn_id")
        ->columns($columns)
        ->where($whereClause, $whereBind)
        ->groupBy("grn.id")
        ->fetch();

        return response($results)->sendJson();
    }


    private function save(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $action = "create";
        if( $id ) {
            $action = "update";
        }

        $inputs = $request->getInputs();

        $service = $this->servicePoGrn();
        if( $action === "update" ) {
            $response = $service->update($id, $inputs);
        } else {
            $poId = $request->getInput("purchase_order_id", "Int", 0);
            $response = $service->create($poId, $inputs);
        }

        if( $response["success"] ) {
            
            $responseMessage = $action === "update" ? "Purchase receipt updated successfully" : "Purchase receipt created successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response($response["data"], $responseMessage, $responseCode)->sendJson();
        } else {
            
            $responseMessage = $action === "update" ? "Failed to update purchase receipt" : "Failed to create purchase receipt";
            
            return response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
        }
    }


    private function show(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->servicePoGrn();
        $data = $service->getDetails($id);

        return response($data)->sendJson();
    }


    private function updateStatus(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);        
        $inputs = $request->getInputs();

        $service = $this->servicePoGrn();
        $response = $service->updateStatus($id, $inputs);

        if( $response["success"] ) {
            return response($response["data"], "Status updated successfully", 200)->sendJson();
        } else {
            return response([], "Failed to update status", 422)->errors($response["errors"])->sendJson();
        }
    }
}
