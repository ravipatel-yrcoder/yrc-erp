@extends('layouts.app')
@section('title', 'Teams')

@section('content')
<!-- Content -->
<div class="container-fluid">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <h5 class="card-title mb-0">Teams</h5>
            <button type="button" class="btn btn-primary btn-sm" onclick="openTeamFormDrawer()">
                <i class="icon-base bx bx-plus icon-sm"></i> Add Team
            </button>
        </div>
        <div class="card-datatable text-nowrap">
            <table class="table table-bordered" id="teams_list">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Description</th>
                        <th>Members</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@includeOnce('app.components.drawers.teams.add-edit')
@endsection

@push('scripts')
<script>
const teamsDtOptions = {
    order: [[0, 'asc']],
    serverSide: false,
    ajax: {
        url: '/api/company/teams',
        dataSrc: function(json) {
            return json.data || [];
        }
    },
    columns: [
        { data: 'name' },
        { data: 'description', defaultContent: '—' },
        { data: 'member_count', defaultContent: '0' },
        {
            data: 'status',
            render: function(data) {
                const cls = data === 'active' ? 'bg-label-success' : 'bg-label-secondary';
                return `<span class="badge ${cls}">${data ? (data.charAt(0).toUpperCase() + data.slice(1)) : '—'}</span>`;
            }
        },
        {
            data: null,
            orderable: false,
            searchable: false,
            render: function(data, type, row) {
                return `<div class="d-flex gap-1">
                    <button class="btn btn-sm btn-icon btn-label-primary" title="Edit" onclick="openTeamFormDrawer(${row.id})">
                        <i class="bx bx-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-icon btn-label-info" title="Members" onclick="openTeamMembersDrawer(${row.id}, '${row.name}')">
                        <i class="bx bx-group"></i>
                    </button>
                    <button class="btn btn-sm btn-icon btn-label-danger" title="Delete" onclick="deleteTeam(${row.id})">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>`;
            }
        },
    ]
};
const teamsDt = initDataTable('#teams_list', teamsDtOptions);

function deleteTeam(id) {
    showConfirmation(DELETE_CONFIRM_MESSAGE, 'warning',
        {
            text: 'Delete',
            callback: async function() {
                try {
                    await api.delete(`/company/teams/${id}`);
                    notyf.success('Team deleted successfully.');
                    teamsDt.ajax.reload();
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
