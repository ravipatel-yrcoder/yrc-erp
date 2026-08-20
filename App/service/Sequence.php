<?php
class Service_Sequence extends Service_Base {

    private function getKnownSequences() {
        return [
            'sales_orders'         => ['label' => 'Sales Order',            'default_pattern' => 'SO-',  'default_padding' => 7],
            'sales_deliveries'     => ['label' => 'Delivery Note',          'default_pattern' => 'DN-',  'default_padding' => 7],
            'sales_returns'        => ['label' => 'Sales Return',           'default_pattern' => 'RET-',  'default_padding' => 7],
            'sales_proforma_invoices' => ['label' => 'Proforma Invoice',   'default_pattern' => 'PRF-{YYYY}-', 'default_padding' => 7],
            'purchase_inquiry'     => ['label' => 'Purchase Inquiry',        'default_pattern' => 'PIN-', 'default_padding' => 7],
            'purchase_orders'      => ['label' => 'Purchase Order',         'default_pattern' => 'PO-',  'default_padding' => 7],
            'purchase_order_grns'  => ['label' => 'Purchase Receipt (GRN)', 'default_pattern' => 'PR-',  'default_padding' => 7],
            'manufacturing_orders' => ['label' => 'Manufacturing Order',    'default_pattern' => 'MO-',  'default_padding' => 7],
            'crm_leads'            => ['label' => 'CRM Lead',               'default_pattern' => 'LD-',  'default_padding' => 7],
            'customers'            => ['label' => 'Customer',               'default_pattern' => 'CN-',  'default_padding' => 7],
            'vendors'              => ['label' => 'Vendor',                 'default_pattern' => 'VN-',  'default_padding' => 7],
        ];
    }

    private function getDocTypeToSequenceKey(): array
    {
        return [
            'quotation'        => 'sales_orders',
            'sales_order'      => 'sales_orders',
            'proforma_invoice' => 'sales_proforma_invoices',
            'purchase_order'   => 'purchase_orders',
            'purchase_inquiry' => 'purchase_inquiry',
        ];
    }

    public function getOneForSettings(string $docType): array
    {
        $map  = $this->getDocTypeToSequenceKey();
        $key  = $map[$docType] ?? null;
        if (!$key) return [];

        $known = $this->getKnownSequences();
        if (!isset($known[$key])) return [];

        $meta = $known[$key];
        $row  = $this->db->fetchOne(
            "SELECT * FROM sequences WHERE company_id = ? AND sequence_key = ?",
            [$this->context->companyId, $key]
        );

        return [
            'sequence_key' => $key,
            'label'        => $meta['label'],
            'pattern'      => $row ? $row->pattern        : $meta['default_pattern'],
            'padding'      => $row ? (int) $row->padding  : $meta['default_padding'],
            'reset_period' => $row ? $row->reset_period   : 'none',
            'last_number'  => $row ? (int) $row->last_number : 0,
        ];
    }

    public function saveOne(string $docType, array $data): array
    {
        $map = $this->getDocTypeToSequenceKey();
        $key = $map[$docType] ?? null;
        if (!$key) {
            return ['success' => false, 'errors' => ['sequence_key' => 'Unknown document type.']];
        }
        $data['sequence_key'] = $key;
        return $this->saveSettings([$data]);
    }

    public function getAllForSettings() {
        $companyId = $this->context->companyId;
        $known     = $this->getKnownSequences();
        $keys      = array_keys($known);
        $placeholders = implode(',', array_fill(0, count($keys), '?'));

        $rows = $this->db->fetchAll(
            "SELECT * FROM sequences WHERE company_id = ? AND sequence_key IN ($placeholders)",
            array_merge([$companyId], $keys)
        );

        $indexed = [];
        foreach ($rows as $row) {
            $indexed[$row->sequence_key] = $row;
        }

        $result = [];
        foreach ($known as $key => $meta) {
            $row = $indexed[$key] ?? null;
            $result[] = [
                'sequence_key' => $key,
                'label'        => $meta['label'],
                'pattern'      => $row ? $row->pattern        : $meta['default_pattern'],
                'padding'      => $row ? (int) $row->padding  : $meta['default_padding'],
                'reset_period' => $row ? $row->reset_period   : 'none',
                'last_number'  => $row ? (int) $row->last_number : 0,
            ];
        }

        return $result;
    }

    public function saveSettings(array $updates) {
        $known          = $this->getKnownSequences();
        $companyId      = $this->context->companyId;
        $validPaddings  = [4, 5, 6, 7, 8, 9];
        $validPeriods   = ['none', 'monthly', 'yearly'];
        $allowedTokens  = ['{YY}', '{YYYY}', '{MM}'];

        $errors = [];
        foreach ($updates as $i => $item) {
            $key = $item['sequence_key'] ?? '';
            if (!isset($known[$key])) {
                $errors["sequences.$i.sequence_key"] = 'Invalid sequence key.';
                continue;
            }

            $pattern = trim($item['pattern'] ?? '');
            if (strlen($pattern) > 20) {
                $errors["sequences.$i.pattern"] = 'Pattern must be 20 characters or fewer.';
            }
            $stripped = str_replace($allowedTokens, '', $pattern);
            if (preg_match('/\{[^}]+\}/', $stripped)) {
                $errors["sequences.$i.pattern"] = 'Pattern contains unsupported tokens. Only {YY}, {YYYY}, {MM} are allowed.';
            }

            $padding = (int) ($item['padding'] ?? 0);
            if (!in_array($padding, $validPaddings)) {
                $errors["sequences.$i.padding"] = 'Number width must be between 4 and 9.';
            }

            $resetPeriod = $item['reset_period'] ?? 'none';
            if (!in_array($resetPeriod, $validPeriods)) {
                $errors["sequences.$i.reset_period"] = 'Invalid reset period.';
            }
            // Pattern must include the period token so reset numbers stay unique
            $label = $known[$key]['label'];
            if ($resetPeriod === 'yearly' && !preg_match('/\{YYYY\}|\{YY\}/', $pattern)) {
                $errors["sequences.$i.reset_period"] = "$label: Yearly reset requires {YYYY} or {YY} in the pattern to keep numbers unique across years.";
            }
            if ($resetPeriod === 'monthly' && (!preg_match('/\{YYYY\}|\{YY\}/', $pattern) || !str_contains($pattern, '{MM}'))) {
                $errors["sequences.$i.reset_period"] = "$label: Monthly reset requires {YYYY} (or {YY}) and {MM} in the pattern to keep numbers unique across months.";
            }
        }

        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        $db = $this->db;
        $db->startTransaction();

        try {
            foreach ($updates as $item) {
                $key         = $item['sequence_key'];
                $pattern     = trim($item['pattern']);
                $padding     = (int) $item['padding'];
                $resetPeriod = $item['reset_period'] ?? 'none';
                $now         = date('Y-m-d H:i:s');

                $existing = $db->fetchOne(
                    "SELECT id FROM sequences WHERE company_id = ? AND sequence_key = ?",
                    [$companyId, $key]
                );

                if ($existing) {
                    $db->update('sequences', [
                        'pattern'      => $pattern,
                        'padding'      => $padding,
                        'reset_period' => $resetPeriod,
                        'updated_at'   => $now,
                    ], "id = {$existing->id}");
                } else {
                    $seq               = new Models_Sequence();
                    $seq->company_id   = $companyId;
                    $seq->sequence_key = $key;
                    $seq->pattern      = $pattern;
                    $seq->padding      = $padding;
                    $seq->reset_period = $resetPeriod;
                    $seq->create();
                }
            }

            $db->commit();
            return ['success' => true];

        } catch (Exception $e) {
            $db->rollback();
            throw $e;
        }
    }


    public function nextPreview($sequenceKey) {
        return $this->next($this->context->companyId, $sequenceKey, false);
    }


    public function nextCommit($sequenceKey) {

        //global $db;

        $db = $this->db;

        $db->startTransaction();

        try {

            $sequenceNumber = $this->next($this->context->companyId, $sequenceKey, true);

            $db->commit();

            return $sequenceNumber;

        } catch (Exception $e) {

            $db->rollback();
            throw $e;
        }
    }

    private function next($companyId, $sequenceKey, $commit) {

        $db = $this->db;

        try {

            $pattern = $this->lockAndFetchPattern($companyId, $sequenceKey, $commit);

            if( !$pattern ) {
                throw new Exception("Sequence pattern configuration is missing");
            }

            $pattern->sequence_key = $sequenceKey;

            $currentYear  = (int) date('Y');
            $currentMonth = (int) date('n');

            $lastNumber = (int) $pattern->last_number;

            // Detect period rollover and reset counter when a new period has started
            if ($pattern->reset_period !== 'none' && $pattern->last_reset_year !== null) {
                $lastYear  = (int) $pattern->last_reset_year;
                $lastMonth = (int) $pattern->last_reset_month;

                $yearRolled  = $currentYear > $lastYear;
                $monthRolled = $yearRolled || ($currentMonth > $lastMonth);

                if ($pattern->reset_period === 'yearly'  && $yearRolled)  $lastNumber = 0;
                if ($pattern->reset_period === 'monthly' && $monthRolled) $lastNumber = 0;
            }

            [$number, $counter] = $this->getNextAvailableNumber($lastNumber, $pattern);

            // Save updated last_number and current period tracking
            if( $commit === true )
            {
                if( $number ) {
                    $db->update("sequences", [
                        "last_number"      => $counter,
                        "last_reset_year"  => $currentYear,
                        "last_reset_month" => $currentMonth,
                    ], "id=$pattern->id");
                }
            }

            return $number;

        } catch (Exception $e) {
            throw $e;
        }

    }


    private function lockAndFetchPattern($companyId, $sequenceKey, $commit) {

        $db = $this->db;

        //$sequenceKey = "test";

        // Try product-specific first
        $sql = "SELECT * FROM sequences WHERE company_id = ? AND lower(sequence_key) = ? AND is_active = ?";
        
        // only lock for commit
        if( $commit === true ) {
            $sql .=" FOR UPDATE";
        }
        
        $pattern = $db->fetchOne($sql, [$companyId, strtolower($sequenceKey), 1]);

        if( $pattern ) {
            return $pattern;
        }

        // create default pattern and return it
        $sequence = new Models_Sequence();
        $sequence->company_id = $companyId;
        $sequence->sequence_key = $sequenceKey;
        $sequence->padding = 7;
        $knownDefaults = [
            'purchase_inquiry'     => 'PIN-',
            'purchase_orders'      => 'PO-',
            'purchase_order_grns'  => 'PR-',
            'sales_orders'         => 'SO-',
            'sales_deliveries'     => 'DN-',
            'vendors'              => 'VN-',
            'customers'            => 'CN-',
            'crm_leads'            => 'LD-',
            'manufacturing_orders' => 'MO-',
            'sales_returns'        => 'RET-',
            'sales_proforma_invoices' => 'PRF-{YYYY}-',
        ];
        if (isset($knownDefaults[$sequenceKey])) {
            $sequence->pattern = $knownDefaults[$sequenceKey];
        }

        $id = $sequence->create();
        if( $id ) {

            $pattern = new stdClass();

            $pattern->id = $id;
            $pattern->company_id = $sequence->company_id;
            $pattern->sequence_key = $sequence->sequence_key;
            $pattern->pattern = $sequence->pattern;
            $pattern->padding = $sequence->padding;
            $pattern->last_number = $sequence->last_number;
            $pattern->reset_period = $sequence->reset_period;
            $pattern->is_active = $sequence->is_active;
            $pattern->created_at = $sequence->created_at;
            $pattern->updated_at = $sequence->updated_at;

            return $pattern;
        }

        return null;
    }



    private function getNextAvailableNumber($lastNumber, $pattern) {

        $counter = $lastNumber;
        while (true) {

            $counter++;

            $number = $this->applyPattern($pattern, $counter);
            if (!$this->sequenceExists($pattern->company_id, $number, $pattern->sequence_key)) {
                return [$number, $counter];
            }
        }
    }




    /**
     * Apply pattern formatting and append padded counter
     */
    private function applyPattern($pattern, $counter)
    {
        $formatted = (String) $pattern->pattern;
        $formatted = str_replace("{YY}", date("y"), $formatted);
        $formatted = str_replace("{YYYY}", date("Y"), $formatted);
        $formatted = str_replace("{MM}", date("m"), $formatted);

        $padding = $pattern->padding ?: 6;


        return $formatted . str_pad($counter, $padding, "0", STR_PAD_LEFT);
    }


    private function sequenceExists($companyId, $number, $sequenceKey) {

        $db = $this->db;
        
        if( $sequenceKey === "vendors" ) {

            $sql = "SELECT id FROM vendors WHERE company_id = ? AND vendor_code = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "purchase_orders" ) {

            $sql = "SELECT id FROM purchase_orders WHERE company_id = ? AND po_number = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "sales_orders" ) {

            $sql = "SELECT id FROM sales_orders WHERE company_id = ? AND so_number = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "sales_deliveries" ) {

            $sql = "SELECT id FROM sales_deliveries WHERE company_id = ? AND dn_number = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "customers" ) {

            $sql = "SELECT id FROM customers WHERE company_id = ? AND customer_code = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "crm_leads" ) {

            $sql = "SELECT id FROM crm_leads WHERE company_id = ? AND lead_code = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "manufacturing_orders" ) {

            $sql = "SELECT id FROM manufacturing_orders WHERE company_id = ? AND mo_number = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "sales_returns" ) {

            $sql = "SELECT id FROM `returns` WHERE company_id = ? AND return_number = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "purchase_inquiry" ) {

            $sql = "SELECT id FROM purchase_inquiries WHERE company_id = ? AND inquiry_number = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }
        else if( $sequenceKey === "sales_proforma_invoices" ) {

            $sql = "SELECT id FROM sales_proforma_invoices WHERE company_id = ? AND proforma_number = ? LIMIT 1";
            return (bool) $db->fetchCol($sql, [$companyId, $number]);

        }

        return false;
    }


    /**
     * Advance last_number in sequences to reflect a manually entered document number.
     * Strips the resolved pattern prefix from $customNumber, parses the numeric counter,
     * and updates last_number if that counter exceeds the stored value.
     * Numbers that do not match the pattern prefix are silently skipped.
     * Must be called from within an open transaction so the update is atomic with the document INSERT.
     */
    public function advanceCounter(string $sequenceKey, string $customNumber): void
    {
        $companyId = $this->context->companyId;
        $db = $this->db;

        $pattern = $db->fetchOne(
            "SELECT * FROM sequences WHERE company_id = ? AND lower(sequence_key) = ? AND is_active = 1 LIMIT 1",
            [$companyId, strtolower($sequenceKey)]
        );

        if (!$pattern) return;

        $resolvedPrefix = str_replace(['{YY}', '{YYYY}', '{MM}'], [date('y'), date('Y'), date('m')], (string) $pattern->pattern);
        $prefixLen = strlen($resolvedPrefix);

        if ($prefixLen > 0) {
            if (strpos($customNumber, $resolvedPrefix) !== 0) return;
            $numericPart = substr($customNumber, $prefixLen);
        } else {
            $numericPart = $customNumber;
        }

        if (!ctype_digit($numericPart)) return;

        $counter = (int) $numericPart;
        if ($counter > (int) $pattern->last_number) {
            $db->update('sequences', ['last_number' => $counter], "id = {$pattern->id}");
        }
    }

}
?>