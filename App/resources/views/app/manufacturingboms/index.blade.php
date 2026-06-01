@extends('layouts.app')
@section('title', 'Bills of Materials')

@section('content')

<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Bills of Materials</h4>
            <p class="text-muted mb-0 small">Define the components needed to manufacture each product</p>
        </div>
        @if(tenantContext()->canDo('manufacturing_boms', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" onclick="openBomFormDrawer()">
                <i class="icon-base bx bx-plus icon-sm"></i> Add BOM
            </button>
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="boms_table">
                <thead>
                    <tr>
                        <th>Finished Product</th>
                        <th>BOM Name</th>
                        <th>Output Qty</th>
                        <th>Components</th>
                        <th>Default</th>
                        <th>Status</th>
                        <th>Created By</th>
                        <th>Created At</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>

@if(tenantContext()->canDo('manufacturing_boms', 'write'))
@includeOnce('app.components.drawers.manufacturing-boms.add-edit')
@endif

@endsection

@push('scripts')
<script>
const bomsDtOptions = {
    order: [[0, 'asc']],
    ajax: {
        url: '/api/manufacturing/boms',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        { data: 'product_name' },
        { data: 'name' },
        {
            data: 'output_qty',
            render: function(data) { return formatQty(data); }
        },
        { data: 'component_count' },
        {
            data: 'is_default',
            render: function(data) {
                return data == 1
                    ? '<span class="badge bg-label-success">Yes</span>'
                    : '<span class="badge bg-label-secondary">No</span>';
            }
        },
        {
            data: 'status',
            render: function(data) {
                return data === 'active'
                    ? '<span class="badge bg-label-primary">Active</span>'
                    : '<span class="badge bg-label-warning">Inactive</span>';
            }
        },
        { data: 'created_by_name' },
        {
            data: 'created_at',
            render: function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            data: 'id',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                const editBtn = canDo('manufacturing_boms', 'write')
                    ? `<a href="javascript:void(0);" onclick="openBomFormDrawer(${row.id})" class="btn text-warning btn-icon item-edit" title="Edit BOM"><i class="icon-base bx bxs-edit"></i></a>`
                    : '';
                const deleteItem = canDo('manufacturing_boms', 'delete')
                    ? `<li><a href="javascript:void(0);" onclick="deleteBom(${data})" class="dropdown-item text-danger">Delete</a></li>`
                    : '';
                const dotsMenu = deleteItem
                    ? `<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a><ul class="dropdown-menu dropdown-menu-end">${deleteItem}</ul>`
                    : '';
                return `<div class="d-inline-block">${editBtn}${dotsMenu}</div>`;
            }
        }
    ]
};

const bomsDt = initDataTable("#boms_table", bomsDtOptions);

const deleteBomCallback = async function(id) {
    try {
        const response = await api.delete(`/manufacturing/boms/${id}`);
        const { message } = response.data;
        notyf.success(message);
        bomsDt.ajax.reload();
    } catch(err) {
        handleApiError(err);
    }
};

const deleteBom = function(id) {
    showConfirmation(DELETE_CONFIRM_MESSAGE, "warning", {'text': 'Delete', 'class': 'btn-label-danger', 'callback': function(){ deleteBomCallback(id); }});
};
</script>
@endpush
