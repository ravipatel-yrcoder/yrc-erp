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
