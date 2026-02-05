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

        $id = $request->getInput("id", "Int", 0);

        $action = "create";
        if( $id ) {
            $action = "update";
        }

        $companyId = auth()->getCompanyId();

        $vendor = new Models_Vendor($id);
        if( $action === "update" ) {
            if( $vendor->isEmpty ) {
                response([], "The requested resource could not be found", 404)->sendJson();
            }

            if( $vendor->company_id != $companyId ) {
                response([], "You do not have permission to perform this action", 403)->sendJson();
            }
        }

        $vendorType = $request->getInput("vendor_type", "string", "company"); // default to company until we have implement something company vs personal
        $companyName = $legalName = "";
        if( $vendorType === "company" ) {
            $companyName = $request->getInput("company_name", "string", "");            
        } else if( $vendorType === "personal" ) {
            
            $firstName = $request->getInput("first_name", "string", "");
            $lastName = $request->getInput("last_name", "string", "");
            $companyName = trim($firstName . ' ' . $lastName);
        }
        $legalName = $companyName; // currently legal name will be same as company name


        $vendor->fillFromRequest($request);
        $vendor->display_name = $companyName;
        $vendor->legal_name = $legalName;
        $vendor->status = $request->getInput("status", "string", "inactive");
        $vendor->payment_term_id = $request->getInput("payment_term_id", "int", null) ?: null;
        $vendor->currency_code = $request->getInput("currency_code", "string", null) ?: null;

        if( $action === "update" ) {
            $id = $vendor->update();
        } else {
            $id = $vendor->create();
        }

        if( $id )
        {
            $responseMessage = $action === "update" ? "Vendor details updated successfully" : "Vendor added successfully";
            $responseCode = $action === "update" ? 200 : 201;
            response([], $responseMessage, $responseCode)->sendJson();
        }
        else
        {
            $errorCode = $vendor->getErrorCode();
            $errorMessage = $vendor->getErrorMessage();
            $errors = $vendor->getErrors();

            $responseCode = $errorCode ?: 422;
            $responseMessage = $action === "update" ? ($errorMessage ?: "Failed to update vendor details") : ( $errorMessage ?: "Failed to add vendor");
            response([], $responseMessage, $responseCode)->errors($errors)->sendJson();
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
}