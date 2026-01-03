@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">
    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center pb-0">
            <h5 class="card-title mb-0">Users</h5>
            <a href="/users/add" class="btn create-new btn-primary btn-sm"><i class="icon-base bx bx-plus icon-sm"></i>Add New</a>
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
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->
@endsection

@push('scripts')
<script>
const usersDtOptions = {
    ajax: {
        url: '/api/users',
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {'data': 'name'},
        {'data': 'email'},
        {'data': 'role'},
        {'data': 'status'},
        {'data': 'created_at'},
    ]
}
const userssDt = initDataTable("#users_list", usersDtOptions);
</script>
@endpush