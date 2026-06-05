@extends('layouts.app')
@section('title', 'CRM - Pull Leads (Integrations)')

@section('content')

<!-- Content -->
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Pull Leads</h4>
            <p class="text-muted mb-0 small">Manage integrations</p>
        </div>
        @if(tenantContext()->canDo('crm_integrations', 'write'))
        <div>
            <button class="btn btn-primary btn-sm" type="button" onclick="openIntegrationFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i>Add New</button>
        </div>
        @endif
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="crm_integrations_table">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Source</th>
                        <th>Webhook URL</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@if(tenantContext()->canDo('crm_integrations', 'write'))
@includeOnce('app.components.drawers.crm.integrations.add-edit')
@endif

@endsection

@push('scripts')
<script>
const WEBHOOK_BASE_URL = '{{ config("app.url") }}';

const delIntegrationCallback = async function(id) {
    try {
        const response = await api.delete('/crm/integrations', { data: { id } });
        notyf.success(response.data.message);
        integrationsDt.ajax.reload();
    } catch(error) {
        handleApiError(error);
    }
};

const delIntegration = function(id) {
    showConfirmation(DELETE_CONFIRM_MESSAGE, 'warning', {text: 'Delete', class: 'btn-label-danger', callback: function() { delIntegrationCallback(id); }});
};

const copyWebhookUrl = function(url) {
    navigator.clipboard.writeText(url).then(function() {
        notyf.success('Webhook URL copied to clipboard');
    }).catch(function() {
        // Fallback for older browsers
        const ta = document.createElement('textarea');
        ta.value = url;
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        document.body.removeChild(ta);
        notyf.success('Webhook URL copied to clipboard');
    });
};

const integrationsDtOptions = {
    ajax: {
        url: '/api/crm/integrations',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            data: 'name',
            render: function(data) {
                return '<span class="fw-medium">' + data + '</span>';
            }
        },
        {
            data: 'source',
            render: function(data) {
                const labels = { indiamart: 'India Mart' };
                return '<span class="badge text-bg-info">' + (labels[data] || data) + '</span>';
            }
        },
        {
            data: 'token',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                const url = WEBHOOK_BASE_URL + 'api/webhooks/' + row.source + '/' + data;
                return (
                    '<div class="d-flex align-items-center gap-2">' +
                        '<code class="text-truncate d-inline-block" style="max-width:360px;" title="' + url + '">' + url + '</code>' +
                        '<button type="button" class="btn btn-sm btn-icon btn-label-secondary flex-shrink-0" title="Copy URL" onclick="copyWebhookUrl(\'' + url + '\')">' +
                            '<i class="bx bx-copy"></i>' +
                        '</button>' +
                    '</div>'
                );
            }
        },
        {
            data: 'is_active',
            render: function(data) {
                return data == 1
                    ? '<span class="badge text-bg-success">Active</span>'
                    : '<span class="badge text-bg-secondary">Inactive</span>';
            }
        },
        {
            data: 'id',
            orderable: false,
            searchable: false,
            render: function(data) {
                const editBtn   = canDo('crm_integrations', 'write')  ? '<a href="javascript:void(0);" onclick="openIntegrationFormDrawer(' + data + ')" class="btn text-warning btn-icon" title="Edit"><i class="icon-base bx bxs-edit"></i></a>' : '';
                const deleteItem = canDo('crm_integrations', 'delete') ? '<li><a href="javascript:void(0);" onclick="delIntegration(' + data + ')" class="dropdown-item text-danger">Delete</a></li>' : '';
                const dotsMenu  = deleteItem ? '<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a><ul class="dropdown-menu dropdown-menu-end">' + deleteItem + '</ul>' : '';
                return '<div class="d-inline-block">' + editBtn + dotsMenu + '</div>';
            }
        }
    ]
};

const integrationsDt = initDataTable('#crm_integrations_table', integrationsDtOptions);
</script>
@endpush
