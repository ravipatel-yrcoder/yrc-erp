<?php
class SalesOrdersController extends TinyPHP_Controller {

    public function indexAction() {

        $tenantContext = tenantContext();
        $scope = $tenantContext->scopeFor('sales_orders');            

        $showSalespersonFilter = in_array($scope, ['team', 'all']);
        $salespersonOptions    = [];

        if ($showSalespersonFilter) {

            $db = Service_TenantDBResolver::resolve($tenantContext->companyId);
            $salespersonOptions = $db->fetchAll("SELECT id, name FROM users WHERE company_id = ? AND status = 'active' ORDER BY name ASC", [$tenantContext->companyId]);
        }

        $this->setViewVar('showSalespersonFilter', $showSalespersonFilter);
        $this->setViewVar('salespersonOptions', $salespersonOptions);
    }

    public function quotationsAction(TinyPHP_Request $request) {
        
        $leadId = $request->getInput("lead_id", "Int", 0);
        $this->setViewVar("leadId", $leadId);
    }

    public function editAction(TinyPHP_Request $request) {

        $id = $request->getInput("id", "Int", 0);
        $tenantContext = tenantContext();
        $companyId = $tenantContext->companyId;

        $salesOrder = new Models_SalesOrder($id);
        if ($salesOrder->isEmpty || $salesOrder->company_id != $companyId) {
            abort(403);
        }

        $db = Service_TenantDBResolver::resolve($tenantContext->companyId);

        // Apply data scope — same check the API list uses
        $scope  = (new Service_Scope($tenantContext))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by']);
        $sql = "SELECT so.id FROM sales_orders so WHERE so.id = ? AND so.company_id = ?";
        $params = [$id, $companyId];
        if ($scope['sql']) {
            $sql   .= " AND (" . $scope['sql'] . ")";
            $params = array_merge($params, $scope['bindings']);
        }
        if (!$db->fetchOne($sql, $params)) {
            abort(403, "You do not have access to this sales order.");
        }

        $this->setViewVar('salesOrder', $salesOrder);
    }

    public function pdfAction(TinyPHP_Request $request) {

        $this->setNoRenderer(true);

        $id = $request->getInput("id", "Int", 0);
        $mode = $request->getInput("mode", "String", "inline");

        $service = new Service_So_Order(tenantContext());

        try {
            $pdf = $service->buildPdf($id);
        } catch (Service_Exception $e) {
            http_response_code($e->getHttpStatusCode());
            exit($e->getMessage());
        }

        Helpers_Pdf::stream($pdf['bytes'], $pdf['filename'], $mode);
    }
}
?>
