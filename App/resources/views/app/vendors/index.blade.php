@extends('layouts.app')
@section('title', 'Vendors')

@section('content')

<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <h5 class="card-title mb-0">Vendors</h5>
            <button class="btn btn-primary btn-sm" type="button" onClick="openVendorFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i>Add New</button>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="table table-bordered" id="vendors_table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>State</th>
                        <th>Country</th>                        
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@include('app.components.drawers.vendors.add-edit')

@endsection

@push('scripts')
<script>
const vendorsDtOptions = {
    ajax: {
        url: '/api/vendors',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {'data': 'display_name'},
        {'data': 'email'},
        {'data': 'phone'},
        {'data': 'state'},
        {'data': 'country'},
        {'data': 'created_at'},
        {
            'data': 'id', 
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                return (
                    '<div class="d-inline-block">' +
                        '<a href="javascript:void(0);" onClick="openVendorFormDrawer('+row.id+')" class="btn text-warning btn-icon item-edit" title="Edit product"><i class="icon-base bx bxs-edit"></i></a>'+
                        '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>' +
                        '<ul class="dropdown-menu dropdown-menu-end">' +
                            '<li><a href="javascrip:void(0)" onClick="delProduct('+data+')" class="dropdown-item text-danger delete-record" title="Delete product">Delete</a></li>' +
                        '</ul>' +
                    '</div>'
                );
            }
        }
    ]
}
const vendorsDt = initDataTable("#vendors_table", vendorsDtOptions);
</script>
@endpush