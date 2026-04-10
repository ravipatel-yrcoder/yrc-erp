@extends('layouts.app')
@section('title', 'Sales Orders')

@section('content')
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <h5 class="card-title mb-0">Sales Orders</h5>
            <button class="btn btn-primary btn-sm" type="button" onClick="openSalesOrderFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i>Add New</button>
        </div>
        <div class="card-datatable text-nowrap">
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
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@include('app.components.drawers.sales-orders.add-edit')

@endsection

@push('scripts')
<script>
const salesOrdersDtOptions = {
    ajax: {
        url: '/api/sales-orders',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            'data': 'so_number',
            'render': function(data, type, row) {
                return `<a href="/sales-orders/${row.id}/">${data}</a>`;
            }
        },
        {'data': 'order_date'},
        {'data': 'customer'},
        {'data': 'reference', 'defaultContent': '-'},
        {
            'data': 'status',
            'render': function(data) {
                const statusMap = {
                    draft: ['Quotation', 'warning'],
                    confirmed: ['Confirmed', 'primary'],
                    cancelled: ['Cancelled', 'danger'],
                    partially_dispatched: ['Partially Dispatched', 'info'],
                    dispatched: ['Dispatched', 'primary'],
                    partially_delivered:  ['Partially Delivered', 'info'],
                    delivered: ['Delivered', 'success'],
                };
                const s = statusMap[data] || [data, 'secondary'];
                return `<span class="badge bg-label-${s[1]}">${s[0]}</span>`;
            }
        },
        {'data': 'expected_delivery_date', 'defaultContent': '-'},
        {
            'data': 'total_amount',
            'render': function(data) { return formatCurrency(data); }
        },
        {
            'data': 'id',
            'orderable': false,
            'searchable': false,
            'render': function(data) {
                return (
                    `<div class="d-inline-block">
                        <a href="/sales-orders/${data}/" class="btn text-primary btn-icon item-edit" title="View sales order"><i class="icon-base bx bx-show"></i></a>
                    </div>`
                );
            }
        }
    ]
};
const salesOrdersDt = initDataTable("#sales_orders_table", salesOrdersDtOptions);
</script>
@endpush
