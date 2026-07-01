@extends('layouts.app')
@section('title', 'Profit Margin Report')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Profit Margin Report</h4>
            <p class="text-muted mb-0 small">Sales revenue, cost and margin analysis by order line</p>
        </div>
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-end gap-3">

                {{-- Order Date preset --}}
                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-200">
                        <label class="form-label mb-1 small fw-medium">Order Date</label>
                        <select id="filterDatePreset" class="form-select form-select-sm">
                            <option value="this_month" selected>This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="last_3_months">Last 3 Months</option>
                            <option value="this_year">This Year</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filterDateRangeWrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filterDateRange" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>
                </div>

                {{-- Product --}}
                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Product</label>
                    <select class="form-select form-select-sm" id="filterProduct">
                        <option value="">All products</option>
                    </select>
                </div>

                {{-- Customer --}}
                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Customer</label>
                    <select class="form-select form-select-sm" id="filterCustomer">
                        <option value="">All customers</option>
                    </select>
                </div>

                @if(in_array($scope, ['team', 'all']))
                {{-- Salesperson --}}
                <div class="w-px-200">
                    <label class="form-label mb-1 small fw-medium">Salesperson</label>
                    <select class="form-select form-select-sm" id="filterSalesperson">
                        <option value="">All salespersons</option>
                        @foreach($salespersonOptions as $sp)
                        <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                {{-- Actions --}}
                <div class="d-flex gap-2">
                    <button type="button" id="runReportBtn" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetReportBtn" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>

            </div>
        </div>
    </div>

    {{-- Summary KPIs --}}
    <div class="row g-3 mb-4" id="reportKpis" style="display:none;">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">Revenue</div>
                    <div class="fw-bold h5 mb-0" id="kpiRevenue">—</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">COGS</div>
                    <div class="fw-bold h5 mb-0" id="kpiCogs">—</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">Gross Margin</div>
                    <div class="fw-bold h5 mb-0" id="kpiMargin">—</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body text-center">
                    <div class="text-muted small mb-1">Margin %</div>
                    <div class="fw-bold h5 mb-0" id="kpiMarginPct">—</div>
                </div>
            </div>
        </div>
    </div>

    {{-- Data table --}}
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered table-sm" id="profitMarginTable" style="width:100%">
                <thead>
                    <tr>
                        <th>Order Date</th>
                        <th>SO#</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>SKU</th>
                        <th class="text-end">Net Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Unit Cost</th>
                        <th class="text-end">Revenue</th>
                        <th class="text-end">COGS</th>
                        <th class="text-end">Gross Margin</th>
                        <th class="text-end">Margin %</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
'use strict';

const reportScope = '{{ $scope }}';
const localDate   = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

const fmtCurrency = v => formatCurrency(parseFloat(v) || 0);
const fmtQty      = v => formatQty(parseFloat(v) || 0);
const fmtPct      = v => { const n = parseFloat(v); return (isNaN(n) || !isFinite(n)) ? '—' : n.toFixed(2) + '%'; };

let reportFilters = {
    date_preset:    'this_month',
    date_from:      '',
    date_to:        '',
    product_id:     '',
    customer_id:    '',
    salesperson_id: '',
};

let reportDt;

const reportDtOptions = {
    ajax: {
        url: '/api/reports/profit-margin',
        data: function(d) {
            d.date_preset    = reportFilters.date_preset;
            d.date_from      = reportFilters.date_from;
            d.date_to        = reportFilters.date_to;
            d.product_id     = reportFilters.product_id;
            d.customer_id    = reportFilters.customer_id;
            d.salesperson_id = reportFilters.salesperson_id;
        },
        dataSrc: function(json) {
            const totals      = (json.data && json.data.totals) ? json.data.totals : {};
            const revenue     = parseFloat(totals.revenue)      || 0;
            const cogs        = parseFloat(totals.cogs)         || 0;
            const grossMargin = parseFloat(totals.gross_margin) || 0;
            const marginPct   = revenue > 0 ? (grossMargin / revenue) * 100 : 0;

            document.getElementById('kpiRevenue').textContent   = fmtCurrency(revenue);
            document.getElementById('kpiCogs').textContent      = fmtCurrency(cogs);
            document.getElementById('kpiMargin').textContent    = fmtCurrency(grossMargin);
            document.getElementById('kpiMarginPct').textContent = fmtPct(marginPct);
            document.getElementById('reportKpis').style.display = '';

            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            data: 'order_date',
            render: function(d) {
                return d ? formatMySqlDate(d + ' 00:00:00', window.sysDefaultConfig.dateFormat, '—') : '—';
            }
        },
        {
            data: 'so_number',
            render: function(d, t, row) {
                return `<a href="/sales/orders/${row.so_id}/">${d || '—'}</a>`;
            }
        },
        { data: 'customer_name', defaultContent: '—' },
        { data: 'product_name',  defaultContent: '—' },
        { data: 'product_sku',   defaultContent: '—' },
        {
            data: 'net_qty',
            render: function(d, t, row) { return `${fmtQty(d)} ${row.uom_code || ''}`; },
            className: 'text-end'
        },
        {
            data: 'unit_price',
            render: function(d) { return fmtCurrency(d); },
            className: 'text-end'
        },
        {
            data: 'unit_cost',
            render: function(d) { return (d !== null && d !== undefined) ? fmtCurrency(d) : '<span class="text-muted">—</span>'; },
            className: 'text-end'
        },
        {
            data: 'revenue',
            render: function(d) { return fmtCurrency(d); },
            className: 'text-end'
        },
        {
            data: 'cogs',
            render: function(d) { return fmtCurrency(d); },
            className: 'text-end'
        },
        {
            data: 'gross_margin',
            render: function(d) {
                const cls = parseFloat(d) < 0 ? 'text-danger' : '';
                return `<span class="${cls}">${fmtCurrency(d)}</span>`;
            },
            className: 'text-end fw-medium'
        },
        {
            data: 'margin_pct',
            render: function(d) {
                if (d === null || d === undefined) return '—';
                const cls = d < 0 ? 'text-danger' : d < 10 ? 'text-warning' : 'text-success';
                return `<span class="${cls}">${fmtPct(d)}</span>`;
            },
            className: 'text-end fw-medium'
        },
    ],
    order: [[0, 'desc']],
};

jQuery(document).ready(function () {

    initSelect2('#filterDatePreset', {
        minimumResultsForSearch: Infinity,
        allowClear: false,
        resetVal: false,
        width: 'resolve',
        onChange: function(el) {
            const val      = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filterDateRangeWrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filterDateRange')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filterDateRange', { mode: 'range', static: false });

    initSelect2('#filterProduct', {
        placeholder: 'All products',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '/api/products/search',
            dataType: 'json',
            delay: 300,
            data: function(params) { return { q: params.term || '' }; },
            processResults: function(data) {
                return { results: (data.data || []).map(function(p) { return { id: p.id, text: p.name }; }) };
            },
        },
    });

    initSelect2('#filterCustomer', {
        placeholder: 'All customers',
        allowClear: true,
        minimumInputLength: 2,
        ajax: {
            url: '/api/customers/search',
            dataType: 'json',
            delay: 300,
            data: function(params) { return { q: params.term || '' }; },
            processResults: function(data) {
                return { results: (data.data || []).map(function(c) { return { id: c.id, text: c.display_name }; }) };
            },
        },
    });

    if (['team', 'all'].includes(reportScope)) {
        initSelect2('#filterSalesperson', { minimumResultsForSearch: 5, width: 'resolve' });
    }

    reportDt = initDataTable('#profitMarginTable', reportDtOptions);

    document.getElementById('runReportBtn').addEventListener('click', function() {
        const preset = $('#filterDatePreset').val() || 'this_month';

        reportFilters.date_preset    = preset;
        reportFilters.date_from      = '';
        reportFilters.date_to        = '';
        reportFilters.product_id     = $('#filterProduct').val()  || '';
        reportFilters.customer_id    = $('#filterCustomer').val() || '';

        if (preset === 'custom') {
            const fp = document.getElementById('filterDateRange')._flatpickr;
            if (fp && fp.selectedDates.length >= 2) {
                reportFilters.date_from = localDate(fp.selectedDates[0]);
                reportFilters.date_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
            }
        }

        const spEl = document.getElementById('filterSalesperson');
        if (spEl) reportFilters.salesperson_id = spEl.value || '';

        reportDt.ajax.reload();
    });

    document.getElementById('resetReportBtn').addEventListener('click', function() {
        $('#filterDatePreset').val('this_month').trigger('change');
        $('#filterProduct').val(null).trigger('change');
        $('#filterCustomer').val(null).trigger('change');
        const spEl = document.getElementById('filterSalesperson');
        if (spEl) $('#filterSalesperson').val('').trigger('change');

        reportFilters = {
            date_preset:    'this_month',
            date_from:      '',
            date_to:        '',
            product_id:     '',
            customer_id:    '',
            salesperson_id: '',
        };

        reportDt.ajax.reload();
    });
});
</script>
@endpush
