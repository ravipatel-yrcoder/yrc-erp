@extends('layouts.app')
@section('title', 'Activities')

@section('content')
<div class="container-fluid">

    <!-- Page Header -->
    <div class="mb-4">
        <h4 class="fw-bold mb-1">Activities</h4>
        <p class="text-muted mb-0 small">Track calls, emails, meetings and to-dos</p>
    </div>

    <!-- Filters Card -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">

                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-medium">Activity Type</label>
                    <select id="filter_activity_type" class="form-select form-select-sm">
                        <option value="">All Types</option>
                        <option value="call">Phone Call</option>
                        <option value="email">Email</option>
                        <option value="meeting">Meeting</option>
                        <option value="todo">To-Do</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-medium">Status</label>
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="pending">Pending</option>
                        <option value="in_progress">In Progress</option>
                        <option value="completed">Completed</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="skipped">Skipped</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-medium">Priority</label>
                    <select id="filter_priority" class="form-select form-select-sm">
                        <option value="">All Priorities</option>
                        <option value="low">Low</option>
                        <option value="medium">Medium</option>
                        <option value="high">High</option>
                        <option value="urgent">Urgent</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-medium">Entity Type</label>
                    <select id="filter_entity_type" class="form-select form-select-sm">
                        <option value="">All Entities</option>
                        <option value="lead">Lead</option>
                        <option value="customer">Customer</option>
                        <option value="sales_order">Sales Order</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-medium">Due Date</label>
                    <select id="filter_due_preset" class="form-select form-select-sm">
                        <option value="">Any Date</option>
                        <option value="overdue">Overdue</option>
                        <option value="today">Today</option>
                        <option value="this_week">This Week</option>
                        <option value="unscheduled">Unscheduled</option>
                        <option value="custom">Custom Range</option>
                    </select>
                </div>

                @if($showAssignedTo)
                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-medium">Assigned To</label>
                    <select id="filter_assigned_to" class="form-select form-select-sm">
                        <option value="">Anyone</option>
                        @foreach($assignedToOptions as $u)
                        <option value="{{ $u->id }}">{{ $u->name }}</option>
                        @endforeach
                    </select>
                </div>
                @endif

                <div class="col-md-2 d-flex align-items-end">
                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary" id="applyFiltersBtn">Apply</button>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="resetFiltersBtn">Reset</button>
                    </div>
                </div>

            </div>

            <!-- Custom date range row (hidden by default) -->
            <div class="row g-3 mt-1 d-none" id="customDateRangeRow">
                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-medium">From</label>
                    <input type="text" id="filter_date_from" class="form-control form-control-sm" placeholder="DD/MM/YYYY" autocomplete="off" />
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1 small fw-medium">To</label>
                    <input type="text" id="filter_date_to" class="form-control form-control-sm" placeholder="DD/MM/YYYY" autocomplete="off" />
                </div>
            </div>

        </div>
    </div>

    <!-- DataTable Card -->
    <div class="card">
        <div class="card-datatable table-responsive">
            <table id="activitiesTable" class="table table-bordered" style="width:100%">
                <thead>
                    <tr>
                        <th>Type</th>
                        <th>Summary</th>
                        <th>Entity</th>
                        <th>Status</th>
                        <th>Priority</th>
                        <th>Due Date</th>
                        <th>Assigned To</th>
                        <th>Created By</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>

</div>

@include('app.components.drawers.activities.add-edit')
@endsection


@push('scripts')
<script>
'use strict';

// ── Permissions ──────────────────────────────────────────────────────────────
const _canWrite        = canDo('activities', 'write');
const _canDelete       = canDo('activities', 'delete');
const _canMarkComplete = canDo('activities', 'mark_complete') || canDo('activities', 'write');

// ── Type / status / priority maps ────────────────────────────────────────────
const _typeIcons  = { call: 'bx-phone', email: 'bx-envelope', meeting: 'bx-group', todo: 'bx-check-square' };
const _typeLabels = { call: 'Call', email: 'Email', meeting: 'Meeting', todo: 'To-Do' };
const _statusBadge = {
    pending:     'bg-label-secondary',
    in_progress: 'bg-label-info',
    completed:   'bg-label-success',
    cancelled:   'bg-label-danger',
    skipped:     'bg-label-warning',
};
const _statusLabel = {
    pending: 'Pending', in_progress: 'In Progress', completed: 'Completed',
    cancelled: 'Cancelled', skipped: 'Skipped',
};
const _priorityBadge = {
    low: 'bg-label-secondary', medium: 'bg-label-primary',
    high: 'bg-label-warning',  urgent: 'bg-label-danger',
};
const _entityLabel = { lead: 'Lead', customer: 'Customer', sales_order: 'Sales Order' };

// ── DataTable ────────────────────────────────────────────────────────────────
let _activitiesTable = null;

function buildParams() {
    return {
        activity_type:   $('#filter_activity_type').val()  || '',
        status:          $('#filter_status').val()         || '',
        priority:        $('#filter_priority').val()       || '',
        entity_type:     $('#filter_entity_type').val()    || '',
        due_date_preset: $('#filter_due_preset').val()     || '',
        due_date_from:   $('#filter_date_from').val()      || '',
        due_date_to:     $('#filter_date_to').val()        || '',
        @if($showAssignedTo)
        assigned_to:     $('#filter_assigned_to').val()    || '',
        @endif
    };
}

jQuery(document).ready(function() {

    // ── Select2 filter controls ───────────────────────────────────────────────
    initSelect2('#filter_activity_type', {
        placeholder: 'All Types',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_status', {
        placeholder: 'All Statuses',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_priority', {
        placeholder: 'All Priorities',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_entity_type', {
        placeholder: 'All Entities',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
    });

    initSelect2('#filter_due_preset', {
        placeholder: 'Any Date',
        minimumResultsForSearch: Infinity,
        width: 'resolve',
        onChange: function(el) {
            const val = jQuery(el).val() || '';
            const show = val === 'custom';
            document.getElementById('customDateRangeRow').classList.toggle('d-none', !show);
            if (!show) {
                datePickerSetDate('#filter_date_from', '');
                datePickerSetDate('#filter_date_to', '');
            }
        },
    });

    @if($showAssignedTo)
    initSelect2('#filter_assigned_to', {
        placeholder: 'Anyone',
        minimumResultsForSearch: 5,
        width: 'resolve',
    });
    @endif

    // Date pickers for custom range
    initDatePicker('#filter_date_from');
    initDatePicker('#filter_date_to');

    // ── Pre-populate filters from URL params (dashboard links) ────────────────
    (function() {
        const _p = new URLSearchParams(window.location.search);
        const _due    = _p.get('due')    || '';
        const _status = _p.get('status') || '';
        if (_due)    { $('#filter_due_preset').val(_due).trigger('change'); }
        if (_status) { $('#filter_status').val(_status).trigger('change'); }
    })();

    // ── DataTable ─────────────────────────────────────────────────────────────
    _activitiesTable = initDataTable('#activitiesTable', {
        ajax: {
            url: '/api/activities',
            type: 'GET',
            data: function(d) {
                const f = buildParams();
                d.activity_type    = f.activity_type;
                d.status           = f.status;
                d.priority         = f.priority;
                d.entity_type      = f.entity_type;
                d.due_date_preset  = f.due_date_preset;
                d.due_date_from    = f.due_date_from;
                d.due_date_to      = f.due_date_to;
                @if($showAssignedTo)
                d.assigned_to      = f.assigned_to;
                @endif
                d.search = d.search && d.search.value ? d.search.value : '';
                return d;
            },
            dataSrc: function(json) {
                return mapApiToDataTable(json);
            },
        },
        columns: [
            {
                data: 'activity_type',
                render: function(val) {
                    const icon  = _typeIcons[val]  || 'bx-calendar';
                    const label = _typeLabels[val] || val;
                    return `<span class="d-flex align-items-center gap-1"><i class="bx ${icon} text-muted"></i> <span class="small">${label}</span></span>`;
                },
                orderable: false,
            },
            { data: 'summary', orderable: false },
            {
                data: 'entity_type',
                render: function(val, type, row) {
                    return `<span class="small text-muted">${_entityLabel[val] || val} #${row.entity_id}</span>`;
                },
                orderable: false,
            },
            {
                data: 'status',
                render: function(val) {
                    return `<span class="badge ${_statusBadge[val] || 'bg-label-secondary'}">${_statusLabel[val] || val}</span>`;
                },
                orderable: false,
            },
            {
                data: 'priority',
                render: function(val) {
                    return val ? `<span class="badge ${_priorityBadge[val] || 'bg-label-secondary'}">${val.charAt(0).toUpperCase() + val.slice(1)}</span>` : '—';
                },
                orderable: false,
            },
            {
                data: 'due_date',
                render: function(val, type, row) {
                    if (!val) return '—';
                    const d = formatMySqlDate(val, window.sysDefaultConfig.dateFormat);
                    const t = row.due_time ? ` <small class="text-muted">${row.due_time.substring(0,5)}</small>` : '';
                    return d + t;
                },
                orderable: false,
            },
            {
                data: 'assigned_user_name',
                render: function(val) { return val || '<span class="text-muted small">Unassigned</span>'; },
                orderable: false,
            },
            {
                data: 'created_by_name',
                render: function(val) { return val || '—'; },
                orderable: false,
            },
            {
                data: null,
                orderable: false,
                render: function(val, type, row) {
                    const editBtn = _canWrite
                        ? `<a href="javascript:void(0);" class="btn text-warning btn-icon act-edit-btn" title="Edit" data-id="${row.id}"><i class="icon-base bx bxs-edit"></i></a>`
                        : '';
                    let dropdownItems = '';
                    if (_canMarkComplete && row.status !== 'completed') {
                        dropdownItems += `<li><a href="javascript:void(0);" class="dropdown-item act-done-btn" data-id="${row.id}">Mark as Done</a></li>`;
                    }
                    if (_canDelete) {
                        dropdownItems += `<li><a href="javascript:void(0);" class="dropdown-item text-danger act-delete-btn" data-id="${row.id}">Delete</a></li>`;
                    }
                    const dotsBtn = dropdownItems
                        ? `<a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a><ul class="dropdown-menu dropdown-menu-end">${dropdownItems}</ul>`
                        : '';
                    return `<div class="d-inline-block">${editBtn}${dotsBtn}</div>`;
                },
            },
        ],
        pageLength: 25,
    });

    // ── Filter buttons ────────────────────────────────────────────────────────
    document.getElementById('applyFiltersBtn').addEventListener('click', function() {
        _activitiesTable.ajax.reload();
    });

    document.getElementById('resetFiltersBtn').addEventListener('click', function() {
        $('#filter_activity_type').val('').trigger('change');
        $('#filter_status').val('').trigger('change');
        $('#filter_priority').val('').trigger('change');
        $('#filter_entity_type').val('').trigger('change');
        $('#filter_due_preset').val('').trigger('change');
        @if($showAssignedTo)
        $('#filter_assigned_to').val('').trigger('change');
        @endif
        _activitiesTable.ajax.reload();
    });

    // ── Table action buttons (event delegation) ───────────────────────────────
    document.addEventListener('click', function(e) {

        const editBtn   = e.target.closest('.act-edit-btn');
        const doneBtn   = e.target.closest('.act-done-btn');
        const deleteBtn = e.target.closest('.act-delete-btn');

        if (editBtn) {
            openActivityFormDrawer(parseInt(editBtn.dataset.id));
            return;
        }

        if (doneBtn) {
            const id = parseInt(doneBtn.dataset.id);
            showConfirmation(
                'Mark this activity as done?',
                'question',
                { text: 'Mark as Done', class: 'btn-success', callback: async function(outcome) {
                    try {
                        await api.post(`/activities/${id}/status`, { status: 'completed', outcome: outcome || '' });
                        notyf.success('Activity marked as done.');
                        _activitiesTable.ajax.reload(null, false);
                    } catch (err) {
                        handleApiError(err);
                    }
                }},
                { text: 'Cancel' },
                { input: 'textarea', inputPlaceholder: 'Describe what happened...' }
            );
            return;
        }

        if (deleteBtn) {
            const id = parseInt(deleteBtn.dataset.id);
            showConfirmation(
                DELETE_CONFIRM_MESSAGE,
                'warning',
                { text: 'Delete', class: 'btn-danger', callback: async function() {
                    try {
                        await api.delete(`/activities/${id}`);
                        notyf.success('Activity deleted.');
                        _activitiesTable.ajax.reload(null, false);
                    } catch (err) {
                        handleApiError(err);
                    }
                }},
                { text: 'Cancel' }
            );
            return;
        }
    });

    // ── Refresh table when activity drawer saves ──────────────────────────────
    document.addEventListener('activityFormSaved', function() {
        _activitiesTable.ajax.reload(null, false);
    });

});
</script>
@endpush
