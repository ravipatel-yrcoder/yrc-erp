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
                        <th>Full name</th>
                        <th>Email</th>
                        <th>Position</th>
                        <th>Office</th>
                        <th>Start date</th>
                        <th>Salary</th>
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
let users_dt = new DataTable(document.querySelector('#users_list'), {
    processing: true,
    ajax: {
        url: '/api/users',
        dataSrc: 'data'
    },
    layout: {
        topStart: {
            rowClass: 'row mx-3 my-0 justify-content-between',
            features: [{
                pageLength: {
                    menu: [7, 10, 25, 50, 100],
                    text: 'Show_MENU_entries'
                }
            }]
        },
        topEnd: {
            search: {
            placeholder: ''
            }
        },
        bottomStart: {
            rowClass: 'row mx-3 justify-content-between',
            features: ['info']
        },
        bottomEnd: {
            paging: {
            firstLast: false
            }
        }
    },
    language: {
        paginate: {
            next: '<i class="icon-base bx bx-chevron-right scaleX-n1-rtl icon-sm"></i>',
            previous: '<i class="icon-base bx bx-chevron-left scaleX-n1-rtl icon-sm"></i>'
        }
    }
});
</script>
@endpush