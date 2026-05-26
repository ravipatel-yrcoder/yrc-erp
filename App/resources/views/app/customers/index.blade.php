@extends('layouts.app')
@section('title', 'Customers')

@section('content')

<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Customers</h4>
            <p class="text-muted mb-0 small">Manage your customers</p>
        </div>
        @if(tenantContext()->canDo('customers', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" onClick="openCustomerFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i> Add New</button>
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="customers_table">
                <thead>
                    <tr>
                        <th>Code</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>State</th>
                        <th>Country</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@if(tenantContext()->canDo('customers', 'write'))
@includeOnce('app.components.drawers.customers.add-edit')
@endif

@endsection

@push('scripts')
<script>
const customersDtOptions = {
    order: [[1, 'asc']],
    ajax: {
        url: '/api/customers',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        { 'data': 'customer_code' },
        { 'data': 'display_name' },
        { 'data': 'email' },
        { 'data': 'phone' },
        { 'data': 'state' },
        { 'data': 'country' },
        {
            'data': 'status',
            'render': function(data) {
                const cls = data === 'active' ? 'bg-label-success' : 'bg-label-secondary';
                return `<span class="badge ${cls}">${data}</span>`;
            }
        },
        {
            'data': 'id',
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                const editBtn = canDo('customers', 'write')
                    ? '<a href="javascript:void(0);" onClick="openCustomerFormDrawer(' + row.id + ')" class="btn text-warning btn-icon" title="Edit customer"><i class="icon-base bx bxs-edit"></i></a>'
                    : '';
                return '<div class="d-inline-block">' + editBtn + '</div>';
            }
        }
    ]
};
const customersDt = initDataTable("#customers_table", customersDtOptions);
</script>
@endpush
