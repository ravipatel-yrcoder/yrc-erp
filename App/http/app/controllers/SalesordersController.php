<?php
class SalesOrdersController extends TinyPHP_Controller {

    public function indexAction() {
    }

    public function editAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $salesOrder = new Models_SalesOrder($id);

        if( !(!$salesOrder->isEmpty && $salesOrder->company_id == auth()->getCompanyId()) ) {
            redirect("/sales-orders/");
        }

        $this->setViewVar('salesOrder', $salesOrder);
    }

    /**
     * Renders the clean print HTML that Puppeteer visits to generate a PDF.
     * Authenticated via a fixed token (PRINT_VIEW_SECRET) — no session required.
     * View file auto-resolved to: salesorders/printview.blade.php
     */
    public function printViewAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);        

        $salesOrder = new Models_SalesOrder($id);

        $companyId = $salesOrder->company_id;
       // $userId = auth()->user()->id;

        
        /*
        if ($salesOrder->isEmpty || $salesOrder->company_id != $companyId) {
            http_response_code(404);
            exit('Forbidden');
        }
        */

        $pdfService = new Service_So_Order(new Service_TenantContext($companyId, 0));
        $printData  = $pdfService->buildPrintData($id);

        $this->setViewVar('printData', $printData);
    }

    /**
     * Calls the Puppeteer service and streams the PDF back to the browser.
     * mode=inline  → opens in browser tab
     * mode=download → triggers file download
     */
    public function pdfAction(TinyPHP_Request $request) {

        $this->setNoRenderer(true);

        $id = $request->getInput("id", "Int", 0);
        $mode = $request->getInput("mode", "String", "inline");

        $salesOrder = new Models_SalesOrder($id);
        /*
        if ($salesOrder->isEmpty || $salesOrder->company_id != auth()->getCompanyId()) {
            http_response_code(404);
            exit('Not found');
        }
        */

        $appUrl = rtrim(config('app.url'), '/') . '/';
        $printViewUrl = $appUrl . "sales-orders/{$id}/print-view";

        $pdfService = new Service_So_Order(new Service_TenantContext($salesOrder->company_id, 0));

        try {
            $pdfBytes = $pdfService->callPdfService($printViewUrl);
        } catch (Service_Exception $e) {
            http_response_code($e->getStatusCode());
            exit($e->getMessage());
        }

        $filename    = 'SO-' . $salesOrder->so_number . '.pdf';
        $disposition = ($mode === 'download') ? 'attachment' : 'inline';

        header('Content-Type: application/pdf');
        header("Content-Disposition: {$disposition}; filename=\"{$filename}\"");
        header('Content-Length: ' . strlen($pdfBytes));
        header('Cache-Control: no-cache, no-store');

        echo $pdfBytes;
        exit;
    }
}
?>
