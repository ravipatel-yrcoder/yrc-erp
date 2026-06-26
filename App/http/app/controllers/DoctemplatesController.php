<?php
class DocTemplatesController extends TinyPHP_Controller {

    public function indexAction() {

        $settings  = new Service_CompanySettings(tenantContext());
        $registry  = config('pdf_templates', []);

        $current = [
            'sales_order'    => $settings->get('so_pdf_template',  'template_1'),
            'purchase_order' => $settings->get('po_pdf_template',  'template_1'),
            'rfq'            => $settings->get('rfq_pdf_template', 'template_1'),
        ];

        $docTypes = [
            'sales_order'    => ['label' => 'Sales Order',            'settingKey' => 'so_pdf_template'],
            'purchase_order' => ['label' => 'Purchase Order',         'settingKey' => 'po_pdf_template'],
            'rfq'            => ['label' => 'Request for Quotation',  'settingKey' => 'rfq_pdf_template'],
        ];

        $this->setViewVar('registry', $registry);
        $this->setViewVar('current', $current);
        $this->setViewVar('docTypes', $docTypes);
    }
}
?>
