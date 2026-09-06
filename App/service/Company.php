<?php
/**
 * Service_Company
 *
 * Handles company self-registration, email activation, trial provisioning,
 * and default data seeding. All platform tables (companies, users,
 * subscriptions, roles) live in platform_db via $this->db (inherited from
 * Service_PlatformBase). Operational tables (warehouses, company_locations, crm_stages, taxes)
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
            
            $this->db->rollback();
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

        $this->db->startTransaction();
        try {

            // Lock the user row so concurrent activation attempts block here
            $row = $this->db->fetchOne(
                "SELECT id, company_id, email_verification_expires_at
                 FROM   users
                 WHERE  email_verification_token = ?
                 AND    status = 'pending'
                 LIMIT  1 FOR UPDATE",
                [$hash]
            );

            if (!$row) {
                $this->db->rollback();
                $this->addError(validationErrMsg('invalid_activation_token', ''), 'token');
                return ["success" => false, "errors" => $this->getErrors()];
            }

            if (strtotime($row->email_verification_expires_at) < time()) {
                $this->db->rollback();
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

            $this->db->commit();

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }

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
        $company = new Models_Company($companyId);
        $country = strtoupper(trim($company->country ?? '')) ?: 'IN';

        $this->seedAdminRole($companyId, $userId);
        $this->seedCompanyLocation($companyId);
        $this->seedWarehouse($companyId);
        $this->seedPaymentTerms($companyId, $userId);
        Service_CompanySettings::seedDefaults($companyId, $this->db);
        Service_EmailConfig::seedDefaults($companyId, $this->db);

        if (in_array('crm', $moduleKeys)) {
            $this->seedCrmStages($companyId, $userId);
        }

        $commercialModules = ['sales', 'inventory', 'purchasing'];
        if (array_intersect($moduleKeys, $commercialModules)) {
            $this->seedTaxes($companyId, $userId, $country);
        }

        if (in_array('sales', $moduleKeys)) {
            $this->seedReturnDefaults($companyId, $userId);
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

            $countryCode = strtoupper(trim($data['country'] ?? ''));
            $defaults    = $this->getCountryDefaults($countryCode);

            $company = new Models_Company();
            $company->name = $companyName;
            $company->email = $email;
            $company->phone = $phone;
            $company->country   = $countryCode ?: null;
            $company->timezone  = $defaults['timezone'];
            $company->currency  = $defaults['currency'];
            $company->contact_name  = $fullName;
            $company->contact_email = $email;
            $company->contact_phone = $phone;
            $company->business_type = 'general';
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

            $countryCode = strtoupper(trim($data['country'] ?? ''));
            $defaults    = $this->getCountryDefaults($countryCode);

            $company = new Models_Company($companyId);
            $company->name     = $companyName;
            $company->phone    = $phone;
            $company->country  = $countryCode ?: null;
            $company->timezone = $defaults['timezone'];
            $company->currency = $defaults['currency'];
            $company->contact_name  = $fullName;
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
        // Guard: skip if a current subscription already exists (prevents duplicates on race conditions)
        $existing = $this->db->fetchOne(
            "SELECT id FROM company_subscriptions WHERE company_id = ? AND is_current = 1 LIMIT 1",
            [$companyId]
        );
        if ($existing) return [];

        $plan = $this->db->fetchOne("SELECT * FROM subscription_plans WHERE slug = 'all_apps' LIMIT 1");
        if (!$plan) return [];

        $sub = new Models_CompanySubscription();
        $sub->company_id = $companyId;
        $sub->plan_id = $plan->id;
        $sub->status = 'trial';
        $sub->trial_ends_at = date('Y-m-d H:i:s', strtotime('+14 days'));
        $sub->free_users_included = $plan->free_users_included;
        $sub->purchased_extra_seats = 0;
        $sub->is_current = 1;
        $sub->created_by = $userId;
        $subId = $sub->create();

        if (!$subId) throw new Service_Exception("Failed to create trial subscription");

        // Fetch all active modules — including system modules — so every company's
        // subscription record is complete and all access checks go through one path.
        $modules    = $this->db->fetchAll("SELECT id, `key` FROM modules WHERE is_active = 1 ORDER BY sort_order ASC");
        $moduleKeys = [];

        foreach ($modules as $module) {
            $csm = new Models_CompanySubscriptionModule();
            $csm->company_id = $companyId;
            $csm->subscription_id = $subId;
            $csm->module_id = $module->id;
            $csm->is_active = 1;

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
        $role = new Models_CompanyRole();
        $role->company_id = $companyId;
        $role->name = 'Admin';
        $role->slug = 'admin';
        $role->is_admin = 1;
        $role->status = 'active';
        $role->created_by = $userId;
        $roleId = $role->create();

        if (!$roleId)  {
            throw new Service_Exception("Failed to create admin role");
        }

        $userRole = new Models_UserRole();
        $userRole->company_id = $companyId;
        $userRole->user_id = $userId;
        $userRole->role_id = $roleId;
        $userRole->created_by = $userId;

        if (!$userRole->create()) {
            throw new Service_Exception("Failed to assign admin role to user");
        }

        // Mark the registering user as the company owner
        $this->db->query("UPDATE users SET is_company = 1 WHERE id = ? AND company_id = ?", [$userId, $companyId]);
    }

    private function seedCompanyLocation(int $companyId): void
    {
        $company = new Models_Company($companyId);

        $location = new Models_CompanyLocation();
        $location->company_id    = $companyId;
        $location->name          = 'Default';
        $location->city          = $company->city;
        $location->state         = $company->state;
        $location->country       = $company->country;
        $location->zip           = $company->zipcode;
        $location->address_line1 = $company->address;
        $location->gstin         = $company->gstin;
        $location->phone         = $company->phone;
        $location->email         = $company->email;
        $location->is_default    = 1;
        $location->status        = 'active';

        if (!$location->create()) throw new Service_Exception("Failed to seed default company location");
    }

    private function seedWarehouse(int $companyId): void
    {
        $db = Service_TenantDBResolver::resolve($companyId);

        $companyLocationId = $db->fetchOne(
            "SELECT id FROM company_locations WHERE company_id = ? AND is_default = 1 LIMIT 1",
            [$companyId]
        )->id ?? null;

        $warehouse = new Models_InvWarehouse();
        $warehouse->company_id           = $companyId;
        $warehouse->company_location_id  = $companyLocationId;
        $warehouse->name                 = 'Main';
        $warehouse->type                 = 'warehouse';
        $warehouse->is_default           = 1;
        $warehouse->status               = 'active';

        if (!$warehouse->create()) throw new Service_Exception("Failed to seed default warehouse");
    }

    private function seedPaymentTerms(int $companyId, int $userId): void
    {
        $terms = [
            ['name' => 'Immediate', 'days' => 0, 'is_default' => 1],
            ['name' => 'Net 15', 'days' => 15, 'is_default' => 0],
            ['name' => 'Net 30', 'days' => 30, 'is_default' => 0],
            ['name' => 'Net 60', 'days' => 60, 'is_default' => 0],
        ];

        foreach ($terms as $termData) {
            $term = new Models_PaymentTerm();
            $term->company_id = $companyId;
            $term->name = $termData['name'];
            $term->days = $termData['days'];
            $term->is_default = $termData['is_default'];
            $term->status = 'active';
            $term->created_by = $userId;
            $term->created_at = date('Y-m-d H:i:s');

            if (!$term->create()) throw new Service_Exception("Failed to seed payment term: {$termData['name']}");
        }
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

    private function seedTaxes(int $companyId, int $userId, string $country = 'IN'): void
    {
        $taxSets = [
            'IN' => [
                ['name' => 'GST 0%',  'code' => 'GST0',  'rate' => '0.0000',  'gst_component' => 'none'],
                ['name' => 'GST 5%',  'code' => 'GST5',  'rate' => '5.0000',  'gst_component' => 'gst'],
                ['name' => 'GST 12%', 'code' => 'GST12', 'rate' => '12.0000', 'gst_component' => 'gst'],
                ['name' => 'GST 18%', 'code' => 'GST18', 'rate' => '18.0000', 'gst_component' => 'gst'],
                ['name' => 'GST 28%', 'code' => 'GST28', 'rate' => '28.0000', 'gst_component' => 'gst'],
            ],
            'US' => [
                ['name' => 'Tax Exempt', 'code' => 'EXEMPT', 'rate' => '0.0000'],
                ['name' => 'Sales Tax',  'code' => 'TAX',    'rate' => '0.0000'],
            ],
            'CA' => [
                ['name' => 'Tax Exempt', 'code' => 'EXEMPT', 'rate' => '0.0000'],
                ['name' => 'GST',        'code' => 'GST',    'rate' => '5.0000'],
                ['name' => 'HST',        'code' => 'HST',    'rate' => '13.0000'],
            ],
        ];

        $taxes = $taxSets[$country] ?? [
            ['name' => 'Tax Exempt',   'code' => 'EXEMPT', 'rate' => '0.0000'],
            ['name' => 'Standard Tax', 'code' => 'TAX',    'rate' => '0.0000'],
        ];

        foreach ($taxes as $taxData) {
            $tax                 = new Models_Tax();
            $tax->company_id     = $companyId;
            $tax->name           = $taxData['name'];
            $tax->code           = $taxData['code'];
            $tax->rate           = $taxData['rate'];
            $tax->gst_component  = $taxData['gst_component'] ?? 'none';
            $tax->tax_type       = 'percentage';
            $tax->price_included = 0;
            $tax->status         = 'active';
            $tax->created_by     = $userId;

            if (!$tax->create()) throw new Service_Exception("Failed to seed tax: {$taxData['name']}");
        }
    }

    private function seedReturnDefaults(int $companyId, int $userId): void
    {
        $dispositions = [
            ['name' => 'Restock',             'bucket' => 'unrestricted', 'is_default' => 1, 'sort_order' => 1],
            ['name' => 'Quality Inspection',  'bucket' => 'quality',      'is_default' => 0, 'sort_order' => 2],
            ['name' => 'Blocked Stock',       'bucket' => 'blocked',      'is_default' => 0, 'sort_order' => 3],
            ['name' => 'Scrap',               'bucket' => 'scrap',        'is_default' => 0, 'sort_order' => 4],
        ];

        $db = Service_TenantDBResolver::resolve($companyId);

        foreach ($dispositions as $d) {
            $db->query(
                "INSERT IGNORE INTO return_dispositions (company_id, name, bucket, is_default, is_active, sort_order, created_by)
                 VALUES (?, ?, ?, ?, 1, ?, ?)",
                [$companyId, $d['name'], $d['bucket'], $d['is_default'], $d['sort_order'], $userId]
            );
        }

        $reasons = [
            ['name' => 'Wrong Item Delivered',   'is_default' => 1, 'sort_order' => 1],
            ['name' => 'Damaged in Transit',      'is_default' => 0, 'sort_order' => 2],
            ['name' => 'Defective Product',       'is_default' => 0, 'sort_order' => 3],
            ['name' => 'Customer Changed Mind',   'is_default' => 0, 'sort_order' => 4],
            ['name' => 'Excess Quantity',         'is_default' => 0, 'sort_order' => 5],
            ['name' => 'Not as Described',        'is_default' => 0, 'sort_order' => 6],
        ];

        foreach ($reasons as $r) {
            $exists = $db->fetchVar(
                "SELECT id FROM return_reasons WHERE company_id = ? AND name = ?",
                [$companyId, $r['name']]
            );
            if (!$exists) {
                $db->query(
                    "INSERT INTO return_reasons (company_id, name, is_default, is_active, sort_order, created_by)
                     VALUES (?, ?, ?, 1, ?, ?)",
                    [$companyId, $r['name'], $r['is_default'], $r['sort_order'], $userId]
                );
            }
        }
    }


    private function getCountryDefaults(string $country): array
    {
        $map = [
            'IN' => ['timezone' => 'Asia/Kolkata',        'currency' => 'INR'],
            'US' => ['timezone' => 'America/New_York',    'currency' => 'USD'],
            'CA' => ['timezone' => 'America/Toronto',     'currency' => 'CAD'],
            'GB' => ['timezone' => 'Europe/London',       'currency' => 'GBP'],
            'AE' => ['timezone' => 'Asia/Dubai',          'currency' => 'AED'],
            'AU' => ['timezone' => 'Australia/Sydney',    'currency' => 'AUD'],
            'SG' => ['timezone' => 'Asia/Singapore',      'currency' => 'SGD'],
            'NZ' => ['timezone' => 'Pacific/Auckland',    'currency' => 'NZD'],
            'ZA' => ['timezone' => 'Africa/Johannesburg', 'currency' => 'ZAR'],
            'DE' => ['timezone' => 'Europe/Berlin',       'currency' => 'EUR'],
            'FR' => ['timezone' => 'Europe/Paris',        'currency' => 'EUR'],
            'IT' => ['timezone' => 'Europe/Rome',         'currency' => 'EUR'],
            'ES' => ['timezone' => 'Europe/Madrid',       'currency' => 'EUR'],
            'NL' => ['timezone' => 'Europe/Amsterdam',    'currency' => 'EUR'],
            'PK' => ['timezone' => 'Asia/Karachi',        'currency' => 'PKR'],
            'BD' => ['timezone' => 'Asia/Dhaka',          'currency' => 'BDT'],
            'LK' => ['timezone' => 'Asia/Colombo',        'currency' => 'LKR'],
            'MY' => ['timezone' => 'Asia/Kuala_Lumpur',   'currency' => 'MYR'],
            'PH' => ['timezone' => 'Asia/Manila',         'currency' => 'PHP'],
            'ID' => ['timezone' => 'Asia/Jakarta',        'currency' => 'IDR'],
            'NG' => ['timezone' => 'Africa/Lagos',        'currency' => 'NGN'],
            'KE' => ['timezone' => 'Africa/Nairobi',      'currency' => 'KES'],
            'MX' => ['timezone' => 'America/Mexico_City', 'currency' => 'MXN'],
            'BR' => ['timezone' => 'America/Sao_Paulo',   'currency' => 'BRL'],
            'JP' => ['timezone' => 'Asia/Tokyo',          'currency' => 'JPY'],
            'CN' => ['timezone' => 'Asia/Shanghai',       'currency' => 'CNY'],
        ];

        return $map[$country] ?? ['timezone' => 'UTC', 'currency' => 'USD'];
    }


    // -------------------------------------------------------------------------
    // Private — token + email
    // -------------------------------------------------------------------------

    private function sendActivationEmail(string $to, string $rawToken, string $name): void
    {
        $appUrl        = rtrim(config('app.url'), '/');
        $activationUrl = $appUrl . '/companies/activate?token=' . urlencode($rawToken);
        $appName       = config('app.name');
        $fromEmail     = config('app.support_email', 'noreply@zentraqone.com');

        $subject = "Confirm your email to get started - {$appName}";
        $body = Helpers_EmailRenderer::render('emails.activation', [
            'name' => $name,
            'activationUrl' => $activationUrl,
            'appName' => $appName,
            'logoUrl' => $appUrl . '/assets/img/logo.png',
            'preheader' => "You're one click away — confirm your email to start your 14-day free trial.",
        ]);

        $mailer = new Helpers_Mailer();
        $mailer->addBCC("ravipatel96013@gmail.com");
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
            "id"             => $company->id,
            "name"           => $company->name,
            "legal_name"     => $company->legal_name,
            "email"          => $company->email,
            "phone"          => $company->phone,
            "website"        => $company->website,
            "address"        => $company->address,
            "city"           => $company->city,
            "state"          => $company->state,
            "country"        => $company->country,
            "zipcode"        => $company->zipcode,
            "gstin"          => $company->gstin,
            "pan"            => $company->pan,
            "tan"            => $company->tan,
            "cin"            => $company->cin,
            "logo_path"      => $company->logo_path,
            "signature_path" => $company->signature_path,
            "contact_name"   => $company->contact_name,
            "contact_email"  => $company->contact_email,
            "contact_phone"  => $company->contact_phone,
            "timezone"       => $company->timezone,
            "currency"       => $company->currency,
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
            $company->legal_name    = trim($data['legal_name'] ?? '') ?: null;
            $company->email         = trim($data['email'] ?? '');
            $company->phone         = trim($data['phone'] ?? '');
            $company->website       = trim($data['website'] ?? '') ?: null;
            $company->address       = trim($data['address'] ?? '') ?: null;
            $company->city          = trim($data['city'] ?? '') ?: null;
            $company->state         = trim($data['state'] ?? '') ?: null;
            $company->country       = trim($data['country'] ?? '') ?: null;
            $company->zipcode       = trim($data['zipcode'] ?? '') ?: null;
            $company->gstin         = trim($data['gstin'] ?? '') ?: null;
            $company->pan           = trim($data['pan'] ?? '') ?: null;
            $company->tan           = trim($data['tan'] ?? '') ?: null;
            $company->cin           = trim($data['cin'] ?? '') ?: null;
            $company->contact_name  = trim($data['contact_name'] ?? '') ?: null;
            $company->contact_email = trim($data['contact_email'] ?? '') ?: null;
            $company->contact_phone = trim($data['contact_phone'] ?? '') ?: null;
            $company->timezone      = $data['timezone'];
            $company->currency      = $data['currency'];

            if (!empty($data['logo_file']) && is_array($data['logo_file'])) {
                $company->logo_path = $this->saveUploadedFile($companyId, $data['logo_file']);
            }
            if (!empty($data['signature_file']) && is_array($data['signature_file'])) {
                $company->signature_path = $this->saveUploadedFile($companyId, $data['signature_file']);
            }

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

        $website = trim($data['website'] ?? '');
        if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
            $this->addError(validationErrMsg('invalid', 'Website URL'), 'website');
        }

        if (empty($data['timezone'])) {
            $this->addError(validationErrMsg('required', 'Timezone'), 'timezone');
        }

        if (empty($data['currency'])) {
            $this->addError(validationErrMsg('required', 'Currency'), 'currency');
        }
    }


    // -------------------------------------------------------------------------

    public function getGeneralSettings(int $companyId): array
    {
        $company = new Models_Company($companyId);
        if ($company->isEmpty) {
            throw new Service_Exception("Company not found", 404);
        }

        return ["success" => true, "data" => [
            "name"           => $company->name,
            "email"          => $company->email,
            "phone"          => $company->phone,
            "website"        => $company->website,
            "address"        => $company->address,
            "city"           => $company->city,
            "state"          => $company->state,
            "country"        => $company->country,
            "zipcode"        => $company->zipcode,
            "logo_path"      => $company->logo_path,
            "contact_name"   => $company->contact_name,
            "contact_email"  => $company->contact_email,
            "contact_phone"  => $company->contact_phone,
            "timezone"       => $company->timezone,
            "currency"       => $company->currency,
            "business_type"  => $company->business_type ?? 'general',
        ]];
    }

    public function updateGeneralSettings(int $companyId, array $data): array
    {
        $this->validateGeneralSettings($data);

        if ($this->hasErrors()) {
            return ["success" => false, "errors" => $this->getErrors()];
        }

        $company                = new Models_Company($companyId);
        $company->name          = trim($data['name']);
        $company->email         = trim($data['email'] ?? '');
        $company->phone         = trim($data['phone'] ?? '');
        $company->website       = trim($data['website'] ?? '') ?: null;
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
        $company->business_type = trim($data['business_type'] ?? '') ?: 'general';

        if (!empty($data['logo_file']) && is_array($data['logo_file'])) {
            $company->logo_path = $this->saveUploadedFile($companyId, $data['logo_file']);
        }

        if (!$company->update()) {
            throw new Service_Exception("Failed to update general settings");
        }

        return ["success" => true, "data" => []];
    }

    private function validateGeneralSettings(array $data): void
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

        $website = trim($data['website'] ?? '');
        if (!empty($website) && !filter_var($website, FILTER_VALIDATE_URL)) {
            $this->addError(validationErrMsg('invalid', 'Website URL'), 'website');
        }

        if (empty($data['timezone'])) {
            $this->addError(validationErrMsg('required', 'Timezone'), 'timezone');
        }

        if (empty($data['currency'])) {
            $this->addError(validationErrMsg('required', 'Currency'), 'currency');
        }

        $validBusinessTypes = array_column(config('constants.company.business_types'), 'key');
        $businessType = trim($data['business_type'] ?? '');
        if (!empty($businessType) && !in_array($businessType, $validBusinessTypes)) {
            $this->addError(validationErrMsg('invalid', 'Business type'), 'business_type');
        }
    }

    // -------------------------------------------------------------------------

    public function getLegalSettings(int $companyId): array
    {
        $company = new Models_Company($companyId);
        if ($company->isEmpty) {
            throw new Service_Exception("Company not found", 404);
        }

        return ["success" => true, "data" => [
            "legal_name"     => $company->legal_name,
            "gstin"          => $company->gstin,
            "pan"            => $company->pan,
            "tan"            => $company->tan,
            "cin"            => $company->cin,
            "signature_path" => $company->signature_path,
        ]];
    }

    public function updateLegalSettings(int $companyId, array $data): array
    {
        $company             = new Models_Company($companyId);
        $company->legal_name = trim($data['legal_name'] ?? '') ?: null;
        $company->gstin      = trim($data['gstin'] ?? '') ?: null;
        $company->pan        = trim($data['pan'] ?? '') ?: null;
        $company->tan        = trim($data['tan'] ?? '') ?: null;
        $company->cin        = trim($data['cin'] ?? '') ?: null;

        if (!empty($data['signature_file']) && is_array($data['signature_file'])) {
            $company->signature_path = $this->saveUploadedFile($companyId, $data['signature_file']);
        }

        if (!$company->update()) {
            throw new Service_Exception("Failed to update legal settings");
        }

        return ["success" => true, "data" => []];
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

        $country = strtoupper(trim($data['country'] ?? ''));
        if (empty($country)) {
            $this->addError(validationErrMsg('required', 'Country'), 'country');
        } elseif (!array_key_exists($country, getCountries())) {
            $this->addError(validationErrMsg('invalid', 'Country'), 'country');
        }
    }

    private function saveUploadedFile(int $companyId, array $file): string
    {
        $mimeType = $file['mime_type'] ?? '';
        $originalName = $file['name'] ?? 'upload';
        $base64 = $file['content'] ?? '';

        $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($mimeType, $allowedMimes)) {
            throw new Service_Exception("Only image files (JPEG, PNG, GIF, WebP) are allowed", 422);
        }

        $mimeExtMap = ['image/jpeg' => 'jpg', 'image/png' => 'png', 'image/gif' => 'gif', 'image/webp' => 'webp'];
        $ext = $mimeExtMap[$mimeType] ?? 'jpg';

        $year     = date('Y');
        $month    = date('m');
        $filename = uniqid('', true) . '.' . $ext;

        $publicRoot = dirname(__FILE__, 3) . DIRECTORY_SEPARATOR . 'Public';
        $absDir = $publicRoot . DIRECTORY_SEPARATOR . 'uploads'
                . DIRECTORY_SEPARATOR . $companyId
                . DIRECTORY_SEPARATOR . $year
                . DIRECTORY_SEPARATOR . $month;

        if (!is_dir($absDir) && !mkdir($absDir, 0755, true)) {
            throw new Service_Exception("Failed to create upload directory", 500);
        }

        $decoded = base64_decode($base64, true);
        if ($decoded === false || strlen($decoded) === 0) {
            throw new Service_Exception("Invalid file data", 422);
        }
        if (strlen($decoded) > 2 * 1024 * 1024) {
            throw new Service_Exception("File size must not exceed 2MB", 422);
        }

        file_put_contents($absDir . DIRECTORY_SEPARATOR . $filename, $decoded);

        return '/uploads/' . $companyId . '/' . $year . '/' . $month . '/' . $filename;
    }


    // -------------------------------------------------------------------------
    // Static lookup helpers — usable from any tenant-scoped service
    // -------------------------------------------------------------------------

    public static function getDefaultLocationId(int $companyId): ?int {
        $db = Service_TenantDBResolver::resolve($companyId);
        $row = $db->fetchOne(
            "SELECT id FROM company_locations WHERE company_id = ? AND is_default = 1 LIMIT 1",
            [$companyId]
        );
        return $row ? (int) $row->id : null;
    }

    public static function getActiveWarehouses(int $companyId): array {
        $warehouse = new Models_InvWarehouse();
        return $warehouse->getAll([], ["company_id" => $companyId, "status" => ["active"]]);
    }

    public static function getDefaultWarehouseId(int $companyId): ?int {
        $db = Service_TenantDBResolver::resolve($companyId);
        $row = $db->fetchOne(
            "SELECT id FROM inv_warehouses WHERE company_id = ? AND is_default = 1 AND status = 'active' LIMIT 1",
            [$companyId]
        );
        return $row ? (int) $row->id : null;
    }
}
?>
