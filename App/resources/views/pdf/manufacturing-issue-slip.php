<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
@page { margin: 14mm 16mm 14mm 16mm; }
body {
    font-family: notosans, sans-serif;
    font-size: 9.5pt;
    color: #1f2937;
    background: #ffffff;
}

/* ── Title block ── */
.doc-title {
    text-align: center;
    font-size: 15pt;
    font-weight: 700;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    color: #111827;
    margin-bottom: 4px;
}
.doc-ref {
    text-align: center;
    font-size: 9pt;
    color: #6b7280;
    margin-bottom: 4px;
}
.doc-sub {
    text-align: center;
    font-size: 8.5pt;
    color: #6b7280;
    margin-bottom: 18px;
}

/* ── Divider ── */
.divider {
    border: none;
    border-top: 1px solid #e5e7eb;
    margin: 0 0 16px 0;
}

/* ── Meta grid ── */
.meta-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 20px;
}
.meta-table td {
    padding: 4px 12px 4px 0;
    vertical-align: top;
    font-size: 9pt;
    width: 33.33%;
}
.meta-label {
    color: #6b7280;
    font-size: 8.5pt;
}
.meta-val {
    color: #111827;
    font-weight: 600;
}

/* ── Items table ── */
.items-table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 16px;
}
.items-table th {
    background: #f3f4f6;
    border: 1px solid #e5e7eb;
    padding: 8px 10px;
    font-size: 8pt;
    font-weight: 600;
    color: #374151;
    text-align: left;
    letter-spacing: 0.3px;
}
.items-table th.text-right { text-align: right; }
.items-table td {
    border: 1px solid #e5e7eb;
    padding: 8px 10px;
    font-size: 9pt;
    color: #374151;
    vertical-align: top;
}
.items-table td.text-right { text-align: right; }
.item-name { font-weight: 600; color: #111827; }
.serial-list { font-size: 7.5pt; color: #6b7280; margin-top: 3px; line-height: 1.6; }

/* ── Signature block ── */
.signature-block {
    position: fixed;
    bottom: 14mm;
    left: 0;
    right: 0;
}
.sig-table {
    width: 80%;
    margin: 0 auto;
    border-collapse: collapse;
}
.sig-table td {
    text-align: center;
    padding: 0 12px;
    width: 33.33%;
}
.sig-line {
    border-top: 1pt solid #374151;
    margin-top: 36px;
    padding-top: 5px;
    font-size: 8.5pt;
    font-weight: 600;
    color: #374151;
}
.sig-sublabel {
    font-size: 7.5pt;
    color: #9ca3af;
    margin-top: 2px;
}

/* ── Footer note ── */
.footer-note {
    font-size: 7.5pt;
    color: #9ca3af;
    text-align: right;
    margin-top: 16px;
}
</style>
</head>
<body>

<?php
    $mo         = $printData['mo'];
    $allocation = $printData['allocation'];
    $items      = $printData['items'];

    $dateFormat = config('sys_default.dateFormat', 'd/m/Y');
    $fmtDate = function($d) use ($dateFormat) {
        return $d ? date($dateFormat, strtotime($d)) : '—';
    };
    $fmtDateTime = function($d) use ($dateFormat) {
        return $d ? date($dateFormat . ' H:i', strtotime($d)) : '—';
    };
    $fmtQty = function($v) {
        return rtrim(rtrim(number_format((float)$v, 4, '.', ','), '0'), '.');
    };
    $e = function($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    };

    $allocRef = $mo['mo_number'] . '-' . $allocation['id'];
?>

<!-- Title -->
<div class="doc-title">Material Issue Slip</div>
<div class="doc-ref">Issue Ref: <strong><?= $e($allocRef) ?></strong></div>
<div class="doc-sub">
    Issued on: <?= $e($fmtDateTime($allocation['created_at'])) ?>
    &nbsp;&middot;&nbsp;
    Issued by: <?= $e($allocation['created_by_name']) ?>
</div>

<hr class="divider">

<!-- MO Meta: 3 columns -->
<table class="meta-table">
    <tr>
        <td>
            <div class="meta-label">Finished Product</div>
            <div class="meta-val"><?= $e($mo['product_name']) ?></div>
        </td>
        <td>
            <div class="meta-label">BOM</div>
            <div class="meta-val"><?= $e($mo['bom_name']) ?></div>
        </td>
        <td>
            <div class="meta-label">Source Location</div>
            <div class="meta-val"><?= $e($mo['source_location_name']) ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="meta-label">Destination</div>
            <div class="meta-val"><?= $e($mo['destination_location_name']) ?></div>
        </td>
        <td>
            <?php if (!empty($mo['planned_date'])): ?>
            <div class="meta-label">Scheduled Date</div>
            <div class="meta-val"><?= $e($fmtDate($mo['planned_date'])) ?></div>
            <?php endif; ?>
        </td>
        <td>
            <?php if (!empty($allocation['notes'])): ?>
            <div class="meta-label">Notes</div>
            <div class="meta-val" style="font-weight:400;color:#374151;"><?= $e($allocation['notes']) ?></div>
            <?php endif; ?>
        </td>
    </tr>
</table>

<!-- Items table -->
<table class="items-table">
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:34%">Item</th>
            <th style="width:13%">SKU</th>
            <th style="width:10%">Tracking</th>
            <th class="text-right" style="width:13%">Issued Qty</th>
            <th style="width:26%">Serial / Lot Numbers</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $item): ?>
        <?php
            $trackLabel = $item['tracking_method'] === 'serial' ? 'Serial' : ($item['tracking_method'] === 'lot' ? 'Lot' : 'Qty');
        ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><span class="item-name"><?= $e($item['product_name']) ?></span></td>
            <td style="color:#6b7280;font-size:8.5pt;"><?= $e($item['sku'] ?: '—') ?></td>
            <td style="color:#6b7280;font-size:8.5pt;"><?= $e($trackLabel) ?></td>
            <td class="text-right">
                <?= $e($fmtQty($item['allocated_qty'])) ?>
                <?php if (!empty($item['uom_code'])): ?><span style="font-size:7.5pt;font-weight:600;"> <?= $e($item['uom_code']) ?></span><?php endif; ?>
            </td>
            <td>
                <?php if (!empty($item['serial_numbers'])): ?>
                    <div class="serial-list"><?= $e(implode(', ', $item['serial_numbers'])) ?></div>
                <?php else: ?>
                    <span style="color:#d1d5db;">—</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="footer-note">
    <?= count($items) ?> item(s) &nbsp;&middot;&nbsp; Printed on <?= date($dateFormat) ?>
</div>

<!-- Signature block — fixed to page bottom -->
<div class="signature-block">
    <table class="sig-table">
        <tr>
            <td>
                <div class="sig-line">Prepared By</div>
                <div class="sig-sublabel">Name &amp; Signature</div>
            </td>
            <td>
                <div class="sig-line">Issued By (Store)</div>
                <div class="sig-sublabel">Name &amp; Signature</div>
            </td>
            <td>
                <div class="sig-line">Received By (Production)</div>
                <div class="sig-sublabel">Name &amp; Signature</div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
