<?php
class Api_CustomersController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    private function customerService(): Service_Customer {
        return new Service_Customer(tenantContext());
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if ($request->isMethod("get")) {
            return $this->handleGet($request);
        } else if ($request->isMethod("post")) {
            return $this->handlePost($request);
        }

        return response([], "Method not allowed", 405)->sendJson();
    }


    private function handleGet(TinyPHP_Request $request) {

        $companyId = tenantContext()->companyId;
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

        return response($results)->sendJson();
    }


    private function handlePost(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);        
        $inputs = $request->getInputs();

        $action = $id ? "update" : "create";

        $service = $this->customerService();

        if ($action === "update") {
            $response = $service->update($id, $inputs);
        } else {
            $response = $service->create($inputs);
        }

        if ($response["success"]) {
            
            $message = $action === "update" ? "Customer updated successfully" : "Customer created successfully";
            $code = $action === "update" ? 200 : 201;
            
            return response($response["data"], $message, $code)->sendJson();
        } else {
            
            $message = $action === "update" ? "Failed to update customer" : "Failed to create customer";
            
            return response([], $message, 422)->errors($response["errors"])->sendJson();
        }
    }


    public function storeAddressAction(TinyPHP_Request $request) {

        $customerId = $request->getInput("id", "Int", 0);

        $service = $this->customerService();
        $result  = $service->saveAddress($customerId, $request->getInputs());

        if ($result["success"]) {
            return response($result["data"], "Address saved successfully", 201)->sendJson();
        } else {
            return response([], "Failed to save address", 422)->errors($result["errors"])->sendJson();
        }
    }


    public function shippingAddressesAction(TinyPHP_Request $request) {

        $customerId = $request->getInput("id", "Int", 0);

        $service = $this->customerService();
        $data    = $service->getShippingAddresses($customerId);

        return response($data)->sendJson();
    }


    public function billingAddressesAction(TinyPHP_Request $request) {

        $customerId = $request->getInput("id", "Int", 0);

        $service = $this->customerService();
        $data    = $service->getBillingAddresses($customerId);

        return response($data)->sendJson();
    }


    public function searchAction(TinyPHP_Request $request) {
        
        $q = trim($request->getInput("q", "String", ""));
            
        $service = $this->customerService();
        $results = $service->search($q);

        return response($results)->sendJson();
    }


    public function checkDuplicateAction(TinyPHP_Request $request) {
        
        $field = $request->getInput("field", "String", "");
        $value = trim($request->getInput("value", "String", ""));
        $customerId = $request->getInput("customer_id", "Int", 0);

        $service = $this->customerService();
        $result = $service->checkDuplicate($field, $value, $customerId);

        return response($result)->sendJson();
    }


    public function formContextAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
            
        $service = $this->customerService();
        $data = $service->getFormContext($id);

        return response($data)->sendJson();
    }
}
?>