@extends('layouts.app')
@section('title', 'Lead Details')

@section('content')
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">

    <div id="actionButtons"></div>

    {{-- Stage Pipeline Bar --}}
    <div class="card mb-4" id="leadStagePipeline" style="display:none;">
        <div class="card-body py-3">
            <div id="stagePipelineBar" class="d-flex align-items-center flex-wrap gap-0"></div>
        </div>
    </div>

    <div class="row g-4">

        {{-- Left: Lead Details --}}
        <div class="col-lg-8">
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

        {{-- Right: Add Note + Timeline --}}
        <div class="col-lg-4">

            <div class="card mb-4">
                <div class="card-body py-3">
                    <h6 class="card-title mb-3">Add Note</h6>
                    <textarea id="noteText" class="form-control form-control-sm mb-2" rows="3" placeholder="Write a note..."></textarea>
                    <button type="button" id="saveNoteBtn" class="btn btn-primary btn-sm">Add Note</button>
                </div>
            </div>

            <div class="card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title m-0 me-2">Timeline</h5>
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

@endsection

@push('scripts')
<script>
const leadId = "{{ $lead->id }}";
let _leadData = null;

const leadStatusBadge = (status) => {
    const map = { active: ['Active', 'primary'], won: ['Won', 'success'], lost: ['Lost', 'danger'] };
    const s = map[status] || [status, 'secondary'];
    return `<span class="badge bg-label-${s[1]}">${s[0]}</span>`;
};

const leadPriorityBadge = (priority) => {
    const map = { high: ['High', 'danger'], medium: ['Medium', 'warning'], low: ['Low', 'secondary'] };
    const p = map[priority] || [priority, 'secondary'];
    return `<span class="badge bg-label-${p[1]}">${p[0]}</span>`;
};

const leadSourceLabel = (source) => {
    const map = {
        website: 'Website', referral: 'Referral', cold_call: 'Cold Call',
        email_campaign: 'Email Campaign', social_media: 'Social Media',
        trade_show: 'Trade Show', other: 'Other',
    };
    return map[source] || source || '—';
};


const renderStagePipeline = (stages, currentStageId, leadStatus) => {
    
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


const renderActionButtons = (leadData) => {
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

const renderLeadDetails = (data) => {
    
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


const renderLeadHistoryItem = (item) => {
    
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

const renderLeadHistory = (history = []) => {
    
    const container = document.getElementById('leadHistoryTimeline');
    
    if (!container) return;
    container.innerHTML = '';

    if (!Array.isArray(history) || history.length === 0) {
        container.innerHTML = `<li class="timeline-item timeline-item-transparent"><div class="timeline-event text-muted">No history available</div></li>`;
        return;
    }

    history.forEach(item => container.insertAdjacentHTML('beforeend', renderLeadHistoryItem(item)));
};


const refreshLeadDetails = async (id) => {
    
    try {
        const res = await api.get(`/crm/leads/${id}`);
        renderLeadDetails(res.data.data);
    } catch (e) {
        notyf.error("Failed to load lead details");
    }
};

const refreshLeadHistory = async (id) => {
    
    try {
        const res = await api.get(`/crm/leads/${id}/history`);
        renderLeadHistory(res.data.data);
    } catch (e) {
        notyf.error("Failed to load lead history");
    }
};

const updateLeadStatus = async (id, status, lostReason = '') => {
    
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

const updateLeadStage = async (id, stageId) => {
    
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
        Swal.fire({
            title: 'Mark as Lost',
            html: `<p class="text-muted mb-3">Optionally provide a reason for losing this lead.</p>
                   <textarea id="swal_lost_reason" class="form-control" rows="3" placeholder="Reason (optional)"></textarea>`,
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Mark as Lost',
            confirmButtonColor: '#dc3545',
            cancelButtonText: 'Cancel',
            preConfirm: () => document.getElementById('swal_lost_reason').value.trim(),
        }).then(result => {
            if (result.isConfirmed) updateLeadStatus(id, 'lost', result.value || '');
        });
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


document.getElementById('saveNoteBtn').addEventListener('click', async function() {
    
    const note = document.getElementById('noteText').value.trim();
    if (!note) { notyf.error("Please enter a note"); return; }
    
    try {
        
        await api.post(`/crm/leads/${leadId}/note`, { note });
        notyf.success("Note added");
        document.getElementById('noteText').value = '';
        
        refreshLeadHistory(leadId);

    } catch (e) {
        handleApiError(e);
    }
});


document.addEventListener('leadFormSaved', function() {
    
    refreshLeadDetails(leadId);
    refreshLeadHistory(leadId);
});


document.addEventListener('DOMContentLoaded', () => {
    
    refreshLeadDetails(leadId);
    refreshLeadHistory(leadId);
});
</script>
@endpush