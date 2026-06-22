@extends('layouts.app')
@section('title', 'Return Details')

@section('content')
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Return Details</h4>
    </div>

    <div id="returnActionButtons"></div>

    <div class="row g-4">
        <div class="col-lg-8">

            <div class="card" id="returnDetailsCard">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-center mb-8">
                        <h5 class="mb-0" id="returnNumber">Return <strong>#—</strong></h5>
                        <div class="d-flex gap-2" id="returnStatusBadges"></div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <h6 class="mb-0">Sales Order</h6>
                            <p class="mb-0" id="returnReferenceDisplay">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Customer</h6>
                            <p class="mb-0" id="returnPartyDisplay">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Return Date</h6>
                            <p class="mb-0" id="returnDateDisplay">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Receive Location</h6>
                            <p class="mb-0" id="returnLocationDisplay">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Received At</h6>
                            <p class="mb-0" id="returnReceivedAtDisplay">-</p>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h6 class="mb-0">Notes</h6>
                        <p class="mb-0" id="returnNotesDisplay">-</p>
                    </div>

                    <div class="table-responsive border border-bottom-0 border-top-0 rounded">
                        <table class="table m-0" id="returnItemsDetailTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Return Qty</th>
                                    <th>Disposition</th>
                                    <th>Reason</th>
                                    <th>Follow-Up</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody><tr><td colspan="6" class="text-center text-muted py-3">Loading…</td></tr></tbody>
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
                    <ul class="timeline timeline-outline mb-0" id="returnHistoryTimeline">
                        <li class="timeline-item timeline-item-transparent">
                            <div class="timeline-event text-muted">No history available</div>
                        </li>
                    </ul>
                </div>
            </div>

        </div>
    </div>

</div>

@if($tenantContext->canDo('sales_returns', 'write'))
@includeOnce('app.components.drawers.sales-returns.add-edit')
@endif

<!-- Follow-Up Modal -->
<div class="modal fade" id="followUpModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Process Follow-Up</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 p-3 bg-light rounded">
                    <div class="fw-semibold" id="fuProductName">—</div>
                    <div class="small text-muted mt-1">
                        <span id="fuDisposition">—</span>
                        &nbsp;·&nbsp;Total returned: <span id="fuTotalQty">—</span>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">Quantity to process</label>
                    <div class="input-group">
                        <input type="number" class="form-control" id="fuQtyInput" min="0.0001" step="any" placeholder="0">
                        <span class="input-group-text" id="fuUomLabel">—</span>
                    </div>
                    <div class="form-text" id="fuRemainingHint"></div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-medium">Notes <span class="text-muted fw-normal">(optional)</span></label>
                    <textarea class="form-control" id="fuNotes" rows="2" placeholder="e.g. Passed inspection, customer error…"></textarea>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-success flex-fill" id="fuRestockBtn">
                        <i class="bx bx-check-circle me-1"></i>Restock to Stock
                    </button>
                    <button type="button" class="btn btn-danger flex-fill" id="fuScrapBtn">
                        <i class="bx bx-trash me-1"></i>Scrap
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const returnId = <?= (int) $return->id ?>;

const returnStatusMap = {
    draft:      ['Draft',      'secondary'],
    in_transit: ['In Transit', 'warning'],
    received:   ['Received',   'success'],
    cancelled:  ['Cancelled',  'dark'],
};

const bucketBadgeMap = {
    unrestricted: 'success',
    quality:      'warning',
    blocked:      'danger',
    scrap:        'dark',
};

let _returnDetails = null;

const renderReturnDetailsSection = function(ret) {

    _returnDetails = ret;

    const wrapper = document.getElementById('returnDetailsCard');

    wrapper.querySelector('#returnNumber strong').textContent = `#${ret.return_number}`;

    const badgeWrap = wrapper.querySelector('#returnStatusBadges');
    badgeWrap.innerHTML = '';
    const s = returnStatusMap[ret.status] || [ret.status, 'secondary'];
    badgeWrap.insertAdjacentHTML('beforeend',
        `<span class="badge bg-label-${s[1]}">${s[0]}</span>`
    );

    wrapper.querySelector('#returnPartyDisplay').textContent    = ret.party_name || '-';
    wrapper.querySelector('#returnReferenceDisplay').innerHTML  = ret.so_number
        ? `<a href="/sales/orders/${ret.reference_id}/">${ret.so_number}</a>`
        : (ret.reference_id ? `#${ret.reference_id}` : '-');
    wrapper.querySelector('#returnDateDisplay').textContent     = formatMySqlDate(ret.return_date, window.sysDefaultConfig.dateFormat);
    wrapper.querySelector('#returnLocationDisplay').textContent = ret.received_location_name || '-';
    wrapper.querySelector('#returnReceivedAtDisplay').textContent = ret.received_at
        ? formatMySqlDate(ret.received_at, window.sysDefaultConfig.dateFormat)
        : '-';
    wrapper.querySelector('#returnNotesDisplay').textContent    = ret.notes || '-';

    // Action buttons
    let editBtn = '', transitBtn = '', receiveBtn = '', cancelBtn = '';

    if (ret.status === 'draft' && canDo('sales_returns', 'write')) {
        editBtn    = `<button class="btn btn-warning btn-sm ret-action-btn" data-action="edit"><i class="icon-base bx bx-edit icon-sm me-2"></i>Edit</button>`;
        transitBtn = `<button class="btn btn-primary btn-sm ret-action-btn" data-action="in_transit"><i class="icon-base bx bx-car icon-sm me-2"></i>Mark In Transit</button>`;
        receiveBtn = `<button class="btn btn-success btn-sm ret-action-btn" data-action="received"><i class="icon-base bx bx-check icon-sm me-2"></i>Receive</button>`;
        cancelBtn  = `<button class="btn btn-danger btn-sm ret-action-btn" data-action="cancelled"><i class="icon-base bx bx-x icon-sm me-1"></i>Cancel</button>`;
    }
    if (ret.status === 'in_transit' && canDo('sales_returns', 'write')) {
        receiveBtn = `<button class="btn btn-success btn-sm ret-action-btn" data-action="received"><i class="icon-base bx bx-check icon-sm me-2"></i>Receive</button>`;
        cancelBtn  = `<button class="btn btn-danger btn-sm ret-action-btn" data-action="cancelled"><i class="icon-base bx bx-x icon-sm me-1"></i>Cancel</button>`;
    }

    document.getElementById('returnActionButtons').innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2">
                ${editBtn}${transitBtn}${receiveBtn}${cancelBtn}
            </div>
        </div>`;
};


const renderReturnItems = function(items) {

    const tbody = document.querySelector('#returnItemsDetailTable tbody');

    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted py-3">No items</td></tr>';
        return;
    }

    tbody.innerHTML = items.map(item => {
        const uomCode      = item.uom_code || '';
        const returnQty    = parseFloat(item.return_qty)             || 0;
        const processedQty = parseFloat(item.follow_up_processed_qty) || 0;
        const remainingQty = Math.max(0, parseFloat((returnQty - processedQty).toFixed(4)));

        let trackHtml = '';
        if (item.serials && item.serials.length) {
            trackHtml = item.serials.map(s =>
                `<span class="badge badge-sm bg-label-primary me-1 mt-1" style="font-size:11px;font-weight:500;">${s.serial_number}</span>`
            ).join('');
            trackHtml = `<div class="mt-1">${trackHtml}</div>`;
        }

        let followUpHtml = '-';
        let actionsHtml  = '';

        if (item.follow_up_status === 'completed') {
            followUpHtml = `<span class="badge badge-sm bg-label-success">Follow-Up Done</span>`;

        } else if (item.follow_up_status === 'pending') {
            const badgeLabel = processedQty > 0
                ? `In Progress (${formatQty(processedQty)}/${formatQty(returnQty)})`
                : 'Follow-Up Required';
            followUpHtml = `<span class="badge badge-sm bg-label-warning">${badgeLabel}</span>`;

            if (canDo('sales_returns', 'write') && _returnDetails && _returnDetails.status === 'received') {
                const isSerial = item.serials && item.serials.length > 0;
                actionsHtml = `
                <div class="dropdown">
                    <a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a href="javascript:void(0);" class="dropdown-item fu-process-btn"
                            data-item-id="${item.id}"
                            data-product="${item.product_name}"
                            data-total-qty="${returnQty}"
                            data-remaining-qty="${remainingQty}"
                            data-uom="${uomCode}"
                            data-is-serial="${isSerial ? '1' : '0'}"
                            data-disposition="${item.disposition_name || ''}">Process Follow-Up</a></li>
                    </ul>
                </div>`;
            }
        }

        return `
        <tr>
            <td>
                <div class="fw-medium">${item.product_name}</div>
                ${trackHtml}
            </td>
            <td class="text-end">${formatQty(returnQty)} <span class="fs-tiny fw-semibold">${uomCode}</span></td>
            <td>${item.disposition_name || '-'}</td>
            <td>${item.reason_name || '-'}</td>
            <td>${followUpHtml}</td>
            <td class="text-center">${actionsHtml}</td>
        </tr>`;
    }).join('');
};


const renderReturnHistory = function(history) {

    const container = document.getElementById('returnHistoryTimeline');
    container.innerHTML = '';

    if (!history.length) {
        container.innerHTML = `<li class="timeline-item timeline-item-transparent"><div class="timeline-event text-muted">No history available</div></li>`;
        return;
    }

    history.forEach(h => {
        container.insertAdjacentHTML('beforeend', `
            <li class="timeline-item timeline-item-transparent border-dashed">
                <span class="timeline-point timeline-point-info"></span>
                <div class="timeline-event">
                    <div class="timeline-header mb-1">
                        <h6 class="mb-0">${h.title || ''}</h6>
                        <small class="text-body-secondary">${h.created_by_name || 'System'}</small>
                    </div>
                    <div class="small text-muted mb-1">${formatMySqlDate(h.created_at, window.sysDefaultConfig.dateFormat)}</div>
                </div>
            </li>
        `);
    });
};


const loadReturnDetail = async function() {
    try {
        const res  = await axios.get(`/api/sales/returns/${returnId}`);
        const data = res.data.data || {};
        renderReturnDetailsSection(data.return);
        renderReturnItems(data.items || []);
        renderReturnHistory(data.history || []);
    } catch (e) {
        handleApiError(e);
    }
};


const retActionConfigs = {
    in_transit: { text: 'Mark this return as in transit?',                         icon: 'question', confirmText: 'Confirm',     btnClass: 'btn-primary' },
    received:   { text: 'Receive this return? Inventory will be updated.',          icon: 'question', confirmText: 'Receive',     btnClass: 'btn-success' },
    cancelled:  { text: 'Cancel this return? This cannot be undone.',               icon: 'warning',  confirmText: 'Yes, Cancel', btnClass: 'btn-danger'  },
};

const retActionHandlers = {
    edit:       (id) => openSalesReturnFormDrawer(id, 0),
    in_transit: (id) => confirmRetAction(id, 'in_transit'),
    received:   (id) => confirmRetAction(id, 'received'),
    cancelled:  (id) => confirmRetAction(id, 'cancelled'),
};

const confirmRetAction = function(id, status) {
    const cfg = retActionConfigs[status];
    if (!cfg) return;

    showConfirmation(
        cfg.text, cfg.icon,
        { text: cfg.confirmText, class: cfg.btnClass, callback: () => updateReturnStatus(id, status) },
        { text: 'Cancel' }
    );
};

const updateReturnStatus = async function(id, status) {
    try {
        await axios.post(`/api/sales/returns/${id}/status`, { status });
        const labels = { in_transit: 'Marked as in transit', received: 'Return received — inventory updated', cancelled: 'Return cancelled' };
        notyf.success(labels[status] || 'Status updated');
        loadReturnDetail();
    } catch (e) {
        handleApiError(e);
    }
};


document.addEventListener('click', function(e) {
    const btn = e.target.closest('.ret-action-btn');
    if (!btn) return;
    const action = btn.dataset.action;
    if (retActionHandlers[action]) retActionHandlers[action](returnId);
});


// --- Follow-Up Modal ---
let _fuItemId       = null;
let _fuRemainingQty = 0;
const fuModal = new bootstrap.Modal(document.getElementById('followUpModal'));

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.fu-process-btn');
    if (!btn) return;

    _fuItemId       = parseInt(btn.dataset.itemId);
    _fuRemainingQty = parseFloat(btn.dataset.remainingQty) || 0;
    const totalQty  = parseFloat(btn.dataset.totalQty)     || 0;
    const uom       = btn.dataset.uom || '';
    const isSerial  = btn.dataset.isSerial === '1';

    document.getElementById('fuProductName').textContent  = btn.dataset.product;
    document.getElementById('fuTotalQty').textContent     = `${formatQty(totalQty)} ${uom}`.trim();
    document.getElementById('fuDisposition').textContent  = btn.dataset.disposition;
    document.getElementById('fuUomLabel').textContent     = uom || 'units';
    document.getElementById('fuNotes').value              = '';

    const qtyInput = document.getElementById('fuQtyInput');
    qtyInput.max   = _fuRemainingQty;
    qtyInput.value = _fuRemainingQty;
    qtyInput.readOnly = isSerial;

    const hint = document.getElementById('fuRemainingHint');
    hint.textContent = isSerial
        ? `Serial-tracked — must process all ${formatQty(_fuRemainingQty)} remaining at once`
        : `Remaining: ${formatQty(_fuRemainingQty)} ${uom}`.trim();

    fuModal.show();
});

const submitFollowUp = async function(action) {
    if (!_fuItemId) return;

    const qty = parseFloat(document.getElementById('fuQtyInput').value) || 0;
    if (qty <= 0 || qty > _fuRemainingQty + 0.0001) {
        showFormInputFeedback(document.getElementById('fuQtyInput'), `Enter a quantity between 0.0001 and ${_fuRemainingQty}`);
        return;
    }

    const actionLabel = action === 'restock' ? 'Restock to stock' : 'Scrap';
    const confirmText = action === 'restock' ? 'Yes, Restock' : 'Yes, Scrap';
    const btnClass    = action === 'restock' ? 'btn-success' : 'btn-danger';
    const notes       = document.getElementById('fuNotes').value.trim() || null;

    showConfirmation(
        `${actionLabel} ${formatQty(qty)} unit(s)? Inventory will be updated.`,
        'question',
        {
            text: confirmText, class: btnClass,
            callback: async () => {
                try {
                    fuModal.hide();
                    await axios.post(`/api/sales/returns/items/${_fuItemId}/follow-up`, { action, qty, notes });
                    notyf.success(action === 'restock' ? 'Items restocked to stock' : 'Items scrapped');
                    loadReturnDetail();
                } catch (err) {
                    handleApiError(err);
                }
            }
        },
        { text: 'Cancel' }
    );
};

document.getElementById('fuRestockBtn').addEventListener('click', () => submitFollowUp('restock'));
document.getElementById('fuScrapBtn').addEventListener('click',   () => submitFollowUp('scrap'));


document.addEventListener('DOMContentLoaded', function() {
    loadReturnDetail();
});
</script>
@endpush
