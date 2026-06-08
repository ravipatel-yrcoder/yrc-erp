@extends('layouts.app')
@section('title', 'Manufacturing Orders')

@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Manufacturing Orders</h4>
            <p class="text-muted mb-0 small">Track and manage production orders</p>
        </div>
        @if(tenantContext()->canDo('manufacturing_orders', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" onclick="openMoFormDrawer()">
                <i class="icon-base bx bx-plus icon-sm"></i> New Order
            </button>
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
                    <select id="filter_mo_status" class="form-select form-select-sm" multiple>
                        <option value="draft">Draft</option>
                        <option value="confirmed">Confirmed</option>
                        <option value="in_production">In Production</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                    </select>
                </div>

                <!-- Finished Product -->
                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Finished Product</label>
                    <select id="filter_mo_product_id" class="form-select form-select-sm">
                        <option value="">All Products</option>
                        @foreach($productOptions as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Allocation Status -->
                <div class="w-px-200">
                    <label class="form-label mb-1 small fw-medium">Allocation</label>
                    <select id="filter_mo_allocation_status" class="form-select form-select-sm" multiple>
                        <option value="not_allocated">Not Allocated</option>
                        <option value="partially_allocated">Partial</option>
                        <option value="fully_allocated">Allocated</option>
                    </select>
                </div>

                <!-- Scheduled Date -->
                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-200">
                        <label class="form-label mb-1 small fw-medium">Scheduled Date</label>
                        <select id="filter_mo_scheduled_date_preset" class="form-select form-select-sm">
                            <option value="">Any Time</option>
                            <option value="overdue">Overdue</option>
                            <option value="due_today">Due Today</option>
                            <option value="due_this_week">Due This Week</option>
                            <option value="due_this_month">Due This Month</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filter-mo-scheduled-date-range-wrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filter_mo_scheduled_date_range" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2">
                    <button type="button" id="applyMoFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetMoFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>

            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="mo_table">
                <thead>
                    <tr>
                        <th>MO #</th>
                        <th>Product</th>
                        <th>BOM</th>
                        <th>Warehouse</th>
                        <th>Planned Qty</th>
                        <th>Produced Qty</th>
                        <th>Scheduled Date</th>
                        <th>Status</th>
                        <th>Allocation</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@if(tenantContext()->canDo('manufacturing_orders', 'write'))
@includeOnce('app.components.drawers.manufacturing-orders.add-edit')
@endif

@endsection

@push('scripts')
<script>
const moStatusBadge = function(status) {
    const map = {
        draft:         '<span class="badge bg-label-secondary">Draft</span>',
        confirmed:     '<span class="badge bg-label-info">Confirmed</span>',
        in_production: '<span class="badge bg-label-primary">In Production</span>',
        completed:     '<span class="badge bg-label-success">Completed</span>',
        cancelled:     '<span class="badge bg-label-danger">Cancelled</span>',
    };
    return map[status] || status;
};

const moAllocationStatusBadge = function(status) {
    const map = {
        not_allocated:       '<span class="badge bg-label-secondary">Not Allocated</span>',
        partially_allocated: '<span class="badge bg-label-warning">Partial</span>',
        fully_allocated:     '<span class="badge bg-label-success">Allocated</span>',
    };
    return map[status] || status;
};

let moFilters = {
    status:                  [],
    product_id:              '',
    allocation_status:       [],
    scheduled_date_preset:   '',
    scheduled_date_from:     '',
    scheduled_date_to:       ''
};

let moDt;

const initMoFilterControls = function() {

    initSelect2('#filter_mo_status', {
        placeholder: 'All Statuses',
        multiple: true,
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_mo_product_id', {
        placeholder: 'All Products',
        width: 'resolve',
    });

    initSelect2('#filter_mo_allocation_status', {
        placeholder: 'All',
        multiple: true,
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_mo_scheduled_date_preset', {
        placeholder: 'Any Time',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filter-mo-scheduled-date-range-wrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filter_mo_scheduled_date_range')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filter_mo_scheduled_date_range', { mode: 'range', static: false });
};

const moDtOptions = {
    order: [[0, 'desc']],
    ajax: {
        url: '/api/manufacturing/orders',
        data: function(d) {
            d.filter_status                = moFilters.status;
            d.filter_product_id            = moFilters.product_id;
            d.filter_allocation_status     = moFilters.allocation_status;
            d.filter_scheduled_date_preset = moFilters.scheduled_date_preset;
            d.filter_scheduled_date_from   = moFilters.scheduled_date_from;
            d.filter_scheduled_date_to     = moFilters.scheduled_date_to;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        { data: 'mo_number', render: function(data, type, row) { return `<a href="/manufacturing/orders/${row.id}/">${data}</a>`; } },
        { data: 'product_name' },
        { data: 'bom_name' },
        { data: 'source_location_name', render: function(data) { return data || '—'; } },
        {
            data: 'planned_qty',
            render: function(data) { return formatQty(data); }
        },
        {
            data: 'produced_qty',
            render: function(data) { return formatQty(data); }
        },
        {
            data: 'planned_date',
            render: function(data) {
                return data ? formatMySqlDate(data, window.sysDefaultConfig.dateFormat) : '—';
            }
        },
        {
            data: 'status',
            render: function(data) { return moStatusBadge(data); }
        },
        {
            data: 'allocation_status',
            render: function(data) { return moAllocationStatusBadge(data); }
        },
        { data: 'created_by_name' },
        {
            data: 'created_at',
            render: function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            data: 'id',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                const canEdit = canDo('manufacturing_orders', 'write') && row.status === 'draft';

                const editBtn = canEdit
                    ? `<a href="javascript:void(0);" onclick="openMoFormDrawer(${data})" class="btn text-warning btn-icon item-edit" title="Edit"><i class="icon-base bx bxs-edit"></i></a>`
                    : '';

                const viewItem = `<li><a href="/manufacturing/orders/${data}/" class="dropdown-item">View Details</a></li>`;

                return (
                    '<div class="d-inline-block">' +
                        editBtn +
                        '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>' +
                        '<ul class="dropdown-menu dropdown-menu-end">' + viewItem + '</ul>' +
                    '</div>'
                );
            }
        }
    ]
};

document.getElementById('applyMoFilters').addEventListener('click', function() {
    moFilters.status              = $('#filter_mo_status').val() || [];
    moFilters.product_id          = $('#filter_mo_product_id').val() || '';
    moFilters.allocation_status   = $('#filter_mo_allocation_status').val() || [];
    moFilters.scheduled_date_preset = $('#filter_mo_scheduled_date_preset').val() || '';

    const localDate = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

    moFilters.scheduled_date_from = '';
    moFilters.scheduled_date_to   = '';
    if (moFilters.scheduled_date_preset === 'custom') {
        const fp = document.getElementById('filter_mo_scheduled_date_range')._flatpickr;
        if (fp && fp.selectedDates.length >= 2) {
            moFilters.scheduled_date_from = localDate(fp.selectedDates[0]);
            moFilters.scheduled_date_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
        }
    }

    moDt.ajax.reload();
});

document.getElementById('resetMoFilters').addEventListener('click', function() {
    $('#filter_mo_status').val([]).trigger('change');
    $('#filter_mo_product_id').val('').trigger('change');
    $('#filter_mo_allocation_status').val([]).trigger('change');
    $('#filter_mo_scheduled_date_preset').val('').trigger('change'); // onChange → hides wrap + clears flatpickr
    moFilters = { status: [], product_id: '', allocation_status: [], scheduled_date_preset: '', scheduled_date_from: '', scheduled_date_to: '' };
    moDt.ajax.reload();
});

jQuery(document).ready(function() {
    initMoFilterControls();
    moDt = initDataTable("#mo_table", moDtOptions);
});
</script>
@endpush
