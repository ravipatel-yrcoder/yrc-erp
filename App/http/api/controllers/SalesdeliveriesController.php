<?php
class Api_SalesDeliveriesController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function indexAction(TinyPHP_Request $request) {

        if ($request->isMethod("get")) {
            $this->listDeliveries($request);
        }
        if ($request->isMethod("post")) {
            $this->save($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    public function entityAction(TinyPHP_Request $request) {

        if ($request->isMethod("get")) {
            $this->show($request);
        }
        if ($request->isMethod("post")) {
            $this->save($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    public function formContextAction(TinyPHP_Request $request) {

        if (!$request->isMethod("get")) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $dnId = $request->getInput("id", "Int", 0);
        $soId = $request->getInput("so_id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;

        try {

            $dnService = new Service_So_Delivery(new Service_TenantContext($companyId, $userId));
            $data = $dnService->getFormContext($dnId, $soId);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch form context", 500)->sendJson();
        }
    }


    public function soSearchAction(TinyPHP_Request $request) {

        if (!$request->isMethod("get")) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $query     = $request->getInput("q", "String", "");
        $companyId = auth()->getCompanyId();
        $userId    = auth()->user()->id;

        try {

            $dnService = new Service_So_Delivery(new Service_TenantContext($companyId, $userId));
            $data = $dnService->searchSalesOrders($query);

            response($data)->sendJson();

        } catch (Exception $e) {
            response([], "Failed to search sales orders", 500)->sendJson();
        }
    }


    public function statusAction(TinyPHP_Request $request) {

        if ($request->isMethod("post")) {
            $this->updateStatus($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    public function historyAction(TinyPHP_Request $request) {

        if (!$request->isMethod("get")) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $id        = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId    = auth()->user()->id;

        try {

            $dnService = new Service_So_Delivery(new Service_TenantContext($companyId, $userId));
            $data = $dnService->getHistory($id);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch delivery history", 500)->sendJson();
        }
    }


    private function listDeliveries(TinyPHP_Request $request) {

        $companyId = auth()->getCompanyId();
        $soId      = $request->getInput("so_id", "Int", 0);

        $dataFetch = new TinyPHP_DataFetch($request);

        $columns = [
            "id"            => "dn.id",
            "dn_number"     => "dn.dn_number",
            "so_number"     => "so.so_number",
            "customer"      => "c.display_name",
            "location"      => "l.name",
            "status"        => "dn.status",
            "dispatch_date" => "dn.dispatch_date",
            "delivery_date" => "dn.delivery_date",
            "items_count"   => "(SELECT COUNT(*) FROM sales_delivery_items WHERE sales_delivery_id = dn.id)",
            "created_at"    => "dn.created_at",
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

        response($results)->sendJson();
    }


    private function show(TinyPHP_Request $request) {

        $id        = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId    = auth()->user()->id;

        try {

            $dnService = new Service_So_Delivery(new Service_TenantContext($companyId, $userId));
            $data = $dnService->getDetails($id);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch delivery note", 500)->sendJson();
        }
    }


    private function save(TinyPHP_Request $request) {

        try {

            $id     = $request->getInput("id", "Int", 0);
            $action = $id ? "update" : "create";

            $companyId = auth()->getCompanyId();
            $userId    = auth()->user()->id;
            $inputs    = $request->getInputs();

            $dnService = new Service_So_Delivery(new Service_TenantContext($companyId, $userId));

            if ($action === "update") {
                $response = $dnService->update($id, $inputs);
            } else {
                $response = $dnService->create($inputs);
            }

            if ($response["success"]) {
                $responseMessage = $action === "update" ? "Delivery note updated successfully" : "Delivery note created successfully";
                $responseCode    = $action === "update" ? 200 : 201;
                response($response["data"], $responseMessage, $responseCode)->sendJson();
            } else {
                $responseMessage = $action === "update" ? "Failed to update delivery note" : "Failed to create delivery note";
                response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
            }

        } catch (Service_Exception $e) {
            $statusCode = $e->getStatusCode() ?: 500;
            response([], $e->getMessage(), $statusCode)->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to save delivery note", 500)->errors([$e->getMessage()])->sendJson();
        }
    }


    private function updateStatus(TinyPHP_Request $request) {

        try {

            $id        = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId    = auth()->user()->id;
            $inputs    = $request->getInputs();

            $dnService = new Service_So_Delivery(new Service_TenantContext($companyId, $userId));
            $response  = $dnService->updateStatus($id, $inputs);

            if ($response["success"]) {
                response($response["data"], "Delivery note status updated", 200)->sendJson();
            } else {
                response([], "Failed to update delivery note status", 422)->errors($response["errors"] ?? [])->sendJson();
            }

        } catch (Service_Exception $e) {
            $statusCode = $e->getStatusCode() ?: 500;
            response([], $e->getMessage(), $statusCode)->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to update delivery note status", 500)->errors([$e->getMessage()])->sendJson();
        }
    }
}
?>
