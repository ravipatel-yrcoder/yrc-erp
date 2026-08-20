<?php
class Api_CompaniesController extends TinyPHP_Controller {

    /**
     * GET /api/company/profile
     */
    public function profileAction(TinyPHP_Request $request)
    {
        if ($request->isMethod('get')) {
            
            $companyId = tenantContext()->companyId;            
            $service = new Service_Company();
            $result = $service->getProfile($companyId);

            return response($result['data'])->sendJson();
        }
        else if ($request->isMethod('post')) {

            $companyId = tenantContext()->companyId;
            $service = new Service_Company();
            $result = $service->updateProfile($companyId, $request->getInputs());

            if ($result['success']) {
                return response([], 'Company profile updated successfully.')->sendJson();                            
            }

            return response([], 'Failed to update company profile', 422)->errors($result['errors'])->sendJson();
        }        
    }


    /**
     * GET /api/company/settings/general
     * POST /api/company/settings/general
     */
    public function generalSettingsAction(TinyPHP_Request $request)
    {
        $companyId = tenantContext()->companyId;
        $service   = new Service_Company();

        if ($request->isMethod('get')) {
            $result      = $service->getGeneralSettings($companyId);
            $settingsSvc = new Service_CompanySettings(tenantContext());
            $data        = $result['data'];
            $data['bank_details_1']   = $settingsSvc->get('bank_details_1',   '') ?: '';
            // $data['doc_jurisdiction'] = $settingsSvc->get('doc_jurisdiction',  '') ?: '';  // commented out — enable when PDF support is ready
            return response($data)->sendJson();
        }

        if ($request->isMethod('post')) {
            $inputs = $request->getInputs();
            $result = $service->updateGeneralSettings($companyId, $inputs);

            if ($result['success']) {
                $settingsSvc = new Service_CompanySettings(tenantContext());
                $settingsSvc->set('bank_details_1',  Helpers_Html::sanitize($inputs['bank_details_1']  ?? ''));
                // $settingsSvc->set('doc_jurisdiction', Helpers_Html::sanitize($inputs['doc_jurisdiction'] ?? ''));  // commented out — enable when PDF support is ready
                return response([], 'General settings updated successfully.')->sendJson();
            }

            return response([], 'Validation failed', 422)->errors($result['errors'])->sendJson();
        }
    }


    /**
     * GET /api/company/settings/accounting
     * POST /api/company/settings/accounting
     *
     * GET response: { "legal": {...}, "invoicing": {...} }
     * POST payload: { "legal": {...}, "invoicing": {...} }
     */
    public function accountingSettingsAction(TinyPHP_Request $request)
    {
        $companyId      = tenantContext()->companyId;
        $companySvc     = new Service_Company();
        $settingsSvc    = new Service_CompanySettings(tenantContext());

        if ($request->isMethod('get')) {
            $legal = $companySvc->getLegalSettings($companyId);
            return response([
                'legal'     => $legal['data'],
                'invoicing' => $settingsSvc->getRoundOffConfig(),
            ])->sendJson();
        }

        if ($request->isMethod('post')) {
            $inputs     = $request->getInputs();
            $legalData  = $inputs['legal']     ?? [];
            $invoicing  = $inputs['invoicing'] ?? [];

            // Validate invoicing group
            $mode    = $invoicing['round_off_mode']     ?? null;
            $roundTo = $invoicing['round_off_round_to'] ?? null;
            $method  = $invoicing['round_off_method']   ?? null;

            $validModes    = ['auto', 'manual', 'off'];
            $validRoundTos = ['0.01', '0.05', '0.10', '0.50', '1.00'];
            $validMethods  = ['nearest', 'up', 'down'];

            $errors = [];
            if (!in_array($mode, $validModes)) {
                $errors['invoicing.round_off_mode'] = 'Invalid round-off mode.';
            }
            if (!in_array($roundTo, $validRoundTos)) {
                $errors['invoicing.round_off_round_to'] = 'Invalid round-to value.';
            }
            if (!in_array($method, $validMethods)) {
                $errors['invoicing.round_off_method'] = 'Invalid rounding method.';
            }

            if (!empty($errors)) {
                return response([], 'Validation failed', 422)->errors($errors)->sendJson();
            }

            // Save legal fields (companies table)
            $companySvc->updateLegalSettings($companyId, $legalData);

            // Save invoicing settings (company_settings table)
            $settingsSvc->setMultiple([
                'round_off.mode'     => $mode,
                'round_off.round_to' => $roundTo,
                'round_off.method'   => $method,
            ]);

            return response([
                'legal'     => $companySvc->getLegalSettings($companyId)['data'],
                'invoicing' => $settingsSvc->getRoundOffConfig(),
            ], 'Accounting settings updated successfully.')->sendJson();
        }
    }


    /**
     * GET /api/company/settings/inventory
     * POST /api/company/settings/inventory
     */
    public function inventorySettingsAction(TinyPHP_Request $request)
    {
        $companyId   = tenantContext()->companyId;
        $settingsSvc = new Service_CompanySettings(tenantContext());

        if ($request->isMethod('get')) {
            return response([
                'cost_method'    => $settingsSvc->get('inventory.cost_method', 'standard'),
                'multi_warehouse' => (bool)(int) $settingsSvc->get('inventory.multi_warehouse', '0'),
            ])->sendJson();
        }

        if ($request->isMethod('post')) {
            $inputs     = $request->getInputs();
            $costMethod = trim($inputs['cost_method'] ?? '');
            $errors     = [];

            $validMethods = ['standard', 'avco'];
            if (!in_array($costMethod, $validMethods)) {
                $errors['cost_method'] = 'Invalid cost method.';
            }

            $multiWarehouse = isset($inputs['multi_warehouse']) ? (string)(int) $inputs['multi_warehouse'] : null;
            if ($multiWarehouse !== null) {
                if (!in_array($multiWarehouse, ['0', '1'])) {
                    $errors['multi_warehouse'] = 'Invalid value.';
                } elseif ($multiWarehouse === '0') {
                    $activeCount = (int) db()->fetchVar(
                        "SELECT COUNT(*) FROM inv_warehouses WHERE company_id = ? AND status = 'active'",
                        [$companyId]
                    );
                    if ($activeCount > 1) {
                        $errors['multi_warehouse'] = "Cannot disable multi-warehouse: company has {$activeCount} active warehouses. Deactivate all but one before disabling this setting.";
                    }
                }
            }

            if (!empty($errors)) {
                return response([], 'Validation failed', 422)->errors($errors)->sendJson();
            }

            $settingsSvc->set('inventory.cost_method', $costMethod);

            if ($multiWarehouse !== null) {
                $settingsSvc->set('inventory.multi_warehouse', $multiWarehouse);
                Service_CompanySettings::clearMwCache($companyId);
            }

            return response([
                'cost_method'    => $costMethod,
                'multi_warehouse' => $multiWarehouse !== null ? (bool)(int) $multiWarehouse : (bool)(int) $settingsSvc->get('inventory.multi_warehouse', '0'),
            ], 'Inventory settings updated successfully.')->sendJson();
        }
    }


    public function purchasingSettingsAction(TinyPHP_Request $request)
    {
        $settingsSvc = new Service_CompanySettings(tenantContext());

        if ($request->isMethod('get')) {
            return response([
                'vendor_quote_comparison' => (bool)(int) $settingsSvc->get('purchasing.vendor_quote_comparison', '0'),
            ])->sendJson();
        }

        if ($request->isMethod('post')) {
            $inputs = $request->getInputs();
            $vendorQuoteComparison = isset($inputs['vendor_quote_comparison']) ? (string)(int) $inputs['vendor_quote_comparison'] : '0';
            if (!in_array($vendorQuoteComparison, ['0', '1'])) {
                return response([], 'Validation failed', 422)->errors(['vendor_quote_comparison' => 'Invalid value.'])->sendJson();
            }
            $settingsSvc->set('purchasing.vendor_quote_comparison', $vendorQuoteComparison);
            return response(['vendor_quote_comparison' => (bool)(int) $vendorQuoteComparison], 'Purchasing settings updated successfully.')->sendJson();
        }
    }

    private function handleDocSettings(
        TinyPHP_Request $request,
        string $docType,
        array $visibilityKeys,
        bool $hasTerms = true,
        bool $hasDeclaration = true
    ): mixed {
        $settingsSvc = new Service_CompanySettings(tenantContext());
        $emailConfig = new Service_EmailConfig(tenantContext());
        $seqSvc      = new Service_Sequence(tenantContext());
        $registry    = config('pdf_templates', []);

        if ($request->isMethod('get')) {
            $data = [
                'pdf_template' => $emailConfig->getPdfTemplate($docType),
                'sequence'     => $seqSvc->getOneForSettings($docType),
            ];
            foreach ($visibilityKeys as $key) {
                $data[$key] = (bool)(int) $settingsSvc->get("doc_config.{$docType}.{$key}", '1');
            }
            if ($hasTerms)       $data['terms']      = $settingsSvc->get("doc_terms.{$docType}", '') ?: '';
            if ($hasDeclaration) $data['declaration'] = $settingsSvc->get("doc_declaration.{$docType}", '') ?: '';
            return response($data)->sendJson();
        }

        if ($request->isMethod('post')) {
            $inputs = $request->getInputs();

            // PDF template
            $validTemplates = array_keys($registry[$docType] ?? []);
            $tpl = $inputs['pdf_template'] ?? '';
            if (!empty($validTemplates) && !empty($tpl) && !in_array($tpl, $validTemplates)) {
                return response([], 'Invalid template.', 422)->sendJson();
            }
            if (!empty($tpl)) {
                $emailConfig->saveDocConfig($docType, ['pdf_template' => $tpl]);
            }

            // Sequence
            if (!empty($inputs['sequence'])) {
                $seqResult = $seqSvc->saveOne($docType, (array) $inputs['sequence']);
                if (!$seqResult['success']) {
                    return response([], 'Sequence validation failed.', 422)->errors($seqResult['errors'])->sendJson();
                }
            }

            // Visibility toggles
            foreach ($visibilityKeys as $key) {
                $settingsSvc->set("doc_config.{$docType}.{$key}", isset($inputs[$key]) && $inputs[$key] ? '1' : '0');
            }

            // Text content
            if ($hasTerms)       $settingsSvc->set("doc_terms.{$docType}",      Helpers_Html::sanitize($inputs['terms']      ?? ''));
            if ($hasDeclaration) $settingsSvc->set("doc_declaration.{$docType}", Helpers_Html::sanitize($inputs['declaration'] ?? ''));

            return response([], 'Document settings saved.')->sendJson();
        }
    }

    public function docQuotationSettingsAction(TinyPHP_Request $request) {
        return $this->handleDocSettings($request, 'quotation',
            ['show_amount_in_words', 'show_signature']); // show_jurisdiction commented out — enable when PDF support is ready
    }

    public function docSalesOrderSettingsAction(TinyPHP_Request $request) {
        return $this->handleDocSettings($request, 'sales_order',
            ['show_amount_in_words', 'show_signature']); // show_jurisdiction commented out
    }

    public function docProformaInvoiceSettingsAction(TinyPHP_Request $request) {
        return $this->handleDocSettings($request, 'proforma_invoice',
            ['show_amount_in_words', 'show_signature', 'show_bank_details', 'tc_inherit_from_so']); // show_jurisdiction commented out
    }

    public function docPurchaseOrderSettingsAction(TinyPHP_Request $request) {
        return $this->handleDocSettings($request, 'purchase_order',
            ['show_amount_in_words', 'show_signature']); // show_jurisdiction commented out
    }

    public function docPurchaseInquirySettingsAction(TinyPHP_Request $request) {
        return $this->handleDocSettings($request, 'purchase_inquiry',
            [], hasTerms: true, hasDeclaration: false); // show_jurisdiction commented out
    }

    /**
     * GET /api/company/settings/sales
     * POST /api/company/settings/sales
     */
    public function salesSettingsAction(TinyPHP_Request $request)
    {
        $settingsSvc = new Service_CompanySettings(tenantContext());

        if ($request->isMethod('get')) {
            return response([
                'quote_validity_days'   => (int) $settingsSvc->get('sales.quote_validity_days', 15),
                'customer_gst_required' => (bool) $settingsSvc->get('sales.customer_gst_required', false),
                'customer_search_by'    => json_decode($settingsSvc->get('sales.customer_search_by', '["name","gstin","email","phone"]'), true) ?: ['name', 'gstin', 'email', 'phone'],
                'proforma_invoice'      => (bool)(int) $settingsSvc->get('proforma_invoice', 0),
            ])->sendJson();
        }

        if ($request->isMethod('post')) {
            $validityDays    = max(0, (int) $request->getInput('quote_validity_days', 'Int', 15));
            $gstRequired     = $request->getInput('customer_gst_required', 'Int', 0) ? 1 : 0;
            $searchBy        = $request->getInput('customer_search_by', 'array', ['name', 'gstin']);
            $allowedFields   = ['name', 'gstin', 'email', 'phone'];
            $searchBy        = array_values(array_filter((array) $searchBy, fn($f) => in_array($f, $allowedFields)));
            $proformaEnabled = $request->getInput('proforma_invoice', 'Int', 0) ? 1 : 0;

            $settingsSvc->set('sales.quote_validity_days', (string) $validityDays);
            $settingsSvc->set('sales.customer_gst_required', (string) $gstRequired);
            $settingsSvc->set('sales.customer_search_by', json_encode($searchBy, JSON_UNESCAPED_UNICODE));
            $settingsSvc->set('proforma_invoice', (string) $proformaEnabled);

            return response([
                'quote_validity_days'   => $validityDays,
                'customer_gst_required' => (bool) $gstRequired,
                'customer_search_by'    => $searchBy,
                'proforma_invoice'      => (bool) $proformaEnabled,
            ], 'Sales settings saved.')->sendJson();
        }
    }


    /**
     * GET /api/company/settings/doc-sequences
     * POST /api/company/settings/doc-sequences
     */
    public function docSequencesAction(TinyPHP_Request $request)
    {
        $service = new Service_Sequence(tenantContext());

        if ($request->isMethod('get')) {
            return response(['sequences' => $service->getAllForSettings()])->sendJson();
        }

        if ($request->isMethod('post')) {
            $updates = $request->getInput('sequences', 'Array', []);

            $result = $service->saveSettings($updates);

            if ($result['success']) {
                return response(['sequences' => $service->getAllForSettings()], 'Document sequences saved successfully.')->sendJson();
            }

            return response([], 'Validation failed', 422)->errors($result['errors'])->sendJson();
        }
    }


    /**
     * POST /api/companies/register
     * Create a new company + user (pending), send activation email.
     */
    public function registerAction(TinyPHP_Request $request) {

        $data = [
            'first_name'       => $request->getInput('first_name'),
            'last_name'        => $request->getInput('last_name'),
            'company_name'     => $request->getInput('company_name'),
            'email'            => $request->getInput('email'),
            'phone'            => $request->getInput('phone'),
            'country'          => $request->getInput('country'),
            'password'         => $request->getInput('password'),
            'confirm_password' => $request->getInput('confirm_password'),
        ];

        $service = new Service_Company();
        $result = $service->register($data);

        if ($result['success']) {
            return response([], 'Account created successfully. Please check your email to activate your account.')->sendJson();
        }
        
        return response([], 'Failed to create account', 422)->errors($result['errors'])->sendJson();        
    }


    /**
     * POST /api/companies/activate
     * Validate token, activate account, provision trial, seed data.
     * Returns JWT tokens so the front-end can auto-login.
     */
    public function activateAction(TinyPHP_Request $request)
    {
        $rawToken = $request->getInput('token');
        if (empty($rawToken)) {
            return response([], 'Invalid activation url', 422)->sendJson();
        }

        $service = new Service_Company();
        $result  = $service->activate($rawToken);

        if (!$result['success']) {
            return response([], 'Failed to activate account', 422)->errors($result['errors'])->sendJson();
        }

        $user = new Models_User($result['data']['user_id']);
        $tokens = auth()->login($user, $request->getHeader('X-Client-Type'));
        
        if ($tokens) {
            return response($tokens, 'Account activated successfully. Welcome!')->sendJson();
        }

        return response([], 'Account activated but auto-login failed. Please log in manually.', 500)->sendJson();
    }


    /**
     * GET  /api/company/settings/email/smtp  — fetch SMTP + global sender settings
     * POST /api/company/settings/email/smtp  — save
     */
    public function emailSmtpAction(TinyPHP_Request $request)
    {
        $emailSvc = new Service_EmailConfig(tenantContext());

        if ($request->isMethod('get')) {
            return response($emailSvc->getSmtpSettingsForDisplay())->sendJson();
        }

        if ($request->isMethod('post')) {
            try {
                $emailSvc->saveSmtpSettings($request->getInputs());
            } catch (Service_Exception $e) {
                return response([], $e->getMessage(), $e->getHttpStatusCode())->sendJson();
            }
            return response([], 'Email settings saved successfully.')->sendJson();
        }
    }


    /**
     * POST /api/company/settings/email/test-smtp — send a test email using saved SMTP config
     */
    public function emailTestSmtpAction(TinyPHP_Request $request)
    {
        $emailSvc  = new Service_EmailConfig(tenantContext());
        $smtpConfig = $emailSvc->getSMTPConfig();

        $to = trim($request->getInput('to', 'String', ''));
        if (empty($to)) {
            return response([], 'Please provide a recipient email address.', 422)->sendJson();
        }

        if (empty($smtpConfig['host'])) {
            return response([], 'No SMTP configured. Save your server settings first.', 422)->sendJson();
        }

        $mailer = new Helpers_Mailer();
        $from   = $emailSvc->getGlobalFrom();

        $sent = $mailer->sendMail($from, $to, 'Zentraq - SMTP Test Email',
            '<p>This is a test email from your Zentraq account to confirm SMTP is configured correctly.</p>',
            $smtpConfig
        );

        if (!$sent) {
            $errors = $mailer->getErrors();
            $detail = !empty($errors) ? implode('; ', $errors) : 'Unknown SMTP error';
            return response([], "Test failed: {$detail}", 500)->sendJson();
        }

        return response([], 'Test email sent successfully.')->sendJson();
    }


    /**
     * GET  /api/company/settings/email/doc-config?document_type=X — fetch single doc config
     * POST /api/company/settings/email/doc-config                 — save single doc config
     */
    public function emailDocConfigAction(TinyPHP_Request $request)
    {
        $emailSvc     = new Service_EmailConfig(tenantContext());
        $documentType = trim($request->getInput('document_type', 'String', ''));

        if ($request->isMethod('get')) {
            $configs = [];
            foreach (['purchase_order', 'purchase_inquiry', 'sales_order', 'quotation', 'proforma_invoice'] as $type) {
                $configs[$type] = $emailSvc->getDocConfig($type);
            }
            return response(['configs' => $configs])->sendJson();
        }

        if ($request->isMethod('post')) {
            try {
                $emailSvc->saveDocConfig($documentType, $request->getInputs());
            } catch (Service_Exception $e) {
                return response([], $e->getMessage(), $e->getHttpStatusCode())->sendJson();
            }
            return response([], 'Document email config saved successfully.')->sendJson();
        }
    }

}
?>
