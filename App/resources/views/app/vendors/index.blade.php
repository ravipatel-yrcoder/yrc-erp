@extends('layouts.app')
@section('title', 'Vendors')

@section('content')

<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Vendors</h4>
            <p class="text-muted mb-0 small">Manage your vendors</p>
        </div>
        @if(tenantContext()->canDo('vendors', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" onClick="openVendorFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i> Add New</button>
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
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

@if(tenantContext()->canDo('vendors', 'write'))
@includeOnce('app.components.drawers.vendors.add-edit')
@endif

@endsection

@push('scripts')
<script>
const vendorsDtOptions = {
    order: [[0, 'asc']],
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
        {
            'data': 'created_at',
            'render': function(data, type, row) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            'data': 'id', 
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                const editBtn = canDo('vendors', 'write')
                    ? '<a href="javascript:void(0);" onClick="openVendorFormDrawer('+row.id+')" class="btn text-warning btn-icon item-edit" title="Edit vendor"><i class="icon-base bx bxs-edit"></i></a>'
                    : '';
                const deleteItem = canDo('vendors', 'delete')
                    ? '<li><a href="javascrip:void(0)" onClick="delProduct('+data+')" class="dropdown-item text-danger delete-record" title="Delete vendor">Delete</a></li>'
                    : '';
                const dotsMenu = deleteItem
                    ? '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a><ul class="dropdown-menu dropdown-menu-end">' + deleteItem + '</ul>'
                    : '';
                return '<div class="d-inline-block">' + editBtn + dotsMenu + '</div>';
            }
        }
    ]
}
const vendorsDt = initDataTable("#vendors_table", vendorsDtOptions);
</script>
@endpush