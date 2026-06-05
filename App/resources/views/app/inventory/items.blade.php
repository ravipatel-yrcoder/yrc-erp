@extends('layouts.app')
@section('title', 'Inventory - Items')

@section('content')
<!-- Content -->
<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Items</h4>
            <p class="text-muted mb-0 small">Stock levels across all products</p>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Location</label>
                    <select id="items_filter_location" class="form-select form-select-sm"></select>
                </div>
                <div class="col-md-9 d-flex align-items-end gap-2">
                    <button type="button" id="applyItemsFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetItemsFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table id="invItemsTable" class="table table-bordered">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>On-Hand Qty</th>
                        <th>Reserved Qty</th>
                        <th>Available Qty</th>
                        <th>Actions</th>
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
let itemsFilters = {
    location_id: '',
};

let invItemsDt;

const loadItemsFilters = async function() {
    try {
        const res = await api.get('/inv/movements/form-context');
        const { locations = [] } = res.data.data;

        initSelect2('#items_filter_location', {
            placeholder: 'All Locations',
            data: [{ id: '', text: 'All Locations' }, ...buildSelect2Options(locations, { idKey: 'id', textKey: 'name' })],
        });

    } catch(e) {}
};

const invItemsDtOptions = {
    order: [[0, 'asc']],
    ajax: {
        url: '/api/inv/items',
        data: function(d) {
            d.location_id = itemsFilters.location_id;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        { data: 'name' },
        {
            data: 'on_hand_qty',
            render: function(data, type, row) {
                return `${formatQty(data)}${row.uom_code ? ' <span class="fs-tiny fw-semibold">' + row.uom_code + '</span>' : ''}`;
            }
        },
        {
            data: 'reserved_qty',
            render: function(data, type, row) {
                return `${formatQty(data)}${row.uom_code ? ' <span class="fs-tiny fw-semibold">' + row.uom_code + '</span>' : ''}`;
            }
        },
        {
            data: 'available_qty',
            render: function(data, type, row) {
                const qty = parseFloat(data);
                const color = qty > 0 ? 'text-success' : 'text-danger';
                const uom = row.uom_code ? ` <span class="fs-tiny fw-semibold">${row.uom_code}</span>` : '';
                return `<span class="fw-semibold ${color}">${formatQty(qty)}</span>${uom}`;
            }
        },
        {
            data: 'id',
            orderable: false,
            searchable: false,
            render: function(data) {
                const adjustItem = canDo('inventory_adjustments', 'write')
                    ? `<li><a href="javascript:void(0);" onclick="openAddEditProdStockDrawer(${data})" class="dropdown-item">Adjust Stock</a></li>`
                    : '';
                const pid = encodeURIComponent(btoa(String(data)));
                return `<div class="d-flex align-items-center gap-1">
                    <div class="dropdown">
                        <a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a href="/inv/products/${data}/stock-locations" class="dropdown-item">Manage Stock</a></li>
                            ${adjustItem}
                            <li><a href="/inv/movements?pid=${pid}" class="dropdown-item">Stock History</a></li>
                        </ul>
                    </div>
                </div>`;
            }
        },
    ]
};

document.getElementById('applyItemsFilters').addEventListener('click', function() {
    itemsFilters.location_id = $('#items_filter_location').val() || '';
    invItemsDt.ajax.reload();
});

document.getElementById('resetItemsFilters').addEventListener('click', function() {
    $('#items_filter_location').val('').trigger('change');
    itemsFilters = { location_id: '' };
    invItemsDt.ajax.reload();
});

loadItemsFilters().then(function() {
    invItemsDt = initDataTable('#invItemsTable', invItemsDtOptions);
});
</script>
@endpush
