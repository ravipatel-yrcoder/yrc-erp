@extends('layouts.app')
@section('title', 'Customers')

@section('content')

<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <h5 class="card-title mb-0">Customers</h5>
            <button class="btn btn-primary btn-sm" type="button" onClick="openCustomerFormDrawer();">
                <i class="icon-base bx bx-plus icon-sm"></i>Add New
            </button>
        </div>
        <div class="card-datatable text-nowrap">
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

@include('app.components.drawers.customers.add-edit')

@endsection

@push('scripts')
<script>
const customersDtOptions = {
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
                return (
                    '<div class="d-inline-block">' +
                        '<a href="javascript:void(0);" onClick="openCustomerFormDrawer(' + row.id + ')" class="btn text-warning btn-icon" title="Edit customer"><i class="icon-base bx bxs-edit"></i></a>' +
                    '</div>'
                );
            }
        }
    ]
};
const customersDt = initDataTable("#customers_table", customersDtOptions);
</script>
@endpush
