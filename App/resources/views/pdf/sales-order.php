<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<?php include APP_PATH . '/resources/views/pdf/_shared/styles-t1.php'; ?>
</head>
<body>

<?php
    $so       = $printData['so'];
    $company  = $printData['company'];
    $billing  = $printData['billing_address'] ?? [];
    $shipping = $printData['shipping_address'] ?? [];
    $items    = $printData['line_items'];
    $charges  = $printData['charges'] ?? [];
    $settings = $printData['settings'] ?? [];

    $chargesSubtotal = array_sum(array_column($charges, 'amount'));
    $chargesTaxTotal = array_sum(array_column($charges, 'tax_amount'));
    $gs       = $printData['gst_summary'] ?? [];
    $hasGst   = !empty($gs) && !empty($gs['totals']);

    $taxableBase       = (float)$so['subtotal'] - (float)($so['item_discount_total'] ?? 0) - (float)($so['order_discount_amount'] ?? 0);
    $allTaxForDisplay  = $hasGst
        ? (($gs['totals']['igst_amount'] ?? 0) + ($gs['totals']['cgst_amount'] ?? 0)
         + ($gs['totals']['sgst_amount'] ?? 0) + ($gs['totals']['ugst_amount'] ?? 0)
         + ($gs['totals']['cess_amount'] ?? 0))
        : ((float)$so['tax_amount'] + $chargesTaxTotal);
    $displayGrandTotal = round($taxableBase + $allTaxForDisplay + $chargesSubtotal + (float)($so['round_off_amount'] ?? 0), 2);

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

    $companyDisplayName  = !empty($company['legal_name']) ? $company['legal_name'] : ($company['name'] ?? '');
    $logoPath            = !empty($company['logo_path']) ? Helpers_Pdf::assetPath($company['logo_path']) : null;
    $sigPath             = !empty($company['signature_path']) ? Helpers_Pdf::assetPath($company['signature_path']) : null;
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
            <?php /* if (!empty($company['pan'])): ?>PAN: <?= $e($company['pan']) ?><br><?php endif; */ ?>
        </div>
    </div>
    <div class="t2-header-right">
        <div class="doc-title">Sales Order</div>
        <table style="width:100%;border-collapse:collapse;text-align:right;margin-top:2px;">
            <tr>
                <td style="padding:1.5px 0;width:50%;"><span class="meta-label">Number:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($so['so_number']) ?></span></td>
                <td style="padding:1.5px 0;width:50%;"><span class="meta-label">Date:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($so['order_date']) ?></span></td>
            </tr>
            <?php if (!empty($so['po_number']) || !empty($so['po_date'])): ?>
            <tr>
                <td style="padding:1.5px 0;width:50%;">
                    <?php if (!empty($so['po_number'])): ?><span class="meta-label">Customer PO #:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($so['po_number']) ?></span><?php endif; ?>
                </td>
                <td style="padding:1.5px 0;width:50%;">
                    <?php if (!empty($so['po_date'])): ?><span class="meta-label">PO Date:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($so['po_date']) ?></span><?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($so['expected_delivery_date']) || !empty($so['reference'])): ?>
            <tr>
                <td style="padding:1.5px 0;width:50%;">
                    <?php if (!empty($so['expected_delivery_date'])): ?><span class="meta-label">Delivery By:</span>&nbsp;&nbsp;<span class="meta-val"><?= $fmtDate($so['expected_delivery_date']) ?></span><?php endif; ?>
                </td>
                <td style="padding:1.5px 0;width:50%;">
                    <?php if (!empty($so['reference'])): ?><span class="meta-label">Reference:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($so['reference']) ?></span><?php endif; ?>
                </td>
            </tr>
            <?php endif; ?>
            <?php if (!empty($so['place_of_supply_name'])): ?>
            <tr>
                <td colspan="2" style="padding:1.5px 0;"><span class="meta-label">Place of Supply:</span>&nbsp;&nbsp;<span class="meta-val"><?= $e($so['place_of_supply_name']) ?><?= !empty($so['place_of_supply_code']) ? ' (' . $e($so['place_of_supply_code']) . ')' : '' ?></span></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>
    <div style="clear:both;"></div>
</div>

<!-- 2. Address boxes -->
<table class="doc-info-table t2-address-block" cellspacing="4" cellpadding="4">
    <thead style="padding-bottom: 25px;margin-bottom: 25px;">
        <tr>
            <th align="left" class="border-bottom" style="width: 45%;">Bill To</th>
            <th style="width: 10%;">&nbsp;</th>
            <th align="left" class="border-bottom" style="width: 45%;"><?= $so['delivery_type'] === 'ship' ? 'Ship To' : 'Delivery' ?></th>
        <tr>
    </thead>
    <tbody>
        <tr>
            <td style="width: 45%;padding-top: 5px;">
                <div class="info-col-name" style="font-weight:bold;"><?= $e(!empty($billing['attention']) ? $billing['attention'] : ($printData['customer']['name'] ?? '')) ?></div>
                <?php if ($billingValues): ?><?= $e(implode(', ', $billingValues)) ?><br><?php endif; ?>
                <?php $billLoc = $fmtLoc($billing); if ($billLoc): ?><div><?= $e($billLoc) ?></div><?php endif; ?>
                <?php if (!empty($so['customer_gstin_snapshot'])): ?><div>GSTIN: <?= $e($so['customer_gstin_snapshot']) ?></div><?php endif; ?>
                <?php if (!empty($billing['phone'])): ?><div><?= $e($billing['phone']) ?></div><?php endif; ?>
            </td>
            <td style="width: 10%;padding-top: 5px;">&nbsp;</td>
            <td style="width: 45%;padding-top: 5px;">
                <?php if ($so['delivery_type'] === 'ship'): ?>
                    <?php if (!empty($shipping)): ?>
                        <div class="info-col-name"><?= $e(!empty($shipping['attention']) ? $shipping['attention'] : ($printData['customer']['name'] ?? '')) ?></div>
                        <?php if ($shippingValues): ?><?= $e(implode(', ', $shippingValues)) ?><br><?php endif; ?>
                        <?php $shipLoc = $fmtLoc($shipping); if ($shipLoc): ?><div><?= $e($shipLoc) ?></div><?php endif; ?>
                        <?php if (!empty($shipping['phone'])): ?><div><?= $e($shipping['phone']) ?></div><?php endif; ?>
                    <?php else: ?>
                        <span style="font-size:7.5pt;color:#9ca3af;font-style:italic;">No address provided</span>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="pickup-badge">Pickup</div>
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
                <?php if (!empty($item['uom_code'])): ?><span style="font-size:7pt;font-weight:600;"> <?= $e($item['uom_code']) ?></span><?php endif; ?>
            </td>
            <td class="text-right"><?= $fmtCurr($item['unit_price']) ?></td>
            <td class="text-right"><?= $discDisplay ?></td>
            <td class="text-right"><?= $e($item['tax_label'] ?: '—') ?></td>
            <td class="text-right"><?= $fmtCurr($item['line_total']) ?></td>
        </tr>
        <?php endforeach; ?>
        <?php foreach ($charges as $ci => $charge):
            $chTaxName = !empty($charge['tax_info']) ? ($charge['tax_info'][0]['name'] ?? '—') : '—';
            $chRowNum  = count($items) + $ci + 1;
        ?>
        <tr>
            <td><?= $chRowNum ?></td>
            <td><strong><?= $e($charge['label']) ?></strong></td>
            <td class="text-right">&mdash;</td>
            <td class="text-right"><?= $fmtCurr($charge['amount']) ?></td>
            <td class="text-right">&mdash;</td>
            <td class="text-right"><?= $e($chTaxName) ?></td>
            <td class="text-right"><?= $fmtCurr((float)$charge['amount'] + (float)($charge['tax_amount'] ?? 0)) ?></td>
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
                <td align="right"><?= $fmtCurr((float)$so['subtotal'] + $chargesSubtotal) ?></td>
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
            <?php if ($hasGst): ?>
            <?php if ($gs['is_intra_state']): ?>
                <?php if ($gs['totals']['cgst_amount'] > 0): ?>
                <tr><td class="totals-label">CGST</td><td align="right"><?= $fmtCurr($gs['totals']['cgst_amount']) ?></td></tr>
                <?php endif; ?>
                <?php if ($gs['use_ugst'] && $gs['totals']['ugst_amount'] > 0): ?>
                <tr><td class="totals-label">UGST</td><td align="right"><?= $fmtCurr($gs['totals']['ugst_amount']) ?></td></tr>
                <?php elseif (!$gs['use_ugst'] && $gs['totals']['sgst_amount'] > 0): ?>
                <tr><td class="totals-label">SGST</td><td align="right"><?= $fmtCurr($gs['totals']['sgst_amount']) ?></td></tr>
                <?php endif; ?>
            <?php else: ?>
                <?php if ($gs['totals']['igst_amount'] > 0): ?>
                <tr><td class="totals-label">IGST</td><td align="right"><?= $fmtCurr($gs['totals']['igst_amount']) ?></td></tr>
                <?php endif; ?>
            <?php endif; ?>
            <?php if (!empty($gs['has_cess']) && $gs['totals']['cess_amount'] > 0): ?>
            <tr><td class="totals-label">CESS</td><td align="right"><?= $fmtCurr($gs['totals']['cess_amount']) ?></td></tr>
            <?php endif; ?>
            <?php else: ?>
            <tr>
                <td class="totals-label">Tax</td>
                <td align="right"><?= $fmtCurr((float)$so['tax_amount'] + $chargesTaxTotal) ?></td>
            </tr>
            <?php endif; ?>
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
                <td>Grand Total</td>
                <td align="right"><?= $fmtCurr($displayGrandTotal) ?></td>
            </tr>
        </table>
    </div>

    <div class="notes-col">
        <?php if (!empty($settings['show_amount_in_words'])): ?>
        <div style="margin-top:4px;font-size:7pt;color:#374151;">
            <strong>Amount in Words:</strong>&nbsp;<?= $e(Helpers_Pdf::amountToWordsINR($displayGrandTotal)) ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($so['notes'])): ?>
        <div class="notes-section">
            <div class="notes-label"><b>Notes:</b></div>
            <div class="notes-body"><?= $e($so['notes']) ?></div>
        </div>
        <?php endif; ?>
    </div>

    <div style="clear:both;"></div>
</div>

<!-- Terms, Declaration, Signature — two-column layout -->
<?php
$_hasTerms = !Helpers_Html::isEmpty($so['so_terms'] ?? '');
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
                    <tr><td style="line-height:1.5;"><div class="terms-body"><?= $so['so_terms'] ?></div></td></tr>
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
