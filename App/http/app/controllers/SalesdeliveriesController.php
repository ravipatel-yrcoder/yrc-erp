<?php
class SalesDeliveriesController extends TinyPHP_Controller {

    public function indexAction() {

        $tenantContext = tenantContext();
        $db = Service_TenantDBResolver::resolve($tenantContext->companyId);

        $customerOptions = $db->fetchAll(
            "SELECT id, display_name FROM customers WHERE company_id = ? AND status = 'active' ORDER BY display_name ASC",
            [$tenantContext->companyId]
        );

        $this->setViewVar('customerOptions', $customerOptions);
    }

    public function editAction(TinyPHP_Request $request) {

        $id            = $request->getInput("id", "Int", 0);
        $tenantContext = tenantContext();
        $companyId     = $tenantContext->companyId;

        $dn = new Models_SalesDelivery($id);
        if ($dn->isEmpty || $dn->company_id != $companyId) {
            redirect("/sales/deliveries/");
        }

        // Apply parent-SO data scope — deliveries inherit access from their SO
        $scope  = (new Service_Scope($tenantContext))->getCondition('sales_orders', ['so.salesperson_id', 'so.created_by']);
        $sql    = "SELECT dn.id FROM sales_deliveries dn
                   LEFT JOIN sales_orders so ON so.id = dn.sales_order_id
                   WHERE dn.id = ? AND dn.company_id = ?";
        $params = [$id, $companyId];
        if ($scope['sql']) {
            $sql   .= " AND (" . $scope['sql'] . ")";
            $params = array_merge($params, $scope['bindings']);
        }
        if (!DB()->fetchOne($sql, $params)) {
            abort(403, "You do not have access to this delivery.");
        }

        $this->setViewVar('delivery', $dn);
    }
}
?>
