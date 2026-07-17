<?php
class InvSettingsController extends TinyPHP_Controller {

    public function indexAction() {
        $settingsSvc = new Service_CompanySettings(tenantContext());
        $this->setViewVar('cost_method', $settingsSvc->get('inventory.cost_method', 'standard'));
        $this->setViewVar('multi_warehouse', (bool)(int) $settingsSvc->get('inventory.multi_warehouse', '0'));
    }
}
?>
