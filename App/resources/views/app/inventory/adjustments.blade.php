@extends('layouts.app')
@section('title', 'Stock - Adjustments')

@section('content')

@php
    $productId = request()->getInput("id");
@endphp
<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Stock Adjustments</h4>
            <p class="text-muted mb-0 small">View and manage stock adjustments</p>
        </div>
        @if(tenantContext()->canDo('inventory_adjustments', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" onClick="openAddEditProdStockDrawer()">Adjust Stock</button>
        </div>
        @endif
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            
            <!-- Row 1: Dropdowns -->
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Product</label>
                    <select id="adj_filter_product" class="form-select form-select-sm"></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Location</label>
                    <select id="adj_filter_location" class="form-select form-select-sm"></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Type</label>
                    <select id="adj_filter_type" class="form-select form-select-sm"></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Performed By</label>
                    <select id="adj_filter_performed_by" class="form-select form-select-sm"></select>
                </div>
            </div>
            
            <!-- Row 2: Date range + button -->
            <div class="row g-3 align-items-end mt-1">
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Date</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" id="adj_filter_date_from" class="form-control form-control-sm" placeholder="From">
                        </div>
                        <div class="col-6">
                            <input type="text" id="adj_filter_date_to" class="form-control form-control-sm" placeholder="To">
                        </div>
                    </div>
                </div>
                <div class="col-md-9 d-flex align-items-end gap-2">
                    <button type="button" id="applyAdjFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetAdjFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table id="invAdjustments" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Location</th>
                        <th>Qty</th>
                        <th>Note</th>
                        <th>Performed By</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@if(tenantContext()->canDo('inventory_adjustments', 'write'))
@includeOnce('app.components.drawers.inventory.products.adjust-stock')
@endif

@endsection

@push('scripts')
<script>
let adjFilters = {
    product_id:      '',
    location_id:     '',
    adjustment_type: '',
    performed_by:    '',
    date_from:       '',
    date_to:         '',
};

let invAdjustmentsDt;

const loadAdjFilters = async function() {
    try {
        const res = await api.get('/inv/movements/form-context');
        const { locations = [], users = [], products = [] } = res.data.data;

        initSelect2('#adj_filter_product', {
            placeholder: 'All Products',
            data: [{ id: '', text: 'All Products' }, ...buildSelect2Options(products, { idKey: 'id', textKey: 'name' })],
        });

        initSelect2('#adj_filter_location', {
            placeholder: 'All Locations',
            data: [{ id: '', text: 'All Locations' }, ...buildSelect2Options(locations, { idKey: 'id', textKey: 'name' })],
        });

        initSelect2('#adj_filter_type', {
            placeholder: 'All Types',
            minimumResultsForSearch: Infinity,
            data: [
                { id: '',         text: 'All Types'  },
                { id: 'increase', text: 'Adjust In'  },
                { id: 'decrease', text: 'Adjust Out' },
            ],
        });

        initSelect2('#adj_filter_performed_by', {
            placeholder: 'Anyone',
            data: [{ id: '', text: 'Anyone' }, ...buildSelect2Options(users, { idKey: 'id', textKey: 'name' })],
        });

        initDatePicker('#adj_filter_date_from');
        initDatePicker('#adj_filter_date_to');

    } catch(e) {}
};

const invAdjustmentsDtOptions = {
    order: [[0, 'desc']],
    ajax: {
        url: `/api/inv/adjustments`,
        data: function(d) {
            d.product_id      = adjFilters.product_id;
            d.location_id     = adjFilters.location_id;
            d.adjustment_type = adjFilters.adjustment_type;
            d.performed_by    = adjFilters.performed_by;
            d.date_from       = adjFilters.date_from;
            d.date_to         = adjFilters.date_to;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            data: 'created_at',
            render: function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        { data: 'prod_name' },
        { data: 'location' },
        {
            data: 'quantity',
            render: function(data, type, row) {
                if (!data) return '-';
                const isIn  = row.adjustment_type === 'increase';
                const color  = isIn ? 'text-success' : 'text-danger';
                const prefix = isIn ? '+' : '-';
                const uom    = row.uom_code ? ` <span class="fs-tiny fw-semibold">${row.uom_code}</span>` : '';
                return `<span class="fw-semibold ${color}">${prefix}${formatQty(data)}</span>${uom}`;
            }
        },
        {
            data: 'notes',
            render: function(data) {
                if (!data) return '-';
                return `<div class="text-truncate d-none d-sm-block w-px-150">${data}</div>`;
            }
        },
        { data: 'created_by' },
    ]
};

document.getElementById('applyAdjFilters').addEventListener('click', function() {
    adjFilters.product_id      = $('#adj_filter_product').val()      || '';
    adjFilters.location_id     = $('#adj_filter_location').val()     || '';
    adjFilters.adjustment_type = $('#adj_filter_type').val()         || '';
    adjFilters.performed_by    = $('#adj_filter_performed_by').val() || '';
    adjFilters.date_from       = document.getElementById('adj_filter_date_from').value;
    adjFilters.date_to         = document.getElementById('adj_filter_date_to').value;
    invAdjustmentsDt.ajax.reload();
});

document.getElementById('resetAdjFilters').addEventListener('click', function() {
    $('#adj_filter_product').val('').trigger('change');
    $('#adj_filter_location').val('').trigger('change');
    $('#adj_filter_type').val('').trigger('change');
    $('#adj_filter_performed_by').val('').trigger('change');
    datePickerSetDate('#adj_filter_date_from', '');
    datePickerSetDate('#adj_filter_date_to', '');
    adjFilters = { product_id: '', location_id: '', adjustment_type: '', performed_by: '', date_from: '', date_to: '' };
    invAdjustmentsDt.ajax.reload();
});

loadAdjFilters().then(function() {
    invAdjustmentsDt = initDataTable("#invAdjustments", invAdjustmentsDtOptions);
});
</script>
@endpush
