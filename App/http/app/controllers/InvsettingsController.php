<?php
class InvSettingsController extends TinyPHP_Controller {

    public function indexAction() {
        $settingsSvc = new Service_CompanySettings(tenantContext());
        $this->setViewVar('cost_method', $settingsSvc->get('inventory.cost_method', 'standard'));
    }
}
?>
