@extends('layouts.app')
@section('title', 'Sales Orders')

@section('content')
<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Sales Orders</h4>
            <p class="text-muted mb-0 small">Manage your sales orders</p>
        </div>
        @if(tenantContext()->canDo('sales_orders', 'write'))
        <div class="d-flex gap-2">
            <button class="btn btn-outline-primary btn-sm" type="button" onClick="openSalesOrderFormDrawer(0, {mode: 'lead_quotation', leadId: 0});"><i class="icon-base bx bx-plus icon-sm"></i> Create Quotation</button>
            <button class="btn btn-primary btn-sm" type="button" onClick="openSalesOrderFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i> Add Order</button>
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
                    <select id="filter_status" class="form-select form-select-sm" multiple style="width:200px">
                        <option value="draft">Draft</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="partially_dispatched">Partially Dispatched</option>
                        <option value="dispatched">Dispatched</option>
                        <option value="partially_delivered">Partially Delivered</option>
                        <option value="delivered">Delivered</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Expected Delivery -->
                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-200">
                        <label class="form-label mb-1 small fw-medium">Expected Delivery</label>
                        <select id="filter_delivery" class="form-select form-select-sm">
                            <option value="">Any</option>
                            <option value="overdue">Overdue</option>
                            <option value="due_today">Due Today</option>
                            <option value="due_this_week">Due This Week</option>
                            <option value="due_this_month">Due This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filter-delivery-range-wrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filter_delivery_range" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>                    
                </div>

                <!-- Order Date -->
                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-200">
                        <label class="form-label mb-1 small fw-medium">Order Date</label>                        
                        <select id="filter_order_date_preset" class="form-select form-select-sm" style="width:148px">
                            <option value="">Any Time</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="last_3_months">Last 3 Months</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filter-order-date-range-wrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filter_order_date_range" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>
                </div>

                @if($showSalespersonFilter)
                <!-- Sales Rep -->
                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Sales Rep</label>
                    <select id="filter_salesperson_id" class="form-select form-select-sm" style="width:148px">
                        <option value="">Anyone</option>
                        @foreach($salespersonOptions as $sp)
                        <option value="{{ $sp->id }}">{{ $sp->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <!-- Buttons -->
                <div class="d-flex gap-2">
                    <button type="button" id="applySoFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetSoFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>

            </div>
        </div>
    </div>

    <div id="so-filter-config" data-show-salesperson="{{ $showSalespersonFilter ? '1' : '0' }}" style="display:none;"></div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="sales_orders_table">
                <thead>
                    <tr>
                        <th>SO#</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Reference</th>
                        <th>Status</th>
                        <th>Exp. Delivery</th>
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
const SHOW_SALESPERSON_FILTER = document.getElementById('so-filter-config').dataset.showSalesperson === '1';

let soFilters = {
    status: [],
    delivery: '',
    delivery_date_from: '',
    delivery_date_to: '',
    order_date_preset: '',
    order_date_from: '',
    order_date_to: '',
    salesperson_id: ''
};

let salesOrdersDt;

const initSoFilterControls = function() {

    initSelect2('#filter_status', {
        placeholder: 'All Statuses',
        multiple: true,
        minimumResultsForSearch: Infinity,
        width: 'resolve',        
    });

    initSelect2('#filter_delivery', {
        placeholder: 'Any',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filter-delivery-range-wrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filter_delivery_range')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filter_delivery_range', { mode: 'range', static: false });

    initSelect2('#filter_order_date_preset', {
        placeholder: 'Any Time',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filter-order-date-range-wrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filter_order_date_range')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filter_order_date_range', { mode: 'range', static: false });

    if (SHOW_SALESPERSON_FILTER) {
        initSelect2('#filter_salesperson_id', {
            placeholder: 'Anyone',
            minimumResultsForSearch: 5,
            resetVal: false,
            width: 'resolve',
        });
    }
};

const salesOrdersDtOptions = {
    order: [[8, 'desc']],
    ajax: {
        url: '/api/sales/orders',
        data: function(d) {
            d.filter_status             = soFilters.status;
            d.filter_delivery           = soFilters.delivery;
            d.filter_delivery_date_from = soFilters.delivery_date_from;
            d.filter_delivery_date_to   = soFilters.delivery_date_to;
            d.filter_order_date_preset  = soFilters.order_date_preset;
            d.filter_order_date_from    = soFilters.order_date_from;
            d.filter_order_date_to      = soFilters.order_date_to;
            d.filter_salesperson_id     = soFilters.salesperson_id;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            data: 'so_number',
            render: function(data, type, row) {
                return `<a href="/sales/orders/${row.id}/">${data}</a>`;
            }
        },
        {
            data: 'order_date',
            render: function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        { data: 'customer' },
        { data: 'reference', defaultContent: '-' },
        {
            data: 'status',
            render: function(data) {
                const statusMap = {
                    draft:                ['Draft',               'warning'],
                    confirmed:            ['Confirmed',           'primary'],
                    cancelled:            ['Cancelled',           'danger'],
                    partially_dispatched: ['Partially Dispatched','info'],
                    dispatched:           ['Dispatched',          'primary'],
                    partially_delivered:  ['Partially Delivered', 'info'],
                    delivered:            ['Delivered',           'success'],
                };
                const s = statusMap[data] || [data, 'secondary'];
                return `<span class="badge badge-sm bg-label-${s[1]}">${s[0]}</span>`;
            }
        },
        {
            data: 'expected_delivery_date',
            defaultContent: '-',
            render: function(data) {
                return data ? formatMySqlDate(data, window.sysDefaultConfig.dateFormat) : '-';
            }
        },
        {
            data: 'grand_total',
            render: function(data) { return formatCurrency(data); }
        },
        { data: 'created_by_name', defaultContent: '-' },
        {
            data: 'id',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                const editBtn = (row.status === 'draft' && canDo('sales_orders', 'write'))
                    ? `<a href="javascript:void(0);" onClick="openSalesOrderFormDrawer(${data})" class="btn text-warning btn-icon item-edit" title="Edit"><i class="icon-base bx bxs-edit"></i></a>`
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

document.getElementById('applySoFilters').addEventListener('click', function() {
    soFilters.status            = $('#filter_status').val() || [];
    soFilters.delivery          = $('#filter_delivery').val() || '';
    soFilters.order_date_preset = $('#filter_order_date_preset').val() || '';
    soFilters.salesperson_id    = SHOW_SALESPERSON_FILTER ? ($('#filter_salesperson_id').val() || '') : '';

    const localDate = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

    soFilters.delivery_date_from = '';
    soFilters.delivery_date_to   = '';
    if (soFilters.delivery === 'custom') {
        const fp = document.getElementById('filter_delivery_range')._flatpickr;
        if (fp && fp.selectedDates.length >= 2) {
            soFilters.delivery_date_from = localDate(fp.selectedDates[0]);
            soFilters.delivery_date_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
        }
    }

    soFilters.order_date_from = '';
    soFilters.order_date_to   = '';
    if (soFilters.order_date_preset === 'custom') {
        const fp = document.getElementById('filter_order_date_range')._flatpickr;
        if (fp && fp.selectedDates.length >= 2) {
            soFilters.order_date_from = localDate(fp.selectedDates[0]);
            soFilters.order_date_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
        }
    }

    salesOrdersDt.ajax.reload();
});

document.getElementById('resetSoFilters').addEventListener('click', function() {
    $('#filter_status').val([]).trigger('change');
    $('#filter_delivery').val('').trigger('change');           // onChange → hides wrap + clears flatpickr
    $('#filter_order_date_preset').val('').trigger('change'); // onChange → hides wrap + clears flatpickr
    if (SHOW_SALESPERSON_FILTER) {
        $('#filter_salesperson_id').val('').trigger('change');
    }
    soFilters = { status: [], delivery: '', delivery_date_from: '', delivery_date_to: '', order_date_preset: '', order_date_from: '', order_date_to: '', salesperson_id: '' };
    salesOrdersDt.ajax.reload();
});

jQuery(document).ready(function() {
    initSoFilterControls();

    const urlParams    = new URLSearchParams(window.location.search);
    const initStatus   = urlParams.get('status');
    const initDelivery = urlParams.get('delivery');

    if (initStatus) {

        if( initStatus == "open" ) {
            soFilters.status = ['confirmed', 'partially_dispatched', 'dispatched', 'partially_delivered'];
        } 
        else if( initStatus == "pending_dispatch" ) {
            soFilters.status = ['confirmed', 'partially_dispatched'];
        }        
        else {
            soFilters.status = [initStatus];
        }
                
        $('#filter_status').val(soFilters.status).trigger('change');
    }
    if (initDelivery) {
        soFilters.delivery = initDelivery;
        $('#filter_delivery').val(initDelivery).trigger('change');
    }

    salesOrdersDt = initDataTable("#sales_orders_table", salesOrdersDtOptions);
});

document.addEventListener('salesOrderFormSaved', function() {
    salesOrdersDt.ajax.reload(null, false);
});
</script>
@endpush
