<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<?php include APP_PATH . '/resources/views/pdf/_shared/styles-t1.php'; ?>
</head>
<body>

<?php
    $pf       = $printData;
    $company  = $pf['company'];
    $billing  = $pf['billing_address']  ?? [];
    $shipping = $pf['shipping_address'] ?? [];
    $items    = $pf['items'];
    $settings = $pf['settings'] ?? [];
    $isRcm    = !empty($pf['reverse_charge']);
    $dateFormat = config('sys_default.dateFormat', 'd/m/Y');

    $fmtDate = function($d) use ($dateFormat) {
        return $d ? date($dateFormat, strtotime($d)) : '-';
    };
    $fmtCurr = function($v) {
        return '&#8377;' . number_format((float)$v, 2);
    };
    $e = function($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    };

    $companyDisplayName = !empty($company['legal_name']) ? $company['legal_name'] : ($company['name'] ?? '');
    $logoPath           = !empty($company['logo_path'])       ? Helpers_Pdf::assetPath($company['logo_path'])       : null;
    $sigPath            = !empty($company['signature_path'])  ? Helpers_Pdf::assetPath($company['signature_path'])  : null;
    $cityZip      = trim(($company['city'] ?? '') . ' - ' . ($company['zipcode'] ?? ''), ' -');
    $stateCountry = trim(($company['state'] ?? '') . ', ' . ($company['country'] ?? ''), ', ');

    $addressFields = ['address_line1', 'address_line2'];
    $billingValues = [];
    foreach ($billing as $key => $val) {
        if (in_array($key, $addressFields) && !empty($val)) $billingValues[] = $val;
    }
    $shippingValues = [];
    foreach ($shipping as $key => $val) {
        if (in_array($key, $addressFields) && !empty($val)) $shippingValues[] = $val;
    }
    $fmtLoc = function($addr) {
        $cityZip = trim(($addr['city'] ?? '') . ' - ' . ($addr['postal_code'] ?? ''), ' -');
        return implode(', ', array_filter([$cityZip, $addr['state'] ?? '', $addr['country'] ?? '']));
    };
?>

<!-- 1. Header -->
<div class="doc-header">
    <div class="t2-header-left">
        <div class="doc-header-logo">
            <?php if ($logoPath && file_exists($logoPath)): ?>
                <img src="<?= $e($logoPath) ?>" alt="Logo">
            <?php endif; ?>
        </div>
        <span class="company-name"><?= $e($companyDisplayName) ?></span>
        <div class="company-meta">
            <?php if (!empty($company['address'])): ?><?= nl2br($e($company['address'])) ?><br><?php endif; ?>
            <?php $companyLocLine = implode(', ', array_filter([$cityZip, $stateCountry])); ?>
            <?php if ($companyLocLine): ?><?= $e($companyLocLine) ?><br><?php endif; ?>
            <?php if (!empty($company['gstin'])): ?>GSTIN: <?= $e($company['gstin']) ?><br><?php endif; ?>
        </div>
    </div>
    <div class="t2-header-right">
        <div class="doc-title">Proforma Invoice</div>
        <div style="font-size:7pt;color:#9ca3af;font-style:italic;margin-bottom:4px;">Not a Tax Invoice — Cannot be used for GST input credit</div>
        <div><span class="meta-label">Number:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($pf['proforma_number']) ?></span></div>
        <div><span class="meta-label">Date:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($pf['proforma_date']) ?></span></div>
        <?php if (!empty($pf['valid_until'])): ?>
        <div><span class="meta-label">Valid Until:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($pf['valid_until']) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($pf['payment_terms'])): ?>
        <div><span class="meta-label">Payment Terms:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($pf['payment_terms']) ?></span></div>
        <?php endif; ?>
        <div><span class="meta-label">Sales Order:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($pf['so_number'] ?? '') ?></span></div>
        <?php if (!empty($pf['place_of_supply_name'])): ?>
        <div><span class="meta-label">Place of Supply:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($pf['place_of_supply_name'] . ' (' . $pf['place_of_supply_code'] . ')') ?></span></div>
        <?php endif; ?>
        <?php if (!empty($pf['reverse_charge'])): ?>
        <div><span class="meta-label">Reverse Charge:</span>&nbsp;&nbsp;<span class="meta-val" style="color:#b45309;">Yes</span></div>
        <?php endif; ?>
    </div>
    <div style="clear:both;"></div>
</div>

<!-- 2. Address boxes -->
<table class="doc-info-table t2-address-block" cellspacing="4" cellpadding="4">
    <thead style="padding-bottom: 25px;margin-bottom: 25px;">
        <tr>
            <th align="left" class="border-bottom" style="width: 45%;">Bill To</th>
            <th style="width: 10%;">&nbsp;</th>
            <th align="left" class="border-bottom" style="width: 45%;">Ship To</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="width: 45%;padding-top: 5px;">
                <div class="info-col-name" style="font-weight:bold;"><?= $e(!empty($billing['attention']) ? $billing['attention'] : ($pf['customer_name'] ?? '')) ?></div>
                <?php if ($billingValues): ?><?= $e(implode(', ', $billingValues)) ?><br><?php endif; ?>
                <?php $billLoc = $fmtLoc($billing); if ($billLoc): ?><div><?= $e($billLoc) ?></div><?php endif; ?>
                <?php if (!empty($pf['customer_gstin_snapshot'])): ?><div>GSTIN: <?= $e($pf['customer_gstin_snapshot']) ?></div><?php endif; ?>
                <?php if (!empty($billing['phone'])): ?><div><?= $e($billing['phone']) ?></div><?php endif; ?>
            </td>
            <td style="width: 10%;padding-top: 5px;">&nbsp;</td>
            <td style="width: 45%;padding-top: 5px;">
                <?php if (!empty($shippingValues)): ?>
                    <div class="info-col-name"><?= $e(!empty($shipping['attention']) ? $shipping['attention'] : ($pf['customer_name'] ?? '')) ?></div>
                    <?php if ($shippingValues): ?><?= $e(implode(', ', $shippingValues)) ?><br><?php endif; ?>
                    <?php $shipLoc = $fmtLoc($shipping); if ($shipLoc): ?><div><?= $e($shipLoc) ?></div><?php endif; ?>
                    <?php if (!empty($shipping['phone'])): ?><div><?= $e($shipping['phone']) ?></div><?php endif; ?>
                <?php else: ?>
                    <span style="font-size:7.5pt;color:#9ca3af;font-style:italic;">Same as billing</span>
                <?php endif; ?>
            </td>
        </tr>
    </tbody>
</table>

<!-- 3. Line items -->
<table class="items-table">
    <thead>
        <tr>
            <th style="width:3%">#</th>
            <th style="width:28%">Item</th>
            <th style="width:12%;white-space:nowrap;">HSN/SAC</th>
            <th class="text-right" style="width:7%">Qty</th>
            <th class="text-right" style="width:12%">Unit Price</th>
            <th class="text-right" style="width:11%">Discount</th>
            <th class="text-right" style="width:10%">Tax</th>
            <th class="text-right" style="width:13%">Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $item): ?>
        <?php
            $discAmt     = (float)($item['discount_amount'] ?? 0);
            $discDisplay = $discAmt > 0 ? $fmtCurr($discAmt) : '&mdash;';
            $taxInfoArr  = is_array($item['tax_info']) ? $item['tax_info'] : [];
            $taxLabel    = implode(', ', array_filter(array_column($taxInfoArr, 'name'))) ?: '—';
            $hsnCode     = $item['tax_classification_code'] ?? '';
        ?>
        <tr class="<?= ($i % 2 !== 0) ? 'even-row' : '' ?>">
            <td><?= $i + 1 ?></td>
            <td>
                <div class="item-product"><?= $e($item['product_name'] ?? '') ?></div>
                <?php if (!empty($item['description'])): ?><div class="item-desc"><?= $e($item['description']) ?></div><?php endif; ?>
            </td>
            <td style="white-space:nowrap;"><?= $e($hsnCode ?: '—') ?></td>
            <td class="text-right">
                <?= $e(formatQty($item['quantity'])) ?>
                <?php if (!empty($item['uom_code'])): ?><span style="font-size:7pt;font-weight:600;"> <?= $e($item['uom_code']) ?></span><?php endif; ?>
            </td>
            <td class="text-right"><?= $fmtCurr($item['unit_price']) ?></td>
            <td class="text-right"><?= $discDisplay ?></td>
            <td class="text-right"><?= $e($taxLabel) ?></td>
            <td class="text-right"><?= $fmtCurr($isRcm ? $item['taxable_amount'] : $item['line_total']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php $emptyRows = max(0, 10 - count($items)); for ($p = 0; $p < $emptyRows; $p++): ?>
        <tr class="<?= ((count($items) + $p) % 2 !== 0) ? 'even-row' : '' ?>"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <?php endfor; ?>
    </tbody>
</table>

<!-- 4. Bottom section: notes left, totals right -->
<div class="bottom-section">
    <div class="totals-col">
        <table class="totals-table">
            <tr>
                <td class="totals-label">Subtotal</td>
                <td align="right"><?= $fmtCurr($pf['subtotal']) ?></td>
            </tr>
            <?php if ($pf['item_discount_total'] > 0): ?>
            <tr>
                <td class="totals-label">Item Discounts</td>
                <td align="right">- <?= $fmtCurr($pf['item_discount_total']) ?></td>
            </tr>
            <tr>
                <td class="totals-label">Subtotal After Discount</td>
                <td align="right"><?= $fmtCurr($pf['subtotal_after_item_discount']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($pf['order_discount_amount'] > 0): ?>
            <tr>
                <td class="totals-label">Order Discount</td>
                <td align="right">- <?= $fmtCurr($pf['order_discount_amount']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($pf['gst_summary']) && !empty($pf['gst_summary']['rows'])): ?>
            <?php $gs = $pf['gst_summary']; $rcmStyle = $isRcm ? 'color:#6b7280;' : ''; ?>
            <?php if ($gs['is_intra_state']): ?>
                <?php if ($gs['totals']['cgst_amount'] > 0): ?>
                <tr><td class="totals-label" style="<?= $rcmStyle ?>">CGST</td><td align="right" style="<?= $rcmStyle ?>"><?= $fmtCurr($gs['totals']['cgst_amount']) ?></td></tr>
                <?php endif; ?>
                <?php if ($gs['use_ugst'] && $gs['totals']['ugst_amount'] > 0): ?>
                <tr><td class="totals-label" style="<?= $rcmStyle ?>">UGST</td><td align="right" style="<?= $rcmStyle ?>"><?= $fmtCurr($gs['totals']['ugst_amount']) ?></td></tr>
                <?php elseif (!$gs['use_ugst'] && $gs['totals']['sgst_amount'] > 0): ?>
                <tr><td class="totals-label" style="<?= $rcmStyle ?>">SGST</td><td align="right" style="<?= $rcmStyle ?>"><?= $fmtCurr($gs['totals']['sgst_amount']) ?></td></tr>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($gs['totals']['igst_amount'] > 0): ?>
                <tr><td class="totals-label" style="<?= $rcmStyle ?>">IGST</td><td align="right" style="<?= $rcmStyle ?>"><?= $fmtCurr($gs['totals']['igst_amount']) ?></td></tr>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($gs['totals']['cess_amount']) && $gs['totals']['cess_amount'] > 0): ?>
            <tr><td class="totals-label">CESS</td><td align="right"><?= $fmtCurr($gs['totals']['cess_amount']) ?></td></tr>
            <?php endif; ?>
            <?php else: ?>
            <tr>
                <td class="totals-label">Tax</td>
                <td align="right"><?= $fmtCurr($pf['tax_amount']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($pf['round_off_amount']) && (float)$pf['round_off_amount'] !== 0.0): ?>
            <?php $roundOff = (float)$pf['round_off_amount']; ?>
            <tr>
                <td class="totals-label">Round Off</td>
                <td align="right" style="<?= $roundOff < 0 ? 'color:#c0392b;' : 'color:#27ae60;' ?>">
                    <?= ($roundOff < 0 ? '&minus; ' : '+ ') . $fmtCurr(abs($roundOff)) ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($pf['adjustment_label']) && (float)$pf['adjustment_amount'] != 0): ?>
            <tr>
                <td class="totals-label"><?= $e($pf['adjustment_label']) ?></td>
                <td align="right"><?= $fmtCurr($pf['adjustment_amount']) ?></td>
            </tr>
            <?php endif; ?>
            <?php
                $taxAmt     = (float)($pf['tax_amount'] ?? 0);
                $amtPayable = (float)$pf['grand_total'];
            ?>
            <tr class="grand-total-row">
                <td>Total</td>
                <td align="right"><?= $fmtCurr($amtPayable) ?></td>
            </tr>
            <?php if ($isRcm && $taxAmt > 0): ?>
            <tr>
                <td class="totals-label" style="color:#6b7280;font-size:7pt;">GST payable under RCM (by recipient)</td>
                <td align="right" style="color:#6b7280;font-size:7pt;"><?= $fmtCurr($taxAmt) ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <div class="notes-col">

        <!-- Bank Details -->
        <?php if (!empty($settings['show_bank_details']) && !Helpers_Html::isEmpty($settings['bank_details'] ?? '')): ?>
        <div>
            <div class="terms-label">Bank Details</div>
            <div class="terms-body"><?= $settings['bank_details'] ?></div>
        </div>
        <?php endif; ?>

        <!-- Amount in Words -->
        <?php if (!empty($settings['show_amount_in_words'])): ?>
        <div style="font-size:7pt;color:#374151;margin-top:8px;">
            <strong>Amount in Words:</strong>&nbsp;<?= $e(Helpers_Pdf::amountToWordsINR($amtPayable)) ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($pf['notes'])): ?>
        <div class="notes-section">
            <div class="notes-label"><b>Notes:</b></div>
            <div class="notes-body"><?= $e($pf['notes']) ?></div>
        </div>
        <?php endif; ?>

    </div>

    <div style="clear:both;"></div>
</div>

<!-- GST Summary Table -->
<?php if (!empty($pf['gst_summary']) && !empty($pf['gst_summary']['rows'])): ?>
<?php $gs = $pf['gst_summary']; ?>
<div class="terms-section">
    <div class="terms-label">GST Summary</div>
    <table style="width:100%;font-size:7pt;border-collapse:collapse;" cellspacing="0">
        <thead>
            <tr style="background:#f8f8f8;">
                <th style="text-align:left;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">HSN/SAC</th>
                <th style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">Taxable Value</th>
                <?php if ($gs['is_intra_state']): ?>
                <th style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">CGST %</th>
                <th style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">CGST Amt</th>
                <?php if ($gs['use_ugst']): ?>
                <th style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">UGST %</th>
                <th style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">UGST Amt</th>
                <?php else: ?>
                <th style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">SGST %</th>
                <th style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">SGST Amt</th>
                <?php endif; ?>
                <?php else: ?>
                <th style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">IGST %</th>
                <th style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">IGST Amt</th>
                <?php endif; ?>
                <?php if ($gs['has_cess']): ?>
                <th style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">CESS %</th>
                <th style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">CESS Amt</th>
                <?php endif; ?>
                <th style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #e5e7eb;">Total Tax</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($gs['rows'] as $row): ?>
            <?php $rowTax = $row['cgst_amount'] + $row['sgst_amount'] + $row['ugst_amount'] + $row['igst_amount'] + $row['cess_amount']; ?>
            <tr>
                <td style="padding:2pt 3pt;border-bottom:1px solid #f3f4f6;"><?= $e($row['hsn_code'] ?: '—') ?></td>
                <td style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #f3f4f6;"><?= $fmtCurr($row['taxable_amount']) ?></td>
                <?php if ($gs['is_intra_state']): ?>
                <td style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #f3f4f6;"><?= $e($row['cgst_rate']) ?>%</td>
                <td style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #f3f4f6;"><?= $fmtCurr($row['cgst_amount']) ?></td>
                <?php if ($gs['use_ugst']): ?>
                <td style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #f3f4f6;"><?= $e($row['ugst_rate']) ?>%</td>
                <td style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #f3f4f6;"><?= $fmtCurr($row['ugst_amount']) ?></td>
                <?php else: ?>
                <td style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #f3f4f6;"><?= $e($row['sgst_rate']) ?>%</td>
                <td style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #f3f4f6;"><?= $fmtCurr($row['sgst_amount']) ?></td>
                <?php endif; ?>
                <?php else: ?>
                <td style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #f3f4f6;"><?= $e($row['igst_rate']) ?>%</td>
                <td style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #f3f4f6;"><?= $fmtCurr($row['igst_amount']) ?></td>
                <?php endif; ?>
                <?php if ($gs['has_cess']): ?>
                <td style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #f3f4f6;"><?= $e($row['cess_rate']) ?>%</td>
                <td style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #f3f4f6;"><?= $fmtCurr($row['cess_amount']) ?></td>
                <?php endif; ?>
                <td style="text-align:right;padding:2pt 3pt;border-bottom:1px solid #f3f4f6;font-weight:bold;"><?= $fmtCurr($rowTax) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
        <tfoot>
            <tr style="background:#f8f8f8;font-weight:bold;">
                <td style="padding:2pt 3pt;">Total</td>
                <td style="text-align:right;padding:2pt 3pt;"><?= $fmtCurr($gs['totals']['taxable_amount']) ?></td>
                <?php if ($gs['is_intra_state']): ?>
                <td style="text-align:right;padding:2pt 3pt;">&nbsp;</td>
                <td style="text-align:right;padding:2pt 3pt;"><?= $fmtCurr($gs['totals']['cgst_amount']) ?></td>
                <td style="text-align:right;padding:2pt 3pt;">&nbsp;</td>
                <td style="text-align:right;padding:2pt 3pt;"><?= $fmtCurr($gs['use_ugst'] ? $gs['totals']['ugst_amount'] : $gs['totals']['sgst_amount']) ?></td>
                <?php else: ?>
                <td style="text-align:right;padding:2pt 3pt;">&nbsp;</td>
                <td style="text-align:right;padding:2pt 3pt;"><?= $fmtCurr($gs['totals']['igst_amount']) ?></td>
                <?php endif; ?>
                <?php if ($gs['has_cess']): ?>
                <td style="text-align:right;padding:2pt 3pt;">&nbsp;</td>
                <td style="text-align:right;padding:2pt 3pt;"><?= $fmtCurr($gs['totals']['cess_amount']) ?></td>
                <?php endif; ?>
                <td style="text-align:right;padding:2pt 3pt;"><?= $fmtCurr($pf['tax_amount']) ?></td>
            </tr>
        </tfoot>
    </table>
    <?php if (!empty($gs['state_not_determined'])): ?>
    <div style="font-size:7pt;color:#dc2626;margin-top:4pt;">* Place of Supply could not be determined — shown as IGST. Please verify.</div>
    <?php endif; ?>
    <?php if (!empty($pf['reverse_charge'])): ?>
    <div style="font-size:7pt;color:#b45309;margin-top:4pt;">* GST amounts under Reverse Charge — payable by recipient directly to government.</div>
    <?php endif; ?>
</div>
<?php endif; ?>

<?php if (!empty($pf['supply_type']) && in_array($pf['supply_type'], ['export_supply', 'sez_supply'])): ?>
<div class="terms-section" style="padding:4pt 0;">
    <div style="font-size:7pt;color:#1d4ed8;">
        <?php if ($pf['supply_type'] === 'export_supply'): ?>
        Zero-rated supply — Supply made without payment of IGST under Bond/LUT [Section 16, IGST Act, 2017].
        <?php else: ?>
        Zero-rated supply to SEZ unit/developer — Supply made without payment of IGST under Bond/LUT [Section 16, IGST Act, 2017].
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- Terms, Declaration, Signature — two-column layout -->
<?php
$_hasTerms = !Helpers_Html::isEmpty($pf['invoice_terms'] ?? '');
$_hasDecl  = !Helpers_Html::isEmpty($pf['invoice_declaration'] ?? '');
$_hasSig   = !empty($settings['show_signature']);
?>
<?php if ($_hasTerms || $_hasDecl || $_hasSig): ?>
<div class="terms-section">
    <table style="width:100%;border-collapse:collapse;">
        <tr>
            <td style="width:72%;vertical-align:top;padding-right:12px;">
                <table width="100%" cellpadding="0" cellspacing="0" style="border-collapse:collapse;">
                    <?php if ($_hasTerms): ?>
                    <tr><td style="padding-bottom:5px;"><div class="terms-label">Terms &amp; Conditions</div></td></tr>
                    <tr><td style="line-height:1.5;"><div class="terms-body"><?= $pf['invoice_terms'] ?></div></td></tr>
                    <?php endif; ?>
                    <?php if ($_hasDecl): ?>
                    <?php if ($_hasTerms): ?><tr><td style="height:10px;font-size:1pt;">&nbsp;</td></tr><?php endif; ?>
                    <tr><td style="padding-bottom:5px;"><div class="declaration-label">Declaration</div></td></tr>
                    <tr><td style="line-height:1.5;"><div class="declaration-body"><?= $pf['invoice_declaration'] ?></div></td></tr>
                    <?php endif; ?>
                </table>
            </td>
            <?php if ($_hasSig): ?>
            <td style="width:28%;vertical-align:bottom;text-align:center;padding-left:8px;">
                <div style="font-size:7pt;font-weight:600;color:#374151;">For, <?= $e($companyDisplayName) ?></div>
                <?php if ($sigPath && file_exists($sigPath)): ?>
                <img src="<?= $e($sigPath) ?>" alt="Signature" style="max-height:36pt;max-width:100pt;display:block;margin:4pt auto 4pt auto;">
                <?php endif; ?>
                <div style="font-size:7pt;color:#374151;">Authorised Signatory</div>
            </td>
            <?php endif; ?>
        </tr>
    </table>
</div>
<?php endif; ?>

</body>
</html>
