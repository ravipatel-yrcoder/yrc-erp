@extends('layouts.app')
@section('title', 'Purchase Order')

@section('content')

<?php $tenantContext = tenantContext(); ?>

<div class="container-fluid">

    <!-- Page Header -->
    <div class="mb-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h4 class="mb-0">Purchase Order <span class="text-muted fw-normal fs-5" id="poDocCode"></span></h4>
            <span id="poStatusBadge"></span>
            <span id="headerEditBtnSlot"></span>
        </div>
    </div>

    <!-- Vendor (left) + action buttons (right) — own row, col-lg-8 width matches main layout -->
    <div class="row mb-4">
        <div class="col-lg-9">
            <div class="row g-3 align-items-center">
                <div class="col-md-5" id="poVendorSubline"></div>
                <div class="col-md-7" id="actionButtons" class="flex-shrink-0"></div>
            </div>
        </div>
    </div>

    <!-- Main two-column layout: col-lg-8 content + col-lg-4 timeline -->
    <div class="row g-4">
        <div class="col-md-9">

            <!-- KPI Cards (4-in-a-row) -->
            <div class="row g-4 mb-4">
                <!-- Order Status -->
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar avatar-sm flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-primary" id="kpiStatusIcon"><i class="icon-base bx bx-file-blank icon-lg"></i></span>
                                </div>
                                <div class="min-w-0 flex-grow-1">
                                    <div class="detail-kpi-label">Order Status</div>
                                    <div class="fw-semibold text-truncate" id="kpiStatusText">—</div>
                                    <small class="text-muted" id="kpiStatusSub"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Receiving Progress -->
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar avatar-sm flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-warning"><i class="icon-base bx bx-package icon-lg"></i></span>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <div class="detail-kpi-label">Receiving Progress</div>
                                    <div class="fw-semibold" id="kpiProgressText">—</div>
                                    <div class="d-flex align-items-center gap-2">
                                        <div class="progress flex-grow-1" style="height:6px;">
                                            <div class="progress-bar" id="kpiProgressBar" role="progressbar" style="width:0%"></div>
                                        </div>
                                        <small class="text-muted flex-shrink-0" id="kpiProgressPct">0%</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- Expected Delivery -->
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <div class="d-flex align-items-start gap-3">
                                <div class="avatar avatar-sm flex-shrink-0">
                                    <span class="avatar-initial rounded bg-label-info"><i class="icon-base bx bx-calendar icon-lg"></i></span>
                                </div>
                                <div>
                                    <div class="detail-kpi-label">Expected Delivery</div>
                                    <div class="fw-semibold" id="kpiDeliveryDate">—</div>
                                    <small id="kpiDeliveryRelative"></small>
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
                                    <div class="fw-semibold" id="kpiGrandTotal">—</div>
                                    <small class="text-muted" id="kpiItemCount"></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Next Step card (rendered by JS, shown between KPI and tabs) -->
            <div id="poNextStepCard" class="d-none mb-4"></div>

            <!-- Tab Navigation -->
            <ul class="nav detail-tabs border g-5" id="poDetailTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-po-tab="overview" type="button"><i class="icon-base bx bx-layout me-1"></i>Overview</button>
                </li>
                @if($tenantContext->canAccess('purchase_receipts'))
                <li class="nav-item">
                    <button class="nav-link" data-po-tab="receives" type="button"><i class="icon-base bx bx-package me-1"></i>Receives <span class="badge bg-label-primary" id="receivesTabBadge">0</span></button>
                </li>
                @endif
            </ul>

            <!-- Overview Pane -->
            <div id="overviewPane" class="detail-tab-pane">

                <!-- Order Details + Vendor side-by-side -->
                <div class="row g-4 mt-0">
                    <!-- Order Details -->
                    <div class="col-md-9">
                        <div class="card h-100">
                            <div class="card-body">
                                <!-- Fields: label fixed-width + value on same row -->
                                <div class="row g-2">
                                    <div class="col-6 d-none" id="poInquirySection">
                                        <span class="detail-label detail-label-w">Purchase Inquiry</span><span id="poInquiry">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="detail-label detail-label-w">Order Date</span><span id="orderDate">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="detail-label detail-label-w">Payment Terms</span><span id="paymentTerms">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="detail-label detail-label-w">Currency</span><span id="poCurrency">-</span>
                                    </div>
                                    <div class="col-6 d-none" id="poReferenceSection">
                                        <span class="detail-label detail-label-w">Reference</span><span id="poReference">-</span>
                                    </div>
                                </div>
                                <!-- Notes: label on own row, value below, two-column layout -->
                                <div class="row g-2 mt-3 pt-2 border-top-dashed">
                                    <div class="col-6">
                                        <div class="detail-label">Notes</div>
                                        <p class="mb-0" id="notes">-</p>
                                    </div>
                                    <div class="col-6 d-none" id="internalNotesSection">
                                        <div class="detail-label">Internal Notes</div>
                                        <p class="mb-0" id="internalNotes">-</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Vendor Details -->
                    <div class="col-md-3">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="detail-label mb-2">Vendor</div>
                                <p class="fw-semibold text-primary mb-2" id="vendorCardName">—</p>
                                <div class="text-muted lh-sm" id="vendorCardAddress"></div>
                                <div class="mt-3 d-none" id="vendorGstinRow">
                                    <div class="detail-label">GSTIN</div>
                                    <p class="mb-0 font-monospace" id="vendorCardGstin"></p>
                                </div>
                                <div class="mt-3 d-none" id="vendorPosRow">
                                    <div class="detail-label">Place of Supply</div>
                                    <p class="mb-0" id="poPlaceOfSupply"></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <!-- Items card -->
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-datatable table-responsive">
                                <div class="table-responsive">
                                    <table class="table m-0" id="lineItemsTable">
                                        <thead>
                                            <tr>
                                                <th class="ps-3 border-top-0">#</th>
                                                <th class="border-top-0">Item</th>
                                                <th class="text-end border-top-0">Ordered</th>
                                                <th class="text-end border-top-0 d-none" id="receivedColHeader">Received</th>
                                                <th class="text-end border-top-0 d-none" id="pendingColHeader">Pending</th>
                                                <th class="text-end border-top-0">Unit Cost</th>
                                                <th class="text-end border-top-0 d-none" id="discColHeader">Discount</th>
                                                <th class="text-end border-top-0">Tax</th>
                                                <th class="text-end pe-3 border-top-0">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody><tr><td colspan="9" class="text-center py-4 text-muted ps-3">No data</td></tr></tbody>
                                    </table>
                                </div>
                                <div class="d-flex justify-content-end pt-4">
                                    <table class="table table-borderless w-auto mb-0" id="totalsTable"></table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>

            <!-- Receives Pane -->
            @if($tenantContext->canAccess('purchase_receipts'))
            <div id="receivesPane" class="detail-tab-pane d-none">
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="table m-0" id="poReceivesTable">
                                        <thead>
                                            <tr>
                                                <th class="border-top-0">Purchase Receive#</th>
                                                <th class="border-top-0">Create Date</th>
                                                <th class="border-top-0">Status</th>
                                                <th class="border-top-0">Received Date</th>
                                                <th class="text-end border-top-0">Items</th>
                                                <th class="border-top-0"></th>
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
            @endif

        </div>

        <div class="col-md-3">
            <div class="card full-height-sticky-card">
                <div class="card-header">
                    <h5 class="card-title m-0">Timeline</h5>
                </div>
                <div class="card-body pt-2">
                    <ul class="timeline timeline-outline mb-0" id="poHistoryTimeline">
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

@includeOnce('app.components.drawers.purchase-orders.add-edit')
@includeOnce('app.components.drawers.purchase-orders.receive')
@if(tenantContext()->canDo('vendors', 'write'))
@includeOnce('app.components.drawers.vendors.add-edit')
@endif

<!-- Email Composer Modal -->
<div class="modal fade" id="poEmailComposerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="poEmailComposerForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="poEmailTo">To <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="poEmailTo" name="to" placeholder="recipient@example.com" />
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="poEmailCc">CC <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="email" class="form-control" id="poEmailCc" name="cc" placeholder="cc@example.com" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="poEmailBcc">BCC <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="email" class="form-control" id="poEmailBcc" name="bcc" placeholder="bcc@example.com" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="poEmailSubject">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="poEmailSubject" name="subject" />
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea id="poEmailBody" name="body"></textarea>
                    </div>
                    <div id="poEmailAttachmentsList" class="d-flex flex-wrap gap-2 mt-2"></div>
                    <input type="file" id="poEmailAttachments" multiple class="d-none" />
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="poAttachFilesBtn" title="Attach files">
                    <i class="icon-base bx bx-paperclip fs-5"></i>
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary" id="poSendEmailSubmitBtn">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
let _poDetails = null;

const poRelativeDate = function(dateStr) {
    if (!dateStr) return '';
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const d = new Date(dateStr.substring(0, 10)); d.setHours(0, 0, 0, 0);
    const diff = Math.round((d - today) / 86400000);
    if (diff === 0) return '<span class="text-warning fw-medium">today</span>';
    if (diff > 0)  return `<span class="text-muted">in ${diff} day${diff > 1 ? 's' : ''}</span>`;
    return `<span class="text-danger fw-medium">overdue by ${Math.abs(diff)} day${Math.abs(diff) > 1 ? 's' : ''}</span>`;
};

const renderPoNextStep = function(poDetails) {
    const card = document.getElementById('poNextStepCard');
    if (!card) return;

    const status    = poDetails.status;
    const lineItems = poDetails.line_items || [];
    const allFullyReceived = lineItems.length > 0 && lineItems.every(i => parseFloat(i.received_qty) >= parseFloat(i.ordered_qty));

    if (status === 'cancelled') { card.classList.add('d-none'); return; }

    if (allFullyReceived || status === 'closed') {
        card.classList.add('d-none');
        return;
    }

    const steps = {
        draft: {
            icon: 'bx-clipboard-check',
            title: 'Review and confirm',
            desc:  'Review items and amounts, then confirm to proceed.',
            action: 'confirmed', btnText: 'Confirm Order', btnClass: 'btn-success',
        },
        confirmed: {
            icon: 'bx-inbox',
            title: 'Receive items',
            desc:  'This purchase order is confirmed and ready for receiving.',
            action: 'receive', btnText: 'Receive Items', btnClass: 'btn-primary',
        },
        partially_received: {
            icon: 'bx-inbox',
            title: 'Continue receiving',
            desc:  'Some items are still pending. Receive them when they arrive.',
            action: 'receive', btnText: 'Receive Items', btnClass: 'btn-primary',
        },
    };

    const step = steps[status];
    if (!step) { card.classList.add('d-none'); return; }

    card.innerHTML = `
        <div class="card border-0 shadow-none bg-soft-surface">
            <div class="card-body d-flex flex-wrap align-items-center gap-3 py-3">
                <div class="avatar flex-shrink-0">
                    <span class="avatar-initial rounded bg-label-primary"><i class="icon-base bx ${step.icon} icon-lg"></i></span>
                </div>
                <div class="flex-grow-1">
                    <div class="detail-kpi-label">Next Step</div>
                    <div class="fw-semibold">${step.title}</div>
                    <small class="text-muted">${step.desc}</small>
                </div>
                <button class="btn ${step.btnClass} po-action-btn flex-shrink-0 w-md-100" data-action="${step.action}">
                    ${step.btnText}
                </button>
            </div>
        </div>`;
    card.classList.remove('d-none');
};

const renderPODetailsSection = async function(poDetails) {

    _poDetails = poDetails;

    const poStatus   = poDetails.status;
    const lineItems  = poDetails.line_items || [];
    const poCurrency = poDetails.currency_code || window.sysDefaultConfig?.currency || 'INR';

    // --- Receiving totals (needed by both KPI status and progress cards) ---
    const totalOrdered  = lineItems.reduce((s, i) => s + parseFloat(i.ordered_qty  || 0), 0);
    const totalReceived = lineItems.reduce((s, i) => s + parseFloat(i.received_qty || 0), 0);
    const pct = totalOrdered > 0 ? Math.round((totalReceived / totalOrdered) * 100) : 0;

    const allNotReceived   = lineItems.every(i => parseFloat(i.received_qty) === 0);
    const allFullyReceived = lineItems.length > 0 && lineItems.every(i => parseFloat(i.received_qty) >= parseFloat(i.ordered_qty));

    // --- Page header: order number + status badge ---
    const poDocCodeEl = document.getElementById('poDocCode');
    if (poDocCodeEl) poDocCodeEl.textContent = poDetails.po_number ? `— #${poDetails.po_number}` : '';

    const statusMap = {
        draft:               ['Draft',              'warning'],
        confirmed:           ['Confirmed',          'primary'],
        partially_received:  ['Part. Received',     'info'],
        received:            ['Received',           'success'],
        cancelled:           ['Cancelled',          'danger'],
        closed:              ['Closed',             'secondary'],
    };
    const poStatusBadge = document.getElementById('poStatusBadge');
    if (poStatusBadge) {
        poStatusBadge.innerHTML = statusMap[poStatus]
            ? `<span class="badge bg-label-${statusMap[poStatus][1]} align-middle ms-1">${statusMap[poStatus][0]}</span>`
            : '';
    }
    const headerEditSlot = document.getElementById('headerEditBtnSlot');
    if (headerEditSlot) {
        headerEditSlot.innerHTML = poStatus === 'draft'
            ? `<button class="btn btn-outline-warning btn-sm po-action-btn" data-action="edit" title="Edit order"><i class="bx bx-edit"></i></button>`
            : '';
    }

    // --- Page header: vendor name + location subline ---
    const vendorAddrRaw = poDetails.vendor_address_snapshot;
    const vendorAddr    = vendorAddrRaw && typeof vendorAddrRaw === 'object' ? vendorAddrRaw : null;
    const vendorCity    = vendorAddr ? [vendorAddr.city, vendorAddr.state].filter(Boolean).join(', ') : '';

    const poVendorSubline = document.getElementById('poVendorSubline');
    if (poVendorSubline) {
        const parts = [];
        if (poDetails.vendor_name) {
            parts.push(`<span class="d-inline-flex align-items-center gap-1 fw-semibold"><i class="icon-base bx bx-user icon-sm text-muted"></i>${poDetails.vendor_name}</span>`);
        }
        if (vendorCity) {
            parts.push(`<span class="d-inline-flex align-items-center gap-1 text-muted small"><i class="icon-base bx bx-map-pin icon-sm"></i>${vendorCity}</span>`);
        }
        poVendorSubline.innerHTML = parts.length ? `<div class="d-flex align-items-center gap-4 flex-wrap">${parts.join('')}</div>` : '';
    }

    // --- Vendor card ---
    const vendorCardName    = document.getElementById('vendorCardName');
    const vendorCardAddress = document.getElementById('vendorCardAddress');
    const vendorGstinRow    = document.getElementById('vendorGstinRow');
    const vendorCardGstin   = document.getElementById('vendorCardGstin');

    if (vendorCardName) vendorCardName.textContent = poDetails.vendor_name || '—';
    if (vendorCardAddress && vendorAddr) {
        const addrParts = [
            vendorAddr.address_line_1 || vendorAddr.street || '',
            vendorAddr.city,
            vendorAddr.state,
            vendorAddr.pincode || vendorAddr.zip_code || '',
            vendorAddr.country,
        ].filter(Boolean);
        vendorCardAddress.innerHTML = addrParts.join('<br>');
    }
    const gstin = vendorAddr?.gstin || vendorAddr?.gst_number || poDetails.vendor_gstin || '';
    if (gstin && vendorGstinRow && vendorCardGstin) {
        vendorCardGstin.textContent = gstin;
        vendorGstinRow.classList.remove('d-none');
    }

    const vendorPosRow = document.getElementById('vendorPosRow');
    if (poDetails.place_of_supply_name) {
        document.getElementById('poPlaceOfSupply').textContent = poDetails.place_of_supply_name + (poDetails.place_of_supply_code ? ' (' + poDetails.place_of_supply_code + ')' : '');
        vendorPosRow?.classList.remove('d-none');
    } else {
        vendorPosRow?.classList.add('d-none');
    }

    // --- KPI: Order Status (icon + color static in HTML; only text is dynamic) ---
    let kpiStatusLabel, kpiStatusSub;
    if (poStatus === 'draft') {
        kpiStatusLabel = 'Draft';       kpiStatusSub = 'Pending confirmation';
    } else if (poStatus === 'cancelled') {
        kpiStatusLabel = 'Cancelled';   kpiStatusSub = '';
    } else if (poStatus === 'closed') {
        kpiStatusLabel = 'Closed';      kpiStatusSub = '';
    } else if (allFullyReceived) {
        kpiStatusLabel = 'Received';    kpiStatusSub = 'All items received';
    } else if (!allNotReceived) {
        kpiStatusLabel = 'Part. Recv';  kpiStatusSub = `${formatQty(totalReceived)} of ${formatQty(totalOrdered)}`;
    } else {
        kpiStatusLabel = 'Confirmed';   kpiStatusSub = 'Ready to receive';
    }
    document.getElementById('kpiStatusText').textContent = kpiStatusLabel;
    document.getElementById('kpiStatusSub').textContent  = kpiStatusSub;
    if (allFullyReceived) {
        const iconEl = document.getElementById('kpiStatusIcon');
        iconEl.className = 'avatar-initial rounded bg-label-success';
        iconEl.innerHTML = '<i class="icon-base bx bx-check-circle icon-lg"></i>';
    }

    // --- KPI: Receiving progress ---
    document.getElementById('kpiProgressText').textContent = `${formatQty(totalReceived)} / ${formatQty(totalOrdered)} received`;
    const bar = document.getElementById('kpiProgressBar');
    bar.style.width = `${pct}%`;
    bar.className   = `progress-bar ${pct >= 100 ? 'bg-success' : 'bg-info'}`;
    document.getElementById('kpiProgressPct').textContent = `${pct}%`;

    // --- KPI: Expected delivery ---
    const deliveryDate = poDetails.expected_delivery_date;
    document.getElementById('kpiDeliveryDate').textContent   = deliveryDate ? formatMySqlDate(deliveryDate) : '—';
    document.getElementById('kpiDeliveryRelative').innerHTML = poRelativeDate(deliveryDate);

    // --- KPI: Total amount ---
    document.getElementById('kpiGrandTotal').textContent = formatCurrency(parseFloat(poDetails.grand_total || 0), { currency: poCurrency });
    document.getElementById('kpiItemCount').textContent  = lineItems.length ? `${lineItems.length} item${lineItems.length > 1 ? 's' : ''}` : '';

    // --- Next Step card ---
    renderPoNextStep(poDetails);

    // --- Overview: info grid ---
    const poInquirySection = document.getElementById('poInquirySection');
    if (poDetails.inquiry_id && poDetails.inquiry_number) {
        document.getElementById('poInquiry').innerHTML = `<a class="align-middle" href="/purchase/inquiries/${poDetails.inquiry_id}/" target="_blank" rel="noopener">${poDetails.inquiry_number} <i class="ms-1 align-middle bx bx-link-external"></i></a>`;
        poInquirySection?.classList.remove('d-none');
    } else {
        poInquirySection?.classList.add('d-none');
    }

    document.getElementById('orderDate').innerHTML    = formatMySqlDate(poDetails.order_date);
    document.getElementById('paymentTerms').innerHTML = poDetails.payment_terms || '-';
    document.getElementById('poCurrency').innerHTML   = poCurrency;
    document.getElementById('notes').innerHTML        = poDetails.notes || '-';

    const poReferenceSection = document.getElementById('poReferenceSection');
    if (poDetails.reference) {
        document.getElementById('poReference').innerHTML = poDetails.reference;
        poReferenceSection?.classList.remove('d-none');
    } else {
        poReferenceSection?.classList.add('d-none');
    }

    const internalNotesSection = document.getElementById('internalNotesSection');
    if (poDetails.internal_notes) {
        document.getElementById('internalNotes').innerHTML = poDetails.internal_notes;
        internalNotesSection?.classList.remove('d-none');
    } else {
        internalNotesSection?.classList.add('d-none');
    }

    // --- Items table ---
    const showReceived = poStatus !== 'draft';
    document.getElementById('receivedColHeader')?.classList.toggle('d-none', !showReceived);
    document.getElementById('pendingColHeader')?.classList.toggle('d-none', !showReceived);

    const hasDiscount = lineItems.some(item => parseFloat(item.discount_amount || 0) > 0);
    document.getElementById('discColHeader')?.classList.toggle('d-none', !hasDiscount);

    const tbody = document.querySelector('#lineItemsTable tbody');
    tbody.innerHTML = '';

    lineItems.forEach((item, idx) => {
        const itemUomCode = item.uom_code || '';
        const orderedQty  = parseFloat(item.ordered_qty  || 0);
        const receivedQty = parseFloat(item.received_qty || 0);
        const pendingQty  = Math.max(0, orderedQty - receivedQty);
        const discountAmt = parseFloat(item.discount_amount || 0);
        const discCell    = hasDiscount ? `<td class="text-end">${discountAmt > 0 ? formatCurrency(discountAmt, { currency: poCurrency }) : '—'}</td>` : '';

        const taxRaw     = item.tax_info;
        const taxInfoArr = Array.isArray(taxRaw) ? taxRaw : (typeof taxRaw === 'string' && taxRaw ? JSON.parse(taxRaw) : []);
        const taxLabel   = taxInfoArr.map(t => t.name).filter(Boolean).join(', ') || '—';

        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td class="ps-3 text-muted">${idx + 1}</td>
                <td>
                    <div class="fw-medium">${item.product_name}</div>
                    ${item.description ? `<small class="text-muted">${item.description}</small>` : ''}
                </td>
                <td class="text-end">${formatQty(orderedQty)} <span class="fs-tiny fw-semibold">${itemUomCode}</span></td>
                <td class="text-end receivedCell ${showReceived ? '' : 'd-none'}">${formatQty(receivedQty)}</td>
                <td class="text-end pendingCell ${showReceived ? '' : 'd-none'}">${formatQty(pendingQty)}</td>
                <td class="text-end">${formatCurrency(item.unit_price, { currency: poCurrency })}</td>
                ${discCell}
                <td class="text-end">${taxLabel}</td>
                <td class="text-end pe-3 fw-semibold">${formatCurrency(item.line_total, { currency: poCurrency })}</td>
            </tr>
        `);
    });

    // --- Totals ---
    const po                  = poDetails;
    const subtotal            = parseFloat(po.subtotal              || 0);
    const itemDiscTotal       = parseFloat(po.item_discount_total   || 0);
    const orderDiscountAmount = parseFloat(po.order_discount_amount || 0);
    const taxAmount           = parseFloat(po.tax_amount            || 0);
    const adjustmentAmount    = parseFloat(po.adjustment_amount     || 0);
    const roundOffAmount      = parseFloat(po.round_off_amount      || 0);
    const grandTotal          = parseFloat(po.grand_total           || 0);
    const adjustmentLabel     = po.adjustment_label || '';

    const totalsTable = document.getElementById('totalsTable');
    let totalsHtml = `
        <tr>
            <td class="ps-0 text-muted">Subtotal</td>
            <td class="text-end">${formatCurrency(subtotal, { currency: poCurrency })}</td>
        </tr>`;

    if (itemDiscTotal > 0) {
        totalsHtml += `
        <tr>
            <td class="ps-0 text-muted">Item Discounts</td>
            <td class="text-end text-danger">−${formatCurrency(itemDiscTotal, { currency: poCurrency })}</td>
        </tr>
        <tr>
            <td class="ps-0 text-muted">Subtotal after Discounts</td>
            <td class="text-end">${formatCurrency(subtotal - itemDiscTotal, { currency: poCurrency })}</td>
        </tr>`;
    }

    if (orderDiscountAmount > 0) {
        totalsHtml += `
        <tr>
            <td class="ps-0 text-muted">Order Discount</td>
            <td class="text-end text-danger">−${formatCurrency(orderDiscountAmount, { currency: poCurrency })}</td>
        </tr>`;
    }

    totalsHtml += `
        <tr>
            <td class="ps-0 text-muted">Tax</td>
            <td class="text-end">${formatCurrency(taxAmount, { currency: poCurrency })}</td>
        </tr>`;

    if (adjustmentLabel || adjustmentAmount !== 0) {
        totalsHtml += `
        <tr>
            <td class="ps-0 text-muted">${adjustmentLabel || 'Adjustment'}</td>
            <td class="text-end">${formatCurrency(adjustmentAmount, { currency: poCurrency })}</td>
        </tr>`;
    }

    if (roundOffAmount !== 0) {
        const roSign  = roundOffAmount < 0 ? '− ' : '+ ';
        const roClass = roundOffAmount < 0 ? 'text-danger' : 'text-success';
        totalsHtml += `
        <tr>
            <td class="ps-0 text-muted">Round-off</td>
            <td class="text-end ${roClass}">${roSign}${formatCurrency(Math.abs(roundOffAmount), { currency: poCurrency })}</td>
        </tr>`;
    }

    totalsHtml += `
        <tr class="border-top">
            <td class="ps-0 fw-semibold pt-2">Grand Total</td>
            <td class="text-end fw-bold pt-2">${formatCurrency(grandTotal, { currency: poCurrency })}</td>
        </tr>`;

    totalsTable.innerHTML = totalsHtml;

    // --- Action buttons ---
    const sendEmailBtn = `<button class="btn btn-outline-secondary btn-sm po-action-btn" id="sendEmailButton" data-action="send_email"><i class="icon-base bx bx-envelope icon-sm me-1"></i>Send</button>`;
    const viewBtn      = `<button class="btn btn-outline-secondary btn-sm po-action-btn" data-action="pdf-view"><i class="icon-base bx bx-show icon-sm me-1"></i>View</button>`;

    const moreItems = [];

    if (poStatus === 'draft') {
        moreItems.push(`<li><a class="dropdown-item po-action-btn" data-action="edit" href="javascript:void(0)"><i class="icon-base bx bx-edit icon-sm me-2"></i>Edit</a></li>`);
        moreItems.push(`<li><a class="dropdown-item po-action-btn" data-action="confirmed" href="javascript:void(0)"><i class="icon-base bx bx-like icon-sm me-2"></i>Confirm Order</a></li>`);
        moreItems.push(`<li><hr class="dropdown-divider"></li>`);
        moreItems.push(`<li><a class="dropdown-item text-danger po-action-btn" data-action="cancel" href="javascript:void(0)"><i class="icon-base bx bx-x icon-sm me-2"></i>Cancel</a></li>`);
    }

    if (poStatus === 'confirmed') {
        moreItems.push(`<li><a class="dropdown-item po-action-btn" data-action="receive" href="javascript:void(0)"><i class="icon-base bx bx-import icon-sm me-2"></i>Receive Items</a></li>`);
        moreItems.push(`<li><hr class="dropdown-divider"></li>`);
        moreItems.push(`<li><a class="dropdown-item text-danger po-action-btn" data-action="cancel" href="javascript:void(0)"><i class="icon-base bx bx-x icon-sm me-2"></i>Cancel</a></li>`);
    }

    if (poStatus === 'partially_received') {
        moreItems.push(`<li><a class="dropdown-item po-action-btn" data-action="receive" href="javascript:void(0)"><i class="icon-base bx bx-import icon-sm me-2"></i>Receive Items</a></li>`);
    }

    const moreBtn = moreItems.length ? `
        <div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                More
            </button>
            <ul class="dropdown-menu dropdown-menu-end">${moreItems.join('')}</ul>
        </div>` : '';

    document.getElementById('actionButtons').innerHTML = `
        <div class="d-flex justify-content-lg-end gap-3">
            ${sendEmailBtn}${viewBtn}${moreBtn}
        </div>`;
};

const refreshPurchaseOrderDetails = async function(poId) {
    try {
        const response = await api.get(`/purchase/orders/${poId}`);
        const { data } = response.data;
        renderPODetailsSection(data.po_details);
    } catch (error) {
        notyf.error("Unable to load purchase order details");
    }
};

const formatChange = function(oldVal, newVal, data = {}) {

    if (oldVal == "" && newVal == "") return "";

    const type = data.type || "";
    let html = '';

    if (oldVal) {
        let oldValUomHtml = '';
        if (type === 'qty') oldValUomHtml = ` <span class="fs-tiny fw-semibold">${data.oldUomCode || ""}</span>`;
        html += `<span class="text-muted">${oldVal}${oldValUomHtml}</span>`;
        if (newVal) html += `<span class="mx-1 text-primary fw-semibold">→</span>`;
    }

    if (newVal) {
        let newValUomHtml = '';
        if (type === 'qty') newValUomHtml = ` <span class="fs-tiny fw-semibold">${data.newUomCode || ""}</span>`;
        html += `<span class="text-primary">${newVal}${newValUomHtml}</span>`;
    }

    return html;
};

const buildPoAttachmentList = function(attachments) {
    if (!attachments || !attachments.length) return '';
    const links = attachments.map(a => {
        const icon       = a.is_image ? 'bx-image' : 'bx-file';
        const size       = a.file_size > 1048576 ? (a.file_size / 1048576).toFixed(1) + ' MB' : Math.round(a.file_size / 1024) + ' KB';
        const isViewable = a.is_image || a.mime_type === 'application/pdf';
        const viewIcon   = isViewable
            ? `<a href="javascript:void(0);" onclick="openPdfViewer('${a.download_url}', '${a.original_name.replace(/'/g, "\\'")}')" class="text-muted ms-1 flex-shrink-0" title="View"><i class="bx bx-show fs-6"></i></a>`
            : '';
        return `<div class="d-flex align-items-center py-1">
                    <a href="javascript:void(0);" onclick="downloadAttachment('${a.download_url}', '${a.original_name.replace(/'/g, "\\'")}')"
                       class="d-flex align-items-center gap-1 text-muted small text-decoration-none flex-grow-1"
                       title="${a.original_name}">
                        <i class="bx ${icon} fs-6 flex-shrink-0"></i>
                        <span class="text-truncate" style="max-width:180px;">${a.original_name}</span>
                        <span class="flex-shrink-0 ms-1 opacity-75">(${size})</span>
                    </a>${viewIcon}
                </div>`;
    }).join('');
    return `<div class="border rounded px-2 py-1 mt-1 bg-light">${links}</div>`;
};

const renderPoHistoryItemMeta = function(activityType, meta = {}) {

    if (!meta || typeof meta !== 'object') return '';

    let html = '';

    if (activityType === "created") {
        html = '<ul class="mt-2 mb-2 ps-3 small">';
        if (meta.status) html += `<li>Status: <strong class='text-primary'>${ucFirst(meta.status)}</strong></li>`;
        if (meta.source === 'purchase_inquiry' && meta.inquiry_number) {
            html += `<li>From Inquiry: <strong class='text-primary'>${meta.inquiry_number}</strong></li>`;
        }
        if (meta.item_count != null) html += `<li>Items: <strong>${meta.item_count}</strong></li>`;
        html += '</ul>';
    }
    else if (activityType === "updated_details") {
        html = `<ul class="mt-2 mb-2 ps-3 small">`;
        meta.forEach(item => {
            let finalOldVal = item.old_val || "";
            let finalNewVal = item.new_val || "";
            if (item.field === "order_date" || item.field === "expected_delivery_date") {
                if (finalOldVal) finalOldVal = formatMySqlDate(finalOldVal);
                if (finalNewVal) finalNewVal = formatMySqlDate(finalNewVal);
            }
            const formattedHtml = formatChange(finalOldVal, finalNewVal);
            if (formattedHtml) html += `<li>${item.label}: <strong class='text-primary'>${formattedHtml}</strong></li>`;
        });
        html += `</ul>`;
    }
    else if (activityType === "updated_line_items") {
        meta.forEach(item => {
            const itemOldUomCode = item.old_uom || "";
            const itemNewUomCode = item.new_uom || "";
            html += `<div class="small mb-1">
                        <strong>${item.prod_name}</strong>
                        ${item.event === 'deleted' ? `<span class="badge bg-label-danger ms-1 p-1">Delete</span>` : ''}
                        ${item.event === 'created' ? `<span class="badge bg-label-success ms-1 p-1">Add</span>` : ''}
                        ${item.event === 'updated' ? `<span class="badge bg-label-warning ms-1 p-1">Update</span>` : ''}
                    </div>`;
            html += `<ul class="mt-2 mb-2 ps-7 small">`;
            if (item.event === 'created') {
                html += `<li class="ps-0">Qty: <span class="text-primary">${item.new_qty} <span class="fs-tiny fw-semibold">${itemNewUomCode}</span></span></li>`;
            } else if (item.event === 'deleted') {
                html += `<li class="ps-0">Qty: <span class="text-danger">${item.old_qty} <span class="fs-tiny fw-semibold">${itemOldUomCode}</span></span></li>`;
            } else {
                if (item.old_qty != item.new_qty) {
                    const changeData = { type: 'qty', oldUomCode: itemOldUomCode, newUomCode: itemNewUomCode };
                    html += `<li class="ps-0">Qty: ${formatChange(item.old_qty, item.new_qty, changeData)}</li>`;
                }
            }
            if (item.event === 'created') {
                html += `<li class="ps-0">Unit Cost: <span class="text-primary">${item.new_unit_cost}</span></li>`;
            } else if (item.event === 'deleted') {
                html += `<li class="ps-0">Unit Cost: <span class="text-muted">${item.old_unit_cost}</span></li>`;
            } else {
                if (item.old_unit_cost != item.new_unit_cost) {
                    html += `<li class="ps-0">Unit Cost: ${formatChange(item.old_unit_cost, item.new_unit_cost)}</li>`;
                }
            }
            html += `</ul>`;
        });
    }
    else if (activityType === "status_changed") {
        const statusLabels = { draft: 'Draft', confirmed: 'Confirmed', partially_received: 'Partially Received', received: 'Received', cancelled: 'Cancelled' };
        const oldLabel = statusLabels[meta.old_status] || ucFirst(meta.old_status || '');
        const newLabel = statusLabels[meta.new_status] || ucFirst(meta.new_status || '');
        html += `<ul class="mt-2 mb-2 ps-7 small">
            <li class="ps-0">${formatChange(oldLabel, newLabel)}</li>
        </ul>`;
    }
    else if (activityType === "email_sent") {
        html += '<ul class="mt-2 mb-2 ps-3 small">';
        if (meta.from) html += `<li>From: <strong>${meta.from}</strong></li>`;
        html += `<li>To: <strong>${meta.to || '-'}</strong></li>`;
        if (meta.cc)  html += `<li>CC: <strong>${meta.cc}</strong></li>`;
        if (meta.bcc) html += `<li>BCC: <strong>${meta.bcc}</strong></li>`;
        html += `<li>Subject: <strong>${meta.subject || '-'}</strong></li>`;
        html += '</ul>';
        html += buildPoAttachmentList(meta.attachments || []);
    }
    else if (activityType === "received") {
        html += `<ul class="mt-2 mb-2 ps-7 small">
            <li class="ps-0">Received items: <strong class="text-primary">${meta.items_count}</strong></li>
            <li class="ps-0">Received Qty: <strong class="text-primary">${meta.quantities}</strong></li>
        </ul>`;
    }

    return html;
};

const renderPurchaseOrderHistory = function(history = []) {

    const container = document.getElementById('poHistoryTimeline');
    if (!container) return;

    container.innerHTML = '';

    if (!Array.isArray(history) || history.length === 0) {
        container.innerHTML = `
            <li class="timeline-item timeline-item-transparent">
                <div class="timeline-event text-muted">No history available</div>
            </li>`;
        return;
    }

    history.forEach(item => {
        const activityType = item.log_type || "";
        const item_meta    = item.meta || {};
        let finalTitle     = item.title || '';
        if (activityType === "received") {
            const receipt_number = item_meta.receipt_number || "";
            if (receipt_number) finalTitle += " #" + receipt_number;
        }

        const rawName = item.performed_by || 'System';
        const nameParts = rawName.trim().split(/\s+/);
        const shortName = nameParts.length > 1
            ? `${nameParts[0]} ${nameParts[nameParts.length - 1].charAt(0)}.`
            : nameParts[0];

        container.insertAdjacentHTML('beforeend', `
            <li class="timeline-item timeline-item-transparent border-dashed">
                <span class="timeline-point timeline-point-info"></span>
                <div class="timeline-event">
                    <h6 class="timeline-event-title mb-1">${finalTitle}</h6>
                    ${renderPoHistoryItemMeta(activityType, item_meta)}
                    <div class="small text-muted mt-1">
                        <span class="fw-medium">${shortName}</span> &middot; ${item.date_time || '-'}
                    </div>
                </div>
            </li>
        `);
    });
};

const refreshPurchaseOrderHistory = async function(poId) {
    try {
        const response = await api.get(`/purchase/orders/${poId}/history`);
        renderPurchaseOrderHistory(response.data.data);
    } catch (error) {
        console.log(error);
        notyf.error("Unable to load purchase order history");
    }
};

const refreshPurchaseOrderReceipts = async function(poId) {

    @if(!$tenantContext->canAccess('purchase_receipts'))
    return;
    @endif

    try {
        const response = await api.get(`/purchase/receipts`, { params: { po_id: poId } });
        const { data } = response.data;

        const tbody              = document.querySelector('#poReceivesTable tbody');
        const receiptsCountBadge = document.getElementById('receivesTabBadge');

        tbody.innerHTML = '';
        if (receiptsCountBadge) receiptsCountBadge.textContent = '0';

        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">No purchase receipts found</td></tr>`;
            return;
        }

        if (receiptsCountBadge) receiptsCountBadge.textContent = data.length;

        const receiptStatusMap = {
            draft:      ['Draft',      'warning'],
            in_transit: ['In Transit', 'info'],
            received:   ['Received',   'success'],
            cancelled:  ['Cancelled',  'danger'],
        };

        let rowsHtml = '';
        data.forEach(item => {
            const [statusLabel, statusColor] = receiptStatusMap[item.status] ?? ['Draft', 'secondary'];
            rowsHtml += `<tr>
                <td><a href="/purchase/receipts/${item.id}/" class="text-primary fw-medium">${item.receipt_number}</a></td>
                <td>${item.create_date ? formatMySqlDate(item.create_date) : '-'}</td>
                <td><span class="badge bg-label-${statusColor}">${statusLabel}</span></td>
                <td>${item.received_date ? formatMySqlDate(item.received_date) : '-'}</td>
                <td class="text-end">${item.items_count ?? '0'}</td>
                <td class="text-end">
                    <a href="/purchase/receipts/${item.id}/" class="text-primary"><i class="icon-base bx bx-show"></i></a>
                </td>
            </tr>`;
        });

        tbody.innerHTML = rowsHtml;

    } catch (error) {
        notyf.error("Unable to load purchase order receives");
    }
};


document.addEventListener('DOMContentLoaded', async () => {

    const poId = "{{ request()->getInput('id') ?? '' }}";
    if (!poId) return;

    refreshPurchaseOrderDetails(poId);
    refreshPurchaseOrderReceipts(poId);
    refreshPurchaseOrderHistory(poId);

    // Tab switching
    document.querySelectorAll('[data-po-tab]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-po-tab]').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.detail-tab-pane').forEach(p => p.classList.add('d-none'));
            this.classList.add('active');
            const pane = document.getElementById(this.dataset.poTab + 'Pane');
            if (pane) pane.classList.remove('d-none');
        });
    });
});


const cancelPurchaseOrder = function(poId) {
    showConfirmation(
        'Are you sure you want to cancel this Purchase Order? This action cannot be undone.',
        'warning',
        {
            text: 'Cancel Order',
            class: 'btn-label-danger',
            callback: async function() {
                try {
                    await api.post(`/purchase/orders/${poId}/status`, { status: 'cancelled' });
                    notyf.success('Purchase Order cancelled.');
                    refreshPurchaseOrderDetails(poId);
                    refreshPurchaseOrderHistory(poId);
                } catch (error) {
                    handleApiError(error);
                }
            }
        }
    );
};


const updatePurchaseOrderStatus = async function(poId, status, notes = '') {
    try {
        await api.post(`/purchase/orders/${poId}/status`, { status, notes });
        notyf.success(status === "confirmed" ? "Purchase order approved/confirmed successfully" : "Status updated successfully");
        refreshPurchaseOrderDetails(poId);
        refreshPurchaseOrderHistory(poId);
    } catch (error) {
        notyf.error("Failed to update status");
    }
};


document.addEventListener('receiptFormSaved', function(e) {
    const poId = e.detail.poId || "{{ request()->getInput('id') ?? '' }}";
    if (!poId) return;
    refreshPurchaseOrderDetails(poId);
    refreshPurchaseOrderReceipts(poId);
    refreshPurchaseOrderHistory(poId);
});


const actionHandlers = {
    edit:       (poId) => openPurchaseOrderFormDrawer(poId),
    send_email: async (poId) => {
        const btn = document.querySelector('.po-action-btn[data-action="send_email"]');
        setButtonLoading(btn, true, 'Generating PDF…');
        try {
            const res = await api.get(`/purchase/orders/${poId}/generate-email-pdf`);
            openPoEmailComposer(poId, [res.data.data]);
        } catch (err) {
            notyf.error(err?.response?.data?.message || 'Failed to generate PDF. Please try again.');
        } finally {
            setButtonLoading(btn, false);
        }
    },
    confirmed:  (poId) => updatePurchaseOrderStatus(poId, "confirmed", "PO Confirmed by user"),
    cancel:     (poId) => cancelPurchaseOrder(poId),
    'pdf-view': (poId) => openPdfViewer(`/purchase/orders/${poId}/pdf`, `PO #${_poDetails?.po_number || ''}`),
    receive:    (poId) => openReceivePurchaseOrderFormDrawer(poId),
};

document.addEventListener('click', function(e) {
    const btn = e.target.closest('.po-action-btn');
    if (!btn) return;
    const poId = "{{ request()->getInput('id') ?? '' }}";
    if (!poId) return;
    const action = btn.dataset.action;
    if (actionHandlers[action]) {
        actionHandlers[action](poId);
    } else {
        console.warn(`No handler registered for action: ${action}`);
    }
});


// ─── Email Composer ───────────────────────────────────────────────────────────

let _poJoditInstance      = null;
let _poEmailComposerModal = null;
let _poEmailDefaultBody   = '';
let _poEmailPoId          = null;
let _poAttachedFiles      = [];

const renderPoEmailAttachmentChips = function() {
    const container = document.getElementById('poEmailAttachmentsList');
    container.innerHTML = '';
    _poAttachedFiles.forEach((file, index) => {
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

const openPoEmailComposer = async function(poId, preAttachments = []) {
    _poEmailPoId = poId;
    const po = _poDetails || {};

    cleanFormInputFeedback(document.getElementById('poEmailComposerForm'));

    document.getElementById('poEmailTo').value  = po.vendor_email || '';
    document.getElementById('poEmailCc').value  = '';
    document.getElementById('poEmailBcc').value = '';

    document.querySelector('#poEmailComposerModal .modal-title').textContent = 'Send Purchase Order';

    try {
        const res      = await api.get(`/purchase/orders/${poId}/email-defaults`);
        const defaults = res.data?.data || {};
        document.getElementById('poEmailSubject').value = defaults.subject || '';
        if (defaults.cc)  document.getElementById('poEmailCc').value  = defaults.cc;
        if (defaults.bcc) document.getElementById('poEmailBcc').value = defaults.bcc;
        _poEmailDefaultBody = defaults.body || '';
    } catch (_) {
        document.getElementById('poEmailSubject').value = '';
        _poEmailDefaultBody = '';
    }

    _poAttachedFiles = preAttachments;
    renderPoEmailAttachmentChips();

    if (_poJoditInstance) { _poJoditInstance.destruct(); _poJoditInstance = null; }

    _poEmailComposerModal.show();
};

const handlePoSendEmail = async function() {
    const sendBtn = document.getElementById('poSendEmailSubmitBtn');
    const form    = document.getElementById('poEmailComposerForm');

    cleanFormInputFeedback(form);

    const to      = document.getElementById('poEmailTo').value.trim();
    const cc      = document.getElementById('poEmailCc').value.trim();
    const bcc     = document.getElementById('poEmailBcc').value.trim();
    const subject = document.getElementById('poEmailSubject').value.trim();
    const body    = _poJoditInstance ? _poJoditInstance.value : '';

    setButtonLoading(sendBtn, true);
    try {
        await api.post(`/purchase/orders/${_poEmailPoId}/send-email`, { to, cc, bcc, subject, body, attachments: _poAttachedFiles });
        notyf.success('Email sent successfully');
        _poEmailComposerModal.hide();
        refreshPurchaseOrderDetails(_poEmailPoId);
        refreshPurchaseOrderHistory(_poEmailPoId);
    } catch (error) {
        handleApiError(error, form);
    } finally {
        setButtonLoading(sendBtn, false);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    _poEmailComposerModal = new bootstrap.Modal(document.getElementById('poEmailComposerModal'), {
        backdrop: 'static',
        keyboard: false,
        focus: false,
    });

    document.getElementById('poEmailComposerModal').addEventListener('shown.bs.modal', function() {
        if (_poJoditInstance) { _poJoditInstance.destruct(); _poJoditInstance = null; }
        _poJoditInstance = Jodit.make('#poEmailBody', {
            height: 300,
            enter: 'BR',
            buttons: 'bold,italic,underline,strikethrough,|,ul,ol,|,paragraph,|,link,image',
            toolbarAdaptive: false,
            showCharsCounter: false,
            showWordsCounter: false,
            showXPathInStatusbar: false,
            addNewLine: false,
        });
        _poJoditInstance.value = _poEmailDefaultBody;
    });

    document.getElementById('poAttachFilesBtn').addEventListener('click', function() {
        document.getElementById('poEmailAttachments').click();
    });

    document.getElementById('poEmailAttachments').addEventListener('change', async function() {
        if (!this.files.length) return;
        const newFiles = await readFilesAsBase64(this);
        _poAttachedFiles.push(...newFiles);
        renderPoEmailAttachmentChips();
        this.value = '';
    });

    document.getElementById('poEmailAttachmentsList').addEventListener('click', function(e) {
        const btn = e.target.closest('[data-attach-index]');
        if (!btn) return;
        _poAttachedFiles.splice(parseInt(btn.dataset.attachIndex), 1);
        renderPoEmailAttachmentChips();
    });

    document.getElementById('poSendEmailSubmitBtn').addEventListener('click', handlePoSendEmail);
});
</script>
@endpush
