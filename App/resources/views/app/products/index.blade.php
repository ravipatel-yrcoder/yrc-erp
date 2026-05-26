@extends('layouts.app')
@section('title', 'Products')

@section('content')
<!-- Content -->
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Products</h4>
            <p class="text-muted mb-0 small">Manage your products</p>
        </div>
        @if(tenantContext()->canDo('products', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" onClick="openProductFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i> Add New</button>
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="products_table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Category</th>
                        <th>Price</th>
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

@if(tenantContext()->canDo('products', 'write'))
@includeOnce('app.components.drawers.products.add-edit')
@endif

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
    order: [[5, 'desc']],
    ajax: {
        url: '/api/products',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            'data': 'name',
            'render': function(data, type, row) {
                const imgUrl = row.image_url || "{{asset('/assets/img/img-preview.png')}}";
                const description = row.description || "";
                
                let html = `<div class="d-flex justify-content-start align-items-center product-name">
                    <div class="avatar-wrapper">
                        <div class="avatar avatar me-2 me-sm-4 rounded-2 bg-label-secondary">
                            <img src="${imgUrl}" alt="${data}" class="rounded" />
                        </div>
                    </div>
                    <div class="d-flex flex-column">
                        <h6 class="text-nowrap mb-0">${data}</h6>
                        ${description ? `<small class="text-truncate d-none d-sm-block w-px-150">${description}</small>` : ''}
                    </div>
                </div>`;
                
                return html;
            }
        },
        {
            'data': 'category',
            'render': function(data, type, row) {
                return data || "-";
            }
        },
        {
            'data': 'sale_price',
            'render': function(data, type, row) {
                return formatCurrency(data);
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
                const isInventory = ['quantity', 'lot', 'serial'].includes(row.stock_tracking_method);
                const manageStockItem = isInventory
                    ? '<li><a href="/inv/products/'+data+'/stock-locations/" class="dropdown-item">Manage stock</a></li><div class="dropdown-divider"></div>'
                    : '';
                const editBtn = canDo('products', 'write')
                    ? '<a href="javascript:void(0);" onClick="openProductFormDrawer('+data+')" class="btn text-warning btn-icon item-edit" title="Edit product"><i class="icon-base bx bxs-edit"></i></a>'
                    : '';
                const deleteItem = canDo('products', 'delete')
                    ? '<li><a href="javascript:void(0);" onClick="delProduct('+data+')" class="dropdown-item text-danger delete-record">Delete</a></li>'
                    : '';
                return (
                    '<div class="d-inline-block">' +
                        editBtn +
                        '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>' +
                        '<ul class="dropdown-menu dropdown-menu-end">' +
                            manageStockItem +
                            deleteItem +
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