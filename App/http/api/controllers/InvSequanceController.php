<?php
class Api_InvSequanceController extends TinyPHP_Controller {

    public function init() {
        $this->setNoRenderer(true);
    }

    public function generateAction(TinyPHP_Request $request) {
        
        if( $request->isMethod("post") ) {
            
            $companyId = auth()->getCompanyId();

            $productId = $request->getInput("product_id", "Int", 0);
            $reserved = $request->getInput("reserved", "Int", 0); // default preview only
            $count = $request->getInput("count", "Int", 0); // no of numbers to generate


            $isProductValid = true;
            $product = new Models_Product($productId);
            if( $product->isEmpty || $product->company_id != auth()->getCompanyId() ) {
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

            if( $this->hasErrors() )
            {
                response([], "One or more fields failed validation", 422)->errors($this->getErrors())->sendJson();
            }
            
            try {

                $numbers = Service_Inv_Sequance::generate($companyId, $productId, $prodTrackingMethod, $count);
                response($numbers)->sendJson();

            } catch(Exception $e) {
                $error = $e->getMessage();
                response($numbers, "A system error occurred while generating the ".$prodTrackingMethod." numbers.", 500)->errors([$error])->sendJson();
            }

            
        }

        response([], "Method not allowed", 405)->sendJson();
    }

}