<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<?php include APP_PATH . '/resources/views/pdf/_shared/styles-t1.php'; ?>
</head>
<body>

<?php
    $printData  = $printData ?? [];
    $inquiry    = (array) ($printData['inquiry'] ?? []);
    $company    = (array) ($printData['company'] ?? null);
    $items      = $printData['items'] ?? [];
    $terms      = $printData['terms'] ?? '';
    $settings   = $printData['settings'] ?? [];
    $dateFormat = config('sys_default.dateFormat', 'd/m/Y');

    // If company passed as object (Models_Company), convert
    if ($company instanceof Models_Company) {
        $co = $company->toArray();
        $company = $co;
    }

    $fmtDate = function($d) use ($dateFormat) {
        return $d ? date($dateFormat, strtotime($d)) : '-';
    };
    $e = function($v) {
        return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
    };

    $companyDisplayName = !empty($company['legal_name']) ? $company['legal_name'] : ($company['display_name'] ?? ($company['name'] ?? ''));
    $logoPath = !empty($company['logo_path']) ? Helpers_Pdf::assetPath($company['logo_path']) : null;
    $sigPath  = !empty($company['signature_path']) ? Helpers_Pdf::assetPath($company['signature_path']) : null;
    $cityZip      = trim(($company['city'] ?? '') . ' - ' . ($company['zipcode'] ?? ''), ' -');
    $stateCountry = trim(($company['state'] ?? '') . ', ' . ($company['country'] ?? ''), ', ');

    $inquiryNumber  = $inquiry['inquiry_number'] ?? '';
    $createdDate    = !empty($inquiry['created_at']) ? $fmtDate($inquiry['created_at']) : date($dateFormat);
    $requiredByDate = !empty($inquiry['required_by_date']) ? $fmtDate($inquiry['required_by_date']) : null;
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
        </div>
    </div>
    <div class="t2-header-right">
        <div class="doc-title">Purchase Inquiry</div>
        <div><span class="meta-label">Inquiry #:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($inquiryNumber) ?></span></div>
        <div><span class="meta-label">Date:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($createdDate) ?></span></div>
        <?php if ($requiredByDate): ?>
        <div><span class="meta-label">Required By:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($requiredByDate) ?></span></div>
        <?php endif; ?>
    </div>
    <div style="clear:both;"></div>
</div>

<?php /*
<!-- 2. "To Vendor" placeholder note -->
<table class="doc-info-table" cellspacing="4" cellpadding="4" style="margin-bottom:10px;">
    <tbody>
        <tr>
            <td style="padding:8px; background:#f8f8f8; border-radius:4px; font-size:11px; color:#555;">
                <strong>To Vendor:</strong> Please review the items listed below and provide your best quotation including unit prices, applicable taxes, delivery terms, and lead time.
            </td>
        </tr>
    </tbody>
</table>
*/ ?>

<!-- 2. Deliver To block -->
<table class="doc-info-table t2-address-block" cellspacing="4" cellpadding="4" style="width:50%;">
    <thead>
        <tr>
            <th align="left" class="border-bottom">Deliver To</th>
        </tr>
    </thead>
    <tbody>
        <tr>
            <td style="padding-top:5px;">
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
            <th class="text-right" style="width:15%">Qty</th>
            <th style="width:25%">Notes</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $item): ?>
        <?php $item = (array) $item; ?>
        <tr class="<?= ($i % 2 !== 0) ? 'even-row' : '' ?>">
            <td><?= $i + 1 ?></td>
            <td>
                <div class="item-product"><?= $e($item['product_name'] ?? '') ?></div>
                <?php if (!empty($item['description'])): ?><div class="item-desc"><?= $e($item['description']) ?></div><?php endif; ?>
            </td>
            <td class="text-right">
                <?= $e(formatQty($item['required_qty'] ?? 0)) ?>
                <?php if (!empty($item['uom_code'])): ?><span style="font-size:7.5pt;font-weight:600;"> <?= $e($item['uom_code']) ?></span><?php endif; ?>
            </td>
            <td><?= !empty($item['notes']) ? $e($item['notes']) : '-' ?></td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($items)): ?>
        <tr><td colspan="4" style="text-align:center;color:#999;">No items</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<!-- 4. Notes section -->
<?php if (!empty($inquiry['notes'])): ?>
<div class="bottom-section">
    <div class="notes-col" style="float:none; width:100%;">
        <div class="notes-section">
            <div class="notes-label"><b>Notes:</b></div>
            <div class="notes-body"><?= nl2br($e($inquiry['notes'])) ?></div>
        </div>
    </div>
    <div style="clear:both;"></div>
</div>
<?php endif; ?>

<!-- Terms & conditions -->
<?php if (!Helpers_Html::isEmpty($terms)): ?>
<div class="terms-section">
    <div class="terms-label">Terms &amp; Conditions</div>
    <div class="terms-body" style="font-size:7.5pt;"><?= $terms ?></div>
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
