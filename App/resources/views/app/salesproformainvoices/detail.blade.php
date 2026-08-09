@extends('layouts.app')
@section('title', 'Proforma Invoice')

@section('content')

<?php $tenantContext = tenantContext(); ?>

<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Proforma Invoice <span class="text-muted fw-normal fs-5" id="pfDocCode"></span></h4>
    </div>

    <div id="pfActionButtons" class="mb-3 d-flex gap-2 flex-wrap"></div>

    <div class="row g-4">
        <div class="col-lg-8">

            <!-- Outdated warning -->
            <div class="alert alert-warning d-none mb-4" id="pfOutdatedBanner" role="alert">
                <i class="bx bx-error-circle me-1"></i>
                <strong>Outdated:</strong> The Sales Order was amended after this proforma was created. Please review before sending.
            </div>

            <div class="card" id="pfDetailsCard">
                <div class="card-body">

                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h5 class="mb-1" id="pfNumber">—</h5>
                            <div class="text-muted small">
                                SO: <a id="pfSoLink" href="#" class="text-primary">—</a>
                            </div>
                        </div>
                        <div class="d-flex gap-2" id="pfBadges"></div>
                    </div>

                    <div class="row g-3 mb-4">
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
                            <tr>
                                <th class="ps-0 text-muted w-px-300">Subtotal</th>
                                <td class="px-0 text-end" id="pfSubtotal">-</td>
                            </tr>
                            <tr class="d-none" id="pfDiscountRow">
                                <th class="ps-0 text-muted w-px-300">Discount</th>
                                <td class="px-0 text-end" id="pfDiscount">-</td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted w-px-300">Tax</th>
                                <td class="px-0 text-end" id="pfTax">-</td>
                            </tr>
                            <tr class="d-none" id="pfRoundOffRow">
                                <th class="ps-0 text-muted w-px-300">Round Off</th>
                                <td class="px-0 text-end" id="pfRoundOff">-</td>
                            </tr>
                            <tr class="border-top">
                                <th class="ps-0 w-px-300">Total</th>
                                <td class="px-0 text-end fw-bold" id="pfGrandTotal">-</td>
                            </tr>
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
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send Proforma Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="pfEmailForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label">To <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="pfEmailTo" name="to" placeholder="recipient@example.com" />
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label">CC <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="email" class="form-control" id="pfEmailCc" name="cc" placeholder="cc@example.com" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">BCC <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="email" class="form-control" id="pfEmailBcc" name="bcc" placeholder="bcc@example.com" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="pfEmailSubject" name="subject" />
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="pfEmailBody" name="body" rows="5"></textarea>
                    </div>
                    <div class="form-glob-feedback mt-2"></div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="pfEmailSubmitBtn">Send</button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
const pfId = {{ $proformaId }};
let _pfData = null;

const pfStatusMap = {
    draft:     ['Draft',     'warning'],
    sent:      ['Sent',      'success'],
    cancelled: ['Cancelled', 'secondary'],
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
    document.getElementById('pfNumber').textContent  = pf.proforma_number;

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

    // Items table
    const tbody = document.querySelector('#pfItemsTable tbody');
    if (!pf.items || !pf.items.length) {
        tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">No items</td></tr>`;
    } else {
        let html = '';
        pf.items.forEach((item, i) => {
            const discAmt = parseFloat(item.discount_amount || 0);
            const taxArr  = Array.isArray(item.tax_info) ? item.tax_info : [];
            const taxLabel = taxArr.map(t => t.name).filter(Boolean).join(', ') || '—';
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
                <td class="text-end fw-medium">${formatCurrency(item.line_total)}</td>
            </tr>`;
        });
        tbody.innerHTML = html;
    }

    // Totals
    document.getElementById('pfSubtotal').textContent   = formatCurrency(pf.subtotal);
    document.getElementById('pfTax').textContent        = formatCurrency(pf.tax_amount);
    document.getElementById('pfGrandTotal').textContent = formatCurrency(pf.grand_total);

    const discTotal = parseFloat(pf.discount_total || 0);
    const discRow   = document.getElementById('pfDiscountRow');
    if (discTotal > 0) {
        document.getElementById('pfDiscount').textContent = '- ' + formatCurrency(discTotal);
        discRow.classList.remove('d-none');
    } else {
        discRow.classList.add('d-none');
    }

    const roundOff    = parseFloat(pf.round_off_amount || 0);
    const roundOffRow = document.getElementById('pfRoundOffRow');
    if (roundOff !== 0) {
        document.getElementById('pfRoundOff').textContent = (roundOff < 0 ? '− ' : '+ ') + formatCurrency(Math.abs(roundOff));
        roundOffRow.classList.remove('d-none');
    } else {
        roundOffRow.classList.add('d-none');
    }

    // Action buttons
    renderActionButtons(pf);

    // Timeline
    renderTimeline(pf.history || []);
};

const renderActionButtons = (pf) => {
    const wrap = document.getElementById('pfActionButtons');
    wrap.innerHTML = '';

    if (pf.status !== 'cancelled') {
        @if($tenantContext->canDo('proforma_invoices', 'send_email'))
        wrap.insertAdjacentHTML('beforeend',
            `<button type="button" class="btn btn-sm btn-primary" onclick="openEmailModal()">
                <i class="bx bx-send me-1"></i> Send Email
            </button>`
        );
        @endif

        wrap.insertAdjacentHTML('beforeend',
            `<a href="/sales/proforma-invoices/${pf.id}/pdf/?mode=inline" target="_blank" class="btn btn-sm btn-outline-secondary">
                <i class="bx bx-file me-1"></i> View PDF
            </a>`
        );
        wrap.insertAdjacentHTML('beforeend',
            `<a href="/sales/proforma-invoices/${pf.id}/pdf/?mode=download" class="btn btn-sm btn-outline-secondary">
                <i class="bx bx-download me-1"></i> Download PDF
            </a>`
        );

        @if($tenantContext->canDo('proforma_invoices', 'cancel'))
        if (pf.status === 'draft' || pf.status === 'sent') {
            wrap.insertAdjacentHTML('beforeend',
                `<button type="button" class="btn btn-sm btn-outline-danger ms-auto" onclick="cancelProforma()">
                    <i class="bx bx-x me-1"></i> Cancel
                </button>`
            );
        }
        @endif
    }
};

const renderTimeline = (history) => {
    const ul = document.getElementById('pfHistoryTimeline');
    if (!history || !history.length) {
        ul.innerHTML = `<li class="timeline-item timeline-item-transparent"><div class="timeline-event text-muted">No history available</div></li>`;
        return;
    }

    const iconMap = {
        created:   ['bx-plus-circle', 'success'],
        sent:      ['bx-send',        'primary'],
        cancelled: ['bx-x-circle',    'danger'],
        outdated:  ['bx-error-circle','warning'],
    };

    let html = '';
    history.forEach(h => {
        const [icon, color] = iconMap[h.log_type] || ['bx-history', 'secondary'];
        html += `<li class="timeline-item timeline-item-transparent">
            <span class="timeline-point timeline-point-${color}"><i class="bx ${icon}"></i></span>
            <div class="timeline-event">
                <div class="timeline-header">
                    <small class="text-muted">${formatMySqlDate(h.created_at, 'd MMM yyyy, hh:mm A')}</small>
                </div>
                <p class="mb-1 fw-medium">${h.title}</p>
                ${h.user_name ? `<small class="text-muted">${h.user_name}</small>` : ''}
            </div>
        </li>`;
    });
    ul.innerHTML = html;
};

const openEmailModal = () => {
    const pf = _pfData;
    document.getElementById('pfEmailSubject').value = `Proforma Invoice — ${pf.proforma_number}`;
    document.getElementById('pfEmailTo').value      = '';
    document.getElementById('pfEmailCc').value      = '';
    document.getElementById('pfEmailBcc').value     = '';
    document.getElementById('pfEmailBody').value    = '';
    cleanFormInputFeedback(document.getElementById('pfEmailForm'));
    const modal = new bootstrap.Modal(document.getElementById('pfEmailModal'));
    modal.show();
};

const cancelProforma = () => {
    showConfirmation(
        "Are you sure you want to cancel this proforma invoice? This cannot be undone.",
        "warning",
        {
            label: "Yes, Cancel",
            action: async () => {
                try {
                    await api.post(`/sales/proforma-invoices/${pfId}/cancel`);
                    notyf.success("Proforma invoice cancelled");
                    loadPf();
                } catch (err) {
                    handleApiError(err);
                }
            }
        },
        { label: "Keep" }
    );
};

document.getElementById('pfEmailSubmitBtn')?.addEventListener('click', async () => {
    const form    = document.getElementById('pfEmailForm');
    const payload = {
        to:      document.getElementById('pfEmailTo').value.trim(),
        cc:      document.getElementById('pfEmailCc').value.trim(),
        bcc:     document.getElementById('pfEmailBcc').value.trim(),
        subject: document.getElementById('pfEmailSubject').value.trim(),
        body:    document.getElementById('pfEmailBody').value.trim(),
    };

    cleanFormInputFeedback(form);
    try {
        await api.post(`/sales/proforma-invoices/${pfId}/send-email`, payload);
        bootstrap.Modal.getInstance(document.getElementById('pfEmailModal'))?.hide();
        notyf.success("Proforma invoice sent successfully");
        loadPf();
    } catch (err) {
        handleApiError(err, form);
    }
});

const loadPf = async () => {
    try {
        const res = await api.get(`/sales/proforma-invoices/${pfId}`);
        renderPfDetails(res.data.data);
    } catch (err) {
        notyf.error("Unable to load proforma invoice");
    }
};

document.addEventListener('DOMContentLoaded', loadPf);
</script>
@endpush
