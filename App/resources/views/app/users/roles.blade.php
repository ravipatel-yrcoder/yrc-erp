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
        <button type="button" class="btn btn-primary btn-sm" onclick="openRoleFormDrawer()">
            <i class="icon-base bx bx-plus icon-sm"></i> Add Role
        </button>
    </div>

    <div id="rolesContainer" class="row g-4">
        <div class="col-12 text-center py-5 text-muted" id="rolesLoading">
            <div class="spinner-border spinner-border-sm me-2" role="status"></div> Loading roles...
        </div>
    </div>

</div>
<!-- / Content -->

@include('app.components.drawers.users.roles-add-edit')
@include('app.components.drawers.users.roles-permissions')
@endsection

@push('scripts')
<script>
const roleCardTemplate = function(role) {
    const systemBadge = role.is_system == 1 ? '<span class="badge bg-label-info ms-1">System</span>' : '';
    const superBadge  = role.is_super  == 1 ? '<span class="badge bg-label-warning ms-1">Admin</span>'  : '';
    const statusBadge = role.status === 'active'
        ? '<span class="badge bg-label-success">Active</span>'
        : '<span class="badge bg-label-secondary">Inactive</span>';

    const description = role.description
        ? `<p class="text-muted small mb-0 mt-1">${role.description}</p>`
        : '';

    const editBtn = `<button class="btn btn-sm btn-icon btn-label-primary ms-2" title="Edit" onclick="openRoleFormDrawer(${role.id})">
                        <i class="bx bx-edit"></i>
                     </button>`;

    const permBtn = role.is_super == 1
        ? `<button class="btn btn-sm btn-icon btn-label-secondary ms-1" title="Full Admin Access — no grants needed" disabled>
               <i class="bx bx-shield-check"></i>
           </button>`
        : `<button class="btn btn-sm btn-icon btn-label-info ms-1" title="Manage Permissions" onclick="openRolePermissionsDrawer(${role.id})">
               <i class="bx bx-key"></i>
           </button>`;

    return `
    <div class="col-md-4 col-sm-6">
        <div class="card h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-2">
                    <div>
                        <h6 class="fw-semibold mb-0">${role.name}${superBadge}${systemBadge}</h6>
                        ${description}
                    </div>
                    <div class="d-flex align-items-center">
                        ${statusBadge}
                        ${editBtn}
                        ${permBtn}
                    </div>
                </div>
                <hr class="my-3">
                <div class="d-flex align-items-center gap-1 text-muted small">
                    <i class="bx bx-user"></i>
                    <span>${role.user_count} active user${role.user_count != 1 ? 's' : ''}</span>
                </div>
            </div>
        </div>
    </div>`;
};

const loadRoles = async function() {
    try {
        const response = await api.get('/users/roles');
        const roles = response.data.data || [];

        const container = document.getElementById('rolesContainer');

        if (roles.length === 0) {
            container.innerHTML = '<div class="col-12 text-center py-5 text-muted">No roles found.</div>';
            return;
        }

        container.innerHTML = roles.map(roleCardTemplate).join('');

    } catch (err) {
        document.getElementById('rolesContainer').innerHTML =
            '<div class="col-12 text-center py-5 text-danger">Failed to load roles.</div>';
    }
};

document.addEventListener('DOMContentLoaded', loadRoles);
</script>
@endpush
