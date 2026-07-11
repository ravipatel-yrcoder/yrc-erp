@extends('layouts.app')
@section('title', 'Vendor Pricelist')

@section('content')

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Vendor Pricelist</h4>
            <p class="text-muted mb-0 small">Manage vendor-specific product price rules</p>
        </div>
        @if(tenantContext()->canDo('vendor_pricelists', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" onclick="openVendorPriceDrawer(0)">
                <i class="icon-base bx bx-plus icon-sm"></i> New Price
            </button>
        </div>
        @endif
    </div>

    {{-- Filters --}}
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Vendor</label>
                    <select id="filterVendor" class="form-select form-select-sm">
                        <option value="">All Vendors</option>
                        @foreach($vendorOptions as $v)
                        <option value="{{ $v->id }}">{{ $v->display_name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label form-label-sm mb-1">Product</label>
                    <select id="filterProduct" class="form-select form-select-sm">
                        <option value="">All Products</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label form-label-sm mb-1">Status</label>
                    <select id="filterStatus" class="form-select form-select-sm">
                        <option value="">All Status</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-auto">
                    <div class="d-flex gap-2">
                        <button type="button" id="applyVpFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                        <button type="button" id="clearVpFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="vendorPricesTable">
                <thead>
                    <tr>
                        <th>Vendor</th>
                        <th>Product</th>
                        <th>Vendor SKU</th>
                        <th class="text-end">Min Qty</th>
                        <th class="text-end">Unit Price</th>
                        <th class="text-end">Discount %</th>
                        <th class="text-end">Lead Time</th>
                        <th>Start</th>
                        <th>End</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>

@includeOnce('app.components.drawers.vendor-product-prices.add-edit')

@endsection

@push('scripts')
<script>
let vpFilters = {};

const vpDelCallback = async function(id) {
    try {
        const response = await api.delete('/purchase/vendor-prices', { data: { id } });
        notyf.success(response.data.message);
        if (typeof vendorPricesDt !== 'undefined') vendorPricesDt.ajax.reload();
    } catch (error) {
        handleApiError(error);
    }
};

const vpDel = function(id) {
    showConfirmation(DELETE_CONFIRM_MESSAGE, "warning", {
        text: 'Delete',
        class: 'btn-label-danger',
        callback: function() { vpDelCallback(id); }
    });
};

jQuery(document).ready(function() {

    initSelect2('#filterVendor', { placeholder: 'All Vendors' });

    initSelect2('#filterStatus', { placeholder: 'All Status', minimumResultsForSearch: Infinity });

    initSelect2('#filterProduct', {
        placeholder: 'All Products',
        minimumInputLength: 1,
        ajax: {
            url: '/api/products/search',
            dataType: 'json',
            delay: 300,
            data: params => ({ q: params.term || '' }),
            processResults: function(response) {
                return { results: (response.data || []).map(p => ({ id: p.id, text: p.name + (p.sku ? ' · ' + p.sku : '') })) };
            }
        }
    });

    window.vendorPricesDt = initDataTable("#vendorPricesTable", {
        order: [[0, 'asc']],
        ajax: {
            url: '/api/purchase/vendor-prices',
            dataSrc: function(json) { return mapApiToDataTable(json); },
            data: function(d) {
                d.vendor_id  = vpFilters.vendor_id  || '';
                d.product_id = vpFilters.product_id || '';
                d.status     = vpFilters.status     || '';
            }
        },
        columns: [
            { data: 'vendor_name', name: 'vendor_name', render: data => '<span class="fw-medium">' + data + '</span>' },
            { data: 'product_name', name: 'product_name' },
            { data: 'vendor_product_code', name: 'vendor_product_code', defaultContent: '—' },
            { data: 'min_qty', name: 'min_qty', className: 'text-end', render: data => formatQty(data) },
            { data: 'unit_price', name: 'unit_price', className: 'text-end', render: data => formatPrice(data) },
            {
                data: 'discount_amount', name: 'discount_amount',
                className: 'text-end',
                render: function(data) {
                    return parseFloat(data) > 0 ? parseFloat(data).toFixed(2) + '%' : '—';
                }
            },
            {
                data: 'lead_time_days', name: 'lead_time_days',
                className: 'text-end',
                render: data => data ? data + ' day' + (data == 1 ? '' : 's') : '—'
            },
            { data: 'start_date', name: 'start_date', defaultContent: '—', render: data => data ? formatMySqlDate(data, window.sysDefaultConfig.dateFormat) : '—' },
            { data: 'end_date',   name: 'end_date',   defaultContent: '—', render: data => data ? formatMySqlDate(data, window.sysDefaultConfig.dateFormat) : '—' },
            {
                data: 'status', name: 'status',
                render: data => data === 'active'
                    ? '<span class="badge text-bg-success">Active</span>'
                    : '<span class="badge text-bg-secondary">Inactive</span>'
            },
            {
                data: 'id', name: 'id',
                orderable: false,
                searchable: false,
                render: function(data) {
                    const editBtn   = canDo('vendor_pricelists', 'write')  ? `<a href="javascript:void(0);" onclick="openVendorPriceDrawer(${data})" class="btn text-warning btn-icon item-edit" title="Edit"><i class="icon-base bx bxs-edit"></i></a>` : '';
                    const deleteBtn = canDo('vendor_pricelists', 'delete') ? `<div class="dropdown-divider"></div><li><a href="javascript:void(0);" onclick="vpDel(${data})" class="dropdown-item text-danger">Delete</a></li>` : '';
                    const dotsMenu  = deleteBtn ? `<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a><ul class="dropdown-menu dropdown-menu-end">${deleteBtn}</ul>` : '';
                    return '<div class="d-inline-block">' + editBtn + dotsMenu + '</div>';
                }
            }
        ]
    });

    document.getElementById('applyVpFilters').addEventListener('click', function() {
        vpFilters = {
            vendor_id:  document.getElementById('filterVendor').value,
            product_id: document.getElementById('filterProduct').value,
            status:     document.getElementById('filterStatus').value,
        };
        vendorPricesDt.ajax.reload();
    });

    document.getElementById('clearVpFilters').addEventListener('click', function() {
        vpFilters = {};
        jQuery('#filterVendor').val('').trigger('change');
        jQuery('#filterProduct').val('').trigger('change');
        jQuery('#filterStatus').val('').trigger('change');
        vendorPricesDt.ajax.reload();
    });
});
</script>
@endpush
