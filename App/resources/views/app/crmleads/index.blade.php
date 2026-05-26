@extends('layouts.app')
@section('title', 'Leads')

@section('content')

<!-- Content -->
<div class="container-fluid">

    <!-- Page Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">Leads</h4>
            <p class="text-muted mb-0 small">Manage your leads & prospects</p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <div class="dropdown">
                <button class="btn btn-outline-secondary btn-sm dropdown-toggle d-flex justify-content-center gap-1" type="button" data-bs-toggle="dropdown">
                    <i class="icon-base bx bx-export icon-sm"></i> Export
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="exportLeads('csv')"><i class="bx bx-file me-2"></i>CSV</a></li>
                    <li><a class="dropdown-item" href="javascript:void(0);" onclick="exportLeads('xlsx')"><i class="bx bx-spreadsheet me-2"></i>Excel</a></li>
                </ul>
            </div>
            @if(tenantContext()->canDo('crm_leads', 'write'))
            <button class="btn btn-primary btn-sm d-flex justify-content-center gap-1" type="button" onClick="openLeadFormDrawer();"><i class="icon-base bx bx-plus icon-sm"></i> New Lead</button>
            @endif
        </div>
    </div>

    <!-- Filter Bar -->
    <div class="card mb-4">
        <div class="card-body py-3">
            <!-- Row 1: Dropdowns -->
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Stage</label>
                    <select id="filter_stage" class="form-select form-select-sm" multiple></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Assigned To</label>
                    <select id="filter_assigned" class="form-select form-select-sm" multiple></select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Priority</label>
                    <select id="filter_priority" class="form-select form-select-sm">
                        @foreach(config('constants.crm.lead_priorities') as $p)
                            <option value="{{ $p['key'] }}">{{ $p['label'] }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Source</label>
                    <select id="filter_source" class="form-select form-select-sm">
                        @foreach(config('constants.crm.lead_sources') as $s)
                            <option value="{{ $s['key'] }}">{{ $s['label'] }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <!-- Row 2: Date ranges + amount range + button -->
            <div class="row g-3 align-items-end mt-1">
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Exp. Close Date</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" id="filter_close_date_from" class="form-control form-control-sm" placeholder="From">
                        </div>
                        <div class="col-6">
                            <input type="text" id="filter_close_date_to" class="form-control form-control-sm" placeholder="To">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Created At</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="text" id="filter_created_from" class="form-control form-control-sm" placeholder="From">
                        </div>
                        <div class="col-6">
                            <input type="text" id="filter_created_to" class="form-control form-control-sm" placeholder="To">
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1 small fw-medium">Lead Value</label>
                    <div class="row g-2">
                        <div class="col-6">
                            <input type="number" id="filter_lead_value_min" class="form-control form-control-sm" placeholder="Min" min="0" step="any">
                        </div>
                        <div class="col-6">
                            <input type="number" id="filter_lead_value_max" class="form-control form-control-sm" placeholder="Max" min="0" step="any">
                        </div>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end gap-2">
                    <button type="button" id="applyFilters" class="btn btn-sm btn-primary">Apply Filters</button>
                    <button type="button" id="resetLeadFilters" class="btn btn-sm btn-outline-secondary">Reset</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Leads Table -->
    <div class="card">
        <div class="card-datatable table-responsive">
            <table class="table table-bordered" id="crm_leads_table">
                <thead>
                    <tr>
                        <th>Lead #</th>
                        <th>Title</th>
                        <th>Contact</th>
                        <th>Stage</th>
                        <th>Priority</th>
                        <th>Lead Value</th>
                        <th>Source</th>
                        <th>Exp. Close Date</th>
                        <th>Assigned To</th>
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

@if(tenantContext()->canDo('crm_leads', 'write'))
@includeOnce('app.components.drawers.crm.leads.add-edit')
@endif

@endsection

@push('scripts')
<script>
let leadFilters = {
    stage_id: [], assigned_to: [], priority: [], source: [],
    close_date_from: '', close_date_to: '',
    created_from: '', created_to: '',
    lead_value_min: '', lead_value_max: ''
};

let leadsDt;

const initFilterControls = function() {

    initSelect2('#filter_priority', {
        placeholder: 'All Priorities',
        multiple: true,
        minimumResultsForSearch: Infinity,
    });

    initSelect2('#filter_source', {
        placeholder: 'All Sources',
        multiple: true,
        minimumResultsForSearch: Infinity,
    });
    
    initDatePicker('#filter_close_date_from');
    initDatePicker('#filter_close_date_to');
    initDatePicker('#filter_created_from');
    initDatePicker('#filter_created_to');
};

const loadLeadFilters = async function() {

    try {

        const res = await api.get('/crm/leads/form-context');
        const { stages = [], users = [] } = res.data.data;

        initSelect2('#filter_stage', {
            placeholder: 'All Stages',
            multiple: true,
            resetVal: false,
            data: buildSelect2Options(stages, { idKey: 'id', textKey: 'name' })
        });

        // Default: pre-select all active (non-won, non-lost) stages
        const defaultStageIds = stages
            .filter(s => !s.is_won && !s.is_lost)
            .map(s => String(s.id));
        $('#filter_stage').val(defaultStageIds).trigger('change');
        leadFilters.stage_id = defaultStageIds;

        initSelect2('#filter_assigned', {
            placeholder: 'Anyone',
            multiple: true,
            data: [
                { id: 'unassigned', text: 'Unassigned' },
                ...buildSelect2Options(users, { idKey: 'id', textKey: 'name' })
            ]
        });

        initFilterControls();

    } catch(e) {}
};

const priorityBadge = function(priority) {
    const p = window.crmLeadPriorities.find(x => x.key === priority) || { label: priority, color: 'secondary' };
    return `<span class="d-flex justify-content-center badge bg-label-${p.color}">${p.label}</span>`;
};

const stagePill = function(name, color) {
    if (!name) return '<span class="text-muted">—</span>';
    const bg = color || '#6c757d';
    const textColor = getContrastTextColor(bg) || "#ffffff";
    return `<span class="d-flex justify-content-center badge rounded-pill" style="background:${bg};color:${textColor};border:1px solid ${bg}">${name}</span>`;
};

const sourceLabel = function(source) {
    const s = window.crmLeadSources.find(x => x.key === source);
    return s ? s.label : (source || '—');
};

// ── DataTable ────────────────────────────────────────────────────
const leadsDtOptions = {
    order: [[10, 'desc']],
    ajax: {
        url: '/api/crm/leads',
        data: function(d) {
            d.stage_id = leadFilters.stage_id;
            d.assigned_to = leadFilters.assigned_to;
            d.priority = leadFilters.priority;
            d.source = leadFilters.source;
            d.close_date_from = leadFilters.close_date_from;
            d.close_date_to = leadFilters.close_date_to;
            d.created_from = leadFilters.created_from;
            d.created_to = leadFilters.created_to;
            d.lead_value_min = leadFilters.lead_value_min;
            d.lead_value_max = leadFilters.lead_value_max;
        },
        dataSrc: function(json) {
            return mapApiToDataTable(json);
        }
    },
    columns: [
        {
            // 0: Lead #
            data: 'lead_code',
            render: function(data, type, row) {
                return `<a href="/crm/leads/${row.id}/" class="fw-medium">${data}</a>`;
            }
        },
        {
            // 1: Title
            data: 'title',
            render: function(data) {
                if (!data) return '<span class="text-muted">—</span>';
                const short = data.length > 40 ? data.substring(0, 40) + '…' : data;
                return `<span title="${data.replace(/"/g, '&quot;')}">${short}</span>`;
            }
        },
        {
            // 2: Contact (merged: display_name + email + phone + company_name — all searched via virtualColumns)
            data: 'display_name',
            render: function(data, type, row) {
                let lines = `<div class="d-flex align-items-center gap-2 mb-1"><i class="bx bx-user"></i><span class="fw-medium">${data || '—'}</span></div>`;
                if (row.email) lines += `<div class="small text-muted d-flex align-items-center gap-2 mb-1"><i class="bx bx-envelope"></i>${row.email}</div>`;
                if (row.phone) lines += `<div class="small text-muted d-flex align-items-center gap-2 mb-1"><i class="bx bx-phone"></i>${row.phone}</div>`;
                if (row.company_name) lines += `<div class="small text-muted d-flex align-items-center gap-2"><i class="bx bx-building"></i>${row.company_name}</div>`;
                return `<div>${lines}</div>`;
            }
        },
        {
            // 3: Stage — not searchable (formatted/structured)
            data: 'stage_name',
            searchable: false,
            className: 'text-center',
            render: function(data, type, row) {
                return stagePill(data, row.stage_color);
            }
        },
        {
            // 4: Priority — not searchable (formatted/structured)
            data: 'priority',
            searchable: false,
            className: 'text-center',
            render: function(data) { return priorityBadge(data); }
        },
        {
            // 5: Lead Value — not searchable (numeric, currency-formatted)
            data: 'expected_revenue',
            searchable: false,
            render: function(data) {
                return data ? formatCurrency(data) : '<span class="text-muted">—</span>';
            }
        },
        {
            // 6: Source — not searchable (formatted label)
            data: 'source',
            searchable: false,
            render: function(data) {
                return data ? sourceLabel(data) : '<span class="text-muted">—</span>';
            }
        },
        {
            // 7: Expected Close Date — not searchable (date-formatted)
            data: 'expected_close_date',
            searchable: false,
            render: function(data) {
                return data ? formatMySqlDate(data, window.sysDefaultConfig.dateFormat) : '<span class="text-muted">—</span>';
            }
        },
        {
            // 8: Assigned To — not searchable (use filter instead)
            data: 'assigned_user_name',
            searchable: false,
            render: function(data) {

                if (data) return `<div class="d-flex justify-content-center">${data}</div>`;
                return `<span class="d-flex justify-content-center badge bg-label-secondary"><i class="bx bx-user me-1"></i>Unassigned</span>`;
            }
        },
        {
            // 9: Created By — not searchable
            data: 'created_by_name',
            orderable: false,
            searchable: false,
            render: function(data) {
                return data ? data : '<span class="text-muted">—</span>';
            }
        },
        {
            // 10: Created At — not searchable (date-formatted)
            data: 'created_at',
            searchable: false,
            render: function(data) {
                return data ? formatMySqlDate(data, window.sysDefaultConfig.dateTimeFormat) : '—';
            }
        },
        {
            // 11: Actions
            data: 'id',
            orderable: false,
            searchable: false,
            render: function(data) {
                let dropdown = '';
                if (canDo('crm_leads', 'write')) {
                    dropdown = `<div class="dropdown">
                        <a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a href="javascript:void(0);" onclick="openLeadFormDrawer(${data})" class="dropdown-item">Edit</a></li>
                        </ul>
                    </div>`;
                }
                return `<div class="d-flex align-items-center gap-1">
                    <a href="/crm/leads/${data}/" class="btn text-primary btn-icon" title="View lead"><i class="icon-base bx bx-show"></i></a>
                    ${dropdown}
                </div>`;
            }
        }
    ]
};

document.getElementById('applyFilters').addEventListener('click', function() {
    leadFilters.stage_id = $('#filter_stage').val() || [];
    leadFilters.assigned_to = $('#filter_assigned').val() || [];
    leadFilters.priority = $('#filter_priority').val() || [];
    leadFilters.source = $('#filter_source').val() || [];
    leadFilters.close_date_from = document.getElementById('filter_close_date_from').value;
    leadFilters.close_date_to = document.getElementById('filter_close_date_to').value;
    leadFilters.created_from = document.getElementById('filter_created_from').value;
    leadFilters.created_to = document.getElementById('filter_created_to').value;
    leadFilters.lead_value_min = document.getElementById('filter_lead_value_min').value;
    leadFilters.lead_value_max = document.getElementById('filter_lead_value_max').value;
    leadsDt.ajax.reload();
});

document.getElementById('resetLeadFilters').addEventListener('click', function() {
    $('#filter_stage').val([]).trigger('change');
    $('#filter_assigned').val([]).trigger('change');
    $('#filter_priority').val([]).trigger('change');
    $('#filter_source').val([]).trigger('change');
    datePickerSetDate('#filter_close_date_from', '');
    datePickerSetDate('#filter_close_date_to', '');
    datePickerSetDate('#filter_created_from', '');
    datePickerSetDate('#filter_created_to', '');
    document.getElementById('filter_lead_value_min').value = '';
    document.getElementById('filter_lead_value_max').value = '';
    leadFilters = { stage_id: [], assigned_to: [], priority: [], source: [], close_date_from: '', close_date_to: '', created_from: '', created_to: '', lead_value_min: '', lead_value_max: '' };
    leadsDt.ajax.reload();
});

// Init static filter controls immediately, then load dynamic filters.
// DataTable is initialized only after dynamic filters are ready so the
// first AJAX call already carries the default stage selection.

loadLeadFilters().then(function() {
    leadsDt = initDataTable("#crm_leads_table", leadsDtOptions);
});

const exportLeads = async function(format) {

    const params = new URLSearchParams();
    params.set('format', format);

    leadFilters.stage_id.forEach(v => params.append('stage_id[]', v));
    leadFilters.assigned_to.forEach(v => params.append('assigned_to[]', v));
    leadFilters.priority.forEach(v => params.append('priority[]', v));
    leadFilters.source.forEach(v => params.append('source[]', v));

    if (leadFilters.close_date_from) params.set('close_date_from', leadFilters.close_date_from);
    if (leadFilters.close_date_to)   params.set('close_date_to',   leadFilters.close_date_to);
    if (leadFilters.created_from)    params.set('created_from',    leadFilters.created_from);
    if (leadFilters.created_to)      params.set('created_to',      leadFilters.created_to);
    if (leadFilters.lead_value_min !== '') params.set('lead_value_min', leadFilters.lead_value_min);
    if (leadFilters.lead_value_max !== '') params.set('lead_value_max', leadFilters.lead_value_max);

    try {
        const response = await api.get('/crm/leads/export?' + params.toString(), { responseType: 'blob' });
        const ext = format === 'xlsx' ? 'xlsx' : 'csv';
        const filename = 'leads-' + new Date().toISOString().slice(0, 10) + '.' + ext;
        const url = URL.createObjectURL(response.data);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    } catch(e) {
        handleApiError(e);
    }
};
</script>
@endpush