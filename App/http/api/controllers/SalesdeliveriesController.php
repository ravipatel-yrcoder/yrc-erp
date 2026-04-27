<?php
class Api_SalesDeliveriesController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function serviceSoDelivery() : Service_So_Delivery {
        return new Service_So_Delivery(tenantContext());
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if ($request->isMethod("get")) {
            return $this->listDeliveries($request);
        }
        if ($request->isMethod("post")) {
            return $this->save($request);
        }        
    }


    public function entityAction(TinyPHP_Request $request) {
        
        if ($request->isMethod("get")) {
            return $this->show($request);
        }
        if ($request->isMethod("post")) {
            return $this->save($request);
        }        
    }


    public function formContextAction(TinyPHP_Request $request) {
        
        $dnId = $request->getInput("id", "Int", 0);
        $soId = $request->getInput("so_id", "Int", 0);
        
        $service = $this->serviceSoDelivery();
        $data = $service->getFormContext($dnId, $soId);

        return response($data)->sendJson();
    }


    public function soSearchAction(TinyPHP_Request $request) {
        
        $query = $request->getInput("q", "String", "");
        
        $service = $this->serviceSoDelivery();
        $data = $service->searchSalesOrders($query);

        return response($data)->sendJson();
    }


    public function statusAction(TinyPHP_Request $request) {        
        
        $id = $request->getInput("id", "Int", 0);
        $inputs = $request->getInputs();

        $service = $this->serviceSoDelivery();
        $response = $service->updateStatus($id, $inputs);

        if ($response["success"]) {
            return response($response["data"], "Delivery note status updated", 200)->sendJson();
        } else {
            return response([], "Failed to update delivery note status", 422)->errors($response["errors"] ?? [])->sendJson();
        }
    }


    public function historyAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->serviceSoDelivery();
        $data = $service->getHistory($id);

        return response($data)->sendJson();
    }


    private function listDeliveries(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
        $soId = $request->getInput("so_id", "Int", 0);

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id" => "dn.id",
            "dn_number" => "dn.dn_number",
            "so_number" => "so.so_number",
            "customer" => "c.display_name",
            "location" => "l.name",
            "status" => "dn.status",
            "dispatch_date" => "dn.dispatch_date",
            "delivery_date" => "dn.delivery_date",
            "items_count" => "(SELECT COUNT(*) FROM sales_delivery_items WHERE sales_delivery_id = dn.id)",
            "created_at" => "dn.created_at",
        ];

        $query = $dataFetch
            ->table("sales_deliveries AS dn")
            ->joins(
                "LEFT JOIN sales_orders AS so ON so.id = dn.sales_order_id
                 LEFT JOIN customers AS c ON c.id = dn.customer_id
                 LEFT JOIN company_locations AS l ON l.id = dn.location_id"
            )
            ->columns($columns)
            ->where("dn.company_id = ?", [$companyId]);

        if ($soId) {
            $query->where("dn.sales_order_id = ?", [$soId]);
        }

        $results = $query->fetch();

        return response($results)->sendJson();
    }


    private function show(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        
        $service = $this->serviceSoDelivery();
        $data = $service->getDetails($id);

        return response($data)->sendJson();
    }


    private function save(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $action = $id ? "update" : "create";

        $inputs = $request->getInputs();

        $service = $this->serviceSoDelivery();

        if ($action === "update") {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }

        if ($response["success"]) {
            
            $responseMessage = $action === "update" ? "Delivery note updated successfully" : "Delivery note created successfully";
            $responseCode = $action === "update" ? 200 : 201;
            
            return response($response["data"], $responseMessage, $responseCode)->sendJson();
        } else {
            
            $responseMessage = $action === "update" ? "Failed to update delivery note" : "Failed to create delivery note";
            return response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
        }
    }    
}
?>
