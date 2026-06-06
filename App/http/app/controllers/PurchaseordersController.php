<?php
class PurchaseOrdersController extends TinyPHP_Controller {

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
        $purchaseOrder = new Models_PurchaseOrder($id);

        $tenantContext = tenantContext();
        $companyId = $tenantContext->companyId;

        if( !(!$purchaseOrder->isEmpty && $purchaseOrder->company_id == $companyId) ) {
            redirect("/purchase-orders/");
        }

        $this->setViewVar('purchaseOrder', $purchaseOrder);
    }

    public function pdfAction(TinyPHP_Request $request) {

        $this->setNoRenderer(true);

        $id   = $request->getInput("id", "Int", 0);
        $mode = $request->getInput("mode", "String", "inline");

        try {
            $pdf = (new Service_Po_Order(tenantContext()))->buildPdf($id);
        } catch (Service_Exception $e) {
            http_response_code($e->getHttpStatusCode());
            exit($e->getMessage());
        }

        Helpers_Pdf::stream($pdf['bytes'], $pdf['filename'], $mode);
    }
}
?>
