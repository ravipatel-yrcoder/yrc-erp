<?php
class Api_CustomersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }


    public function indexAction(TinyPHP_Request $request) {

        if ($request->isMethod("get")) {
            $this->handleGet($request);
        } else if ($request->isMethod("post")) {
            $this->handlePost($request);
        }

        response([], "Method not allowed", 405)->sendJson();
    }


    private function handleGet(TinyPHP_Request $request) {

        $companyId = auth()->getCompanyId();
        $columns = [
            "id" => "c.id",
            "customer_code" => "c.customer_code",
            "display_name" => "c.display_name",
            "email" => "c.email",
            "phone" => "c.phone",
            "state" => "ca.state",
            "country" => "ca.country",
            "status" => "c.status",
            "created_at" => "c.created_at",
        ];

        $dataFetch = new TinyPHP_DataFetch($request);
        $results = $dataFetch
            ->table("customers AS c")
            ->joins("LEFT JOIN customer_addresses AS ca ON ca.customer_id = c.id AND ca.address_type = 'billing'")
            ->columns($columns)
            ->where("c.company_id = ?", [$companyId])
            ->fetch();

        response($results)->sendJson();
    }


    private function handlePost(TinyPHP_Request $request) {

        try {

            $id = $request->getInput("id", "Int", 0);
            $action = $id ? "update" : "create";

            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $customerService = new Service_Customer(new Service_TenantContext($companyId, $userId));

            if ($action === "update") {
                $response = $customerService->update($id, $inputs);
            } else {
                $response = $customerService->create($inputs);
            }

            if ($response["success"]) {
                $message = $action === "update" ? "Customer updated successfully" : "Customer created successfully";
                $code = $action === "update" ? 200 : 201;
                response($response["data"], $message, $code)->sendJson();
            } else {
                $message = $action === "update" ? "Failed to update customer" : "Failed to create customer";
                response([], $message, 422)->errors($response["errors"])->sendJson();
            }

        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to save customer", 500)->errors([$e->getMessage()])->sendJson();
        }
    }


    /*
    public function entityAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;

        try {
            $customerService = new Service_Customer(new Service_TenantContext($companyId, $userId));
            $data = $customerService->getFormContext($id);
            response($data['customerDetails'])->sendJson();
        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->sendJson();
        }
    }
    */


    public function storeAddressAction(TinyPHP_Request $request) {

        if (!$request->isMethod("post")) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $customerId = $request->getInput("id", "Int", 0);
        $companyId  = auth()->getCompanyId();
        $userId     = auth()->user()->id;

        try {
            $service = new Service_Customer(new Service_TenantContext($companyId, $userId));
            $result  = $service->saveAddress($customerId, $request->getInputs());

            if ($result["success"]) {
                response($result["data"], "Address saved successfully", 201)->sendJson();
            } else {
                response([], "Failed to save address", 422)->errors($result["errors"])->sendJson();
            }
        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->errors($e->getErrors())->sendJson();
        } catch (Exception $e) {
            response([], "Failed to save address", 500)->sendJson();
        }
    }


    public function checkDuplicateAction(TinyPHP_Request $request) {

        if (!$request->isMethod("get")) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $field = $request->getInput("field", "String", "");
        $value = trim($request->getInput("value", "String", ""));
        $customerId = $request->getInput("customer_id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;

        $customerService = new Service_Customer(new Service_TenantContext($companyId, $userId));
        $result = $customerService->checkDuplicate($field, $value, $customerId);

        response($result)->sendJson();
    }


    public function formContextAction(TinyPHP_Request $request) {

        if (!$request->isMethod("get")) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $id = $request->getInput("id", "Int", 0);
        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;

        try {
            $customerService = new Service_Customer(new Service_TenantContext($companyId, $userId));
            $data = $customerService->getFormContext($id);
            response($data)->sendJson();
        } catch (Service_Exception $e) {
            response([], $e->getMessage(), $e->getStatusCode())->sendJson();
        }
    }
}
?>
