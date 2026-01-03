@extends('layouts.app')
@section('title', 'Products')

@section('content')
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <h5 class="card-title mb-0">Products</h5>
            <button class="btn btn-primary btn-sm" type="button" onClick="openProductFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i>Add New</button>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="table table-bordered" id="products_table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Status</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@include('app.components.drawers.products.add-edit')

@endsection

@push('scripts')
<script>
const delProdCallback = async function(id) {

    try {
        
        const response = await api.delete("/products", {data: {'id': id}});
        const { message } = response.data;

        notyf.success(message);

        if( typeof(productsDt) != "undefined" ) {
            productsDt.ajax.reload()
        }

    } catch(error) {
        
        handleApiError(error);
    }
}

const delProduct = function(id) {
    showConfirmation(DELETE_CONFIRM_MESSAGE, "warning", {'text': 'Delete', 'class': 'btn-label-danger', 'callback': function(){delProdCallback(id)}});
}

const productsDtOptions = {
    ajax: {
        url: '/api/products',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {'data': 'name'},
        {
            'data': 'category',
            'render': function(data, type, row) {
                return data || "-";
            }
        },
        {'data': 'sale_price'},
        {
            'data': 'id',
            'render': function(data, type, row) {
                return 'N.A';
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
        {'data': 'created_at'},
        {
            'data': 'id', 
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                return (
                    '<div class="d-inline-block">' +
                        '<a href="javascript:void(0);" onClick="openProductFormDrawer('+row.master_id+')" class="btn text-warning btn-icon item-edit" title="Edit product"><i class="icon-base bx bxs-edit"></i></a>'+
                        '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>' +
                        '<ul class="dropdown-menu dropdown-menu-end">' +
                            '<li><a href="/inv/products/'+data+'/stock-locations/" class="dropdown-item" title="Manage stock">Manage stock</a></li>' +
                            '<div class="dropdown-divider"></div>' +
                            '<li><a href="javascrip:void(0)" onClick="delProduct('+data+')" class="dropdown-item text-danger delete-record" title="Delete product">Delete</a></li>' +
                        '</ul>' +
                    '</div>'
                );
            }
        }
    ]
}
const productsDt = initDataTable("#products_table", productsDtOptions);
</script>
@endpush