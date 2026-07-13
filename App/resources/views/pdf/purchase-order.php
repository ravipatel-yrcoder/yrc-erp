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

    $vendorValues = [];
    foreach ($vendor as $key => $val) {
        if (in_array($key, $addressFields) && !empty($val)) $vendorValues[] = $val;
    }
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
            <?php if (!empty($company['address'])): ?><?= $e($company['address']) ?><br><?php endif; ?>
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
                <?php if ($vendorValues): ?><?= $e(implode(', ', $vendorValues)) ?><br><?php endif; ?>
                <?php if (!empty($vendor['postal_code'])): ?><div><?= $e($vendor['postal_code']) ?></div><?php endif; ?>
                <?php if (!empty($vendor['country'])): ?><div><?= $e($vendor['country']) ?></div><?php endif; ?>
                <?php if (!empty($vendor['phone'])): ?><div><?= $e($vendor['phone']) ?></div><?php endif; ?>
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
                    <?php if (!empty($company['address'])): ?><div><?= $e($company['address']) ?></div><?php endif; ?>
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
        <?php if (!empty($po['notes'])): ?>
        <div class="notes-section">
            <div class="notes-label"><b>Notes:</b></div>
            <div class="notes-body"><?= $e($po['notes']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div style="clear:both;"></div>
</div>

<!-- 5. Signature block -->
<?php if ($sigPath && file_exists($sigPath)): ?>
<div class="signature-section">
    <div class="signature-inner">
        <img class="signature-img" src="<?= $e($sigPath) ?>" alt="Signature">
        <div class="signature-line">Authorised Signatory</div>
    </div>
</div>
<?php endif; ?>

</body>
</html>
