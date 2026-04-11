<?php
class InvProductsController extends TinyPHP_Controller {
	
    public function stockLocationsAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        $product = new Models_Product($id);
        
        if( !(!$product->isEmpty && $product->company_id == auth()->getCompanyId()) ) {
            redirect("/products/");
        }

        $this->setViewVar('product', $product);
	}
}