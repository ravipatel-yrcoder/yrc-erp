<?php
class Api_SalesOrdersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceSalesOrder() : Service_So_Order {
        return new Service_So_Order(tenantContext());
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
        $leadId = $request->getInput("lead_id", "Int", 0);
        
        $service = $this->serviceSalesOrder();
        $data = $service->getFormContext($id, $leadId);

        return response($data)->sendJson();
    }


    public function customersSearchAction(TinyPHP_Request $request) {
        
        $query = $request->getInput("q", "String", "");
        
        $service = $this->serviceSalesOrder();
        $data = $service->searchCustomers($query);

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
        
        $id = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->serviceSalesOrder();
        $response = $service->updateStatus($id, $inputs);

        if( $response["success"] ) {
            return response($response["data"], "Status updated successfully", 200)->sendJson();
        } elseif (!empty($response["warning"])) {
            return response([], "Please review stock warnings", 200)->warnings($response["warnings"])->warningType($response["warning_type"])->sendJson();
        } else {
            return response([], "Failed to update status", 422)->errors($response["errors"])->sendJson();
        }
    }


    public function sendEmailAction(TinyPHP_Request $request) {    

        $id = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->serviceSalesOrder();
        $result = $service->sendEmail($id, $inputs);

        if( $result["success"] ) {
            return response([], "Email sent successfully", 200)->sendJson();
        } else {
            return response([], "Failed to send email", 422)->errors($result["errors"])->sendJson();
        }
    }


    public function historyAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->serviceSalesOrder();
        $data = $service->getHistory($id);

        response($data)->sendJson();
    }


    private function list(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        $leadId = $request->getInput("lead_id", "Int", 0);
        $excludeQuotations = $request->getInput("exclude_quotations", "Int", 0);

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id" => "so.id",
            "so_number" => "so.so_number",
            "order_date" => "so.order_date",
            "customer" => "c.display_name",
            "reference" => "so.reference",
            "status" => "so.status",
            "expected_delivery_date" => "so.expected_delivery_date",
            "total_amount" => "so.total_amount",
            "lead_id" => "so.lead_id",
        ];

        $dataFetch
            ->table("sales_orders AS so")
            ->joins("LEFT JOIN customers AS c ON so.customer_id = c.id")
            ->columns($columns)
            ->where("so.company_id = ?", [$companyId]);

        if ($leadId > 0) {
            $dataFetch->where("so.lead_id = ?", [$leadId]);
        }

        if( $excludeQuotations == 1 ) {
            $dataFetch->where("so.status != ?", ['draft']);
        }

        $results = $dataFetch->fetch();

        response($results)->sendJson();
    }


    private function save(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $action = $id ? "update" : "create";

        $inputs = $request->getInputs();

        $service = $this->serviceSalesOrder();
        if( $action === "update" ) {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }

        if( $response["success"] ) {
            
            $responseMessage = $action === "update" ? "Sales order updated successfully" : "Sales order created successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response($response["data"], $responseMessage, $responseCode)->sendJson();
        } elseif (!empty($response["warning"])) {
            
            return response([], "Please review stock warnings", 200)->warnings($response["warnings"])->warningType($response["warning_type"])->sendJson();
        } else {
            
            $responseMessage = $action === "update" ? "Failed to update sales order" : "Failed to create sales order";
            return response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
        }
    }


    private function show(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->serviceSalesOrder();
        $data = $service->getDetails($id);

        return response($data)->sendJson();
    }
}
