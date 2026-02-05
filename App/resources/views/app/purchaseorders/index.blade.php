@extends('layouts.app')
@section('title', 'Purchase Orders')

@section('content')
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <h5 class="card-title mb-0">Purchase Orders</h5>
            <button class="btn btn-primary btn-sm" type="button" onClick="openPurchaseOrderFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i>Add New</button>
        </div>
        <div class="card-datatable text-nowrap">
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

@include('app.components.drawers.purchase-orders.add-edit')

@endsection

@push('scripts')
<script>
const purchaseOrderDtOptions = {
    ajax: {
        url: '/api/purchase-orders',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            'data': 'po_number',
            'render': function(data, type, row) {
                return `<a href="/purchase-orders/${row.id}/">${data}</a>`;
            }
        },
        {'data': 'order_date'},        
        {'data': 'vendor'},
        {'data': 'reference'},
        {'data': 'status'},
        {'data': 'exp_delivery_date'},
        {'data': 'amount'},        
        {
            'data': 'id', 
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                return (
                    `<div class="d-inline-block">
                        <a href="/purchase-orders/${data}/" class="btn text-primary btn-icon item-edit" title="View purchase order"><i class="icon-base bx bx-show"></i></a>
                    </div>`
                );
            }
        }
    ]
}
const purchaseOrdersDt = initDataTable("#purchase_orders_table", purchaseOrderDtOptions);
</script>
@endpush