<?php
/**
 * Service_Company
 *
 * Handles company self-registration, email activation, trial provisioning,
 * and default data seeding. All platform tables (companies, users,
 * subscriptions, roles) live in platform_db via $this->db (inherited from
 * Service_PlatformBase). Operational tables (locations, crm_stages, taxes)
 * are accessed via their respective models (main_db connection).
 */
class Service_Company extends Service_PlatformBase {

    /**
     * Register a new company.
     *
     * If the email already exists with status=pending, upserts the record
     * and resends the activation email — the front-end sees a normal success.
     * If the email already exists with status=active, returns a field error.
     *
     * @return array ["success" => bool, "data" => [], "errors" => []]
     */
    public function register(array $data): array {
        
        $this->validateRegistration($data);

        if ( $this->hasErrors() ) {
            return ["success" => false, "errors" => $this->getErrors()];
        }
        
        try {

            $email = strtolower(trim($data['email']));
            $existingUser = $this->db->fetchOne("SELECT id, company_id, status FROM users WHERE email = ? LIMIT 1", [$email]);

            if ( $existingUser ) {

                if ( $existingUser->status === 'pending' ) {
                    
                    return $this->upsertPendingRegistration((int) $existingUser->id, (int) $existingUser->company_id, $data);
                }

                $this->addError(validationErrMsg('email_already_registered', ''), 'email');

                return ["success" => false, "errors" => $this->getErrors()];
            }

            return $this->createNewRegistration($data);
        
        } catch (Exception $e) {
            
            $this->db->rollBack();
            throw $e;
        }     
    }



    /**
     * Activate a company account from a raw email verification token.
     *
     * On success: activates user, provisions trial subscription,
     * seeds default data, and returns the user_id for auto-login.
     *
     * @return array ["success" => bool, "data" => ["user_id" => int], "errors" => []]
     */
    public function activate(string $rawToken): array
    {
        $hash = hash('sha256', $rawToken);

        $row = $this->db->fetchOne(
            "SELECT id, company_id, email_verification_expires_at
             FROM   users
             WHERE  email_verification_token = ?
             AND    status = 'pending'
             LIMIT  1",
            [$hash]
        );

        if (!$row) {
            $this->addError(validationErrMsg('invalid_activation_token', ''), 'token');
            return ["success" => false, "errors" => $this->getErrors()];
        }

        if (strtotime($row->email_verification_expires_at) < time()) {
            $this->addError(validationErrMsg('expired_activation_token', ''), 'token');
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $user = new Models_User((int) $row->id);
        $user->status = 'active';
        $user->email_verified_at = date('Y-m-d H:i:s');
        $user->email_verification_token = null;
        $user->email_verification_expires_at = null;

        if (!$user->update()) {
            throw new Service_Exception("Failed to activate user account", 500);
        }

        $moduleKeys = $this->createTrialSubscription((int) $row->company_id, (int) $row->id);

        $this->seedForModules((int) $row->company_id, (int) $row->id, $moduleKeys);

        return ["success" => true, "data" => ["user_id" => (int) $row->id]];
    }


    // -------------------------------------------------------------------------
    // Public — seeding entry point (can be called independently per module)
    // -------------------------------------------------------------------------

    /**
     * Seed all default operational data for the given company/user/modules.
     * Each sub-method is isolated so it can be called independently when a
     * company upgrades or adds a new module to an existing account.
     */
    public function seedForModules(int $companyId, int $userId, array $moduleKeys): void
    {
        $this->seedAdminRole($companyId, $userId);
        $this->seedLocation($companyId);

        if (in_array('crm', $moduleKeys)) {
            $this->seedCrmStages($companyId, $userId);
        }

        $commercialModules = ['sales', 'inventory', 'purchasing'];
        if (array_intersect($moduleKeys, $commercialModules)) {
            $this->seedTaxes($companyId, $userId);
        }
    }


    // -------------------------------------------------------------------------
    // Private — registration helpers
    // -------------------------------------------------------------------------

    private function createNewRegistration(array $data): array
    {
        $this->db->startTransaction();

        try {

            $firstName = trim($data['first_name']);
            $lastName = trim($data['last_name'] ?? '');
            $fullName = $firstName . ($lastName !== '' ? ' ' . $lastName : '');
            $email = strtolower(trim($data['email']));
            $phone = trim($data['phone']);
            $companyName = trim($data['company_name']);

            $company = new Models_Company();
            $company->name = $companyName;
            $company->email = $email;
            $company->phone = $phone;
            $company->contact_name  = $fullName;
            $company->contact_email = $email;
            $company->contact_phone = $phone;
            $company->status = 'active';
            $companyId = $company->create();

            if (!$companyId) throw new Service_Exception("Failed to create company");

            $token = Service_AuthToken::generateOpaqueToken(24 * 60);

            $user = new Models_User();
            $user->company_id = $companyId;
            $user->first_name = $firstName;
            $user->last_name = $lastName !== '' ? $lastName : null;
            $user->name = $fullName;
            $user->email = $email;
            $user->phone = $phone;
            $user->password = hashPassword($data['password']);
            $user->status = 'pending';
            $user->email_verification_token = $token['hash'];
            $user->email_verification_expires_at = date('Y-m-d H:i:s', $token['expires_at']);

            if (!$user->create()) {
                throw new Service_Exception("Failed to create user");
            }

            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->sendActivationEmail($email, $token['raw'], $fullName);

        return ["success" => true, "data" => []];
    }

    private function upsertPendingRegistration(int $userId, int $companyId, array $data): array
    {
        $firstName = trim($data['first_name']);
        $lastName = trim($data['last_name'] ?? '');
        $fullName = $firstName . ($lastName !== '' ? ' ' . $lastName : '');
        $phone = trim($data['phone']);
        $companyName = trim($data['company_name']);

        $this->db->startTransaction();

        try {

            $company = new Models_Company($companyId);
            $company->name = $companyName;
            $company->phone = $phone;
            $company->contact_name = $fullName;
            $company->contact_phone = $phone;

            if (!$company->update()) throw new Service_Exception("Failed to update company");

            $token = Service_AuthToken::generateOpaqueToken(24 * 60);

            $user = new Models_User($userId);
            $user->first_name = $firstName;
            $user->last_name = $lastName !== '' ? $lastName : null;
            $user->name = $fullName;
            $user->phone = $phone;
            $user->password = hashPassword($data['password']);
            $user->email_verification_token      = $token['hash'];
            $user->email_verification_expires_at = date('Y-m-d H:i:s', $token['expires_at']);

            if (!$user->update()) throw new Service_Exception("Failed to update user");

            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }

        $this->sendActivationEmail(strtolower(trim($data['email'])), $token['raw'], $fullName);

        return ["success" => true, "data" => []];
    }


    // -------------------------------------------------------------------------
    // Private — trial provisioning
    // -------------------------------------------------------------------------

    private function createTrialSubscription(int $companyId, int $userId): array
    {
        $plan = $this->db->fetchOne("SELECT * FROM subscription_plans WHERE slug = 'all_apps' LIMIT 1");
        if (!$plan) return [];

        $sub                        = new Models_CompanySubscription();
        $sub->company_id            = $companyId;
        $sub->plan_id               = $plan->id;
        $sub->status                = 'trial';
        $sub->trial_ends_at         = date('Y-m-d H:i:s', strtotime('+14 days'));
        $sub->free_users_included   = $plan->free_users_included;
        $sub->purchased_extra_seats = 0;
        $sub->is_current            = 1;
        $sub->created_by            = $userId;
        $subId = $sub->create();

        if (!$subId) throw new Service_Exception("Failed to create trial subscription");

        $modules    = $this->db->fetchAll("SELECT id, `key` FROM modules WHERE is_active = 1 ORDER BY sort_order ASC");
        $moduleKeys = [];

        foreach ($modules as $module) {
            $csm                  = new Models_CompanySubscriptionModule();
            $csm->company_id      = $companyId;
            $csm->subscription_id = $subId;
            $csm->module_id       = $module->id;
            $csm->is_active       = 1;

            if (!$csm->create()) throw new Service_Exception("Failed to assign module: {$module->key}");

            $moduleKeys[] = $module->key;
        }

        return $moduleKeys;
    }


    // -------------------------------------------------------------------------
    // Private — default data seeding (one method per concern)
    // -------------------------------------------------------------------------

    private function seedAdminRole(int $companyId, int $userId): void
    {
        $role             = new Models_CompanyRole();
        $role->company_id = $companyId;
        $role->name       = 'Admin';
        $role->slug       = 'admin';
        $role->is_super   = 1;
        $role->is_system  = 1;
        $role->status     = 'active';
        $role->created_by = $userId;
        $roleId = $role->create();

        if (!$roleId) throw new Service_Exception("Failed to create admin role");

        $userRole             = new Models_UserRole();
        $userRole->company_id = $companyId;
        $userRole->user_id    = $userId;
        $userRole->role_id    = $roleId;
        $userRole->created_by = $userId;

        if (!$userRole->create()) throw new Service_Exception("Failed to assign admin role to user");
    }

    private function seedLocation(int $companyId): void
    {
        $location            = new Models_Location();
        $location->company_id = $companyId;
        $location->name      = 'Main Office';
        $location->type      = 'head_office';
        $location->is_main   = 1;
        $location->status    = 'active';

        if (!$location->create()) throw new Service_Exception("Failed to seed default location");
    }

    private function seedCrmStages(int $companyId, int $userId): void
    {
        $stages = [
            ['name' => 'New',         'probability' => 5,   'sort_order' => 1, 'is_won' => 0, 'is_lost' => 0, 'color' => '#6c757d'],
            ['name' => 'Contacted',   'probability' => 15,  'sort_order' => 2, 'is_won' => 0, 'is_lost' => 0, 'color' => '#0d6efd'],
            ['name' => 'Qualified',   'probability' => 25,  'sort_order' => 3, 'is_won' => 0, 'is_lost' => 0, 'color' => '#0dcaf0'],
            ['name' => 'Proposal',    'probability' => 50,  'sort_order' => 4, 'is_won' => 0, 'is_lost' => 0, 'color' => '#ffc107'],
            ['name' => 'Negotiation', 'probability' => 75,  'sort_order' => 5, 'is_won' => 0, 'is_lost' => 0, 'color' => '#fd7e14'],
            ['name' => 'Won',         'probability' => 100, 'sort_order' => 6, 'is_won' => 1, 'is_lost' => 0, 'color' => '#198754'],
            ['name' => 'Lost',        'probability' => 0,   'sort_order' => 7, 'is_won' => 0, 'is_lost' => 1, 'color' => '#dc3545'],
        ];

        foreach ($stages as $stageData) {
            $stage              = new Models_CrmStage();
            $stage->company_id  = $companyId;
            $stage->name        = $stageData['name'];
            $stage->probability = $stageData['probability'];
            $stage->sort_order  = $stageData['sort_order'];
            $stage->is_won      = $stageData['is_won'];
            $stage->is_lost     = $stageData['is_lost'];
            $stage->color       = $stageData['color'];
            $stage->status      = 'active';
            $stage->created_by  = $userId;

            if (!$stage->create()) throw new Service_Exception("Failed to seed CRM stage: {$stageData['name']}");
        }
    }

    private function seedTaxes(int $companyId, int $userId): void
    {
        $taxes = [
            ['name' => 'GST 0%',  'code' => 'GST0',  'rate' => '0.0000'],
            ['name' => 'GST 5%',  'code' => 'GST5',  'rate' => '5.0000'],
            ['name' => 'GST 12%', 'code' => 'GST12', 'rate' => '12.0000'],
            ['name' => 'GST 18%', 'code' => 'GST18', 'rate' => '18.0000'],
            ['name' => 'GST 28%', 'code' => 'GST28', 'rate' => '28.0000'],
        ];

        foreach ($taxes as $taxData) {
            $tax                  = new Models_Tax();
            $tax->company_id      = $companyId;
            $tax->name            = $taxData['name'];
            $tax->code            = $taxData['code'];
            $tax->rate            = $taxData['rate'];
            $tax->tax_type        = 'percentage';
            $tax->price_included  = 0;
            $tax->apply_on        = 'both';
            $tax->status          = 'active';
            $tax->created_by      = $userId;

            if (!$tax->create()) throw new Service_Exception("Failed to seed tax: {$taxData['name']}");
        }
    }


    // -------------------------------------------------------------------------
    // Private — token + email
    // -------------------------------------------------------------------------

    private function sendActivationEmail(string $to, string $rawToken, string $name): void
    {
        $activationUrl = rtrim(config('app.url'), '/') . '/companies/activate?token=' . urlencode($rawToken);
        $appName       = config('app.name');
        $fromEmail     = config('app.support_email', 'noreply@zentraqone.com');

        $subject = "Activate your {$appName} account";
        $body    = "
            <div style='font-family:sans-serif;max-width:520px;margin:0 auto;'>
                <p>Hi {$name},</p>
                <p>Thank you for signing up for <strong>{$appName}</strong>. Click the button below to activate your account and start your 14-day free trial.</p>
                <p style='text-align:center;margin:32px 0;'>
                    <a href='{$activationUrl}'
                       style='background:#0d6efd;color:#fff;padding:12px 28px;text-decoration:none;border-radius:6px;font-size:15px;display:inline-block;'>
                        Activate My Account
                    </a>
                </p>
                <p style='color:#666;font-size:13px;'>Or copy this link into your browser:<br>{$activationUrl}</p>
                <p style='color:#666;font-size:13px;'>This link expires in <strong>24 hours</strong>.</p>
                <hr style='border:none;border-top:1px solid #eee;margin:24px 0;'>
                <p style='color:#999;font-size:12px;'>The {$appName} Team</p>
            </div>
        ";

        $mailer = new Helpers_Mailer();
        $mailer->sendMail("{$appName} <{$fromEmail}>", $to, $subject, $body);
    }


    // -------------------------------------------------------------------------
    // Private — validation
    // -------------------------------------------------------------------------


    // -------------------------------------------------------------------------
    // Public — company profile
    // -------------------------------------------------------------------------

    public function getProfile(int $companyId): array
    {
        $company = new Models_Company($companyId);
        if ($company->isEmpty) {
            throw new Service_Exception("Company not found", 404);
        }

        return ["success" => true, "data" => [
            "id"            => $company->id,
            "name"          => $company->name,
            "email"         => $company->email,
            "phone"         => $company->phone,
            "address"       => $company->address,
            "city"          => $company->city,
            "state"         => $company->state,
            "country"       => $company->country,
            "zipcode"       => $company->zipcode,
            "contact_name"  => $company->contact_name,
            "contact_email" => $company->contact_email,
            "contact_phone" => $company->contact_phone,
            "timezone"      => $company->timezone,
            "currency"      => $company->currency,
        ]];
    }

    public function updateProfile(int $companyId, array $data): array
    {
        $this->validateProfile($data);

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        try {

            $company                = new Models_Company($companyId);
            $company->name          = trim($data['name']);
            $company->email         = trim($data['email'] ?? '');
            $company->phone         = trim($data['phone'] ?? '');
            $company->address       = trim($data['address'] ?? '') ?: null;
            $company->city          = trim($data['city'] ?? '') ?: null;
            $company->state         = trim($data['state'] ?? '') ?: null;
            $company->country       = trim($data['country'] ?? '') ?: null;
            $company->zipcode       = trim($data['zipcode'] ?? '') ?: null;
            $company->contact_name  = trim($data['contact_name'] ?? '') ?: null;
            $company->contact_email = trim($data['contact_email'] ?? '') ?: null;
            $company->contact_phone = trim($data['contact_phone'] ?? '') ?: null;
            $company->timezone      = $data['timezone'];
            $company->currency      = $data['currency'];

            if (!$company->update()) {
                throw new Service_Exception("Failed to update company profile");
            }

        } catch (Exception $e) {
            throw $e;
        }

        return ["success" => true, "data" => []];
    }

    private function validateProfile(array $data): void
    {
        if (empty(trim($data['name'] ?? ''))) {
            $this->addError(validationErrMsg('required', 'Company name'), 'name');
        }

        $email = trim($data['email'] ?? '');
        if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError(validationErrMsg('invalid', 'Email'), 'email');
        }

        $contactEmail = trim($data['contact_email'] ?? '');
        if (!empty($contactEmail) && !filter_var($contactEmail, FILTER_VALIDATE_EMAIL)) {
            $this->addError(validationErrMsg('invalid', 'Contact email'), 'contact_email');
        }

        if (empty($data['timezone'])) {
            $this->addError(validationErrMsg('required', 'Timezone'), 'timezone');
        }

        if (empty($data['currency'])) {
            $this->addError(validationErrMsg('required', 'Currency'), 'currency');
        }
    }


    // -------------------------------------------------------------------------

    private function validateRegistration(array $data): void
    {
        if (empty(trim($data['first_name'] ?? ''))) {
            $this->addError(validationErrMsg('required', 'First name'), 'first_name');
        }
        if (empty(trim($data['company_name'] ?? ''))) {
            $this->addError(validationErrMsg('required', 'Company name'), 'company_name');
        }

        $email = trim($data['email'] ?? '');
        if ($email === '') {
            $this->addError(validationErrMsg('required', 'Email'), 'email');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->addError(validationErrMsg('invalid', 'Email'), 'email');
        }

        if (empty(trim($data['phone'] ?? ''))) {
            $this->addError(validationErrMsg('required', 'Phone'), 'phone');
        }

        $password = $data['password'] ?? '';
        if ($password === '') {
            $this->addError(validationErrMsg('required', 'Password'), 'password');
        } elseif (strlen($password) < 8) {
            $this->addError(validationErrMsg('password_too_short', ''), 'password');
        }

        if ($password !== ($data['confirm_password'] ?? '')) {
            $this->addError(validationErrMsg('password_mismatch', ''), 'confirm_password');
        }
    }
}
?>
