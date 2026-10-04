@extends('layouts.app')
@section('title', 'Sales Order')

@section('content')

<?php
$tenantContext = tenantContext();
?>

<div class="container-fluid">

    <!-- Page Header -->
    <div class="mb-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <h4 class="mb-0"><span id="soPageHeading">Sales Order</span> <span class="text-muted fw-normal fs-5" id="soDocCode"></span></h4>
            <span id="soStatusBadge"></span>
            <span id="soSentBadge"></span>
            <span id="soHeaderEditBtnSlot"></span>
        </div>
    </div>

    <!-- Customer subline + action buttons -->
    <div class="row mb-4">
        <div class="col-lg-9">
            <div class="row g-3 align-items-center">
                <div class="col-md-5" id="soCustomerSubline"></div>
                <div class="col-md-7" id="actionButtons"></div>
            </div>
        </div>
    </div>

    <!-- Main two-column layout -->
    <div class="row g-4">
        <div class="col-md-9">

            <!-- KPI Cards: JS-rendered — 3×col-md-4 for quotation, 4×col-md-3 for sales order -->
            <div class="row g-4 mb-4" id="soKpiCards"></div>

            <!-- Next Step card -->
            <div id="soNextStepCard" class="d-none mb-4"></div>

            <!-- Tab Navigation -->
            <ul class="nav detail-tabs border" id="soDetailTabs" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active" data-so-tab="overview" type="button"><i class="icon-base bx bx-layout me-1"></i>Overview</button>
                </li>
                @if($tenantContext->canAccess('proforma_invoices') && Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId))
                <li class="nav-item d-none" id="soProformasTabItem">
                    <button class="nav-link" data-so-tab="proformas" type="button"><i class="icon-base bx bx-receipt me-1"></i>Proforma Invoices <span class="badge bg-label-secondary" id="soProformasTabBadge">0</span></button>
                </li>
                @endif
                @if($tenantContext->canAccess('sales_deliveries'))
                <li class="nav-item d-none" id="soDeliveriesTabItem">
                    <button class="nav-link" data-so-tab="deliveries" type="button"><i class="icon-base bx bx-package me-1"></i>Deliveries <span class="badge bg-label-primary" id="soDeliveriesTabBadge">0</span></button>
                </li>
                @endif
                @if($tenantContext->canAccess('sales_returns'))
                <li class="nav-item d-none" id="soReturnsTabItem">
                    <button class="nav-link" data-so-tab="returns" type="button"><i class="icon-base bx bx-undo me-1"></i>Returns <span class="badge bg-label-warning" id="soReturnsTabBadge">0</span></button>
                </li>
                @endif                
            </ul>

            <!-- Overview Pane -->
            <div id="soOverviewPane" class="detail-tab-pane">
                <div class="row g-4 mt-0">
                    <!-- Customer & Addresses Card (first) -->
                    <div class="col-md-5">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="detail-label mb-1">Customer &amp; Addresses</div>
                                <p class="fw-semibold text-primary mb-1" id="soCustomer">—</p>
                                <p class="text-muted small mb-3" id="soDeliveryTypeLabel">Pickup</p>

                                <div class="row g-4 small">
                                    <div class="col-12" id="soBillToCol">
                                        <div class="fw-semibold text-muted mb-1">Bill To</div>
                                        <div class="d-flex g-1 flex-column" id="soCustomerBillingAddr"></div>
                                    </div>
                                    <div class="col-6 d-none" id="soShippingAddrRow">
                                        <div class="fw-semibold text-muted mb-1">Ship To</div>
                                        <div class="d-flex g-1 flex-column" id="soCustomerShippingAddr"></div>
                                    </div>
                                </div>

                                <div class="mt-2 pt-2 border-top-dashed d-none" id="soCustomerPosRow">
                                    <span class="detail-label detail-label-w">Place of Supply</span><span id="soCustomerPlaceOfSupply">—</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- Order Details (second) -->
                    <div class="col-md-7">
                        <div class="card h-100">
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-6 d-none" id="quoteDateRow">
                                        <span class="detail-label detail-label-w">Quote Date</span><span id="quoteDate">-</span>
                                    </div>
                                    <div class="col-6" id="orderDateRow">
                                        <span class="detail-label detail-label-w">Order Date</span><span id="orderDate">-</span>
                                    </div>
                                    <div class="col-6 d-none" id="convertedAtRow">
                                        <span class="detail-label detail-label-w">Converted On</span><span id="convertedAt">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="detail-label detail-label-w">Payment Terms</span><span id="paymentTerms">-</span>
                                    </div>
                                    <div class="col-6">
                                        <span class="detail-label detail-label-w">Reference</span><span id="soReference">-</span>
                                    </div>
                                    @if(Service_CompanySettings::isMultiWarehouseEnabled(tenantContext()->companyId))
                                    <div class="col-6">
                                        <span class="detail-label detail-label-w">Warehouse</span><span id="warehouse">-</span>
                                    </div>
                                    @endif
                                    <div class="col-6 d-none" id="leadRefRow">
                                        <span class="detail-label detail-label-w">Lead Ref#</span><span id="soLeadLink">-</span>
                                    </div>
                                    <div class="col-6 d-none" id="soExpDeliveryRow">
                                        <span class="detail-label detail-label-w">Expected Delivery</span><span id="soExpDelivery">-</span>
                                    </div>
                                </div>
                                <div class="row g-2 mt-2 pt-2 border-top-dashed">
                                    <div class="col-6">
                                        <div class="detail-label">Notes</div>
                                        <p class="mb-0" id="soNotes">-</p>
                                    </div>
                                    <div class="col-6 d-none" id="soInternalNotesSection">
                                        <div class="detail-label">Internal Notes</div>
                                        <p class="mb-0" id="soInternalNotes">-</p>
                                    </div>
                                    @include('partial.ui.terms-collapse', ['prefix' => 'so'])
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row mt-4">
                    <!-- Items card -->
                    <div class="col-md-12">
                        <div class="card">
                            <!-- Items Table -->
                            <div class="card-datatable table-responsive">
                                <div class="table-responsive">
                                    <table class="table m-0" id="lineItemsTable">
                                        <thead>
                                            <tr>
                                                <th class="ps-3 border-top-0">#</th>
                                                <th class="border-top-0">Item</th>
                                                <th class="text-end border-top-0">Ordered</th>
                                                <th class="text-end border-top-0 d-none" id="deliveredColHeader">Delivered</th>
                                                <th class="text-end border-top-0 d-none" id="returnedColHeader">Returned</th>
                                                <th class="text-end border-top-0">Unit Price</th>
                                                <th class="text-end border-top-0">Discount</th>
                                                <th class="text-end border-top-0">Tax</th>
                                                <th class="text-end pe-3 border-top-0">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody><tr><td colspan="9" class="text-center py-4 text-muted ps-3">Loading…</td></tr></tbody>
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

            <!-- Deliveries Pane -->
            @if($tenantContext->canAccess('sales_deliveries'))
            <div id="soDeliveriesPane" class="detail-tab-pane d-none">
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-datatable table-responsive">
                                <table class="table m-0" id="soDeliveriesTable">
                                    <thead>
                                        <tr>
                                            <th class="ps-3 border-top-0">DN#</th>
                                            <th class="border-top-0">Warehouse</th>
                                            <th class="border-top-0">Status</th>
                                            <th class="border-top-0">Dispatch Date</th>
                                            <th class="border-top-0">Delivery Date</th>
                                            <th class="text-end border-top-0">Items</th>
                                            <th class="border-top-0">Created By</th>
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
            @endif

            <!-- Returns Pane -->
            @if($tenantContext->canAccess('sales_returns'))
            <div id="soReturnsPane" class="detail-tab-pane d-none">
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-datatable table-responsive">
                                <table class="table m-0" id="soReturnsTable">
                                    <thead>
                                        <tr>
                                            <th class="ps-3 border-top-0">Return #</th>
                                            <th class="border-top-0">Status</th>
                                            <th class="border-top-0">Return Date</th>
                                            <th class="text-end border-top-0">Items</th>
                                            <th class="border-top-0">Created By</th>
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
            @endif

            <!-- Proforma Invoices Pane -->
            @if($tenantContext->canAccess('proforma_invoices') && Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId))
            <div id="soProformasPane" class="detail-tab-pane d-none">
                <div class="row mt-4">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-datatable table-responsive">
                                <table class="table m-0" id="soProformasTable">
                                    <thead>
                                        <tr>
                                            <th class="ps-3 border-top-0">Proforma #</th>
                                            <th class="border-top-0">Date</th>
                                            <th class="border-top-0">Status</th>
                                            <th class="text-end border-top-0">Total</th>
                                            <th class="border-top-0">Created By</th>
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
            @endif

        </div>

        <!-- Timeline -->
        <div class="col-md-3">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title m-0">Timeline</h5>
                </div>
                <div class="card-body pt-2">
                    <ul class="timeline timeline-outline mb-0" id="soHistoryTimeline">
                        <li class="timeline-item timeline-item-transparent">
                            <div class="timeline-event text-muted">No history available</div>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

    </div>

</div>

@if($tenantContext->canDo('sales_orders', 'write'))
@includeOnce('app.components.drawers.sales-orders.add-edit')
@endif
@if($tenantContext->canDo('sales_deliveries', 'write'))
@includeOnce('app.components.drawers.sales-deliveries.add-edit')
@endif
@if($tenantContext->canDo('sales_returns', 'write'))
@includeOnce('app.components.drawers.sales-returns.add-edit')
@endif
@if($tenantContext->canDo('proforma_invoices', 'create') && Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId))
@includeOnce('app.components.drawers.proforma-invoices.add')
@endif


@if($tenantContext->canDo('sales_orders', 'send_email'))
<!-- Email Composer Modal -->
<div class="modal fade" id="emailComposerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="emailComposerModalTitle">Send</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailComposerForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="emailTo">To <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="emailTo" name="to" placeholder="recipient@example.com" />
                    </div>
                    <div class="row g-3 mb-3">
                        <div class="col-md-6">
                            <label class="form-label" for="emailCc">CC <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="email" class="form-control" id="emailCc" name="cc" placeholder="cc@example.com" />
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" for="emailBcc">BCC <span class="text-muted fw-normal">(optional)</span></label>
                            <input type="email" class="form-control" id="emailBcc" name="bcc" placeholder="bcc@example.com" />
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="emailSubject">Subject <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="emailSubject" name="subject" />
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Message <span class="text-danger">*</span></label>
                        <textarea id="emailBody" name="body"></textarea>
                    </div>
                    <!-- Attachment chips displayed below editor -->
                    <div id="emailAttachmentsList" class="d-flex flex-wrap gap-2 mt-2"></div>
                    <!-- Hidden file input -->
                    <input type="file" id="emailAttachments" multiple class="d-none" />
                </form>
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-sm btn-outline-secondary" id="attachFilesBtn" title="Attach files">
                    <i class="icon-base bx bx-paperclip fs-5"></i>
                </button>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-sm btn-primary" id="sendEmailSubmitBtn">Send</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

@if($tenantContext->canAccess('proforma_invoices') && Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId))
<div class="modal fade" id="pfPickerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="pfPickerTitle">Select Proforma Invoice</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="pfPickerBody"></div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
const dnStatusMap = {
    draft:      ['Draft',      'warning'],
    dispatched: ['Dispatched', 'info'],
    delivered:  ['Delivered',  'success'],
    returned:   ['Returned',   'warning'],
    lost:       ['Lost',       'danger'],
    cancelled:  ['Cancelled',  'danger'],
};

const refreshSalesOrderDeliveries = async function(soId) {

    @if(!$tenantContext->canAccess('sales_deliveries'))
    return;
    @endif


    try {
        const response = await api.get('/sales/deliveries', { params: { so_id: soId } });
        const { data } = response.data;

        const tbody = document.querySelector('#soDeliveriesTable tbody');
        const badge = document.getElementById('soDeliveriesTabBadge');

        tbody.innerHTML = '';
        badge.innerHTML = '0';

        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-3">No deliveries found</td></tr>`;
            return;
        }

        badge.innerHTML = data.length;

        let rowsHtml = '';
        data.forEach(item => {
            const s = dnStatusMap[item.status] || [item.status, 'secondary'];
            rowsHtml += `<tr>
                <td><a href="/sales/deliveries/${item.id}/" class="text-primary fw-medium">${item.dn_number}</a></td>
                <td>${item.warehouse ?? '-'}</td>
                <td><span class="badge badge-sm bg-label-${s[1]}">${s[0]}</span></td>
                <td>${formatMySqlDate(item.dispatch_date)}</td>
                <td>${formatMySqlDate(item.delivery_date)}</td>
                <td class="text-end">${item.items_count ?? '0'}</td>
                <td>${item.created_by_name ?? '-'}</td>
                <td class="text-end">
                    <a href="/sales/deliveries/${item.id}/" class="text-primary"><i class="icon-base bx bx-show"></i></a>
                </td>
            </tr>`;
        });


        tbody.innerHTML = rowsHtml;

    } catch (error) {
        notyf.error("Unable to load deliveries");
    }
};

const refreshSalesOrderReturns = async function(soId) {

    @if(!$tenantContext->canAccess('sales_returns'))
    return;
    @endif

    try {
        const response = await api.get('/sales/returns', { params: { so_id: soId } });
        const { data } = response.data;

        const tbody = document.querySelector('#soReturnsTable tbody');
        const badge = document.getElementById('soReturnsTabBadge');

        tbody.innerHTML = '';
        badge.innerHTML = '0';

        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">No returns found</td></tr>`;
            return;
        }

        badge.innerHTML = data.length;

        const retStatusMap = {
            draft:      ['Draft',      'warning'],
            in_transit: ['In Transit', 'info'],
            received:   ['Received',   'success'],
            cancelled:  ['Cancelled',  'danger'],
        };

        let rowsHtml = '';
        data.forEach(r => {
            const s = retStatusMap[r.status] || [r.status, 'secondary'];
            rowsHtml += `<tr>
                <td><a href="/sales/returns/${r.id}/" class="text-primary fw-medium">${r.return_number}</a></td>
                <td><span class="badge badge-sm bg-label-${s[1]}">${s[0]}</span></td>
                <td>${formatMySqlDate(r.return_date)}</td>
                <td class="text-end">${r.items_count ?? '0'}</td>
                <td>${r.created_by_name ?? '-'}</td>
                <td class="text-end">
                    <a href="/sales/returns/${r.id}/" class="text-primary"><i class="icon-base bx bx-show"></i></a>
                </td>
            </tr>`;
        });

        tbody.innerHTML = rowsHtml;

    } catch (error) {
        notyf.error("Unable to load returns");
    }
};


let _pfList = [];

const refreshSalesOrderProformas = async function(soId, soStatus) {

    @if(!$tenantContext->canAccess('proforma_invoices') || !Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId))
    return;
    @endif

    try {
        const response = await api.get(`/sales/proforma-invoices/list/${soId}`);
        const data = response.data.data;

        _pfList = data || [];

        const tbody = document.querySelector('#soProformasTable tbody');
        const badge = document.getElementById('soProformasTabBadge');

        badge.innerHTML = '0';
        tbody.innerHTML = '';

        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">No proforma invoices found</td></tr>`;
            return;
        }

        badge.innerHTML = data.length;

        const pfStatusMap = {
            draft:     ['Draft',     'warning'],
            sent:      ['Sent',      'success'],
            cancelled: ['Cancelled', 'danger'],
        };

        let html = '';
        data.forEach(pf => {
            const s = pfStatusMap[pf.status] || [pf.status, 'secondary'];
            const outdatedBadge = pf.is_outdated
                ? `<span class="badge badge-sm bg-label-warning ms-1">Outdated</span>`
                : '';
            const sendBtn = pf.status !== 'cancelled'
                ? `<a href="javascript:void(0);" class="btn btn-icon text-warning" onclick="pfPickerSendBtn(this,'send',${pf.id})" title="Send"><i class="icon-base bx bx-send"></i></a>`
                : '';
            html += `<tr>
                <td><a href="/sales/proforma-invoices/${pf.id}/" class="text-primary fw-medium">${pf.proforma_number}</a></td>
                <td>${formatMySqlDate(pf.proforma_date)}</td>
                <td><span class="badge badge-sm bg-label-${s[1]}">${s[0]}</span>${outdatedBadge}</td>
                <td class="text-end">${formatCurrency(pf.grand_total)}</td>
                <td>${pf.created_by_name ?? '-'}</td>
                <td class="text-end">
                    <a href="/sales/proforma-invoices/${pf.id}/" class=" btn btn-icon text-primary"><i class="icon-base bx bx-show"></i></a>${sendBtn}
                </td>
            </tr>`;
        });
        tbody.innerHTML = html;

    } catch (error) {
        notyf.error("Unable to load proforma invoices");
    }
};


const _recalcPfTotals = function() {
    clearTimeout(_pfComputeTimer);
    _pfComputeTimer = setTimeout(_fetchPfComputedTotals, 300);
};

const _fetchPfComputedTotals = async function() {
    const ctx = _pfCtx;
    if (!ctx) return;
    const totalsEl = document.getElementById('pfDrawerTotals');
    if (totalsEl) totalsEl.style.opacity = '0.4';
    const rows = document.querySelectorAll('#pfDrawerItemsBody tr[data-item-idx]');
    const items = [];
    let subtotal = 0;

    rows.forEach(row => {
        const idx      = parseInt(row.dataset.itemIdx, 10);
        const orig     = ctx.items[idx];
        const newQty   = parseFloat(unformatNumber(row.querySelector('.pf-qty-input')?.value)) || 0;
        const origQty  = parseFloat(orig.quantity) || 1;
        const ratio    = origQty !== 0 ? newQty / origQty : 0;
        const itemAmount = parseFloat(orig.line_total) * ratio;
        row.querySelector('.pf-cell-amount').textContent = formatCurrency(itemAmount);
        subtotal += parseFloat(orig.unit_price) * newQty;
        if (newQty <= 0) return;
        const ti = Array.isArray(orig.tax_info) ? orig.tax_info : [];
        items.push({
            product_id:         parseInt(orig.product_id),
            quantity:           newQty,
            unit_price:         parseFloat(orig.unit_price),
            item_discount_type: 'flat',
            item_discount:      parseFloat(orig.discount_amount || 0) * ratio,
            tax_info:           ti.map(t => ({ id: t.id, name: t.name, rate: parseFloat(t.rate || 0), type: t.type || 'percentage', gst_component: t.gst_component || 'none' })),
            tax_classification_code: orig.tax_classification_code || '',
        });
    });

    if (items.length === 0) {
        if (totalsEl) totalsEl.style.opacity = '';
        document.getElementById('pfDSubtotal').textContent   = formatCurrency(0);
        document.getElementById('pfDGrandTotal').textContent = formatCurrency(0);
        document.getElementById('pfDItemDiscRow').classList.add('d-none');
        document.getElementById('pfDOrderDiscRow').classList.add('d-none');
        document.getElementById('pfDRoundOffRow').classList.add('d-none');
        document.getElementById('pfDGstRowsGroup').innerHTML =
            '<tr><th class="ps-0 text-muted fw-normal">Tax</th><td class="text-end">-</td></tr>';
        return;
    }

    const ro = window.sysDefaultConfig?.roundOff || {};

    try {
        const resp = await api.post('/compute/document-totals', {
            document_type:       'pi',
            items:               items,
            ...(function() {
                const _pfDiscInfo = ctx.discount_info || {};
                const _pfSoBase   = parseFloat(ctx.subtotal_after_item_discount || 0);
                const _pfSoFlat   = parseFloat(ctx.order_discount_amount || 0);
                let   _pfDiscRate = 0;
                if (_pfDiscInfo.type === 'percent' && parseFloat(_pfDiscInfo.value || 0) > 0) {
                    _pfDiscRate = parseFloat(_pfDiscInfo.value);
                } else if (_pfSoBase > 0 && _pfSoFlat > 0) {
                    _pfDiscRate = (_pfSoFlat / _pfSoBase) * 100;
                }
                return { order_discount: _pfDiscRate, order_discount_type: 'percentage' };
            })(),
            adjustment_amount:   parseFloat(ctx.adjustment_amount || 0),
            adjustment_label:    ctx.adjustment_label || '',
            round_off_requested: (ro.mode || 'off') === 'manual' && pfRoundOffEnabled,
            round_off_config:    { mode: ro.mode || 'off', round_to: ro.roundTo || 1, method: ro.method || 'nearest' },
            gst_config:          {
                company_gstin:          window.sysDefaultConfig?.company?.gstin || '',
                company_state:          window.sysDefaultConfig?.company?.state || '',
                billing_address_gstin:  _pfBillAddrData?.gstin || '',
                billing_address_state:  _pfBillAddrData?.state || '',
                customer_gstin:         ctx.customer_gstin || '',
                customer_gst_treatment: ctx.customer_gst_treatment,
                reverse_charge:         document.getElementById('pfReverseCharge')?.checked || false,
            },
        });
        const computed       = resp.data.data;
        const gstSummary     = computed.gst_summary || {};
        const roundOff       = parseFloat(computed.round_off)            || 0;
        const grandTotal     = parseFloat(computed.grand_total)          || 0;
        const itemDiscTotal  = parseFloat(computed.item_discount_total   || 0);
        const orderDiscTotal = parseFloat(computed.order_discount_amount || 0);

        document.getElementById('pfDSubtotal').textContent   = formatCurrency(subtotal,   { maximumFractionDigits: 2 });
        document.getElementById('pfDGrandTotal').textContent = formatCurrency(grandTotal, { maximumFractionDigits: 2 });

        document.getElementById('pfDItemDiscRow').classList.toggle('d-none', itemDiscTotal < 0.001);
        if (itemDiscTotal >= 0.001)  document.getElementById('pfDItemDisc').textContent  = '- ' + formatCurrency(itemDiscTotal,  { maximumFractionDigits: 2 });
        document.getElementById('pfDOrderDiscRow').classList.toggle('d-none', orderDiscTotal < 0.001);
        if (orderDiscTotal >= 0.001) document.getElementById('pfDOrderDisc').textContent = '- ' + formatCurrency(orderDiscTotal, { maximumFractionDigits: 2 });

        // Render CGST/SGST or IGST breakdown rows
        const isRcm   = document.getElementById('pfReverseCharge')?.checked || false;
        const rcmLbl  = isRcm ? ' <small class="text-muted">(RCM)</small>' : '';
        const rcmCls  = isRcm ? 'text-muted' : '';
        let gstHtml = '';
        if (gstSummary.is_intra_state) {
            const cgst = parseFloat(computed.cgst_amount || 0);
            const sgst = parseFloat(computed.sgst_amount || 0);
            gstHtml += `<tr><th class="ps-0 text-muted fw-normal">CGST${rcmLbl}</th><td class="text-end ${rcmCls}">${formatCurrency(cgst)}</td></tr>`;
            gstHtml += `<tr><th class="ps-0 text-muted fw-normal">SGST / UTGST${rcmLbl}</th><td class="text-end ${rcmCls}">${formatCurrency(sgst)}</td></tr>`;
        } else {
            const igst = parseFloat(computed.igst_amount || 0);
            gstHtml += `<tr><th class="ps-0 text-muted fw-normal">IGST${rcmLbl}</th><td class="text-end ${rcmCls}">${formatCurrency(igst)}</td></tr>`;
        }
        const cess = parseFloat(computed.cess_amount || 0);
        if (cess > 0) {
            gstHtml += `<tr><th class="ps-0 text-muted fw-normal">Cess${rcmLbl}</th><td class="text-end ${rcmCls}">${formatCurrency(cess)}</td></tr>`;
        }
        document.getElementById('pfDGstRowsGroup').innerHTML = gstHtml;
        document.getElementById('pfRcmNotice')?.classList.toggle('d-none', !isRcm);

        document.getElementById('pfDRoundOffRow').classList.toggle('d-none', roundOff === 0);
        if (roundOff !== 0) document.getElementById('pfDRoundOff').textContent = (roundOff < 0 ? '− ' : '+ ') + formatCurrency(Math.abs(roundOff));
    } catch(e) {
        // silently leave totals as-is on network error
    } finally {
        if (totalsEl) totalsEl.style.opacity = '';
    }
};

const openCreateProforma = async function() {

    const soId = _soDetails?.id;
    if (!soId) return;

    try {
        const res = await api.get('/sales/proforma-invoices/form-context', { params: { so_id: soId } });
        const ctx = res.data.data;

        document.getElementById('pfFormSoId').value = soId;
        document.getElementById('pfNumberPreview').value = ctx.proforma_number_preview || '';
        document.getElementById('pfNumberSuggested').value = ctx.proforma_number_preview || '';

        initDatePicker('#pfProformaDate');
        datePickerSetDate('#pfProformaDate', ctx.proforma_date);

        // Valid Until — init picker and auto-populate from form-context (computed server-side from company settings)
        initDatePicker('#pfValidUntilDate');
        datePickerSetDate('#pfValidUntilDate', ctx.default_valid_until || '');

        document.getElementById('pfNotes').value = ctx.notes || '';

        // Payment Terms — read-only display
        document.getElementById('pfPaymentTermsDisplay').value = ctx.payment_terms_text || '';

        // Populate items
        const tbody = document.getElementById('pfDrawerItemsBody');
        if (!ctx.items || !ctx.items.length) {
            tbody.innerHTML = `<tr><td colspan="8" class="text-center text-muted py-3">No items</td></tr>`;
        } else {
            let html = '';
            ctx.items.forEach((item, i) => {
                const discInfo  = item.discount_info || {};
                const discAmt   = parseFloat(item.discount_amount || 0);
                const discLabel = discInfo.type === 'percent' && parseFloat(discInfo.value || 0) > 0
                    ? parseFloat(discInfo.value) + '%'
                    : (discAmt > 0 ? formatCurrency(discAmt) : '—');
                const taxArr   = Array.isArray(item.tax_info) ? item.tax_info : [];
                const taxLabel = taxArr.map(t => t.name).filter(Boolean).join(', ') || '—';
                const hsnCode  = item.tax_classification_code || '—';
                html += `<tr data-item-idx="${i}">
                    <td class="p-2">${i + 1}</td>
                    <td class="p-2">
                        <div class="fw-medium">${item.product_name || ''}</div>
                        ${item.sku ? `<div class="text-muted small">${item.sku}</div>` : ''}
                    </td>
                    <td class="p-2">${hsnCode}</td>
                    <td class="p-2 text-end">
                        <span class="fw-medium">${formatQty(item.quantity)}</span>
                        <input type="hidden" class="pf-qty-input" value="${item.quantity}" />
                        ${item.uom_code ? `<span class="fs-tiny mt-1 d-block text-primary fw-semibold">UOM: ${item.uom_code}</span>` : ''}
                    </td>
                    <td class="p-2 text-end">${formatCurrency(item.unit_price)}</td>
                    <td class="p-2 text-end">${discLabel}</td>
                    <td class="p-2">${taxLabel}</td>
                    <td class="p-2 text-end fw-semibold pf-cell-amount">${formatCurrency(item.line_total)}</td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        // Initial totals — fetched via compute API
        _pfCtx = ctx;
        pfRoundOffEnabled = parseFloat(ctx.round_off_amount || 0) !== 0;
        _recalcPfTotals();

        // Billing address dropdown
        _pfBillAddrSource            = null;
        _pfBillAddrData              = {};
        _pfBillAddrCustomerAddresses = ctx.customer_billing_addresses || [];
        _pfGstStates                 = ctx.gst_states || {};
        const pfBillSelect = jQuery('#pfBillingAddressId');
        pfBillSelect.empty().append('<option value="">Select address...</option>');
        _pfBillAddrCustomerAddresses.forEach(addr => pfBillSelect.append(new Option(addr.label, addr.id)));
        initSelect2('#pfBillingAddressId', {
            dropdownParent: jQuery('#addProformaInvoice'),
            placeholder: 'Select address...',
            allowClear: true,
            onChange: _pfBillingAddressChanged,
        });

        const snapBill = ctx.billing_address_snapshot || {};
        const matchedBillAddr = snapBill.id
            ? _pfBillAddrCustomerAddresses.find(a => String(a.id) === String(snapBill.id))
            : null;
        if (matchedBillAddr) {
            pfBillSelect.val(matchedBillAddr.id).trigger('change');
        } else if (_pfBillAddrCustomerAddresses.length === 1) {
            pfBillSelect.val(_pfBillAddrCustomerAddresses[0].id).trigger('change');
        } else if (snapBill && Object.keys(snapBill).length > 0) {
            _pfBillAddrData = snapBill;
            const parts = [snapBill.address_line1, snapBill.address_line2, snapBill.city, snapBill.state].filter(Boolean);
            const label = parts.length > 0 ? ('Saved — ' + parts.join(', ')) : 'Saved address';
            pfBillSelect.append(new Option(label, '_snapshot'));
            pfBillSelect.val('_snapshot').trigger('change');
        } else {
            pfBillSelect.trigger('change');
        }

        // Reset Reverse Charge toggle and RCM notice
        const rcEl = document.getElementById('pfReverseCharge');
        if (rcEl) {
            rcEl.checked = false;
            document.getElementById('pfRcmNotice')?.classList.add('d-none');
            rcEl.addEventListener('change', _recalcPfTotals);
            rcEl.addEventListener('change', function() {
                document.getElementById('pfRcmNotice')?.classList.toggle('d-none', !this.checked);
            });
        }

        // T&C editor — collapse and store default value; Jodit lazy-inited on first Show
        resetPfTermsEditor();
        _pfTermsValue = ctx.default_terms_conditions || '';

        // Store context on form for submit
        document.getElementById('addProformaForm').dataset.ctx = JSON.stringify(ctx);

        cleanFormInputFeedback(document.getElementById('addProformaForm'));

        const offcanvas = new bootstrap.Offcanvas(document.getElementById('addProformaInvoice'));
        offcanvas.show();

    } catch (err) {
        handleApiError(err);
    }
};


/* ===================================================
   PROFORMA INVOICE — TERMS & CONDITIONS EDITOR
   (collapsed by default, Jodit lazy-inited on first open)
=================================================== */
let _pfTermsJodit = null;
let _pfTermsValue = '';

const resetPfTermsEditor = function() {
    if (_pfTermsJodit) { _pfTermsJodit.destruct(); _pfTermsJodit = null; }
    _pfTermsValue = '';
    document.getElementById('pfTermsEditorWrap').classList.add('d-none');
    document.getElementById('pfTermsToggle').textContent = 'Show';
};

const togglePfTermsEditor = function() {
    const wrap = document.getElementById('pfTermsEditorWrap');
    const isHidden = wrap.classList.toggle('d-none');
    document.getElementById('pfTermsToggle').textContent = isHidden ? 'Show' : 'Hide';
    if (!isHidden && !_pfTermsJodit) {
        _pfTermsJodit = Jodit.make('#pfTermsInput', {
            height: 200,
            buttons: 'bold,italic,underline,strikethrough,|,ul,ol,|,left,center,right,|,hr,|,undo,redo',
            toolbarAdaptive: false,
            showCharsCounter: false,
            showWordsCounter: false,
            showXPathInStatusbar: false,
            addNewLine: false,
            askBeforePasteHTML: false,
            defaultActionOnPaste: 'insert_clear_html',
        });
        _pfTermsJodit.value = _pfTermsValue;
    }
};

const getPfTermsValue = function() {
    const html = _pfTermsJodit ? _pfTermsJodit.value : _pfTermsValue;
    return isHtmlEmpty(html) ? '' : html;
};


/* ===================================================
   PROFORMA INVOICE — BILLING ADDRESS
=================================================== */
let _pfBillAddrSource            = null;
let _pfBillAddrData              = {};
let _pfBillAddrCustomerAddresses = [];
let _pfGstStates                 = {};
let pfRoundOffEnabled            = false;
let _pfCtx                       = null;
let _pfComputeTimer              = null;

const _pfResolvePosDisplay = function(gstin, state, ctx) {
    const posEl = document.getElementById('pfPlaceOfSupply');
    if (!posEl) return;
    const g = (gstin || '').trim();
    if (g.length === 15) {
        const code = g.substring(0, 2);
        const name = _pfGstStates[code];
        if (name) { posEl.textContent = `${name} (${code})`; return; }
    }
    if (state) { posEl.textContent = state; return; }
    const posName = (ctx || {}).place_of_supply_name || '';
    const posCode = (ctx || {}).place_of_supply_code || '';
    posEl.textContent = posName
        ? (posCode ? `${posName} (${posCode})` : posName)
        : '— unknown, will default to IGST —';
};

const _pfSyncBillingAddressJson = function() {
    document.getElementById('pfBillingAddressJson').value =
        (_pfBillAddrData && Object.keys(_pfBillAddrData).length > 0) ? JSON.stringify(_pfBillAddrData) : '';
};

const _pfFormatAddrDisplay = function(addr) {
    if (!addr || !Object.keys(addr).length) return '—';
    const lines = [];
    if (addr.attention) lines.push(`<strong>${addr.attention}</strong>`);
    const street = [addr.address_line1, addr.address_line2].filter(Boolean).join(', ');
    if (street) lines.push(street);
    const cityState = [addr.city, addr.state].filter(Boolean).join(', ');
    if (cityState || addr.postal_code) lines.push([cityState, addr.postal_code].filter(Boolean).join(' – '));
    if (addr.gstin) lines.push(`<span class="text-muted">GSTIN: ${addr.gstin}</span>`);
    return lines.join('<br>');
};

// Unselected: select visible, no cancel, no change/edit
const _pfHideBillDisplay = function() {
    document.getElementById('pfBillAddrDisplayWrap').classList.add('d-none');
    document.getElementById('pfBillAddrSelectWrap').classList.remove('d-none');
    document.getElementById('pfAddNewBillingAddressBtn').classList.remove('d-none');
    document.getElementById('pfChangeBillingAddressBtn').classList.add('d-none');
    document.getElementById('pfEditBillingAddressBtn').classList.add('d-none');
    document.getElementById('pfCancelBillingAddressBtn').classList.add('d-none');
};

// Selected: display visible, change+edit visible, add+cancel hidden
const _pfShowBillDisplay = function(addr) {
    document.getElementById('pfBillAddrText').innerHTML = _pfFormatAddrDisplay(addr);
    document.getElementById('pfBillAddrDisplayWrap').classList.remove('d-none');
    document.getElementById('pfBillAddrSelectWrap').classList.add('d-none');
    document.getElementById('pfAddNewBillingAddressBtn').classList.add('d-none');
    document.getElementById('pfChangeBillingAddressBtn').classList.remove('d-none');
    document.getElementById('pfEditBillingAddressBtn').classList.remove('d-none');
    document.getElementById('pfCancelBillingAddressBtn').classList.add('d-none');
};

// Selecting: select visible, add+cancel visible, change+edit+display hidden
const _pfShowBillSelectForChange = function() {
    document.getElementById('pfBillAddrDisplayWrap').classList.add('d-none');
    document.getElementById('pfBillAddrSelectWrap').classList.remove('d-none');
    document.getElementById('pfAddNewBillingAddressBtn').classList.remove('d-none');
    document.getElementById('pfChangeBillingAddressBtn').classList.add('d-none');
    document.getElementById('pfEditBillingAddressBtn').classList.add('d-none');
    document.getElementById('pfCancelBillingAddressBtn').classList.remove('d-none');
};

const _pfBillingAddressChanged = function(_this) {
    const val = _this.value;
    const ctx = JSON.parse(document.getElementById('addProformaForm').dataset.ctx || '{}');
    if (!val) {
        _pfBillAddrSource = null;
        _pfBillAddrData   = {};
        _pfHideBillDisplay();
        document.getElementById('pfBillingAddressJson').value = '';
        _pfResolvePosDisplay(ctx.customer_gstin || '', '', ctx);
        return;
    }
    if (val === '_snapshot') {
        _pfBillAddrSource = 'snapshot';
    } else {
        _pfBillAddrSource = 'customer';
        _pfBillAddrData   = _pfBillAddrCustomerAddresses.find(a => String(a.id) === String(val)) || {};
    }
    _pfSyncBillingAddressJson();
    _pfShowBillDisplay(_pfBillAddrData);
    _pfResolvePosDisplay(_pfBillAddrData.gstin || ctx.customer_gstin || '', _pfBillAddrData.state || '', ctx);
    _recalcPfTotals();
};

// Add new billing address on PI
document.getElementById('pfAddNewBillingAddressBtn')?.addEventListener('click', function() {
    const ctx = JSON.parse(document.getElementById('addProformaForm').dataset.ctx || '{}');
    const customerId = ctx.customer_id;
    if (!customerId) return;
    openCustomerAddressModal(customerId, 'billing', {
        onSaved: function(addr) {
            _pfBillAddrCustomerAddresses.push(addr);
            const billSelect = jQuery('#pfBillingAddressId');
            billSelect.append(new Option(addr.label, addr.id));
            billSelect.val(addr.id).trigger('change');
        },
    });
});

// Change billing address on PI — enter selecting state
document.getElementById('pfChangeBillingAddressBtn')?.addEventListener('click', () => _pfShowBillSelectForChange());

// Edit billing address on PI — open edit address modal
document.getElementById('pfEditBillingAddressBtn')?.addEventListener('click', function() {
    const ctx = JSON.parse(document.getElementById('addProformaForm').dataset.ctx || '{}');
    const customerId = ctx.customer_id;
    if (!customerId) return;
    if (_pfBillAddrSource === 'customer') {
        const addrId = jQuery('#pfBillingAddressId').val();
        openCustomerAddressModal(customerId, 'billing', {
            editId:      addrId,
            prefillData: _pfBillAddrData,
            onSaved: function(addr) {
                _pfBillAddrData = addr;
                jQuery('#pfBillingAddressId').find(`option[value="${addrId}"]`).text(addr.label);
                const idx = _pfBillAddrCustomerAddresses.findIndex(a => String(a.id) === String(addrId));
                if (idx !== -1) _pfBillAddrCustomerAddresses[idx] = addr;
                _pfSyncBillingAddressJson();
                _pfShowBillDisplay(addr);
                _pfResolvePosDisplay(addr.gstin || ctx.customer_gstin || '', addr.state || '', ctx);
            },
        });
    } else {
        openCustomerAddressModal(null, 'billing', {
            mode:        'so_local',
            prefillData: _pfBillAddrData,
            onSaved: function(addr) {
                const pfCtx = JSON.parse(document.getElementById('addProformaForm').dataset.ctx || '{}');
                _pfBillAddrData = addr;
                _pfSyncBillingAddressJson();
                _pfShowBillDisplay(addr);
                _pfResolvePosDisplay(addr.gstin || pfCtx.customer_gstin || '', addr.state || '', pfCtx);
            },
        });
    }
});

// Cancel billing address change on PI
document.getElementById('pfCancelBillingAddressBtn')?.addEventListener('click', function() {
    if (_pfBillAddrData && Object.keys(_pfBillAddrData).length > 0) {
        _pfShowBillDisplay(_pfBillAddrData);
    } else {
        _pfHideBillDisplay();
    }
});


document.getElementById('pfSaveBtn')?.addEventListener('click', async function() {
    const form = document.getElementById('addProformaForm');
    const ctx  = JSON.parse(form.dataset.ctx || '{}');

    // Build items — backend recomputes all financial values; we send raw inputs only.
    const rows = document.querySelectorAll('#pfDrawerItemsBody tr[data-item-idx]');
    let piSubtotal = 0, discItemTotal = 0;
    const items = Array.from(rows).map(row => {
        const idx     = parseInt(row.dataset.itemIdx, 10);
        const orig    = ctx.items[idx];
        const newQty  = parseFloat(unformatNumber(row.querySelector('.pf-qty-input')?.value)) || 0;
        const origQty = parseFloat(orig.quantity) || 1;
        const ratio   = origQty !== 0 ? newQty / origQty : 0;
        const discAmt = parseFloat(orig.discount_amount || 0) * ratio;

        piSubtotal    += parseFloat(orig.unit_price) * newQty;
        discItemTotal += discAmt;

        return {
            sales_order_item_id:     orig.sales_order_item_id,
            product_id:              orig.product_id,
            product_name:            orig.product_name,
            sku:                     orig.sku,
            description:             orig.description,
            quantity:                newQty,
            unit_price:              parseFloat(orig.unit_price),
            discount_amount:         discAmt,
            discount_info:           orig.discount_info,
            tax_info:                orig.tax_info,
            uom_code:                orig.uom_code,
            product_uom_id:          orig.product_uom_id,
            tax_classification_type: orig.tax_classification_type,
            tax_classification_code: orig.tax_classification_code,
        };
    });

    // round_off_amount signals intent: non-zero = apply round-off. Backend computes actual value.
    const roMode   = window.sysDefaultConfig?.roundOff?.mode || 'off';
    const roundOff = (roMode === 'auto' || (roMode === 'manual' && pfRoundOffEnabled)) ? 1 : 0;

    const _pfDiscInfoS = ctx.discount_info || {};
    const _pfSoBaseS   = parseFloat(ctx.subtotal_after_item_discount || 0);
    const _pfSoFlatS   = parseFloat(ctx.order_discount_amount || 0);
    let   _pfDiscRateS = 0;
    if (_pfDiscInfoS.type === 'percent' && parseFloat(_pfDiscInfoS.value || 0) > 0) {
        _pfDiscRateS = parseFloat(_pfDiscInfoS.value);
    } else if (_pfSoBaseS > 0 && _pfSoFlatS > 0) {
        _pfDiscRateS = (_pfSoFlatS / _pfSoBaseS) * 100;
    }

    const payload = {
        sales_order_id:            parseInt(document.getElementById('pfFormSoId').value),
        proforma_number:           document.getElementById('pfNumberPreview').value.trim(),
        proforma_number_suggested: document.getElementById('pfNumberSuggested').value,
        proforma_date:             document.getElementById('pfProformaDate').value,
        valid_until:               document.getElementById('pfValidUntilDate').value.trim() || null,
        payment_terms:             document.getElementById('pfPaymentTermsDisplay').value.trim() || null,
        notes:                     document.getElementById('pfNotes').value.trim() || null,
        order_discount_rate:       _pfDiscRateS,
        discount_info:             ctx.discount_info,
        round_off_amount:          roundOff,
        adjustment_label:          ctx.adjustment_label || null,
        adjustment_amount:         parseFloat(ctx.adjustment_amount || 0),
        billing_address_snapshot:  JSON.parse(document.getElementById('pfBillingAddressJson').value || 'null') || ctx.billing_address_snapshot,
        customer_gstin:            (_pfBillAddrData.gstin || ctx.customer_gstin || '').trim().toUpperCase() || null,
        shipping_address_snapshot: ctx.shipping_address_snapshot,
        reverse_charge:            document.getElementById('pfReverseCharge')?.checked ? 1 : 0,
        invoice_terms:             getPfTermsValue(),
        items:                     items,
    };

    cleanFormInputFeedback(form);

    try {
        await api.post('/sales/proforma-invoices', payload);
        bootstrap.Offcanvas.getInstance(document.getElementById('addProformaInvoice'))?.hide();
        notyf.success("Proforma invoice created");
        refreshSalesOrderProformas(_soDetails.id, _soDetails.status);
    } catch (err) {
        handleApiError(err, form);
    }
});


const executeProformaAction = async function(action, pfId) {
    if (action === 'download') {
        window.location.href = `/sales/proforma-invoices/${pfId}/pdf?mode=download`;
    } else if (action === 'view') {
        const pfInfo = (_pfList || []).find(p => p.id === pfId);
        openPdfViewer(`/sales/proforma-invoices/${pfId}/pdf`, `Proforma ${pfInfo?.proforma_number || ''}`);
    } else if (action === 'send') {
        try {
            const res = await api.get(`/sales/proforma-invoices/${pfId}/generate-email-pdf`);
            bootstrap.Modal.getInstance(document.getElementById('pfPickerModal'))?.hide();
            openPfEmailComposer(pfId, [res.data.data]);
        } catch (err) {
            notyf.error('Failed to generate PDF. Please try again.');
        }
    }
};

const pfPickerSendBtn = async function(btn, action, pfId) {
    
    if (action === 'download' || action === 'view') {
        bootstrap.Modal.getInstance(document.getElementById('pfPickerModal'))?.hide();
        executeProformaAction(action, pfId);
        return;
    }
    
    const icon = btn.querySelector('i');
    const origClass = icon ? icon.className : null;
    btn.disabled = true;
    
    if (icon) icon.className = 'bx bx-loader-alt bx-spin';
    
    try {

        await executeProformaAction('send', pfId);

    } finally {
        
        btn.disabled = false;        
        if (icon && origClass) icon.className = origClass;
    }

};

const showProformaPicker = function(action, pfs) {
    const titles = { view: 'View Proforma Invoice', download: 'Download Proforma Invoice', send: 'Send Proforma Invoice' };
    document.getElementById('pfPickerTitle').textContent = titles[action] || 'Proforma Invoice';

    const btnMeta = {
        view:     { cls: 'text-info',    title: 'View',     icon: 'bx-show' },
        download: { cls: 'text-primary', title: 'Download', icon: 'bx-download' },
        send:     { cls: 'text-warning', title: 'Send',     icon: 'bx-send' },
    };
    const { cls, title: btnTitle, icon } = btnMeta[action] || btnMeta.send;

    const statusBadge = (status) => {
        const map = { draft: 'warning', sent: 'success', cancelled: 'danger' };
        const color = map[status] || 'secondary';
        return `<span class="badge bg-label-${color} text-capitalize">${status}</span>`;
    };

    const rows = pfs.map((pf, i) => `<tr>
        <td class="p-2 text-muted" style="width:32px;">${i + 1}</td>
        <td class="p-2 fw-medium">${pf.proforma_number}</td>
        <td class="p-2 text-muted small">${formatMySqlDate(pf.proforma_date)}</td>
        <td class="p-2">${statusBadge(pf.status)}</td>
        <td class="p-2 text-end">${formatCurrency(pf.grand_total)}</td>
        <td class="p-2 text-muted small">${pf.created_by_name ?? '-'}</td>
        <td class="p-2 text-center" style="width:56px;">
            <button class="btn btn-icon ${cls}"
                title="${btnTitle}"
                onclick="pfPickerSendBtn(this, '${action}', ${pf.id})">
                <i class="bx ${icon} fs-5"></i>
            </button>
        </td>
    </tr>`).join('');

    document.getElementById('pfPickerBody').innerHTML = `<table class="table table-bordered align-middle mb-0">
        <thead class="table-light">
            <tr>
                <th class="p-2" style="width:32px;">#</th>
                <th class="p-2">Number</th>
                <th class="p-2">Date</th>
                <th class="p-2">Status</th>
                <th class="p-2 text-end">Total</th>
                <th class="p-2">Created By</th>
                <th class="p-2" style="width:56px;"></th>
            </tr>
        </thead>
        <tbody>${rows}</tbody>
    </table>`;

    new bootstrap.Modal(document.getElementById('pfPickerModal')).show();
};

const openProformaPicker = function(action) {
    const activePfs = (_pfList || []).filter(pf => pf.status !== 'cancelled');
    if (activePfs.length === 0) { notyf.error('No active proforma invoices found.'); return; }
    if (activePfs.length === 1) { executeProformaAction(action, activePfs[0].id); return; }
    showProformaPicker(action, activePfs);
};


const soRelativeDate = function(dateStr) {
    if (!dateStr) return '';
    const today = new Date(); today.setHours(0, 0, 0, 0);
    const d = new Date(dateStr.substring(0, 10)); d.setHours(0, 0, 0, 0);
    const diff = Math.round((d - today) / 86400000);
    if (diff === 0) return '<span class="text-warning fw-medium">today</span>';
    if (diff > 0)  return `<span class="text-muted">in ${diff} day${diff > 1 ? 's' : ''}</span>`;
    return `<span class="text-danger fw-medium">overdue by ${Math.abs(diff)} day${Math.abs(diff) > 1 ? 's' : ''}</span>`;
};

const renderSODetailsSection = async function(soDetails) {

    _soDetails = soDetails;

    const soStatus        = soDetails.status;
    const isQuotationDoc  = soDetails.origin_type === 'quotation';
    const isOpenQuotation = isQuotationDoc && soStatus === 'draft';

    // Sidebar highlight
    const _sidebarQuotations = document.querySelector('a.menu-link[href="/sales/quotations/"]')?.closest('.menu-item');
    const _sidebarOrders     = document.querySelector('a.menu-link[href="/sales/orders/"]')?.closest('.menu-item');
    if (_sidebarQuotations) _sidebarQuotations.classList.toggle('active', isOpenQuotation);
    if (_sidebarOrders)     _sidebarOrders.classList.toggle('active', !isOpenQuotation);

    const _pfEnabled = @json(Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId) && $tenantContext->canAccess('proforma_invoices'));
    const isDraft    = soStatus === 'draft';

    // Sub-document tab items
    document.getElementById('soDeliveriesTabItem')?.classList.toggle('d-none', isDraft);
    document.getElementById('soReturnsTabItem')?.classList.toggle('d-none', isDraft);
    document.getElementById('soProformasTabItem')?.classList.toggle('d-none', !_pfEnabled);
    if (!isDraft) {
        refreshSalesOrderDeliveries(soDetails.id);
        refreshSalesOrderReturns(soDetails.id);
    }
    if (_pfEnabled) {
        refreshSalesOrderProformas(soDetails.id, soDetails.status);
    }

    // Page heading + doc number
    document.getElementById('soPageHeading').textContent = isOpenQuotation ? 'Quotation' : 'Sales Order';
    document.title = isOpenQuotation ? 'Quotation' : 'Sales Order';
    const docNumber = soDetails.so_number || soDetails.quotation_number;
    document.getElementById('soDocCode').textContent = docNumber ? `— #${docNumber}` : '';

    // Status badge + header edit slot
    const statusMap = {
        draft:                [isQuotationDoc ? 'Open' : 'Draft', 'warning'],
        confirmed:            ['Confirmed',         'primary'],
        cancelled:            ['Cancelled',         'danger'],
        partially_dispatched: ['Part. Dispatched',  'info'],
        dispatched:           ['Dispatched',        'info'],
        partially_delivered:  ['Part. Delivered',   'info'],
        delivered:            ['Delivered',         'success'],
    };
    renderDetailStatusBadge(
        document.getElementById('soStatusBadge'),
        document.getElementById('soHeaderEditBtnSlot'),
        statusMap,
        soStatus,
        { show: soStatus === 'draft' && canDo('sales_orders', 'write'), btnClass: 'so-action-btn', action: isQuotationDoc ? 'edit-quotation' : 'edit' }
    );
    const soSentBadgeEl = document.getElementById('soSentBadge');
    if (soSentBadgeEl) {
        soSentBadgeEl.innerHTML = (isOpenQuotation && soDetails.quote_sent)
            ? '<span class="badge bg-label-success align-middle ms-1">Sent</span>'
            : '';
    }

    // Customer subline
    const sublineEl = document.getElementById('soCustomerSubline');
    if (sublineEl) {
        sublineEl.innerHTML = soDetails.customer_name
            ? `<div class="d-flex align-items-center gap-2"><i class="icon-base bx bx-user icon-sm text-muted"></i><span class="fw-semibold">${soDetails.customer_name}</span></div>`
            : '';
    }

    // --- Delivery totals (used by KPI status, progress, and next step) ---
    const lineItems      = soDetails.line_items || [];
    const totalOrdered   = lineItems.reduce((s, i) => s + parseFloat(i.ordered_qty   || 0), 0);
    const totalDelivered = lineItems.reduce((s, i) => s + parseFloat(i.delivered_qty || 0), 0);
    const deliveryPct    = totalOrdered > 0 ? Math.round((totalDelivered / totalOrdered) * 100) : 0;
    const allDelivered   = lineItems.length > 0 && lineItems.every(i => parseFloat(i.delivered_qty || 0) >= parseFloat(i.ordered_qty || 0));

    // --- KPI Cards: build and inject (3 cards for quotation, 4 for order) ---
    let kpiStatusLabel, kpiStatusSub;
    if (isOpenQuotation) {
        kpiStatusLabel = 'Open';              kpiStatusSub = 'Pending customer approval';
    } else if (soStatus === 'draft') {
        kpiStatusLabel = 'Draft';             kpiStatusSub = 'Pending confirmation';
    } else if (soStatus === 'cancelled') {
        kpiStatusLabel = 'Cancelled';         kpiStatusSub = '';
    } else if (allDelivered || soStatus === 'delivered') {
        kpiStatusLabel = 'Delivered';         kpiStatusSub = 'All items delivered';
    } else if (soStatus === 'dispatched') {
        kpiStatusLabel = 'Dispatched';        kpiStatusSub = 'Awaiting delivery confirmation';
    } else if (soStatus === 'partially_dispatched') {
        kpiStatusLabel = 'Part. Dispatched';  kpiStatusSub = `${formatQty(totalDelivered)} of ${formatQty(totalOrdered)}`;
    } else if (soStatus === 'partially_delivered') {
        kpiStatusLabel = 'Part. Delivered';   kpiStatusSub = `${formatQty(totalDelivered)} of ${formatQty(totalOrdered)}`;
    } else {
        kpiStatusLabel = 'Confirmed';         kpiStatusSub = 'Ready to deliver';
    }

    const statusIconClass = (allDelivered || soStatus === 'delivered') ? 'bg-label-success' : 'bg-label-primary';
    const statusIconName  = (allDelivered || soStatus === 'delivered') ? 'bx-check-circle' : 'bx-file-blank';
    const col             = isOpenQuotation ? 'col-md-4' : 'col-md-3';
    const kpiCards        = [];

    // Card 1: Order/Quote Status (always)
    const kpiStatusCardLabel = isOpenQuotation ? 'Quote Status' : 'Order Status';
    const lastSentSpan = (isOpenQuotation && soDetails.quote_sent_at)
        ? ` <span class="fw-normal text-primary small">· Last sent ${formatMySqlDate(soDetails.quote_sent_at, window.sysDefaultConfig.dateFormat)}</span>`
        : '';
    kpiCards.push(`
        <div class="${col}"><div class="card h-100"><div class="card-body p-3">
            <div class="d-flex align-items-start gap-3">
                <div class="avatar avatar-sm flex-shrink-0"><span class="avatar-initial rounded ${statusIconClass}"><i class="icon-base bx ${statusIconName} icon-lg"></i></span></div>
                <div class="min-w-0 flex-grow-1">
                    <div class="detail-kpi-label">${kpiStatusCardLabel}</div>
                    <div class="fw-semibold">${kpiStatusLabel}${lastSentSpan}</div>
                    <small class="text-muted">${kpiStatusSub}</small>
                </div>
            </div>
        </div></div></div>`);

    // Card 2: Delivery Progress (Sales Order only — not applicable for quotation)
    if (!isOpenQuotation) {
        const pctBarClass = deliveryPct >= 100 ? 'bg-success' : 'bg-info';
        kpiCards.push(`
        <div class="${col}"><div class="card h-100"><div class="card-body p-3">
            <div class="d-flex align-items-start gap-3">
                <div class="avatar avatar-sm flex-shrink-0"><span class="avatar-initial rounded bg-label-warning"><i class="icon-base bx bx-package icon-lg"></i></span></div>
                <div class="flex-grow-1 min-w-0">
                    <div class="detail-kpi-label">Delivery Progress</div>
                    <div class="fw-semibold">${formatQty(totalDelivered)} / ${formatQty(totalOrdered)} delivered</div>
                    <div class="d-flex align-items-center gap-2">
                        <div class="progress flex-grow-1" style="height:6px;"><div class="progress-bar ${pctBarClass}" role="progressbar" style="width:${deliveryPct}%"></div></div>
                        <small class="text-muted flex-shrink-0">${deliveryPct}%</small>
                    </div>
                </div>
            </div>
        </div></div></div>`);
    }

    // Card 3: Valid Until (quotation) or Expected Delivery (order)
    const deliveryCardLabel = isOpenQuotation ? 'Valid Until' : 'Expected Delivery';
    const deliveryCardDate  = isOpenQuotation ? soDetails.valid_until : soDetails.expected_delivery_date;
    kpiCards.push(`
        <div class="${col}"><div class="card h-100"><div class="card-body p-3">
            <div class="d-flex align-items-start gap-3">
                <div class="avatar avatar-sm flex-shrink-0"><span class="avatar-initial rounded bg-label-info"><i class="icon-base bx bx-calendar icon-lg"></i></span></div>
                <div>
                    <div class="detail-kpi-label">${deliveryCardLabel}</div>
                    <div class="fw-semibold">${deliveryCardDate ? formatMySqlDate(deliveryCardDate) : '—'}</div>
                    <small>${soRelativeDate(deliveryCardDate)}</small>
                </div>
            </div>
        </div></div></div>`);

    // Card 4: Total Amount (always)
    const itemCountText = lineItems.length ? `${lineItems.length} item${lineItems.length > 1 ? 's' : ''}` : '';
    kpiCards.push(`
        <div class="${col}"><div class="card h-100"><div class="card-body p-3">
            <div class="d-flex align-items-start gap-3">
                <div class="avatar avatar-sm flex-shrink-0"><span class="avatar-initial rounded bg-label-success"><i class="icon-base bx bx-rupee icon-lg"></i></span></div>
                <div>
                    <div class="detail-kpi-label">Total Amount</div>
                    <div class="fw-semibold">${formatCurrency(parseFloat(soDetails.grand_total || 0))}</div>
                    <small class="text-muted">${itemCountText}</small>
                </div>
            </div>
        </div></div></div>`);

    document.getElementById('soKpiCards').innerHTML = kpiCards.join('');

    // --- Next Step (quotation and SO have distinct cycles) ---
    const quoteDraftStep = soDetails.quote_sent
        ? { icon: 'bx-clipboard-check', title: 'Confirm this quotation', desc: 'Customer has been notified. Confirm the order when ready to proceed.', action: 'confirmed', btnText: 'Confirm Order', btnClass: 'btn-success', actionBtnClass: 'so-action-btn' }
        : { icon: 'bx-send', title: 'Send this quotation', desc: 'Share the quotation with your customer for review and approval.', action: 'send_email', btnText: 'Send Quotation', btnClass: 'btn-success', actionBtnClass: 'so-action-btn' };
    const soSteps = {
        draft: isOpenQuotation ? quoteDraftStep : {
            icon: 'bx-clipboard-check',
            title: 'Confirm this order',
            desc: 'Review items and amounts, then confirm to proceed.',
            action: 'confirmed', btnText: 'Confirm Order', btnClass: 'btn-success', actionBtnClass: 'so-action-btn',
        },
        confirmed: {
            icon: 'bx-package',
            title: 'Create a delivery',
            desc: 'Order is confirmed. Create a delivery to dispatch items.',
            action: 'delivery', btnText: 'Create Delivery', btnClass: 'btn-primary', actionBtnClass: 'so-action-btn',
        },
        partially_dispatched: {
            icon: 'bx-package',
            title: 'Continue delivering',
            desc: 'Some items are still pending dispatch.',
            action: 'delivery', btnText: 'Create Delivery', btnClass: 'btn-primary', actionBtnClass: 'so-action-btn',
        },
        partially_delivered: {
            icon: 'bx-package',
            title: 'Continue delivering',
            desc: 'Some items are still pending delivery.',
            action: 'delivery', btnText: 'Create Delivery', btnClass: 'btn-primary', actionBtnClass: 'so-action-btn',
        },
    };
    const effectiveSoStatus = (allDelivered || ['delivered', 'dispatched', 'cancelled'].includes(soStatus)) ? '_done' : soStatus;
    renderDetailNextStep('soNextStepCard', soSteps, effectiveSoStatus);

    // --- Overview fields ---
    document.getElementById('soCustomer').textContent  = soDetails.customer_name || '—';
    document.getElementById('soReference').innerHTML   = soDetails.reference || '-';
    document.getElementById('paymentTerms').innerHTML  = soDetails.payment_terms || '-';
    document.getElementById('soNotes').innerHTML       = soDetails.notes || '-';

    const warehouseEl = document.getElementById('warehouse');
    if (warehouseEl) warehouseEl.innerHTML = soDetails.source_warehouse_name || '-';

    // Customer card — billing address, shipping address, place of supply
    const billAddr = soDetails.billing_address_snapshot ? JSON.parse(soDetails.billing_address_snapshot) : null;
    const shipAddr = soDetails.shipping_address_snapshot ? JSON.parse(soDetails.shipping_address_snapshot) : null;
    const billAddrEl = document.getElementById('soCustomerBillingAddr');
    if (billAddrEl && billAddr) billAddrEl.innerHTML = formatAddrHtml(billAddr);

    const soShippingAddrRow = document.getElementById('soShippingAddrRow');
    const soBillToCol       = document.getElementById('soBillToCol');
    const shipAddrEl        = document.getElementById('soCustomerShippingAddr');
    if (shipAddr && shipAddrEl) {
        shipAddrEl.innerHTML = formatAddrHtml(shipAddr);
        soShippingAddrRow?.classList.remove('d-none');
        soBillToCol?.classList.replace('col-12', 'col-6');
    } else {
        soShippingAddrRow?.classList.add('d-none');
        soBillToCol?.classList.replace('col-6', 'col-12');
    }

    const soCustomerPosRow = document.getElementById('soCustomerPosRow');
    if (soDetails.place_of_supply_name) {
        const posCode = soDetails.place_of_supply_code || '';
        document.getElementById('soCustomerPlaceOfSupply').textContent = posCode
            ? `${soDetails.place_of_supply_name} (${posCode})`
            : soDetails.place_of_supply_name;
        soCustomerPosRow?.classList.remove('d-none');
    } else {
        soCustomerPosRow?.classList.add('d-none');
    }

    // Delivery type label in customer card
    const deliveryTypeLabelEl = document.getElementById('soDeliveryTypeLabel');
    if (deliveryTypeLabelEl) deliveryTypeLabelEl.textContent = soDetails.delivery_type === 'ship' ? 'Delivery: Ship' : 'Delivery: Pickup';

    // Expected delivery

    const expDeliveryRow = document.getElementById('soExpDeliveryRow');
    if (soDetails.expected_delivery_date) {
        document.getElementById('soExpDelivery').innerHTML = formatMySqlDate(soDetails.expected_delivery_date);
        expDeliveryRow?.classList.remove('d-none');
    } else {
        expDeliveryRow?.classList.add('d-none');
    }

    // Internal Notes
    const soInternalNotesSection = document.getElementById('soInternalNotesSection');
    if (soDetails.internal_notes) {
        document.getElementById('soInternalNotes').innerHTML = soDetails.internal_notes;
        soInternalNotesSection?.classList.remove('d-none');
    } else {
        soInternalNotesSection?.classList.add('d-none');
    }

    // Terms & conditions — always visible as collapse toggle
    const termsHtml = isQuotationDoc && soStatus === 'draft' ? (soDetails.quotation_terms || '') : (soDetails.so_terms || '');
    document.getElementById('soTerms').innerHTML = termsHtml || '<em class="text-muted">No terms set.</em>';

    // Date fields
    const quoteDateRow   = document.getElementById('quoteDateRow');
    const orderDateRow   = document.getElementById('orderDateRow');
    const convertedAtRow = document.getElementById('convertedAtRow');

    if (isQuotationDoc) {
        quoteDateRow.classList.remove('d-none');
        document.getElementById('quoteDate').innerHTML = formatMySqlDate(soDetails.quote_date);
        if (soDetails.converted_at) {
            orderDateRow.classList.remove('d-none');
            document.getElementById('orderDate').innerHTML = formatMySqlDate(soDetails.order_date);
            convertedAtRow.classList.remove('d-none');
            document.getElementById('convertedAt').innerHTML = formatMySqlDate(soDetails.converted_at);
        } else {
            orderDateRow.classList.add('d-none');
            convertedAtRow.classList.add('d-none');
        }
    } else {
        quoteDateRow.classList.add('d-none');
        convertedAtRow.classList.add('d-none');
        orderDateRow.classList.remove('d-none');
        document.getElementById('orderDate').innerHTML = formatMySqlDate(soDetails.order_date);
    }

    // Lead row
    const leadRefRowEl = document.getElementById('leadRefRow');
    leadRefRowEl.classList.add('d-none');
    if (soDetails.lead_id) {
        document.getElementById('soLeadLink').innerHTML = `<a href="/crm/leads/${soDetails.lead_id}/" class="text-primary">${soDetails.lead_name || 'Lead #' + soDetails.lead_id}</a>`;
        leadRefRowEl.classList.remove('d-none');
    }

    // --- Items table ---
    const tbody = document.querySelector('#lineItemsTable tbody');
    tbody.innerHTML = '';
    const showDeliveryColumns = soStatus !== 'draft';
    document.getElementById('deliveredColHeader')?.classList.toggle('d-none', !showDeliveryColumns);
    document.getElementById('returnedColHeader')?.classList.toggle('d-none', !showDeliveryColumns);

    lineItems.forEach((item, idx) => {
        const uomCode      = item.uom_code || '';
        const discountAmt  = parseFloat(item.discount_amount || 0);
        const discDisplay  = discountAmt > 0 ? formatCurrency(discountAmt) : '—';
        const taxInfoArr   = Array.isArray(item.tax_info) ? item.tax_info : (typeof item.tax_info === 'string' && item.tax_info ? JSON.parse(item.tax_info) : []);
        const taxLabel     = taxInfoArr.map(t => t.name).filter(Boolean).join(', ') || '—';
        const deliveredQty = parseFloat(item.delivered_qty || 0);
        const returnedQty  = parseFloat(item.returned_qty  || 0);
        const colHidden    = showDeliveryColumns ? '' : 'd-none';

        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td class="ps-3 text-muted">${idx + 1}</td>
                <td>
                    <div class="fw-medium">${item.product_name}</div>
                    ${item.description ? `<small class="text-muted">${item.description}</small>` : ''}
                </td>
                <td class="text-end">${formatQty(item.ordered_qty)} <span class="fs-tiny fw-semibold">${uomCode}</span></td>
                <td class="text-end deliveredCell ${colHidden} ${deliveredQty > 0 ? '' : 'text-muted'}">${formatQty(deliveredQty)} <span class="fs-tiny fw-semibold">${uomCode}</span></td>
                <td class="text-end returnedCell ${colHidden} ${returnedQty > 0 ? 'text-danger' : 'text-muted'}">${formatQty(returnedQty)} <span class="fs-tiny fw-semibold">${returnedQty > 0 ? uomCode : ''}</span></td>
                <td class="text-end">${formatCurrency(item.unit_price)}</td>
                <td class="text-end">${discDisplay}</td>
                <td class="text-end">${taxLabel}</td>
                <td class="text-end fw-semibold pe-3">${formatCurrency(item.line_total)}</td>
            </tr>
        `);
    });

    // --- Totals ---
    const totalsTable      = document.getElementById('totalsTable');
    const itemDiscTotal    = parseFloat(soDetails.item_discount_total || 0);
    const orderDiscAmt     = parseFloat(soDetails.order_discount_amount || 0);
    const subAfterItemDisc = parseFloat(soDetails.subtotal_after_item_discount || 0);
    const taxAmt           = parseFloat(soDetails.tax_amount || 0);

    let totalsHtml = `
        <tr>
            <td class="ps-0 text-muted">Subtotal</td>
            <td class="text-end">${formatCurrency(soDetails.subtotal)}</td>
        </tr>`;
    if (itemDiscTotal > 0) {
        totalsHtml += `
        <tr>
            <td class="ps-0 text-muted">Item Discounts</td>
            <td class="text-end text-danger">−${formatCurrency(itemDiscTotal)}</td>
        </tr>
        <tr>
            <td class="ps-0 text-muted">Subtotal After Discount</td>
            <td class="text-end">${formatCurrency(subAfterItemDisc)}</td>
        </tr>`;
    }
    if (orderDiscAmt > 0) {
        totalsHtml += `
        <tr>
            <td class="ps-0 text-muted">Order Discount</td>
            <td class="text-end text-danger">−${formatCurrency(orderDiscAmt)}</td>
        </tr>`;
    }
    totalsHtml += `
        <tr>
            <td class="ps-0 text-muted">Tax</td>
            <td class="text-end">${formatCurrency(taxAmt)}</td>
        </tr>`;
    const ro = parseFloat(soDetails.round_off_amount || 0);
    if (ro !== 0) {
        const roSign  = ro < 0 ? '− ' : '+ ';
        const roClass = ro < 0 ? 'text-danger' : 'text-success';
        totalsHtml += `
        <tr>
            <td class="ps-0 text-muted">Round-off</td>
            <td class="text-end ${roClass}">${roSign}${formatCurrency(Math.abs(ro))}</td>
        </tr>`;
    }
    totalsHtml += `
        <tr class="border-top">
            <td class="ps-0 fw-semibold pt-2">Grand Total</td>
            <td class="text-end fw-bold pt-2">${formatCurrency(soDetails.grand_total)}</td>
        </tr>`;
    totalsTable.innerHTML = totalsHtml;

    // --- Action Buttons: Send + View (plain buttons or dropdown if Proforma enabled) + More ---
    let sendBtn = '';
    @if($tenantContext->canDo('sales_orders', 'send_email'))
    sendBtn = _pfEnabled
        ? `<div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="icon-base bx bx-envelope icon-sm me-1"></i>Send
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item so-action-btn" data-action="send_email" href="javascript:void(0)">${isOpenQuotation ? 'Quotation' : 'Sales Order'}</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="openProformaPicker('send')">Proforma Invoice</a></li>
            </ul>
           </div>`
        : `<button class="btn btn-outline-secondary btn-sm so-action-btn" data-action="send_email"><i class="icon-base bx bx-envelope icon-sm me-1"></i>Send</button>`;
    @endif

    const viewBtn = _pfEnabled
        ? `<div class="dropdown">
            <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="icon-base bx bx-show icon-sm me-1"></i>View
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li><a class="dropdown-item so-action-btn" data-action="pdf-view" href="javascript:void(0)">${isOpenQuotation ? 'Quotation' : 'Sales Order'}</a></li>
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="openProformaPicker('view')">Proforma Invoice</a></li>
            </ul>
           </div>`
        : `<button class="btn btn-outline-secondary btn-sm so-action-btn" data-action="pdf-view"><i class="icon-base bx bx-show icon-sm me-1"></i>View</button>`;

    const moreItems = [];
    if (soStatus === 'draft') {
        if (canDo('sales_orders', 'write')) {
            moreItems.push(`<li><a class="dropdown-item so-action-btn" data-action="${isQuotationDoc ? 'edit-quotation' : 'edit'}" href="javascript:void(0)"><i class="icon-base bx bx-edit icon-sm me-2"></i>Edit</a></li>`);
        }
        if (canDo('sales_orders', 'confirm')) {
            moreItems.push(`<li><a class="dropdown-item so-action-btn" data-action="confirmed" href="javascript:void(0)"><i class="icon-base bx bx-like icon-sm me-2"></i>Confirm Order</a></li>`);
        }
        @if($tenantContext->canDo('proforma_invoices', 'create') && Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId))
        moreItems.push(`<li><a class="dropdown-item" href="javascript:void(0)" onclick="openCreateProforma()"><i class="icon-base bx bx-receipt icon-sm me-2"></i>Create Proforma</a></li>`);
        @endif
        if (canDo('sales_orders', 'cancel')) {
            moreItems.push(`<li><hr class="dropdown-divider"></li>`);
            moreItems.push(`<li><a class="dropdown-item text-danger so-action-btn" data-action="cancel" href="javascript:void(0)"><i class="icon-base bx bx-x icon-sm me-2"></i>Cancel</a></li>`);
        }
    } else if (soStatus === 'confirmed') {
        if (canDo('sales_deliveries', 'write')) {
            moreItems.push(`<li><a class="dropdown-item so-action-btn" data-action="delivery" href="javascript:void(0)"><i class="icon-base bx bx-package icon-sm me-2"></i>Create Delivery</a></li>`);
        }
        @if($tenantContext->canDo('proforma_invoices', 'create') && Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId))
        moreItems.push(`<li><a class="dropdown-item" href="javascript:void(0)" onclick="openCreateProforma()"><i class="icon-base bx bx-receipt icon-sm me-2"></i>Create Proforma</a></li>`);
        @endif
        if (canDo('sales_orders', 'cancel')) {
            moreItems.push(`<li><hr class="dropdown-divider"></li>`);
            moreItems.push(`<li><a class="dropdown-item text-danger so-action-btn" data-action="cancel" href="javascript:void(0)"><i class="icon-base bx bx-x icon-sm me-2"></i>Cancel</a></li>`);
        }
    } else if (soStatus === 'partially_dispatched' || soStatus === 'partially_delivered') {
        if (canDo('sales_deliveries', 'write')) {
            moreItems.push(`<li><a class="dropdown-item so-action-btn" data-action="delivery" href="javascript:void(0)"><i class="icon-base bx bx-package icon-sm me-2"></i>Create Delivery</a></li>`);
        }
    }

    const hasReturnable = lineItems.some(item => (parseFloat(item.delivered_qty || 0) - parseFloat(item.returned_qty || 0)) > 0);
    if (hasReturnable && canDo('sales_returns', 'write')) {
        if (moreItems.length) moreItems.push(`<li><hr class="dropdown-divider"></li>`);
        moreItems.push(`<li><a class="dropdown-item so-action-btn" data-action="create-return" href="javascript:void(0)"><i class="icon-base bx bx-undo icon-sm me-2"></i>Customer Return</a></li>`);
    }

    const moreBtn = moreItems.length
        ? `<div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">More</button>
            <ul class="dropdown-menu dropdown-menu-end">${moreItems.join('')}</ul>
           </div>`
        : '';

    document.getElementById('actionButtons').innerHTML = `<div class="d-flex justify-content-lg-end gap-3">${sendBtn}${viewBtn}${moreBtn}</div>`;
}


const refreshSalesOrderDetails = async function(soId) {
    try {
        const response = await api.get(`/sales/orders/${soId}`);
        const { data } = response.data;
        renderSODetailsSection(data.so_details);
    } catch (error) {
        notyf.error("Unable to load sales order details");
    }
}


const formatSOFieldChange = function(oldVal, newVal, data={}) {
    
    if( oldVal == "" && newVal == "" ) return "";

    //console.log(data);

    const type = data.type || "";

    let html = '';
    if( oldVal ) {

        let oldValUomHtml = '';
        if( type === 'qty' ) {
            oldValUomHtml = ` <span class="fs-tiny fw-semibold">${data.oldUomCode || ""}</span>`;
        }

        html += `<span class="text-muted">${oldVal}${oldValUomHtml}</span>`;
        if( newVal ) {
            html += `<span class="mx-1 text-primary fw-semibold">→</span>`;
        }
    }

    if( newVal ) {
        
        let newValUomHtml = '';
        if( type === 'qty' ) {
            newValUomHtml = ` <span class="fs-tiny fw-semibold">${data.newUomCode || ""}</span>`;
        }

        html += `<span class="text-primary">${newVal}${newValUomHtml}</span>`;
    }

    return html;
}

const buildAttachmentList = function(attachments) {
    if (!attachments || !attachments.length) return '';
    const links = attachments.map(a => {
        const icon = a.is_image ? 'bx-image' : 'bx-file';
        const size = a.file_size > 1048576 ? (a.file_size / 1048576).toFixed(1) + ' MB' : Math.round(a.file_size / 1024) + ' KB';
        const isViewable = a.is_image || a.mime_type === 'application/pdf';
        const viewIcon = isViewable
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


const renderSOHistoryItemMeta = function(activityType, meta = {}) {

    if (!meta || typeof meta !== 'object') return '';

    let html = '';

    if (activityType === 'created') {
        html = `<ul class="mt-2 mb-2 ps-3 small">
            <li>Status: <strong class="text-primary">${ucFirst(meta.status || '')}</strong></li>
            <li>Customer: <strong class="text-primary">${meta.customer_name || '-'}</strong></li>
        </ul>`;
    }
    else if (activityType === 'updated_details') {
        html = `<ul class="mt-2 mb-2 ps-3 small">`;
        (Array.isArray(meta) ? meta : []).forEach(item => {
            if (item.field === 'so_terms' || item.field === 'quotation_terms') return; // rendered separately below
            let oldVal = item.old_val || '';
            let newVal = item.new_val || '';
            if (['order_date', 'expected_delivery_date'].includes(item.field)) {
                if (oldVal) oldVal = formatMySqlDate(oldVal);
                if (newVal) newVal = formatMySqlDate(newVal);
            }
            const formattedHtml = formatSOFieldChange(oldVal, newVal);
            if (formattedHtml) {
                html += `<li>${item.label}: <strong class="text-primary">${formattedHtml}</strong></li>`;
            }
        });
        html += `</ul>`;

        // T&C — render at the end as labeled content blocks
        const soTermsChange = (Array.isArray(meta) ? meta : []).find(item => item.field === 'so_terms' || item.field === 'quotation_terms');
        if (soTermsChange) {
            html += `<div class="small mt-2 fw-semibold text-muted">${soTermsChange.label}</div>`;
            if (soTermsChange.new_val) {
                html += `<div class="small text-muted mb-1">New Terms:</div>
                         <div class="small border rounded p-2 mb-2 bg-light">${soTermsChange.new_val}</div>`;
            }
            if (soTermsChange.old_val) {
                html += `<div class="small text-muted mb-1">Old Terms:</div>
                         <div class="small border rounded p-2 mb-2 bg-light text-muted">${soTermsChange.old_val}</div>`;
            }
        }
    }
    else if (activityType === 'updated_line_items') {
        (Array.isArray(meta) ? meta : []).forEach(item => {
            html += `<div class="small mb-1">
                <strong>${item.prod_name}</strong>
                ${item.event === 'deleted' ? `<span class="badge bg-label-danger ms-1 p-1">Delete</span>` : ''}
                ${item.event === 'created' ? `<span class="badge bg-label-success ms-1 p-1">Add</span>` : ''}
                ${item.event === 'updated' ? `<span class="badge bg-label-warning ms-1 p-1">Update</span>` : ''}
            </div>`;
            html += `<ul class="mt-2 mb-2 ps-7 small">`;
            if (item.event === 'created') {
                html += `<li class="ps-0">Qty: <span class="text-primary">${item.new_qty} <span class="fs-tiny fw-semibold">${item.new_uom || ''}</span></span></li>`;
                html += `<li class="ps-0">Unit Price: <span class="text-primary">${item.new_unit_price}</span></li>`;
                if (item.new_discount) html += `<li class="ps-0">Discount: <span class="text-primary">${item.new_discount}</span></li>`;
            } else if (item.event === 'deleted') {
                html += `<li class="ps-0">Qty: <span class="text-danger">${item.old_qty} <span class="fs-tiny fw-semibold">${item.old_uom || ''}</span></span></li>`;
            } else {
                if (item.old_qty != item.new_qty) {
                    html += `<li class="ps-0">Qty: ${formatSOFieldChange(item.old_qty, item.new_qty)}</li>`;
                }
                if (item.old_unit_price != item.new_unit_price) {
                    html += `<li class="ps-0">Unit Price: ${formatSOFieldChange(item.old_unit_price, item.new_unit_price)}</li>`;
                }
                if (item.old_discount !== item.new_discount) {
                    html += `<li class="ps-0">Discount: ${formatSOFieldChange(item.old_discount || 'None', item.new_discount || 'None')}</li>`;
                }
            }
            html += `</ul>`;
        });
    }
    else if (activityType === 'status_changed') {
        html += `<ul class="mt-2 mb-2 ps-7 small">
            <li class="ps-0">${formatSOFieldChange(ucFirst(meta.old_status || ''), ucFirst(meta.new_status || ''))}</li>
        </ul>`;
        if (meta.notes) {
            html += `<div class="small text-muted ps-7">${meta.notes}</div>`;
        }
        html += buildAttachmentList(meta.attachments || []);
    }
    else if (activityType === 'dn_created') {
        html = `<ul class="mt-2 mb-2 ps-3 small">
            <li>Status: <strong class="text-primary">${ucFirst(meta.dn_status || '')}</strong></li>
        </ul>`;
    }
    else if (activityType === 'dn_status_changed') {
        html += `<ul class="mt-2 mb-2 ps-7 small">
            <li class="ps-0">${formatSOFieldChange(ucFirst(meta.old_status || ''), ucFirst(meta.new_status || ''))}</li>
        </ul>`;
    }
    else if (activityType === 'return_created') {
        html = `<ul class="mt-2 mb-2 ps-3 small">
            <li>Status: <strong class="text-primary">${ucFirst((meta.return_status || 'draft').replace('_', ' '))}</strong></li>
        </ul>`;
    }
    else if (activityType === 'return_status_changed') {
        html = `<ul class="mt-2 mb-2 ps-3 small">
            <li>Status: <strong class="text-primary">${ucFirst((meta.new_status || '').replace('_', ' '))}</strong></li>
        </ul>`;
    }
    else if (activityType === 'email_sent') {
        html = '<ul class="mt-2 mb-2 ps-3 small">';
        if (meta.from)    html += `<li>From: <strong class="text-primary">${meta.from}</strong></li>`;
        html += `<li>To: <strong class="text-primary">${meta.to || '-'}</strong></li>`;
        if (meta.cc)      html += `<li>CC: <strong class="text-primary">${meta.cc}</strong></li>`;
        if (meta.bcc)     html += `<li>BCC: <strong class="text-primary">${meta.bcc}</strong></li>`;
        html += `<li>Subject: <strong class="text-primary">${meta.subject || '-'}</strong></li>`;
        html += '</ul>';
        html += buildAttachmentList(meta.attachments || []);
    }

    return html;
}


const renderSalesOrderHistory = function(history = []) {
    renderDetailTimeline('soHistoryTimeline', history, renderSOHistoryItemMeta);
}


const refreshSalesOrderHistory = async function(soId) {
    try {
        const response = await api.get(`/sales/orders/${soId}/history`);
        const { data } = response.data;
        renderSalesOrderHistory(data);
    } catch (error) {
        notyf.error("Unable to load sales order history");
    }
}


const updateSalesOrderStatus = async function(soId, newStatus, notes = '', acknowledgedWarning = false, acknowledgedDraftDns = false) {
    try {
        const payload = { status: newStatus, notes };
        if (acknowledgedWarning) payload.acknowledged_warning = true;
        if (acknowledgedDraftDns) payload.acknowledged_draft_dns = true;

        const response = await api.post(`/sales/orders/${soId}/status`, payload);
        const { status: responseStatus, warnings, warning_type } = response.data;

        if (responseStatus === 'warning') {
            if (warning_type === 'draft_dns') {
                const dnList = warnings.map(num => `<li>${num}</li>`).join('');
                const html = `Cancelling this order will also cancel the following draft delivery notes:<ul>${dnList}</ul>`;
                showConfirmation(
                    html,
                    'warning',
                    { text: 'Yes, Cancel All', class: 'btn-danger', callback: () => updateSalesOrderStatus(soId, 'cancelled', notes, false, true) },
                    { text: 'No, Keep' },
                    { width: '25em', htmlContainer: 'swal-warning' }
                );
                return;
            }
            const listItems = warnings.map(w => `<li>${w}</li>`).join('');
            const html = `<strong>Stock may be insufficient for some items:</strong><ul>${listItems}</ul><p class="fw-semibold text-muted mt-2 mb-0"><small>The order can still be confirmed and fulfilled once stock arrives.</small></p>`;
            showConfirmation(
                html,
                'warning',
                { text: 'Save as Confirmed', class: 'btn-info', callback: () => updateSalesOrderStatus(soId, "confirmed", notes, true) },
                { text: 'Cancel' },
                { width: '40em', htmlContainer: 'swal-warning' }
            );
            return;
        }

        let message = 'Status updated successfully';
        if (newStatus === 'confirmed') message = 'Sales order confirmed successfully';
        if (newStatus === 'cancelled') message = 'Sales order cancelled';
        notyf.success(message);
        refreshSalesOrderDetails(soId);
        refreshSalesOrderHistory(soId);
    } catch (error) {
        handleApiError(error);
    }
}


document.addEventListener('DOMContentLoaded', async () => {

    const soId = "{{ request()->getInput('id') ?? '' }}";
    if (!soId) return;

    refreshSalesOrderDetails(soId);
    refreshSalesOrderHistory(soId);

    // Tab switching (pane IDs = 'so' + capitalize(data-so-tab) + 'Pane')
    document.querySelectorAll('[data-so-tab]').forEach(btn => {
        btn.addEventListener('click', function() {
            document.querySelectorAll('[data-so-tab]').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.detail-tab-pane').forEach(p => p.classList.add('d-none'));
            this.classList.add('active');
            const tabName = this.dataset.soTab;
            const pane = document.getElementById('so' + tabName.charAt(0).toUpperCase() + tabName.slice(1) + 'Pane');
            if (pane) pane.classList.remove('d-none');
        });
    });
});


const soActionHandlers = {
    'edit': (soId) => openSalesOrderFormDrawer(soId),
    'confirmed': (soId) => {
        showConfirmation(
            'Confirmed order cannot be edited. It can be cancelled and recreated if changes are needed.',
            'question',
            { text: 'Confirm', class: 'btn-primary', callback: () => updateSalesOrderStatus(soId, 'confirmed') },
            { text: 'Cancel' }
        );
    },
    'cancel': (soId) => {
        showConfirmation(
            'This action is permanent and cannot be undone.',
            'warning',
            { text: 'Yes, Cancel It', class: 'btn-danger', callback: () => updateSalesOrderStatus(soId, 'cancelled') },
            { text: 'Cancel' }
        );
    },
    'instant-deliver': (soId) => {
        const isDraft = _soDetails?.status === 'draft';
        const message = isDraft
            ? 'This will confirm the order and mark all items as delivered immediately. Stock will be deducted. This cannot be undone.'
            : 'This will mark all items as delivered immediately. Stock will be deducted. This cannot be undone.';
        showConfirmation(
            message,
            'question',
            { text: isDraft ? 'Confirm & Deliver' : 'Mark Delivered', class: 'btn-success', callback: () => updateSalesOrderStatus(soId, 'delivered') },
            { text: 'Cancel' }
        );
    },
    'delivery': (soId) => openDeliveryFormDrawer(0, soId),
    'pdf-view': (soId) => openPdfViewer(`/sales/orders/${soId}/pdf`, `#${_soDetails?.so_number || _soDetails?.quotation_number || ''}`),
    'send_email': async (soId) => {
        const btn = document.querySelector('.so-action-btn[data-action="send_email"]');
        setButtonLoading(btn, true, 'Generating PDF…');
        try {
            const res = await api.get(`/sales/orders/${soId}/generate-email-pdf`);
            openEmailComposer(soId, [res.data.data]);
        } catch (err) {
            const msg = err?.response?.data?.message || 'Failed to generate PDF. Please try again.';
            notyf.error(msg);
        } finally {
            setButtonLoading(btn, false);
        }
    },
    'edit-quotation':   (soId) => openSalesOrderFormDrawer(parseInt(soId), {mode: 'lead_quotation', leadId: 0}),
    'create-return':    (soId) => openSalesReturnFormDrawer(0, parseInt(soId)),
};


document.addEventListener('click', function(e) {
    const btn = e.target.closest('.so-action-btn');
    if (!btn) return;
    const soId = "{{ request()->getInput('id') ?? '' }}";
    if (!soId) return;
    const action = btn.dataset.action;
    if (soActionHandlers[action]) {
        soActionHandlers[action](soId);
    }
});


// After the drawer saves (create/edit), refresh the page details
document.addEventListener('salesOrderFormSaved', function(e) {
    const soId = e.detail.soId || "{{ request()->getInput('id') ?? '' }}";
    if (!soId) return;
    refreshSalesOrderDetails(soId);
    refreshSalesOrderHistory(soId);
});

// After a delivery is saved from the drawer, refresh SO details and deliveries tab
document.addEventListener('deliveryFormSaved', function(e) {
    const soId = "{{ request()->getInput('id') ?? '' }}";
    if (!soId) return;
    refreshSalesOrderDetails(soId);
    refreshSalesOrderDeliveries(soId);
});

// After a return is saved from the drawer, refresh SO details, returns tab, and history
document.addEventListener('returnFormSaved', function(e) {
    const soId = "{{ request()->getInput('id') ?? '' }}";
    if (!soId) return;
    refreshSalesOrderDetails(soId);
    refreshSalesOrderReturns(soId);
    refreshSalesOrderHistory(soId);
});



// ─── Email Composer ───────────────────────────────────────────────────────────

let _joditInstance      = null;
let _emailComposerModal = null;
let _emailDefaultBody   = '';
let _emailSoId          = null;
let _emailMode          = 'sales_order';   // 'sales_order' | 'proforma_invoice'
let _emailDocId         = null;
let _attachedFiles      = [];   // [{name, mime_type, content}]

const renderEmailAttachmentChips = function() {
    const container = document.getElementById('emailAttachmentsList');
    container.innerHTML = '';
    _attachedFiles.forEach((file, index) => {
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

const openEmailComposer = async function(soId, preAttachments = []) {
    _emailMode  = 'sales_order';
    _emailDocId = soId;
    _emailSoId  = soId;
    const so    = _soDetails || {};

    cleanFormInputFeedback(document.getElementById('emailComposerForm'));

    const isOpenQuotation = so.origin_type === 'quotation' && so.status === 'draft';
    document.getElementById('emailComposerModalTitle').textContent = isOpenQuotation ? 'Send Quotation' : 'Send Sales Order';

    document.getElementById('emailTo').value  = so.customer_email || '';
    document.getElementById('emailCc').value  = '';
    document.getElementById('emailBcc').value = '';

    const docType = isOpenQuotation ? 'quotation' : 'sales_order';
    try {
        const res = await api.get(`/sales/orders/${soId}/email-defaults`);
        const defaults = res.data?.data || {};
        document.getElementById('emailSubject').value = defaults.subject || '';
        if (defaults.cc)  document.getElementById('emailCc').value  = defaults.cc;
        if (defaults.bcc) document.getElementById('emailBcc').value = defaults.bcc;
        _emailDefaultBody = defaults.body || '';
    } catch (_) {
        document.getElementById('emailSubject').value = '';
        _emailDefaultBody = '';
    }

    _attachedFiles = preAttachments;
    renderEmailAttachmentChips();

    if (_joditInstance) {
        _joditInstance.destruct();
        _joditInstance = null;
    }

    _emailComposerModal.show();
};

const openPfEmailComposer = async function(pfId, preAttachments = []) {
    _emailMode  = 'proforma_invoice';
    _emailDocId = pfId;

    cleanFormInputFeedback(document.getElementById('emailComposerForm'));
    document.getElementById('emailComposerModalTitle').textContent = 'Send Proforma Invoice';

    document.getElementById('emailTo').value  = _soDetails?.customer_email || '';
    document.getElementById('emailCc').value  = '';
    document.getElementById('emailBcc').value = '';

    try {
        const res      = await api.get(`/sales/proforma-invoices/${pfId}/email-defaults`);
        const defaults = res.data?.data || {};
        document.getElementById('emailSubject').value = defaults.subject || '';
        if (defaults.cc)  document.getElementById('emailCc').value  = defaults.cc;
        if (defaults.bcc) document.getElementById('emailBcc').value = defaults.bcc;
        _emailDefaultBody = defaults.body || '';
    } catch (_) {
        document.getElementById('emailSubject').value = '';
        _emailDefaultBody = '';
    }

    _attachedFiles = preAttachments;
    renderEmailAttachmentChips();

    if (_joditInstance) {
        _joditInstance.destruct();
        _joditInstance = null;
    }

    _emailComposerModal.show();
};

const handleSendEmail = async function() {
    const sendBtn = document.getElementById('sendEmailSubmitBtn');
    const form    = document.getElementById('emailComposerForm');

    cleanFormInputFeedback(form);

    const to      = document.getElementById('emailTo').value.trim();
    const cc      = document.getElementById('emailCc').value.trim();
    const bcc     = document.getElementById('emailBcc').value.trim();
    const subject = document.getElementById('emailSubject').value.trim();

    const body = _joditInstance ? _joditInstance.value : '';

    setButtonLoading(sendBtn, true);
    try {
        const endpoint = _emailMode === 'proforma_invoice'
            ? `/sales/proforma-invoices/${_emailDocId}/send-email`
            : `/sales/orders/${_emailDocId}/send-email`;

        await api.post(endpoint, { to, cc, bcc, subject, body, attachments: _attachedFiles });
        notyf.success('Email sent successfully');
        _emailComposerModal.hide();

        if (_emailMode === 'proforma_invoice') {
            if (_soDetails) {
                refreshSalesOrderProformas(_soDetails.id, _soDetails.status);
                refreshSalesOrderHistory(_soDetails.id);
            }
        } else {
            refreshSalesOrderHistory(_emailDocId);
        }
    } catch (error) {
        handleApiError(error, form);
    } finally {
        setButtonLoading(sendBtn, false);
    }
};

@if($tenantContext->canDo('sales_orders', 'send_email'))
document.addEventListener('DOMContentLoaded', function() {
    // Static backdrop — only close via the × button
    _emailComposerModal = new bootstrap.Modal(document.getElementById('emailComposerModal'), {
        backdrop: 'static',
        keyboard: false,
        focus: false,
    });

    // Init Jodit after modal finishes opening (so dimensions are correct)
    document.getElementById('emailComposerModal').addEventListener('shown.bs.modal', function() {
        if (_joditInstance) { _joditInstance.destruct(); _joditInstance = null; }
        _joditInstance = Jodit.make('#emailBody', {
            height: 300,
            enter: 'BR',
            buttons: 'bold,italic,underline,strikethrough,|,ul,ol,|,paragraph,|,link,image',
            toolbarAdaptive: false,
            showCharsCounter: false,
            showWordsCounter: false,
            showXPathInStatusbar: false,
            addNewLine: false,
        });
        _joditInstance.value = _emailDefaultBody;
    });

    // Paperclip button → trigger hidden file input
    document.getElementById('attachFilesBtn').addEventListener('click', function() {
        document.getElementById('emailAttachments').click();
    });

    // When files are selected, read as base64 and add chips
    document.getElementById('emailAttachments').addEventListener('change', async function() {
        if (!this.files.length) return;
        const newFiles = await readFilesAsBase64(this);
        _attachedFiles.push(...newFiles);
        renderEmailAttachmentChips();
        this.value = ''; // Reset so the same file can be re-selected
    });

    // Remove chip on × click
    document.getElementById('emailAttachmentsList').addEventListener('click', function(e) {
        const btn = e.target.closest('[data-attach-index]');
        if (!btn) return;
        _attachedFiles.splice(parseInt(btn.dataset.attachIndex), 1);
        renderEmailAttachmentChips();
    });

    document.getElementById('sendEmailSubmitBtn').addEventListener('click', handleSendEmail);
});
@endif

</script>
@endpush
