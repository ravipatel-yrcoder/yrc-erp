<?php
class PurchaseinquiriesController extends TinyPHP_Controller
{
    public function indexAction()
    {
        $tenantContext = tenantContext();
        $db            = Service_TenantDBResolver::resolve($tenantContext->companyId);

        $vendorOptions = $db->fetchAll(
            "SELECT id, display_name AS name FROM vendors WHERE company_id = ? AND status = 'active' ORDER BY display_name",
            [$tenantContext->companyId]
        );

        $settingsSvc = new Service_CompanySettings($tenantContext);
        $this->setViewVar('vendorOptions', $vendorOptions);
        $this->setViewVar('vendor_quote_comparison', (bool)(int) $settingsSvc->get('purchasing.vendor_quote_comparison', '0'));
    }

    public function detailAction(TinyPHP_Request $request)
    {
        $id      = $request->getInput("id", "Int", 0);
        $inquiry = new Models_PurchaseInquiry($id);

        $tenantContext = tenantContext();
        $companyId     = $tenantContext->companyId;

        if ($inquiry->isEmpty || $inquiry->company_id != $companyId) {
            redirect("/purchase/inquiries/");
        }

        $settingsSvc = new Service_CompanySettings($tenantContext);
        $this->setViewVar('inquiry', $inquiry);
        $this->setViewVar('vendor_quote_comparison', (bool)(int) $settingsSvc->get('purchasing.vendor_quote_comparison', '0'));
    }

    public function pdfAction(TinyPHP_Request $request)
    {
        $this->setNoRenderer(true);

        $id   = $request->getInput("id",   "Int",    0);
        $mode = $request->getInput("mode", "String", "inline");

        try {
            $svc      = new Service_Po_Inquiry(tenantContext());
            $inquiry  = new Models_PurchaseInquiry($id);
            $bytes    = $svc->getPdfBytes($id);
            $filename = "{$inquiry->inquiry_number}.pdf";
        } catch (Service_Exception $e) {
            http_response_code($e->getHttpStatusCode());
            exit($e->getMessage());
        }

        Helpers_Pdf::stream($bytes, $filename, $mode);
    }
}
?>
