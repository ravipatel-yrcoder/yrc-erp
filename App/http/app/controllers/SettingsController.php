<?php
class SettingsController extends TinyPHP_Controller {

    public function generalAction() {
        $settings = new Service_CompanySettings(tenantContext());
        $this->setViewVar('bankDetails1',    (string) $settings->get('bank_details_1',   ''));
        // $this->setViewVar('docJurisdiction', (string) $settings->get('doc_jurisdiction', ''));  // commented out — enable when PDF support is ready
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
            'customer_search_by'    => json_decode($settings->get('sales.customer_search_by', '["name","gstin","email","phone"]'), true) ?: ['name', 'gstin', 'email', 'phone'],
            'proforma_invoice'      => (bool)(int) $settings->get('proforma_invoice', 0),
        ]);
    }

    public function documentsAction() {
        header('Location: /settings/documents/quotation/', true, 302);
        exit;
    }

    private function loadDocPageVars(string $docType): void
    {
        $settings    = new Service_CompanySettings(tenantContext());
        $emailConfig = new Service_EmailConfig(tenantContext());
        $registry    = config('pdf_templates', []);
        $seqSvc      = new Service_Sequence(tenantContext());

        $this->setViewVar('docType',   $docType);
        $this->setViewVar('templates', $registry[$docType] ?? []);
        $this->setViewVar('current',   $emailConfig->getPdfTemplate($docType));
        $this->setViewVar('sequence',  $seqSvc->getOneForSettings($docType));
        if ($docType === 'proforma_invoice') {
            $this->setViewVar('proformaEnabled', (bool)(int) $settings->get('proforma_invoice', 0));
        }
    }

    public function docQuotationAction()       { $this->loadDocPageVars('quotation'); }
    public function docSalesOrderAction()      { $this->loadDocPageVars('sales_order'); }
    public function docProformaInvoiceAction() { $this->loadDocPageVars('proforma_invoice'); }
    public function docPurchaseOrderAction()   { $this->loadDocPageVars('purchase_order'); }
    public function docPurchaseInquiryAction() { $this->loadDocPageVars('purchase_inquiry'); }

    public function docsequencesAction() {
        $service   = new Service_Sequence(tenantContext());
        $sequences = $service->getAllForSettings();
        $this->setViewVar('sequences', $sequences);
    }

    public function emailAction() {
    }
}
?>
