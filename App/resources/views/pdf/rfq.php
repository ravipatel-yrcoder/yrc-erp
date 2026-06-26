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
    $items = $printData['line_items'];
    $dateFormat = config('sys_default.dateFormat', 'd/m/Y');

    $fmtDate = function($d) use ($dateFormat) {
        return $d ? date($dateFormat, strtotime($d)) : '-';
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

    $expectedDate = !empty($po['expected_delivery_date']) ? $fmtDate($po['expected_delivery_date']) : '-';
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
            <div class="doc-title">Request for Quotation</div>
            <div><span class="meta-label">Number:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($po['po_number']) ?></span></div>
            <div><span class="meta-label">Date:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($po['order_date']) ?></span></div>
            <?php if (!empty($po['expected_delivery_date'])): ?>
                <div><span class="meta-label">Required By:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($po['expected_delivery_date']) ?></span></div>
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
            <div class="info-col-name"><?= $e($companyDisplayName) ?></div>
            <?php if (!empty($company['address'])): ?><div><?= $e($company['address']) ?></div><?php endif; ?>
            <?php if ($cityState): ?><div><?= $e($cityState) ?></div><?php endif; ?>
        </td>
    </tr>
</table>

<!-- 3. Line items table -->
<table class="items-table">
    <thead>
        <tr>
            <th style="width:5%">#</th>
            <th style="width:55%">Item</th>
            <th class="text-right" style="width:25%">Expected Date</th>
            <th class="text-right" style="width:15%">Qty</th>
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
            <td class="text-right"><?= $expectedDate ?></td>
            <td class="text-right">
                <?= $e(formatQty($item['qty'])) ?>
                <?php if (!empty($item['uom_code'])): ?><span style="font-size:7.5pt;font-weight:600;"> <?= $e($item['uom_code']) ?></span><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<!-- 4. Notes + disclaimer -->
<div class="bottom-section">
    <div class="notes-col" style="float:none; width:100%;">
        <?php if (!empty($po['notes'])): ?>
        <div class="notes-section">
            <div class="notes-label"><b>Notes:</b></div>
            <div class="notes-body"><?= $e($po['notes']) ?></div>
        </div>
        <?php endif; ?>
        <div class="notes-section" style="margin-top:8px;">
            <div class="notes-body" style="font-style:italic;color:#6b7280;font-size:8pt;">
                This is a Request for Quotation only and does not constitute a purchase order or commitment to buy.
            </div>
        </div>
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
