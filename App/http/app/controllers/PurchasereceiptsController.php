<?php
class PurchaseReceiptsController extends TinyPHP_Controller {

    public function indexAction() {
    }

    public function editAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $purchaseReceipt = new Models_PurchaseOrderGrn($id);

        $tenantContext = tenantContext();
        $companyId = $tenantContext->companyId;

        if( !(!$purchaseReceipt->isEmpty && $purchaseReceipt->company_id == $companyId) ) {
            redirect("/purchase-receipts/");
        }

        $this->setViewVar('purchaseReceipt', $purchaseReceipt);
    }
}
?>
