<?php
class PurchaseReceiptsController extends TinyPHP_Controller {

    public function indexAction() {

        $tenantContext = tenantContext();
        $db = Service_TenantDBResolver::resolve($tenantContext->companyId);

        $vendorOptions = $db->fetchAll(
            "SELECT id, display_name FROM vendors WHERE company_id = ? AND status = 'active' ORDER BY display_name ASC",
            [$tenantContext->companyId]
        );

        $this->setViewVar('vendorOptions', $vendorOptions);
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
