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


    public function docTemplatesAction(TinyPHP_Request $request)
    {
        $settingsSvc = new Service_CompanySettings(tenantContext());
        $registry    = config('pdf_templates', []);

        if ($request->isMethod('get')) {
            return response([
                'so_pdf_template'  => $settingsSvc->get('so_pdf_template',  'template_1'),
                'po_pdf_template'  => $settingsSvc->get('po_pdf_template',  'template_1'),
                'rfq_pdf_template' => $settingsSvc->get('rfq_pdf_template', 'template_1'),
            ])->sendJson();
        }

        if ($request->isMethod('post')) {
            $soTemplate  = $request->getInput('so_pdf_template',  'String', '');
            $poTemplate  = $request->getInput('po_pdf_template',  'String', '');
            $rfqTemplate = $request->getInput('rfq_pdf_template', 'String', '');

            $validSo  = array_keys($registry['sales_order']    ?? []);
            $validPo  = array_keys($registry['purchase_order'] ?? []);
            $validRfq = array_keys($registry['rfq']            ?? []);

            $errors = [];
            if (!in_array($soTemplate, $validSo)) {
                $errors['so_pdf_template'] = 'Invalid sales order template.';
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

            $settingsSvc->setMultiple([
                'so_pdf_template'  => $soTemplate,
                'po_pdf_template'  => $poTemplate,
                'rfq_pdf_template' => $rfqTemplate,
            ]);

            return response([
                'so_pdf_template'  => $soTemplate,
                'po_pdf_template'  => $poTemplate,
                'rfq_pdf_template' => $rfqTemplate,
            ], 'Document template preferences saved.')->sendJson();
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
}
?>
