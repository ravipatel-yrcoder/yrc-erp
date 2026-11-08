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
        $charges           = (array) ($input['charges']          ?? []);
        $chargeTaxRates    = (array) ($input['charge_tax_rates'] ?? []); // preview: {tax_id => rate}

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
        // Phase E — Extra Charges
        // Charges run before round-off so the full total (items + charges + taxes) is rounded.
        // ----------------------------------------------------------------
        $isRcm           = !empty($gstConfig['reverse_charge']);
        $chargesSubtotal = 0.0;
        $chargesTaxTotal = 0.0;
        $computedCharges = [];

        foreach ($charges as $charge) {
            $chargeAmount = (float) ($charge['amount'] ?? 0);
            if ($chargeAmount <= 0) continue;

            $chargeTaxId  = isset($charge['tax_id']) && (int) $charge['tax_id'] > 0
                            ? (int) $charge['tax_id'] : null;
            $chargeLabel  = (string) ($charge['label'] ?? '');
            $chargeTypeId = isset($charge['charge_type_id']) && (int) $charge['charge_type_id'] > 0
                            ? (int) $charge['charge_type_id'] : null;

            $taxRate         = 0.0;
            $isGstTax        = false;
            $chargeTaxAmount = 0.0;
            $chargeCgst = $chargeSgst = $chargeUgst = $chargeIgst = $chargeCess = 0.0;

            if ($chargeTaxId !== null) {
                if ($db === null) {
                    // Preview path — rate from client-provided map; treat as GST-type
                    $taxRate  = isset($chargeTaxRates[$chargeTaxId]) ? (float) $chargeTaxRates[$chargeTaxId] : 0.0;
                    $isGstTax = $taxRate > 0;
                } else {
                    // Save path — rate from DB; check gst_component for split
                    $taxRow  = $db->fetchOne("SELECT rate, gst_component FROM taxes WHERE id = ? LIMIT 1", [$chargeTaxId]);
                    $taxRate  = $taxRow ? (float) $taxRow->rate : 0.0;
                    $isGstTax = $taxRow && $taxRow->gst_component === 'gst';
                }
                if ($taxRate > 0) {
                    $chargeTaxAmount = round($chargeAmount * $taxRate / 100, 4);
                }
            }

            // GST split — when GST summary is running (save path full GST, or PI preview)
            if ($isGstTax && $taxRate > 0 && !$skipGst && $gstSummary !== null) {
                $isIntraState = (bool) $gstSummary['is_intra_state'];
                $useUgst      = (bool) $gstSummary['use_ugst'];

                if ($isIntraState) {
                    $half       = $taxRate / 2;
                    $chargeCgst = round($chargeAmount * $half / 100, 2);
                    $chargeSgst = $useUgst ? 0.0 : round($chargeAmount * $half / 100, 2);
                    $chargeUgst = $useUgst ? round($chargeAmount * $half / 100, 2) : 0.0;
                } else {
                    $chargeIgst = round($chargeAmount * $taxRate / 100, 2);
                }

                // Merge into an existing row with the same GST rate, or add a new row
                $mergedRow = false;
                foreach ($gstSummary['rows'] as &$row) {
                    if (abs((float) $row['gst_rate'] - $taxRate) < 0.001) {
                        $row['taxable_amount'] += $chargeAmount;
                        $row['cgst_amount']    += $chargeCgst;
                        $row['sgst_amount']    += $chargeSgst;
                        $row['ugst_amount']    += $chargeUgst;
                        $row['igst_amount']    += $chargeIgst;
                        $row['cess_amount']    += $chargeCess;
                        $mergedRow = true;
                        break;
                    }
                }
                unset($row);

                if (!$mergedRow) {
                    $gstSummary['rows'][] = [
                        'hsn_code'       => (string) ($charge['sac_code'] ?? ''),
                        'taxable_amount' => $chargeAmount,
                        'gst_rate'       => $taxRate,
                        'cgst_rate'      => $isIntraState ? $taxRate / 2 : 0,
                        'cgst_amount'    => $chargeCgst,
                        'sgst_rate'      => ($isIntraState && !$useUgst) ? $taxRate / 2 : 0,
                        'sgst_amount'    => $chargeSgst,
                        'ugst_rate'      => ($isIntraState && $useUgst) ? $taxRate / 2 : 0,
                        'ugst_amount'    => $chargeUgst,
                        'igst_rate'      => !$isIntraState ? $taxRate : 0,
                        'igst_amount'    => $chargeIgst,
                        'cess_rate'      => 0,
                        'cess_amount'    => $chargeCess,
                    ];
                }

                $gstSummary['totals']['taxable_amount'] = round((float) $gstSummary['totals']['taxable_amount'] + $chargeAmount, 2);
                $gstSummary['totals']['cgst_amount']    = round((float) $gstSummary['totals']['cgst_amount']    + $chargeCgst,  2);
                $gstSummary['totals']['sgst_amount']    = round((float) $gstSummary['totals']['sgst_amount']    + $chargeSgst,  2);
                $gstSummary['totals']['ugst_amount']    = round((float) $gstSummary['totals']['ugst_amount']    + $chargeUgst,  2);
                $gstSummary['totals']['igst_amount']    = round((float) $gstSummary['totals']['igst_amount']    + $chargeIgst,  2);
                $gstSummary['totals']['cess_amount']    = round((float) $gstSummary['totals']['cess_amount']    + $chargeCess,  2);

                // Propagate back to Phase C totals so return values stay consistent
                $cgstAmount += $chargeCgst;
                $sgstAmount += $chargeSgst;
                $ugstAmount += $chargeUgst;
                $igstAmount += $chargeIgst;
                $cessAmount += $chargeCess;
                $taxDisplay  = round($cgstAmount + $sgstAmount + $ugstAmount + $igstAmount + $cessAmount, 4);
            }

            $chargesSubtotal += $chargeAmount;
            $chargesTaxTotal += $chargeTaxAmount;

            // Build tax breakdown for the caller (save service assembles full tax_info JSON from this)
            $taxBreakdown = null;
            if ($chargeTaxId !== null && $taxRate > 0) {
                $isIntra = $gstSummary !== null ? (bool) $gstSummary['is_intra_state'] : false;
                $ugst    = $gstSummary !== null ? (bool) $gstSummary['use_ugst']       : false;
                $taxBreakdown = [
                    'tax_id'      => $chargeTaxId,
                    'rate'        => $taxRate,
                    'cgst_rate'   => $isIntra ? $taxRate / 2 : 0,
                    'cgst_amount' => round($chargeCgst, 4),
                    'sgst_rate'   => ($isIntra && !$ugst) ? $taxRate / 2 : 0,
                    'sgst_amount' => round($chargeSgst, 4),
                    'ugst_rate'   => ($isIntra && $ugst)  ? $taxRate / 2 : 0,
                    'ugst_amount' => round($chargeUgst, 4),
                    'igst_rate'   => !$isIntra ? $taxRate : 0,
                    'igst_amount' => round($chargeIgst, 4),
                    'cess_rate'   => 0,
                    'cess_amount' => round($chargeCess, 4),
                ];
            }

            $computedCharges[] = [
                'charge_type_id' => $chargeTypeId,
                'label'          => $chargeLabel,
                'amount'         => round($chargeAmount, 4),
                'taxable_amount' => round($chargeAmount, 4), // = amount; no discount on charges
                'tax_id'         => $chargeTaxId,
                'tax_amount'     => round($chargeTaxAmount, 4),
                'tax_breakdown'  => $taxBreakdown,           // null when no tax or no GST split
            ];
        }

        // For SO/PO preview (skipGst=true): add charge taxes to the single Tax display line
        if ($skipGst && $chargesTaxTotal > 0) {
            $taxDisplay = round($taxDisplay + $chargesTaxTotal, 4);
        }

        // ----------------------------------------------------------------
        // Phase D — Totals
        // ----------------------------------------------------------------
        // Round-off is applied to the full total (items + charges + all taxes).
        // $taxDisplay includes charge taxes when: (a) preview path added them explicitly above,
        // or (b) save path with gst_summary merged them via Phase E line 294.
        // When gstConfig was not provided (PO/SO save path), $gstSummary is null and
        // Phase E's GST block is skipped — $taxDisplay has item taxes only, so add charges explicitly.
        $taxForPreRound = ($skipGst || $gstSummary !== null)
            ? $taxDisplay
            : round($taxDisplay + $chargesTaxTotal, 4);

        $preRound = $isRcm
            ? round($taxableTotal + $chargesSubtotal + $adjustmentAmt, 4)
            : round($taxableTotal + $taxForPreRound + $chargesSubtotal + $adjustmentAmt, 4);

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
            'charges'              => $computedCharges,
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
            'charges_subtotal'     => round($chargesSubtotal, 4),
            'charges_tax_total'    => round($chargesTaxTotal, 4),
            'grand_total'          => $grandTotal,
        ];
    }
}
