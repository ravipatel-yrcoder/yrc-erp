<?php
class PurchaseOrdersController extends TinyPHP_Controller {

    public function indexAction() {        
    }

    public function editAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $purchaseOrder = new Models_PurchaseOrder($id);

        $tenantContext = tenantContext();
        $companyId = $tenantContext->companyId;

        if( !(!$purchaseOrder->isEmpty && $purchaseOrder->company_id == $companyId) ) {
            redirect("/purchase-orders/");
        }

        $this->setViewVar('purchaseOrder', $purchaseOrder);
    }
}
?>
