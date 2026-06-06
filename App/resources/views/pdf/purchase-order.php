<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<?php include APP_PATH . '/resources/views/pdf/_shared/styles.php'; ?>
</head>
<body>

<?php
    $po = $printData['po'];
    $company = $printData['company'];
    $vendor = $printData['vendor_address'] ?? [];
    $delivery = $printData['delivery_address'] ?? [];
    $items = $printData['line_items'];
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
    $logoPath = !empty($company['logo_path']) ? Helpers_Pdf::assetPath($company['logo_path']) : null;
    $sigPath  = !empty($company['signature_path']) ? Helpers_Pdf::assetPath($company['signature_path']) : null;
    $cityState = trim(($company['city'] ?? '') . ', ' . ($company['state'] ?? ''), ', ');

    $addressFields = ['address_line1', 'address_line2', 'city', 'state'];

    $vendorAddressValues = [];
    foreach ($vendor as $key => $val) {
        if (in_array($key, $addressFields) && !empty($val)) {
            $vendorAddressValues[] = $val;
        }
    }

    $deliveryAddressValues = [];
    foreach ($delivery as $key => $val) {
        if (in_array($key, $addressFields) && !empty($val)) {
            $deliveryAddressValues[] = $val;
        }
    }
?>

<!-- 1. Header -->
<div class="doc-header">
    <div class="doc-header-logo">
        <?php if ($logoPath && file_exists($logoPath)): ?>
            <img src="<?= $e($logoPath) ?>" alt="Logo">
        <?php endif; ?>
    </div>
    <div class="doc-header-company">
        <span class="company-name"><?= $e($companyDisplayName) ?></span>
        <div class="company-meta">
            <?php if (!empty($company['address'])): ?><?= $e($company['address']) ?><br><?php endif; ?>
            <?php if ($cityState): ?><?= $e($cityState) ?><br><?php endif; ?>
            <?php if (!empty($company['country'])): ?><?= $e($company['country']) ?><?php if (!empty($company['zipcode'])): ?> &nbsp;<?= $e($company['zipcode']) ?><?php endif; ?><br><?php endif; ?>
            <?php if (!empty($company['gstin'])): ?>GSTIN: <?= $e($company['gstin']) ?><br><?php endif; ?>
            <?php if (!empty($company['pan'])): ?>PAN: <?= $e($company['pan']) ?><br><?php endif; ?>
        </div>
    </div>
    <div style="clear:both;"></div>
</div>

<div class="header-divider"></div>

<!-- 2. Info block: 3-col table -->
<table class="doc-info-table">
    <tr>
        <!-- Col 1: Document info -->
        <td class="doc-info-col-title border-right">
            <div class="doc-title">Purchase Order</div>
            <div><span class="meta-label">Number:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($po['po_number']) ?></span></div>
            <div><span class="meta-label">Date:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($po['order_date']) ?></span></div>
            <?php if (!empty($po['expected_delivery_date'])): ?>
                <div><span class="meta-label">Expected By:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($po['expected_delivery_date']) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($po['reference'])): ?>
                <div><span class="meta-label">Reference:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($po['reference']) ?></span></div>
            <?php endif; ?>
        </td>

        <!-- Col 2: Vendor Details -->
        <td class="border-right">
            <div class="info-col-label">Vendor Details</div>
            <div class="info-col-name"><?= $e(!empty($vendor['attention']) ? $vendor['attention'] : ($printData['vendor']['name'] ?? '')) ?></div>
            <?php if ($vendorAddressValues): ?>
                <?= $e(implode(', ', $vendorAddressValues)) ?>
            <?php endif; ?>
            <?php if (!empty($vendor['postal_code'])): ?><div><?= $e($vendor['postal_code']) ?></div><?php endif; ?>
            <?php if (!empty($vendor['country'])): ?><div><?= $e($vendor['country']) ?></div><?php endif; ?>
            <?php if (!empty($vendor['phone'])): ?><div><?= $e($vendor['phone']) ?></div><?php endif; ?>
        </td>

        <!-- Col 3: Ship To -->
        <td>
            <div class="info-col-label">Ship To</div>
            <?php if ($po['receiving_type'] === 'drop_ship' && !empty($delivery)): ?>
                <div class="info-col-name"><?= $e($delivery['attention'] ?? '') ?></div>
                <?php if ($deliveryAddressValues): ?>
                    <?= $e(implode(', ', $deliveryAddressValues)) ?>
                <?php endif; ?>
                <?php if (!empty($delivery['postal_code'])): ?><div><?= $e($delivery['postal_code']) ?></div><?php endif; ?>
                <?php if (!empty($delivery['country'])): ?><div><?= $e($delivery['country']) ?></div><?php endif; ?>
            <?php else: ?>
                <div class="info-col-name"><?= $e($companyDisplayName) ?></div>
                <?php if (!empty($company['address'])): ?><div><?= $e($company['address']) ?></div><?php endif; ?>
                <?php if ($cityState): ?><div><?= $e($cityState) ?></div><?php endif; ?>
            <?php endif; ?>
        </td>
    </tr>
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
    <div class="totals-col" style="padding: 10px 0px;border-top: 1px solid #e5e7eb">
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
