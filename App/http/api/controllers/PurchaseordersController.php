<?php
class Api_PurchaseOrdersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function servicePurchaseOrder(): Service_Po_Order {
        return new Service_Po_Order(tenantContext());
    }

    private function servicePOGrn(): Service_Po_Grn {
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
        
        $id = $request->getInput("id", "Int", 0);

        $service = $this->servicePurchaseOrder();
        $data = $service->getFormContext($id);

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
            
            $id = $request->getInput("id", "Int", 0);    
        
            $service = $this->servicePurchaseOrder();
            $data = $service->getStatus($id);

            return response($data)->sendJson();
        }
        else if( $request->isMethod("post") ) {
            return $this->updateStatus($request);
        }
    }


    public function receiveFormContextAction(TinyPHP_Request $request) {
        
        $poId = $request->getInput("id", "Int", 0);
        
        $grnService = $this->servicePOGrn();
        $data = $grnService->getCreateFormContext($poId);

        return response($data)->sendJson();
    }


    public function historyAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);

        $service = $this->servicePurchaseOrder();
        $data = $service->getHistory($id);

        return response($data)->sendJson();
    }


    public function sendEmailAction(TinyPHP_Request $request) {

        $id     = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->servicePurchaseOrder();
        $result  = $service->sendEmail($id, $inputs);

        if ($result["success"]) {
            return response([], "Email sent successfully", 200)->sendJson();
        }

        return response([], "Failed to send email", 422)->errors($result["errors"])->sendJson();
    }


    private function list(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = ["id" => "po.id", "po_number" => "po.po_number", "order_date" => "po.order_date", "vendor" => "v.display_name", "reference" => "po.reference", "status" => "po.status", "exp_delivery_date" => "po.expected_delivery_date", "amount" => "SUM(poi.line_total)"];

        $results = $dataFetch
        ->table("purchase_orders AS po")
        ->joins("LEFT JOIN vendors AS v ON po.vendor_id=v.id
        LEFT JOIN purchase_order_items AS poi ON po.id=poi.purchase_order_id")
        ->columns($columns)
        ->where("po.company_id = ?", [$companyId])
        ->groupBy("po.id")
        ->fetch();

        response($results)->sendJson();
    }


    private function save(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $action = "create";
        if( $id ) {
            $action = "update";
        }

        $inputs = $request->getInputs();

        $service = $this->servicePurchaseOrder();
        if( $action === "update" ) {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }

        if( $response["success"] ) {
            
            $responseMessage = $action === "update" ? "Purchase order updated successfully" : "Purchase order created successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response($response["data"], $responseMessage, $responseCode)->sendJson();
        } else {
            
            $responseMessage = $action === "update" ? "Failed to update purchase order" : "Failed to create purchase order";
            return response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
        }
    }


    private function show(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);    

        $service = $this->servicePurchaseOrder();
        $data = $service->getDetails($id);

        return response($data)->sendJson();
    }


    private function updateStatus(TinyPHP_Request $request) {

        $id     = $request->getInput("id", "Int", 0);
        $status = $request->getInput("status", "String", "");
        $inputs = $request->getInputs();

        $service = $this->servicePurchaseOrder();

        if ($status === 'cancelled') {
            $response = $service->cancel($id);
        } else {
            $response = $service->updateStatus($id, $inputs);
        }

        if( $response["success"] ) {
            return response($response["data"], "Status updated successfully", 200)->sendJson();
        } else {
            return response([], "Failed to update status", 422)->errors($response["errors"])->sendJson();
        }
    }
}