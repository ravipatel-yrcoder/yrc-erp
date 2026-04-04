<?php
class Api_SalesOrdersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function indexAction(TinyPHP_Request $request) {

        if( $request->isMethod("get") ) {
            $this->list($request);
        }
        else if( $request->isMethod("post") ) {
            $this->save($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    public function formContextAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $id = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;

        try {

            $soService = new Service_So_Order(new Service_TenantContext($companyId, $userId));
            $data = $soService->getFormContext($id);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch form context", 500)->sendJson();
        }
    }


    public function customersSearchAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $query = $request->getInput("q", "String", "");
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;

        try {

            $soService = new Service_So_Order(new Service_TenantContext($companyId, $userId));
            $data = $soService->searchCustomers($query);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to search customers", 500)->sendJson();
        }
    }


    public function entityAction(TinyPHP_Request $request) {

        if( $request->isMethod("get") ) {
            $this->show($request);
        }
        else if( $request->isMethod("post") ) {
            $this->save($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    public function statusAction(TinyPHP_Request $request) {

        if( $request->isMethod("post") ) {
            $this->updateStatus($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    public function historyAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $id = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;

        try {

            $soService = new Service_So_Order(new Service_TenantContext($companyId, $userId));
            $data = $soService->getHistory($id);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch sales order history", 500)->sendJson();
        }
    }


    private function list(TinyPHP_Request $request) {

        $companyId = auth()->getCompanyId();

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
        ];

        $results = $dataFetch
            ->table("sales_orders AS so")
            ->joins("LEFT JOIN customers AS c ON so.customer_id = c.id")
            ->columns($columns)
            ->where("so.company_id = ?", [$companyId])
            ->fetch();

        response($results)->sendJson();
    }


    private function save(TinyPHP_Request $request) {

        try {

            $id = $request->getInput("id", "Int", 0);
            $action = $id ? "update" : "create";

            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $soService = new Service_So_Order(new Service_TenantContext($companyId, $userId));

            if( $action === "update" ) {
                $response = $soService->update($id, $inputs);
            } else {
                $response = $soService->create($inputs);
            }

            if( $response["success"] ) {
                $responseMessage = $action === "update" ? "Sales order updated successfully" : "Sales order created successfully";
                $responseCode    = $action === "update" ? 200 : 201;
                response($response["data"], $responseMessage, $responseCode)->sendJson();
            } elseif (!empty($response["warning"])) {
                response([], "Please review stock warnings", 200)
                    ->warnings($response["warnings"])
                    ->warningType($response["warning_type"])
                    ->sendJson();
            } else {
                $responseMessage = $action === "update" ? "Failed to update sales order" : "Failed to create sales order";
                response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
            }

        } catch (Service_Exception $e) {
            $statusCode = $e->getStatusCode() ?: 500;
            response([], $e->getMessage(), $statusCode)->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to save sales order", 500)->errors([$e->getMessage()])->sendJson();
        }
    }


    private function show(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;

        try {

            $soService = new Service_So_Order(new Service_TenantContext($companyId, $userId));
            $data = $soService->getDetails($id);

            response($data)->sendJson();

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to fetch sales order details", 500)->sendJson();
        }
    }


    private function updateStatus(TinyPHP_Request $request) {

        try {

            $id = $request->getInput("id", "Int", 0);
            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $soService = new Service_So_Order(new Service_TenantContext($companyId, $userId));
            $response = $soService->updateStatus($id, $inputs);

            if( $response["success"] ) {
                response($response["data"], "Status updated successfully", 200)->sendJson();
            } elseif (!empty($response["warning"])) {
                response([], "Please review stock warnings", 200)
                    ->warnings($response["warnings"])
                    ->warningType($response["warning_type"])
                    ->sendJson();
            } else {
                response([], "Failed to update status", 422)->errors($response["errors"])->sendJson();
            }

        } catch (Service_Exception $e) {
            $statusCode = $e->getStatusCode() ?: 500;
            response([], $e->getMessage(), $statusCode)->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to update status", 500)->errors([$e->getMessage()])->sendJson();
        }
    }

}
