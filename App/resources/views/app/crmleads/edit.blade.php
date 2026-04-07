@extends('layouts.app')
@section('title', 'Lead Details')

@section('content')
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">

    <div id="actionButtons"></div>    

    <div class="row g-4">
        
        <div class="col-lg-6">
            
            {{-- Stage Pipeline Bar --}}
            <div class="card mb-4" id="leadStagePipeline" style="display:none;">
                <div class="card-body py-3">
                    <div id="stagePipelineBar" class="d-flex align-items-center flex-wrap gap-0"></div>
                </div>
            </div>

            {{-- Left: Lead Details --}}
            <div class="card" id="leadDetailsCard">
                <div class="card-body">

                    {{-- Header --}}
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h5 class="mb-1" id="leadDisplayName">—</h5>
                            <div class="d-flex gap-2 align-items-center flex-wrap mt-1" id="leadBadges"></div>
                        </div>
                        <span class="text-muted small fw-medium" id="leadCode"></span>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="mb-1 text-muted small text-uppercase">Company</h6>
                            <p class="mb-0" id="leadCompany">—</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-1 text-muted small text-uppercase">Job Title</h6>
                            <p class="mb-0" id="leadJobTitle">—</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-1 text-muted small text-uppercase">Email</h6>
                            <p class="mb-0" id="leadEmail">—</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-1 text-muted small text-uppercase">Phone</h6>
                            <p class="mb-0" id="leadPhone">—</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-1 text-muted small text-uppercase">Source</h6>
                            <p class="mb-0" id="leadSource">—</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-1 text-muted small text-uppercase">Priority</h6>
                            <p class="mb-0" id="leadPriority">—</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-1 text-muted small text-uppercase">Expected Revenue</h6>
                            <p class="mb-0" id="leadRevenue">—</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-1 text-muted small text-uppercase">Expected Close Date</h6>
                            <p class="mb-0" id="leadCloseDate">—</p>
                        </div>
                        <div class="col-md-6">
                            <h6 class="mb-1 text-muted small text-uppercase">Assigned To</h6>
                            <p class="mb-0" id="leadAssignedTo">—</p>
                        </div>
                    </div>

                    <hr>

                    <div>
                        <h6 class="mb-2 text-muted small text-uppercase">Notes</h6>
                        <p class="mb-0" id="leadNotes" style="white-space: pre-wrap;">—</p>
                    </div>

                </div>
            </div>
        </div>

        {{-- Activities Card --}}
        <div class="col-lg-3">
            <div class="card full-height-sticky-card h-100" id="activitiesCard">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">Activities</h5>
                    <button type="button" class="btn btn-sm btn-primary" id="scheduleActivityBtn">
                        <i class="icon-base bx bx-plus icon-sm me-1"></i> Schedule Activity
                    </button>
                </div>
                <div class="card-body p-0" id="activitiesList">
                    <div class="text-center text-muted py-4 px-3">No activities yet</div>
                </div>
            </div>
        </div>

        {{-- Right: Add Note + Timeline --}}
        <div class="col-lg-3">

            <div class="card full-height-sticky-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0 me-2">Timeline</h5>
                    <button type="button" class="btn btn-sm btn-primary" id="leadAddNoteBtn">
                        <i class="icon-base bx bx-plus icon-sm me-1"></i> Add Note
                    </button>
                </div>
                <div class="card-body pt-2">
                    <ul class="timeline timeline-outline mb-0" id="leadHistoryTimeline">
                        <li class="timeline-item timeline-item-transparent">
                            <div class="timeline-event text-muted">No history available</div>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

</div>
<!-- / Content -->

@include('app.components.drawers.crm.leads.add-edit')
@include('app.components.drawers.crm.leads.add-edit-note')
@include('app.components.drawers.activities.add-edit')

@endsection

@push('scripts')
<script>
const leadId = "{{ $lead->id }}";
let _leadData = null;

const leadStatusBadge = function(status) {
    const map = { active: ['Active', 'primary'], won: ['Won', 'success'], lost: ['Lost', 'danger'] };
    const s = map[status] || [status, 'secondary'];
    return `<span class="badge bg-label-${s[1]}">${s[0]}</span>`;
};

const leadPriorityBadge = function(priority) {
    const map = { high: ['High', 'danger'], medium: ['Medium', 'warning'], low: ['Low', 'secondary'] };
    const p = map[priority] || [priority, 'secondary'];
    return `<span class="badge bg-label-${p[1]}">${p[0]}</span>`;
};

const leadSourceLabel = function(source) {
    const map = {
        website: 'Website', referral: 'Referral', cold_call: 'Cold Call',
        email_campaign: 'Email Campaign', social_media: 'Social Media',
        trade_show: 'Trade Show', other: 'Other',
    };
    return map[source] || source || '—';
};


const renderStagePipeline = function(stages, currentStageId, leadStatus) {
    
    const bar = document.getElementById('stagePipelineBar');
    
    if (!bar) return;
    bar.innerHTML = '';

    const pipelineStages = stages.filter(s => !s.is_won && !s.is_lost);
    const wonStage = stages.find(s => s.is_won);
    const allStages = [...pipelineStages];
    if (wonStage) allStages.push(wonStage);

    const isClickable = leadStatus === 'active';

    allStages.forEach((stage, idx) => {
        const isActive = stage.id == currentStageId;
        const color = stage.color || '#6c757d';
        const pillStyle = isActive
            ? `background:${color};color:#fff;border:1px solid ${color};`
            : `background:${color}18;color:${color};border:1px solid ${color}40;`;
        const cursor = isClickable && !isActive ? 'pointer' : 'default';
        const wonIcon = stage.is_won ? '<i class="bx bx-check-circle me-1"></i>' : '';

        bar.insertAdjacentHTML('beforeend', `
            <div class="d-flex align-items-center">
                <button type="button" class="badge rounded-pill px-3 py-2 border-0 stage-pill-btn"
                    style="${pillStyle}font-size:0.8rem;cursor:${cursor};"
                    data-stage-id="${stage.id}"
                    ${!isClickable || isActive ? 'disabled' : ''}
                    title="${isClickable && !isActive ? 'Move to ' + stage.name : stage.name}">
                    ${wonIcon}${stage.name}
                </button>
                ${idx < allStages.length - 1 ? `<i class="bx bx-chevron-right text-muted mx-1" style="font-size:1rem;"></i>` : ''}
            </div>
        `);
    });

    document.getElementById('leadStagePipeline').style.display = '';
};


const renderActionButtons = function(leadData) {
    const status = leadData.status;
    let editBtn = '', wonBtn = '', lostBtn = '', reopenBtn = '';

    if (status === 'active') {
        editBtn   = `<button class="btn btn-warning btn-sm lead-action-btn" data-action="edit"><i class="icon-base bx bx-edit icon-sm me-1"></i>Edit</button>`;
        wonBtn    = `<button class="btn btn-success btn-sm lead-action-btn" data-action="won"><i class="icon-base bx bx-trophy icon-sm me-1"></i>Mark Won</button>`;
        lostBtn   = `<button class="btn btn-danger btn-sm lead-action-btn" data-action="lost"><i class="icon-base bx bx-x-circle icon-sm me-1"></i>Mark Lost</button>`;
    } else {
        reopenBtn = `<button class="btn btn-secondary btn-sm lead-action-btn" data-action="reopen"><i class="icon-base bx bx-refresh icon-sm me-1"></i>Reopen</button>`;
    }

    document.getElementById('actionButtons').innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                ${editBtn}${wonBtn}${lostBtn}${reopenBtn}
            </div>
        </div>
    `;
};


// ── Lead Details ─────────────────────────────────────────────────────────────

const renderLeadDetails = function(data) {
    
    _leadData = data;

    document.getElementById('leadDisplayName').textContent = data.display_name || '—';
    document.getElementById('leadCode').textContent = data.lead_code ? `#${data.lead_code}` : '';

    const badges = document.getElementById('leadBadges');
    badges.innerHTML = leadStatusBadge(data.status);
    if (data.status === 'lost' && data.lost_reason) {
        badges.insertAdjacentHTML('beforeend', `<small class="text-muted ms-1">— ${data.lost_reason}</small>`);
    }

    document.getElementById('leadCompany').textContent  = data.company_name || '—';
    document.getElementById('leadJobTitle').textContent = data.job_title || '—';
    document.getElementById('leadEmail').innerHTML = data.email ? `<a href="mailto:${data.email}">${data.email}</a>` : '—';
    document.getElementById('leadPhone').textContent = data.phone || '—';
    document.getElementById('leadSource').textContent = leadSourceLabel(data.source);
    document.getElementById('leadPriority').innerHTML = data.priority ? leadPriorityBadge(data.priority) : '—';
    document.getElementById('leadRevenue').textContent = data.expected_revenue ? formatCurrency(data.expected_revenue) : '—';
    document.getElementById('leadCloseDate').textContent = data.expected_close_date ? formatMySqlDate(data.expected_close_date) : '—';
    document.getElementById('leadAssignedTo').textContent = (data.assigned_user && data.assigned_user.name) ? data.assigned_user.name : '—';
    document.getElementById('leadNotes').textContent = data.notes || '—';

    renderStagePipeline(data.stages || [], data.stage_id, data.status);
    renderActionButtons(data);
};


const renderLeadHistoryItem = function(item) {
    
    const logType = item.log_type || '';
    const meta = item.meta || {};
    let pointColor = 'info';
    let metaHtml = '';

    if (logType === 'created') {

        pointColor = 'success';
        if (meta.stage) metaHtml = `<div class="small text-muted mt-1">Stage: <span class="text-primary">${meta.stage}</span></div>`;
    }
    else if (logType === 'stage_change') {

        pointColor = 'primary';
        if (meta.from_stage_name !== undefined || meta.to_stage_name !== undefined) {
            
            const from = meta.from_stage_name || 'None';
            const to = meta.to_stage_name || 'None';
            metaHtml = `<div class="small mt-1">
                <span class="text-muted">${from}</span>
                <span class="mx-1 text-primary fw-semibold">→</span>
                <span class="text-primary">${to}</span>
            </div>`;
        }
    }
    else if (logType === 'system') {
        pointColor = 'warning';
    }
    else if (logType === 'note') {
        pointColor = 'secondary';
    }

    const isNote = logType === 'note';
    const titleHtml = isNote
        ? `<div class="small text-muted fw-medium mb-1">Note by ${item.created_by_name || 'User'}</div>
           <div class="small">${item.title || ''}</div>`
        : `<div class="timeline-header mb-1">
               <h6 class="mb-0 small">${item.title || ''}</h6>
               <small class="text-body-secondary">${item.created_by_name || 'System'}</small>
           </div>`;

    return `
        <li class="timeline-item timeline-item-transparent border-dashed">
            <span class="timeline-point timeline-point-${pointColor}"></span>
            <div class="timeline-event">
                ${titleHtml}
                ${metaHtml}
                <div class="small text-muted mt-1">${item.created_at || ''}</div>
            </div>
        </li>
    `;
};

const renderLeadHistory = function(history = []) {
    
    const container = document.getElementById('leadHistoryTimeline');
    
    if (!container) return;
    container.innerHTML = '';

    if (!Array.isArray(history) || history.length === 0) {
        container.innerHTML = `<li class="timeline-item timeline-item-transparent"><div class="timeline-event text-muted">No history available</div></li>`;
        return;
    }

    history.forEach(item => container.insertAdjacentHTML('beforeend', renderLeadHistoryItem(item)));
};


const refreshLeadDetails = async function(id) {
    
    try {
        const res = await api.get(`/crm/leads/${id}`);
        renderLeadDetails(res.data.data);
    } catch (e) {
        notyf.error("Failed to load lead details");
    }
};

const refreshLeadHistory = async function(id) {
    
    try {
        const res = await api.get(`/crm/leads/${id}/history`);
        renderLeadHistory(res.data.data);
    } catch (e) {
        notyf.error("Failed to load lead history");
    }
};

const updateLeadStatus = async function(id, status, lostReason = '') {
    
    try {
        
        const payload = { status };
        if (lostReason) payload.lost_reason = lostReason;
        await api.post(`/crm/leads/${id}/status`, payload);
        const msgs = { won: 'Lead marked as Won!', lost: 'Lead marked as Lost.', active: 'Lead reopened.' };
        
        notyf.success(msgs[status] || 'Status updated');
        
        refreshLeadDetails(id);
        refreshLeadHistory(id);

    } catch (e) {
        handleApiError(e);
    }
};

const updateLeadStage = async function(id, stageId) {
    
    try {
        
        await api.post(`/crm/leads/${id}/stage`, { stage_id: stageId });
        notyf.success("Stage updated");
        
        refreshLeadDetails(id);
        refreshLeadHistory(id);

    } catch (e) {
        handleApiError(e);
    }
};


document.addEventListener('click', function(e) {
    
    const pill = e.target.closest('.stage-pill-btn');
    if (!pill || pill.disabled) return;
    
    const stageId = pill.dataset.stageId;
    if (!_leadData || stageId == _leadData.stage_id) return;
    
    updateLeadStage(leadId, stageId);
});


const leadActionHandlers = {
    edit: (id) => openLeadFormDrawer(parseInt(id)),
    won: (id) => {
        showConfirmation(
            'Mark this lead as Won? It will be moved to the Won stage.',
            'question',
            { text: 'Mark as Won', class: 'btn-success', callback: () => updateLeadStatus(id, 'won') },
            { text: 'Cancel' }
        );
    },
    lost: (id) => {
        showConfirmation(
            'Mark this lead as Lost? You can reopen it later.',
            'warning',
            { text: 'Mark as Lost', class: 'btn-danger', callback: (reason) => updateLeadStatus(id, 'lost', reason || '') },
            { text: 'Cancel' },
            { input: 'textarea', inputLabel: 'Reason (optional)', inputPlaceholder: 'e.g. Budget constraints, no response...' }
        );
    },
    reopen: (id) => {
        showConfirmation(
            'Reopen this lead and set it back to active?',
            'question',
            { text: 'Reopen', class: 'btn-primary', callback: () => updateLeadStatus(id, 'active') },
            { text: 'Cancel' }
        );
    },
};

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.lead-action-btn');
    if (!btn) return;
    const action = btn.dataset.action;
    if (leadActionHandlers[action]) leadActionHandlers[action](leadId);
});


document.getElementById('leadAddNoteBtn').addEventListener('click', () => openLeadNoteDrawer());

document.addEventListener('leadNoteAdded', function() {
    refreshLeadHistory(leadId);
});


document.addEventListener('leadFormSaved', function() {
    refreshLeadDetails(leadId);
    refreshLeadHistory(leadId);
});


const leadActivityTypeMap = {
    call: { label: 'Phone Call', icon: 'bx-phone', color: 'primary' },
    email: { label: 'Email', icon: 'bx-envelope', color: 'info' },
    meeting: { label: 'Meeting', icon: 'bx-calendar', color: 'warning' },
    todo: { label: 'To-Do', icon: 'bx-task', color: 'secondary' },
};

const renderLeadActivitiesList = function(activities) {
    
    const container = document.getElementById('activitiesList');
    if (!container) return;

    if (!activities || activities.length === 0) {
        container.innerHTML = `<div class="text-center text-muted py-4 px-3">No activities yet</div>`;
        return;
    }

    const pending   = activities.filter(a => !a.is_done);
    const completed = activities.filter(a => a.is_done);

    let html = '';

    if (completed.length > 0) {
        html += `
            <div class="px-4 py-2 border-top bg-label-primary">
                <button class="btn btn-link btn-sm p-0 fw-bold collapsed" type="button"
                    data-bs-toggle="collapse" data-bs-target="#completedActivities">
                    <i class="fs-4 bx bx-chevron-down me-1"></i>${completed.length} completed
                </button>
            </div>
            <div class="collapse" id="completedActivities">
                <div class="list-group list-group-flush">`;
        completed.forEach(a => {
            const t = leadActivityTypeMap[a.type] || { label: a.type, icon: 'bx-circle', color: 'secondary' };
            html += `
                <div class="list-group-item px-4 py-3 opacity-75">
                    <div class="d-flex align-items-start gap-3">
                        <span class="avatar avatar-xs rounded-circle bg-label-success flex-shrink-0 mt-1"
                            style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                            <i class="bx bx-check" style="font-size:1rem;"></i>
                        </span>
                        <div class="flex-grow-1">
                            <span class="text-decoration-line-through text-muted">${a.summary}</span>
                            <span class="badge bg-label-${t.color} ms-2 small">${t.label}</span>
                            ${a.outcome ? `<div class="small text-muted mt-1">Outcome: ${a.outcome}</div>` : ''}
                            <div class="small text-muted mt-1">${a.done_at || a.due_date}</div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += `</div></div>`;
    }

    if (pending.length > 0) {
        html += `<div class="list-group list-group-flush">`;
        pending.forEach(a => {
            const t = leadActivityTypeMap[a.type] || { label: a.type, icon: 'bx-circle', color: 'secondary' };
            const isOverdue = a.due_date && a.due_date < new Date().toISOString().slice(0, 10);
            html += `
                <div class="list-group-item px-4 py-3">
                    <div class="d-flex align-items-start gap-3">
                        <span class="avatar avatar-xs rounded-circle bg-label-${t.color} flex-shrink-0 mt-1"
                            style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;">
                            <i class="bx ${t.icon}" style="font-size:1rem;"></i>
                        </span>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="fw-medium">${a.summary}</span>
                                    <span class="badge bg-label-${t.color} ms-2 small">${t.label}</span>
                                </div>
                                <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                    <button type="button" class="btn btn-sm btn-outline-success px-2 activity-done-btn"
                                        title="Mark done" data-id="${a.id}">
                                        <i class="bx bx-check-circle fs-5"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning px-2 activity-edit-btn"
                                        title="Edit" data-id="${a.id}">
                                        <i class="bx bx-edit fs-5"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger px-2 activity-delete-btn"
                                        title="Delete" data-id="${a.id}">
                                        <i class="bx bx-trash fs-5"></i>
                                    </button>
                                </div>
                            </div>                            
                            ${a.note ? `<div class="small fw-semibold mt-1">Note:</div><div class="small text-muted">${a.note}</div>` : ''}
                            <div class="small text-muted mt-3">
                                <span class="${isOverdue ? 'text-danger fw-medium' : ''}">
                                    <span class="fw-semibold">Due Date:</span> ${a.due_date}${a.due_time ? ' ' + a.due_time : ''}
                                </span>
                                <br>
                                <span class="fw-semibold">Assigned To:</span> ${a.assigned_user_name ? `${a.assigned_user_name}` : '-'}
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        html += `</div>`;
    }    

    container.innerHTML = html;
};

const refreshActivities = async function(id) {
    try {
        const res = await api.get('/activities', { params: { related_type: 'lead', related_id: id } });
        renderLeadActivitiesList(res.data.data);
    } catch (e) {
        notyf.error("Failed to load activities");
    }
};

document.getElementById('scheduleActivityBtn').addEventListener('click', function() {
    openActivityFormDrawer(0, 'lead', leadId);
});

document.addEventListener('click', function(e) {

    const doneBtn = e.target.closest('.activity-done-btn');
    if (doneBtn) {
        const actId = doneBtn.dataset.id;
        showConfirmation(
            'Mark this activity as done?',
            'question',
            { text: 'Mark as Done', class: 'btn-success', callback: async (outcome) => {
                try {
                    await api.post(`/activities/${actId}/done`, { outcome: outcome || '' });
                    notyf.success('Activity marked as done');
                    refreshActivities(leadId);
                    refreshLeadHistory(leadId);
                } catch (err) { handleApiError(err); }
            }},
            { text: 'Cancel' },
            { input: 'textarea', inputLabel: 'Outcome (optional)', inputPlaceholder: 'Describe what happened...' }
        );
        return;
    }

    const editBtn = e.target.closest('.activity-edit-btn');
    if (editBtn) {
        openActivityFormDrawer(parseInt(editBtn.dataset.id), 'lead', leadId);
        return;
    }

    const delBtn = e.target.closest('.activity-delete-btn');
    if (delBtn) {
        const actId = delBtn.dataset.id;
        showConfirmation(
            'Delete this activity? This cannot be undone.',
            'warning',
            { text: 'Delete', class: 'btn-danger', callback: async () => {
                try {
                    await api.delete(`/activities/${actId}`);
                    notyf.success('Activity deleted');
                    refreshActivities(leadId);
                } catch (err) { handleApiError(err); }
            }},
            { text: 'Cancel' }
        );
    }
});

document.addEventListener('activityFormSaved', function() {
    refreshActivities(leadId);
});


document.addEventListener('DOMContentLoaded', () => {
    refreshLeadDetails(leadId);
    refreshLeadHistory(leadId);
    refreshActivities(leadId);
});
</script>
@endpush