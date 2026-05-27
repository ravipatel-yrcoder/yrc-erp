@extends('layouts.app')
@section('title', 'Quotations')

@section('content')
<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Quotations</h4>
            <p class="text-muted mb-0 small">Manage your quotations</p>
        </div>
        @if(tenantContext()->canDo('sales_orders', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" id="createQuotation"><i class="icon-base bx bx-plus icon-sm"></i> Add New</button>
        </div>
        @endif
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-end gap-3">

                <!-- Status -->
                <div class="w-px-150">
                    <label class="form-label mb-1 small fw-medium">Status</label>
                    <select id="filter_quote_status" class="form-select form-select-sm" style="width:160px">
                        <option value="">All</option>
                        <option value="open">Open</option>
                        <option value="converted">Converted</option>
                    </select>
                </div>

                <!-- Expiry -->
                <div class="w-px-200">
                    <label class="form-label mb-1 small fw-medium">Expiry</label>
                    <select id="filter_quote_expiry" class="form-select form-select-sm" style="width:180px">
                        <option value="">All</option>
                        <option value="expired">Expired</option>
                        <option value="today">Expiring Today</option>
                        <option value="soon">Expiring (7 days)</option>
                    </select>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2">
                    <button type="button" id="applyQuoteFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetQuoteFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>

            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="quotations_table">
                <thead>
                    <tr>
                        <th>SO#</th>
                        <th>Quote Date</th>
                        <th>Valid Until</th>
                        <th>Customer</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Total</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@if(tenantContext()->canDo('sales_orders', 'write'))
@includeOnce('app.components.drawers.sales-orders.add-edit')
@endif
@if(tenantContext()->canDo('customers', 'write'))
@includeOnce('app.components.drawers.customers.add-edit')
@endif

@endsection

@push('scripts')
<script>
const leadId = '{{ $leadId }}';
const _urlParams = new URLSearchParams(window.location.search);
const _initialExpiry = _urlParams.get('expiry') || '';
let quoteFilters = { quote_status: 'open', quote_expiry: _initialExpiry };
let quotationsDt;

const buildQuotationsDtUrl = function() {
    let url = '/api/sales/quotations';
    const params = [];
    if (leadId && leadId != '0') params.push(`lead_id=${leadId}`);
    if (quoteFilters.quote_status) params.push(`filter_quote_status=${quoteFilters.quote_status}`);
    if (quoteFilters.quote_expiry) params.push(`filter_quote_expiry=${quoteFilters.quote_expiry}`);
    return params.length ? url + '?' + params.join('&') : url;
};

const quotationStatusMap = {
    draft:                ['Open',               'warning'],
    confirmed:            ['Confirmed',           'primary'],
    cancelled:            ['Cancelled',           'danger'],
    partially_dispatched: ['Partially Dispatched','info'],
    dispatched:           ['Dispatched',          'primary'],
    partially_delivered:  ['Partially Delivered', 'info'],
    delivered:            ['Delivered',           'success'],
};

const quotationsDtOptions = {
    order: [[8, 'desc']],
    ajax: {
        url: buildQuotationsDtUrl(),
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            'data': 'so_number',
            'render': function(data, type, row) {
                return `<a href="/sales/orders/${row.id}/" class="text-primary fw-medium">${data}</a>`;
            }
        },
        {
            'data': 'quote_date',
            'render': function(data) {
                return data ? formatMySqlDate(data, window.sysDefaultConfig.dateFormat) : '-';
            }
        },
        {
            'data': 'valid_until',
            'defaultContent': '-',
            'render': function(data) {
                return data ? formatMySqlDate(data, window.sysDefaultConfig.dateFormat) : '-';
            }
        },
        {'data': 'customer', 'defaultContent': '-'},
        {'data': 'reference', 'defaultContent': '-'},
        {
            'data': 'status',
            'render': function(data) {
                const s = quotationStatusMap[data] || [data, 'secondary'];
                // Show "Sent" badge for open quotes that have been emailed
                return `<span class="badge bg-label-${s[1]}">${s[0]}</span>`;
            }
        },
        {
            'data': 'grand_total',
            'render': function(data) { return formatCurrency(data); }
        },
        {'data': 'created_by_name', 'defaultContent': '-'},
        {
            'data': 'id',
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                const editBtn = (row.status === 'draft' && canDo('sales_orders', 'write'))
                    ? `<a href="javascript:void(0);" onClick="openSalesOrderFormDrawer(${data}, {mode: 'lead_quotation', leadId: 0})" class="btn text-warning btn-icon item-edit" title="Edit quotation"><i class="icon-base bx bxs-edit"></i></a>`
                    : '';
                return (
                    '<div class="d-inline-block">' +
                        editBtn +
                        '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>' +
                        '<ul class="dropdown-menu dropdown-menu-end">' +
                            `<li><a href="/sales/orders/${data}/" class="dropdown-item">View Details</a></li>` +
                        '</ul>' +
                    '</div>'
                );
            }
        }
    ]
};

document.getElementById('applyQuoteFilters').addEventListener('click', function() {
    quoteFilters.quote_status = document.getElementById('filter_quote_status').value || '';
    quoteFilters.quote_expiry = document.getElementById('filter_quote_expiry').value || '';
    quotationsDt.ajax.url(buildQuotationsDtUrl()).load();
});

document.getElementById('resetQuoteFilters').addEventListener('click', function() {
    $('#filter_quote_status').val('open').trigger('change');
    $('#filter_quote_expiry').val('').trigger('change');
    quoteFilters = { quote_status: 'open', quote_expiry: '' };
    quotationsDt.ajax.url(buildQuotationsDtUrl()).load();
});

jQuery(document).ready(function() {
    initSelect2('#filter_quote_status', {
        placeholder: 'All',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });
    initSelect2('#filter_quote_expiry', {
        placeholder: 'All',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });
    $('#filter_quote_status').val('open').trigger('change');
    if (_initialExpiry) {
        $('#filter_quote_expiry').val(_initialExpiry).trigger('change');
    }

    quotationsDt = initDataTable("#quotations_table", quotationsDtOptions);
});

const createQuotationBtn = document.getElementById('createQuotation');
if (createQuotationBtn) createQuotationBtn.addEventListener('click', function() {
    openSalesOrderFormDrawer(0, {mode: 'lead_quotation', leadId: 0});
});

document.addEventListener('leadQuotationCreated', function(e) {
    quotationsDt.ajax.reload();
});

document.addEventListener('salesOrderFormSaved', function() {
    quotationsDt.ajax.reload(null, false);
});
</script>
@endpush
