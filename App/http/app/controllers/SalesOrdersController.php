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

        $companyId = auth()->getCompanyId();
        $userId = auth()->user()->id;


        $salesOrder = new Models_SalesOrder($id);
        if ($salesOrder->isEmpty || $salesOrder->company_id != $companyId) {
            http_response_code(404);
            exit('Forbidden');
        }

        $pdfService = new Service_So_Pdf(new Service_TenantContext($companyId, $userId));
        $printData  = $pdfService->buildPrintData($id);

        $this->setViewVar('printData', $printData);
        // No @extends in printview.blade.php — standalone HTML, no app layout
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

        // Forward session cookies so Puppeteer can authenticate as the current user
        $domain = parse_url($appUrl, PHP_URL_HOST);
        $cookies = [];
        foreach ($_COOKIE as $name => $value) {
            $cookies[] = ['name' => $name, 'value' => $value, 'domain' => $domain];
        }

        //$companyId  = auth()->getCompanyId();
        //$userId = auth()->user()->id;
        $pdfService = new Service_So_Pdf(new Service_TenantContext($salesOrder->company_id, 0));

        try {
            $pdfBytes = $pdfService->callPdfService($printViewUrl, $cookies);
        } catch (Service_Exception $e) {
            http_response_code($e->getStatusCode());
            exit($e->getMessage());
        }

        $filename    = 'SO-' . $salesOrder->so_number . '.pdf';
        $disposition = ($mode === 'download') ? 'attachment' : 'inline';

        $disposition = "download";

        header('Content-Type: application/pdf');
        header("Content-Disposition: {$disposition}; filename=\"{$filename}\"");
        header('Content-Length: ' . strlen($pdfBytes));
        header('Cache-Control: no-cache, no-store');

        echo $pdfBytes;
        exit;
    }
}
?>
