@extends('layouts.app')
@section('title', 'Purchase Receives')

@section('content')
<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Purchase Receives</h4>
            <p class="text-muted mb-0 small">Manage your purchase receives</p>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="purchase_receives_table">
                <thead>
                    <tr>
                        <th>Purchase Receive#</th>
                        <th>Date</th>                        
                        <th>Purchase Order#</th>             
                        <th>Vendor</th>
                        <th>Status</th>
                        <!--<th>Billed</th>-->
                        <th>Items</th>
                        <th>Actions</th>
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
const purchaseReceivesDtOptions = {
    order: [[1, 'desc']],
    ajax: {
        url: '/api/purchase/receipts',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            'data': 'receipt_number',
            'render': function(data, type, row) {
                return `<a href="/purchase/receipts/${row.id}/">${data}</a>`;
            }
        },
        {
            'data': 'create_date',
            'render': function(data, type, row) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            'data': 'po_number',
            'render': function(data, type, row) {
                return `<a href="/purchase/orders/${row.purchase_order_id}/">${data}</a>`;
            }
        },
        {'data': 'vendor'},
        {'data': 'status'},
        /*{'data': 'status'},*/
        {'data': 'items_count'},
        {
            'data': 'id', 
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                return (
                    `<div class="d-inline-block">
                        <a href="/purchase/receipts/${data}/" class="btn text-primary btn-icon item-edit" title="View purchase receive"><i class="icon-base bx bx-show"></i></a>
                    </div>`
                );
            }
        }
    ]
}
const purchaseReceivesDt = initDataTable("#purchase_receives_table", purchaseReceivesDtOptions);
</script>
@endpush