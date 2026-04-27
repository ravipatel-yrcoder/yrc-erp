@extends('layouts.app')
@section('title', 'Users')

@section('content')
<!-- Content -->
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <h5 class="card-title mb-0">Users</h5>
            <button type="button" class="btn btn-primary btn-sm" onclick="openUserFormDrawer()">
                <i class="icon-base bx bx-plus icon-sm"></i> Add New
            </button>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="table table-bordered" id="users_list">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
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

@include('app.components.drawers.users.add-edit')
@endsection

@push('scripts')
<script>
const currentUserId = {{ auth()->user()->id }};
const usersDtOptions = {
    order: [[0, 'asc']],
    ajax: {
        url: '/api/users',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        { data: 'name' },
        { data: 'email' },
        { data: 'role_name', name: 'role_name', defaultContent: '—' },
        {
            data: 'status',
            render: function(data) {
                const badgeClass = data === 'active' ? 'bg-label-success' : 'bg-label-secondary';
                const label = data ? (data.charAt(0).toUpperCase() + data.slice(1)) : '—';
                return `<span class="badge ${badgeClass}">${label}</span>`;
            }
        },
        {
            data: 'created_at',
            render: function(data) {
                return formatMySqlDate(data, window.sysDefaultConfig.dateFormat);
            }
        },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                const isSelf = row.id === currentUserId;
                const isActive = row.status === 'active';
                const toggleBtn = isSelf ? '' :
                    `<button class="btn btn-sm btn-icon ${isActive ? 'btn-label-warning' : 'btn-label-success'}"
                             title="${isActive ? 'Deactivate' : 'Activate'}"
                             onclick="toggleUserStatus(${row.id}, '${row.status}')">
                         <i class="bx ${isActive ? 'bx-user-x' : 'bx-user-check'}"></i>
                     </button>`;
                return `<div class="d-flex gap-1">
                    <button class="btn btn-sm btn-icon btn-label-primary" title="Edit" onclick="openUserFormDrawer(${row.id})">
                        <i class="bx bx-edit"></i>
                    </button>
                    ${toggleBtn}
                </div>`;
            }
        },
    ]
};
const usersDt = initDataTable('#users_list', usersDtOptions);

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
</script>
@endpush
