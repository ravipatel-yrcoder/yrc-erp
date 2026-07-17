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
            $result = $service->getGeneralSettings($companyId);
            return response($result['data'])->sendJson();
        }

        if ($request->isMethod('post')) {
            $result = $service->updateGeneralSettings($companyId, $request->getInputs());

            if ($result['success']) {
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


    public function docTemplatesAction(TinyPHP_Request $request)
    {
        $settingsSvc = new Service_CompanySettings(tenantContext());
        $emailConfig = new Service_EmailConfig(tenantContext());
        $registry    = config('pdf_templates', []);

        if ($request->isMethod('get')) {
            return response([
                'so_pdf_template'        => $emailConfig->getPdfTemplate('sales_order',   $settingsSvc),
                'quotation_pdf_template' => $emailConfig->getPdfTemplate('quotation',      $settingsSvc),
                'po_pdf_template'        => $emailConfig->getPdfTemplate('purchase_order', $settingsSvc),
                'rfq_pdf_template'       => $emailConfig->getPdfTemplate('rfq',            $settingsSvc),
            ])->sendJson();
        }

        if ($request->isMethod('post')) {
            $soTemplate        = $request->getInput('so_pdf_template',        'String', '');
            $quotationTemplate = $request->getInput('quotation_pdf_template', 'String', '');
            $poTemplate        = $request->getInput('po_pdf_template',        'String', '');
            $rfqTemplate       = $request->getInput('rfq_pdf_template',       'String', '');

            $validSo        = array_keys($registry['sales_order']   ?? []);
            $validQuotation = array_keys($registry['quotation']      ?? []);
            $validPo        = array_keys($registry['purchase_order'] ?? []);
            $validRfq       = array_keys($registry['rfq']            ?? []);

            $errors = [];
            if (!in_array($soTemplate, $validSo)) {
                $errors['so_pdf_template'] = 'Invalid sales order template.';
            }
            if (!in_array($quotationTemplate, $validQuotation)) {
                $errors['quotation_pdf_template'] = 'Invalid quotation template.';
            }
            if (!in_array($poTemplate, $validPo)) {
                $errors['po_pdf_template'] = 'Invalid purchase order template.';
            }
            if (!in_array($rfqTemplate, $validRfq)) {
                $errors['rfq_pdf_template'] = 'Invalid RFQ template.';
            }

            if (!empty($errors)) {
                return response([], 'Validation failed', 422)->errors($errors)->sendJson();
            }

            $emailConfig->saveDocConfig('sales_order',    ['pdf_template' => $soTemplate]);
            $emailConfig->saveDocConfig('quotation',      ['pdf_template' => $quotationTemplate]);
            $emailConfig->saveDocConfig('purchase_order', ['pdf_template' => $poTemplate]);
            $emailConfig->saveDocConfig('rfq',            ['pdf_template' => $rfqTemplate]);

            return response([
                'so_pdf_template'        => $soTemplate,
                'quotation_pdf_template' => $quotationTemplate,
                'po_pdf_template'        => $poTemplate,
                'rfq_pdf_template'       => $rfqTemplate,
            ], 'Document template preferences saved.')->sendJson();
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
                $result = $emailSvc->saveSmtpSettings($request->getInputs());
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
            foreach (['purchase_order', 'rfq', 'sales_order', 'quotation'] as $type) {
                $configs[$type] = $emailSvc->getDocConfig($type);
            }
            return response(['configs' => $configs])->sendJson();
        }

        if ($request->isMethod('post')) {
            try {
                $result = $emailSvc->saveDocConfig($documentType, $request->getInputs());
            } catch (Service_Exception $e) {
                return response([], $e->getMessage(), $e->getHttpStatusCode())->sendJson();
            }
            return response([], 'Document email config saved successfully.')->sendJson();
        }
    }

}
?>
