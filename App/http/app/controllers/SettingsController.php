<?php
class SettingsController extends TinyPHP_Controller {

    public function generalAction() {
    }

    public function accountingAction() {
    }

    public function inventoryAction() {
        $settingsSvc = new Service_CompanySettings(tenantContext());
        $this->setViewVar('cost_method', $settingsSvc->get('inventory.cost_method', 'standard'));
        $this->setViewVar('multi_warehouse', (bool)(int) $settingsSvc->get('inventory.multi_warehouse', '0'));
    }

    public function salesAction() {
        $settings = new Service_CompanySettings(tenantContext());
        $this->setViewVar('salesSettings', [
            'quote_validity_days'   => (int) $settings->get('sales.quote_validity_days', 15),
            'customer_gst_required' => (bool) $settings->get('sales.customer_gst_required', false),
            'customer_search_by'    => json_decode($settings->get('sales.customer_search_by', '["name","gstin"]'), true) ?: ['name', 'gstin'],
            'quotation_terms'       => (string) $settings->get('doc_terms.quotation', ''),
            'sales_order_terms'     => (string) $settings->get('doc_terms.sales_order', ''),
        ]);
    }

    public function doctemplatesAction() {
        $settings    = new Service_CompanySettings(tenantContext());
        $emailConfig = new Service_EmailConfig(tenantContext());
        $registry    = config('pdf_templates', []);

        $current = [
            'sales_order'    => $emailConfig->getPdfTemplate('sales_order',   $settings),
            'quotation'      => $emailConfig->getPdfTemplate('quotation',      $settings),
            'purchase_order' => $emailConfig->getPdfTemplate('purchase_order', $settings),
        ];

        $docTypes = [
            'sales_order'    => ['label' => 'Sales Order',    'docType' => 'sales_order'],
            'purchase_order' => ['label' => 'Purchase Order', 'docType' => 'purchase_order'],
        ];

        $this->setViewVar('registry', $registry);
        $this->setViewVar('current', $current);
        $this->setViewVar('docTypes', $docTypes);
    }

    public function docsequencesAction() {
        $service   = new Service_Sequence(tenantContext());
        $sequences = $service->getAllForSettings();
        $this->setViewVar('sequences', $sequences);
    }

    public function emailAction() {
    }
}
?>
