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

<!-- Reservations Modal -->
<div class="modal fade" id="reservationsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Reserved Stock Breakdown</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="reservationsModalBody">
                <div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>
            </div>
        </div>
    </div>
</div>

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

const openReservationsModal = async function(productId, locationId) {
    const modal = new bootstrap.Modal(document.getElementById('reservationsModal'));
    document.getElementById('reservationsModalBody').innerHTML =
        '<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>';
    modal.show();

    try {
        const locationParam = locationId ? `&location_id=${locationId}` : '';
        const res = await api.get(`/inv/reservations?product_id=${productId}${locationParam}`);
        const { total_reserved, reservations } = res.data.data;

        if (!reservations.length) {
            document.getElementById('reservationsModalBody').innerHTML =
                '<p class="text-muted text-center py-3">No reservations found.</p>';
            return;
        }

        const showLocation = !locationId;
        const docTypeLabel = { sales_order: 'Sales Order', manufacturing_order: 'Manufacturing Order' };

        let rows = reservations.map(r => {
            const label    = docTypeLabel[r.document_type] || r.document_type;
            const customer = r.customer_name ? ` <span class="text-muted small">(${r.customer_name})</span>` : '';
            const locCell  = showLocation ? `<td>${r.location_name || ''}</td>` : '';
            return `<tr>
                ${locCell}
                <td>${label}</td>
                <td><a href="${r.link}" target="_blank">${r.document_number}</a>${customer}</td>
                <td class="text-end">${formatQty(r.reserved_qty)}</td>
            </tr>`;
        }).join('');

        const locationHeader = showLocation ? '<th>Location</th>' : '';
        const totalColspan   = showLocation ? 3 : 2;

        document.getElementById('reservationsModalBody').innerHTML = `
            <table class="table table-sm table-bordered mb-0">
                <thead><tr>${locationHeader}<th>Type</th><th>Reference</th><th class="text-end">Reserved Qty</th></tr></thead>
                <tbody>${rows}</tbody>
                <tfoot><tr class="fw-bold"><td colspan="${totalColspan}">Total</td><td class="text-end">${formatQty(total_reserved)}</td></tr></tfoot>
            </table>`;
    } catch(e) {
        document.getElementById('reservationsModalBody').innerHTML =
            '<p class="text-danger text-center py-3">Failed to load reservations.</p>';
    }
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
            data: 'unrestricted_qty',
            render: function(data, type, row) {
                return `${formatQty(data)}${row.uom_code ? ' <span class="fs-tiny fw-semibold">' + row.uom_code + '</span>' : ''}`;
            }
        },
        {
            data: 'reserved_qty',
            render: function(data, type, row) {
                const qty = parseFloat(data);
                const uom = row.uom_code ? ` <span class="fs-tiny fw-semibold">${row.uom_code}</span>` : '';
                if (qty > 0) {
                    const locId = itemsFilters.location_id || 0;
                    return `<a href="javascript:void(0);" class="text-warning fw-semibold" onclick="openReservationsModal(${row.id}, ${locId})">${formatQty(qty)}</a>${uom}`;
                }
                return `${formatQty(qty)}${uom}`;
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
