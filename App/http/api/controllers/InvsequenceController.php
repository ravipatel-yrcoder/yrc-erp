<?php
class Api_InvSequenceController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function generateAction(TinyPHP_Request $request) {
        
        $tenantContext = tenantContext();
        $companyId = $tenantContext->companyId;
        $userId = $tenantContext->userId;

        $productId = $request->getInput("product_id", "Int", 0);
        $reserved = $request->getInput("reserved", "Int", 0); // default preview only
        $count = $request->getInput("count", "Int", 0); // no of numbers to generate


        $isProductValid = true;
        $product = new Models_Product($productId);
        if( $product->isEmpty || $product->company_id != $companyId ) {
            $this->addError(validationErrMsg("missing_or_invalid", "Product id"));
            $isProductValid = false;
        }

        $prodTrackingMethod = "";
        if( $isProductValid ) {

            $prodTrackingMethod = $product->master->stock_tracking_method;
            if( !in_array($prodTrackingMethod, ["lot", "serial"]) ) {
                $this->addError(validationErrMsg("not_supported_lot_or_serial", "This product’s"));
            }                
        }

        if( !isPositiveNumeric($count) ) {
            $this->addError(validationErrMsg("positive_number", "Count"));
        } else if( $count > 100 ) {
            $this->addError(validationErrMsg("max_length", "Count"));
        }

        if( $this->hasErrors() ) {
            return response([], "One or more fields failed validation", 422)->errors($this->getErrors())->sendJson();
        }
        
        try {

            $seqService = new Service_Inv_Sequence(new Service_TenantContext($companyId, $userId));
            $numbers = $seqService->generate($productId, $prodTrackingMethod, $count);
            
            return response($numbers)->sendJson();

        } catch(Exception $e) {
            
            $error = $e->getMessage();
            return response([], "Failed to generate ".$prodTrackingMethod." numbers.", 500)->errors([$error])->sendJson();
        }        
    }

}