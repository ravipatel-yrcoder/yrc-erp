<?php
/**
 * Service_DocumentCompute — single, authoritative financial computation path.
 *
 * Two public entry points share one private implementation:
 *   previewCompute() — called by ComputeController, zero DB queries, all data from request
 *   saveCompute()    — called by SO/PO/PI save services, DB-authoritative
 */
class Service_DocumentCompute {

    /**
     * Preview path — zero DB queries. All data (round_off_config, gst_config,
     * gst_component per tax) must be provided in $input by the caller.
     * GST summary is skipped for SO/PO (they show a single Tax line);
     * computed for PI/tax_invoice using client-provided gst_component.
     */
    public static function previewCompute(array $input): array {
        return self::_compute($input, null);
    }

    /**
     * Save path — DB-authoritative. $db is passed to Service_Gst to fetch
     * gst_component from the taxes table. Always runs full GST summary.
     */
    public static function saveCompute(array $input, object $db): array {
        return self::_compute($input, $db);
    }

    private static function _compute(array $input, ?object $db): array {

        $documentType      = (string) ($input['document_type'] ?? 'so');
        $items             = (array) ($input['items'] ?? []);
        $orderDiscount     = (float) ($input['order_discount'] ?? 0);
        $orderDiscountType = (string) ($input['order_discount_type'] ?? 'flat');
        $adjustmentAmt     = (float) ($input['adjustment_amount'] ?? 0);
        $adjustmentLabel   = (string) ($input['adjustment_label'] ?? '');
        $roCfg             = $input['round_off_config'] ?? ['mode' => 'off', 'round_to' => 1, 'method' => 'nearest'];
        $roundOffRequested = (bool) ($input['round_off_requested'] ?? false);
        $gstConfig         = $input['gst_config'] ?? null;

        // Preview path skips GST phase for SO/PO — they show a single Tax line only.
        // Save path always runs full GST (DB-authoritative).
        $skipGst = ($db === null) && in_array($documentType, ['so', 'po'], true);

        // ----------------------------------------------------------------
        // Phase A — Per-item base amounts
        // ----------------------------------------------------------------
        $subtotal          = 0.0;
        $itemDiscountTotal = 0.0;
        $totalBase         = 0.0;

        $computed = [];
        foreach ($items as $item) {
            $qty    = (float) ($item['quantity'] ?? 0);
            $price  = (float) ($item['unit_price'] ?? 0);
            $prodId = (int) ($item['product_id'] ?? 0);

            if ($prodId <= 0 || $qty <= 0) {
                $computed[] = array_merge($item, [
                    'item_discount_amount' => 0.0,
                    'taxable_amount'       => 0.0,
                    'tax_amount'           => 0.0,
                ]);
                continue;
            }

            $itemSubtotal = round($qty * $price, 4);

            $discType  = (string) ($item['item_discount_type'] ?? 'flat');
            $discValue = (float) ($item['item_discount'] ?? 0);

            $itemDiscAmt = $discType === 'percentage'
                ? round($itemSubtotal * $discValue / 100, 4)
                : round($discValue, 4);
            $itemDiscAmt = min($itemDiscAmt, $itemSubtotal);

            $itemBase = round($itemSubtotal - $itemDiscAmt, 4);

            $subtotal          += $itemSubtotal;
            $itemDiscountTotal += $itemDiscAmt;
            $totalBase         += $itemBase;

            $computed[] = array_merge($item, [
                '_item_subtotal'       => $itemSubtotal,
                '_item_base'           => $itemBase,
                'item_discount_amount' => $itemDiscAmt,
            ]);
        }

        // ----------------------------------------------------------------
        // Phase B — Order discount allocation + per-item taxable/tax
        // ----------------------------------------------------------------
        $orderDiscountAmt = $orderDiscountType === 'percentage'
            ? round($totalBase * $orderDiscount / 100, 4)
            : round($orderDiscount, 4);
        $orderDiscountAmt = min($orderDiscountAmt, $totalBase);

        $lastIdx      = count($computed) - 1;
        $allocatedSum = 0.0;
        $taxTotal     = 0.0;

        foreach ($computed as $i => &$c) {
            if (($c['product_id'] ?? 0) <= 0 || ($c['quantity'] ?? 0) <= 0) {
                $c['taxable_amount'] = 0.0;
                $c['tax_amount']     = 0.0;
                continue;
            }

            $itemBase = $c['_item_base'];

            if ($orderDiscountAmt > 0 && $totalBase > 0) {
                if ($i < $lastIdx) {
                    $share         = round($orderDiscountAmt * ($itemBase / $totalBase), 4);
                    $allocatedSum += $share;
                } else {
                    $share = round($orderDiscountAmt - $allocatedSum, 4);
                }
            } else {
                $share = 0.0;
            }

            $taxableAmount = round(max(0, $itemBase - $share), 4);

            $taxInfo = is_array($c['tax_info'] ?? null) ? $c['tax_info'] : [];
            $itemTax = 0.0;
            foreach ($taxInfo as $t) {
                $rate = (float) ($t['rate'] ?? 0);
                $type = (string) ($t['type'] ?? 'percentage');
                if ($type === 'percentage') {
                    $itemTax += $taxableAmount * $rate / 100;
                } else {
                    $itemTax += $rate * (float) ($c['quantity'] ?? 1);
                }
            }
            $itemTax = round($itemTax, 4);
            $taxTotal += $itemTax;

            $c['taxable_amount'] = $taxableAmount;
            $c['tax_amount']     = $itemTax;

            unset($c['_item_subtotal'], $c['_item_base']);
        }
        unset($c);

        $taxableTotal = round($totalBase - $orderDiscountAmt, 4);

        // ----------------------------------------------------------------
        // Phase C — GST summary + authoritative tax_display
        // ----------------------------------------------------------------
        $gstSummary = null;
        $cgstAmount = 0.0;
        $sgstAmount = 0.0;
        $ugstAmount = 0.0;
        $igstAmount = 0.0;
        $cessAmount = 0.0;
        $taxDisplay = 0.0;

        if ($gstConfig !== null && !$skipGst) {
            $gstItems = [];
            foreach ($computed as $c) {
                if (($c['product_id'] ?? 0) <= 0 || ($c['quantity'] ?? 0) <= 0) continue;
                $gstItems[] = [
                    'tax_info'                => is_array($c['tax_info'] ?? null) ? $c['tax_info'] : [],
                    'taxable_amount'          => $c['taxable_amount'],
                    'tax_classification_code' => (string) ($c['tax_classification_code'] ?? ''),
                ];
            }

            $gstSummary = Service_Gst::computeGstSummary(
                $gstItems,
                (string) ($gstConfig['company_gstin']        ?? ''),
                (string) ($gstConfig['company_state']        ?? ''),
                (string) ($gstConfig['place_of_supply_code'] ?? ''),
                (string) ($gstConfig['supply_type']          ?? 'inter_state'),
                (bool)   ($gstConfig['reverse_charge']       ?? false),
                $db
            );

            $gs         = $gstSummary['totals'];
            $cgstAmount = (float) $gs['cgst_amount'];
            $sgstAmount = (float) $gs['sgst_amount'];
            $ugstAmount = (float) $gs['ugst_amount'];
            $igstAmount = (float) $gs['igst_amount'];
            $cessAmount = (float) $gs['cess_amount'];

            $taxDisplay = round($cgstAmount + $sgstAmount + $ugstAmount + $igstAmount + $cessAmount, 4);
        } else {
            $taxDisplay = round($taxTotal, 2);
        }

        // ----------------------------------------------------------------
        // Phase D — Totals
        // ----------------------------------------------------------------
        $preRound = round($taxableTotal + $taxDisplay + $adjustmentAmt, 4);

        $roMode  = (string) ($roCfg['mode']     ?? 'off');
        $roundTo = (float)  ($roCfg['round_to'] ?? 1);
        $method  = (string) ($roCfg['method']   ?? 'nearest');

        $shouldRound = ($roMode === 'auto') || ($roMode === 'manual' && $roundOffRequested);
        $roundOff    = $shouldRound
            ? Service_CompanySettings::computeRoundOff($preRound, $roMode, $roundTo, $method)
            : 0.0;

        $grandTotal = round($preRound + $roundOff, 4);

        $outputItems = [];
        foreach ($computed as $c) {
            unset($c['_item_subtotal'], $c['_item_base']);
            $outputItems[] = $c;
        }

        return [
            'items'                => $outputItems,
            'subtotal'             => round($subtotal, 4),
            'item_discount_total'  => round($itemDiscountTotal, 4),
            'order_discount_amount'=> round($orderDiscountAmt, 4),
            'taxable_total'        => $taxableTotal,
            'tax_display'          => $taxDisplay,
            'gst_summary'          => $gstSummary,
            'cgst_amount'          => $cgstAmount,
            'sgst_amount'          => $sgstAmount,
            'ugst_amount'          => $ugstAmount,
            'igst_amount'          => $igstAmount,
            'cess_amount'          => $cessAmount,
            'adjustment_amount'    => round($adjustmentAmt, 4),
            'adjustment_label'     => $adjustmentLabel,
            'pre_round'            => $preRound,
            'round_off'            => $roundOff,
            'grand_total'          => $grandTotal,
        ];
    }
}
