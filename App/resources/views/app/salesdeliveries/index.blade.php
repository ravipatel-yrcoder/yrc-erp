@extends('layouts.app')
@section('title', 'Sales Deliveries')

@section('content')
<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Sales Deliveries</h4>
            <p class="text-muted mb-0 small">Manage your deliveries</p>
        </div>
        <div>
            <button class="btn btn-primary btn-sm" type="button" onClick="openDeliveryFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i> New Delivery</button>
        </div>
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="sales_deliveries_table">
                <thead>
                    <tr>
                        <th>DN#</th>
                        <th>SO#</th>
                        <th>Customer</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Dispatch Date</th>
                        <th>Delivery Date</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@include('app.components.drawers.sales-deliveries.add-edit')

@endsection

@push('scripts')
<script>
const dnStatusMap = {
    draft: ['Draft', 'secondary'],
    dispatched: ['Dispatched', 'primary'],
    delivered: ['Delivered', 'success'],
    returned: ['Returned', 'warning'],
    lost: ['Lost', 'danger'],
    cancelled: ['Cancelled',  'dark'],
};

const salesDeliveriesDtOptions = {
    order: [[7, 'desc']],
    ajax: {
        url: '/api/sales/deliveries',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            'data': 'dn_number',
            'render': function(data, type, row) {
                return `<a href="/sales/deliveries/${row.id}/">${data}</a>`;
            }
        },
        {'data': 'so_number', 'defaultContent': '-'},
        {'data': 'customer',  'defaultContent': '-'},
        {'data': 'location',  'defaultContent': '-'},
        {
            'data': 'status',
            'render': function(data) {
                const s = dnStatusMap[data] || [data, 'secondary'];
                return `<span class="badge bg-label-${s[1]}">${s[0]}</span>`;
            }
        },
        {
            'data': 'dispatch_date', 
            'defaultContent': '-',
            'render': function(data, type, row) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            'data': 'delivery_date', 
            'defaultContent': '-',
            'render': function(data, type, row) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            'data': 'id',
            'orderable': false,
            'searchable': false,
            'render': function(data) {
                return (
                    `<div class="d-inline-block">
                        <a href="/sales/deliveries/${data}/" class="btn text-primary btn-icon item-edit" title="View delivery note"><i class="icon-base bx bx-show"></i></a>
                    </div>`
                );
            }
        }
    ]
};
const salesDeliveriesDt = initDataTable("#sales_deliveries_table", salesDeliveriesDtOptions);
</script>
@endpush
