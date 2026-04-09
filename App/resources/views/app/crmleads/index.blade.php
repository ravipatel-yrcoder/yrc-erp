@extends('layouts.app')
@section('title', 'CRM - Leads')

@section('content')

<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Leads</h4>
            <p class="text-muted mb-0 small">Manage your sales pipeline and track prospects.</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="btn-group btn-group-sm" role="group">
                <a href="/crm/leads" class="btn btn-secondary active" title="List view">
                    <i class="bx bx-list-ul"></i>
                </a>
                <a href="/crm/pipeline" class="btn btn-outline-secondary" title="Pipeline view">
                    <i class="bx bx-columns"></i>
                </a>
            </div>
            <button class="btn btn-primary btn-sm" type="button" onClick="openLeadFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i> New Lead</button>
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Status</label>
                    <select id="filter_status" class="form-select form-select-sm">
                        <option value="">All Statuses</option>
                        <option value="active" selected>Active</option>
                        <option value="won">Won</option>
                        <option value="lost">Lost</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Stage</label>
                    <select id="filter_stage" class="form-select form-select-sm">
                        <option value="">All Stages</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Assigned To</label>
                    <select id="filter_assigned" class="form-select form-select-sm">
                        <option value="">Anyone</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <button type="button" id="applyFilters" class="btn btn-sm btn-secondary w-100">Apply Filters</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Leads Table -->
    <div class="card">
        <div class="card-datatable text-nowrap">
            <table class="table table-bordered" id="crm_leads_table">
                <thead>
                    <tr>
                        <th>Lead #</th>
                        <th>Contact</th>
                        <th>Company</th>
                        <th>Email / Phone</th>
                        <th>Stage</th>
                        <th>Priority</th>
                        <th>Expected Revenue</th>
                        <th>Assigned To</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
<!-- / Content -->

@include('app.components.drawers.crm.leads.add-edit')

@endsection

@push('scripts')
<script>
let leadFilters = { status: 'active', stage_id: '', assigned_to: '' };

const loadLeadFilters = async function() {
    
    try {

        const res = await api.get('/crm/leads/form-context');
        const { stages = [], users = [] } = res.data.data;

        const stageEl = document.getElementById('filter_stage');
        stages.forEach(s => {
            const opt = document.createElement('option');
            opt.value = s.id;
            opt.textContent = s.name;
            stageEl.appendChild(opt);
        });

        const userEl = document.getElementById('filter_assigned');
        users.forEach(u => {
            const opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.name;
            userEl.appendChild(opt);
        });

    } catch(e) {}
}

const priorityBadge = function(priority) {
    
    const map = {
        high: ['High', 'danger'],
        medium: ['Medium', 'warning'],
        low: ['Low', 'secondary'],
    };
    
    const p = map[priority] || [priority, 'secondary'];
    
    return `<span class="badge bg-label-${p[1]}">${p[0]}</span>`;
}

const statusBadge = function(status) {
    const map = {
        active: ['Active', 'primary'],
        won:    ['Won',    'success'],
        lost:   ['Lost',   'danger'],
    };
    const s = map[status] || [status, 'secondary'];
    return `<span class="badge bg-label-${s[1]}">${s[0]}</span>`;
}

const stagePill = function(name, color) {
    if( !name ) return '<span class="text-muted">—</span>';
    const bg = color || '#6c757d';
    return `<span class="badge rounded-pill" style="background:${bg}20;color:${bg};border:1px solid ${bg}40">${name}</span>`;
}

// ── DataTable ────────────────────────────────────────────────────
const leadsDtOptions = {
    
    ajax: {
        url: '/api/crm/leads',
        data: function(d) {
            d.status = leadFilters.status;
            d.stage_id = leadFilters.stage_id;
            d.assigned_to = leadFilters.assigned_to;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            'data': 'lead_code',
            'render': function(data, type, row) {
                return `<a href="/crm/leads/${row.id}/" class="fw-medium">${data}</a>
                        <br><small class="text-muted">${statusBadge(row.status)}</small>`;
            }
        },
        {
            'data': 'display_name',
            'render': function(data, type, row) {
                const initials = (data || '?').substring(0, 2).toUpperCase();
                return `<div class="d-flex align-items-center gap-2">
                    <span class="avatar avatar-xs rounded-circle bg-label-primary flex-shrink-0"
                          style="width:28px;height:28px;font-size:11px;display:flex;align-items:center;justify-content:center;">
                        ${initials}
                    </span>
                    <span>${data}</span>
                </div>`;
            }
        },
        {
            'data': 'company_name',
            'defaultContent': '<span class="text-muted">—</span>'
        },
        {
            'data': 'email',
            'render': function(data, type, row) {
                const email = data ? `<div><i class="bx bx-envelope text-muted me-1"></i>${data}</div>` : '';
                const phone = row.phone ? `<div><i class="bx bx-phone text-muted me-1"></i>${row.phone}</div>` : '';
                return (email || phone) ? (email + phone) : '<span class="text-muted">—</span>';
            }
        },
        {
            'data': 'stage_name',
            'render': function(data, type, row) {
                return stagePill(data, row.stage_color);
            }
        },
        {
            'data': 'priority',
            'render': function(data) { return priorityBadge(data); }
        },
        {
            'data': 'expected_revenue',
            'render': function(data) {
                return data ? formatCurrency(data) : '<span class="text-muted">—</span>';
            }
        },
        {
            'data': 'assigned_user_name',
            'defaultContent': '<span class="text-muted">Unassigned</span>'
        },
        { 'data': 'created_at' },
        {
            'data': 'id',
            'orderable': false,
            'searchable': false,
            'render': function(data, type, row) {
                return (
                    `<div class="d-inline-block">
                        <a href="/crm/leads/${data}/" class="btn text-primary btn-icon" title="View lead"><i class="icon-base bx bx-show"></i></a>
                        <a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a href="/crm/leads/${data}/" class="dropdown-item">View Details</a></li>
                            <li><a href="javascript:void(0);" onclick="openLeadFormDrawer(${data})" class="dropdown-item">Edit</a></li>
                        </ul>
                    </div>`
                );
            }
        }
    ]
};

const leadsDt = initDataTable("#crm_leads_table", leadsDtOptions);

document.getElementById('applyFilters').addEventListener('click', function() {
    
    leadFilters.status = document.getElementById('filter_status').value;
    leadFilters.stage_id = document.getElementById('filter_stage').value;
    leadFilters.assigned_to = document.getElementById('filter_assigned').value;
    
    leadsDt.ajax.reload();
});

loadLeadFilters();
</script>
@endpush