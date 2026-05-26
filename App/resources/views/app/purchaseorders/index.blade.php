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

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="purchase_orders_table">
                <thead>
                    <tr>
                        <th>Purchase Order#</th>
                        <th>Date</th>                        
                        <th>Vendor</th>                        
                        <th>Reference#</th>
                        <th>Status</th>
                        <th>Exp. Delivery</th>
                        <th>Total</th>
                        <th>Actions</th>
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

@endsection

@push('scripts')
<script>
const purchaseOrdersDtOptions = {
    order: [[7, 'desc']],
    ajax: {
        url: '/api/purchase/orders',
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
            'render': function(data, type, row) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },        
        {'data': 'vendor'},
        {'data': 'reference'},
        {'data': 'status'},
        {
            'data': 'exp_delivery_date',
            'render': function(data, type, row) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {'data': 'amount'},        
        {
            'data': 'id',
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                const editBtn = (row.status !== 'cancelled' && row.status !== 'closed' && canDo('purchase_orders', 'write'))
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
}
const purchaseOrdersDt = initDataTable("#purchase_orders_table", purchaseOrdersDtOptions);
</script>
@endpush