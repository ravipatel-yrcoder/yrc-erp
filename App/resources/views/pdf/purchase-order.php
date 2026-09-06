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

    $addressFields = ['address_line1', 'address_line2'];

    $deliveryValues = [];
    foreach ($delivery as $key => $val) {
        if (in_array($key, $addressFields) && !empty($val)) $deliveryValues[] = $val;
    }
    $fmtLoc = function($addr) {
        $cityZip = trim(($addr['city'] ?? '') . ' - ' . ($addr['postal_code'] ?? ''), ' -');
        return implode(', ', array_filter([$cityZip, $addr['state'] ?? '', $addr['country'] ?? '']));
    };
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
            <?php $companyLocLine = implode(', ', array_filter([$cityZip, $stateCountry])); ?>
            <?php if ($companyLocLine): ?><?= $e($companyLocLine) ?><br><?php endif; ?>
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
        <?php if (!empty($po['place_of_supply_name'])): ?>
        <div><span class="meta-label">Place of Supply:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($po['place_of_supply_name']) ?><?= !empty($po['place_of_supply_code']) ? ' (' . $e($po['place_of_supply_code']) . ')' : '' ?></span></div>
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
                <?php $vendorLocLine = implode(', ', array_filter([$vendorCityZip, $vendorStateCountry])); ?>
                <?php if ($vendorLocLine): ?><div><?= $e($vendorLocLine) ?></div><?php endif; ?>
                <?php if (!empty($printData['vendor']['gstin'])): ?><div>GSTIN: <?= $e($printData['vendor']['gstin']) ?></div><?php endif; ?>
            </td>
            <td style="width: 10%;padding-top: 5px;">&nbsp;</td>
            <td style="width: 45%;padding-top: 5px;">
                <?php if ($po['receiving_type'] === 'drop_ship' && !empty($delivery)): ?>
                    <div class="info-col-name"><?= $e($delivery['attention'] ?? '') ?></div>
                    <?php if ($deliveryValues): ?><?= $e(implode(', ', $deliveryValues)) ?><br><?php endif; ?>
                    <?php $deliveryLoc = $fmtLoc($delivery); if ($deliveryLoc): ?><div><?= $e($deliveryLoc) ?></div><?php endif; ?>
                <?php else: ?>
                    <div class="info-col-name"><?= $e($companyDisplayName) ?></div>
                    <?php if (!empty($company['address'])): ?><div><?= nl2br($e($company['address'])) ?></div><?php endif; ?>
                    <?php if ($companyLocLine): ?><div><?= $e($companyLocLine) ?></div><?php endif; ?>
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
                <?php if (!empty($item['uom_code'])): ?><span style="font-size:7pt;font-weight:600;"> <?= $e($item['uom_code']) ?></span><?php endif; ?>
            </td>
            <td class="text-right"><?= $fmtCurr($item['unit_price']) ?></td>
            <td class="text-right"><?= $e($item['tax_label'] ?: '—') ?></td>
            <td class="text-right"><?= $fmtCurr($item['line_total']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php $emptyRows = max(0, 10 - count($items)); for ($p = 0; $p < $emptyRows; $p++): ?>
        <tr class="<?= ((count($items) + $p) % 2 !== 0) ? 'even-row' : '' ?>"><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td><td>&nbsp;</td></tr>
        <?php endfor; ?>
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
        <div style="margin-top:4px;font-size:7pt;color:#374151;">
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

<!-- Terms, Declaration, Signature — two-column layout -->
<?php
$_hasTerms = !Helpers_Html::isEmpty($settings['terms'] ?? '');
$_hasDecl  = !Helpers_Html::isEmpty($settings['declaration'] ?? '');
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
                    <tr><td style="line-height:1.5;"><div class="terms-body"><?= $settings['terms'] ?></div></td></tr>
                    <?php endif; ?>
                    <?php if ($_hasDecl): ?>
                    <?php if ($_hasTerms): ?><tr><td style="height:10px;font-size:1pt;">&nbsp;</td></tr><?php endif; ?>
                    <tr><td style="padding-bottom:5px;"><div class="declaration-label">Declaration</div></td></tr>
                    <tr><td style="line-height:1.5;"><div class="declaration-body"><?= $settings['declaration'] ?></div></td></tr>
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
