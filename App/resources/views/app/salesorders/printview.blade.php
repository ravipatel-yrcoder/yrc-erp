<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>{{ $printData['so']['so_number'] ?? '' }}</title>
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
        align-items: center;
        margin-bottom: 28px;
        padding-bottom: 20px;
        border-bottom: 2px solid #2254DD;
    }
    .company-logo {
        width: auto;
        height: 100%;
        max-height: 70px;
        object-fit: contain;
    }
    .company-logo-placeholder {
        width: 120px;
        height: 50px;
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
    .company-info .company-name {
        font-size: 15px;
        font-weight: bold;
        color: #333;
        display: block;
        margin-bottom: 4px;
    }

    /* ── 3-column info block (title+meta | bill to | ship to) ── */
    .doc-info-block {
        display: flex;
        margin-bottom: 24px;
        border: 1px solid #e0e8f0;
        border-radius: 4px;
        overflow: hidden;
    }
    .doc-info-col {
        flex: 1;
        padding: 14px 16px;
        border-right: 1px solid #e0e8f0;
        font-size: 12px;
        color: #444;
        line-height: 1.6;
    }
    .doc-info-col:last-child {
        border-right: none;
    }
    .doc-info-col--title {
        background: #f5f8ff;
    }
    .doc-title {
        font-size: 20px;
        font-weight: bold;
        color: #2254DD;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-bottom: 10px;
    }
    .doc-meta-list {
        font-size: 12px;
        line-height: 1.9;
    }
    .doc-meta-list .meta-label {
        color: #888;
        margin-right: 4px;
        display: inline-block;
        min-width: 72px;
    }
    .doc-meta-list .meta-val strong {
        font-size: 13px;
        color: #333;
    }
    .info-col-label {
        font-size: 10px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #2254DD;
        margin-bottom: 6px;
        font-weight: bold;
    }
    .info-col-name {
        font-weight: 600;
        color: #333;
        font-size: 13px;
        margin-bottom: 2px;
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

    /* ── Signature block ── */
    .signature-block {
        margin-top: 32px;
        display: flex;
        justify-content: flex-end;
    }
    .signature-inner {
        text-align: center;
        min-width: 180px;
    }
    .signature-inner img {
        max-height: 60px;
        max-width: 200px;
        object-fit: contain;
        display: block;
        margin: 0 auto 6px;
    }
    .signature-line {
        border-top: 1px solid #333;
        padding-top: 4px;
        font-size: 11px;
        color: #555;
    }
</style>
</head>
<body>

@php
    $so       = $printData['so'];
    $company  = $printData['company'];
    $billing  = $printData['billing_address'] ?? [];
    $shipping = $printData['shipping_address'] ?? [];
    $items    = $printData['line_items'];
    $dateFormat = config('sys_default.dateFormat', 'd/m/Y');

    $fmtDate  = fn($d) => $d ? date($dateFormat, strtotime($d)) : '-';
    $fmtCurr  = fn($v) => '₹' . number_format((float)$v, 2);

    $isQuotationDoc     = (($so['origin_type'] ?? 'order') === 'quotation');
    $docTitle           = $isQuotationDoc ? 'Quotation' : 'Sales Order';
    $companyDisplayName = !empty($company['legal_name']) ? $company['legal_name'] : ($company['name'] ?? '');
@endphp

{{-- ── Header ── --}}
<div class="doc-header">
    <div>
        @if(!empty($company['logo_path']))
            <img class="company-logo" src="{{ $company['logo_path'] }}" alt="Logo">
        @else
            <div class="company-logo-placeholder">No Logo</div>
        @endif
    </div>
    <div class="company-info">
        <span class="company-name">{{ $companyDisplayName }}</span>
        @if(!empty($company['address'])){{ $company['address'] }}<br>@endif
        @if(!empty($company['city']) || !empty($company['state'])){{ trim(($company['city'] ?? '') . ', ' . ($company['state'] ?? ''), ', ') }}<br>@endif
        @if(!empty($company['country'])){{ $company['country'] }}&nbsp;{{ $company['zipcode'] ?? '' }}<br>@endif
        @if(!empty($company['gstin']))<span style="font-size:11px;">GSTIN: {{ $company['gstin'] }}</span><br>@endif
        @if(!empty($company['pan']))<span style="font-size:11px;">PAN: {{ $company['pan'] }}</span><br>@endif
    </div>
</div>

{{-- ── 3-column info block ── --}}
<div class="doc-info-block">

    {{-- Column 1: Document title + order meta --}}
    <div class="doc-info-col doc-info-col--title">
        <div class="doc-title">{{ $docTitle }}</div>
        <div class="doc-meta-list">
            <div><span class="meta-label">Number:</span><span class="meta-val"><strong>{{ $so['so_number'] }}</strong></span></div>
            @if($isQuotationDoc)
            <div><span class="meta-label">Date:</span><span class="meta-val">{{ $fmtDate($so['quote_date']) }}</span></div>
            @if(!empty($so['valid_until']))
            <div><span class="meta-label">Valid Until:</span><span class="meta-val">{{ $fmtDate($so['valid_until']) }}</span></div>
            @endif
            @if(!empty($so['converted_at']))
            <div><span class="meta-label">Order Date:</span><span class="meta-val">{{ $fmtDate($so['order_date']) }}</span></div>
            @endif
            @else
            <div><span class="meta-label">Date:</span><span class="meta-val">{{ $fmtDate($so['order_date']) }}</span></div>
            @endif
            @if(!empty($so['reference']))
            <div><span class="meta-label">Reference:</span><span class="meta-val">{{ $so['reference'] }}</span></div>
            @endif
        </div>
    </div>

    {{-- Column 2: Bill To --}}
    <div class="doc-info-col">
        <div class="info-col-label">Bill To</div>
        <div class="info-col-name">{{ !empty($billing['attention']) ? $billing['attention'] : ($printData['customer']['name'] ?? '') }}</div>
        @if(!empty($billing['address_line1']))<div>{{ $billing['address_line1'] }}</div>@endif
        @if(!empty($billing['address_line2']))<div>{{ $billing['address_line2'] }}</div>@endif
        @php $billingCityState = trim(($billing['city'] ?? '') . ', ' . ($billing['state'] ?? ''), ', '); @endphp
        @if($billingCityState)<div>{{ $billingCityState }}</div>@endif
        @if(!empty($billing['postal_code']))<div>{{ $billing['postal_code'] }}</div>@endif
        @if(!empty($billing['country']))<div>{{ $billing['country'] }}</div>@endif
        @if(!empty($billing['phone']))<div>{{ $billing['phone'] }}</div>@endif
    </div>

    {{-- Column 3: Ship To (only when delivery_type is shipment) --}}
    @if($so['delivery_type'] === 'ship')
    <div class="doc-info-col">
        <div class="info-col-label">Ship To</div>
        @if(!empty($shipping))
            <div class="info-col-name">{{ !empty($shipping['attention']) ? $shipping['attention'] : ($printData['customer']['name'] ?? '') }}</div>
            @if(!empty($shipping['address_line1']))<div>{{ $shipping['address_line1'] }}</div>@endif
            @if(!empty($shipping['address_line2']))<div>{{ $shipping['address_line2'] }}</div>@endif
            @php $shippingCityState = trim(($shipping['city'] ?? '') . ', ' . ($shipping['state'] ?? ''), ', '); @endphp
            @if($shippingCityState)<div>{{ $shippingCityState }}</div>@endif
            @if(!empty($shipping['postal_code']))<div>{{ $shipping['postal_code'] }}</div>@endif
            @if(!empty($shipping['country']))<div>{{ $shipping['country'] }}</div>@endif
            @if(!empty($shipping['phone']))<div>{{ $shipping['phone'] }}</div>@endif
        @else
            <div style="color:#aaa;font-size:11px;font-style:italic;">No address provided</div>
        @endif
    </div>
    @endif

</div>

{{-- ── Line items ── --}}
<table class="items-table">
    <thead>
        <tr>
            <th style="width:4%">#</th>
            <th style="width:30%">Product</th>
            <th class="text-right" style="width:8%">Qty</th>
            <th class="text-right" style="width:11%">Unit Price</th>
            <th class="text-right" style="width:10%">Discount</th>
            <th style="width:10%">Taxes</th>
            <th class="text-right" style="width:12%">Taxable Value</th>
            <th class="text-right" style="width:11%">Amount</th>
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
            <td class="text-right">{{ $fmtCurr($item['taxable_amount']) }}</td>
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
        @if($so['item_discount_total'] > 0)
        <tr>
            <td class="label-col">Item Discounts</td>
            <td>- {{ $fmtCurr($so['item_discount_total']) }}</td>
        </tr>
        @endif
        @if($so['order_discount_total'] > 0)
        <tr>
            <td class="label-col">Order Discount</td>
            <td>- {{ $fmtCurr($so['order_discount_total']) }}</td>
        </tr>
        @endif
        @if($so['tax_amount'] > 0)
        <tr>
            <td class="label-col">Tax</td>
            <td>{{ $fmtCurr($so['tax_amount']) }}</td>
        </tr>
        @endif
        @if(!empty($so['adjustment_amount']) && $so['adjustment_amount'] != 0)
        @php $adjSign = $so['adjustment_amount'] > 0 ? '+' : '-'; @endphp
        <tr>
            <td class="label-col">{{ $so['adjustment_label'] ?: 'Adjustment' }}</td>
            <td>{{ $adjSign }}{{ $fmtCurr(abs($so['adjustment_amount'])) }}</td>
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

{{-- ── Signature ── --}}
@if(!empty($company['signature_path']))
<div class="signature-block">
    <div class="signature-inner">
        <img src="{{ $company['signature_path'] }}" alt="Signature">
        <div class="signature-line">Authorised Signatory</div>
    </div>
</div>
@endif

</body>
</html>
