@extends('layouts.app')
@section('title', 'Proforma Invoice')

@section('content')

<?php $tenantContext = tenantContext(); ?>

<div class="container-fluid">

    <!-- Page Header -->
    <div class="mb-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h4 class="mb-0">Proforma Invoice <span class="text-muted fw-normal fs-5" id="pfDocCode"></span></h4>
            <span id="pfStatusBadge"></span>
            <span id="pfOutdatedBadge"></span>
        </div>
    </div>

    <!-- Customer subline (left) + action buttons (right) -->
    <div class="row mb-4">
        <div class="col-lg-9">
            <div class="row g-3 align-items-center">
                <div class="col-md-5" id="pfCustomerSubline"></div>
                <div class="col-md-7" id="pfActionButtons"></div>
            </div>
        </div>
    </div>

    <!-- Main layout: col-md-9 content + col-md-3 timeline -->
    <div class="row g-4">
        <div class="col-md-9">

            <!-- KPI Cards -->
            <div class="row g-4 mb-4">
                <!-- Invoice Status -->
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar avatar-sm flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-primary" id="pfKpiStatusIcon"><i class="icon-base bx bx-file-blank icon-lg"></i></span>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="detail-kpi-label">Invoice Status</div>
                                    <div class="fw-semibold text-truncate" id="pfKpiStatusText">—</div>
                                    <small id="pfKpiStatusSub"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Valid Until -->
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar avatar-sm flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-warning"><i class="icon-base bx bx-calendar-exclamation icon-lg"></i></span>
                                </div>
                                <div>
                                    <div class="detail-kpi-label">Valid Until</div>
                                    <div class="fw-semibold" id="pfKpiValidUntil">—</div>
                                    <small id="pfKpiValidUntilSub"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Payment Terms -->
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar avatar-sm flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-info"><i class="icon-base bx bx-credit-card icon-lg"></i></span>
                                </div>
                                <div>
                                    <div class="detail-kpi-label">Payment Terms</div>
                                    <div class="fw-semibold" id="pfKpiPaymentTerms">—</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Total Amount -->
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar avatar-sm flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-success"><i class="icon-base bx bx-rupee icon-lg"></i></span>
                                </div>
                                <div>
                                    <div class="detail-kpi-label">Total Amount</div>
                                    <div class="fw-semibold" id="pfKpiGrandTotal">—</div>
                                    <small class="text-muted" id="pfKpiItemCount"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Step card -->
            <div id="pfNextStepCard" class="d-none mb-4"></div>

            <!-- Tab nav -->
            <ul class="nav detail-tabs border g-5" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" type="button"><i class="icon-base bx bx-layout me-1"></i>Overview</button>
                </li>
            </ul>

            <!-- Overview pane -->
            <div class="detail-tab-pane">

                <!-- Customer card + Details card -->
                <div class="row g-4 mt-0">

                    <!-- Customer & Addresses -->
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="detail-label mb-1">Customer &amp; Addresses</div>
                                <p class="fw-semibold text-primary mb-1" id="pfCardCustomer">—</p>
                                <div class="row small g-3 mt-1">
                                    <div class="col-12" id="pfBillToCol">
                                        <div class="fw-semibold small text-muted mb-1">Bill To</div>
                                        <div class="d-flex flex-column g-1" id="pfBillingAddress"></div>
                                        <div class="d-none" id="pfCustomerGstinRow">
                                            <span>GSTIN: </span><span id="pfCustomerGstin"></span>
                                        </div>
                                    </div>
                                    <div class="col-6 d-none" id="pfShippingAddrRow">
                                        <div class="fw-semibold small text-muted mb-1">Ship To</div>
                                        <div class="d-flex flex-column g-1" id="pfShippingAddress"></div>
                                    </div>
                                </div>
                                <div class="mt-2 pt-2 border-top-dashed d-none" id="pfPosRow">
                                    <span class="detail-label detail-label-w">Place of Supply</span><span id="pfPlaceOfSupply">—</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Details -->
                    <div class="col-md-8">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-6">
                                        <span class="detail-label detail-label-w">Sales Order</span><a id="pfSoLink" href="#" class="text-primary fw-medium" target="_blank" rel="noopener">— <i class="ms-1 align-middle bx bx-link-external"></i></a>
                                    </div>
                                    <div class="col-6">
                                        <span class="detail-label detail-label-w">Proforma Date</span><span id="pfDetailDate">—</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="detail-label detail-label-w">Created By</span><span id="pfDetailCreatedBy">—</span>
                                    </div>
                                </div>
                                <div class="row g-2 mt-3 pt-2 border-top-dashed">
                                    <div class="col-12 d-none" id="pfNotesRow">
                                        <div class="detail-label">Notes</div>
                                        <p class="mb-0" id="pfNotes">—</p>
                                    </div>
                                    @include('partial.ui.terms-collapse', ['prefix' => 'pf'])
                                    @include('partial.ui.terms-collapse', ['prefix' => 'pfDecl', 'label' => 'Declaration'])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Items + Totals -->
                <div class="card mt-4">
                    <div class="table-responsive">
                        <table class="table m-0" id="pfItemsTable">
                            <thead>
                                <tr>
                                    <th class="border-top-0">#</th>
                                    <th class="border-top-0">Item</th>
                                    <th class="text-end border-top-0">Qty</th>
                                    <th class="text-end border-top-0">Unit Price</th>
                                    <th class="text-end border-top-0">Discount</th>
                                    <th class="text-end border-top-0">Tax</th>
                                    <th class="text-end border-top-0">Amount</th>
                                </tr>
                            </thead>
                            <tbody><tr><td colspan="7" class="text-center text-muted py-3">Loading…</td></tr></tbody>
                        </table>
                    </div>
                    <div class="card-body pt-3">
                        <div class="d-flex justify-content-end">
                            <table class="table table-borderless w-auto mb-0">
                                <tbody>
                                    <tr>
                                        <th class="ps-0 text-muted w-px-300">Subtotal</th>
                                        <td class="px-0 text-end" id="pfSubtotal">-</td>
                                    </tr>
                                    <tr class="d-none" id="pfItemDiscRow">
                                        <th class="ps-0 text-muted w-px-300">Item Discounts</th>
                                        <td class="px-0 text-end text-danger" id="pfItemDisc">-</td>
                                    </tr>
                                    <tr class="d-none" id="pfOrderDiscRow">
                                        <th class="ps-0 text-muted w-px-300">Order Discount</th>
                                        <td class="px-0 text-end text-danger" id="pfOrderDisc">-</td>
                                    </tr>
                                </tbody>
                                <tbody id="pfGstRowsGroup">
                                    <tr>
                                        <th class="ps-0 text-muted w-px-300">Tax</th>
                                        <td class="px-0 text-end" id="pfTax">-</td>
                                    </tr>
                                </tbody>
                                <tbody>
                                    <tr class="d-none" id="pfRoundOffRow">
                                        <th class="ps-0 text-muted w-px-300">Round Off</th>
                                        <td class="px-0 text-end" id="pfRoundOff">-</td>
                                    </tr>
                                    <tr class="d-none" id="pfAdjustmentRow">
                                        <th class="ps-0 text-muted w-px-300" id="pfAdjustmentLabel">Adjustment</th>
                                        <td class="px-0 text-end" id="pfAdjustment">-</td>
                                    </tr>
                                    <tr class="border-top">
                                        <th class="ps-0 w-px-300">Total</th>
                                        <td class="px-0 text-end fw-bold" id="pfGrandTotal">-</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div id="pfRcmTotalsNote" class="d-none mt-2">
                            <small class="text-primary justify-content-end d-flex align-items-center">
                                <i class="bx bx-info-circle me-1"></i>GST payable under Reverse Charge by the recipient directly to the government.
                            </small>
                        </div>
                    </div>
                </div>

            </div><!-- /overview pane -->
        </div>

        <!-- Timeline sidebar -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">Timeline</h5>
                </div>
                <div class="card-body pt-2">
                    <ul class="timeline timeline-outline mb-0" id="pfHistoryTimeline">
                        <li class="timeline-item timeline-item-transparent">
                            <div class="timeline-event text-muted">No history available</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Email Composer Modal -->
@if($tenantContext->canDo('proforma_invoices', 'send_email'))
<div class="modal fade" id="pfEmailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Proforma Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="pfEmailForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="pfEmailTo">To <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="pfEmailTo" name="to" placeholder="recipient@example.com" />
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="pfEmailCc">CC <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="email" class="form-control" id="pfEmailCc" name="cc" placeholder="cc@example.com" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="pfEmailBcc">BCC <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="email" class="form-control" id="pfEmailBcc" name="bcc" placeholder="bcc@example.com" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="pfEmailSubject">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="pfEmailSubject" name="subject" />
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea id="pfEmailBody" name="body"></textarea>
                    </div>
                    <div id="pfEmailAttachmentsList" class="d-flex flex-wrap gap-2 mt-2"></div>
                    <input type="file" id="pfEmailAttachments" multiple class="d-none" />
                    <div class="form-glob-feedback mt-2"></div>
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="pfAttachFilesBtn" title="Attach files">
                    <i class="icon-base bx bx-paperclip fs-5"></i>
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary" id="pfEmailSubmitBtn">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
const pfId = {{ $proformaId }};
let _pfData          = null;
let _pfJoditInstance = null;
let _pfDefaultBody   = '';
let _pfAttachedFiles = [];
let _pfEmailModal    = null;

const pfStatusMap = {
    draft:     ['Draft',     'warning'],
    sent:      ['Sent',      'success'],
    cancelled: ['Cancelled', 'danger'],
};

@if($tenantContext->canDo('proforma_invoices', 'send_email'))
const pfDraftStep = {
    icon: 'bx-send', title: 'Send to customer',
    desc: 'Share this proforma invoice with your customer for review.',
    action: 'send_email', btnText: 'Send Email', btnClass: 'btn-primary', actionBtnClass: 'pf-action-btn',
};
const pfDraftOutdatedStep = {
    icon: 'bx-error-circle', title: 'Proforma is outdated',
    desc: 'The Sales Order was amended after this proforma was created. Review and resend to the customer.',
    action: 'send_email', btnText: 'Send Email', btnClass: 'btn-warning', actionBtnClass: 'pf-action-btn',
};
@else
const pfDraftStep = null;
const pfDraftOutdatedStep = null;
@endif

const buildPfAttachmentList = (attachments) => {
    if (!attachments || !attachments.length) return '';
    const links = attachments.map(a => {
        const name = a.original_name || a.name || a.filename || 'attachment';
        const icon = a.is_image ? 'bx-image' : 'bx-file';
        const isViewable = a.is_image || a.mime_type === 'application/pdf';
        const viewIcon = isViewable
            ? `<a href="javascript:void(0);" onclick="openPdfViewer('${a.download_url}', '${name.replace(/'/g, "\\'")}')" class="ms-1 flex-shrink-0" title="View"><i class="icon-base bx bx-show"></i></a>`
            : '';
        return `<div class="d-flex align-items-center py-1">
                    <a href="javascript:void(0);" onclick="downloadAttachment('${a.download_url}', '${name.replace(/'/g, "\\'")}')"
                       class="d-flex align-items-center gap-1 small text-decoration-none flex-grow-1" title="${name}">
                        <i class="icon-base bx ${icon} flex-shrink-0"></i>
                        <span class="text-truncate" style="max-width:180px;">${name}</span>
                    </a>${viewIcon}
                </div>`;
    }).join('');
    return `<div class="rounded px-2 py-1 mt-1 shadow-none bg-soft-surface">${links}</div>`;
};

const renderPfHistoryItemMeta = (logType, meta) => {
    if (logType !== 'sent' || !meta) return '';
    let html = '<ul class="mt-2 mb-2 ps-3 small">';
    if (meta.to)      html += `<li>To: <strong class="text-primary">${meta.to}</strong></li>`;
    if (meta.cc)      html += `<li>CC: <strong class="text-primary">${meta.cc}</strong></li>`;
    if (meta.subject) html += `<li>Subject: <strong class="text-primary">${meta.subject}</strong></li>`;
    html += '</ul>';
    html += buildPfAttachmentList(meta.attachments || []);
    return html;
};

const renderPfActionButtons = (pf) => {
    let sendBtn = '';
    @if($tenantContext->canDo('proforma_invoices', 'send_email'))
    if (pf.status !== 'cancelled') {
        sendBtn = `<button class="btn btn-outline-secondary btn-sm pf-action-btn" data-action="send_email">
            <i class="icon-base bx bx-envelope icon-sm me-1"></i>Send
        </button>`;
    }
    @endif

    const viewBtn = `<button class="btn btn-outline-secondary btn-sm pf-action-btn" data-action="pdf-view">
        <i class="icon-base bx bx-show icon-sm me-1"></i>View
    </button>`;

    const moreItems = [];
    @if($tenantContext->canDo('proforma_invoices', 'send_email'))
    if (pf.status === 'draft') {
        moreItems.push(`<li><a class="dropdown-item pf-action-btn" data-action="mark_sent" href="javascript:void(0);"><i class="icon-base bx bx-check icon-sm me-2"></i>Mark as Sent</a></li>`);
    }
    @endif
    @if($tenantContext->canDo('proforma_invoices', 'cancel'))
    if (pf.status === 'draft' || pf.status === 'sent') {
        if (moreItems.length) moreItems.push('<li><hr class="dropdown-divider"></li>');
        moreItems.push(`<li><a class="dropdown-item text-danger pf-action-btn" data-action="cancel" href="javascript:void(0);"><i class="icon-base bx bx-x icon-sm me-2"></i>Cancel</a></li>`);
    }
    @endif

    const moreBtn = moreItems.length
        ? `<div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">More</button>
            <ul class="dropdown-menu dropdown-menu-end">${moreItems.join('')}</ul>
           </div>`
        : '';

    document.getElementById('pfActionButtons').innerHTML = `<div class="d-flex justify-content-lg-end gap-3">${sendBtn}${viewBtn}${moreBtn}</div>`;
};

const renderPfDetails = (pf) => {
    _pfData = pf;

    document.title = `Proforma Invoice — ${pf.proforma_number}`;
    document.getElementById('pfDocCode').textContent = pf.proforma_number ? `— #${pf.proforma_number}` : '';

    // Header badges
    renderDetailStatusBadge(document.getElementById('pfStatusBadge'), null, pfStatusMap, pf.status);
    document.getElementById('pfOutdatedBadge').innerHTML = (pf.is_outdated && pf.status !== 'cancelled')
        ? '<span class="badge bg-label-warning ms-1">Outdated</span>'
        : '';

    // Customer subline
    const sublineEl = document.getElementById('pfCustomerSubline');
    if (sublineEl) {
        sublineEl.innerHTML = pf.customer_name
            ? `<div class="d-flex align-items-center gap-2"><i class="icon-base bx bx-user icon-sm text-muted"></i><span class="fw-semibold">${pf.customer_name}</span></div>`
            : '';
    }

    // KPI: Invoice Status
    const kpiColors = { draft: 'warning', sent: 'success', cancelled: 'danger' };
    const kpiIcons  = { draft: 'bx-file-blank', sent: 'bx-check-circle', cancelled: 'bx-x-circle' };
    const kpiColor  = kpiColors[pf.status] || 'secondary';
    document.getElementById('pfKpiStatusIcon').className = `avatar-initial rounded bg-label-${kpiColor}`;
    document.getElementById('pfKpiStatusIcon').innerHTML = `<i class="icon-base bx ${kpiIcons[pf.status] || 'bx-file-blank'} icon-lg"></i>`;
    document.getElementById('pfKpiStatusText').textContent = pfStatusMap[pf.status]?.[0] || pf.status;
    let statusSub = '';
    if (pf.status === 'sent' && pf.sent_at) {
        statusSub = `Sent on ${formatMySqlDate(pf.sent_at, window.sysDefaultConfig.dateFormat)}`;
    } else if (pf.status === 'draft') {
        statusSub = pf.is_outdated
            ? '<span class="text-warning">Outdated — SO was amended</span>'
            : 'Awaiting dispatch';
    }
    document.getElementById('pfKpiStatusSub').innerHTML = statusSub;

    // KPI: Valid Until
    if (pf.valid_until) {
        document.getElementById('pfKpiValidUntil').textContent = formatMySqlDate(pf.valid_until, window.sysDefaultConfig.dateFormat);
        const today = new Date(); today.setHours(0, 0, 0, 0);
        const diff  = Math.round((new Date(pf.valid_until) - today) / 86400000);
        let vSub = '';
        if (pf.status !== 'cancelled') {
            if (diff < 0)        vSub = `<span class="text-danger">Expired ${Math.abs(diff)} day${Math.abs(diff) !== 1 ? 's' : ''} ago</span>`;
            else if (diff === 0) vSub = `<span class="text-warning">Expires today</span>`;
            else if (diff <= 7)  vSub = `<span class="text-warning">In ${diff} day${diff !== 1 ? 's' : ''}</span>`;
            else                 vSub = `<span class="text-muted">In ${diff} days</span>`;
        }
        document.getElementById('pfKpiValidUntilSub').innerHTML = vSub;
    } else {
        document.getElementById('pfKpiValidUntil').textContent = '—';
        document.getElementById('pfKpiValidUntilSub').textContent = '';
    }

    // KPI: Payment Terms
    document.getElementById('pfKpiPaymentTerms').textContent = pf.payment_terms || '—';

    // KPI: Total Amount
    document.getElementById('pfKpiGrandTotal').textContent = formatCurrency(pf.grand_total);
    document.getElementById('pfKpiItemCount').textContent  = pf.items ? `${pf.items.length} item${pf.items.length !== 1 ? 's' : ''}` : '';

    // Next Step card
    const effectivePfStatus = (pf.status === 'draft' && pf.is_outdated) ? 'draft_outdated' : pf.status;
    const pfSteps = {};
    if (pfDraftStep)         pfSteps['draft']          = pfDraftStep;
    if (pfDraftOutdatedStep) pfSteps['draft_outdated'] = pfDraftOutdatedStep;
    renderDetailNextStep('pfNextStepCard', pfSteps, effectivePfStatus);

    // Action buttons
    renderPfActionButtons(pf);

    // Customer card
    document.getElementById('pfCardCustomer').textContent = pf.customer_name || '—';

    const billAddr = pf.billing_address  && Object.keys(pf.billing_address).length  ? pf.billing_address  : null;
    const shipAddr = pf.shipping_address && Object.keys(pf.shipping_address).length ? pf.shipping_address : null;
    const billAddrEl        = document.getElementById('pfBillingAddress');
    const shipAddrEl        = document.getElementById('pfShippingAddress');
    const pfShippingAddrRow = document.getElementById('pfShippingAddrRow');
    const pfBillToCol       = document.getElementById('pfBillToCol');

    if (billAddrEl && billAddr) billAddrEl.innerHTML = formatAddrHtml(billAddr);

    if (shipAddr && shipAddrEl) {
        shipAddrEl.innerHTML = formatAddrHtml(shipAddr);
        pfShippingAddrRow?.classList.remove('d-none');
        pfBillToCol?.classList.replace('col-12', 'col-6');
    } else {
        pfShippingAddrRow?.classList.add('d-none');
        pfBillToCol?.classList.replace('col-6', 'col-12');
    }

    const pfPosRow = document.getElementById('pfPosRow');
    if (pf.place_of_supply_name) {
        document.getElementById('pfPlaceOfSupply').textContent =
            pf.place_of_supply_name + (pf.place_of_supply_code ? ` (${pf.place_of_supply_code})` : '');
        pfPosRow?.classList.remove('d-none');
    } else {
        pfPosRow?.classList.add('d-none');
    }

    const pfGstinRow = document.getElementById('pfCustomerGstinRow');
    if (pf.customer_gstin_snapshot) {
        document.getElementById('pfCustomerGstin').textContent = pf.customer_gstin_snapshot;
        pfGstinRow?.classList.remove('d-none');
    } else {
        pfGstinRow?.classList.add('d-none');
    }

    // Details card
    const pfSoLink = document.getElementById('pfSoLink');
    if (pfSoLink) {
        pfSoLink.innerHTML = `${pf.so_number || '—'} <i class="ms-1 align-middle bx bx-link-external"></i>`;
        pfSoLink.href = `/sales/orders/${pf.sales_order_id}/`;
    }
    document.getElementById('pfDetailDate').textContent      = formatMySqlDate(pf.proforma_date, window.sysDefaultConfig.dateFormat);
    document.getElementById('pfDetailCreatedBy').textContent = pf.created_by_name || '—';

    const pfNotesRow = document.getElementById('pfNotesRow');
    if (pf.notes) {
        document.getElementById('pfNotes').textContent = pf.notes;
        pfNotesRow?.classList.remove('d-none');
    } else {
        pfNotesRow?.classList.add('d-none');
    }

    const pfTermsEl = document.getElementById('pfTerms');
    if (pfTermsEl) pfTermsEl.innerHTML = pf.invoice_terms || '<em class="text-muted">No terms set.</em>';

    const pfDeclTermsEl = document.getElementById('pfDeclTerms');
    if (pfDeclTermsEl) pfDeclTermsEl.innerHTML = pf.invoice_declaration || '<em class="text-muted">No declaration set.</em>';

    // Items table
    const tbody = document.querySelector('#pfItemsTable tbody');
    if (!pf.items || !pf.items.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">No items</td></tr>`;
    } else {
        const isRcm = !!pf.reverse_charge;
        let html = '';
        pf.items.forEach((item, i) => {
            const discAmt  = parseFloat(item.discount_amount || 0);
            const taxArr   = Array.isArray(item.tax_info) ? item.tax_info : [];
            const taxLabel = taxArr.map(t => t.name).filter(Boolean).join(', ') || '—';
            const lineAmt  = isRcm ? item.taxable_amount : item.line_total;
            html += `<tr>
                <td>${i + 1}</td>
                <td>
                    <div class="fw-medium">${item.product_name || ''}</div>
                    ${item.description ? `<div class="text-muted small">${item.description}</div>` : ''}
                </td>
                <td class="text-end">${formatQty(item.quantity)}${item.uom_code ? ` <small class="fw-semibold">${item.uom_code}</small>` : ''}</td>
                <td class="text-end">${formatCurrency(item.unit_price)}</td>
                <td class="text-end">${discAmt > 0 ? formatCurrency(discAmt) : '—'}</td>
                <td class="text-end">${taxLabel}</td>
                <td class="text-end fw-medium">${formatCurrency(lineAmt)}</td>
            </tr>`;
        });
        tbody.innerHTML = html;
    }

    // Totals
    document.getElementById('pfSubtotal').textContent  = formatCurrency(pf.subtotal);
    document.getElementById('pfGrandTotal').textContent = formatCurrency(parseFloat(pf.grand_total));
    document.getElementById('pfRcmTotalsNote')?.classList.toggle('d-none', !pf.reverse_charge);

    const gstGroup = document.getElementById('pfGstRowsGroup');
    if (pf.gst_summary && pf.gst_summary.rows && pf.gst_summary.rows.length) {
        const gs  = pf.gst_summary;
        const rcm = pf.reverse_charge ? ' <span class="badge p-1 fs-tiny bg-label-primary">RCM</span>' : '';
        let html = '';
        if (gs.totals.taxable_amount > 0 && Math.abs(gs.totals.taxable_amount - parseFloat(pf.subtotal)) > 0.001) {
            html += `<tr><th class="ps-0 text-muted fw-normal w-px-300">Taxable Amount</th><td class="px-0 text-end">${formatCurrency(gs.totals.taxable_amount)}</td></tr>`;
        }
        if (gs.is_intra_state) {
            if (gs.totals.cgst_amount > 0) html += `<tr><th class="ps-0 fw-normal w-px-300">CGST${rcm}</th><td class="px-0 text-end">${formatCurrency(gs.totals.cgst_amount)}</td></tr>`;
            if (gs.use_ugst && gs.totals.ugst_amount > 0) html += `<tr><th class="ps-0 fw-normal w-px-300">UGST${rcm}</th><td class="px-0 text-end">${formatCurrency(gs.totals.ugst_amount)}</td></tr>`;
            else if (!gs.use_ugst && gs.totals.sgst_amount > 0) html += `<tr><th class="ps-0 fw-normal w-px-300">SGST${rcm}</th><td class="px-0 text-end">${formatCurrency(gs.totals.sgst_amount)}</td></tr>`;
        } else {
            if (gs.totals.igst_amount > 0) html += `<tr><th class="ps-0 fw-normal w-px-300">IGST${rcm}</th><td class="px-0 text-end">${formatCurrency(gs.totals.igst_amount)}</td></tr>`;
        }
        if (gs.totals.cess_amount > 0) html += `<tr><th class="ps-0 fw-normal w-px-300">CESS</th><td class="px-0 text-end">${formatCurrency(gs.totals.cess_amount)}</td></tr>`;
        gstGroup.innerHTML = html;
    } else {
        gstGroup.innerHTML = `<tr><th class="ps-0 text-muted w-px-300">Tax</th><td class="px-0 text-end">${formatCurrency(pf.tax_amount)}</td></tr>`;
    }

    const itemDiscTotal  = parseFloat(pf.item_discount_total  || 0);
    const orderDiscTotal = parseFloat(pf.order_discount_amount || 0);
    const itemDiscRow    = document.getElementById('pfItemDiscRow');
    const orderDiscRow   = document.getElementById('pfOrderDiscRow');
    if (itemDiscTotal > 0)  { document.getElementById('pfItemDisc').textContent  = '- ' + formatCurrency(itemDiscTotal);  itemDiscRow?.classList.remove('d-none'); }
    else                    { itemDiscRow?.classList.add('d-none'); }
    if (orderDiscTotal > 0) { document.getElementById('pfOrderDisc').textContent = '- ' + formatCurrency(orderDiscTotal); orderDiscRow?.classList.remove('d-none'); }
    else                    { orderDiscRow?.classList.add('d-none'); }

    const roundOff    = parseFloat(pf.round_off_amount || 0);
    const roundOffRow = document.getElementById('pfRoundOffRow');
    if (roundOff !== 0) { document.getElementById('pfRoundOff').textContent = (roundOff < 0 ? '− ' : '+ ') + formatCurrency(Math.abs(roundOff)); roundOffRow?.classList.remove('d-none'); }
    else                { roundOffRow?.classList.add('d-none'); }

    const adjAmt = parseFloat(pf.adjustment_amount || 0);
    const adjRow = document.getElementById('pfAdjustmentRow');
    if (adjAmt !== 0) {
        document.getElementById('pfAdjustmentLabel').textContent = pf.adjustment_label || 'Adjustment';
        document.getElementById('pfAdjustment').textContent = formatCurrency(adjAmt);
        adjRow?.classList.remove('d-none');
    } else {
        adjRow?.classList.add('d-none');
    }

    // Timeline
    renderDetailTimeline('pfHistoryTimeline', pf.history, renderPfHistoryItemMeta, {
        getActor: item => item.user_name || 'System',
        getDate:  item => formatMySqlDate(item.created_at, window.sysDefaultConfig.dateTimeFormat),
    });
};

// Action dispatch
const pfActionHandlers = {
    send_email: () => openEmailModal(),
    mark_sent:  () => markProformaAsSent(),
    cancel:     () => cancelProforma(),
    'pdf-view': () => openPdfViewer(`/sales/proforma-invoices/${pfId}/pdf`, `Proforma #${_pfData?.proforma_number || ''}`),
};

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.pf-action-btn');
    if (!btn) return;
    const action = btn.dataset.action;
    if (pfActionHandlers[action]) pfActionHandlers[action]();
});

const renderPfEmailAttachmentChips = () => {
    const container = document.getElementById('pfEmailAttachmentsList');
    container.innerHTML = '';
    _pfAttachedFiles.forEach((file, index) => {
        const chip = document.createElement('div');
        chip.className = 'd-inline-flex align-items-center gap-1 border rounded px-2 py-1 bg-light';
        chip.style.cssText = 'font-size:12px; max-width:220px;';
        chip.innerHTML = `
            <i class="bx bx-file-blank text-muted flex-shrink-0"></i>
            <span class="text-truncate" title="${file.name}">${file.name}</span>
            <button type="button" class="btn-close ms-1 flex-shrink-0" style="font-size:9px" data-attach-index="${index}" aria-label="Remove"></button>
        `;
        container.appendChild(chip);
    });
};

const openEmailModal = async () => {
    if (!_pfEmailModal) return;
    _pfDefaultBody   = '';
    _pfAttachedFiles = [];
    const form = document.getElementById('pfEmailForm');
    cleanFormInputFeedback(form);
    document.getElementById('pfEmailTo').value      = _pfData?.customer_email || '';
    document.getElementById('pfEmailCc').value      = '';
    document.getElementById('pfEmailBcc').value     = '';
    document.getElementById('pfEmailSubject').value = '';
    renderPfEmailAttachmentChips();
    if (_pfJoditInstance) { _pfJoditInstance.destruct(); _pfJoditInstance = null; }
    _pfEmailModal.show();
    try {
        const [defaultsRes, pdfRes] = await Promise.all([
            api.get(`/sales/proforma-invoices/${pfId}/email-defaults`),
            api.get(`/sales/proforma-invoices/${pfId}/generate-email-pdf`),
        ]);
        const defaults = defaultsRes.data?.data || {};
        if (defaults.cc)      document.getElementById('pfEmailCc').value      = defaults.cc;
        if (defaults.bcc)     document.getElementById('pfEmailBcc').value     = defaults.bcc;
        if (defaults.subject) document.getElementById('pfEmailSubject').value = defaults.subject;
        _pfDefaultBody = defaults.body || '';
        if (_pfJoditInstance) _pfJoditInstance.value = _pfDefaultBody;
        const attachment = pdfRes.data?.data || null;
        if (attachment) { _pfAttachedFiles = [attachment]; renderPfEmailAttachmentChips(); }
    } catch (err) {
        notyf.error("Unable to load email defaults");
    }
};

const markProformaAsSent = () => {
    showConfirmation(
        "Mark this proforma as sent? Use this when you've shared it manually (print, WhatsApp, etc.).",
        "info",
        { text: "Yes, Mark as Sent", class: "btn-success", callback: async () => {
            try {
                await api.post(`/sales/proforma-invoices/${pfId}/mark-sent`);
                notyf.success("Proforma invoice marked as sent");
                loadPf();
            } catch (err) { handleApiError(err); }
        }},
        { text: "Keep as Draft" }
    );
};

const cancelProforma = async () => {
    const result = await Swal.fire({
        title: 'Cancel Proforma Invoice',
        html: '<p class="text-muted mb-2">This cannot be undone. The proforma will be marked as cancelled.</p>',
        input: 'textarea',
        inputLabel: 'Reason for cancellation (optional)',
        inputPlaceholder: 'Enter reason...',
        inputAttributes: { rows: 3, style: 'font-size:13px; resize:none;' },
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, Cancel',
        cancelButtonText: 'Keep',
        confirmButtonColor: '#d33',
        reverseButtons: true,
    });
    if (!result.isConfirmed) return;
    try {
        await api.post(`/sales/proforma-invoices/${pfId}/cancel`, { note: (result.value || '').trim() });
        notyf.success("Proforma invoice cancelled");
        loadPf();
    } catch (err) { handleApiError(err); }
};

const handlePfSendEmail = async () => {
    const form    = document.getElementById('pfEmailForm');
    const sendBtn = document.getElementById('pfEmailSubmitBtn');
    cleanFormInputFeedback(form);
    const payload = {
        to:          document.getElementById('pfEmailTo').value.trim(),
        cc:          document.getElementById('pfEmailCc').value.trim(),
        bcc:         document.getElementById('pfEmailBcc').value.trim(),
        subject:     document.getElementById('pfEmailSubject').value.trim(),
        body:        _pfJoditInstance ? _pfJoditInstance.value : '',
        attachments: _pfAttachedFiles,
    };
    sendBtn.disabled = true;
    try {
        await api.post(`/sales/proforma-invoices/${pfId}/send-email`, payload);
        _pfEmailModal.hide();
        notyf.success("Proforma invoice sent successfully");
        loadPf();
    } catch (err) {
        handleApiError(err, form);
    } finally {
        sendBtn.disabled = false;
    }
};

const loadPf = async () => {
    try {
        const res = await api.get(`/sales/proforma-invoices/${pfId}`);
        renderPfDetails(res.data.data);
    } catch (err) {
        notyf.error("Unable to load proforma invoice");
    }
};

document.addEventListener('DOMContentLoaded', function() {
    loadPf();

    @if($tenantContext->canDo('proforma_invoices', 'send_email'))
    _pfEmailModal = new bootstrap.Modal(document.getElementById('pfEmailModal'), {
        backdrop: 'static', keyboard: false, focus: false,
    });

    document.getElementById('pfEmailModal').addEventListener('shown.bs.modal', function() {
        if (_pfJoditInstance) { _pfJoditInstance.destruct(); _pfJoditInstance = null; }
        _pfJoditInstance = Jodit.make('#pfEmailBody', {
            height: 300, enter: 'BR',
            buttons: 'bold,italic,underline,strikethrough,|,ul,ol,|,paragraph,|,link,image',
            toolbarAdaptive: false, showCharsCounter: false, showWordsCounter: false,
            showXPathInStatusbar: false, addNewLine: false,
        });
        _pfJoditInstance.value = _pfDefaultBody;
    });

    document.getElementById('pfEmailSubmitBtn').addEventListener('click', handlePfSendEmail);

    document.getElementById('pfAttachFilesBtn').addEventListener('click', function() {
        document.getElementById('pfEmailAttachments').click();
    });

    document.getElementById('pfEmailAttachments').addEventListener('change', async function() {
        if (!this.files.length) return;
        const newFiles = await readFilesAsBase64(this);
        _pfAttachedFiles.push(...newFiles);
        renderPfEmailAttachmentChips();
        this.value = '';
    });

    document.getElementById('pfEmailAttachmentsList').addEventListener('click', function(e) {
        const btn = e.target.closest('[data-attach-index]');
        if (!btn) return;
        _pfAttachedFiles.splice(parseInt(btn.dataset.attachIndex), 1);
        renderPfEmailAttachmentChips();
    });
    @endif
});
</script>
@endpush
