<?php
class SalesOrdersController extends TinyPHP_Controller {

    public function indexAction() {
        $ctx   = tenantContext();
        $scope = $ctx->scopeFor('sales_orders');

        $showSalespersonFilter = in_array($scope, ['team', 'all']);
        $salespersonOptions    = [];

        if ($showSalespersonFilter) {
            $salespersonOptions = DB()->fetchAll(
                "SELECT id, name FROM users WHERE company_id = ? AND status = 'active' ORDER BY name ASC",
                [$ctx->companyId]
            );
        }

        $this->setViewVar('showSalespersonFilter', $showSalespersonFilter);
        $this->setViewVar('salespersonOptions', $salespersonOptions);
    }

    public function quotationsAction(TinyPHP_Request $request) {
        
    $leadId = $request->getInput("lead_id", "Int", 0);
        $this->setViewVar("leadId", $leadId);
    }

    public function editAction(TinyPHP_Request $request) {

        $id            = $request->getInput("id", "Int", 0);
        $tenantContext = tenantContext();
        $companyId     = $tenantContext->companyId;

        $salesOrder = new Models_SalesOrder($id);
        if ($salesOrder->isEmpty || $salesOrder->company_id != $companyId) {
            redirect("/sales/orders/");
        }

        // Apply data scope — same check the API list uses
        $scope  = (new Service_So_Order($tenantContext))->getScopeCondition('sales_orders', ['so.salesperson_id', 'so.created_by']);
        $sql    = "SELECT so.id FROM sales_orders so WHERE so.id = ? AND so.company_id = ?";
        $params = [$id, $companyId];
        if ($scope['sql']) {
            $sql   .= " AND (" . $scope['sql'] . ")";
            $params = array_merge($params, $scope['bindings']);
        }
        if (!DB()->fetchOne($sql, $params)) {
            abort(403, "You do not have access to this sales order.");
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

        $pdfService = new Service_So_Order(new Service_TenantContext($companyId, 0));
        $printData = $pdfService->buildPrintData($id);

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

        $appUrl = rtrim(config('app.url'), '/') . '/';
        $printViewUrl = $appUrl . "sales/orders/{$id}/print-view";

        $pdfService = new Service_So_Order(new Service_TenantContext($salesOrder->company_id, 0));

        try {
            $pdfBytes = $pdfService->callPdfService($printViewUrl);
        } catch (Service_Exception $e) {
            http_response_code($e->getHttpStatusCode());
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
