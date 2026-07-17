<?php
class InvProductsController extends TinyPHP_Controller {
	
    public function stockWarehousesAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        $product = new Models_Product($id);

        $tenantContext = tenantContext();
        $companyId = $tenantContext->companyId;

        if( !(!$product->isEmpty && $product->company_id == $companyId) ) {
            redirect("/products/");
        }

        if (!in_array($product->stock_tracking_method, ['quantity', 'lot', 'serial'])) {
            abort(404, "Page not found");
        }

        $this->setViewVar('product', $product);
	}
}