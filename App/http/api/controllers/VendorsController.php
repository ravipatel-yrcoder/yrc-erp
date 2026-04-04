<?php
class Api_VendorsController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function indexAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("get") ) {
            $this->handleGet($request);
        }
        else if( $request->isMethod("post") ) {
            $this->handlePost($request);
        }
        /*else if( $request->isMethod("delete") ) {
            $this->handleDelete($request);
        }*/

        response([], "Method not allowed", 405)->sendJson();
    }



    private function handleGet(TinyPHP_Request $request) {

        $companyId = auth()->getCompanyId();        
        
        $dataFetch = new TinyPHP_DataFetch($request);
        
        $columns = ["id" => "v.id", "display_name" => "v.display_name", "email" => "v.email", "phone" => "v.phone", "state" => "va.state", "country" => "va.country", "status" => "v.status", "created_at" => "v.created_at"];

        $results = $dataFetch
        ->table("vendors AS v")
        ->joins("LEFT JOIN vendor_addresses AS va ON va.vendor_id=v.id AND va.address_type='billing'")
        ->columns($columns)
        ->where("v.company_id = ?", [$companyId])
        ->fetch();        

        response($results)->sendJson();
    }


    private function handlePost(TinyPHP_Request $request) {

        try {

            $id = $request->getInput("id", "Int", 0);

            $action = "create";
            if( $id ) {
                $action = "update";
            }

            $companyId = auth()->getCompanyId();
            $userId = auth()->user()->id;
            $inputs = $request->getInputs();

            $vendorService = new Service_Vendor(new Service_TenantContext($companyId, $userId));
            if( $action === "update" ) {                                
                $response = $vendorService->update($id, $inputs);

            } else {                
                $response = $vendorService->create($inputs);
            }

            if( $response["success"] )
            {
                $responseMessage = $action === "update" ? "Vendor updated successfully" : "Vendor created successfully";
                $responseCode = $action === "update" ? 200 : 201;
                response($response["data"], $responseMessage, $responseCode)->sendJson();
            }
            else
            {
                $responseMessage = $action === "update" ? "Failed to update vendor" : "Failed to create vendor";
                response([], $responseMessage, 422)->errors($response["errors"])->sendJson();
            }
        }
        catch(Service_Exception $e) {

            $error = $e->getMessage();
            $statusCode = $e->getStatusCode() ?: 500;
            response([], "Failed to save vendor", $statusCode)->errors([$error])->sendJson();
        } 
        catch(Exception $e) {

            $error = $e->getMessage();
            response([], "Failed to save vendor", 500)->errors([$error])->sendJson();
        }
    }



    public function formContextAction(TinyPHP_Request $request) {

        if( !$request->isMethod("get") ) {
            response([], "Method not allowed", 405)->sendJson();    
        }
        
        $id = $request->getInput("id", "Int", 0);

        $companyId = auth()->getCompanyId();
        $vendorDetails = [];

        $forbidden = false;
        if( $id ) {

            $vendor = new Models_Vendor($id);
            if( !$vendor->isEmpty )
            {
                if( $vendor->company_id === $companyId ) {
                    
                    $vendorDetails = array_merge(['id' => $id], $vendor->toArray());
                    $vendorDetails["billing_address"] = $vendor->getBillingAddress();
                    $vendorDetails["shipping_address"] = $vendor->getShippingAddress();
                } else {
                    $forbidden = true;
                }
            }
        }

        if( $forbidden === true ) {
            response([], "You do not have permission to access this resource", 403)->sendJson();
        }

        $paymentTerm = new Models_PaymentTerm();
        $paymentTerms = $paymentTerm->getAll([], ["company_id" => $companyId, "status" => ["active"]]);

        $data = [
            'paymentTerms' => $paymentTerms,
            'vendorDetails' => $vendorDetails,
        ];

        response($data)->sendJson();
    }


    public function checkDuplicateAction(TinyPHP_Request $request) {

        if (!$request->isMethod("get")) {
            response([], "Method not allowed", 405)->sendJson();
        }

        $field    = $request->getInput("field",     "String", "");
        $value    = trim($request->getInput("value", "String", ""));
        $vendorId = $request->getInput("vendor_id", "Int",    0);

        $companyId = auth()->getCompanyId();
        $userId    = auth()->user()->id;

        $vendorService = new Service_Vendor(new Service_TenantContext($companyId, $userId));
        $result = $vendorService->checkDuplicate($field, $value, $vendorId);

        response($result)->sendJson();
    }
}