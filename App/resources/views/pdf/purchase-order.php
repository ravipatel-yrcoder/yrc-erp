<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<?php include APP_PATH . '/resources/views/pdf/_shared/styles-t1.php'; ?>
</head>
<body>

<?php
    $po       = $printData['po'];
    $company  = $printData['company'];
    $vendor   = $printData['vendor_address'] ?? [];
    $delivery = $printData['delivery_address'] ?? [];
    $items    = $printData['line_items'];
    $settings = $printData['settings'] ?? [];
    $dateFormat = config('sys_default.dateFormat', 'd/m/Y');

    $fmtDate = function($d) use ($dateFormat) {
        return $d ? date($dateFormat, strtotime($d)) : '-';
    };
    $currencyCode = $printData['po']['currency_code'] ?? config('sys_default.currency', 'INR');
    $currencies   = config('currencies', []);
    $currSymbol   = $currencies[$currencyCode]['symbol'] ?? $currencyCode;
    $currDecimals = $currencies[$currencyCode]['decimals'] ?? 2;
    $fmtCurr = function($v) use ($currSymbol, $currDecimals) {
        return htmlspecialchars($currSymbol, ENT_QUOTES, 'UTF-8') . number_format((float)$v, $currDecimals);
    };
    $e = function($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    };

    $companyDisplayName = !empty($company['legal_name']) ? $company['legal_name'] : ($company['name'] ?? '');
    $logoPath           = !empty($company['logo_path']) ? Helpers_Pdf::assetPath($company['logo_path']) : null;
    $sigPath            = !empty($company['signature_path']) ? Helpers_Pdf::assetPath($company['signature_path']) : null;
    $cityZip      = trim(($company['city'] ?? '') . ' - ' . ($company['zipcode'] ?? ''), ' -');
    $stateCountry = trim(($company['state'] ?? '') . ', ' . ($company['country'] ?? ''), ', ');

    $addressFields = ['address_line1', 'address_line2', 'city', 'state'];

    $deliveryValues = [];
    foreach ($delivery as $key => $val) {
        if (in_array($key, $addressFields) && !empty($val)) $deliveryValues[] = $val;
    }
?>

<!-- 1. Split header -->
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
            <?php if (!empty($company['pan'])): ?>PAN: <?= $e($company['pan']) ?><br><?php endif; ?>
        </div>
    </div>
    <div class="t2-header-right">
        <div class="doc-title">Purchase Order</div>
        <div><span class="meta-label">Number #:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($po['po_number']) ?></span></div>
        <div><span class="meta-label">Date:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($po['order_date']) ?></span></div>
        <?php if (!empty($po['expected_delivery_date'])): ?>
        <div><span class="meta-label">Expected By:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($po['expected_delivery_date']) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($po['reference'])): ?>
        <div><span class="meta-label">Reference:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($po['reference']) ?></span></div>
        <?php endif; ?>
    </div>
    <div style="clear:both;"></div>
</div>

<!-- 2. Address boxes -->
<table class="doc-info-table t2-address-block" cellspacing="4" cellpadding="4">
    <thead style="padding-bottom: 25px;margin-bottom: 25px;">
        <tr>
            <th align="left" class="border-bottom" style="width: 45%;">Vendor Details</th>
            <th style="width: 10%;">&nbsp;</th>
            <th align="left" class="border-bottom" style="width: 45%;">Ship To</th>
        <tr>
    </thead>
    <tbody>
        <tr>
            <td style="width: 45%;padding-top: 5px;">
                <div class="info-col-name"><?= $e(!empty($vendor['attention']) ? $vendor['attention'] : ($printData['vendor']['name'] ?? '')) ?></div>
                <?php if (!empty($vendor['address_line1'])): ?><div><?= $e($vendor['address_line1']) ?></div><?php endif; ?>
                <?php if (!empty($vendor['address_line2'])): ?><div><?= $e($vendor['address_line2']) ?></div><?php endif; ?>
                <?php $vendorCityZip = trim(($vendor['city'] ?? '') . ' - ' . ($vendor['postal_code'] ?? ''), ' -'); ?>
                <?php $vendorStateCountry = trim(($vendor['state'] ?? '') . ', ' . ($vendor['country'] ?? ''), ', '); ?>
                <?php if ($vendorCityZip): ?><div><?= $e($vendorCityZip) ?></div><?php endif; ?>
                <?php if ($vendorStateCountry): ?><div><?= $e($vendorStateCountry) ?></div><?php endif; ?>
                <?php if (!empty($printData['vendor']['gstin'])): ?><div>GSTIN: <?= $e($printData['vendor']['gstin']) ?></div><?php endif; ?>
            </td>
            <td style="width: 10%;padding-top: 5px;">&nbsp;</td>
            <td style="width: 45%;padding-top: 5px;">
                <?php if ($po['receiving_type'] === 'drop_ship' && !empty($delivery)): ?>
                    <div class="info-col-name"><?= $e($delivery['attention'] ?? '') ?></div>
                    <?php if ($deliveryValues): ?><?= $e(implode(', ', $deliveryValues)) ?><br><?php endif; ?>
                    <?php if (!empty($delivery['postal_code'])): ?><div><?= $e($delivery['postal_code']) ?></div><?php endif; ?>
                    <?php if (!empty($delivery['country'])): ?><div><?= $e($delivery['country']) ?></div><?php endif; ?>
                <?php else: ?>
                    <div class="info-col-name"><?= $e($companyDisplayName) ?></div>
                    <?php if (!empty($company['address'])): ?><div><?= nl2br($e($company['address'])) ?></div><?php endif; ?>
                    <?php if ($cityZip): ?><div><?= $e($cityZip) ?></div><?php endif; ?>
                    <?php if ($stateCountry): ?><div><?= $e($stateCountry) ?></div><?php endif; ?>
                <?php endif; ?>
            </td>
        </tr>
    </tbody>
</table>

<!-- 3. Line items table -->
<table class="items-table">
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:40%">Item</th>
            <th class="text-right" style="width:8%">Qty</th>
            <th class="text-right" style="width:15%">Unit Price</th>
            <th class="text-right" style="width:11%">Tax</th>
            <th class="text-right" style="width:14%">Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $item): ?>
        <tr class="<?= ($i % 2 !== 0) ? 'even-row' : '' ?>">
            <td><?= $i + 1 ?></td>
            <td>
                <div class="item-product"><?= $e($item['product_name']) ?></div>
                <?php if (!empty($item['description'])): ?><div class="item-desc"><?= $e($item['description']) ?></div><?php endif; ?>
            </td>
            <td class="text-right">
                <?= $e(formatQty($item['qty'])) ?>
                <?php if (!empty($item['uom_code'])): ?><span style="font-size:7.5pt;font-weight:600;"> <?= $e($item['uom_code']) ?></span><?php endif; ?>
            </td>
            <td class="text-right"><?= $fmtCurr($item['unit_price']) ?></td>
            <td class="text-right"><?= $e($item['tax_label'] ?: '—') ?></td>
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
                <td align="right"><?= $fmtCurr($po['subtotal']) ?></td>
            </tr>
            <tr>
                <td class="totals-label">Tax</td>
                <td align="right"><?= $fmtCurr($po['tax_amount']) ?></td>
            </tr>
            <tr class="grand-total-row">
                <td>Total</td>
                <td align="right"><?= $fmtCurr($po['grand_total']) ?></td>
            </tr>
        </table>
    </div>

    <div class="notes-col">
        <?php if (!empty($settings['show_amount_in_words'])): ?>
        <div style="margin-top:6px;font-size:8pt;color:#374151;">
            <strong>Amount in Words:</strong>&nbsp;<?= $e(Helpers_Pdf::amountToWordsINR((float)$po['grand_total'])) ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($po['notes'])): ?>
        <div class="notes-section">
            <div class="notes-label"><b>Notes:</b></div>
            <div class="notes-body"><?= $e($po['notes']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div style="clear:both;"></div>
</div>

<!-- Terms & conditions -->
<?php if (!Helpers_Html::isEmpty($settings['terms'] ?? '')): ?>
<div class="terms-section">
    <div class="terms-label">Terms &amp; Conditions</div>
    <div class="terms-body" style="font-size:7.5pt;"><?= $settings['terms'] ?></div>
</div>
<?php endif; ?>

<!-- Signature / Declaration block -->
<?php if (!empty($settings['show_signature']) || !Helpers_Html::isEmpty($settings['declaration'] ?? '')): ?>
<div class="declaration-signature">
    <div class="declaration-block">
        <?php if (!Helpers_Html::isEmpty($settings['declaration'] ?? '')): ?>
        <div class="declaration-label">Declaration</div>
        <div class="declaration-body" style="font-size:7.5pt;"><?= $settings['declaration'] ?></div>
        <?php endif; ?>
    </div>
    <div class="signature-block">
        <div style="width:25%;float:right;text-align:center;margin-top:15px;">
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
