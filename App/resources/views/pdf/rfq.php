<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<?php include APP_PATH . '/resources/views/pdf/_shared/styles-t1.php'; ?>
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
    $cityZip      = trim(($company['city'] ?? '') . ' - ' . ($company['zipcode'] ?? ''), ' -');
    $stateCountry = trim(($company['state'] ?? '') . ', ' . ($company['country'] ?? ''), ', ');


    $expectedDate = !empty($po['expected_delivery_date']) ? $fmtDate($po['expected_delivery_date']) : '-';
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
            <?php /* if (!empty($company['pan'])): ?>PAN: <?= $e($company['pan']) ?><br><?php endif; */?>
        </div>
    </div>
    <div class="t2-header-right">
        <div class="doc-title">Request for Quotation</div>
        <div><span class="meta-label">Number #:</span>&nbsp;&nbsp;<span class="meta-val fw-bold"><?= $e($po['po_number']) ?></span></div>
        <div><span class="meta-label">Date:</span>&nbsp;&nbsp;<span class="meta-val fw-bold"><?= $fmtDate($po['order_date']) ?></span></div>
        <?php if (!empty($po['expected_delivery_date'])): ?>
        <div><span class="meta-label">Required By:</span>&nbsp;&nbsp;<span class="meta-val fw-bold"><?= $fmtDate($po['expected_delivery_date']) ?></span></div>
        <?php endif; ?>
        <?php if (!empty($po['reference'])): ?>
        <div><span class="meta-label">Reference:</span>&nbsp;&nbsp;<span class="meta-val fw-bold"><?= $e($po['reference']) ?></span></div>
        <?php endif; ?>
    </div>
    <div style="clear:both;"></div>
</div>

<!-- 2. Address boxes -->
<table class="doc-info-table t2-address-block" cellspacing="4" cellpadding="4">
    <thead>
        <tr>
            <th align="left" class="border-bottom" style="width:45%;">Vendor Details</th>
            <th style="width:10%;">&nbsp;</th>
            <th align="left" class="border-bottom" style="width:45%;">Ship To</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="width:45%;padding-top: 5px;">
                <div class="info-col-name"><?= $e(!empty($vendor['attention']) ? $vendor['attention'] : ($printData['vendor']['name'] ?? '')) ?></div>
                <?php if (!empty($vendor['address_line1'])): ?><div><?= $e($vendor['address_line1']) ?></div><?php endif; ?>
                <?php if (!empty($vendor['address_line2'])): ?><div><?= $e($vendor['address_line2']) ?></div><?php endif; ?>
                <?php $vendorCityZip = trim(($vendor['city'] ?? '') . ' - ' . ($vendor['postal_code'] ?? ''), ' -'); ?>
                <?php $vendorStateCountry = trim(($vendor['state'] ?? '') . ', ' . ($vendor['country'] ?? ''), ', '); ?>
                <?php if ($vendorCityZip): ?><div><?= $e($vendorCityZip) ?></div><?php endif; ?>
                <?php if ($vendorStateCountry): ?><div><?= $e($vendorStateCountry) ?></div><?php endif; ?>
                <?php if (!empty($printData['vendor']['gstin'])): ?><div>GSTIN: <?= $e($printData['vendor']['gstin']) ?></div><?php endif; ?>
            </td>
            <td style="width:10%;padding-top: 5px;">&nbsp;</td>
            <td style="width:45%;padding-top: 5px;">
                <div class="info-col-name"><?= $e($companyDisplayName) ?></div>
                <?php if (!empty($company['address'])): ?><div><?= nl2br($e($company['address'])) ?></div><?php endif; ?>
                <?php if ($cityZip): ?><div><?= $e($cityZip) ?></div><?php endif; ?>
                <?php if ($stateCountry): ?><div><?= $e($stateCountry) ?></div><?php endif; ?>
            </td>
        </tr>
    </tbody>
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
<?php /*
if ($sigPath && file_exists($sigPath)): ?>
<div class="signature-section">
    <div class="signature-inner">
        <img class="signature-img" src="<?= $e($sigPath) ?>" alt="Signature">
        <div class="signature-line">Authorised Signatory</div>
    </div>
</div>
<?php endif; 
*/
?>

</body>
</html>
