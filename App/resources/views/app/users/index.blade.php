@extends('layouts.app')
@section('title', 'Users')

@section('content')
<!-- Content -->
<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Users</h4>
            <p class="text-muted mb-0">Manage company users and their access roles.</p>
        </div>
        @if(tenantContext()->canDo('company_users', 'write'))
        <button type="button" class="btn btn-primary btn-sm" onclick="openUserFormDrawer()">
            <i class="icon-base bx bx-plus icon-sm"></i> Add New
        </button>
        @endif
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Role</label>
                    <select id="filter_role" class="form-select form-select-sm">
                        <option value="">All Roles</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Status</label>
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="button" id="applyUserFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetUserFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Table -->
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="users_list">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
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
<!-- / Content -->

@if(tenantContext()->canDo('company_users', 'write'))
@includeOnce('app.components.drawers.users.add-edit')
@endif
@endsection

@push('scripts')
<script>
'use strict';

const currentUserId = {{ auth()->user()->id }};

let userFilters = { filter_role: '', filter_status: '' };


// ── Filters ──────────────────────────────────────────────────────────────────

const loadRoleFilterOptions = async function() {
    try {
        const res  = await api.get('/users/roles');
        const roles = res.data.data || [];
        const sel  = document.getElementById('filter_role');
        roles.forEach(function(r) {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = r.name;
            sel.appendChild(opt);
        });
    } catch (e) { /* non-critical */ }
};

document.getElementById('applyUserFilters').addEventListener('click', function() {
    userFilters.filter_role   = document.getElementById('filter_role').value;
    userFilters.filter_status = document.getElementById('filter_status').value;
    usersDt.ajax.reload();
});

document.getElementById('resetUserFilters').addEventListener('click', function() {
    document.getElementById('filter_role').value   = '';
    document.getElementById('filter_status').value = '';
    userFilters = { filter_role: '', filter_status: '' };
    usersDt.ajax.reload();
});


// ── Status toggle ────────────────────────────────────────────────────────────

function toggleUserStatus(id, currentStatus) {
    const isActive = currentStatus === 'active';
    showConfirmation(
        isActive ? 'Deactivate this user? They will lose access immediately.' : 'Activate this user?',
        isActive ? 'warning' : 'info',
        {
            text: isActive ? 'Deactivate' : 'Activate',
            callback: async function() {
                try {
                    const response = await api.post(`/users/${id}/status`);
                    notyf.success(response.data.message);
                    usersDt.ajax.reload();
                } catch (error) {
                    handleApiError(error);
                }
            }
        },
        { text: 'Cancel' }
    );
}


// ── DataTable ────────────────────────────────────────────────────────────────

const usersDtOptions = {
    order: [[0, 'asc']],
    ajax: {
        url: '/api/users',
        data: function(d) {
            d.filter_role   = userFilters.filter_role;
            d.filter_status = userFilters.filter_status;
            return d;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        { data: 'name' },
        { data: 'email' },
        {
            data: 'role_name',
            defaultContent: '—',
            render: function(data) {
                return data ? data : '<span class="text-muted">—</span>';
            }
        },
        {
            data: 'status',
            searchable: false,
            render: function(data) {
                const badgeClass = data === 'active' ? 'bg-label-success' : 'bg-label-secondary';
                const label = data ? (data.charAt(0).toUpperCase() + data.slice(1)) : '—';
                return `<span class="badge ${badgeClass}">${label}</span>`;
            }
        },
        {
            data: 'created_by_name',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                if (parseInt(row.is_company) === 1) return '<span class="text-muted">System</span>';
                return data ? data : '<span class="text-muted">—</span>';
            }
        },
        {
            data: 'created_at',
            searchable: false,
            render: function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                const isSelf      = row.id === currentUserId;
                const isCompany   = parseInt(row.is_company) === 1;
                const isActive    = row.status === 'active';
                const statusLabel = isActive ? 'Deactivate' : 'Activate';

                // Company owner: only show edit, no status toggle
                const dotsMenu = (isSelf || isCompany) ? '' : `
                    <div class="dropdown">
                        <a href="javascript:void(0);"
                           class="btn text-primary btn-icon dropdown-toggle hide-arrow"
                           data-bs-toggle="dropdown">
                            <i class="icon-base bx bx-dots-vertical-rounded"></i>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <a href="javascript:void(0);"
                                   onclick="toggleUserStatus(${row.id}, '${row.status}')"
                                   class="dropdown-item">${statusLabel}</a>
                            </li>
                        </ul>
                    </div>`;

                const editBtn = canDo('company_users', 'write')
                    ? `<a href="javascript:void(0);" onclick="openUserFormDrawer(${row.id})" class="btn text-warning btn-icon item-edit" title="Edit user"><i class="icon-base bx bxs-edit"></i></a>`
                    : '';
                return `<div class="d-flex align-items-center gap-1">${editBtn}${dotsMenu}</div>`;
            }
        }
    ]
};

const usersDt = initDataTable('#users_list', usersDtOptions);

document.addEventListener('DOMContentLoaded', loadRoleFilterOptions);
</script>
@endpush
