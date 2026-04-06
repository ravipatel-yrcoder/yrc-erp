@extends('layouts.app')
@section('title', 'CRM - Pipeline Stages')

@section('content')

<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <h5 class="card-title mb-0">Pipeline Stages</h5>
            <button class="btn btn-primary btn-sm" type="button" onClick="openStageFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i>Add New</button>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="table table-bordered" id="crm_stages_table">
                <thead>
                    <tr>
                        <th>Order</th>
                        <th>Stage Name</th>
                        <th>Probability</th>
                        <th>Type</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@include('app.components.drawers.crm.stages.add-edit')

@endsection

@push('scripts')
<script>
const delStageCallback = async function(id) {
    try {
        const response = await api.delete("/crm/stages", {data: {'id': id}});
        const { message } = response.data;
        notyf.success(message);
        if( typeof(stagesDt) != "undefined" ) {
            stagesDt.ajax.reload();
        }
    } catch(error) {
        handleApiError(error);
    }
}

const delStage = function(id) {
    showConfirmation(DELETE_CONFIRM_MESSAGE, "warning", {'text': 'Delete', 'class': 'btn-label-danger', 'callback': function(){delStageCallback(id)}});
}

const stagesDtOptions = {
    ordering: false,
    serverSide: false,
    ajax: {
        url: '/api/crm/stages',
        dataSrc: function(json) {
            return json.data || [];
        }
    },
    columns: [
        {
            'data': 'sort_order',
            'render': function(data) {
                return '<span class="text-muted">' + data + '</span>';
            }
        },
        {
            'data': 'name',
            'render': function(data, type, row) {
                let colorDot = '';
                if( row.color ) {
                    colorDot = '<span class="d-inline-block rounded-circle me-2" style="width:10px;height:10px;background:' + row.color + '"></span>';
                }
                return colorDot + '<span class="fw-medium">' + data + '</span>';
            }
        },
        {
            'data': 'probability',
            'render': function(data) {
                return data + '%';
            }
        },
        {
            'data': 'is_won',
            'render': function(data, type, row) {
                if( row.is_won == 1 ) {
                    return '<span class="badge text-bg-success">Won</span>';
                }
                if( row.is_lost == 1 ) {
                    return '<span class="badge text-bg-danger">Lost</span>';
                }
                return '<span class="badge text-bg-info">Normal</span>';
            }
        },
        {
            'data': 'status',
            'render': function(data) {
                if( data === 'active' ) {
                    return '<span class="badge text-bg-success">Active</span>';
                }
                return '<span class="badge text-bg-secondary">Inactive</span>';
            }
        },
        {
            'data': 'id',
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                return (
                    '<div class="d-inline-block">' +
                        '<a href="javascript:void(0);" onClick="openStageFormDrawer('+data+')" class="btn text-warning btn-icon item-edit" title="Edit stage"><i class="icon-base bx bxs-edit"></i></a>' +
                        '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>' +
                        '<ul class="dropdown-menu dropdown-menu-end">' +
                            '<div class="dropdown-divider"></div>' +
                            '<li><a href="javascript:void(0);" onclick="delStage('+data+')" class="dropdown-item text-danger delete-record">Delete</a></li>' +
                        '</ul>' +
                    '</div>'
                );
            }
        }
    ]
};
const stagesDt = initDataTable("#crm_stages_table", stagesDtOptions);
</script>
@endpush
