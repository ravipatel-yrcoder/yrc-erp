@extends('layouts.app')
@section('title', 'Proforma Invoice')

@section('content')

<?php $tenantContext = tenantContext(); ?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Proforma Invoice <span class="text-muted fw-normal fs-5" id="pfDocCode"></span></h4>
    </div>

    <div id="pfActionButtons"></div>

    <div class="row g-4">
        <div class="col-lg-8">

            <!-- Outdated warning -->
            <div class="alert alert-warning d-none mb-4" id="pfOutdatedBanner" role="alert">
                <i class="bx bx-error-circle me-1"></i>
                <strong>Outdated:</strong> The Sales Order was amended after this proforma was created. Please review before sending.
            </div>

            <div class="card" id="pfDetailsCard">
                <div class="card-body">

                    <div class="d-flex justify-content-end mb-4">
                        <div class="d-flex gap-2" id="pfBadges"></div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <h6 class="mb-0">Sales Order</h6>
                            <p class="mb-0"><a id="pfSoLink" href="#" class="text-primary fw-medium">-</a></p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Customer</h6>
                            <p class="mb-0" id="pfCustomer">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Proforma Date</h6>
                            <p class="mb-0" id="pfDate">-</p>
                        </div>
                        <div class="col-md-4" id="pfValidUntilRow">
                            <h6 class="mb-0">Valid Until</h6>
                            <p class="mb-0" id="pfValidUntil">-</p>
                        </div>
                        <div class="col-md-4" id="pfPaymentTermsRow">
                            <h6 class="mb-0">Payment Terms</h6>
                            <p class="mb-0" id="pfPaymentTerms">-</p>
                        </div>
                        <div class="col-md-4 d-none" id="pfPlaceOfSupplyRow">
                            <h6 class="mb-0">Place of Supply</h6>
                            <p class="mb-0" id="pfPlaceOfSupplyMeta">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Created By</h6>
                            <p class="mb-0" id="pfCreatedBy">-</p>
                        </div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <h6 class="mb-2">Bill To</h6>
                            <div id="pfBillingAddress" class="text-muted small">-</div>
                        </div>
                        <div class="col-md-6" id="pfShippingRow">
                            <h6 class="mb-2">Ship To</h6>
                            <div id="pfShippingAddress" class="text-muted small">-</div>
                        </div>
                    </div>

                    <div class="mb-4 d-none" id="pfNotesRow">
                        <h6 class="mb-0">Notes</h6>
                        <p class="mb-0" id="pfNotes">-</p>
                    </div>

                    <div class="mb-4 d-none" id="pfTermsRow">
                        <div class="d-flex align-items-center gap-1 mb-1" role="button"
                             data-bs-toggle="collapse" data-bs-target="#pfTermsContent" aria-expanded="false">
                            <h6 class="mb-0">Terms &amp; Conditions</h6>
                            <i class="bx bx-chevron-down fs-5 text-secondary"></i>
                        </div>
                        <div class="collapse" id="pfTermsContent">
                            <div class="small" id="pfTerms"></div>
                        </div>
                    </div>

                    <div class="mb-4 d-none" id="pfDeclarationRow">
                        <div class="d-flex align-items-center gap-1 mb-1" role="button"
                             data-bs-toggle="collapse" data-bs-target="#pfDeclarationContent" aria-expanded="false">
                            <h6 class="mb-0">Declaration</h6>
                            <i class="bx bx-chevron-down fs-5 text-secondary"></i>
                        </div>
                        <div class="collapse" id="pfDeclarationContent">
                            <div class="small" id="pfDeclarationBody"></div>
                        </div>
                    </div>

                    <div class="table-responsive border border-bottom-0 border-top-0 rounded mb-4">
                        <table class="table m-0" id="pfItemsTable">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Item</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody><tr><td colspan="7" class="text-center text-muted py-3">Loading…</td></tr></tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end">
                        <table class="table table-borderless w-auto mb-0" id="pfTotalsTable">
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
                                    <th class="ps-0 w-px-300" id="pfGrandTotalLabel">Total</th>
                                    <td class="px-0 text-end fw-bold" id="pfGrandTotal">-</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div id="pfRcmTotalsNote" class="d-none mt-2">
                        <small class="text-primary justify-content-end d-flex align-items-center"><i class="bx bx-info-circle me-1"></i>GST payable under Reverse Charge by the recipient directly to the government.</small>
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
                    <!-- Attachment chips displayed below editor -->
                    <div id="pfEmailAttachmentsList" class="d-flex flex-wrap gap-2 mt-2"></div>
                    <!-- Hidden file input -->
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
let _pfData            = null;
let _pfJoditInstance   = null;
let _pfDefaultBody     = '';
let _pfAttachedFiles   = [];
let _pfEmailModal      = null;

const pfStatusMap = {
    draft:     ['Draft',     'warning'],
    sent:      ['Sent',      'success'],
    cancelled: ['Cancelled', 'danger'],
};

const renderPfAddress = (addr) => {
    if (!addr || !Object.keys(addr).length) return '-';
    const parts = [addr.attention, addr.address_line1, addr.address_line2, addr.city, addr.state, addr.postal_code, addr.country].filter(Boolean);
    return parts.join(', ');
};

const renderPfDetails = (pf) => {
    _pfData = pf;

    document.title = `Proforma Invoice — ${pf.proforma_number}`;
    document.getElementById('pfDocCode').textContent = pf.proforma_number ? `— #${pf.proforma_number}` : '';

    const soLink = document.getElementById('pfSoLink');
    soLink.textContent = pf.so_number || '-';
    soLink.href = `/sales/orders/${pf.sales_order_id}/`;

    document.getElementById('pfCustomer').textContent     = pf.customer_name || '-';
    document.getElementById('pfDate').textContent         = formatMySqlDate(pf.proforma_date);
    document.getElementById('pfCreatedBy').textContent    = pf.created_by_name || '-';

    const validUntilEl = document.getElementById('pfValidUntil');
    validUntilEl.textContent = pf.valid_until ? formatMySqlDate(pf.valid_until) : '-';

    const paymentEl = document.getElementById('pfPaymentTerms');
    paymentEl.textContent = pf.payment_terms || '-';

    // Outdated banner
    document.getElementById('pfOutdatedBanner').classList.toggle('d-none', !pf.is_outdated);

    // Status badge
    const badgeWrap = document.getElementById('pfBadges');
    badgeWrap.innerHTML = '';
    if (pf.is_outdated) {
        badgeWrap.insertAdjacentHTML('beforeend', `<span class="badge bg-label-warning">Outdated</span>`);
    }
    if (pf.valid_until && !['cancelled'].includes(pf.status)) {
        const validUntilDate = new Date(pf.valid_until);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        if (validUntilDate < today) {
            badgeWrap.insertAdjacentHTML('beforeend', `<span class="badge bg-label-danger">Expired</span>`);
        }
    }
    const s = pfStatusMap[pf.status] || [pf.status, 'secondary'];
    badgeWrap.insertAdjacentHTML('beforeend', `<span class="badge bg-label-${s[1]}">${s[0]}</span>`);

    // Addresses
    document.getElementById('pfBillingAddress').textContent  = renderPfAddress(pf.billing_address);
    document.getElementById('pfShippingAddress').textContent = renderPfAddress(pf.shipping_address);

    // Notes
    const notesRow = document.getElementById('pfNotesRow');
    if (pf.notes) {
        document.getElementById('pfNotes').textContent = pf.notes;
        notesRow.classList.remove('d-none');
    } else {
        notesRow.classList.add('d-none');
    }

    // Terms & Conditions
    const termsRow = document.getElementById('pfTermsRow');
    if (pf.invoice_terms && pf.invoice_terms.replace(/<[^>]*>/g, '').trim() !== '') {
        document.getElementById('pfTerms').innerHTML = pf.invoice_terms;
        termsRow.classList.remove('d-none');
    } else {
        termsRow.classList.add('d-none');
    }

    // Declaration
    const declRow = document.getElementById('pfDeclarationRow');
    if (pf.invoice_declaration && pf.invoice_declaration.replace(/<[^>]*>/g, '').trim() !== '') {
        document.getElementById('pfDeclarationBody').innerHTML = pf.invoice_declaration;
        declRow.classList.remove('d-none');
    } else {
        declRow.classList.add('d-none');
    }

    // Items table
    const tbody = document.querySelector('#pfItemsTable tbody');
    if (!pf.items || !pf.items.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">No items</td></tr>`;
    } else {
        const isRcmItems = !!pf.reverse_charge;
        let html = '';
        pf.items.forEach((item, i) => {
            const discAmt  = parseFloat(item.discount_amount || 0);
            const taxArr   = Array.isArray(item.tax_info) ? item.tax_info : [];
            const taxLabel = taxArr.map(t => t.name).filter(Boolean).join(', ') || '—';
            const lineAmt  = isRcmItems ? item.taxable_amount : item.line_total;
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

    // Place of Supply
    const posRow = document.getElementById('pfPlaceOfSupplyRow');
    if (pf.place_of_supply_name) {
        document.getElementById('pfPlaceOfSupplyMeta').textContent =
            pf.place_of_supply_name + (pf.place_of_supply_code ? ' (' + pf.place_of_supply_code + ')' : '');
        posRow.classList.remove('d-none');
    } else {
        posRow.classList.add('d-none');
    }

    // Totals
    document.getElementById('pfSubtotal').textContent = formatCurrency(pf.subtotal);

    // When RCM: supplier does not collect GST — amount payable = grand_total minus tax
    const isRcm    = !!pf.reverse_charge;
    const taxAmt   = parseFloat(pf.tax_amount || 0);
    const displayTotal = isRcm ? (parseFloat(pf.grand_total) - taxAmt) : parseFloat(pf.grand_total);
    document.getElementById('pfGrandTotal').textContent = formatCurrency(displayTotal);

    const gtLabel = document.getElementById('pfGrandTotalLabel');
    if (gtLabel) gtLabel.textContent = 'Total';

    const rcmNote = document.getElementById('pfRcmTotalsNote');
    if (rcmNote) rcmNote.classList.toggle('d-none', !isRcm);

    // GST breakdown rows — replace Tax row with component breakdown when available
    const gstGroup = document.getElementById('pfGstRowsGroup');
    if (pf.gst_summary && pf.gst_summary.rows && pf.gst_summary.rows.length) {
        const gs  = pf.gst_summary;
        const rcm = isRcm ? ' <span class="badge p-1 fs-tiny bg-label-primary">RCM</span>' : '';
        let html = '';
        // Only show Taxable Amount when it differs from Subtotal (i.e. there's an order-level discount reducing the tax base)
        if (gs.totals.taxable_amount > 0 && Math.abs(gs.totals.taxable_amount - parseFloat(pf.subtotal)) > 0.001) {
            html += `<tr><th class="ps-0 text-muted fw-normal w-px-300">Taxable Amount</th><td class="px-0 text-end">${formatCurrency(gs.totals.taxable_amount)}</td></tr>`;
        }
        if (gs.is_intra_state) {
            if (gs.totals.cgst_amount > 0) {
                html += `<tr><th class="ps-0 fw-normal w-px-300">CGST${rcm}</th><td class="px-0 text-end">${formatCurrency(gs.totals.cgst_amount)}</td></tr>`;
            }
            if (gs.use_ugst && gs.totals.ugst_amount > 0) {
                html += `<tr><th class="ps-0 fw-normal w-px-300">UGST${rcm}</th><td class="px-0 text-end">${formatCurrency(gs.totals.ugst_amount)}</td></tr>`;
            } else if (!gs.use_ugst && gs.totals.sgst_amount > 0) {
                html += `<tr><th class="ps-0 fw-normal w-px-300">SGST${rcm}</th><td class="px-0 text-end">${formatCurrency(gs.totals.sgst_amount)}</td></tr>`;
            }
        } else {
            if (gs.totals.igst_amount > 0) {
                html += `<tr><th class="ps-0 fw-normal w-px-300">IGST${rcm}</th><td class="px-0 text-end">${formatCurrency(gs.totals.igst_amount)}</td></tr>`;
            }
        }
        if (gs.totals.cess_amount > 0) {
            html += `<tr><th class="ps-0 fw-normal w-px-300">CESS</th><td class="px-0 text-end">${formatCurrency(gs.totals.cess_amount)}</td></tr>`;
        }
        gstGroup.innerHTML = html;
    } else {
        gstGroup.innerHTML = `<tr><th class="ps-0 text-muted w-px-300">Tax</th><td class="px-0 text-end">${formatCurrency(pf.tax_amount)}</td></tr>`;
    }

    const itemDiscTotal  = parseFloat(pf.item_discount_total  || 0);
    const orderDiscTotal = parseFloat(pf.order_discount_amount || 0);
    const itemDiscRow    = document.getElementById('pfItemDiscRow');
    const orderDiscRow   = document.getElementById('pfOrderDiscRow');
    if (itemDiscTotal > 0) {
        document.getElementById('pfItemDisc').textContent = '- ' + formatCurrency(itemDiscTotal);
        itemDiscRow.classList.remove('d-none');
    } else {
        itemDiscRow.classList.add('d-none');
    }
    if (orderDiscTotal > 0) {
        document.getElementById('pfOrderDisc').textContent = '- ' + formatCurrency(orderDiscTotal);
        orderDiscRow.classList.remove('d-none');
    } else {
        orderDiscRow.classList.add('d-none');
    }

    const roundOff    = parseFloat(pf.round_off_amount || 0);
    const roundOffRow = document.getElementById('pfRoundOffRow');
    if (roundOff !== 0) {
        document.getElementById('pfRoundOff').textContent = (roundOff < 0 ? '− ' : '+ ') + formatCurrency(Math.abs(roundOff));
        roundOffRow.classList.remove('d-none');
    } else {
        roundOffRow.classList.add('d-none');
    }

    const adjAmt = parseFloat(pf.adjustment_amount || 0);
    const adjRow = document.getElementById('pfAdjustmentRow');
    if (adjAmt !== 0) {
        document.getElementById('pfAdjustmentLabel').textContent = pf.adjustment_label || 'Adjustment';
        document.getElementById('pfAdjustment').textContent = formatCurrency(adjAmt);
        adjRow.classList.remove('d-none');
    } else {
        adjRow.classList.add('d-none');
    }

    // Action buttons
    renderActionButtons(pf);

    // Timeline
    renderTimeline(pf.history || []);
};

const renderActionButtons = (pf) => {
    let leftBtns  = '';
    let rightBtns = '';

    @if($tenantContext->canDo('proforma_invoices', 'cancel'))
    if (pf.status === 'draft' || pf.status === 'sent') {
        leftBtns += `<button type="button" class="btn btn-sm btn-danger" onclick="cancelProforma()">
            <i class="bx bx-x me-1"></i> Cancel
        </button>`;
    }
    @endif

    @if($tenantContext->canDo('proforma_invoices', 'send_email'))
    if (pf.status === 'draft') {
        rightBtns += `<button type="button" class="btn btn-sm btn-outline-success" onclick="markProformaAsSent()">
            <i class="bx bx-check me-1"></i> Mark as Sent
        </button>`;
    }
    if (pf.status !== 'cancelled') {
        rightBtns += `<button type="button" class="btn btn-sm btn-outline-primary" onclick="openEmailModal()">
            <i class="bx bx-envelope me-1"></i> Send
        </button>`;
    }
    @endif

    rightBtns += `<a href="/sales/proforma-invoices/${pf.id}/pdf/?mode=download" class="btn btn-sm btn-outline-secondary">
        <i class="bx bx-download me-1"></i> Download
    </a>`;

    document.getElementById('pfActionButtons').innerHTML = `<div class="row"><div class="col-lg-8"><div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">${leftBtns}</div>
        <div class="d-flex gap-2">${rightBtns}</div>
    </div></div></div>`;
};

const buildPfAttachmentList = (attachments) => {
    if (!attachments || !attachments.length) return '';
    const links = attachments.map(a => {
        const name = a.original_name || a.name || a.filename || 'attachment';
        const icon = a.is_image ? 'bx-image' : 'bx-file';
        const size = a.file_size > 1048576 ? (a.file_size / 1048576).toFixed(1) + ' MB' : Math.round(a.file_size / 1024) + ' KB';
        return `<a href="javascript:void(0);" onclick="downloadAttachment('${a.download_url}', '${name.replace(/'/g, "\\'")}')"
                   class="d-flex align-items-center gap-1 text-muted small text-decoration-none py-1"
                   title="${name}">
                    <i class="bx ${icon} fs-6 flex-shrink-0"></i>
                    <span class="text-truncate" style="max-width:180px;">${name}</span>
                    <span class="text-muted" style="white-space:nowrap;">(${size})</span>
                </a>`;
    }).join('');
    return `<div class="border rounded px-2 py-1 mt-1 bg-light">${links}</div>`;
};

const renderTimeline = (history) => {
    const ul = document.getElementById('pfHistoryTimeline');
    if (!history || !history.length) {
        ul.innerHTML = `<li class="timeline-item timeline-item-transparent"><div class="timeline-event text-muted">No history available</div></li>`;
        return;
    }

    let html = '';
    history.forEach(h => {
        let metaHtml = '';
        if (h.log_type === 'sent' && h.meta) {
            metaHtml += '<ul class="mt-2 mb-2 ps-3 small">';
            if (h.meta.to)      metaHtml += `<li>To: <strong class="text-primary">${h.meta.to}</strong></li>`;
            if (h.meta.cc)      metaHtml += `<li>CC: <strong class="text-primary">${h.meta.cc}</strong></li>`;
            if (h.meta.subject) metaHtml += `<li>Subject: <strong class="text-primary">${h.meta.subject}</strong></li>`;
            metaHtml += '</ul>';
            metaHtml += buildPfAttachmentList(h.meta.attachments || []);
        }
        html += `<li class="timeline-item timeline-item-transparent border-dashed">
            <span class="timeline-point timeline-point-info"></span>
            <div class="timeline-event">
                <div class="timeline-header mb-1">
                    <h6 class="mb-0">${h.title}</h6>
                    <small class="text-body-secondary">${h.user_name || 'System'}</small>
                </div>
                ${metaHtml}
                <div class="small text-muted mb-1">
                    <div>${formatMySqlDate(h.created_at, window.sysDefaultConfig.dateTimeFormat)}</div>
                </div>
            </div>
        </li>`;
    });
    ul.innerHTML = html;
};

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
        if (attachment) {
            _pfAttachedFiles = [attachment];
            renderPfEmailAttachmentChips();
        }
    } catch (err) {
        notyf.error("Unable to load email defaults");
    }
};

const markProformaAsSent = () => {
    showConfirmation(
        "Mark this proforma as sent? Use this when you've shared it manually (print, WhatsApp, etc.).",
        "info",
        {
            text: "Yes, Mark as Sent",
            class: "btn-success",
            callback: async () => {
                try {
                    await api.post(`/sales/proforma-invoices/${pfId}/mark-sent`);
                    notyf.success("Proforma invoice marked as sent");
                    loadPf();
                } catch (err) {
                    handleApiError(err);
                }
            }
        },
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
    const note = (result.value || '').trim();
    try {
        await api.post(`/sales/proforma-invoices/${pfId}/cancel`, { note });
        notyf.success("Proforma invoice cancelled");
        loadPf();
    } catch (err) {
        handleApiError(err);
    }
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
        backdrop: 'static',
        keyboard: false,
        focus:    false,
    });

    // Init Jodit after modal finishes opening (so dimensions are correct)
    document.getElementById('pfEmailModal').addEventListener('shown.bs.modal', function() {
        if (_pfJoditInstance) { _pfJoditInstance.destruct(); _pfJoditInstance = null; }
        _pfJoditInstance = Jodit.make('#pfEmailBody', {
            height: 300,
            enter: 'BR',
            buttons: 'bold,italic,underline,strikethrough,|,ul,ol,|,paragraph,|,link,image',
            toolbarAdaptive: false,
            showCharsCounter: false,
            showWordsCounter: false,
            showXPathInStatusbar: false,
            addNewLine: false,
        });
        _pfJoditInstance.value = _pfDefaultBody;
    });

    document.getElementById('pfEmailSubmitBtn').addEventListener('click', handlePfSendEmail);

    // Paperclip button → trigger hidden file input
    document.getElementById('pfAttachFilesBtn').addEventListener('click', function() {
        document.getElementById('pfEmailAttachments').click();
    });

    // When files are selected, read as base64 and add chips
    document.getElementById('pfEmailAttachments').addEventListener('change', async function() {
        if (!this.files.length) return;
        const newFiles = await readFilesAsBase64(this);
        _pfAttachedFiles.push(...newFiles);
        renderPfEmailAttachmentChips();
        this.value = '';
    });

    // Remove chip on × click
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
