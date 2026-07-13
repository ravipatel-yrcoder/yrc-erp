<?php
class DocTemplatesController extends TinyPHP_Controller {

    public function indexAction() {

        $settings    = new Service_CompanySettings(tenantContext());
        $emailConfig = new Service_EmailConfig(tenantContext());
        $registry    = config('pdf_templates', []);

        $current = [
            'sales_order'    => $emailConfig->getPdfTemplate('sales_order',   $settings),
            'quotation'      => $emailConfig->getPdfTemplate('quotation',      $settings),
            'purchase_order' => $emailConfig->getPdfTemplate('purchase_order', $settings),
            'rfq'            => $emailConfig->getPdfTemplate('rfq',            $settings),
        ];

        $docTypes = [
            'sales_order'    => ['label' => 'Sales Order',           'docType' => 'sales_order'],
            'purchase_order' => ['label' => 'Purchase Order',        'docType' => 'purchase_order'],
            'rfq'            => ['label' => 'Request for Quotation', 'docType' => 'rfq'],
        ];

        $this->setViewVar('registry', $registry);
        $this->setViewVar('current', $current);
        $this->setViewVar('docTypes', $docTypes);
    }
}
?>
