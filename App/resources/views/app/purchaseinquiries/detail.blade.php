@extends('layouts.app')
@section('title', 'Purchase Inquiry')

@section('content')

<?php $tenantContext = tenantContext(); ?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Purchase Inquiry <span class="text-muted fw-normal fs-5" id="piDocCode"></span></h4>
    </div>

    <div id="piActionButtons"></div>

    <div
        id="piDetailPage"
        data-id="{{ $inquiry->id }}"
        data-can-write="{{ $tenantContext->canDo('purchase_inquiries', 'write') ? '1' : '0' }}"
        data-can-cancel="{{ $tenantContext->canDo('purchase_inquiries', 'cancel') ? '1' : '0' }}"
        data-can-send-rfq="{{ $tenantContext->canDo('purchase_inquiries', 'send_rfq') ? '1' : '0' }}"
        data-can-award="{{ $tenantContext->canDo('purchase_inquiries', 'award') ? '1' : '0' }}"
        data-price-comparison="{{ $vendor_quote_comparison ? '1' : '0' }}">

        <div class="row g-4">
            <div class="col-lg-8">

                <!-- Quote Requests Card -->
                <div class="card mb-4" id="piQuoteRequestsCard">
                    <div class="card-header py-0">
                        <div class="d-flex align-items-stretch">
                            <ul class="nav nav-tabs flex-shrink-0 gap-4" role="tablist">
                                <li class="nav-item">
                                    <button class="nav-link doc-tab px-0 pi-qr-tab" data-bs-target="#piQRTab" type="button">Quote Requests <span class="badge bg-label-primary ms-1" id="piQRCount">0</span></button>
                                </li>
                            </ul>
                            <button class="pi-qr-accordion-toggle flex-grow-1 px-0 border-0 bg-transparent text-end" type="button" aria-label="Toggle">
                                <i class="bx bx-chevron-down fs-4"></i>
                            </button>
                        </div>
                    </div>
                    <div id="piQRCollapse" class="accordion-collapse collapse">
                        <div class="card-body">
                            <div class="tab-content px-0">
                                <div class="tab-pane fade" id="piQRTab">
                                    <div class="table-responsive">
                                        <table class="table m-0" id="piQRTable">
                                            <thead>
                                                <tr>
                                                    <th>Vendor</th>
                                                    <th>Email Sent To</th>
                                                    <th>Email Sent On</th>
                                                    <th>Status</th>
                                                    <th></th>
                                                </tr>
                                            </thead>
                                            <tbody id="piQRTableBody">
                                                <tr><td colspan="5" class="text-center text-muted py-4">No vendors added yet.</td></tr>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inquiry Details Card -->
                <div class="card mb-4" id="piDetails">
                    <div class="card-body">

                        <div class="d-flex gap-3 align-items-start mb-2">
                            <div style="flex:1;min-width:0;" class="d-none" id="piTitleRow">
                                <h5 class="mb-1 fw-bold" id="piTitleText"></h5>
                            </div>
                            <div style="min-width:130px;">                            
                                <div class="d-flex justify-content-end gap-2" id="piBadges"></div>
                            </div>
                        </div>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <h6 class="mb-0">Required By</h6>
                                <p class="mb-0" id="piRequiredBy">-</p>
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-0">Created By</h6>
                                <p class="mb-0" id="piCreatedBy">-</p>
                            </div>
                            <div class="col-md-4">
                                <h6 class="mb-0">Created</h6>
                                <p class="mb-0" id="piCreatedAt">-</p>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="mb-0">Notes</h6>
                            <p class="mb-0" id="piNotes">-</p>
                        </div>

                        <div class="mb-4 d-none" id="piInternalNotesSection">
                            <h6 class="mb-0 text-muted">Internal Notes</h6>
                            <p class="mb-0" id="piInternalNotes">-</p>
                        </div>

                        <!-- Items Table -->
                        <div class="table-responsive border border-bottom-0 border-top-0 rounded">
                            <table class="table m-0" id="piItemsTable">
                                <thead>
                                    <tr>
                                        <th>Item</th>
                                        <th class="text-end">Qty</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody id="piItemsBody"><tr><td colspan="4" class="text-center text-muted">Loading...</td></tr></tbody>
                            </table>
                        </div>

                    </div>
                </div>

            </div>

            <div class="col-lg-4">

                <!-- Timeline Card -->
                <div class="card full-height-sticky-card">
                    <div class="card-header d-flex justify-content-between">
                        <h5 class="card-title m-0 me-2">Timeline</h5>
                        <div class="dropdown">
                            <button class="btn text-body-secondary p-0" type="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                <i class="icon-base bx bx-dots-vertical-rounded icon-lg"></i>
                            </button>
                            <div class="dropdown-menu dropdown-menu-end">
                                <a class="dropdown-item" href="javascript:void(0);">Add log</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body pt-2">
                        <ul class="timeline timeline-outline mb-0" id="piHistoryTimeline">
                            <li class="timeline-item timeline-item-transparent">
                                <div class="timeline-event text-muted">No history available</div>
                            </li>
                        </ul>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Step 1: Vendor Selection Modal -->
<div class="modal fade" id="piVendorSelectModal" tabindex="-1" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-sm">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send to Vendor</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Select Vendor <span class="text-danger">*</span></label>
                    <select class="form-select" id="piStvVendorSelect"></select>
                </div>
                <div class="alert alert-warning py-2 d-none" id="piStvResendWarning">
                    <i class="bx bx-info-circle me-1"></i> This vendor was already sent an RFQ. Proceeding will resend the inquiry.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="piStvNextBtn">Next</button>
            </div>
        </div>
    </div>
</div>

<!-- Step 2: Email Composer Modal -->
<div class="modal fade" id="piEmailComposerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0">Send RFQ</h5>
                    <small class="text-muted fw-semibold" id="piEmailComposerVendorName"></small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="piEmailComposerForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="piEmailTo">To <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="piEmailTo" name="to" placeholder="recipient@example.com" />
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="piEmailCc">CC <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="email" class="form-control" id="piEmailCc" name="cc" placeholder="cc@example.com" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="piEmailBcc">BCC <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="email" class="form-control" id="piEmailBcc" name="bcc" placeholder="bcc@example.com" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="piEmailSubject">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="piEmailSubject" name="subject" />
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea id="piEmailBody" name="body"></textarea>
                    </div>
                    <div id="piEmailAttachmentsList" class="d-flex flex-wrap gap-2 mt-2"></div>
                    <input type="file" id="piEmailAttachments" multiple class="d-none" />
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="piAttachFilesBtn" title="Attach files">
                    <i class="icon-base bx bx-paperclip fs-5"></i>
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary" id="piSendEmailSubmitBtn">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Vendor Price Entry Drawer -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="piPriceDrawer" aria-labelledby="piPriceDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 55%;">
    <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="piPriceDrawerTitle">Enter Vendor Prices</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
    </div>
    <div class="offcanvas-body">
        <form id="piPriceForm">
            <input type="hidden" id="piPriceDrawerVendorId" value="" />
            <div class="form-glob-feedback mb-3"></div>

            <div class="mb-4">
                <h6 class="text-uppercase text-muted small mb-3">Quote Details</h6>
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label">Vendor Quote #</label>
                        <input type="text" class="form-control form-control-sm" name="vendor_quote_number" placeholder="Vendor's ref #" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quote Date</label>
                        <input type="text" class="form-control form-control-sm" id="piQuoteDate" name="vendor_quote_date" placeholder="Date" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Valid Until</label>
                        <input type="text" class="form-control form-control-sm" id="piQuoteValidityDate" name="quote_validity_date" placeholder="Date" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Payment Terms</label>
                        <select class="form-select form-select-sm" name="payment_term_id" id="piQuotePaymentTerm"></select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Delivery Terms</label>
                        <input type="text" class="form-control form-control-sm" name="delivery_terms" placeholder="e.g. FOB Mumbai" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Lead Time (days)</label>
                        <input type="number" class="form-control form-control-sm" name="lead_time_days" placeholder="0" min="0" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Freight Charges</label>
                        <input type="number" class="form-control form-control-sm text-end" name="freight_charges" value="0" step="any" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Other Charges</label>
                        <div class="input-group input-group-sm">
                            <input type="text" class="form-control" name="other_charges_label" placeholder="Label" style="max-width:90px;" />
                            <input type="number" class="form-control text-end" name="other_charges" value="0" step="any" />
                        </div>
                    </div>
                    <div class="col-12">
                        <label class="form-label">Vendor Notes</label>
                        <textarea class="form-control form-control-sm" name="vendor_quote_notes" rows="2" placeholder="Notes from the vendor"></textarea>
                    </div>
                </div>
            </div>

            <div class="mb-4">
                <h6 class="text-uppercase text-muted small mb-3">Item Prices</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle table-sm mb-0" id="piPriceItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th>Product</th>
                                <th class="text-center" style="width:80px;">Can Supply</th>
                                <th class="text-end" style="width:120px;">Unit Price</th>
                                <th class="text-end" style="width:100px;">Line Total</th>
                                <th style="width:130px;">Notes</th>
                            </tr>
                        </thead>
                        <tbody id="piPriceItemsBody"></tbody>
                    </table>
                </div>
            </div>

        </form>
    </div>
    <div class="offcanvas-footer">
        <div class="d-flex gap-2">
            <button type="button" id="savePiPriceBtn" class="btn btn-primary btn-sm">Save Prices</button>
            <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>

@if(tenantContext()->canDo('purchase_inquiries', 'write'))
@includeOnce('app.components.drawers.purchase-inquiries.add-edit')
@endif

@endsection

@push('scripts')
<script>
const _piEl              = document.getElementById('piDetailPage');
const PI_ID              = parseInt(_piEl.dataset.id);
const PI_CAN_WRITE       = _piEl.dataset.canWrite        === '1';
const PI_CAN_CANCEL      = _piEl.dataset.canCancel       === '1';
const PI_CAN_SEND_RFQ    = _piEl.dataset.canSendRfq      === '1';
const PI_CAN_AWARD       = _piEl.dataset.canAward        === '1';
const PI_PRICE_COMPARISON = _piEl.dataset.priceComparison === '1';

const refreshPurchaseInquiryDetails = function() { loadPiData(); };

let piData    = null;
let piFormCtx = null;

const piStatusMap = {
    draft:               { label: 'Draft',               color: 'warning' },
    sent:                { label: 'Sent',                color: 'info' },
    partially_responded: { label: 'Partially Responded', color: 'primary' },
    fully_responded:     { label: 'Fully Responded',     color: 'success' },
    awarded:             { label: 'Awarded',             color: 'success' },
    cancelled:           { label: 'Cancelled',           color: 'danger' },
};

const piVendorStatusMap = {
    pending:   { label: 'Pending',   color: 'secondary' },
    sent:      { label: 'Sent',      color: 'info' },
    responded: { label: 'Responded', color: 'primary' },
    awarded:   { label: 'Awarded',   color: 'success' },
    rejected:  { label: 'Rejected',  color: 'danger' },
};

// ==========================================
// Load & render detail
// ==========================================
const loadPiData = async function() {
    try {
        const res = await api.get(`/purchase/inquiries/${PI_ID}`);
        piData = res.data.data || res.data;
        renderPiDetails();
        renderPiQuoteRequests();
        renderPiActionButtons();
    } catch (err) {
        notyf.error('Unable to load inquiry details');
    }
};

const renderPiDetails = function() {
    const inquiry = piData.inquiry;
    const status  = inquiry.status;

    const piDocCodeEl = document.getElementById('piDocCode');
    if (piDocCodeEl) piDocCodeEl.textContent = inquiry.inquiry_number ? `— #${inquiry.inquiry_number}` : '';

    const badgeWrap = document.getElementById('piBadges');
    badgeWrap.innerHTML = '';
    const s = piStatusMap[status] || { label: status, color: 'secondary' };
    badgeWrap.insertAdjacentHTML('beforeend', `<span class="badge bg-label-${s.color}">${s.label}</span>`);

    document.getElementById('piRequiredBy').textContent = inquiry.required_by_date
        ? formatMySqlDate(inquiry.required_by_date, window.sysDefaultConfig.dateFormat) : '-';
    document.getElementById('piCreatedBy').textContent = inquiry.created_by_name || '-';
    document.getElementById('piCreatedAt').textContent = formatMySqlDate(inquiry.created_at, window.sysDefaultConfig.dateFormat);
    document.getElementById('piNotes').textContent     = inquiry.notes || '-';

    const titleRow = document.getElementById('piTitleRow');
    if (inquiry.title) {
        document.getElementById('piTitleText').textContent = inquiry.title;
        titleRow.classList.remove('d-none');
    } else {
        titleRow.classList.add('d-none');
    }

    const internalNotesSection = document.getElementById('piInternalNotesSection');
    if (inquiry.internal_notes) {
        document.getElementById('piInternalNotes').textContent = inquiry.internal_notes;
        internalNotesSection.classList.remove('d-none');
    } else {
        internalNotesSection.classList.add('d-none');
    }

    const items = piData.items || [];
    const tbody = document.getElementById('piItemsBody');
    tbody.innerHTML = '';

    items.forEach(function(item) {
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>
                    <div class="fw-medium">${item.product_name}</div>
                    ${item.description ? `<small class="text-muted">${item.description}</small>` : ''}
                </td>
                <td class="text-end text-nowrap">${formatQty(item.required_qty)}${item.uom_code ? ` <span class="text-muted small">${item.uom_code}</span>` : ''}</td>
                <td class="small text-muted">${item.notes || '—'}</td>
            </tr>
        `);
    });

    if (items.length === 0) {
        tbody.innerHTML = '<tr><td colspan="3" class="text-center text-muted py-4">No items</td></tr>';
    }
};

const renderPiActionButtons = function() {
    const inquiry   = piData.inquiry;
    const status    = inquiry.status;
    const isTerminal = ['awarded', 'cancelled'].includes(status);

    let editBtn = '', cancelBtn = '', poBtn = '', sendBtn = '';

    const downloadBtn = `<button class="btn btn-outline-secondary btn-sm pi-action-btn" data-action="pdf-download">
        <i class="icon-base bx bx-download icon-sm me-2"></i>Download
    </button>`;

    if (PI_CAN_SEND_RFQ && !isTerminal) {
        sendBtn = `<button class="btn btn-outline-primary btn-sm pi-action-btn" data-action="send-to-vendor">
            <i class="icon-base bx bx-envelope icon-sm me-2"></i>Send to Vendor
        </button>`;
    }

    if (PI_CAN_WRITE && status === 'draft') {
        editBtn = `<button class="btn btn-warning btn-sm pi-action-btn" data-action="edit">
            <i class="icon-base bx bx-edit icon-sm me-2"></i>Edit
        </button>`;
    }

    if (PI_CAN_CANCEL && !isTerminal) {
        cancelBtn = `<button class="btn btn-danger btn-sm pi-action-btn" data-action="cancel">
            <i class="icon-base bx bx-x icon-sm me-1"></i>Cancel
        </button>`;
    }

    if (status === 'awarded') {
        const awardedVendor = (piData.vendors || []).find(function(v) { return v.status === 'awarded'; });
        if (awardedVendor && awardedVendor.po_id) {
            poBtn = `<a href="/purchase/orders/${awardedVendor.po_id}/" class="btn btn-success btn-sm">
                <i class="bx bx-file me-1"></i> View PO
            </a>`;
        }
    }

    const html = `<div class="row"><div class="col-lg-8">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2">${editBtn}${poBtn}${cancelBtn}</div>
            <div class="d-flex gap-2">${sendBtn}${downloadBtn}</div>
        </div>
    </div></div>`;

    document.getElementById('piActionButtons').innerHTML = html;
};

// ==========================================
// Quote Requests Table
// ==========================================
const renderPiQuoteRequests = function() {
    const vendors    = piData.vendors || [];
    const status     = piData.inquiry.status;
    const isTerminal = ['awarded', 'cancelled'].includes(status);
    const totalItems = parseInt(piData.total_items) || 0;

    document.getElementById('piQRCount').textContent = vendors.length;

    const tbody = document.getElementById('piQRTableBody');
    tbody.innerHTML = '';

    if (vendors.length === 0) {
        tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-4">No vendors added yet.</td></tr>';
        return;
    }

    vendors.forEach(function(vendor) {
        const vs       = piVendorStatusMap[vendor.status] || { label: vendor.status, color: 'secondary' };
        const sentDate = vendor.sent_at ? formatMySqlDate(vendor.sent_at, window.sysDefaultConfig.dateFormat) : '—';

        let menuItems = '';

        if (['sent', 'responded'].includes(vendor.status) && PI_CAN_SEND_RFQ && !isTerminal) {
            menuItems += `<li><a class="dropdown-item" href="javascript:void(0);" onclick="openSendToVendorModal(${vendor.vendor_id}, true)">Resend RFQ</a></li>`;
        }
        if (['sent', 'responded'].includes(vendor.status) && PI_CAN_WRITE && PI_PRICE_COMPARISON) {
            menuItems += `<li><a class="dropdown-item" href="javascript:void(0);" onclick="openPriceDrawer(${vendor.id})">Enter Prices</a></li>`;
        }
        if (['sent', 'responded'].includes(vendor.status) && PI_CAN_AWARD && ['sent', 'partially_responded', 'fully_responded'].includes(status)) {
            menuItems += `<li><a class="dropdown-item text-success" href="javascript:void(0);" onclick="awardVendor(${vendor.id})">Award</a></li>`;
        }
        if (vendor.status === 'responded' && PI_CAN_WRITE && PI_PRICE_COMPARISON) {
            menuItems += `<li><hr class="dropdown-divider"></li><li><a class="dropdown-item text-warning" href="javascript:void(0);" onclick="withdrawQuote(${vendor.id})">Withdraw Quote</a></li>`;
        }
        if (vendor.status === 'rejected' && vendor.quote_id && PI_CAN_WRITE && PI_PRICE_COMPARISON) {
            menuItems += `<li><a class="dropdown-item" href="javascript:void(0);" onclick="openPriceDrawer(${vendor.id}, true)">View Prices</a></li>`;
        }
        if (vendor.status === 'awarded' && vendor.po_id) {
            menuItems += `<li><a class="dropdown-item" href="/purchase/orders/${vendor.po_id}/">View PO</a></li>`;
        }

        const actionsHtml = menuItems
            ? `<div class="d-inline-block">
                <a href="javascript:void(0);" class="btn text-body-secondary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown" aria-expanded="false"><i class="icon-base bx bx-dots-vertical-rounded"></i></a>
                <ul class="dropdown-menu dropdown-menu-end">${menuItems}</ul>
               </div>`
            : '';

        const emailSentTo = vendor.vendor_contact_email
            ? `<span class="small">${vendor.vendor_contact_email}</span>`
            : '<span class="text-muted">—</span>';

        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td class="fw-medium">${vendor.vendor_name}</td>
                <td>${emailSentTo}</td>
                <td class="small text-muted">${sentDate}</td>
                <td><span class="badge bg-label-${vs.color}">${vs.label}</span></td>
                <td class="text-end">${actionsHtml}</td>
            </tr>
        `);
    });
};

// ==========================================
// History
// ==========================================
const refreshPurchaseInquiryHistory = async function() {
    try {
        const res  = await api.get(`/purchase/inquiries/${PI_ID}/history`);
        const list = res.data.data || res.data || [];
        renderPurchaseInquiryHistory(list);
    } catch (err) {
        notyf.error('Unable to load inquiry history');
    }
};

const renderPiHistoryItemMeta = function(logType, meta) {
    if (!meta || typeof meta !== 'object') return '';

    let html = '';

    if (logType === 'updated_details' && Array.isArray(meta)) {
        html = `<ul class="mt-2 mb-2 ps-3 small">`;
        meta.forEach(function(item) {
            const oldVal = item.old_val || '';
            const newVal = item.new_val || '';
            if (oldVal || newVal) {
                if (oldVal && newVal && oldVal !== newVal) {
                    html += `<li>${item.label}: <span class="text-muted">${oldVal}</span> <span class="mx-1 text-primary fw-semibold">→</span> <strong class="text-primary">${newVal}</strong></li>`;
                } else if (newVal) {
                    html += `<li>${item.label}: <strong class="text-primary">${newVal}</strong></li>`;
                }
            }
        });
        html += `</ul>`;
    } else if (logType === 'updated_line_items' && Array.isArray(meta)) {
        const eventBadge = { added: 'bg-success', updated: 'bg-warning', removed: 'bg-danger' };
        html = `<ul class="mt-2 mb-2 ps-3 small">`;
        meta.forEach(function(item) {
            const badge = eventBadge[item.event] || 'bg-secondary';
            let detail = '';
            if (item.event === 'added') {
                detail = `Qty: <strong>${item.qty}</strong> ${item.uom || ''}`;
            } else if (item.event === 'updated') {
                detail = `Qty: <span class="text-muted">${item.old_qty}</span> <span class="mx-1 text-primary fw-semibold">→</span> <strong class="text-primary">${item.new_qty}</strong> ${item.uom || ''}`;
            } else if (item.event === 'removed') {
                detail = `Qty: <strong>${item.qty}</strong> ${item.uom || ''}`;
            }
            html += `<li><span class="badge ${badge} me-1">${ucFirst(item.event)}</span> ${item.product_name || ''} — ${detail}</li>`;
        });
        html += `</ul>`;
    } else if (logType === 'vendor_awarded' && meta.po_number) {
        html = `<ul class="mt-2 mb-2 ps-3 small">
            <li>PO: <a href="/purchase/orders/${meta.po_id}/" class="text-primary">${meta.po_number}</a></li>
        </ul>`;
    } else if (logType === 'rfq_sent') {
        html = `<ul class="mt-2 mb-2 ps-3 small">`;
        if (meta.from)    html += `<li>From: <strong>${meta.from}</strong></li>`;
        if (meta.to)      html += `<li>To: <strong>${meta.to}</strong></li>`;
        if (meta.cc)      html += `<li>CC: <strong>${meta.cc}</strong></li>`;
        if (meta.bcc)     html += `<li>BCC: <strong>${meta.bcc}</strong></li>`;
        if (meta.subject) html += `<li>Subject: <strong>${meta.subject}</strong></li>`;
        if (!meta.to && Array.isArray(meta.vendors) && meta.vendors.length > 0) {
            meta.vendors.forEach(function(v) { html += `<li>${v.vendor_name}</li>`; });
        }
        html += `</ul>`;
        if (Array.isArray(meta.attachments) && meta.attachments.length > 0) {
            const links = meta.attachments.map(a => {
                const icon = a.is_image ? 'bx-image' : 'bx-file';
                const size = a.file_size > 1048576 ? (a.file_size / 1048576).toFixed(1) + ' MB' : Math.round(a.file_size / 1024) + ' KB';
                return `<a href="javascript:void(0);" onclick="downloadAttachment('${a.download_url}', '${a.original_name.replace(/'/g, "\\'")}')"
                           class="d-flex align-items-center gap-1 text-muted small text-decoration-none py-1" title="${a.original_name}">
                            <i class="bx ${icon} fs-6 flex-shrink-0"></i>
                            <span class="text-truncate" style="max-width:180px;">${a.original_name}</span>
                            <span class="flex-shrink-0 ms-1 opacity-75">(${size})</span>
                        </a>`;
            }).join('');
            html += `<div class="border rounded px-2 py-1 mt-1 bg-light">${links}</div>`;
        }
    }

    return html;
};

const renderPurchaseInquiryHistory = function(history) {
    const container = document.getElementById('piHistoryTimeline');
    if (!container) return;

    container.innerHTML = '';

    if (!Array.isArray(history) || history.length === 0) {
        container.innerHTML = `<li class="timeline-item timeline-item-transparent">
            <div class="timeline-event text-muted">No history available</div>
        </li>`;
        return;
    }

    history.forEach(function(item) {
        const logType    = item.log_type || '';
        const itemMeta   = item.meta || {};
        const performedBy = item.created_by_name || 'System';
        const dateTime   = formatMySqlDate(item.created_at);

        container.insertAdjacentHTML('beforeend', `
            <li class="timeline-item timeline-item-transparent border-dashed">
                <span class="timeline-point timeline-point-info"></span>
                <div class="timeline-event">
                    <div class="timeline-header mb-1">
                        <h6 class="mb-0">${item.title || ''}</h6>
                        <small class="text-body-secondary">${performedBy}</small>
                    </div>
                    ${renderPiHistoryItemMeta(logType, itemMeta)}
                    <div class="small text-muted mb-1">
                        <div>${dateTime}</div>
                    </div>
                </div>
            </li>
        `);
    });
};

// ==========================================
// Action buttons
// ==========================================
const piActionHandlers = {
    edit: function() { openPurchaseInquiryFormDrawer(PI_ID); },
    'pdf-download': function() { window.location.href = `/purchase/inquiries/${PI_ID}/pdf?mode=download`; },
    'send-to-vendor': function() { openSendToVendorModal(); },
    cancel: function() {
        showConfirmation(
            'Cancel this inquiry? This action cannot be undone.',
            'warning',
            {
                text: 'Yes, Cancel',
                callback: async function() {
                    try {
                        await api.post(`/purchase/inquiries/${PI_ID}/cancel`);
                        notyf.success('Inquiry cancelled');
                        loadPiData();
                        refreshPurchaseInquiryHistory();
                    } catch (err) {
                        handleApiError(err);
                    }
                }
            },
            { text: 'Keep' }
        );
    },
};

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.pi-action-btn');
    if (!btn) return;
    const action = btn.dataset.action;
    if (piActionHandlers[action]) piActionHandlers[action]();
});

// ==========================================
// Vendor quote actions
// ==========================================
const withdrawQuote = function(ivId) {
    showConfirmation(
        'Withdraw this vendor\'s quote? Their status will revert to Sent.',
        'warning',
        {
            text: 'Withdraw',
            callback: async function() {
                try {
                    await api.post(`/purchase/inquiries/${PI_ID}/vendors/${ivId}/withdraw`);
                    notyf.success('Quote withdrawn');
                    loadPiData();
                    refreshPurchaseInquiryHistory();
                } catch (err) {
                    handleApiError(err);
                }
            }
        },
        { text: 'Cancel' }
    );
};

const awardVendor = function(ivId) {
    showConfirmation(
        'Award this vendor? A draft purchase order will be created. Other vendors will be rejected.',
        'warning',
        {
            text: 'Award',
            callback: async function() {
                try {
                    const res = await api.post(`/purchase/inquiries/${PI_ID}/award`, { inquiry_vendor_id: ivId });
                    const poId = res.data?.data?.po_id;
                    notyf.success('Inquiry awarded — Purchase Order created');
                    refreshPurchaseInquiryHistory();
                    if (poId) {
                        setTimeout(function() { window.location.href = `/purchase/orders/${poId}`; }, 1200);
                    } else {
                        loadPiData();
                    }
                } catch (err) {
                    handleApiError(err);
                }
            }
        },
        { text: 'Cancel' }
    );
};

// ==========================================
// Send to Vendor — two-step flow
// ==========================================
let piStvVendorId = null;

let _piVendorSelectModal  = null;
let _piEmailComposerModal = null;
let _piJoditInstance      = null;
let _piEmailDefaultBody   = '';
let _piAttachedFiles      = [];

const openSendToVendorModal = async function(vendorId, skipToComposer) {
    piStvVendorId = vendorId || null;

    if (!piFormCtx) {
        try {
            const res = await api.get('/purchase/inquiries/form-context');
            piFormCtx = res.data.data || res.data;
        } catch (err) {
            notyf.error('Unable to load form context');
            return;
        }
    }

    if (skipToComposer && vendorId) {
        // Resend from table row — skip vendor select, open composer directly
        await openPiEmailComposer(vendorId);
        return;
    }

    // Build vendor dropdown, excluding awarded/rejected vendors
    const excludedStatuses  = ['awarded', 'rejected'];
    const existingVendorMap = {};
    (piData.vendors || []).forEach(function(v) { existingVendorMap[v.vendor_id] = v; });

    const sel = document.getElementById('piStvVendorSelect');
    sel.innerHTML = '<option value="">Select vendor...</option>';
    (piFormCtx.vendors || []).forEach(function(v) {
        const existing = existingVendorMap[v.id];
        if (existing && excludedStatuses.includes(existing.status)) return;
        const opt = document.createElement('option');
        opt.value       = v.id;
        opt.textContent = v.name;
        sel.appendChild(opt);
    });

    initSelect2('#piStvVendorSelect', {
        dropdownParent: document.getElementById('piVendorSelectModal'),
        placeholder:    'Select vendor',
        width:          '100%',
    });

    document.getElementById('piStvResendWarning').classList.add('d-none');
    _piVendorSelectModal.show();
};

// Watch vendor select for resend warning
jQuery(document).on('change', '#piStvVendorSelect', function() {
    const vendorId = parseInt(this.value) || 0;
    const warning  = document.getElementById('piStvResendWarning');
    if (!vendorId) { warning.classList.add('d-none'); return; }
    const existing = (piData.vendors || []).find(function(v) { return v.vendor_id == vendorId; });
    if (existing && ['sent', 'responded'].includes(existing.status)) {
        warning.classList.remove('d-none');
    } else {
        warning.classList.add('d-none');
    }
});

const renderPiEmailAttachmentChips = function() {
    const container = document.getElementById('piEmailAttachmentsList');
    container.innerHTML = '';
    _piAttachedFiles.forEach(function(file, index) {
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

const loadPiEmailComposerData = async function(vendorId) {
    cleanFormInputFeedback(document.getElementById('piEmailComposerForm'));
    document.getElementById('piEmailTo').value      = '';
    document.getElementById('piEmailCc').value      = '';
    document.getElementById('piEmailBcc').value     = '';
    document.getElementById('piEmailSubject').value = '';
    _piEmailDefaultBody = '';
    _piAttachedFiles    = [];
    renderPiEmailAttachmentChips();

    // Show vendor name in composer header
    const vendorObj  = (piFormCtx && piFormCtx.vendors || []).find(function(v) { return v.id == vendorId; });
    const vendorName = vendorObj ? vendorObj.name : '';
    document.getElementById('piEmailComposerVendorName').textContent = vendorName ? 'Vendor: ' + vendorName : '';

    try {
        const res      = await api.get(`/purchase/inquiries/${PI_ID}/email-defaults?vendor_id=${vendorId}`);
        const defaults = res.data.data || res.data;
        document.getElementById('piEmailTo').value      = defaults.to      || '';
        document.getElementById('piEmailCc').value      = defaults.cc      || '';
        document.getElementById('piEmailBcc').value     = defaults.bcc     || '';
        document.getElementById('piEmailSubject').value = defaults.subject  || '';
        _piEmailDefaultBody = defaults.body || '';
        if (defaults.attachment) {
            _piAttachedFiles = [defaults.attachment];
            renderPiEmailAttachmentChips();
        }
    } catch (err) {
        // Silently fail — user can fill fields manually
    }
};

const openPiEmailComposer = async function(vendorId) {
    await loadPiEmailComposerData(vendorId);
    _piEmailComposerModal.show();
};

const handlePiSendEmail = async function() {
    const sendBtn = document.getElementById('piSendEmailSubmitBtn');
    const form    = document.getElementById('piEmailComposerForm');

    cleanFormInputFeedback(form);

    const to      = document.getElementById('piEmailTo').value.trim();
    const cc      = document.getElementById('piEmailCc').value.trim();
    const bcc     = document.getElementById('piEmailBcc').value.trim();
    const subject = document.getElementById('piEmailSubject').value.trim();
    const body    = _piJoditInstance ? _piJoditInstance.value : '';

    setButtonLoading(sendBtn, true);
    try {
        await api.post(`/purchase/inquiries/${PI_ID}/send-to-vendor`, {
            vendor_id: piStvVendorId,
            to, subject, body, cc, bcc,
            attachments: _piAttachedFiles,
        });
        notyf.success('Email sent successfully');
        _piEmailComposerModal.hide();
        loadPiData();
        refreshPurchaseInquiryHistory();
    } catch (err) {
        handleApiError(err, form);
    } finally {
        setButtonLoading(sendBtn, false);
    }
};

// ==========================================
// Price Entry Drawer
// ==========================================
const openPriceDrawer = async function(ivId, readOnly) {
    readOnly = !!readOnly;

    if (!piFormCtx) {
        const res = await api.get('/purchase/inquiries/form-context');
        piFormCtx = res.data.data || res.data;
    }

    const vendor = (piData.vendors || []).find(function(v) { return v.id == ivId; });
    document.getElementById('piPriceDrawerTitle').textContent = (readOnly ? 'Prices: ' : 'Enter Prices — ') + (vendor ? vendor.vendor_name : '');
    document.getElementById('piPriceDrawerVendorId').value    = ivId;

    const form = document.getElementById('piPriceForm');
    form.reset();
    cleanFormInputFeedback(form);

    if (vendor && vendor.quote_id) {
        if (vendor.vendor_quote_number) form.querySelector('[name=vendor_quote_number]').value = vendor.vendor_quote_number;
        if (vendor.vendor_quote_date)   datePickerSetDate('#piQuoteDate', vendor.vendor_quote_date);
    }

    const ptSel = document.getElementById('piQuotePaymentTerm');
    ptSel.innerHTML = '<option value="">No specific terms</option>';
    (piFormCtx.paymentTerms || []).forEach(function(pt) {
        const opt = document.createElement('option');
        opt.value       = pt.id;
        opt.textContent = pt.name;
        ptSel.appendChild(opt);
    });
    initSelect2('#piQuotePaymentTerm', {
        dropdownParent: document.getElementById('piPriceDrawer'),
        placeholder:    'No specific terms',
        width:          '100%',
    });

    renderPriceDrawerItems(ivId);

    const saveBtn = document.getElementById('savePiPriceBtn');
    if (readOnly) {
        saveBtn.style.display = 'none';
        form.querySelectorAll('input,textarea,select').forEach(function(el) { el.disabled = true; });
    } else {
        saveBtn.style.display = '';
        form.querySelectorAll('input,textarea,select').forEach(function(el) { el.disabled = false; });
    }

    new bootstrap.Offcanvas(document.getElementById('piPriceDrawer')).show();
};

const renderPriceDrawerItems = function(ivId) {
    const items = piData.items || [];
    const tbody = document.getElementById('piPriceItemsBody');
    tbody.innerHTML = '';

    items.forEach(function(item) {
        const tr = document.createElement('tr');
        tr.setAttribute('data-item-id', item.id);
        tr.innerHTML = `
            <td>
                <div class="fw-medium small">${item.product_name}</div>
                <div class="text-muted" style="font-size:0.75rem;">${item.required_qty} ${item.uom_code}</div>
                <input type="hidden" name="items[${item.id}][inquiry_item_id]" value="${item.id}" />
            </td>
            <td class="text-center">
                <div class="form-check form-switch d-flex justify-content-center mb-0">
                    <input class="form-check-input pi-can-supply-toggle" type="checkbox" name="items[${item.id}][can_supply]" value="1" checked data-item-id="${item.id}" />
                </div>
            </td>
            <td>
                <input type="number" class="form-control form-control-sm text-end pi-unit-price"
                       name="items[${item.id}][unit_price]" value="" step="any" min="0"
                       data-qty="${item.required_qty}" data-item-id="${item.id}" placeholder="0.00" />
            </td>
            <td class="text-end">
                <span class="pi-line-total text-muted small" data-item-id="${item.id}">—</span>
            </td>
            <td>
                <input type="text" class="form-control form-control-sm" name="items[${item.id}][notes]" placeholder="Notes" />
            </td>
        `;
        tbody.appendChild(tr);
    });

    tbody.querySelectorAll('.pi-unit-price').forEach(function(input) {
        input.addEventListener('input', function() {
            const qty     = parseFloat(this.dataset.qty) || 0;
            const price   = parseFloat(this.value) || 0;
            const itemId  = this.dataset.itemId;
            const totalEl = tbody.querySelector(`.pi-line-total[data-item-id="${itemId}"]`);
            if (totalEl) totalEl.textContent = qty * price > 0 ? formatCurrency(qty * price) : '—';
        });
    });

    tbody.querySelectorAll('.pi-can-supply-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            const itemId  = this.dataset.itemId;
            const priceEl = tbody.querySelector(`.pi-unit-price[data-item-id="${itemId}"]`);
            if (priceEl) {
                priceEl.disabled = !this.checked;
                if (!this.checked) {
                    priceEl.value = '';
                    const totalEl = tbody.querySelector(`.pi-line-total[data-item-id="${itemId}"]`);
                    if (totalEl) totalEl.textContent = 'Cannot Supply';
                }
            }
        });
    });
};

const savePiPrices = async function() {
    const form  = document.getElementById('piPriceForm');
    const btn   = document.getElementById('savePiPriceBtn');
    const ivId  = parseInt(document.getElementById('piPriceDrawerVendorId').value) || 0;

    cleanFormInputFeedback(form);
    setButtonLoading(btn, true);

    const headerData = {
        vendor_quote_number: form.querySelector('[name=vendor_quote_number]')?.value?.trim() || null,
        vendor_quote_date:   form.querySelector('[name=vendor_quote_date]')?.value || null,
        quote_validity_date: form.querySelector('[name=quote_validity_date]')?.value || null,
        payment_term_id:     parseInt(document.getElementById('piQuotePaymentTerm')?.value) || null,
        delivery_terms:      form.querySelector('[name=delivery_terms]')?.value?.trim() || null,
        lead_time_days:      parseInt(form.querySelector('[name=lead_time_days]')?.value) || null,
        freight_charges:     parseFloat(form.querySelector('[name=freight_charges]')?.value) || 0,
        other_charges_label: form.querySelector('[name=other_charges_label]')?.value?.trim() || null,
        other_charges:       parseFloat(form.querySelector('[name=other_charges]')?.value) || 0,
        vendor_quote_notes:  form.querySelector('[name=vendor_quote_notes]')?.value?.trim() || null,
    };

    const items = [];
    document.getElementById('piPriceItemsBody').querySelectorAll('tr[data-item-id]').forEach(function(tr) {
        const itemId = parseInt(tr.dataset.itemId);
        items.push({
            inquiry_item_id: itemId,
            can_supply:      tr.querySelector(`.pi-can-supply-toggle[data-item-id="${itemId}"]`)?.checked ? 1 : 0,
            unit_price:      parseFloat(tr.querySelector(`.pi-unit-price[data-item-id="${itemId}"]`)?.value) || 0,
            notes:           tr.querySelector(`[name="items[${itemId}][notes]"]`)?.value?.trim() || null,
        });
    });

    try {
        await api.post(`/purchase/inquiries/${PI_ID}/vendors/${ivId}/prices`, { header: headerData, items });
        notyf.success('Prices saved');

        bootstrap.Offcanvas.getInstance(document.getElementById('piPriceDrawer')).hide();
        loadPiData();
        refreshPurchaseInquiryHistory();

        if (PI_PRICE_COMPARISON) {
            const vendor = (piData.vendors || []).find(function(v) { return v.id == ivId; });
            if (vendor && vendor.status === 'sent') {
                showConfirmation(
                    'Mark this vendor as responded?',
                    'info',
                    {
                        text: 'Mark Responded',
                        callback: async function() {
                            try {
                                await api.post(`/purchase/inquiries/${PI_ID}/vendors/${ivId}/respond`);
                                notyf.success('Vendor marked as responded');
                                loadPiData();
                                refreshPurchaseInquiryHistory();
                            } catch (err) {
                                handleApiError(err);
                            }
                        }
                    },
                    { text: 'Not Yet' }
                );
            }
        }
    } catch (err) {
        handleApiError(err, form);
    } finally {
        setButtonLoading(btn, false);
    }
};

document.getElementById('savePiPriceBtn').addEventListener('click', savePiPrices);

// ==========================================
// Init
// ==========================================
document.addEventListener('DOMContentLoaded', function() {
    initDatePicker('#piQuoteDate');
    initDatePicker('#piQuoteValidityDate');
    loadPiData();
    refreshPurchaseInquiryHistory();

    // Modal instances
    _piVendorSelectModal  = new bootstrap.Modal(document.getElementById('piVendorSelectModal'), { backdrop: 'static', keyboard: false });
    _piEmailComposerModal = new bootstrap.Modal(document.getElementById('piEmailComposerModal'), { backdrop: 'static', keyboard: false, focus: false });

    // Step 1: Next button — load data, close vendor select, then open composer
    document.getElementById('piStvNextBtn').addEventListener('click', async function() {
        const vendorId = parseInt(jQuery('#piStvVendorSelect').val()) || 0;
        if (!vendorId) { notyf.error('Please select a vendor'); return; }
        piStvVendorId = vendorId;
        const btn = this;
        setButtonLoading(btn, true);

        // Pre-load all email data (including PDF attachment) while step-1 modal is still visible
        await loadPiEmailComposerData(vendorId);

        setButtonLoading(btn, false);
        _piVendorSelectModal.hide();

        // Wait for step-1 to fully hide before opening composer (avoids Bootstrap modal conflict)
        document.getElementById('piVendorSelectModal').addEventListener('hidden.bs.modal', function onHidden() {
            this.removeEventListener('hidden.bs.modal', onHidden);
            _piEmailComposerModal.show();
        });
    });

    // Step 2: Jodit init when composer modal opens
    document.getElementById('piEmailComposerModal').addEventListener('shown.bs.modal', function() {
        if (_piJoditInstance) { _piJoditInstance.destruct(); _piJoditInstance = null; }
        _piJoditInstance = Jodit.make('#piEmailBody', {
            height: 300,
            enter: 'BR',
            buttons: 'bold,italic,underline,strikethrough,|,ul,ol,|,paragraph,|,link,image',
            toolbarAdaptive: false,
            showCharsCounter: false,
            showWordsCounter: false,
            showXPathInStatusbar: false,
            addNewLine: false,
        });
        _piJoditInstance.value = _piEmailDefaultBody;
    });

    // Step 2: Attachments
    document.getElementById('piAttachFilesBtn').addEventListener('click', function() {
        document.getElementById('piEmailAttachments').click();
    });

    document.getElementById('piEmailAttachments').addEventListener('change', async function() {
        if (!this.files.length) return;
        const newFiles = await readFilesAsBase64(this);
        _piAttachedFiles.push(...newFiles);
        renderPiEmailAttachmentChips();
        this.value = '';
    });

    document.getElementById('piEmailAttachmentsList').addEventListener('click', function(e) {
        const btn = e.target.closest('[data-attach-index]');
        if (!btn) return;
        _piAttachedFiles.splice(parseInt(btn.dataset.attachIndex), 1);
        renderPiEmailAttachmentChips();
    });

    document.getElementById('piSendEmailSubmitBtn').addEventListener('click', handlePiSendEmail);

    const piQRCollapseEl   = document.getElementById('piQRCollapse');
    const piQRCollapseInst = new bootstrap.Collapse(piQRCollapseEl, { toggle: false });

    const piQRTabs  = document.querySelectorAll('#piQuoteRequestsCard .doc-tab');
    const piQRPanes = document.querySelectorAll('#piQuoteRequestsCard .tab-pane');
    let piQRDefaultTab = piQRTabs[0];

    function deactivatePiQRTabs() {
        piQRTabs.forEach(function(t) { t.classList.remove('active'); });
        piQRPanes.forEach(function(p) { p.classList.remove('show', 'active'); });
    }

    function activatePiQRTab(tab) {
        deactivatePiQRTabs();
        tab.classList.add('active');
        document.querySelector(tab.dataset.bsTarget).classList.add('show', 'active');
    }

    piQRTabs.forEach(function(tab) {
        tab.addEventListener('click', function() {
            piQRDefaultTab = this;
            activatePiQRTab(this);
            if (!piQRCollapseEl.classList.contains('show')) {
                piQRCollapseInst.show();
            }
        });
    });

    piQRCollapseEl.addEventListener('shown.bs.collapse', function() {
        activatePiQRTab(piQRDefaultTab);
    });

    piQRCollapseEl.addEventListener('hidden.bs.collapse', function() {
        piQRDefaultTab = piQRTabs[0];
        deactivatePiQRTabs();
    });

    document.querySelector('.pi-qr-accordion-toggle').addEventListener('click', function() {
        piQRCollapseInst.toggle();
        this.querySelector('i').classList.toggle('bx-chevron-up');
        this.querySelector('i').classList.toggle('bx-chevron-down');
    });
});
</script>
@endpush
