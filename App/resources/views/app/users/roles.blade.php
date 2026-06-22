@extends('layouts.app')
@section('title', 'User Roles')

@section('content')
<!-- Content -->
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">User Roles</h4>
            <p class="text-muted mb-0">Roles control what users can see and do within the system.</p>
        </div>
        @if(tenantContext()->canDo('company_roles_mgmt', 'write'))
        <button type="button" class="btn btn-primary btn-sm" onclick="openRoleFormDrawer()">
            <i class="icon-base bx bx-plus icon-sm"></i> Add Role
        </button>
        @endif
    </div>

    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="roles_table">
                <thead>
                    <tr>
                        <th>Role</th>
                        <th>Description</th>
                        <th>Permissions</th>
                        <th>Users</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>
<!-- / Content -->

@if(tenantContext()->canDo('company_roles_mgmt', 'write'))
@includeOnce('app.components.drawers.users.roles-add-edit')
@endif
@if(tenantContext()->canDo('company_roles_mgmt', 'write'))
@includeOnce('app.components.drawers.users.roles-permissions')
@endif
@endsection

@push('scripts')
<script>
'use strict';

// ── Delete ──────────────────────────────────────────────────────────────────

const deleteRoleCallback = async function(id) {
    try {
        await api.delete(`/users/roles/${id}`);
        notyf.success('Role deleted successfully.');
        rolesDt.ajax.reload();
    } catch (err) {
        handleApiError(err);
    }
};

const deleteRole = function(id) {
    showConfirmation(
        DELETE_CONFIRM_MESSAGE,
        'warning',
        { text: 'Delete', class: 'btn-label-danger', callback: function() { deleteRoleCallback(id); } }
    );
};


// ── Status toggle ────────────────────────────────────────────────────────────

const toggleRoleStatus = async function(id, currentStatus) {
    const action   = currentStatus === 'active' ? 'Deactivate' : 'Activate';
    const message  = currentStatus === 'active'
        ? 'Deactivating this role will remove access for all users assigned to it. Continue?'
        : 'Are you sure you want to activate this role?';

    showConfirmation(
        message,
        'warning',
        {
            text: action,
            class: currentStatus === 'active' ? 'btn-label-warning' : 'btn-label-success',
            callback: async function() {
                try {
                    await api.post(`/users/roles/${id}/status`);
                    notyf.success(`Role ${action.toLowerCase()}d successfully.`);
                    rolesDt.ajax.reload();
                } catch (err) {
                    handleApiError(err);
                }
            }
        }
    );
};


// ── DataTable ────────────────────────────────────────────────────────────────

const rolesDtOptions = {
    order: [[3, 'desc'], [0, 'asc']],
    ajax: {
        url: '/api/users/roles',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            // 0: Role name + badge
            data: 'name',
            render: function(data, type, row) {
                const isAdmin = parseInt(row.is_admin) === 1;
                const badge   = isAdmin ? ' <span class="badge badge-sm bg-label-warning">Admin</span>' : '';
                return `<span class="fw-medium">${data}</span>${badge}`;
            }
        },
        {
            // 1: Description
            data: 'description',
            orderable: false,
            render: function(data) {
                if (!data) return '<span class="text-muted">—</span>';
                const truncated = data.length > 60 ? data.substring(0, 60) + '…' : data;
                return `<span class="text-muted small">${truncated}</span>`;
            }
        },
        {
            // 2: Permissions — Full Access for Admin, module tags for custom roles
            data: 'activated_modules',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                if (parseInt(row.is_admin) === 1) {
                    return '<span class="badge badge-sm bg-label-warning">Full Access</span>';
                }
                if (!data) return '<span class="text-muted small">No modules activated</span>';
                return data.split(',')
                    .map(function(m) { return `<span class="badge badge-sm bg-label-info me-1">${m.trim()}</span>`; })
                    .join('');
            }
        },
        {
            // 3: User count
            data: 'user_count',
            searchable: false,
            render: function(data) {
                const count = parseInt(data) || 0;
                return count > 0
                    ? `<span class="badge badge-sm bg-label-primary">${count} user${count !== 1 ? 's' : ''}</span>`
                    : '<span class="text-muted small">—</span>';
            }
        },
        {
            // 4: Status
            data: 'status',
            searchable: false,
            render: function(data) {
                return data === 'active'
                    ? '<span class="badge badge-sm bg-label-success">Active</span>'
                    : '<span class="badge badge-sm bg-label-secondary">Inactive</span>';
            }
        },
        {
            // 5: Actions
            data: 'id',
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                if (parseInt(row.is_admin) === 1) {
                    return '';
                }

                const status          = row.status;
                const deactivateLabel = status === 'active' ? 'Deactivate' : 'Activate';

                const canWrite  = canDo('company_roles_mgmt', 'write');
                const canDelete = canDo('company_roles_mgmt', 'delete');
                const permItem     = canWrite  ? `<li><a href="javascript:void(0);" onclick="openRolePermissionsDrawer(${data})" class="dropdown-item">Permissions</a></li>` : '';
                const editItem     = canWrite  ? `<li><a href="javascript:void(0);" onclick="openRoleFormDrawer(${data})" class="dropdown-item">Edit</a></li>` : '';
                const statusItem   = canWrite  ? `<li><hr class="dropdown-divider my-1"></li><li><a href="javascript:void(0);" onclick="toggleRoleStatus(${data}, '${status}')" class="dropdown-item">${deactivateLabel}</a></li>` : '';
                const deleteItem   = canDelete ? `<li><a href="javascript:void(0);" onclick="deleteRole(${data})" class="dropdown-item text-danger">Delete</a></li>` : '';
                if (!canWrite && !canDelete) return '';
                return `<div class="d-inline-block">
                    <a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        ${permItem}${editItem}${statusItem}${deleteItem}
                    </ul>
                </div>`;
            }
        }
    ]
};

const rolesDt = initDataTable('#roles_table', rolesDtOptions);


// Reload after role form drawer saves
const loadRoles = function() {
    if (typeof rolesDt !== 'undefined') {
        rolesDt.ajax.reload();
    }
};
</script>
@endpush
