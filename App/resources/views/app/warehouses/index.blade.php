@extends('layouts.app')
@section('title', 'Company - Warehouses')

@section('content')
<!-- Content -->
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Warehouses</h4>       
            <p class="text-muted mb-0 small">Manage your inventory warehouses</p>
        </div>
        @if(tenantContext()->canDo('inventory_warehouses', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" onClick="openWarehouseFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i> Add New</button>
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="locations_table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Code</th>
                        <th>Type</th>
                        <th>Address</th>
                        <th>Status</th>                        
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@if(tenantContext()->canDo('inventory_warehouses', 'write'))
@includeOnce('app.components.drawers.company.warehouses.add-edit')
@endif

@endsection

@push('scripts')
<script>
const delLocCallback = async function(id) {

    try {
        
        const response = await api.delete("/inv/warehouses", {data: {'id': id}});
        const { message } = response.data;

        notyf.success(message);

        if( typeof(warehousesDt) != "undefined" ) {
            warehousesDt.ajax.reload()
        }

    } catch(error) {
        
        handleApiError(error);
    }
}

const delWarehouse = function(id) {
    showConfirmation(DELETE_CONFIRM_MESSAGE, "warning", {'text': 'Delete', 'class': 'btn-label-danger', 'callback': function(){delLocCallback(id)}});
}

const locationTypes = @json(config('constants.company.warehouse_types', []));
const warehousesDtOptions = {
    order: [[0, 'asc']],
    ajax: {
        url: '/api/inv/warehouses',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {'data': 'name'},
        {
            'data': 'code',
            'render': function(data, type, row) {
                return data || "-";
            }
        },
        {
            'data': 'type',
            'render': function(data, type, row) {
                return locationTypes[data||'-'] || '-';
            }
        },
        {
            'data': 'address',
            'className': 'text-wrap text-break w-25',
            'render': function(data, type, row) {
                
                const parts = [data, row.address_line2, row.city, row.state, row.country].filter(v => v && v.trim() !== '');
                let address = parts.join(', ');
                if (row.zip) {address += ` - ${row.zip}`;}

                return address || '-';
            }
        },
        {
            'data': 'status',
            'render': function(data, type, row) {
                
                let badgeClass = 'text-bg-secondary';
                let statusLabel = data;
                if( data == "active" ) {
                    badgeClass = 'text-bg-success';
                    statusLabel = "Active";
                }
                else if( data == "inactive" ) {
                    badgeClass = 'text-bg-danger';
                    statusLabel = "Inactive";
                }

                return `<span class="badge ${badgeClass}">${statusLabel}</span>`;
            }
        },        
        {
            'data': 'id', 
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                const editBtn   = canDo('inventory_warehouses', 'write')  ? '<a href="javascript:void(0);" onClick="openWarehouseFormDrawer('+data+')" class="btn text-warning btn-icon item-edit"><i class="icon-base bx bxs-edit"></i></a>' : '';
                const deleteItem = canDo('inventory_warehouses', 'delete') ? '<div class="dropdown-divider"></div><li><a href="javascrip:void(0)" onClick="delWarehouse('+data+')" class="dropdown-item text-danger delete-record">Delete</a></li>' : '';
                return (
                    '<div class="d-inline-block">' +
                        editBtn +
                        '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>' +
                        '<ul class="dropdown-menu dropdown-menu-end">' +
                            '<li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>' +
                            '<li><a href="javascript:void(0);" class="dropdown-item">Archive</a></li>' +
                            deleteItem +
                        '</ul>' +
                    '</div>'
                );
            }
        }
    ]
}
const warehousesDt = initDataTable("#locations_table", warehousesDtOptions);
</script>
@endpush