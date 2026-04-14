<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Sales Order {{ $printData['so']['so_number'] ?? '' }}</title>
<style>
    * { box-sizing: border-box; margin: 0; padding: 0; }

    body {
        font-family: Arial, Helvetica, sans-serif;
        font-size: 13px;
        color: #333;
        background: #fff;
        padding: 32px 40px;
    }

    /* ── Header ── */
    .doc-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 28px;
        padding-bottom: 20px;
        border-bottom: 2px solid #2254DD;
    }
    .company-logo {
        width: 120px;
        height: 60px;
        object-fit: contain;
    }
    .company-logo-placeholder {
        width: 120px;
        height: 60px;
        background: #f0f0f0;
        border: 1px dashed #ccc;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        color: #aaa;
    }
    .company-info {
        text-align: right;
        font-size: 12px;
        color: #555;
        line-height: 1.6;
    }
    .company-info strong {
        font-size: 15px;
        color: #333;
        display: block;
        margin-bottom: 4px;
    }

    /* ── Document title block ── */
    .doc-title-block {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 24px;
    }
    .doc-title {
        font-size: 22px;
        font-weight: bold;
        color: #2254DD;
        letter-spacing: 1px;
        text-transform: uppercase;
    }
    .doc-meta {
        text-align: right;
        font-size: 12px;
        line-height: 1.8;
    }
    .doc-meta strong {
        font-size: 14px;
        color: #333;
    }
    .doc-meta .label {
        color: #888;
        margin-right: 6px;
    }

    /* ── Address block ── */
    .address-block {
        display: flex;
        gap: 40px;
        margin-bottom: 24px;
        padding: 14px 16px;
        background: #fafafa;
        border: 1px solid #e8e8e8;
        border-radius: 4px;
    }
    .address-col h6 {
        font-size: 11px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #2254DD;
        margin-bottom: 6px;
        font-weight: bold;
    }
    .address-col p {
        font-size: 12px;
        color: #444;
        line-height: 1.6;
    }

    /* ── Line items table ── */
    .items-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 0;
    }
    .items-table thead tr {
        background: #2254DD;
        color: #fff;
    }
    .items-table thead th {
        padding: 9px 10px;
        font-size: 12px;
        font-weight: 600;
        text-align: left;
        white-space: nowrap;
    }
    .items-table thead th.text-right { text-align: right; }
    .items-table tbody tr {
        border-bottom: 1px solid #eee;
    }
    .items-table tbody tr:last-child {
        border-bottom: 1px solid #ccc;
    }
    .items-table tbody td {
        padding: 8px 10px;
        vertical-align: top;
        font-size: 12px;
    }
    .items-table tbody td.text-right { text-align: right; }
    .item-product { font-weight: 600; }
    .item-desc { font-size: 11px; color: #777; margin-top: 2px; }
    .items-table tbody tr:nth-child(even) { background: #f0f4ff; }

    /* ── Totals ── */
    .totals-section {
        display: flex;
        justify-content: flex-end;
        margin-top: 0;
        margin-bottom: 24px;
        border-top: none;
    }
    .totals-table {
        width: 260px;
        border-collapse: collapse;
    }
    .totals-table td {
        padding: 6px 10px;
        font-size: 12px;
    }
    .totals-table td:last-child { text-align: right; }
    .totals-table .label-col { color: #666; }
    .totals-table tr.grand-total {
        border-top: 2px solid #2254DD;
    }
    .totals-table tr.grand-total td {
        font-size: 14px;
        font-weight: bold;
        color: #333;
        padding-top: 8px;
    }

    /* ── Footer notes ── */
    .doc-footer {
        border-top: 1px solid #eee;
        padding-top: 14px;
        font-size: 11px;
        color: #666;
        line-height: 1.7;
    }
    .doc-footer .footer-label {
        font-weight: bold;
        color: #555;
        text-transform: uppercase;
        font-size: 10px;
        letter-spacing: 0.5px;
        margin-bottom: 3px;
    }
</style>
</head>
<body>

@php
    $so      = $printData['so'];
    $company = $printData['company'];
    $billing = $printData['billing_address'] ?? [];
    $items   = $printData['line_items'];
    $dateFormat = config('sys_default.dateFormat', 'd/m/Y');

    $fmtDate = fn($d) => $d ? date($dateFormat, strtotime($d)) : '-';
    $fmtCurr = fn($v) => '₹' . number_format((float)$v, 2);
@endphp

{{-- ── Header ── --}}
<div class="doc-header">
    <div>
        {{-- Logo placeholder — replace with actual logo once stored --}}
        <div class="company-logo-placeholder">No Logo</div>
    </div>
    <div class="company-info">
        <strong>{{ $company['name'] ?? '' }}</strong>
        @if(!empty($company['address'])){{ $company['address'] }}<br>@endif
        @if(!empty($company['city']) || !empty($company['state'])){{ trim(($company['city'] ?? '') . ', ' . ($company['state'] ?? ''), ', ') }}<br>@endif
        @if(!empty($company['country'])){{ $company['country'] }}&nbsp;{{ $company['zipcode'] ?? '' }}<br>@endif
        @if(!empty($company['phone']))Phone: {{ $company['phone'] }}<br>@endif
        @if(!empty($company['email'])){{ $company['email'] }}<br>@endif
    </div>
</div>

{{-- ── Document title block ── --}}
<div class="doc-title-block">
    <div class="doc-title">Sales Order</div>
    <div class="doc-meta">
        <div><strong>{{ $so['so_number'] }}</strong></div>
        <div><span class="label">Order Date:</span>{{ $fmtDate($so['order_date']) }}</div>
        @if(!empty($so['expected_delivery_date']))
        <div><span class="label">Expected Delivery:</span>{{ $fmtDate($so['expected_delivery_date']) }}</div>
        @endif
        @if(!empty($printData['salesperson']))
        <div><span class="label">Salesperson:</span>{{ $printData['salesperson'] }}</div>
        @endif
        @if(!empty($so['reference']))
        <div><span class="label">Reference:</span>{{ $so['reference'] }}</div>
        @endif
    </div>
</div>

{{-- ── Address block ── --}}
<div class="address-block">
    <div class="address-col">
        <h6>Bill To</h6>
        <p>
            <strong>{{ $printData['customer']['name'] ?? '' }}</strong><br>
            @if(!empty($billing['attention'])){{ $billing['attention'] }}<br>@endif
            @if(!empty($billing['address_line1'])){{ $billing['address_line1'] }}<br>@endif
            @if(!empty($billing['address_line2'])){{ $billing['address_line2'] }}<br>@endif
            @php
                $cityState = trim(($billing['city'] ?? '') . ', ' . ($billing['state'] ?? ''), ', ');
            @endphp
            @if($cityState){{ $cityState }}<br>@endif
            @if(!empty($billing['postal_code'])){{ $billing['postal_code'] }}<br>@endif
            @if(!empty($billing['country'])){{ $billing['country'] }}<br>@endif
            @if(!empty($billing['phone'])){{ $billing['phone'] }}<br>@endif
        </p>
    </div>
    @if(!empty($so['payment_terms']))
    <div class="address-col">
        <h6>Payment Terms</h6>
        <p>{{ $so['payment_terms'] }}</p>
    </div>
    @endif
</div>

{{-- ── Line items ── --}}
<table class="items-table">
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:36%">Product</th>
            <th class="text-right" style="width:9%">Qty</th>
            <th class="text-right" style="width:14%">Unit Price</th>
            <th class="text-right" style="width:12%">Discount</th>
            <th style="width:12%">Taxes</th>
            <th class="text-right" style="width:13%">Amount</th>
        </tr>
    </thead>
    <tbody>
        @foreach($items as $i => $item)
        <tr>
            <td>{{ $i + 1 }}</td>
            <td>
                <div class="item-product">{{ $item['product_name'] }}</div>
                @if(!empty($item['description']))<div class="item-desc">{{ $item['description'] }}</div>@endif
            </td>
            <td class="text-right">{{ $item['qty'] }}{{ !empty($item['uom_code']) ? ' ' . $item['uom_code'] : '' }}</td>
            <td class="text-right">{{ $fmtCurr($item['unit_price']) }}</td>
            <td class="text-right">{{ $fmtCurr($item['discount']) }}</td>
            <td>{{ $item['tax_label'] ?: '-' }}</td>
            <td class="text-right">{{ $fmtCurr($item['line_total']) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ── Totals ── --}}
<div class="totals-section">
    <table class="totals-table">
        <tr>
            <td class="label-col">Subtotal</td>
            <td>{{ $fmtCurr($so['subtotal']) }}</td>
        </tr>
        @if($so['discount_amount'] > 0)
        <tr>
            <td class="label-col">Discount</td>
            <td>- {{ $fmtCurr($so['discount_amount']) }}</td>
        </tr>
        @endif
        @if($so['tax_amount'] > 0)
        <tr>
            <td class="label-col">Tax</td>
            <td>{{ $fmtCurr($so['tax_amount']) }}</td>
        </tr>
        @endif
        <tr class="grand-total">
            <td>Total</td>
            <td>{{ $fmtCurr($so['total_amount']) }}</td>
        </tr>
    </table>
</div>

{{-- ── Footer notes ── --}}
@if(!empty($so['notes']))
<div class="doc-footer">
    <div class="footer-label">Notes</div>
    <div>{{ $so['notes'] }}</div>
</div>
@endif

</body>
</html>
