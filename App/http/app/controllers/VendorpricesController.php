<?php
class VendorpricesController extends TinyPHP_Controller {

    public function indexAction() {

        $tenantContext = tenantContext();
        $db = Service_TenantDBResolver::resolve($tenantContext->companyId);

        $vendorOptions = $db->fetchAll(
            "SELECT id, display_name FROM vendors WHERE company_id = ? AND status = 'active' ORDER BY display_name ASC",
            [$tenantContext->companyId]
        );

        $this->setViewVar('vendorOptions', $vendorOptions);
    }
}
?>
