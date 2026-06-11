@extends('layouts.app')
@section('title', 'Manufacturing Order')

@section('content')

<?php $tenantContext = tenantContext(); ?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0" id="moPageHeading">Manufacturing Order</h4>
    </div>

    <div id="moActionButtons"></div>

    <div class="row g-4">

        <div class="col-lg-8">

            {{-- Documents Card --}}
            <div class="card mb-4 d-none" id="moDocumentsCard">
                <div class="card-header py-0">
                    <div class="d-flex align-items-stretch">
                        <ul class="nav nav-tabs flex-shrink-0 gap-4" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link doc-tab px-0" data-bs-target="#moAllocationsTab" type="button">
                                    Allocations <span class="badge bg-label-primary ms-1" id="moAllocationsCount">0</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link doc-tab px-0" data-bs-target="#moOutputsTab" type="button">
                                    Production <span class="badge bg-label-primary ms-1" id="moOutputsCount">0</span>
                                </button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link doc-tab px-0" data-bs-target="#moReturnsTab" type="button">
                                    Returns <span class="badge bg-label-primary ms-1" id="moReturnsCount">0</span>
                                </button>
                            </li>
                        </ul>
                        <button class="accordion-toggle flex-grow-1 px-0 border-0 bg-transparent text-end" type="button" aria-label="Toggle">
                            <i class="bx bx-chevron-down fs-4"></i>
                        </button>
                    </div>
                </div>
                <div id="moDocumentsBody" class="accordion-collapse collapse">
                    <div class="card-body">
                        <div class="tab-content px-0">

                            {{-- Allocations Tab --}}
                            <div class="tab-pane fade" id="moAllocationsTab">
                                <div class="table-responsive">
                                    <table class="table m-0" id="moAllocationsTable">
                                        <thead>
                                            <tr>
                                                <th style="width:32px;"></th>
                                                <th style="width:52px;">#</th>
                                                <th>DATE &amp; TIME</th>
                                                <th>ALLOCATED BY</th>
                                                <th>STATUS</th>
                                                <th style="width:40px;"></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Production Tab --}}
                            <div class="tab-pane fade" id="moOutputsTab">
                                <div class="table-responsive">
                                    <table class="table m-0" id="moOutputsTable">
                                        <thead>
                                            <tr>
                                                <th>Produced Qty</th>
                                                <th>Destination</th>
                                                <th>Recorded By</th>
                                                <th>Date &amp; Time</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>

                            {{-- Returns Tab --}}
                            <div class="tab-pane fade" id="moReturnsTab">
                                <div class="table-responsive">
                                    <table class="table m-0" id="moReturnsTable">
                                        <thead>
                                            <tr>
                                                <th style="width:32px;"></th>
                                                <th style="width:52px;">#</th>
                                                <th>DATE &amp; TIME</th>
                                                <th>RETURNED BY</th>
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

            {{-- MO Details Card --}}
            <div class="card" id="moDetailsCard">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-8">
                        <h5 class="mb-0" id="moNumber">Manufacturing Order <strong>#—</strong></h5>
                        <div class="d-flex gap-2" id="moBadges"></div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-3">
                            <h6 class="mb-0">Finished Product</h6>
                            <p class="mb-0" id="moProductName">—</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-0">Bill of Materials</h6>
                            <p class="mb-0" id="moBomName">—</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-0">Source Warehouse</h6>
                            <p class="mb-0" id="moSourceLocation">—</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-0">Destination Warehouse</h6>
                            <p class="mb-0" id="moDestLocation">—</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-0">Qty to Produce</h6>
                            <p class="mb-0" id="moPlannedQty">—</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-0">Produced Qty</h6>
                            <p class="mb-0" id="moProducedQty">—</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-0">Scheduled Date</h6>
                            <p class="mb-0" id="moScheduledDate">—</p>
                        </div>
                        <div class="col-md-3">
                            <h6 class="mb-0">Created By</h6>
                            <p class="mb-0" id="moCreatedBy">—</p>
                        </div>
                        <div class="col-md-12">
                            <h6 class="mb-0">Notes</h6>
                            <p class="mb-0" id="moNotes">—</p>
                        </div>
                    </div>

                    {{-- Components --}}
                    <div class="table-responsive border border-bottom-0 border-top-0 rounded">
                        <table class="table m-0" id="moComponentsTable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">
                                        Planned Qty
                                        <i class="bx bx-info-circle ms-1 text-muted th-info-icon"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="Total quantity required per the Bill of Materials for this order's planned production quantity."></i>
                                    </th>
                                    <th class="text-end">
                                        Consumed
                                        <i class="bx bx-info-circle ms-1 text-muted th-info-icon"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="Total quantity used across all recorded production outputs."></i>
                                    </th>
                                    <th class="text-end">
                                        Returned
                                        <i class="bx bx-info-circle ms-1 text-muted th-info-icon"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="Total quantity returned to the warehouse across all return events."></i>
                                    </th>
                                    <th class="text-end">
                                        On Floor
                                        <i class="bx bx-info-circle ms-1 text-muted th-info-icon"
                                           data-bs-toggle="tooltip" data-bs-placement="top"
                                           title="Quantity currently on the production floor — issued to production but not yet consumed or returned."></i>
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr><td colspan="5" class="text-center text-muted py-3">Loading...</td></tr>
                            </tbody>
                        </table>
                    </div>

                </div>
            </div>

        </div>

        <div class="col-lg-4">
            <div class="card full-height-sticky-card">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title m-0 me-2">Timeline</h5>
                </div>
                <div class="card-body pt-2">
                    <ul class="timeline timeline-outline mb-0" id="moHistoryTimeline">
                        <li class="timeline-item timeline-item-transparent">
                            <div class="timeline-event text-muted">Loading...</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

</div>

@if($tenantContext->canDo('manufacturing_orders', 'write'))
@includeOnce('app.components.drawers.manufacturing-orders.add-edit')
@endif

@if($tenantContext->canDo('manufacturing_orders', 'material_allocation'))
@includeOnce('app.components.drawers.manufacturing-orders.add-allocation')
@includeOnce('app.components.drawers.inventory.product-serial-lot-picker')
@endif

@if($tenantContext->canDo('manufacturing_orders', 'produce'))
@includeOnce('app.components.drawers.manufacturing-orders.record-output')
@endif

@if($tenantContext->canDo('manufacturing_orders', 'material_return'))
@includeOnce('app.components.drawers.manufacturing-orders.return-materials')
@endif

@endsection

@push('scripts')
<script>
const moId = {{ $moId }};
let _moDetails = null;

const moStatusMap = {
    draft:         ['Draft',         'secondary'],
    confirmed:     ['Confirmed',     'info'],
    in_production: ['In Production', 'primary'],
    completed:     ['Completed',     'success'],
    cancelled:     ['Cancelled',     'danger'],
};

const moAllocationMap = {
    not_allocated:       ['Not Allocated', 'secondary'],
    partially_allocated: ['Partial',       'warning'],
    fully_allocated:     ['Allocated',     'success'],
};

const renderMoActionButtons = function(mo) {

    const isDraft     = mo.status === 'draft';
    const isConfirmed = mo.status === 'confirmed';

    const isInProduction = mo.status === 'in_production';

    let editBtn = '', confirmBtn = '', allocateBtn = '', recordOutputBtn = '', returnMaterialsBtn = '', forceCompleteBtn = '', cancelBtn = '';

    @if($tenantContext->canDo('manufacturing_orders', 'write'))
    if (isDraft) {
        editBtn = `<button class="btn btn-warning btn-sm" onclick="openMoFormDrawer(${mo.id})"><i class="icon-base bx bx-edit icon-sm me-2"></i>Edit</button>`;
    }
    @endif

    @if($tenantContext->canDo('manufacturing_orders', 'confirm'))
    if (isDraft) {
        confirmBtn = `<button class="btn btn-info btn-sm" onclick="moConfirm(${mo.id})"><i class="icon-base bx bx-like icon-sm me-2"></i>Confirm</button>`;
    }
    @endif

    @if($tenantContext->canDo('manufacturing_orders', 'material_allocation'))
    if (isConfirmed || isInProduction) {
        allocateBtn = `<button class="btn btn-outline-primary btn-sm" onclick="openMoAllocationDrawer(${mo.id})"><i class="icon-base bx bx-package icon-sm me-2"></i>Allocate Materials</button>`;
    }
    @endif

    @if($tenantContext->canDo('manufacturing_orders', 'produce'))
    if (isInProduction || (isConfirmed && mo.allocation_status !== 'not_allocated')) {
        recordOutputBtn = `<button class="btn btn-outline-success btn-sm" onclick="openMoRecordOutputDrawer(${mo.id})"><i class="icon-base bx bx-check-circle icon-sm me-2"></i>Record Production</button>`;
    }
    if (isInProduction) {
        forceCompleteBtn = `<button class="btn btn-outline-warning btn-sm" onclick="moForceComplete(${mo.id})"><i class="icon-base bx bx-flag icon-sm me-2"></i>Mark Complete</button>`;
    }
    @endif

    @if($tenantContext->canDo('manufacturing_orders', 'material_return'))
    if (isConfirmed || isInProduction || mo.status === 'completed') {
        const hasReturnable = (mo.material_items || []).some(function(i) {
            return (parseFloat(i.on_floor_qty) || 0) > 0;
        });
        if (hasReturnable) {
            returnMaterialsBtn = `<button class="btn btn-outline-secondary btn-sm" onclick="openMoReturnMaterialsDrawer(${mo.id})"><i class="icon-base bx bx-undo icon-sm me-2"></i>Return Materials</button>`;
        }
    }
    @endif

    @if($tenantContext->canDo('manufacturing_orders', 'cancel'))
    if (isDraft || isConfirmed) {
        cancelBtn = `<button class="btn btn-danger btn-sm" onclick="moCancel(${mo.id})"><i class="icon-base bx bx-x icon-sm me-1"></i>Cancel</button>`;
    }
    @endif

    document.getElementById('moActionButtons').innerHTML =
        `<div class="row"><div class="col-lg-8"><div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2">${editBtn}${confirmBtn}${cancelBtn}</div>
            <div class="d-flex gap-2">${allocateBtn}${recordOutputBtn}${returnMaterialsBtn}${forceCompleteBtn}</div>
        </div></div></div>`;
};

const renderMoDetails = function(mo) {

    document.getElementById('moNumber').querySelector('strong').textContent = '#' + mo.mo_number;

    const badgesEl = document.getElementById('moBadges');
    badgesEl.innerHTML = '';
    const s = moStatusMap[mo.status];
    if (s) badgesEl.insertAdjacentHTML('beforeend', `<span class="badge bg-label-${s[1]}">${s[0]}</span>`);
    const a = moAllocationMap[mo.allocation_status];
    if (a) badgesEl.insertAdjacentHTML('beforeend', `<span class="badge bg-label-${a[1]}">${a[0]}</span>`);

    document.getElementById('moProductName').textContent    = mo.product_name || '—';
    document.getElementById('moBomName').textContent        = mo.bom_name || '—';
    document.getElementById('moSourceLocation').textContent = mo.source_location_name || '—';
    document.getElementById('moDestLocation').textContent   = mo.destination_location_name || '—';
    document.getElementById('moPlannedQty').textContent     = formatQty(mo.planned_qty);
    document.getElementById('moProducedQty').textContent    = formatQty(mo.produced_qty);
    document.getElementById('moScheduledDate').textContent  = mo.planned_date
        ? formatMySqlDate(mo.planned_date, window.sysDefaultConfig.dateFormat) : '—';
    document.getElementById('moCreatedBy').textContent      = mo.created_by_name || '—';
    document.getElementById('moNotes').textContent          = mo.notes || '—';

    const tbody = document.querySelector('#moComponentsTable tbody');
    tbody.innerHTML = '';
    const items = mo.material_items || [];
    const isDraftStatus = mo.status === 'draft';
    if (!items.length) {
        tbody.innerHTML = `<tr><td colspan="5" class="text-center text-muted py-3">No components</td></tr>`;
    } else {
        items.forEach(function(item) {
            const uom      = item.uom_code ? ' ' + item.uom_code : '';

            const consumed = parseFloat(item.total_consumed) || 0;
            const returned = parseFloat(item.total_returned) || 0;
            const onFloor  = parseFloat(item.on_floor_qty)   || 0;

            const consumedDisplay = consumed <= 0 ? '—' : formatQty(consumed) + uom;
            const returnedDisplay = returned <= 0 ? '—' : formatQty(returned) + uom;
            const onFloorDisplay  = onFloor  <= 0 ? '—' : formatQty(onFloor)  + uom;

            const returnedClass = returned > 0 ? 'text-success' : 'text-muted';

            tbody.insertAdjacentHTML('beforeend', `
                <tr>
                    <td>${item.product_name || '—'}</td>
                    <td class="text-end">${formatQty(item.planned_qty)}${uom}</td>
                    <td class="text-end text-muted small">${isDraftStatus ? '—' : consumedDisplay}</td>
                    <td class="text-end small ${returnedClass}">${isDraftStatus ? '—' : returnedDisplay}</td>
                    <td class="text-end fw-semibold small">${isDraftStatus ? '—' : onFloorDisplay}</td>
                </tr>
            `);
        });
    }

    // Show documents card for non-draft orders
    const docsCard = document.getElementById('moDocumentsCard');
    if (docsCard) {
        docsCard.classList.toggle('d-none', mo.status === 'draft');
    }

    renderMoAllocations(mo.allocations || [], mo.status);
    renderMoOutputs(mo.outputs || []);
    renderMoReturns(mo.returns || []);
};

const renderMoAllocations = function(allocations, moStatus) {
    const countEl = document.getElementById('moAllocationsCount');
    const activeCount = (allocations || []).filter(a => a.status !== 'cancelled').length;
    if (countEl) countEl.textContent = activeCount;

    const tbody = document.querySelector('#moAllocationsTable tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!allocations.length) {
        tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">No allocations yet</td></tr>`;
        return;
    }

    // Pre-pass: for each serial_id record the last allocation index that contains it.
    // Allocations are ordered ASC so the last occurrence = the allocation it was consumed from.
    // Any earlier occurrence = the serial was returned from that allocation.
    const lastAllocIndexForSerial = {};
    allocations.forEach(function(alloc, idx) {
        (alloc.items || []).forEach(function(ai) {
            (ai.serials || []).forEach(function(s) {
                lastAllocIndexForSerial[s.serial_id] = idx;
            });
        });
    });

    let html = '';

    allocations.forEach(function(alloc, idx) {
        const isCancelled  = alloc.status === 'cancelled';
        const expandedInit = false;

        const statusBadge = isCancelled
            ? `<span class="badge bg-label-warning">Cancelled</span>`
            : `<span class="badge bg-label-success">Active</span>`;

        const actionBtn = '';

        // Build nested item rows — last row gets border-bottom-0 to avoid double border
        const allocItems = alloc.items || [];
        const itemRows = allocItems.map(function(item, itemIdx) {
            const isLast  = itemIdx === allocItems.length - 1;
            const lastCls = isLast ? ' border-bottom-0' : '';
            const serials = item.serials || [];
            let qtyCell = '';

            let nameCell = '';
            if (serials.length > 0) {
                const chips = serials.map(function(s) {
                    const sn     = s.serial_number || s;
                    const status = s.status || 'picked';
                    const isLastAlloc = (lastAllocIndexForSerial[s.serial_id] === idx);

                    let chipColor, chipLabel;
                    if (status === 'consumed') {
                        if (isLastAlloc) {
                            chipColor = 'danger';    chipLabel = 'Consumed';
                        } else {
                            chipColor = 'warning';   chipLabel = 'Returned';
                        }
                    } else if (status === 'picked') {
                        chipColor = 'secondary';     chipLabel = 'Active';
                    } else {
                        chipColor = 'warning';       chipLabel = 'Returned';
                    }

                    return `<span class="badge bg-label-${chipColor} me-1 mb-1" style="font-size:0.74em;" title="${chipLabel}">${sn}</span>`;
                }).join('');
                nameCell = `${item.product_name || '—'}<div class="mt-1">${chips}</div>`;
                qtyCell  = `${serials.length}`;
            } else {
                nameCell = item.product_name || '—';
                const qty = parseFloat(item.allocated_qty) || 0;
                const uom = item.uom_code ? ` <span class="text-muted small">${item.uom_code}</span>` : '';
                qtyCell = qty > 0 ? `${formatQty(qty)}${uom}` : '—';
            }

            return `<tr>
                <td class="${lastCls}">${nameCell}</td>
                <td class="text-end${lastCls}">${qtyCell}</td>
            </tr>`;
        }).join('') || `<tr><td colspan="2" class="text-muted small border-bottom-0">No items</td></tr>`;

        html += `
        <tr class="alloc-header-row" data-alloc-id="${alloc.id}" style="cursor:pointer;">
            <td class="text-center position-relative"><i class="bx bx-plus-circle alloc-chevron absolute-center" style="font-size:1.1rem;"></i></td>
            <td class="fw-semibold text-muted">${idx + 1}</td>
            <td>${alloc.date_time ?? '—'}</td>
            <td>${alloc.created_by_name ?? '—'}</td>
            <td>${statusBadge}</td>
            <td class="text-end" onclick="event.stopPropagation();">${actionBtn}</td>
        </tr>
        <tr class="alloc-items-row${expandedInit ? '' : ' d-none'}" data-alloc-id="${alloc.id}">
            <td colspan="6" class="p-0 border-top-0">
                <div class="p-4">
                    ${alloc.notes ? `<p class="text-muted small mb-3"><span class="fw-semibold">Notes:</span> ${alloc.notes}</p>` : ''}
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small text-uppercase py-1" style="letter-spacing:.04em;">Item</th>
                                <th class="small text-uppercase py-1 text-end" style="letter-spacing:.04em; width:200px;">Allocated Qty</th>
                            </tr>
                        </thead>
                        <tbody>${itemRows}</tbody>
                    </table>
                </div>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;

    // Bind collapse/expand on header rows
    tbody.querySelectorAll('.alloc-header-row').forEach(function(row) {
        row.addEventListener('click', function(e) {
            if (e.target.closest('.dropdown')) return;
            var allocId  = this.dataset.allocId;
            var itemsRow = tbody.querySelector(`.alloc-items-row[data-alloc-id="${allocId}"]`);
            var chevron  = this.querySelector('.alloc-chevron');
            if (!itemsRow) return;
            var collapsed = itemsRow.classList.toggle('d-none');
            chevron.classList.toggle('bx-plus-circle', collapsed);
            chevron.classList.toggle('bx-minus-circle', !collapsed);
        });
    });
};

const renderMoTimeline = function(history = []) {

    const container = document.getElementById('moHistoryTimeline');
    if (!container) return;

    container.innerHTML = '';

    if (!Array.isArray(history) || history.length === 0) {
        container.innerHTML = `
            <li class="timeline-item timeline-item-transparent">
                <div class="timeline-event text-muted">No history available</div>
            </li>`;
        return;
    }

    history.forEach(function(item) {
        const title = item.title || ucFirst((item.log_type || '').replace(/_/g, ' '));
        const notes = item.notes ? `<p class="mb-1 small text-muted">${item.notes}</p>` : '';

        container.insertAdjacentHTML('beforeend', `
            <li class="timeline-item timeline-item-transparent border-dashed">
                <span class="timeline-point timeline-point-info"></span>
                <div class="timeline-event">
                    <div class="timeline-header mb-1">
                        <h6 class="mb-0">${title}</h6>
                        <small class="text-body-secondary">${item.performed_by || 'System'}</small>
                    </div>
                    ${notes}
                    <div class="small text-muted mb-1">
                        <div>${item.date_time || '—'}</div>
                    </div>
                </div>
            </li>
        `);
    });
};

const loadMoDetail = async function() {
    try {
        const response = await api.get(`/manufacturing/orders/${moId}`);
        _moDetails = response.data.data.mo_details || {};
        renderMoDetails(_moDetails);
        renderMoTimeline(_moDetails.history || []);
        renderMoActionButtons(_moDetails);
    } catch(err) {
        handleApiError(err);
    }
};

const doMoConfirm = async function(id, acknowledgedWarning = false) {
    


    try {
        
        const payload = acknowledgedWarning ? { acknowledged_warning: true } : {};
        const response = await api.post(`/manufacturing/orders/${id}/confirm`, payload);
        const { status, warnings, message } = response.data;
       
        if (status === 'warning') {

            const listItems = warnings.map(w => `<li>${w}</li>`).join('');
            const html = `<strong>Insufficient stock for some materials:</strong><ul>${listItems}</ul><p class="fw-semibold text-muted mt-2 mb-0"><small>The order can still be confirmed and materials sourced before production.</small></p>`;
            
            showConfirmation(html, 'warning',
                { text: 'Confirm Anyway', class: 'btn-info', callback: () => doMoConfirm(id, true) },
                { text: 'Cancel' },
                { width: '32em', htmlContainer: 'swal-warning' }
            );
            
            return;
        }

        notyf.success(message);
        loadMoDetail();
        
    } catch(err) {
        handleApiError(err);
    }
};

const moConfirm = function(id) {
    showConfirmation('Are you sure you want to confirm this manufacturing order?', 'warning',
        { text: 'Confirm', class: 'btn-label-primary', callback: () => doMoConfirm(id) },
        { text: 'Cancel' }
    );
};

const moCancel = function(id) {
    showConfirmation('Are you sure you want to cancel this manufacturing order? Reserved stock will be released.', 'warning', {
        'text': 'Cancel Order', 'class': 'btn-label-danger', 'callback': async function() {
            try {
                const response = await api.post(`/manufacturing/orders/${id}/cancel`);
                notyf.success(response.data.message);
                loadMoDetail();
            } catch(err) {
                handleApiError(err);
            }
        }
    });
};

const moForceComplete = function(id) {
    showConfirmation(
        'Mark this order as complete? Issued materials not yet consumed or returned will remain unaccounted for. Consider returning them before closing. This cannot be undone.',
        'warning',
        {
            'text': 'Complete', 'class': 'btn-warning', 'callback': async function() {
                try {
                    const response = await api.post(`/manufacturing/orders/${id}/force-complete`);
                    notyf.success(response.data.message);
                    loadMoDetail();
                } catch(err) {
                    handleApiError(err);
                }
            }
        }
    );
};

const renderMoOutputs = function(outputs) {
    const countEl = document.getElementById('moOutputsCount');
    if (countEl) countEl.textContent = outputs.length;

    const tbody = document.querySelector('#moOutputsTable tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!outputs.length) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">No outputs recorded yet</td></tr>`;
        return;
    }

    outputs.forEach(function(o) {
        var serialHtml = '';
        if (o.serials && o.serials.length) {
            serialHtml = '<div class="d-flex flex-wrap gap-1 mt-1">' +
                o.serials.map(function(sn) {
                    return '<span class="badge bg-label-success">' +
                        String(sn).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;') +
                        '</span>';
                }).join('') + '</div>';
        }
        tbody.insertAdjacentHTML('beforeend', `<tr>
            <td><span class="fw-semibold">${formatQty(o.output_qty)}</span>${serialHtml}</td>
            <td>${o.destination_location_name || '—'}</td>
            <td>${o.created_by_name || '—'}</td>
            <td>${o.date_time || '—'}</td>
        </tr>`);
    });
};

const renderMoReturns = function(returns) {
    const countEl = document.getElementById('moReturnsCount');
    if (countEl) countEl.textContent = returns.length;

    const tbody = document.querySelector('#moReturnsTable tbody');
    if (!tbody) return;

    tbody.innerHTML = '';

    if (!returns.length) {
        tbody.innerHTML = `<tr><td colspan="4" class="text-center text-muted py-3">No material returns yet</td></tr>`;
        return;
    }

    let html = '';

    returns.forEach(function(ret, idx) {
        const items = ret.items || [];

        const itemRows = items.map(function(ri, riIdx) {
            const isLast   = riIdx === items.length - 1;
            const lastCls  = isLast ? ' border-bottom-0' : '';
            const isSerial = ri.stock_tracking_method === 'serial';
            const serials  = ri.serials || [];

            let nameCell, qtyCell;
            if (isSerial && serials.length) {
                const chips = serials.map(function(sn) {
                    return `<span class="badge bg-label-warning me-1 mb-1" style="font-size:0.74em;">${sn}</span>`;
                }).join('');
                nameCell = `${ri.product_name || '—'}<div class="mt-1">${chips}</div>`;
                qtyCell  = serials.length;
            } else {
                nameCell = ri.product_name || '—';
                const qty = parseFloat(ri.returned_qty) || 0;
                const uom = ri.uom_code ? ` <span class="text-muted small">${ri.uom_code}</span>` : '';
                qtyCell = qty > 0 ? `${formatQty(qty)}${uom}` : '—';
            }

            return `<tr>
                <td class="${lastCls}">${nameCell}</td>
                <td class="text-end${lastCls}">${qtyCell}</td>
            </tr>`;
        }).join('') || `<tr><td colspan="2" class="text-muted small border-bottom-0">No items</td></tr>`;

        html += `
        <tr class="return-header-row" data-return-id="${ret.id}" style="cursor:pointer;">
            <td class="text-center position-relative"><i class="bx bx-plus-circle return-chevron absolute-center" style="font-size:1.1rem;"></i></td>
            <td class="fw-semibold text-muted">${idx + 1}</td>
            <td>${ret.date_time ?? '—'}</td>
            <td>${ret.created_by_name ?? '—'}</td>
        </tr>
        <tr class="return-items-row d-none" data-return-id="${ret.id}">
            <td colspan="4" class="p-0 border-top-0">
                <div class="p-4">
                    ${ret.notes ? `<p class="text-muted small mb-3"><span class="fw-semibold">Notes:</span> ${ret.notes}</p>` : ''}
                    <table class="table table-sm table-bordered mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="small text-uppercase py-1" style="letter-spacing:.04em;">Item</th>
                                <th class="small text-uppercase py-1 text-end" style="letter-spacing:.04em; width:200px;">Qty Returned</th>
                            </tr>
                        </thead>
                        <tbody>${itemRows}</tbody>
                    </table>
                </div>
            </td>
        </tr>`;
    });

    tbody.innerHTML = html;

    tbody.querySelectorAll('.return-header-row').forEach(function(row) {
        row.addEventListener('click', function() {
            const returnId = this.dataset.returnId;
            const itemsRow = tbody.querySelector(`.return-items-row[data-return-id="${returnId}"]`);
            const chevron  = this.querySelector('.return-chevron');
            if (!itemsRow) return;
            const collapsed = itemsRow.classList.toggle('d-none');
            chevron.classList.toggle('bx-plus-circle', collapsed);
            chevron.classList.toggle('bx-minus-circle', !collapsed);
        });
    });
};


jQuery(document).ready(function() {
    loadMoDetail();

    // Init tooltips on static table header icons
    document.querySelectorAll('#moComponentsTable [data-bs-toggle="tooltip"]').forEach(function(el) {
        new bootstrap.Tooltip(el);
    });

    // Documents card accordion + tab wiring (mirrors Sales Order pattern)
    const moDocumentsEl = document.getElementById('moDocumentsBody');
    const moCollapse    = new bootstrap.Collapse(moDocumentsEl, { toggle: false });
    const moDocTabs     = document.querySelectorAll('#moDocumentsCard .doc-tab');
    const moDocPanes    = document.querySelectorAll('#moDocumentsCard .tab-pane');
    let moDefaultTab    = moDocTabs[0];

    function moDeactivateAllTabs() {
        moDocTabs.forEach(t => t.classList.remove('active'));
        moDocPanes.forEach(p => p.classList.remove('show', 'active'));
    }
    function moActivateTab(tab) {
        moDeactivateAllTabs();
        if (!tab) return;
        tab.classList.add('active');
        const pane = document.querySelector(tab.dataset.bsTarget);
        if (pane) pane.classList.add('show', 'active');
    }

    moDocTabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            moDefaultTab = this;
            moActivateTab(this);
            if (!moDocumentsEl.classList.contains('show')) moCollapse.show();
        });
    });

    moDocumentsEl.addEventListener('shown.bs.collapse', function() { moActivateTab(moDefaultTab); });
    moDocumentsEl.addEventListener('hidden.bs.collapse', function() {
        moDefaultTab = moDocTabs[0];
        moDeactivateAllTabs();
    });

    document.querySelector('#moDocumentsCard .accordion-toggle').addEventListener('click', function() {
        moCollapse.toggle();
        this.querySelector('i').classList.toggle('bx-chevron-up');
        this.querySelector('i').classList.toggle('bx-chevron-down');
    });
});
</script>
@endpush
