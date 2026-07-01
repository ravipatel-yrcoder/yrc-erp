<?php
class ReportProfitMarginController extends TinyPHP_Controller {

    public function lineItemsAction() {
        
        $ctx   = tenantContext();
        $scope = $ctx->scopeFor('reporting_profit_margin');

        $salespersonOptions = [];
        if (in_array($scope, ['team', 'all'])) {
            $db = Service_TenantDBResolver::resolve($ctx->companyId);
            $salespersonOptions = $db->fetchAll(
                "SELECT u.id, u.name
                 FROM users u
                 INNER JOIN user_roles ur ON ur.user_id = u.id
                 WHERE ur.company_id = ? AND u.status = 'active'
                 ORDER BY u.name",
                [$ctx->companyId]
            );
        }

        $this->setViewVar('scope', $scope);
        $this->setViewVar('salespersonOptions', $salespersonOptions);
    }
}
