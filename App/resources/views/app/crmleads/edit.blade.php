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

            {{-- Quotations & Sales Orders tabs card --}}
            <div class="card mb-4" id="leadDocumentsCard">
                <div class="card-header py-0 border-bottom">
                    <div class="d-flex align-items-stretch">
                        <ul class="nav nav-tabs flex-shrink-0 gap-4" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link lead-doc-tab px-0 lead-quotations-tab" data-bs-target="#leadQuotationsTab" type="button">
                                    Quotations <span class="badge bg-label-primary ms-1">0</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link lead-doc-tab px-0 lead-salesorders-tab" data-bs-target="#leadSalesOrdersTab" type="button">
                                    Sales Orders <span class="badge bg-label-primary ms-1">0</span>
                                </button>
                            </li>
                        </ul>
                        <button class="accordion-toggle flex-grow-1 px-0 border-0 bg-transparent text-end" type="button" aria-label="Toggle">
                            <i class="bx bx-chevron-down fs-4"></i>
                        </button>
                    </div>
                </div>

                <div id="leadDocuments" class="accordion-collapse collapse">
                    <div class="card-body">
                        <div class="tab-content px-0">

                            <!-- Quotations Tab -->
                            <div class="tab-pane fade" id="leadQuotationsTab">
                                <div class="table-responsive">
                                    <table class="table m-0" id="leadQuotationsTable">
                                        <thead>
                                            <tr>
                                                <th>SO#</th>
                                                <th>Date</th>
                                                <th>Customer</th>
                                                <th class="text-end">Total</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Sales Orders Tab -->
                            <div class="tab-pane fade" id="leadSalesOrdersTab">
                                <div class="table-responsive">
                                    <table class="table m-0" id="leadSalesOrdersTable">
                                        <thead>
                                            <tr>
                                                <th>SO#</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th class="text-end">Total</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>

                        </div>
                    </div>
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
                        <div class="col-md-4">
                            <h6 class="mb-1 text-muted small text-uppercase">Company</h6>
                            <p class="mb-0" id="leadCompany">—</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-1 text-muted small text-uppercase">Job Title</h6>
                            <p class="mb-0" id="leadJobTitle">—</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-1 text-muted small text-uppercase">Email</h6>
                            <p class="mb-0" id="leadEmail">—</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-1 text-muted small text-uppercase">Phone</h6>
                            <p class="mb-0" id="leadPhone">—</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-1 text-muted small text-uppercase">Website</h6>
                            <p class="mb-0" id="leadWebsite">—</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-1 text-muted small text-uppercase">Source</h6>
                            <p class="mb-0" id="leadSource">—</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-1 text-muted small text-uppercase">Priority</h6>
                            <p class="mb-0" id="leadPriority">—</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-1 text-muted small text-uppercase">Probability</h6>
                            <p class="mb-0" id="leadProbability">—</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-1 text-muted small text-uppercase">Assigned To</h6>
                            <p class="mb-0" id="leadAssignedTo">—</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-1 text-muted small text-uppercase">Expected Revenue</h6>
                            <p class="mb-0" id="leadRevenue">—</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-1 text-muted small text-uppercase">Expected Close Date</h6>
                            <p class="mb-0" id="leadCloseDate">—</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-2 text-muted small text-uppercase">Tags</h6>
                            <div id="leadTags" class="d-flex flex-wrap gap-1"></div>
                        </div>
                    </div>

                    {{-- Address (conditional) --}}
                    <div id="leadAddressRow" class="mb-3">
                        <h6 class="mb-1 text-muted small text-uppercase">Address</h6>
                        <p class="mb-0" id="leadAddress"></p>
                    </div>

                    <hr>

                    {{-- Notes --}}
                    <div class="mb-0">
                        <h6 class="mb-2 text-muted small text-uppercase">Notes</h6>
                        <p class="mb-0" id="leadNotes" style="white-space: pre-wrap;">—</p>
                    </div>

                    {{-- Lost Reason (conditional) --}}
                    <div id="leadLostReasonRow" style="display:none;">
                        <hr>
                        <h6 class="mb-1 text-muted small text-uppercase">Lost Reason</h6>
                        <p class="mb-0 text-danger" id="leadLostReason"></p>
                    </div>

                    {{-- Closed At (conditional) --}}
                    <div id="leadClosedAtRow" style="display:none;">
                        <hr>
                        <h6 class="mb-1 text-muted small text-uppercase">Closed At</h6>
                        <p class="mb-0" id="leadClosedAt"></p>
                    </div>

                    {{-- Converted Customer (conditional) --}}
                    <div id="leadCustomerRow" style="display:none;">
                        <hr>
                        <h6 class="mb-1 text-muted small text-uppercase">Converted To Customer</h6>
                        <p class="mb-0" id="leadCustomerLink"></p>
                    </div>

                </div>
            </div>
        </div>

        {{-- Activities Card --}}
        <div class="col-lg-3">
            <div class="card full-height-sticky-card h-100" id="activitiesCard">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0">Activities</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="scheduleActivityBtn">
                        <i class="icon-base bx bx-plus icon-sm me-1"></i> Schedule
                    </button>
                </div>
                <div class="card-body p-0">
                    <div class="nav-align-top">
                        <ul class="nav nav-tabs shadow" id="activitiesTabNav" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="activitiesTabPendingBtn"
                                    data-bs-toggle="tab" data-bs-target="#activitiesTabPending"
                                    type="button" role="tab">
                                    Pending <span class="badge bg-label-primary ms-1" id="activitiesPendingCount">0</span>
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="activitiesTabCompletedBtn"
                                    data-bs-toggle="tab" data-bs-target="#activitiesTabCompleted"
                                    type="button" role="tab">
                                    Completed <span class="badge bg-label-success ms-1" id="activitiesCompletedCount">0</span>
                                </button>
                            </li>
                        </ul>
                        <div class="tab-content px-0" id="activitiesList">
                            <div class="tab-pane fade show active" id="activitiesTabPending" role="tabpanel">
                                <div class="text-center text-muted py-4 px-3">No pending activities</div>
                            </div>
                            <div class="tab-pane fade" id="activitiesTabCompleted" role="tabpanel">
                                <div class="text-center text-muted py-4 px-3">No completed activities</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Right: Add Note + Timeline --}}
        <div class="col-lg-3">

            <div class="card full-height-sticky-card h-100">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="card-title m-0 me-2">Timeline</h5>
                    <button type="button" class="btn btn-sm btn-outline-primary" id="leadAddNoteBtn">
                        <i class="icon-base bx bx-plus icon-sm me-1"></i> Note
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
@include('app.components.drawers.crm.leads.link-customer')
@include('app.components.drawers.customers.add-edit')
@include('app.components.drawers.activities.add-edit')
@include('app.components.drawers.sales-orders.add-edit')

@endsection

@push('scripts')
<script>
const buildAttachmentList = function(attachments) {
    
    if (!attachments || !attachments.length) return '';
    
    const links = attachments.map(a => {
        const icon = a.is_image ? 'bx-image' : 'bx-file';
        const size = a.file_size > 1048576 ? (a.file_size / 1048576).toFixed(1) + ' MB' : Math.round(a.file_size / 1024) + ' KB';
        return `<a href="javascript:void(0);" onclick="downloadAttachment('${a.download_url}', '${a.original_name.replace(/'/g, "\\'")}')"
                   class="d-flex align-items-center gap-1 text-muted small text-decoration-none py-1"
                   title="${a.original_name}">
                    <i class="bx ${icon} fs-6 flex-shrink-0"></i>
                    <span class="text-truncate" style="max-width:180px;">${a.original_name}</span>
                    <span class="flex-shrink-0 ms-1 opacity-75">(${size})</span>
                </a>`;
    }).join('');

    return `<div class="border rounded px-2 py-1 mt-1 bg-light">${links}</div>`;
};

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
    const wonStage  = stages.find(s => s.is_won);
    const lostStage = stages.find(s => s.is_lost);
    const allStages = [...pipelineStages];

    if (leadStatus === 'lost') {
        if (lostStage) allStages.push(lostStage);
    } else {
        if (wonStage) allStages.push(wonStage);
    }

    const isClickable = leadStatus === 'active';

    allStages.forEach((stage, idx) => {
        const isActive = stage.id == currentStageId;
        const color = stage.color || '#6c757d';
        const textColor = getContrastTextColor(color) || "#ffffff";

        const pillStyle = isActive ? `background:${color} !important;border:1px solid ${color};color: ${textColor};` : `border:1px solid ${color} !important;background: transparent;color: var(--bs-heading-color)`;
        const cursor = isClickable && !isActive ? 'pointer' : 'default';
        const terminalIcon = stage.is_won ? '<i class="bx bx-check-circle me-1"></i>' : stage.is_lost ? '<i class="bx bx-x-circle me-1"></i>' : '';

        bar.insertAdjacentHTML('beforeend', `
            <div class="d-flex align-items-center">
                <button type="button" class="badge rounded-pill px-3 py-2 border-0 stage-pill-btn"
                    style="${pillStyle}font-size:0.8rem;cursor:${cursor};"
                    data-stage-id="${stage.id}" data-is-won="${stage.is_won ? '1' : '0'}" ${!isClickable || isActive ? 'disabled' : ''}
                    title="${isClickable && !isActive ? 'Move to ' + stage.name : stage.name}">${terminalIcon}${stage.name}
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
    let convertBtn = '', linkBtn = '', quotationBtn = '';

    if (status === 'active') {
        editBtn = `<button class="btn btn-warning btn-sm lead-action-btn" data-action="edit"><i class="icon-base bx bx-edit icon-sm me-1"></i>Edit</button>`;
        wonBtn = `<button class="btn btn-success btn-sm lead-action-btn" data-action="won"><i class="icon-base bx bx-trophy icon-sm me-1"></i>Won</button>`;
        lostBtn = `<button class="btn btn-danger btn-sm lead-action-btn" data-action="lost"><i class="icon-base bx bx-x-circle icon-sm me-1"></i>Lost</button>`;
    } else {
        reopenBtn = `<button class="btn btn-secondary btn-sm lead-action-btn" data-action="reopen"><i class="icon-base bx bx-refresh icon-sm me-1"></i>Reopen</button>`;
    }

    if ( !leadData.customer_id && status !== 'lost' ) {
        convertBtn = `<button class="btn btn-outline-success btn-sm lead-action-btn" data-action="convert"><i class="icon-base bx bx-transfer icon-sm me-1"></i>Convert to Customer</button>`;
        linkBtn = `<button class="btn btn-outline-secondary btn-sm lead-action-btn" data-action="link"><i class="icon-base bx bx-link icon-sm me-1"></i>Link Existing</button>`;
    }

    if (status !== 'lost') {
        quotationBtn = `<button class="btn btn-outline-primary btn-sm lead-action-btn" data-action="create_quotation"><i class="icon-base bx bx-file icon-sm me-1"></i>Create Quotation</button>`;
    }

    document.getElementById('actionButtons').innerHTML = `
    <div class="row g-4">
        <div class="col-lg-6">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    ${editBtn}${wonBtn}${lostBtn}${reopenBtn}
                </div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    ${quotationBtn}${convertBtn}${linkBtn}
                </div>
            </div>
        </div>
    </div>`;
};


const renderLeadDetails = function(data) {
    
    const detailsCard = document.getElementById('leadDetailsCard');

    detailsCard.querySelector('#leadDisplayName').innerHTML = data.display_name || '—';
    detailsCard.querySelector('#leadCode').innerHTML = data.lead_code ? `#${data.lead_code}` : '';

    detailsCard.querySelector('#leadBadges').innerHTML = leadStatusBadge(data.status);

    detailsCard.querySelector('#leadCompany').innerHTML = data.company_name || '—';
    detailsCard.querySelector('#leadJobTitle').innerHTML = data.job_title || '—';
    detailsCard.querySelector('#leadEmail').innerHTML = data.email ? `<a href="mailto:${data.email}">${data.email}</a>` : '—';
    detailsCard.querySelector('#leadPhone').innerHTML = data.phone || '—';
    detailsCard.querySelector('#leadWebsite').innerHTML = data.website ? `<a href="${data.website}" target="_blank" rel="noopener">${data.website}</a>` : '—';
    detailsCard.querySelector('#leadSource').innerHTML = leadSourceLabel(data.source);
    detailsCard.querySelector('#leadPriority').innerHTML = data.priority ? leadPriorityBadge(data.priority) : '—';
    detailsCard.querySelector('#leadProbability').innerHTML = data.probability != null ? `${data.probability}%` : '—';
    detailsCard.querySelector('#leadAssignedTo').innerHTML = data.assigned_user?.name || '—';
    detailsCard.querySelector('#leadRevenue').innerHTML = data.expected_revenue ? formatCurrency(data.expected_revenue) : '—';
    detailsCard.querySelector('#leadCloseDate').innerHTML = data.expected_close_date ? formatMySqlDate(data.expected_close_date) : '—';
    detailsCard.querySelector('#leadNotes').innerHTML = data.notes || '—';

    // Tags
    const tags = Array.isArray(data.tags) ? data.tags : [];
    let tagsStr = '—';
    if (tags.length) {
        tagsStr = tags.map(t => `<span class="badge rounded-pill bg-label-info">${t}</span>`).join('');        
    }
    detailsCard.querySelector('#leadTags').innerHTML = tagsStr;

    // Address
    const addressParts = [data.address_line1, data.address_line2, data.city, data.state, data.postal_code, data.country].filter(Boolean);
    let addStr = "—"
    if (addressParts.length) {
        addStr = addressParts.join(', ');
    }
    detailsCard.querySelector('#leadAddress').innerHTML = addStr;

    // Lost reason (conditional)
    const lostRow = detailsCard.querySelector('#leadLostReasonRow');
    if (data.status === 'lost' && data.lost_reason) {
        detailsCard.querySelector('#leadLostReason').textContent = data.lost_reason;
        lostRow.style.display = '';
    } else {
        lostRow.style.display = 'none';
    }

    // Closed at (conditional)
    const closedAtRow = detailsCard.querySelector('#leadClosedAtRow');
    if (data.closed_at) {
        const closedDate = formatMySqlDate(data.closed_at.split(' ')[0]);
        detailsCard.querySelector('#leadClosedAt').textContent = closedDate;
        closedAtRow.style.display = '';
    } else {
        closedAtRow.style.display = 'none';
    }

    // Converted customer (conditional)
    const customerRow = document.getElementById('leadCustomerRow');
    if (data.customer_id) {
        document.getElementById('leadCustomerLink').innerHTML = `<a href="/customers/${data.customer_id}">${data.customer_name || 'View Customer'}</a>`;
        customerRow.style.display = '';
    } else {
        customerRow.style.display = 'none';
    }

    renderStagePipeline(data.stages || [], data.stage_id, data.status);    
    renderActionButtons(data);
};


const formatLeadFieldChange = function(oldVal, newVal) {
    
    if (oldVal == '' && newVal == '') return '';
    let html = '';
    
    if (oldVal) {
        html += `<span class="text-muted">${oldVal}</span>`;
        if (newVal) html += `<span class="mx-1 text-primary fw-semibold">→</span>`;
    }
    
    if (newVal) html += `<span class="text-primary">${newVal}</span>`;
    
    return html;
};


const renderLeadHistoryItemMeta = function(logType, meta) {

    if (!meta || typeof meta !== 'object') return '';

    let html = '';

    if (logType === 'created') {
        if (meta.stage) {
            html = `<div class="small text-muted mt-1">Stage: <span class="text-primary">${meta.stage}</span></div>`;
        }
    }
    else if (logType === 'stage_change') {
        const fromStage = meta.from_stage_name || 'None';
        const toStage   = meta.to_stage_name   || 'None';
        html = `<div class="small mt-1">Stage: ${formatLeadFieldChange(fromStage, toStage)}</div>`;

        if (meta.from_status && meta.to_status) {
            html += `<div class="small mt-1">Status: ${formatLeadFieldChange(meta.from_status, meta.to_status)}</div>`;
        }
    }
    else if (logType === 'updated_details') {
        html = `<ul class="mt-2 mb-2 ps-3 small">`;
        (Array.isArray(meta) ? meta : []).forEach(item => {
            let oldVal = item.old_val;
            let newVal = item.new_val;

            // Format date fields
            if (item.field === 'expected_close_date') {
                if (oldVal) oldVal = formatMySqlDate(oldVal);
                if (newVal) newVal = formatMySqlDate(newVal);
            }

            // Format tag arrays as comma-separated strings
            if (item.field === 'tags') {
                oldVal = Array.isArray(oldVal) && oldVal.length ? oldVal.join(', ') : '';
                newVal = Array.isArray(newVal) && newVal.length ? newVal.join(', ') : '';
            }

            const formattedHtml = formatLeadFieldChange(oldVal || '', newVal || '');
            if (formattedHtml) {
                html += `<li>${item.label}: ${formattedHtml}</li>`;
            }
        });
        html += `</ul>`;
    }
    else if (logType === 'updated_notes') {
        if (meta.old_val || meta.new_val) {
            html = `<div class="small mt-2">`;
            if (meta.old_val) html += `<div class="text-muted mb-1"><em>${meta.old_val}</em></div>`;
            if (meta.old_val && meta.new_val) html += `<span class="mx-1 text-primary fw-semibold">→</span>`;
            if (meta.new_val) html += `<div class="text-primary mt-1"><em>${meta.new_val}</em></div>`;
            html += `</div>`;
        }
    }
    else if (logType === 'status_updated') {
        const fromStatus = meta.from_status || 'None';
        const toStatus   = meta.to_status   || 'None';
        html = `<div class="small mt-1">Status: ${formatLeadFieldChange(fromStatus, toStatus)}</div>`;

        if (meta.from_stage_name !== undefined || meta.to_stage_name !== undefined) {
            const fromStage = meta.from_stage_name || 'None';
            const toStage   = meta.to_stage_name   || 'None';
            if (fromStage !== toStage) {
                html += `<div class="small mt-1">Stage: ${formatLeadFieldChange(fromStage, toStage)}</div>`;
            }
        }

        if (meta.note) {
            html += `<div class="small mt-1 text-muted">Note: ${meta.note}</div>`;
        }
    }
    else if (logType === 'assigned_changed') {
        const from = meta.from_user_name || 'None';
        const to   = meta.to_user_name   || 'None';
        html = `<div class="small mt-1">${formatLeadFieldChange(from, to)}</div>`;
    }
    else if (logType === 'activity_done') {
        if (meta.outcome) {
            html = `<div class="small mt-1">Outcome: ${meta.outcome || ''}</div>`;
        }
    }
    /*
    else if (logType === 'converted_to_customer' || logType === 'linked_to_customer') {}
    else if (logType === 'quotation_created') {
        if (meta.so_number) {
            html = `<div class="small mt-1">
                <a href="/sales-orders/${meta.so_id}/" class="text-primary fw-medium">${meta.so_number}</a>
            </div>`;
        }
    }
    else if (logType === 'quotation_confirmed') {
        if (meta.so_number) {
            html = `<div class="small mt-1">
                <a href="/sales-orders/${meta.so_id}/" class="text-primary fw-medium">${meta.so_number}</a>
            </div>`;
        }
    }
    else if (logType === 'quotation_cancelled') {
        if (meta.so_number) {
            html = `<div class="small mt-1 text-danger">${meta.so_number}</div>`;
        }
    }
    */

    return html;
};


const renderLeadHistoryItem = function(item) {

    const logType = item.log_type || '';
    const meta = item.meta || {};

    let pointColor = 'info';    
    const titleHtml = `<div class="timeline-header mb-1">
        <h6 class="mb-0 small">${item.title || ''}</h6>
        <small class="text-body-secondary">${item.created_by_name || 'System'}</small>
    </div>`;

    const metaHtml = renderLeadHistoryItemMeta(logType, meta);

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

    const leadId  = "{{ $lead->id }}";
    const stageId = pill.dataset.stageId;

    if (pill.dataset.isWon === '1') {
        showConfirmation(
            'Move to Won stage? Status will be updated to Won',
            'question',
            { text: 'Mark as Won', class: 'btn-success', callback: () => updateLeadStage(leadId, stageId) },
            { text: 'Cancel' }
        );
        return;
    }

    updateLeadStage(leadId, stageId);
});


const leadActionHandlers = {
    
    edit: (id) => openLeadFormDrawer(parseInt(id)),
    won: (id) => {
        showConfirmation(
            'Mark this lead as Won? It will be moved to the Won stage',
            'question',
            { text: 'Mark as Won', class: 'btn-success', callback: () => updateLeadStatus(id, 'won') },
            { text: 'Cancel' }
        );
    },
    lost: (id) => {
        showConfirmation('Mark this lead as Lost? You can reopen it later.', 'warning', { text: 'Mark as Lost', class: 'btn-danger', callback: (reason) => updateLeadStatus(id, 'lost', reason || '') }, { text: 'Cancel' }, { input: 'textarea', inputPlaceholder: 'e.g. Budget constraints, no response...' });
    },
    reopen: (id) => {
        showConfirmation(
            'Reopen this lead and set it back to active?',
            'question',
            { text: 'Reopen', class: 'btn-primary', callback: () => updateLeadStatus(id, 'active') },
            { text: 'Cancel' }
        );
    },
    convert: (id) => openCustomerFormDrawer(0, { mode: 'convert_to_customer', leadId: id }),
    link: (id) => openLinkCustomerDrawer(id),
    create_quotation: (id) => openSalesOrderFormDrawer(0, {mode: 'lead_quotation', leadId: parseInt(id)}),
};

document.addEventListener('click', function(e) {
    
    const btn = e.target.closest('.lead-action-btn');
    if (!btn) return;

    const leadId = "{{ $lead->id }}";
    
    const action = btn.dataset.action;
    if (leadActionHandlers[action]) leadActionHandlers[action](leadId);
});


document.getElementById('leadAddNoteBtn').addEventListener('click', () => openLeadNoteDrawer("{{ $lead->id }}"));

document.addEventListener('leadNoteAdded', function(e) {

    const { lead_id = 0 } = e.detail || {};
    refreshLeadHistory(lead_id);
});

document.addEventListener('leadFormSaved', function(e) {
    
    console.log(e.detail);

    const { id = 0 } = e.detail || {};
    refreshLeadDetails(id);
    refreshLeadHistory(id);
});

document.addEventListener('leadConverted', function(e) {

    const { lead_id = 0 } = e.detail || {};

    refreshLeadDetails(lead_id);
    refreshLeadHistory(lead_id);
});

document.addEventListener('leadQuotationCreated', function(e) {

    const { lead_id = 0 } = e.detail || {};

    refreshLeadQuotations(lead_id);
    refreshLeadHistory(lead_id);
});


const leadActivityTypeMap = {
    call: { label: 'Phone Call', icon: 'bx-phone', color: 'primary' },
    email: { label: 'Email', icon: 'bx-envelope', color: 'info' },
    meeting: { label: 'Meeting', icon: 'bx-calendar', color: 'warning' },
    todo: { label: 'To-Do', icon: 'bx-task', color: 'secondary' },
};

const renderLeadActivitiesList = function(activities) {

    const pendingPane = document.getElementById('activitiesTabPending');
    const completedPane = document.getElementById('activitiesTabCompleted');
    const pendingBadge = document.getElementById('activitiesPendingCount');
    const completedBadge = document.getElementById('activitiesCompletedCount');

    if (!pendingPane || !completedPane) return;

    const pending   = activities ? activities.filter(a => !a.is_done) : [];
    const completed = activities ? activities.filter(a =>  a.is_done) : [];

    pendingBadge.textContent   = pending.length;
    completedBadge.textContent = completed.length;

    // --- Pending tab ---
    if (pending.length === 0) {
        pendingPane.innerHTML = `<div class="text-center text-muted py-4 px-3">No pending activities</div>`;
    } else {
        let html = `<div class="list-group list-group-flush">`;
        pending.forEach(a => {
            const t = leadActivityTypeMap[a.type] || { label: a.type, icon: 'bx-circle', color: 'secondary' };
            const isOverdue = a.due_date && a.due_date < new Date().toISOString().slice(0, 10);
            html += `
                <div class="list-group-item px-4 py-3">
                    <div class="d-flex align-items-start gap-3">
                        <span class="avatar avatar-xs rounded-circle bg-label-${t.color} flex-shrink-0 mt-1"
                            style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;" title="${t.label}">
                            <i class="bx ${t.icon}" style="font-size:1rem;"></i>
                        </span>
                        <div class="flex-grow-1 min-width-0">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <span class="fw-medium">${a.summary}</span>
                                </div>
                                <div class="d-flex gap-1 flex-shrink-0 ms-2">
                                    <button type="button" class="btn btn-sm btn-outline-success p-1 activity-done-btn"
                                        title="Mark done" data-id="${a.id}">
                                        <i class="bx bx-check-circle fs-6"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-warning p-1 activity-edit-btn"
                                        title="Edit" data-id="${a.id}">
                                        <i class="bx bx-edit fs-6"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger p-1 activity-delete-btn"
                                        title="Delete" data-id="${a.id}">
                                        <i class="bx bx-trash fs-6"></i>
                                    </button>
                                </div>
                            </div>                            
                            ${a.note ? `<div class="small fw-semibold mt-1">Note:</div><div class="small text-muted">${a.note}</div>` : ''}
                            ${buildAttachmentList(a.attachments || [])}
                            <div class="small text-muted mt-3">
                                <span class="${isOverdue ? 'text-danger fw-medium' : ''}">
                                    <span class="fw-semibold">Due Date:</span> ${a.due_date}${a.due_time ? ' ' + a.due_time : ''}
                                </span>
                                <br>
                                <span class="fw-semibold">Assigned To:</span> ${a.assigned_user_name ? `${a.assigned_user_name}` : '-'}
                            </div>
                        </div>
                    </div>
                </div>`;
        });
        html += `</div>`;
        pendingPane.innerHTML = html;
    }

    // --- Completed tab ---
    if (completed.length === 0) {
        completedPane.innerHTML = `<div class="text-center text-muted py-4 px-3">No completed activities</div>`;
    } else {
        let html = `<div class="list-group list-group-flush">`;
        completed.forEach(a => {
            const t = leadActivityTypeMap[a.type] || { label: a.type, icon: 'bx-circle', color: 'secondary' };
            html += `
                <div class="list-group-item px-4 py-3 opacity-75">
                    <div class="d-flex align-items-start gap-3">
                        <span class="avatar avatar-xs rounded-circle bg-label-${t.color} flex-shrink-0 mt-1"
                            style="width:32px;height:32px;display:flex;align-items:center;justify-content:center;" title="${t.label}">
                            <i class="bx ${t.icon}" style="font-size:1rem;"></i>
                        </span>
                        <div class="flex-grow-1">
                            <span class="text-decoration-line-through text-muted">${a.summary}</span>                            
                            ${a.outcome ? `<div class="small text-muted mt-1">Outcome: ${a.outcome}</div>` : ''}
                            ${buildAttachmentList(a.attachments || [])}
                            <div class="small text-muted mt-1">${a.done_at || a.due_date}</div>
                        </div>
                    </div>
                </div>`;
        });
        html += `</div>`;
        completedPane.innerHTML = html;
    }
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
    openActivityFormDrawer(0, 'lead', "{{ $lead->id }}");
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
                    refreshActivities("{{ $lead->id }}");
                    refreshLeadHistory("{{ $lead->id }}");
                } catch (err) { handleApiError(err); }
            }},
            { text: 'Cancel' },
            { input: 'textarea', inputPlaceholder: 'Describe what happened...' }
        );
        return;
    }

    const editBtn = e.target.closest('.activity-edit-btn');
    if (editBtn) {
        openActivityFormDrawer(parseInt(editBtn.dataset.id), 'lead', "{{ $lead->id }}");
        return;
    }

    const delBtn = e.target.closest('.activity-delete-btn');
    if (delBtn) {
        const actId = delBtn.dataset.id;
        showConfirmation(
            DELETE_CONFIRM_MESSAGE,
            'warning',
            { text: 'Delete', class: 'btn-danger', callback: async () => {
                try {
                    await api.delete(`/activities/${actId}`);
                    notyf.success('Activity deleted');
                    refreshActivities("{{ $lead->id }}");
                } catch (err) { handleApiError(err); }
            }},
            { text: 'Cancel' }
        );
    }
});

document.addEventListener('activityFormSaved', function() {
    refreshActivities("{{ $lead->id }}");
});


/* ===================================================
   QUOTATIONS & SALES ORDERS TABS
=================================================== */
const soStatusMap = {
    confirmed: ['Confirmed', 'primary'],
    cancelled: ['Cancelled', 'danger'],
    partially_dispatched: ['Partially Dispatched', 'info'],
    dispatched: ['Dispatched', 'info'],
    partially_delivered: ['Partially Delivered', 'success'],
    delivered: ['Delivered', 'success'],
};

const refreshLeadQuotations = async function(leadId) {

    const tbody = document.querySelector('#leadQuotationsTable tbody');
    const badge = document.querySelector('#leadDocumentsCard .lead-quotations-tab .badge');

    try {
        const res = await api.get('/quotations', { params: { lead_id: leadId } });
        const data = res.data.data || [];

        tbody.innerHTML = '';
        badge.innerHTML = '0';

        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">No quotations yet</td></tr>`;
            return;
        }

        badge.innerHTML = data.length;

        let html = '';
        data.forEach(row => {
            html += `<tr>
                <td><a href="/sales-orders/${row.id}/" class="text-primary fw-medium">${row.so_number}</a></td>
                <td>${row.order_date || '-'}</td>
                <td>${row.customer || '-'}</td>
                <td class="text-end">${formatCurrency(row.total_amount)}</td>
                <td class="text-end">
                    <a href="/sales-orders/${row.id}/" class="text-primary"><i class="icon-base bx bx-show"></i></a>
                </td>
            </tr>`;
        });

        tbody.innerHTML = html;

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">Failed to load quotations</td></tr>`;
    }
};


const refreshLeadSalesOrders = async function(leadId) {

    const tbody = document.querySelector('#leadSalesOrdersTable tbody');
    const badge = document.querySelector('#leadDocumentsCard .lead-salesorders-tab .badge');

    try {
        const res = await api.get('/sales-orders', { params: { lead_id: leadId, exclude_quotations: 1 } });
        const data = res.data.data || [];

        tbody.innerHTML = '';
        badge.innerHTML = '0';

        if (!data.length) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">No sales orders yet</td></tr>`;
            return;
        }

        badge.innerHTML = data.length;

        let html = '';
        data.forEach(row => {
            const s = soStatusMap[row.status] || [row.status, 'secondary'];
            html += `<tr>
                <td><a href="/sales-orders/${row.id}/" class="text-primary fw-medium">${row.so_number}</a></td>
                <td>${row.order_date || '-'}</td>
                <td><span class="badge bg-label-${s[1]}">${s[0]}</span></td>
                <td class="text-end">${formatCurrency(row.total_amount)}</td>
                <td class="text-end">
                    <a href="/sales-orders/${row.id}/" class="text-primary"><i class="icon-base bx bx-show"></i></a>
                </td>
            </tr>`;
        });

        tbody.innerHTML = html;

    } catch (e) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">Failed to load sales orders</td></tr>`;
    }
};


const initLeadDocumentsTabs = function() {

    const cardEl = document.getElementById('leadDocumentsCard');
    const collapseEl = document.getElementById('leadDocuments');
    if (!cardEl || !collapseEl) return;

    const collapse = new bootstrap.Collapse(collapseEl, { toggle: false });
    const tabs = cardEl.querySelectorAll('.lead-doc-tab');
    const panes = cardEl.querySelectorAll('.tab-pane');
    let defaultTab = tabs[0];

    const deactivateAll = () => {
        tabs.forEach(t => t.classList.remove('active'));
        panes.forEach(p => p.classList.remove('show', 'active'));
    };

    const activateTab = (tab) => {
        deactivateAll();
        tab.classList.add('active');
        cardEl.querySelector(tab.dataset.bsTarget).classList.add('show', 'active');
    };

    tabs.forEach(tab => {
        tab.addEventListener('click', function() {
            defaultTab = this;
            activateTab(this);
            if (!collapseEl.classList.contains('show')) collapse.show();
        });
    });

    collapseEl.addEventListener('shown.bs.collapse', () => activateTab(defaultTab));
    collapseEl.addEventListener('hidden.bs.collapse', () => {
        defaultTab = tabs[0];
        deactivateAll();
    });

    cardEl.querySelector('.accordion-toggle').addEventListener('click', function() {
        collapse.toggle();
        this.querySelector('i').classList.toggle('bx-chevron-up');
        this.querySelector('i').classList.toggle('bx-chevron-down');
    });
};


document.addEventListener('DOMContentLoaded', () => {
    refreshLeadDetails("{{ $lead->id }}");
    refreshLeadHistory("{{ $lead->id }}");
    refreshActivities("{{ $lead->id }}");
    refreshLeadQuotations("{{ $lead->id }}");
    refreshLeadSalesOrders("{{ $lead->id }}");
    initLeadDocumentsTabs();
});
</script>
@endpush