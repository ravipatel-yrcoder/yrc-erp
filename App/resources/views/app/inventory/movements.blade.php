@extends('layouts.app')
@section('title', 'Stock Movements')

@section('content')
<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Stock Movements</h4>
            <p class="text-muted mb-0 small">All stock-affecting transactions in one ledger</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <!-- Row 1: Dropdowns -->
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Movement Type</label>
                    <select id="filter_movement_type" class="form-select form-select-sm" multiple></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Product</label>
                    <select id="filter_product" class="form-select form-select-sm"></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Location</label>
                    <select id="filter_location" class="form-select form-select-sm"></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Performed By</label>
                    <select id="filter_performed_by" class="form-select form-select-sm"></select>
                </div>
            </div>
            <!-- Row 2: Date range + button -->
            <div class="row g-3 align-items-end mt-1">
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Date</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" id="filter_date_from" class="form-control form-control-sm" placeholder="From">
                        </div>
                        <div class="col-6">
                            <input type="text" id="filter_date_to" class="form-control form-control-sm" placeholder="To">
                        </div>
                    </div>
                </div>
                <div class="col-md-9 d-flex align-items-end gap-2">
                    <button type="button" id="applyMovementFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetMovementFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Movements Table -->
    <div class="card">
        <div class="card-datatable table-responsive">
            <table id="invMovements" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Product</th>
                        <th>Location</th>
                        <th>Type</th>
                        <th>Qty Change</th>
                        <th>Reference</th>
                        <th>Notes</th>
                        <th>Performed By</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->
@endsection

@push('scripts')
<script>
// dir: 'in' = success, 'out' = danger, 'bucket' = secondary + warning bucket pill, 'neutral' = secondary
const movementTypeConfig = {
    adjust_in:           { label: 'Adjust In',             dir: 'in'      },
    adjust_out:          { label: 'Adjust Out',            dir: 'out'     },
    transfer_in:         { label: 'Transfer In',           dir: 'in'      },
    transfer_out:        { label: 'Transfer Out',          dir: 'out'     },
    purchase_receipt:    { label: 'Purchase Receipt',      dir: 'in'      },
    sale:                { label: 'Sales Delivery',        dir: 'out'     },
    dn_cancelled:        { label: 'DN Cancelled',          dir: 'in'      },
    dn_returned:         { label: 'Delivery Returned',     dir: 'in'      },
    cust_return:         { label: 'Customer Return',       dir: 'in'      },
    cust_return_blocked: { label: 'Customer Return',       dir: 'bucket', bucket: 'Blocked' },
    cust_return_quality: { label: 'Customer Return',       dir: 'bucket', bucket: 'Quality' },
    return_to_supplier:  { label: 'Return to Supplier',   dir: 'out'     },
    scrap:               { label: 'Scrapped',              dir: 'out'     },
    mo_issue:            { label: 'MO Issue',              dir: 'out'     },
    mo_produce:          { label: 'MO Produce',            dir: 'in'      },
    mo_return:           { label: 'MO Return',             dir: 'in'      },
    to_blocked:          { label: 'Moved to Blocked',      dir: 'out'     },
    from_blocked:        { label: 'Released from Blocked', dir: 'in'      },
    to_quality:          { label: 'Moved to Quality',      dir: 'out'     },
    from_quality:        { label: 'Released from Quality', dir: 'in'      },
    blocked_to_quality:  { label: 'Blocked → Quality',    dir: 'neutral'  },
    quality_to_blocked:  { label: 'Quality → Blocked',    dir: 'neutral'  },
};

let movementFilters = {
    movement_types: [],
    product_id:     '',
    location_id:    '',
    performed_by:   '',
    date_from:      '',
    date_to:        '',
};

let invMovementsDt;

const loadMovementFilters = async function() {
    try {
        const res = await api.get('/inv/movements/form-context');
        const { movement_types = [], locations = [], users = [], products = [] } = res.data.data;

        initSelect2('#filter_movement_type', {
            placeholder: 'All Types',
            multiple: true,
            minimumResultsForSearch: Infinity,
            data: buildSelect2Options(movement_types, { idKey: 'id', textKey: 'name' }),
        });

        initSelect2('#filter_product', {
            placeholder: 'All Products',
            data: [{ id: '', text: 'All Products' }, ...buildSelect2Options(products, { idKey: 'id', textKey: 'name' })],
        });

        initSelect2('#filter_location', {
            placeholder: 'All Locations',
            data: [{ id: '', text: 'All Locations' }, ...buildSelect2Options(locations, { idKey: 'id', textKey: 'name' })],
        });

        initSelect2('#filter_performed_by', {
            placeholder: 'Anyone',
            data: [{ id: '', text: 'Anyone' }, ...buildSelect2Options(users, { idKey: 'id', textKey: 'name' })],
        });

        initDatePicker('#filter_date_from');
        initDatePicker('#filter_date_to');

    } catch(e) {}
};

const invMovementsDtOptions = {
    order: [[8, 'desc']],
    ajax: {
        url: `/api/inv/movements`,
        data: function(d) {
            d.movement_types = movementFilters.movement_types;
            d.product_id     = movementFilters.product_id;
            d.location_id    = movementFilters.location_id;
            d.performed_by   = movementFilters.performed_by;
            d.date_from      = movementFilters.date_from;
            d.date_to        = movementFilters.date_to;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            data: 'created_at',
            orderData: [0, 8],
            render: function(data) {
                return formatMySqlDate(data);
            }
        },
        { data: 'product_name' },
        { data: 'location' },
        {
            data: 'movement_type',
            render: function(data) {
                const cfg = movementTypeConfig[data] || { label: data, dir: 'neutral' };
                if (cfg.dir === 'in')      return `<span class="badge badge-sm bg-label-success">${cfg.label}</span>`;
                if (cfg.dir === 'out')     return `<span class="badge badge-sm bg-label-danger">${cfg.label}</span>`;
                if (cfg.dir === 'bucket')  return `<span class="badge badge-sm bg-label-secondary me-1">${cfg.label}</span><span class="badge badge-sm bg-label-warning">${cfg.bucket}</span>`;
                return `<span class="badge badge-sm bg-label-secondary">${cfg.label}</span>`;
            }
        },
        {
            data: 'qty_change',
            render: function(data, type, row) {
                if (data === null || data === undefined) return '-';
                const num = parseFloat(data);
                const color = num > 0 ? 'text-success' : 'text-danger';
                const prefix = num > 0 ? '+' : '';
                const uom = row.uom_code ? ` <span class="fs-tiny fw-semibold">${row.uom_code}</span>` : '';
                return `<span class="fw-semibold ${color}">${prefix}${formatQty(num)}</span>${uom}`;
            }
        },
        {
            data: 'reference_number',
            render: function(data, type, row) {
                if (!data) return '-';
                const refType = row.reference_type;
                const refId   = row.reference_id;
                if (refType === 'po_grn') {
                    return `<a href="/purchase/receipts/${refId}" class="text-primary">${data}</a>`;
                }
                if (refType === 'sales_delivery') {
                    return `<a href="/sales/deliveries/${refId}" class="text-primary">${data}</a>`;
                }
                if (refType === 'mo_output' || refType === 'mo_allocation' || refType === 'mo_return') {
                    const moId = row.reference_mo_id;
                    return `<a href="/manufacturing/orders/${moId}" class="text-primary">${data}</a>`;
                }
                if (refType === 'return') {
                    return `<a href="/sales/returns/${refId}" class="text-primary">${data}</a>`;
                }
                return data;
            }
        },
        {
            data: 'notes',
            render: function(data) {
                if (!data) return '-';
                const escaped = data.replace(/"/g, '&quot;');
                return `<div class="text-truncate d-none d-sm-block w-px-150" title="${escaped}">${data}</div>`;
            }
        },
        { data: 'created_by' },
        { data: 'id', visible: false },
    ]
};

document.getElementById('applyMovementFilters').addEventListener('click', function() {
    movementFilters.movement_types = $('#filter_movement_type').val() || [];
    movementFilters.product_id     = $('#filter_product').val() || '';
    movementFilters.location_id    = $('#filter_location').val() || '';
    movementFilters.performed_by   = $('#filter_performed_by').val() || '';
    movementFilters.date_from      = document.getElementById('filter_date_from').value;
    movementFilters.date_to        = document.getElementById('filter_date_to').value;
    invMovementsDt.ajax.reload();
});

document.getElementById('resetMovementFilters').addEventListener('click', function() {
    $('#filter_movement_type').val([]).trigger('change');
    $('#filter_product').val('').trigger('change');
    $('#filter_location').val('').trigger('change');
    $('#filter_performed_by').val('').trigger('change');
    datePickerSetDate('#filter_date_from', '');
    datePickerSetDate('#filter_date_to', '');
    movementFilters = { movement_types: [], product_id: '', location_id: '', performed_by: '', date_from: '', date_to: '' };
    invMovementsDt.ajax.reload();
});

loadMovementFilters().then(function() {
    const params = new URLSearchParams(window.location.search);
    const pid = params.get('pid');
    if (pid) {
        try {
            const productId = atob(decodeURIComponent(pid));
            if (productId && !isNaN(productId)) {
                movementFilters.product_id = productId;
                $('#filter_product').val(productId).trigger('change');
            }
        } catch(e) {}
    }
    invMovementsDt = initDataTable("#invMovements", invMovementsDtOptions);
});
</script>
@endpush
