@extends('layouts.app')
@section('title', 'Sales Deliveries')

@section('content')
<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Sales Deliveries</h4>
            <p class="text-muted mb-0 small">Manage your deliveries</p>
        </div>
        <div></div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-end gap-3">

                <!-- Status -->
                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Status</label>
                    <select id="filter_dn_status" class="form-select form-select-sm" multiple>
                        <option value="draft">Draft</option>
                        <option value="dispatched">Dispatched</option>
                        <option value="delivered">Delivered</option>
                        <option value="returned">Returned</option>
                        <option value="lost">Lost</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Customer -->
                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Customer</label>
                    <select id="filter_dn_customer_id" class="form-select form-select-sm">
                        <option value="">All Customers</option>
                        @foreach($customerOptions as $customer)
                        <option value="{{ $customer->id }}">{{ $customer->display_name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Dispatch Date -->
                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-200">
                        <label class="form-label mb-1 small fw-medium">Dispatch Date</label>
                        <select id="filter_dn_dispatch_date_preset" class="form-select form-select-sm">
                            <option value="">Any Time</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="last_3_months">Last 3 Months</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filter-dn-dispatch-date-range-wrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filter_dn_dispatch_date_range" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>
                </div>

                <!-- Delivery Date -->
                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-200">
                        <label class="form-label mb-1 small fw-medium">Delivery Date</label>
                        <select id="filter_dn_delivery_date_preset" class="form-select form-select-sm">
                            <option value="">Any Time</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="last_3_months">Last 3 Months</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filter-dn-delivery-date-range-wrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filter_dn_delivery_date_range" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2">
                    <button type="button" id="applyDnFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetDnFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>

            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="sales_deliveries_table">
                <thead>
                    <tr>
                        <th>DN#</th>
                        <th>SO#</th>
                        <th>Customer</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Dispatch Date</th>
                        <th>Delivery Date</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@if(tenantContext()->canDo('sales_deliveries', 'write'))
@includeOnce('app.components.drawers.sales-deliveries.add-edit')
@endif

@endsection

@push('scripts')
<script>
const dnStatusMap = {
    draft:      ['Draft',      'secondary'],
    dispatched: ['Dispatched', 'primary'],
    delivered:  ['Delivered',  'success'],
    returned:   ['Returned',   'warning'],
    lost:       ['Lost',       'danger'],
    cancelled:  ['Cancelled',  'dark'],
};

let dnFilters = {
    status:                [],
    customer_id:           '',
    dispatch_date_preset:  '',
    dispatch_date_from:    '',
    dispatch_date_to:      '',
    delivery_date_preset:  '',
    delivery_date_from:    '',
    delivery_date_to:      ''
};

let salesDeliveriesDt;

const initDnFilterControls = function() {

    initSelect2('#filter_dn_status', {
        placeholder: 'All Statuses',
        multiple: true,
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_dn_customer_id', {
        placeholder: 'All Customers',
        width: 'resolve',
    });

    initSelect2('#filter_dn_dispatch_date_preset', {
        placeholder: 'Any Time',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filter-dn-dispatch-date-range-wrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filter_dn_dispatch_date_range')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filter_dn_dispatch_date_range', { mode: 'range', static: false });

    initSelect2('#filter_dn_delivery_date_preset', {
        placeholder: 'Any Time',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filter-dn-delivery-date-range-wrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filter_dn_delivery_date_range')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filter_dn_delivery_date_range', { mode: 'range', static: false });
};

const salesDeliveriesDtOptions = {
    order: [[8, 'desc']],
    ajax: {
        url: '/api/sales/deliveries',
        data: function(d) {
            d.filter_status               = dnFilters.status;
            d.filter_customer_id          = dnFilters.customer_id;
            d.filter_dispatch_date_preset = dnFilters.dispatch_date_preset;
            d.filter_dispatch_date_from   = dnFilters.dispatch_date_from;
            d.filter_dispatch_date_to     = dnFilters.dispatch_date_to;
            d.filter_delivery_date_preset = dnFilters.delivery_date_preset;
            d.filter_delivery_date_from   = dnFilters.delivery_date_from;
            d.filter_delivery_date_to     = dnFilters.delivery_date_to;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            'data': 'dn_number',
            'render': function(data, type, row) {
                return `<a href="/sales/deliveries/${row.id}/">${data}</a>`;
            }
        },
        {'data': 'so_number',       'defaultContent': '-'},
        {'data': 'customer',        'defaultContent': '-'},
        {'data': 'location',        'defaultContent': '-'},
        {
            'data': 'status',
            'render': function(data) {
                const s = dnStatusMap[data] || [data, 'secondary'];
                return `<span class="badge badge-sm bg-label-${s[1]}">${s[0]}</span>`;
            }
        },
        {
            'data': 'dispatch_date',
            'defaultContent': '-',
            'render': function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            'data': 'delivery_date',
            'defaultContent': '-',
            'render': function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {'data': 'created_by_name', 'defaultContent': '-'},
        {
            'data': 'id',
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                const editBtn = (row.status === 'draft' && canDo('sales_deliveries', 'write'))
                    ? `<a href="javascript:void(0);" onClick="openDeliveryFormDrawer(${data})" class="btn text-warning btn-icon item-edit" title="Edit delivery note"><i class="icon-base bx bxs-edit"></i></a>`
                    : '';
                return (
                    '<div class="d-inline-block">' +
                        editBtn +
                        '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>' +
                        '<ul class="dropdown-menu dropdown-menu-end">' +
                            `<li><a href="/sales/deliveries/${data}/" class="dropdown-item">View Details</a></li>` +
                        '</ul>' +
                    '</div>'
                );
            }
        }
    ]
};

document.getElementById('applyDnFilters').addEventListener('click', function() {
    dnFilters.status               = $('#filter_dn_status').val() || [];
    dnFilters.customer_id          = $('#filter_dn_customer_id').val() || '';
    dnFilters.dispatch_date_preset = $('#filter_dn_dispatch_date_preset').val() || '';
    dnFilters.delivery_date_preset = $('#filter_dn_delivery_date_preset').val() || '';

    const localDate = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

    dnFilters.dispatch_date_from = '';
    dnFilters.dispatch_date_to   = '';
    if (dnFilters.dispatch_date_preset === 'custom') {
        const fp = document.getElementById('filter_dn_dispatch_date_range')._flatpickr;
        if (fp && fp.selectedDates.length >= 2) {
            dnFilters.dispatch_date_from = localDate(fp.selectedDates[0]);
            dnFilters.dispatch_date_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
        }
    }

    dnFilters.delivery_date_from = '';
    dnFilters.delivery_date_to   = '';
    if (dnFilters.delivery_date_preset === 'custom') {
        const fp = document.getElementById('filter_dn_delivery_date_range')._flatpickr;
        if (fp && fp.selectedDates.length >= 2) {
            dnFilters.delivery_date_from = localDate(fp.selectedDates[0]);
            dnFilters.delivery_date_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
        }
    }

    salesDeliveriesDt.ajax.reload();
});

document.getElementById('resetDnFilters').addEventListener('click', function() {
    $('#filter_dn_status').val([]).trigger('change');
    $('#filter_dn_customer_id').val('').trigger('change');
    $('#filter_dn_dispatch_date_preset').val('').trigger('change'); // onChange → hides wrap + clears flatpickr
    $('#filter_dn_delivery_date_preset').val('').trigger('change'); // onChange → hides wrap + clears flatpickr
    dnFilters = { status: [], customer_id: '', dispatch_date_preset: '', dispatch_date_from: '', dispatch_date_to: '', delivery_date_preset: '', delivery_date_from: '', delivery_date_to: '' };
    salesDeliveriesDt.ajax.reload();
});

jQuery(document).ready(function() {
    initDnFilterControls();
    salesDeliveriesDt = initDataTable("#sales_deliveries_table", salesDeliveriesDtOptions);
});
</script>
@endpush
