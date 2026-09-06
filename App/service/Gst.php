<?php
class Service_Gst extends Service_Base {

    private static ?array $gstConfig = null;

    private static function config(): array {
        if (self::$gstConfig === null) {
            self::$gstConfig = require APP_PATH . '/config/indian_gst.php';
        }
        return self::$gstConfig;
    }

    /**
     * Resolve 2-digit GST state code from GSTIN or free-text address state.
     * GSTIN takes priority (government-authoritative). Returns null if unresolvable.
     */
    public static function resolveStateCode(string $gstin, string $addressState): ?string
    {
        $gstin = trim($gstin);
        if (strlen($gstin) === 15) {
            $code = substr($gstin, 0, 2);
            if (isset(self::config()['states'][$code])) {
                return $code;
            }
        }
        $s = strtolower(trim($addressState));
        if ($s === '') return null;
        $abbrevs = self::config()['state_abbreviations'];
        if (isset($abbrevs[$s])) return $abbrevs[$s];
        foreach (self::config()['states'] as $code => $data) {
            if (strtolower($data['name']) === $s) return $code;
        }
        return null;
    }

    /**
     * Called at document create() time.
     * Resolves Place of Supply, supply type, and customer GSTIN snapshot.
     * Returns array to store as snapshot fields on the document header.
     *
     * PoS cascade (Indian GST law): billing address GSTIN → customer GSTIN
     *   → billing address state → null (IGST fallback).
     * GSTIN prefix is legally authoritative over address state text.
     */
    public static function resolveForDocument(
        string $billingAddressGstin,
        string $customerGstin,
        string $billingAddressState,
        string $companyGstin,
        string $companyAddressState,
        string $customerGstTreatment = 'b2b'
    ): array {
        $customerCode = self::resolveStateCode($billingAddressGstin, '')
                     ?? self::resolveStateCode($customerGstin, '')
                     ?? self::resolveStateCode('', $billingAddressState);
        $companyCode  = self::resolveStateCode($companyGstin, $companyAddressState);

        $posCode    = $customerCode ?? '';
        $posName    = $customerCode ? (self::config()['states'][$customerCode]['name'] ?? '') : '';
        $supplyType = self::_determineSupplyType($companyCode, $customerCode, $customerGstTreatment);

        return [
            'place_of_supply_code'    => $posCode,
            'place_of_supply_name'    => $posName,
            'supply_type'             => $supplyType,
            'customer_gstin_snapshot' => $customerGstin,
        ];
    }

    /**
     * Compute full GST summary from line items + document header fields.
     *
     * $db is nullable:
     *   - null  = preview path: gst_component is read from tax_info[].gst_component (client-provided, no DB query)
     *   - object = save path:   gst_component is fetched from the taxes table (authoritative)
     */
    public static function computeGstSummary(
        array   $items,
        string  $companyGstin,
        string  $companyAddressState,
        string  $posCode,
        string  $supplyType,
        bool    $reverseCharge,
        ?object $db
    ): array {
        $companyCode        = self::resolveStateCode($companyGstin, $companyAddressState);
        $customerCode       = $posCode !== '' ? $posCode : null;
        $stateNotDetermined = ($companyCode === null || $customerCode === null);

        $isIntra = ($supplyType === 'intra_state');
        $useUgst = false;
        if ($isIntra && $customerCode !== null) {
            $stateInfo = self::config()['states'][$customerCode] ?? [];
            $useUgst   = !empty($stateInfo['is_ut']) && empty($stateInfo['ut_legislature']);
        }

        // Build gst_component map — from DB (save path) or from client data (preview path)
        $taxComponentMap = [];
        if ($db !== null) {
            $taxIds = [];
            foreach ($items as $item) {
                foreach (($item['tax_info'] ?? []) as $t) {
                    if (!empty($t['id'])) $taxIds[] = (int) $t['id'];
                }
            }
            if (!empty($taxIds)) {
                $unique = array_unique($taxIds);
                $ph     = implode(',', array_fill(0, count($unique), '?'));
                $rows   = $db->fetchAll("SELECT id, gst_component FROM taxes WHERE id IN ($ph)", array_values($unique));
                foreach ($rows as $row) {
                    $taxComponentMap[$row->id] = $row->gst_component;
                }
            }
        }

        // Group items by HSN + gst_rate + cess_rate, accumulate taxable_amount
        $groups = [];
        foreach ($items as $item) {
            $gstTax   = null;
            $cessRate = 0.0;
            foreach (($item['tax_info'] ?? []) as $t) {
                // Save path: use DB-fetched map. Preview path: use client-provided gst_component field.
                $comp = ($db !== null)
                    ? ($taxComponentMap[$t['id'] ?? 0] ?? 'none')
                    : (string) ($t['gst_component'] ?? 'none');
                if ($comp === 'gst' && $gstTax === null) $gstTax = $t;
                if ($comp === 'cess') $cessRate += (float) ($t['rate'] ?? 0);
            }
            if ($gstTax === null && $cessRate == 0) continue;

            $hsnCode = $item['tax_classification_code'] ?? '';
            $gstRate = $gstTax ? (float) ($gstTax['rate'] ?? 0) : 0.0;
            $key     = $hsnCode . '__' . $gstRate . '__' . $cessRate;
            if (!isset($groups[$key])) {
                $groups[$key] = [
                    'hsn_code'       => $hsnCode,
                    'gst_rate'       => $gstRate,
                    'cess_rate'      => $cessRate,
                    'taxable_amount' => 0.0,
                ];
            }
            $groups[$key]['taxable_amount'] += (float) ($item['taxable_amount'] ?? 0);
        }

        // Compute GST component amounts per group
        $rows        = [];
        $rawGstTotal = 0.0;
        $rawCessTotal= 0.0;
        $totals = [
            'taxable_amount' => 0.0,
            'cgst_amount'    => 0.0,
            'sgst_amount'    => 0.0,
            'ugst_amount'    => 0.0,
            'igst_amount'    => 0.0,
            'cess_amount'    => 0.0,
        ];

        foreach ($groups as $g) {
            $taxable  = round($g['taxable_amount'], 4);
            $gstRate  = $g['gst_rate'];
            $cessRate = $g['cess_rate'];

            if ($isIntra) {
                $half        = $gstRate / 2;
                $cgst_amount = round($taxable * $half / 100, 2);
                $sgst_amount = $useUgst ? 0.0 : round($taxable * $half / 100, 2);
                $ugst_amount = $useUgst ? round($taxable * $half / 100, 2) : 0.0;
                $igst_amount = 0.0;
            } else {
                $cgst_amount = 0.0;
                $sgst_amount = 0.0;
                $ugst_amount = 0.0;
                $igst_amount = round($taxable * $gstRate / 100, 2);
            }
            $cess_amount = round($taxable * $cessRate / 100, 2);

            $rows[] = [
                'hsn_code'       => $g['hsn_code'],
                'taxable_amount' => $taxable,
                'gst_rate'       => $gstRate,
                'cgst_rate'      => $isIntra ? $gstRate / 2 : 0,
                'cgst_amount'    => $cgst_amount,
                'sgst_rate'      => ($isIntra && !$useUgst) ? $gstRate / 2 : 0,
                'sgst_amount'    => $sgst_amount,
                'ugst_rate'      => ($isIntra && $useUgst) ? $gstRate / 2 : 0,
                'ugst_amount'    => $ugst_amount,
                'igst_rate'      => !$isIntra ? $gstRate : 0,
                'igst_amount'    => $igst_amount,
                'cess_rate'      => $cessRate,
                'cess_amount'    => $cess_amount,
            ];

            $totals['taxable_amount'] += $taxable;
            $rawGstTotal              += $taxable * $gstRate / 100;
            $rawCessTotal             += $taxable * $cessRate / 100;
        }

        // Apply global display rounding to totals (standard: round aggregate, not sum of rounded rows).
        $totalCess = round($rawCessTotal, 2);
        if ($isIntra) {
            $cgstGlobal = round($rawGstTotal / 2, 2);
            $totals['cgst_amount'] = $cgstGlobal;
            $totals['sgst_amount'] = $useUgst ? 0.0 : $cgstGlobal;
            $totals['ugst_amount'] = $useUgst ? $cgstGlobal : 0.0;
            $totals['igst_amount'] = 0.0;
        } else {
            $totals['cgst_amount'] = 0.0;
            $totals['sgst_amount'] = 0.0;
            $totals['ugst_amount'] = 0.0;
            $totals['igst_amount'] = round($rawGstTotal, 2);
        }
        $totals['cess_amount']    = $totalCess;
        $totals['taxable_amount'] = round($totals['taxable_amount'], 2);

        return [
            'is_intra_state'       => $isIntra,
            'use_ugst'             => $useUgst,
            'supply_type'          => $supplyType,
            'state_not_determined' => $stateNotDetermined,
            'is_reverse_charge'    => $reverseCharge,
            'has_cess'             => $totals['cess_amount'] > 0,
            'company_state_code'   => $companyCode,
            'customer_state_code'  => $customerCode,
            'rows'                 => $rows,
            'totals'               => $totals,
        ];
    }

    private static function _determineSupplyType(
        ?string $companyCode,
        ?string $customerCode,
        string  $gstTreatment
    ): string {
        if (in_array($gstTreatment, ['sez', 'deemed_export'], true)) return 'sez_supply';
        if ($gstTreatment === 'export') return 'export_supply';
        if ($companyCode === null || $customerCode === null) return 'inter_state';
        return $companyCode === $customerCode ? 'intra_state' : 'inter_state';
    }
}
