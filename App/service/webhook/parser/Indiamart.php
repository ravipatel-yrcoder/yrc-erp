<?php

/**
 * Service_Webhook_Parser_Indiamart
 *
 * Parses a raw IndiaMart webhook payload into a standardised array
 * ready for Service_Crm_Lead::create() plus a meta array for history.
 *
 * Expected payload structure:
 * {
 *   "CODE": 200,
 *   "STATUS": "SUCCESS",
 *   "RESPONSE": {
 *     "UNIQUE_QUERY_ID": "...",
 *     "SENDER_NAME": "...",
 *     "SENDER_COMPANY": "...",
 *     "SENDER_EMAIL": "...",
 *     "SENDER_MOBILE": "...",
 *     "SENDER_PHONE": "...",
 *     "SENDER_ADDRESS": "...",
 *     "SENDER_CITY": "...",
 *     "SENDER_STATE": "...",
 *     "SENDER_PINCODE": "...",
 *     "SENDER_COUNTRY_ISO": "...",
 *     "QUERY_TYPE": "...",
 *     "QUERY_PRODUCT_NAME": "...",
 *     "QUERY_MCAT_NAME": "...",
 *     "SUBJECT": "...",
 *     "QUERY_MESSAGE": "...",
 *     "QUERY_TIME": "...",
 *     "CALL_DURATION": "...",
 *     "RECEIVER_MOBILE": "..."
 *   }
 * }
 */
class Service_Webhook_Parser_Indiamart
{
    /**
     * QUERY_TYPE code → human-readable lead_type
     */
    private const QUERY_TYPE_MAP = [
        'W' => 'Direct Enquiry',
        'B' => 'Buy Lead',
        'P' => 'PNS Call',
        'BIZ' => 'Catalog View',
        'WA' => 'WhatsApp Enquiry',
    ];


    /**
     * Parse raw payload string.
     *
     * @param  string $rawPayload  The raw_payload value from webhook_logs
     * @return array{lead: array, meta: array, external_id: string|null}
     * @throws \TinyPHP_Exception  on invalid/unacceptable payload
     */
    public static function parse(string $rawPayload): array
    {
        // ── Decode ───────────────────────────────────────────────────────────
        $data = json_decode($rawPayload, true);

        if (!is_array($data)) {
            throw new TinyPHP_Exception("IndiaMart parser: invalid JSON payload");
        }

        if (($data['STATUS'] ?? '') !== 'SUCCESS') {
            throw new TinyPHP_Exception("IndiaMart parser: payload STATUS is not SUCCESS");
        }

        $r = $data['RESPONSE'] ?? null;

        if (!is_array($r)) {
            throw new TinyPHP_Exception("IndiaMart parser: RESPONSE key missing or invalid");
        }


        // ── Helper closures ──────────────────────────────────────────────────
        $str = fn(string $key): string => trim((string)($r[$key] ?? ''));


        // ── Name splitting ───────────────────────────────────────────────────
        $fullName = $str('SENDER_NAME');
        $parts = preg_split('/\s+/', $fullName);
        $firstName = $parts[0] ?? null;
        $lastName = count($parts) > 1 ? implode(' ', array_slice($parts, 1)) : null;

        // ── Phone ────────────────────────────────────────────────────────────
        $phoneRaw = $str('SENDER_MOBILE') ?: $str('SENDER_PHONE');
        $phone = $phoneRaw ? normalizeIndianPhone($phoneRaw) : null;   
        
        
        // ── Email ────────────────────────────────────────────────────────────
        $emailRaw = $str('SENDER_EMAIL');
        $email = filter_var($emailRaw, FILTER_VALIDATE_EMAIL) ?: null;


        // ── Lead type ────────────────────────────────────────────────────────
        $queryType = strtoupper($str('QUERY_TYPE'));
        $leadType  = self::QUERY_TYPE_MAP[$queryType] ?? null;


        // ── Product interest ─────────────────────────────────────────────────
        $productName = $str('QUERY_PRODUCT_NAME');
        $productInterest = $productName ? json_encode([['product_name' => $productName, 'product_id' => null, 'source' => 'indiamart']]) : null;


        // ── Notes ────────────────────────────────────────────────────────────
        $notes = self::buildNotes($r);


        // ── Lead payload ─────────────────────────────────────────────────────
        $lead = [
            'first_name' => $firstName ?: null,
            'last_name' => $lastName,
            'display_name' => $fullName ?: null,
            'company_name' => $str('SENDER_COMPANY') ?: null,
            'email' => $email,
            'phone' => $phone,
            'address_line1' => $str('SENDER_ADDRESS') ?: null,
            'city' => $str('SENDER_CITY') ?: null,
            'state' => $str('SENDER_STATE') ?: null,
            'postal_code' => $str('SENDER_PINCODE') ?: null,
            'country' => $str('SENDER_COUNTRY_ISO') ?: 'IN',
            'source' => 'indiamart',
            'lead_type' => $leadType,
            'external_id' => $str('UNIQUE_QUERY_ID') ?: null,
            'product_interest' => $productInterest,
            'notes' => $notes,
            'priority' => 'medium',
        ];
                

        // ── History meta ─────────────────────────────────────────────────────
        $meta = [
            'call_duration' => $str('CALL_DURATION') ?: null,
            'receiver_mobile' => $str('RECEIVER_MOBILE') ?: null,
            'query_time' => $str('QUERY_TIME') ?: null,            
        ];

        return [
            'lead' => $lead,
            'meta' => $meta,
            'external_id' => $str('UNIQUE_QUERY_ID') ?: null,
        ];
    }


    /**
     * Build structured notes from the RESPONSE fields.
     *
     * Format:
     *   Category: Glycerin Soap Base
     *
     *   Requirement for 98% Natural Soap Base, 25 kg...
     *
     * Subject is prepended only if QUERY_MESSAGE does not already start with it.
     */
    private static function buildNotes(array $r): ?string
    {
        $str = fn(string $key): string => trim((string)($r[$key] ?? ''));

        $parts = [];
        $category = $str('QUERY_MCAT_NAME');
        $subject = $str('SUBJECT');
        $message = $str('QUERY_MESSAGE');

        if ($category !== '') {
            $parts[] = "Category: {$category}";
            $parts[] = '';  // blank line
        }

        if ($subject !== '' && $message !== '' && !str_starts_with($message, $subject)) {
            $parts[] = "Subject: {$subject}";
            $parts[] = '';  // blank line
        }

        if ($message !== '') {
            $parts[] = $message;
        } elseif ($subject !== '') {
            $parts[] = $subject;
        }

        $notes = implode("\n", $parts);

        return $notes !== '' ? $notes : null;
    }
}
?>