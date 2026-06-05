<?php
class ManufacturingbomsController extends TinyPHP_Controller
{
    public function indexAction() {

        $tenantContext = tenantContext();
        $db = Service_TenantDBResolver::resolve($tenantContext->companyId);

        $productOptions = $db->fetchAll(
            "SELECT id, name, sku FROM products WHERE company_id = ? AND status = 'active' ORDER BY name ASC",
            [$tenantContext->companyId]
        );

        $userOptions = $db->fetchAll(
            "SELECT id, name FROM users WHERE company_id = ? AND status = 'active' ORDER BY name ASC",
            [$tenantContext->companyId]
        );

        $this->setViewVar('productOptions', $productOptions);
        $this->setViewVar('userOptions', $userOptions);
    }
}
