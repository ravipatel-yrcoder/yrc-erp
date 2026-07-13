<?php
class Service_EmailConfig extends Service_Base {

    private const PLATFORM_FROM_EMAIL = 'notifications@zentraqone.com';
    private const VALID_DOC_TYPES     = ['purchase_order', 'rfq', 'sales_order', 'quotation'];

    // fetchOne returns stdClass — cast to array for consistent key access
    private function fetchRow(string $sql, array $bindings = []): array
    {
        $row = $this->db->fetchOne($sql, $bindings);
        return $row ? (array)$row : [];
    }

    // Returns SMTP settings for display in the settings UI (password masked, never raw)
    public function getSmtpSettingsForDisplay(): array
    {
        $row = $this->fetchRow(
            "SELECT smtp_host, smtp_port, smtp_encryption, smtp_username,
                    from_name, from_email, reply_to,
                    CASE WHEN smtp_password IS NOT NULL AND smtp_password != '' THEN 1 ELSE 0 END AS has_password
             FROM company_email_settings WHERE company_id = ?",
            [$this->context->companyId]
        );

        return [
            'smtp_host'       => $row['smtp_host']       ?? '',
            'smtp_port'       => $row['smtp_port']       ?? 587,
            'smtp_encryption' => $row['smtp_encryption'] ?? 'tls',
            'smtp_username'   => $row['smtp_username']   ?? '',
            'from_name'       => $row['from_name']       ?? '',
            'from_email'      => $row['from_email']      ?? '',
            'reply_to'        => $row['reply_to']        ?? '',
            'smtp_password'   => !empty($row['has_password']) ? '••••••••' : '',
        ];
    }

    // Returns the global From string for test emails
    public function getGlobalFrom(): string
    {
        $row       = $this->fetchRow(
            "SELECT from_name, from_email FROM company_email_settings WHERE company_id = ?",
            [$this->context->companyId]
        );
        $fromEmail = $row['from_email'] ?? self::PLATFORM_FROM_EMAIL;
        $fromName  = $row['from_name']  ?? 'Zentraq';
        return "{$fromName}<{$fromEmail}>";
    }

    // Returns SMTP config array — company row if configured, else empty (Mailer uses env fallback)
    public function getSMTPConfig(): array
    {
        $row = $this->fetchRow(
            "SELECT * FROM company_email_settings WHERE company_id = ?",
            [$this->context->companyId]
        );

        if (!empty($row['smtp_host'])) {
            return [
                'host'       => $row['smtp_host'],
                'port'       => (int) $row['smtp_port'],
                'encryption' => $row['smtp_encryption'],
                'username'   => $row['smtp_username'] ?? '',
                'password'   => !empty($row['smtp_password'])
                                    ? self::decryptPassword($row['smtp_password'])
                                    : '',
            ];
        }

        return [];
    }

    // Returns doc config row as array or empty array
    public function getDocConfig(string $documentType): array
    {
        return $this->fetchRow(
            "SELECT * FROM company_email_doc_config WHERE company_id = ? AND document_type = ?",
            [$this->context->companyId, $documentType]
        );
    }

    // Resolves from_email + from_name based on from_mode + fallback chain
    public function resolveFrom(array $docConfig, int $userId): array
    {
        $mode = $docConfig['from_mode'] ?? null;

        if ($mode === 'user') {
            $user = new Models_User($userId);
            return [
                'email' => !$user->isEmpty ? $user->email : self::PLATFORM_FROM_EMAIL,
                'name'  => !$user->isEmpty ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '',
            ];
        }

        $globalRow = $this->fetchRow(
            "SELECT from_email, from_name FROM company_email_settings WHERE company_id = ?",
            [$this->context->companyId]
        );

        $email = (!empty($docConfig['from_email']) && $mode === 'fixed')
                    ? $docConfig['from_email']
                    : ($globalRow['from_email'] ?? null);

        $name  = (!empty($docConfig['from_name']) && $mode === 'fixed')
                    ? $docConfig['from_name']
                    : ($globalRow['from_name'] ?? null);

        if (empty($email)) {
            $email = self::PLATFORM_FROM_EMAIL;
        }

        if (empty($name)) {
            $company = $this->fetchRow("SELECT name FROM companies WHERE id = ?", [$this->context->companyId]);
            $name    = $company['name'] ?? '';
        }

        return ['email' => $email, 'name' => $name];
    }

    // Substitutes {placeholder} tokens in a subject template string
    public function resolveSubject(string $template, array $data): string
    {
        $tokens = [
            '{company_name}'    => $data['company_name']    ?? '',
            '{company_address}' => $data['company_address'] ?? '',
            '{user_name}'       => $data['user_name']       ?? '',
            '{user_email}'      => $data['user_email']      ?? '',
            '{user_mobile}'     => $data['user_mobile']     ?? '',
            '{po_number}'       => $data['po_number']       ?? '',
            '{so_number}'       => $data['so_number']       ?? '',
            '{vendor_name}'     => $data['vendor_name']     ?? '',
            '{customer_name}'   => $data['customer_name']   ?? '',
            '{order_date}'      => $data['order_date']      ?? '',
        ];

        return str_replace(array_keys($tokens), array_values($tokens), $template);
    }

    // Returns pre-filled email defaults for a document type + document ID
    public function getEmailDefaults(string $documentType, int $documentId): array
    {
        $docConfig = $this->getDocConfig($documentType);
        $globalRow = $this->fetchRow(
            "SELECT reply_to FROM company_email_settings WHERE company_id = ?",
            [$this->context->companyId]
        );
        $resolved  = $this->resolveFrom($docConfig, $this->context->userId);

        $tokenData = $this->buildTokenData($documentType, $documentId);

        $subject = '';
        if (!empty($docConfig['email_subject'])) {
            $subject = $this->resolveSubject($docConfig['email_subject'], $tokenData);
        }

        $body = '';
        if (!empty($docConfig['email_body'])) {
            $body = $this->resolveSubject($docConfig['email_body'], $tokenData);
        }

        return [
            'from_email' => $resolved['email'],
            'from_name'  => $resolved['name'],
            'subject'    => $subject,
            'body'       => $body,
            'cc'         => $docConfig['email_cc']  ?? '',
            'bcc'        => $docConfig['email_bcc'] ?? '',
            'reply_to'   => $globalRow['reply_to']  ?? '',
        ];
    }

    public function saveSmtpSettings(array $inputs): array
    {
        if (!$this->context->canDo('company_settings', 'write')) {
            throw new Service_Exception("You do not have permission to update email settings", 403);
        }

        $password = trim($inputs['smtp_password'] ?? '');
        if ($password === '' || $password === '••••••••') {
            $password = null;
        } elseif (!empty($password)) {
            $password = self::encryptPassword($password);
        }

        $existing = $this->fetchRow(
            "SELECT id FROM company_email_settings WHERE company_id = ?",
            [$this->context->companyId]
        );

        $data = [
            'smtp_host'       => trim($inputs['smtp_host'] ?? '') ?: null,
            'smtp_port'       => !empty($inputs['smtp_port']) ? (int)$inputs['smtp_port'] : 587,
            'smtp_encryption' => in_array($inputs['smtp_encryption'] ?? '', ['tls','ssl','none'])
                                    ? $inputs['smtp_encryption'] : 'tls',
            'smtp_username'   => trim($inputs['smtp_username'] ?? '') ?: null,
            'from_name'       => trim($inputs['from_name']     ?? '') ?: null,
            'from_email'      => trim($inputs['from_email']    ?? '') ?: null,
            'reply_to'        => trim($inputs['reply_to']      ?? '') ?: null,
        ];

        if ($password !== null) {
            $data['smtp_password'] = $password;
        }

        $data['updated_by'] = $this->context->userId;

        if (!empty($existing['id'])) {
            $this->db->update('company_email_settings', $data, "id = " . (int)$existing['id']);
        } else {
            $data['company_id'] = $this->context->companyId;
            $this->db->insert('company_email_settings', $data);
        }

        return ['success' => true];
    }

    public function saveDocConfig(string $documentType, array $inputs): array
    {
        if (!$this->context->canDo('company_settings', 'write')) {
            throw new Service_Exception("You do not have permission to update email settings", 403);
        }

        if (!in_array($documentType, self::VALID_DOC_TYPES)) {
            throw new Service_Exception("Invalid document type", 422);
        }

        $fromMode = $inputs['from_mode'] ?? null;
        if ($fromMode === '' || !in_array($fromMode, ['user', 'fixed', null])) {
            $fromMode = null;
        }

        $data = [
            'from_mode'     => $fromMode,
            'from_name'     => trim($inputs['from_name']     ?? '') ?: null,
            'from_email'    => trim($inputs['from_email']    ?? '') ?: null,
            'email_subject' => trim($inputs['email_subject'] ?? '') ?: null,
            'email_body'    => !empty($inputs['email_body'])  ? $inputs['email_body']  : null,
            'email_cc'      => trim($inputs['email_cc']      ?? '') ?: null,
            'email_bcc'     => trim($inputs['email_bcc']     ?? '') ?: null,
        ];

        if (isset($inputs['pdf_template'])) {
            $data['pdf_template'] = trim($inputs['pdf_template']) ?: null;
        }

        $existing = $this->fetchRow(
            "SELECT id FROM company_email_doc_config WHERE company_id = ? AND document_type = ?",
            [$this->context->companyId, $documentType]
        );

        $data['updated_by'] = $this->context->userId;

        if (!empty($existing['id'])) {
            $this->db->update('company_email_doc_config', $data, "id = " . (int)$existing['id']);
        } else {
            $data['company_id']    = $this->context->companyId;
            $data['document_type'] = $documentType;
            $this->db->insert('company_email_doc_config', $data);
        }

        return ['success' => true];
    }

    public static function encryptPassword(string $password): string
    {
        $key = env('MAIL_ENCRYPT_KEY', '');
        $iv  = env('MAIL_ENCRYPT_IV',  '');

        if (empty($key) || empty($iv)) {
            return $password;
        }

        return openssl_encrypt($password, 'AES-256-CBC', $key, 0, $iv);
    }

    public static function decryptPassword(string $encrypted): string
    {
        $key = env('MAIL_ENCRYPT_KEY', '');
        $iv  = env('MAIL_ENCRYPT_IV',  '');

        if (empty($key) || empty($iv)) {
            return $encrypted;
        }

        $decrypted = openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
        return $decrypted !== false ? $decrypted : '';
    }

    // Returns pdf_template for a document type — new table first, legacy KV fallback
    public function getPdfTemplate(string $documentType, Service_CompanySettings $settingsSvc): string
    {
        $legacyKeyMap = [
            'sales_order'    => 'so_pdf_template',
            'purchase_order' => 'po_pdf_template',
            'rfq'            => 'rfq_pdf_template',
        ];

        $row = $this->getDocConfig($documentType);
        if (!empty($row['pdf_template'])) {
            return $row['pdf_template'];
        }

        $legacyKey = $legacyKeyMap[$documentType] ?? null;
        if ($legacyKey) {
            return $settingsSvc->get($legacyKey, 'template_1');
        }

        return 'template_1';
    }

    /**
     * Seed default email doc config for a newly provisioned company.
     * Uses INSERT IGNORE — safe to call multiple times, never overwrites user edits.
     */
    public static function seedDefaults(int $companyId, $db): void
    {
        $defaults = [
            'purchase_order' => [
                'email_subject' => 'Purchase Order #{po_number} from {company_name}',
                'email_body'    => 'Dear {vendor_name},<br><br>Please find attached our Purchase Order <strong>#{po_number}</strong> for your reference.<br><br>Kindly confirm receipt and advise the expected delivery date at your earliest convenience.<br><br>If you have any questions, please do not hesitate to contact us.<br><br>Regards,<br>{company_name}',
            ],
            'rfq' => [
                'email_subject' => 'Request for Quotation #{po_number} from {company_name}',
                'email_body'    => 'Dear {vendor_name},<br><br>We would like to request a quotation for the items listed in the attached document <strong>#{po_number}</strong>.<br><br>Please review the requirements and provide your best pricing, availability, and expected delivery timeline at your earliest convenience.<br><br>If you have any questions, please do not hesitate to contact us.<br><br>Regards,<br>{company_name}',
            ],
            'sales_order' => [
                'email_subject' => '{so_number} — {company_name}',
                'email_body'    => 'Dear {customer_name},<br><br>Please find your sales order <strong>#{so_number}</strong> enclosed.<br><br>If you have any questions, please do not hesitate to contact us.<br><br>Regards,<br>{company_name}',
            ],
            'quotation' => [
                'email_subject' => 'Quotation #{so_number} from {company_name}',
                'email_body'    => 'Dear {customer_name},<br><br>Please find your quotation <strong>#{so_number}</strong> enclosed.<br><br>If you have any questions, please do not hesitate to contact us.<br><br>Regards,<br>{company_name}',
            ],
        ];

        foreach ($defaults as $docType => $values) {
            $db->query(
                "INSERT IGNORE INTO company_email_doc_config (company_id, document_type, pdf_template, email_subject, email_body)
                 VALUES (?, ?, 'template_1', ?, ?)",
                [$companyId, $docType, $values['email_subject'], $values['email_body']]
            );
        }
    }

    private function buildTokenData(string $documentType, int $documentId): array
    {
        $companyId = $this->context->companyId;
        $data      = [];

        $company              = $this->fetchRow("SELECT name, address, city, state, country, zipcode FROM companies WHERE id = ?", [$companyId]);
        $data['company_name'] = $company['name'] ?? '';

        $addrParts    = [];
        if (!empty($company['address'])) $addrParts[] = nl2br($company['address']);
        $cityZip      = trim(($company['city'] ?? '') . ' - ' . ($company['zipcode'] ?? ''), ' -');
        if ($cityZip !== '') $addrParts[] = $cityZip;
        $stateCountry = trim(($company['state'] ?? '') . ', ' . ($company['country'] ?? ''), ', ');
        if ($stateCountry !== '') $addrParts[] = $stateCountry;
        $data['company_address'] = implode('<br>', $addrParts);

        $user              = new Models_User($this->context->userId);
        $data['user_name']   = !$user->isEmpty ? trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')) : '';
        $data['user_email']  = !$user->isEmpty ? ($user->email  ?? '') : '';
        $data['user_mobile'] = !$user->isEmpty ? ($user->phone  ?? '') : '';

        if (in_array($documentType, ['purchase_order', 'rfq']) && $documentId > 0) {
            $po = $this->fetchRow(
                "SELECT po.po_number, po.order_date, v.display_name AS vendor_name
                 FROM purchase_orders po LEFT JOIN vendors v ON v.id = po.vendor_id
                 WHERE po.id = ? AND po.company_id = ?",
                [$documentId, $companyId]
            );
            $data['po_number']   = $po['po_number']   ?? '';
            $data['vendor_name'] = $po['vendor_name'] ?? '';
            $data['order_date']  = $po['order_date']  ?? '';
        } elseif (in_array($documentType, ['sales_order', 'quotation']) && $documentId > 0) {
            $so = $this->fetchRow(
                "SELECT so.so_number, so.order_date, c.display_name AS customer_name
                 FROM sales_orders so LEFT JOIN customers c ON c.id = so.customer_id
                 WHERE so.id = ? AND so.company_id = ?",
                [$documentId, $companyId]
            );
            $data['so_number']     = $so['so_number']     ?? '';
            $data['customer_name'] = $so['customer_name'] ?? '';
            $data['order_date']    = $so['order_date']    ?? '';
        }

        return $data;
    }
}
?>
