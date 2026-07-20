@extends('layouts.app')
@section('title', 'Purchase Orders')

@section('content')
<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Purchase Orders</h4>
            <p class="text-muted mb-0 small">Manage your purchase orders</p>
        </div>
        @if(tenantContext()->canDo('purchase_orders', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" onClick="openPurchaseOrderFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i> Add New</button>
        </div>
        @endif
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-end gap-3">

                <!-- Status -->
                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Status</label>
                    <select id="filter_po_status" class="form-select form-select-sm" multiple>
                        <option value="draft">Draft</option>
                        <option value="rfq_sent">RFQ Sent</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="partially_received">Partially Received</option>
                        <option value="received">Received</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Vendor -->
                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Vendor</label>
                    <select id="filter_po_vendor_id" class="form-select form-select-sm">
                        <option value="">All Vendors</option>
                        @foreach($vendorOptions as $vendor)
                        <option value="{{ $vendor->id }}">{{ $vendor->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Expected Delivery -->
                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-200">
                        <label class="form-label mb-1 small fw-medium">Expected Delivery</label>
                        <select id="filter_po_exp_delivery_preset" class="form-select form-select-sm">
                            <option value="">Any</option>
                            <option value="overdue">Overdue</option>
                            <option value="due_today">Due Today</option>
                            <option value="due_this_week">Due This Week</option>
                            <option value="due_this_month">Due This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filter-po-exp-delivery-range-wrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filter_po_exp_delivery_range" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>
                </div>

                <!-- Order Date -->
                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-200">
                        <label class="form-label mb-1 small fw-medium">Order Date</label>
                        <select id="filter_po_order_date_preset" class="form-select form-select-sm">
                            <option value="">Any Time</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="last_3_months">Last 3 Months</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filter-po-order-date-range-wrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filter_po_order_date_range" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2">
                    <button type="button" id="applyPoFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetPoFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>

            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="purchase_orders_table">
                <thead>
                    <tr>
                        <th width="150px">Order#</th>
                        <th width="125px">Date</th>
                        <th>Vendor</th>
                        <th>Reference#</th>
                        <th class="text-center" width="100px">Status</th>
                        <th width="150px">Exp. Delivery</th>
                        <th>Total</th>
                        <th>Created By</th>
                        <th width="125px" class="text-center">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@if(tenantContext()->canDo('purchase_orders', 'write'))
@includeOnce('app.components.drawers.purchase-orders.add-edit')
@endif
@if(tenantContext()->canDo('vendors', 'write'))
@includeOnce('app.components.drawers.vendors.add-edit')
@endif

@endsection

@push('scripts')
<script>
let poFilters = {
    status:             [],
    vendor_id:          '',
    exp_delivery_preset: '',
    exp_delivery_from:  '',
    exp_delivery_to:    '',
    order_date_preset:  '',
    order_date_from:    '',
    order_date_to:      ''
};

let purchaseOrdersDt;

const initPoFilterControls = function() {

    initSelect2('#filter_po_status', {
        placeholder: 'All',
        multiple: true,
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_po_vendor_id', {
        placeholder: 'All',
        width: 'resolve',
    });

    initSelect2('#filter_po_exp_delivery_preset', {
        placeholder: 'Any',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filter-po-exp-delivery-range-wrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filter_po_exp_delivery_range')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filter_po_exp_delivery_range', { mode: 'range', static: false });

    initSelect2('#filter_po_order_date_preset', {
        placeholder: 'Any',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filter-po-order-date-range-wrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filter_po_order_date_range')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filter_po_order_date_range', { mode: 'range', static: false });
};

const purchaseOrdersDtOptions = {
    order: [[8, 'desc']],
    ajax: {
        url: '/api/purchase/orders',
        data: function(d) {
            d.filter_status              = poFilters.status;
            d.filter_vendor_id           = poFilters.vendor_id;
            d.filter_exp_delivery_preset = poFilters.exp_delivery_preset;
            d.filter_exp_delivery_from   = poFilters.exp_delivery_from;
            d.filter_exp_delivery_to     = poFilters.exp_delivery_to;
            d.filter_order_date_preset   = poFilters.order_date_preset;
            d.filter_order_date_from     = poFilters.order_date_from;
            d.filter_order_date_to       = poFilters.order_date_to;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            'data': 'po_number',
            'render': function(data, type, row) {
                return `<a href="/purchase/orders/${row.id}/">${data}</a>`;
            }
        },
        {
            'data': 'order_date',
            'render': function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {'data': 'vendor'},
        {
            'data': 'reference',
            'render': function(data) {
                return data || "-";
            }
        },
        {
            'data': 'status',
            'class': 'text-center',
            'render': function(data) {
                const statusMap = {
                    draft:              ['Draft',              'warning'],
                    rfq_sent:           ['RFQ Sent',           'info'],
                    confirmed:          ['Confirmed',          'primary'],
                    partially_received: ['Partially Received', 'info'],
                    received:           ['Received',           'success'],
                    cancelled:          ['Cancelled',          'danger'],
                };
                const s = statusMap[data] || [data, 'secondary'];
                return `<span class="badge badge-sm bg-label-${s[1]}">${s[0]}</span>`;
            }
        },
        {
            'data': 'exp_delivery_date',
            'render': function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            'data': 'amount',
            'render': function(data, type, row) {
                const currency = row.currency_code || window.sysDefaultConfig?.currency || 'INR';
                return formatCurrency(data, { currency });
            }
        },
        {'data': 'created_by_name', 'defaultContent': '-'},
        {'data': 'created_at', 'visible': false},
        {
            'data': 'id',
            'class': 'text-center',
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                const editBtn = ((row.status === 'draft' || row.status === 'rfq_sent') && canDo('purchase_orders', 'write'))
                    ? `<a href="javascript:void(0);" onClick="openPurchaseOrderFormDrawer(${data})" class="btn text-warning btn-icon item-edit" title="Edit purchase order"><i class="icon-base bx bxs-edit"></i></a>`
                    : '';
                return (
                    '<div class="d-inline-block">' +
                        editBtn +
                        '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>' +
                        '<ul class="dropdown-menu dropdown-menu-end">' +
                            `<li><a href="/purchase/orders/${data}/" class="dropdown-item">View Details</a></li>` +
                        '</ul>' +
                    '</div>'
                );
            }
        }
    ]
};

document.getElementById('applyPoFilters').addEventListener('click', function() {
    poFilters.status              = $('#filter_po_status').val() || [];
    poFilters.vendor_id           = $('#filter_po_vendor_id').val() || '';
    poFilters.exp_delivery_preset = $('#filter_po_exp_delivery_preset').val() || '';
    poFilters.order_date_preset   = $('#filter_po_order_date_preset').val() || '';

    const localDate = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

    poFilters.exp_delivery_from = '';
    poFilters.exp_delivery_to   = '';
    if (poFilters.exp_delivery_preset === 'custom') {
        const fp = document.getElementById('filter_po_exp_delivery_range')._flatpickr;
        if (fp && fp.selectedDates.length >= 2) {
            poFilters.exp_delivery_from = localDate(fp.selectedDates[0]);
            poFilters.exp_delivery_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
        }
    }

    poFilters.order_date_from = '';
    poFilters.order_date_to   = '';
    if (poFilters.order_date_preset === 'custom') {
        const fp = document.getElementById('filter_po_order_date_range')._flatpickr;
        if (fp && fp.selectedDates.length >= 2) {
            poFilters.order_date_from = localDate(fp.selectedDates[0]);
            poFilters.order_date_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
        }
    }

    purchaseOrdersDt.ajax.reload();
});

document.getElementById('resetPoFilters').addEventListener('click', function() {
    $('#filter_po_status').val([]).trigger('change');
    $('#filter_po_vendor_id').val('').trigger('change');
    $('#filter_po_exp_delivery_preset').val('').trigger('change'); // onChange → hides wrap + clears flatpickr
    $('#filter_po_order_date_preset').val('').trigger('change');   // onChange → hides wrap + clears flatpickr
    poFilters = { status: [], vendor_id: '', exp_delivery_preset: '', exp_delivery_from: '', exp_delivery_to: '', order_date_preset: '', order_date_from: '', order_date_to: '' };
    purchaseOrdersDt.ajax.reload();
});


jQuery(document).ready(function() {
    initPoFilterControls();
    purchaseOrdersDt = initDataTable("#purchase_orders_table", purchaseOrdersDtOptions);
});
</script>
@endpush
