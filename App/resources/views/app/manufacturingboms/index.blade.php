@extends('layouts.app')
@section('title', 'Bills of Materials')

@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Bills of Materials</h4>
            <p class="text-muted mb-0 small">Define the components needed to manufacture each product</p>
        </div>
        @if(tenantContext()->canDo('manufacturing_boms', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" onclick="openBomFormDrawer()">
                <i class="icon-base bx bx-plus icon-sm"></i> Add BOM
            </button>
        </div>
        @endif
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="d-flex flex-wrap align-items-end gap-3">

                <!-- Status -->
                <div class="w-px-200">
                    <label class="form-label mb-1 small fw-medium">Status</label>
                    <select id="filter_bom_status" class="form-select form-select-sm" multiple>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <!-- Finished Product -->
                <div class="w-px-250">
                    <label class="form-label mb-1 small fw-medium">Finished Product</label>
                    <select id="filter_product_id" class="form-select form-select-sm">
                        <option value="">All Products</option>
                        @foreach($productOptions as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}{{ $product->sku ? ' ('.$product->sku.')' : '' }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Is Default -->
                <div class="w-px-160">
                    <label class="form-label mb-1 small fw-medium">Default</label>
                    <select id="filter_is_default" class="form-select form-select-sm">
                        <option value="">All</option>
                        <option value="1">Default Only</option>
                        <option value="0">Non-Default</option>
                    </select>
                </div>

                <!-- Created Date -->
                <div class="d-flex align-items-center gap-2">
                    <div class="w-px-200">
                        <label class="form-label mb-1 small fw-medium">Created Date</label>
                        <select id="filter_created_date_preset" class="form-select form-select-sm">
                            <option value="">Any Time</option>
                            <option value="today">Today</option>
                            <option value="this_week">This Week</option>
                            <option value="this_month">This Month</option>
                            <option value="last_month">Last Month</option>
                            <option value="last_3_months">Last 3 Months</option>
                            <option value="custom">Custom Range</option>
                        </select>
                    </div>
                    <div id="filter-created-date-range-wrap" style="display:none;">
                        <label class="form-label mb-1 small fw-medium">&nbsp;</label>
                        <input type="text" id="filter_created_date_range" class="form-control form-control-sm w-px-200" placeholder="Pick date range">
                    </div>
                </div>

                <!-- Created By -->
                <div class="w-px-200">
                    <label class="form-label mb-1 small fw-medium">Created By</label>
                    <select id="filter_created_by" class="form-select form-select-sm">
                        <option value="">Anyone</option>
                        @foreach($userOptions as $user)
                        <option value="{{ $user->id }}">{{ $user->name }}</option>
                        @endforeach
                    </select>
                </div>

                <!-- Buttons -->
                <div class="d-flex gap-2">
                    <button type="button" id="applyBomFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetBomFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>

            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="boms_table">
                <thead>
                    <tr>
                        <th>Finished Product</th>
                        <th>BOM Name</th>
                        <th>Output Qty</th>
                        <th>Components</th>
                        <th>Default</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@if(tenantContext()->canDo('manufacturing_boms', 'write'))
@includeOnce('app.components.drawers.manufacturing-boms.add-edit')
@endif

@endsection

@push('scripts')
<script>
let bomsFilters = {
    status: [],
    product_id: '',
    is_default: '',
    created_date_preset: '',
    created_date_from: '',
    created_date_to: '',
    created_by: ''
};

let bomsDt;

const initBomFilterControls = function() {

    initSelect2('#filter_bom_status', {
        placeholder: 'All Statuses',
        multiple: true,
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_product_id', {
        placeholder: 'All Products',
        width: 'resolve',
    });

    initSelect2('#filter_is_default', {
        placeholder: 'All',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_created_date_preset', {
        placeholder: 'Any Time',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val = jQuery(el).val() || '';
            const rangeWrap = document.getElementById('filter-created-date-range-wrap');
            if (val === 'custom') {
                rangeWrap.style.display = '';
            } else {
                rangeWrap.style.display = 'none';
                const fp = document.getElementById('filter_created_date_range')._flatpickr;
                if (fp) fp.clear();
            }
        }
    });

    initDatePicker('#filter_created_date_range', { mode: 'range', static: false });

    initSelect2('#filter_created_by', {
        placeholder: 'Anyone',
        minimumResultsForSearch: 5,
        width: 'resolve',
    });
};

const bomsDtOptions = {
    order: [[7, 'desc']],
    ajax: {
        url: '/api/manufacturing/boms',
        data: function(d) {
            d.filter_status               = bomsFilters.status;
            d.filter_product_id           = bomsFilters.product_id;
            d.filter_is_default           = bomsFilters.is_default;
            d.filter_created_date_preset  = bomsFilters.created_date_preset;
            d.filter_created_date_from    = bomsFilters.created_date_from;
            d.filter_created_date_to      = bomsFilters.created_date_to;
            d.filter_created_by           = bomsFilters.created_by;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        { data: 'product_name' },
        { data: 'name' },
        {
            data: 'output_qty',
            render: function(data) { return formatQty(data); }
        },
        { data: 'component_count' },
        {
            data: 'is_default',
            render: function(data) {
                return data == 1
                    ? '<span class="badge bg-label-success">Yes</span>'
                    : '<span class="badge bg-label-secondary">No</span>';
            }
        },
        {
            data: 'status',
            render: function(data) {
                return data === 'active'
                    ? '<span class="badge bg-label-primary">Active</span>'
                    : '<span class="badge bg-label-warning">Inactive</span>';
            }
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
                const editBtn = canDo('manufacturing_boms', 'write')
                    ? `<a href="javascript:void(0);" onclick="openBomFormDrawer(${row.id})" class="btn text-warning btn-icon item-edit" title="Edit BOM"><i class="icon-base bx bxs-edit"></i></a>`
                    : '';
                const deleteItem = canDo('manufacturing_boms', 'delete')
                    ? `<li><a href="javascript:void(0);" onclick="deleteBom(${data})" class="dropdown-item text-danger">Delete</a></li>`
                    : '';
                const dotsMenu = deleteItem
                    ? `<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a><ul class="dropdown-menu dropdown-menu-end">${deleteItem}</ul>`
                    : '';
                return `<div class="d-inline-block">${editBtn}${dotsMenu}</div>`;
            }
        }
    ]
};

document.getElementById('applyBomFilters').addEventListener('click', function() {
    bomsFilters.status               = $('#filter_bom_status').val() || [];
    bomsFilters.product_id           = $('#filter_product_id').val() || '';
    bomsFilters.is_default           = $('#filter_is_default').val() || '';
    bomsFilters.created_date_preset  = $('#filter_created_date_preset').val() || '';
    bomsFilters.created_by           = $('#filter_created_by').val() || '';

    const localDate = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

    bomsFilters.created_date_from = '';
    bomsFilters.created_date_to   = '';
    if (bomsFilters.created_date_preset === 'custom') {
        const fp = document.getElementById('filter_created_date_range')._flatpickr;
        if (fp && fp.selectedDates.length >= 2) {
            bomsFilters.created_date_from = localDate(fp.selectedDates[0]);
            bomsFilters.created_date_to   = localDate(fp.selectedDates[fp.selectedDates.length - 1]);
        }
    }

    bomsDt.ajax.reload();
});

document.getElementById('resetBomFilters').addEventListener('click', function() {
    $('#filter_bom_status').val([]).trigger('change');
    $('#filter_product_id').val('').trigger('change');
    $('#filter_is_default').val('').trigger('change');
    $('#filter_created_date_preset').val('').trigger('change'); // onChange → hides wrap + clears flatpickr
    $('#filter_created_by').val('').trigger('change');
    bomsFilters = { status: [], product_id: '', is_default: '', created_date_preset: '', created_date_from: '', created_date_to: '', created_by: '' };
    bomsDt.ajax.reload();
});

const deleteBomCallback = async function(id) {
    try {
        const response = await api.delete(`/manufacturing/boms/${id}`);
        const { message } = response.data;
        notyf.success(message);
        bomsDt.ajax.reload();
    } catch(err) {
        handleApiError(err);
    }
};

const deleteBom = function(id) {
    showConfirmation(DELETE_CONFIRM_MESSAGE, "warning", {'text': 'Delete', 'class': 'btn-label-danger', 'callback': function(){ deleteBomCallback(id); }});
};

jQuery(document).ready(function() {
    initBomFilterControls();
    bomsDt = initDataTable("#boms_table", bomsDtOptions);
});

</script>
@endpush
