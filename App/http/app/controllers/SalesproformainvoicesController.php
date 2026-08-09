<?php
class SalesproformainvoicesController extends TinyPHP_Controller {

    public function detailAction(TinyPHP_Request $request) {
        $id            = $request->getInput("id", "Int", 0);
        $tenantContext = tenantContext();
        $companyId     = $tenantContext->companyId;

        if (!Service_CompanySettings::isProformaInvoiceEnabled($companyId)) {
            abort(403, "Proforma Invoice feature is not enabled.");
        }

        $pf = new Models_SalesProformaInvoice($id);
        if ($pf->isEmpty || $pf->company_id != $companyId) {
            abort(403);
        }

        $this->setViewVar('proformaId', $id);
        $this->setViewVar('tenantContext', $tenantContext);
    }

    public function pdfAction(TinyPHP_Request $request) {
        $this->setNoRenderer(true);

        $id   = $request->getInput("id", "Int", 0);
        $mode = $request->getInput("mode", "String", "inline");

        $service = new Service_So_ProformaInvoice(tenantContext());

        try {
            $pdf = $service->downloadPdf($id);
        } catch (Service_Exception $e) {
            http_response_code($e->getHttpStatusCode());
            exit($e->getMessage());
        }

        Helpers_Pdf::stream($pdf['bytes'], $pdf['filename'], $mode);
    }
}
?>
