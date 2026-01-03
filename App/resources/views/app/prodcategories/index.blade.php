@extends('layouts.app')
@section('title', 'Product - Categories')

@section('content')
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <h5 class="card-title mb-0">Categories</h5>
            <button class="btn btn-primary btn-sm" type="button" onClick="openProdCategoryFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i>Add New</button>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="table table-bordered" id="categories_table">
                <thead>
                    <tr>
                        <th>Category</th>
                        <th>Code</th>                        
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

@include('app.components.drawers.categories.add-edit')

@endsection

@push('scripts')
<script>
// Flatten tree while keeping hierarchy and indentation level
const formatCategoryTree = function(categories, level = 0) {
    
    if (!Array.isArray(categories)) return [];

    let result = [];

    for (const cat of categories) {
        // Add current category with level
        result.push({ ...cat, level });

        // Recursively add children
        if (Array.isArray(cat.children) && cat.children.length > 0) {
            result = result.concat(formatCategoryTree(cat.children, level + 1));
        }
    }

    return result;
}


const delCatCallback = async function(id) {
    
    try {
        
        const response = await api.delete("/product-categories", {data: {'id': id}});
        const { message } = response.data;

        notyf.success(message);

        if( typeof(categoriesDt) != "undefined" ) {
            categoriesDt.ajax.reload()
        }

    } catch(error) {
        
        handleApiError(error);
    }
}

const delCategory = function(id) {
    showConfirmation(DELETE_CONFIRM_MESSAGE, "warning", {'text': 'Delete', 'class': 'btn-label-danger', 'callback': function(){delCatCallback(id)}});
}

const categoriesDtOptions = {
    ordering: false,
    ajax: {
        url: '/api/product-categories?format=tree',
        dataSrc: function(json) {
            return formatCategoryTree(json.data);
        }
    },
    columns: [
        {
            'data': 'category',
            'render': function(data, type, row) {
                
                const indent = '&nbsp;'.repeat(row.level * 4);
                const icon_url = row.icon || "{{asset('/assets/img/no-images/no-image-64.png')}}";
                const cat_icon = `<img src="${icon_url}" alt="category-${row.id}" class="rounded">`;

                return `
                <div class="d-flex align-items-center">
                    <div class="avatar-wrapper me-3 rounded-2 bg-label-secondary">
                        <div class="avatar">${cat_icon}</div>
                    </div>
                    <div class="d-flex flex-column justify-content-center">
                        <span class="text-heading text-wrap fw-medium">
                            ${indent}${row.level > 0 ? '- ' : ''}${row.category}
                        </span>
                        <span class="text-truncate mb-0 d-none d-sm-block">
                            <small>${indent}${row.level > 0 ? ' ' : ''}${row.description || ''}</small>
                        </span>
                    </div>
                </div>`;
            }
        },
        {'data': 'code'},        
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
                        '<a href="javascript:void(0);" onClick="openProdCategoryFormDrawer('+data+')" class="btn text-warning btn-icon item-edit"><i class="icon-base bx bxs-edit"></i></a>'+
                        '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>' +
                        '<ul class="dropdown-menu dropdown-menu-end">' +
                            '<li><a href="javascript:void(0);" class="dropdown-item">Details</a></li>' +
                            '<div class="dropdown-divider"></div>' +
                            '<li><a href="javascrip:void(0)" onclick="delCategory('+data+')" class="dropdown-item text-danger delete-record">Delete</a></li>' +
                        '</ul>' +
                    '</div>'
                );
            }
        }
    ]
};
const categoriesDt = initDataTable("#categories_table", categoriesDtOptions);
</script>
@endpush