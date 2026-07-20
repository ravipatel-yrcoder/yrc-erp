<?php
class PurchasingsettingsController extends TinyPHP_Controller {

    public function indexAction() {
        $settingsSvc = new Service_CompanySettings(tenantContext());
        $this->setViewVar('vendor_quote_comparison', (bool)(int) $settingsSvc->get('purchasing.vendor_quote_comparison', '0'));
    }
}
?>
