<?php
class PurchaseReceiptsController extends TinyPHP_Controller {
	
    public function indexAction() {
	}

    public function editAction(TinyPHP_Request $request) {
        
        $id = $request->getInput("id", "Int", 0);
        $purchaseReceipt = new Models_PurchaseOrderGrn($id);

        if( !(!$purchaseReceipt->isEmpty && $purchaseReceipt->company_id == auth()->getCompanyId()) ) {
            redirect("/purchase-receipts/");
        }

        $this->setViewVar('purchaseReceipt', $purchaseReceipt);
    }
}
?> 