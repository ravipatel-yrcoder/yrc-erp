<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<?php include APP_PATH . '/resources/views/pdf/_shared/styles.php'; ?>
</head>
<body>

<?php
    $so = $printData['so'];
    $company  = $printData['company'];
    $billing  = $printData['billing_address'] ?? [];
    $shipping = $printData['shipping_address'] ?? [];
    $items = $printData['line_items'];
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

    $isQuotationDoc = (($so['origin_type'] ?? 'order') === 'quotation') && (($so['status'] ?? '') === 'draft');
    $docTitle = $isQuotationDoc ? 'Quotation' : 'Sales Order';
    $companyDisplayName = !empty($company['legal_name']) ? $company['legal_name'] : ($company['name'] ?? '');

    $logoPath = !empty($company['logo_path']) ? Helpers_Pdf::assetPath($company['logo_path']) : null;
    $sigPath  = !empty($company['signature_path']) ? Helpers_Pdf::assetPath($company['signature_path']) : null;

    $cityState = trim(($company['city'] ?? '') . ', ' . ($company['state'] ?? ''), ', ');
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

<?php
$addressFields = ['address_line1', 'address_line2', 'city', 'state'];
$billingAddressValues = [];
foreach($billing as $key => $val) {
    if( in_array($key, $addressFields) && !empty($val) ) {
        $billingAddressValues[] = $val;
    }
}

$shippingddressValues = [];
foreach($shipping as $key => $val) {
    if( in_array($key, $addressFields) && !empty($val) ) {
        $shippingddressValues[] = $val;
    }
}

?>

<!-- 2. Info block: 3-col table -->
<table class="doc-info-table">
    <tr>
        <!-- Col 1: Document info -->
        <td class="doc-info-col-title border-right">
            <div class="doc-title"><?= $e($docTitle);?></div>
            <div><span class="meta-label">Number:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($so['so_number']); ?></span></div>

            <?php if ($isQuotationDoc): ?>
                <div><span class="meta-label">Date:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($so['quote_date']) ?></span></div>
                <?php if (!empty($so['valid_until'])): ?>
                <div><span class="meta-label">Valid Until:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($so['valid_until']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($so['order_date'])): ?>
                <div><span class="meta-label">Order Date:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($so['order_date']) ?></span></div>
                <?php endif; ?>
            <?php else: ?>
                <div><span class="meta-label">Date:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($so['order_date']) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($so['expected_delivery_date'])): ?>
                <div><span class="meta-label">Delivery By:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($so['expected_delivery_date']) ?></span></div>
            <?php endif; ?>
            <?php if (!empty($so['reference'])): ?>
                <div><span class="meta-label">Reference:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($so['reference']) ?></span></div>
            <?php endif; ?>
        </td>

        <!-- Col 2: Bill To -->
        



        <td class="border-right">
            <div class="info-col-label">Bill To</div>
            <div class="info-col-name"><?= $e(!empty($billing['attention']) ? $billing['attention'] : ($printData['customer']['name'] ?? '')) ?></div>
            <?php
            if( $billingAddressValues ){
                echo $e(implode(", ", $billingAddressValues));
            }
            ?>
            <?php if (!empty($billing['postal_code'])): ?><div><?= $e($billing['postal_code']) ?></div><?php endif; ?>
            <?php if (!empty($billing['country'])): ?><div><?= $e($billing['country']) ?></div><?php endif; ?>
            <?php if (!empty($billing['phone'])): ?><div><?= $e($billing['phone']) ?></div><?php endif; ?>
        </td>

        <!-- Col 3: Ship To or Pickup -->
        <td>
            <div class="info-col-label"><?= $so['delivery_type'] === 'ship' ? 'Ship To' : 'Delivery' ?></div>
            <?php if ($so['delivery_type'] === 'ship'): ?>
                <?php if (!empty($shipping)): ?>
                    <div class="info-col-name"><?= $e(!empty($shipping['attention']) ? $shipping['attention'] : ($printData['customer']['name'] ?? '')) ?></div>
                    <?php
                    if( $shippingddressValues ){
                        echo $e(implode(", ", $shippingddressValues));
                    }
                    ?>
                    <?php if (!empty($shipping['postal_code'])): ?><div><?= $e($shipping['postal_code']) ?></div><?php endif; ?>
                    <?php if (!empty($shipping['country'])): ?><div><?= $e($shipping['country']) ?></div><?php endif; ?>
                    <?php if (!empty($shipping['phone'])): ?><div><?= $e($shipping['phone']) ?></div><?php endif; ?>
                <?php else: ?>
                    <span style="font-size:7.5pt;color:#9ca3af;font-style:italic;">No address provided</span>
                <?php endif; ?>
            <?php else: ?>
                <div class="pickup-badge">Pickup</div>
            <?php endif; ?>
        </td>
    </tr>
</table>

<!-- 3. Line items table -->
<table class="items-table">
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:36%">Item</th>
            <th class="text-right" style="width:8%">Qty</th>
            <th class="text-right" style="width:13%">Unit Price</th>
            <th class="text-right" style="width:11%">Discount</th>
            <th class="text-right" style="width:11%">Tax</th>
            <th class="text-right" style="width:13%">Amount</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $item): ?>
        <?php
            $discAmt     = (float)($item['discount'] ?? 0);
            $discDisplay = $discAmt > 0 ? $fmtCurr($discAmt) : '&mdash;';
        ?>
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
            <td class="text-right"><?= $discDisplay ?></td>
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
                <td align="right"><?= $fmtCurr($so['subtotal']) ?></td>
            </tr>
            <?php if ($so['item_discount_total'] > 0): ?>
            <tr>
                <td class="totals-label">Item Discounts</td>
                <td align="right">- <?= $fmtCurr($so['item_discount_total']) ?></td>
            </tr>
            <tr>
                <td class="totals-label">Subtotal After Discount</td>
                <td align="right"><?= $fmtCurr($so['subtotal_after_item_discount']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($so['order_discount_amount'] > 0): ?>
            <tr>
                <td class="totals-label">Order Discount</td>
                <td align="right">- <?= $fmtCurr($so['order_discount_amount']) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td class="totals-label">Tax</td>
                <td align="right"><?= $fmtCurr($so['tax_amount']) ?></td>
            </tr>
            <?php if (!empty($so['round_off_amount']) && (float)$so['round_off_amount'] !== 0.0): ?>
            <?php $roundOff = (float)$so['round_off_amount']; ?>
            <tr>
                <td class="totals-label">Round Off</td>
                <td align="right" style="<?= $roundOff < 0 ? 'color:#c0392b;' : 'color:#27ae60;' ?>">
                    <?= ($roundOff < 0 ? '&minus; ' : '+ ') . $fmtCurr(abs($roundOff)) ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr class="grand-total-row">
                <td>Total</td>
                <td align="right"><?= $fmtCurr($so['grand_total']) ?></td>
            </tr>
        </table>
    </div>

    <div class="notes-col">
    <?php if (!empty($so['notes'])): ?>
        <div class="notes-section">
            <div class="notes-label"><b>Notes:</b></div>
            <div class="notes-body"><?= $e($so['notes']) ?></div>
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
