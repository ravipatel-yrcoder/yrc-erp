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

    $addressFields = ['address_line1', 'address_line2', 'city', 'state'];
    $billingValues = [];
    foreach ($billing as $key => $val) {
        if (in_array($key, $addressFields) && !empty($val)) $billingValues[] = $val;
    }
    $shippingValues = [];
    foreach ($shipping as $key => $val) {
        if (in_array($key, $addressFields) && !empty($val)) $shippingValues[] = $val;
    }
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
            <?php if ($cityZip): ?><?= $e($cityZip) ?><br><?php endif; ?>
            <?php if ($stateCountry): ?><?= $e($stateCountry) ?><br><?php endif; ?>
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
                <div class="info-col-name"><?= $e(!empty($billing['attention']) ? $billing['attention'] : ($pf['customer_name'] ?? '')) ?></div>
                <?php if ($billingValues): ?><?= $e(implode(', ', $billingValues)) ?><br><?php endif; ?>
                <?php if (!empty($billing['postal_code'])): ?><div><?= $e($billing['postal_code']) ?></div><?php endif; ?>
                <?php if (!empty($billing['country'])): ?><div><?= $e($billing['country']) ?></div><?php endif; ?>
                <?php if (!empty($billing['phone'])): ?><div><?= $e($billing['phone']) ?></div><?php endif; ?>
            </td>
            <td style="width: 10%;padding-top: 5px;">&nbsp;</td>
            <td style="width: 45%;padding-top: 5px;">
                <?php if (!empty($shippingValues)): ?>
                    <div class="info-col-name"><?= $e(!empty($shipping['attention']) ? $shipping['attention'] : ($pf['customer_name'] ?? '')) ?></div>
                    <?php if ($shippingValues): ?><?= $e(implode(', ', $shippingValues)) ?><br><?php endif; ?>
                    <?php if (!empty($shipping['postal_code'])): ?><div><?= $e($shipping['postal_code']) ?></div><?php endif; ?>
                    <?php if (!empty($shipping['country'])): ?><div><?= $e($shipping['country']) ?></div><?php endif; ?>
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
            <th style="width:30%">Item</th>
            <th style="width:10%">HSN/SAC</th>
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
            <td><?= $e($hsnCode ?: '—') ?></td>
            <td class="text-right">
                <?= $e(formatQty($item['quantity'])) ?>
                <?php if (!empty($item['uom_code'])): ?><span style="font-size:7.5pt;font-weight:600;"> <?= $e($item['uom_code']) ?></span><?php endif; ?>
            </td>
            <td class="text-right"><?= $fmtCurr($item['unit_price']) ?></td>
            <td class="text-right"><?= $discDisplay ?></td>
            <td class="text-right"><?= $e($taxLabel) ?></td>
            <td class="text-right"><?= $fmtCurr($item['line_total']) ?></td>
        </tr>
        <?php endforeach; ?>
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
            <tr>
                <td class="totals-label">Tax</td>
                <td align="right"><?= $fmtCurr($pf['tax_amount']) ?></td>
            </tr>
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
            <tr class="grand-total-row">
                <td>Total</td>
                <td align="right"><?= $fmtCurr($pf['grand_total']) ?></td>
            </tr>
        </table>
    </div>

    <div class="notes-col">

        <!-- Amount in Words (full-width, below totals row — same pattern as SO PDF) -->
        <?php if (!empty($settings['show_amount_in_words'])): ?>
        <div style="margin-top:6px;font-size:8pt;color:#374151;">
            <strong>Amount in Words:</strong>&nbsp;<?= $e(Helpers_Pdf::amountToWordsINR((float)$pf['grand_total'])) ?>
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

<!-- Bank Details -->
<?php if (!empty($settings['show_bank_details']) && !Helpers_Html::isEmpty($settings['bank_details'] ?? '')): ?>
<div class="terms-section">
    <div class="terms-label">Bank Details</div>
    <div class="terms-body" style="font-size:7.5pt;"><?= $settings['bank_details'] ?></div>
</div>
<?php endif; ?>

<!-- Terms & Conditions (raw sanitized HTML — do not escape) -->
<?php if (!Helpers_Html::isEmpty($pf['invoice_terms'] ?? '')): ?>
<div class="terms-section">
    <div class="terms-label">Terms &amp; Conditions</div>
    <div class="terms-body" style="font-size:7.5pt;"><?= $pf['invoice_terms'] ?></div>
</div>
<?php endif; ?>

<!-- Signature / Declaration block -->
<?php if (!empty($settings['show_signature']) || !Helpers_Html::isEmpty($settings['invoice_declaration'] ?? '')): ?>
<div class="declaration-signature">
    <div class="declaration-block">
        <?php if (!Helpers_Html::isEmpty($pf['invoice_declaration'] ?? '')): ?>
        <div class="declaration-section">
            <div class="declaration-label">Declaration</div>
            <div class="declaration-body" style="font-size:7.5pt;"><?= $pf['invoice_declaration'] ?></div>
        </div>
        <?php endif; ?>
    </div>    
    <div class="signature-block">
        <div style="width: 25%; float: right; text-align: center;margin-top: 15px;">
            <?php if (!empty($settings['show_signature'])): ?>
            <div style="font-size:7.5pt;font-weight:600;color:#374151;">For, <?= $e($companyDisplayName) ?></div>
            <?php if ($sigPath && file_exists($sigPath)): ?>
            <img src="<?= $e($sigPath) ?>" alt="Signature" style="max-height:36pt;max-width:100pt;display:block;margin:4pt auto 4pt auto;">
            <?php endif; ?>
            <div style="font-size:7.5pt;color:#374151;">Authorised Signatory</div>
            <?php endif; ?>
        </div>
    </div>
</div>
<?php endif; ?>

</body>
</html>
