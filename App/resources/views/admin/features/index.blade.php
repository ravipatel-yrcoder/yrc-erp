@extends('layouts.admin')
@section('title', 'Features — Admin')

@section('content')
<div class="container-fluid py-4 px-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h5 class="fw-bold mb-0">Features</h5>
            <p class="text-muted mb-0 small">Manage system features and their module assignments</p>
        </div>
        <button class="btn btn-primary btn-sm" onclick="openFeatureFormDrawer()">
            <i class="bx bx-plus me-1"></i> New Feature
        </button>
    </div>

    <div class="card shadow-sm">
        <div class="card-header border-bottom pb-3">
            <div class="d-flex align-items-center gap-3">
                <label class="form-label mb-0 text-nowrap fw-medium small">Filter by Module</label>
                <select id="moduleFilter" class="form-select form-select-sm" style="max-width:220px;">
                    <option value="">All Modules</option>
                    @foreach($modules as $mod)
                    <option value="{{ $mod->id }}">{{ $mod->name }}</option>
                    @endforeach
                </select>
            </div>
        </div>
        <div class="card-datatable table-responsive">
            <table class="table table-bordered table-hover" id="featuresTable">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Key</th>
                        <th>Route</th>
                        <th>Type</th>                        
                        <th>Modules</th>
                        <th>Active</th>
                        <th style="width:90px;">Actions</th>
                    </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

@include('admin.components.drawers.features.add-edit')
@endsection

@push('scripts')
@php
    $featuresJson  = json_encode($features);
    $modulesJson   = json_encode($modules);
@endphp
<script>
const adminFeaturesData = {!! $featuresJson !!};
const adminModulesData  = {!! $modulesJson !!};

const accessLevelBadge = {
    subscription: '<span class="badge bg-label-primary">Subscription</span>',
    core:         '<span class="badge bg-label-info">Core</span>',
    super_admin:  '<span class="badge bg-label-danger">Super Admin</span>',
};

const routeTypeLabels = { front: 'Front', api: 'API', both: 'Both' };
const routeTypeBadge  = { front: 'bg-label-primary', api: 'bg-label-danger', both: 'bg-label-success' };

let featuresDt;
let activeModuleFilter = '';

// Custom DataTable search: filter rows by selected module_id
$.fn.dataTable.ext.search.push(function(settings, data, dataIndex, rowData) {
    if (!activeModuleFilter) return true;
    return (rowData.module_id_list || []).includes(parseInt(activeModuleFilter));
});

document.addEventListener('DOMContentLoaded', function () {

    featuresDt = initDataTable('#featuresTable', {
        serverSide: false,
        data: adminFeaturesData,
        columns: [
            { data: 'name' },
            { data: 'key', render: d => `<code class="text-muted small">${d}</code>` },
            { data: 'route', render: d => d ? `<code class="small">${d}</code>` : '<span class="text-muted">—</span>' },
            {
                data: 'route_type',
                render: d => `<span class="badge ${routeTypeBadge[d] || 'bg-label-secondary'}">${routeTypeLabels[d] || d}</span>`
            },            
            {
                data: 'all_modules',
                render: d => d
                    ? d.split(', ').map(n => `<span class="badge bg-label-secondary me-1">${n}</span>`).join('')
                    : '<span class="text-muted">—</span>'
            },
            {
                data: 'is_active',
                render: d => d == 1
                    ? '<span class="badge bg-label-success">Yes</span>'
                    : '<span class="badge bg-label-secondary">No</span>'
            },
            {
                data: 'id',
                orderable: false,
                render: function(id) {
                    return `
                        <button class="btn btn-sm btn-icon btn-text-secondary" title="Edit" onclick="openFeatureFormDrawer(${id})">
                            <i class="bx bx-edit"></i>
                        </button>
                        <button class="btn btn-sm btn-icon btn-text-danger" title="Delete" onclick="deleteFeature(${id})">
                            <i class="bx bx-trash"></i>
                        </button>`;
                }
            },
        ],
        order: [[4, 'asc'], [5, 'asc'], [0, 'asc']],
    });

    document.getElementById('moduleFilter').addEventListener('change', function() {
        activeModuleFilter = this.value;
        featuresDt.draw();
    });
});

const deleteFeature = async function(id) {
    showConfirmation('Delete this feature?', 'warning',
        {
            text: 'Yes, delete',
            callback: async function() {
                try {
                    const resp = await fetch(`/admin/features/${id}/delete`, { method: 'POST' });
                    const json = await resp.json();
                    if (!resp.ok) {
                        notyf.error(json.message || 'Delete failed');
                        return;
                    }
                    notyf.success(json.message || 'Deleted');
                    reloadFeaturesTable();
                } catch(e) {
                    notyf.error('Delete failed');
                }
            }
        },
        { text: 'Cancel' }
    );
};

const reloadFeaturesTable = async function() {
    try {
        const resp = await fetch('/admin/features/form-context');
        const json = await resp.json();
        // Reload page to refresh table data (simple approach for admin tool)
        window.location.reload();
    } catch(e) {}
};
</script>
@endpush
