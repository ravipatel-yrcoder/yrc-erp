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
.doc-status {
    text-align: center;
    margin-bottom: 18px;
}
.status-badge {
    display: inline-block;
    padding: 2px 9px;
    border-radius: 3px;
    font-size: 7.5pt;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}
.badge-confirmed    { background:#dbeafe; color:#1d4ed8; }
.badge-in_production{ background:#fef3c7; color:#b45309; }
.badge-completed    { background:#d1fae5; color:#065f46; }
.badge-draft        { background:#f3f4f6; color:#6b7280; }
.badge-cancelled    { background:#fee2e2; color:#b91c1c; }

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
.alloc-full    { color: #065f46; font-weight: 600; }
.alloc-partial { color: #b45309; font-weight: 600; }
.alloc-none    { color: #9ca3af; }

/* ── Footer note ── */
.footer-note {
    font-size: 7.5pt;
    color: #9ca3af;
    text-align: right;
}
</style>
</head>
<body>

<?php
    $mo      = $printData['mo'];
    $items   = $printData['material_items'];

    $dateFormat = config('sys_default.dateFormat', 'd/m/Y');
    $fmtDate = function($d) use ($dateFormat) {
        return $d ? date($dateFormat, strtotime($d)) : '—';
    };
    $fmtQty = function($v) {
        return rtrim(rtrim(number_format((float)$v, 4, '.', ','), '0'), '.');
    };
    $e = function($v) {
        return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
    };

    $statusLabels = [
        'draft'         => 'Draft',
        'confirmed'     => 'Confirmed',
        'in_production' => 'In Production',
        'completed'     => 'Completed',
        'cancelled'     => 'Cancelled',
    ];
    $statusLabel = $statusLabels[$mo['status']] ?? ucfirst($mo['status']);
    $statusClass = 'badge-' . $mo['status'];
?>

<!-- Title -->
<div class="doc-title">Material Requirement Sheet</div>
<div class="doc-ref">MO Number: <strong><?= $e($mo['mo_number']) ?></strong></div>
<div class="doc-status"><span class="status-badge <?= $e($statusClass) ?>"><?= $e($statusLabel) ?></span></div>

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
            <div class="meta-label">Planned Qty</div>
            <div class="meta-val"><?= $e($fmtQty($mo['planned_qty'])) ?></div>
        </td>
    </tr>
    <tr>
        <td>
            <div class="meta-label">Source Location</div>
            <div class="meta-val"><?= $e($mo['source_location_name']) ?></div>
        </td>
        <td>
            <div class="meta-label">Destination</div>
            <div class="meta-val"><?= $e($mo['destination_location_name']) ?></div>
        </td>
        <td>
            <?php if (!empty($mo['planned_date'])): ?>
            <div class="meta-label">Scheduled Date</div>
            <div class="meta-val"><?= $e($fmtDate($mo['planned_date'])) ?></div>
            <?php endif; ?>
            <?php if ($mo['produced_qty'] > 0): ?>
            <div class="meta-label" style="margin-top:6px;">Produced Qty</div>
            <div class="meta-val"><?= $e($fmtQty($mo['produced_qty'])) ?></div>
            <?php endif; ?>
        </td>
    </tr>
</table>

<!-- Materials table -->
<table class="items-table">
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:34%">Item</th>
            <th style="width:13%">SKU</th>
            <th style="width:10%">Tracking</th>
            <th class="text-right" style="width:13%">Required Qty</th>
            <th class="text-right" style="width:13%">Allocated Qty</th>
            <th class="text-right" style="width:13%">Remaining Qty</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($items as $i => $item): ?>
        <?php
            $isAllocated = $item['remaining_qty'] <= 0;
            $isPartial   = !$isAllocated && $item['allocated_qty'] > 0;
            $allocClass  = $isAllocated ? 'alloc-full' : ($isPartial ? 'alloc-partial' : 'alloc-none');
            $trackLabel  = $item['tracking_method'] === 'serial' ? 'Serial' : ($item['tracking_method'] === 'lot' ? 'Lot' : 'Qty');
        ?>
        <tr>
            <td><?= $i + 1 ?></td>
            <td><span class="item-name"><?= $e($item['product_name']) ?></span></td>
            <td style="color:#6b7280;font-size:8.5pt;"><?= $e($item['sku'] ?: '—') ?></td>
            <td style="color:#6b7280;font-size:8.5pt;"><?= $e($trackLabel) ?></td>
            <td class="text-right">
                <?= $e($fmtQty($item['planned_qty'])) ?>
                <?php if (!empty($item['uom_code'])): ?><span style="font-size:7.5pt;font-weight:600;"> <?= $e($item['uom_code']) ?></span><?php endif; ?>
            </td>
            <td class="text-right <?= $e($allocClass) ?>">
                <?= $e($fmtQty($item['allocated_qty'])) ?>
                <?php if (!empty($item['uom_code'])): ?><span style="font-size:7.5pt;font-weight:600;"> <?= $e($item['uom_code']) ?></span><?php endif; ?>
            </td>
            <td class="text-right <?= $e($allocClass) ?>">
                <?= $e($fmtQty($item['remaining_qty'])) ?>
                <?php if (!empty($item['uom_code'])): ?><span style="font-size:7.5pt;font-weight:600;"> <?= $e($item['uom_code']) ?></span><?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<div class="footer-note">
    <?= count($items) ?> item(s) &nbsp;&middot;&nbsp; Printed on <?= date($dateFormat) ?>
</div>

</body>
</html>
