@extends('layouts.app')
@section('title', 'Sales Order')

@section('content')

<?php
$tenantContext = tenantContext();
?>

<!-- Content -->
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0"><span id="soPageHeading">Sales Order</span> <span class="text-muted fw-normal fs-5" id="soDocCode"></span></h4>
    </div>

    <div id="actionButtons"></div>

    <div class="row g-4">
        <div class="col-lg-8">

            @if($tenantContext->canAccess('sales_deliveries') || $tenantContext->canAccess('sales_returns') || ($tenantContext->canAccess('proforma_invoices') && Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId)))
            <div class="card mb-4 d-none" id="soDocumentsCard">
                <div class="card-header py-0">
                    <div class="d-flex align-items-stretch">
                        <ul class="nav nav-tabs flex-shrink-0 gap-4" role="tablist">
                            @if($tenantContext->canAccess('sales_deliveries'))
                            <li class="nav-item">
                                <button class="nav-link doc-tab px-0 so-deliveries-tab" data-bs-target="#soDeliveriesTab" type="button">Deliveries <span class="badge bg-label-primary ms-1">0</span></button>
                            </li>
                            @endif
                            @if($tenantContext->canAccess('sales_returns'))
                            <li class="nav-item">
                                <button class="nav-link doc-tab px-0 so-returns-tab" data-bs-target="#soReturnsTab" type="button">Returns <span class="badge bg-label-warning ms-1">0</span></button>
                            </li>
                            @endif
                            @if($tenantContext->canAccess('proforma_invoices') && Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId))
                            <li class="nav-item">
                                <button class="nav-link doc-tab px-0 so-proformas-tab" data-bs-target="#soProformasTab" type="button">Proforma Invoices <span class="badge bg-label-secondary ms-1">0</span></button>
                            </li>
                            @endif
                        </ul>
                        <button class="accordion-toggle flex-grow-1 px-0 border-0 bg-transparent text-end" type="button" aria-label="Toggle">
                            <i class="bx bx-chevron-down fs-4"></i>
                        </button>
                    </div>
                </div>                

                <div id="soDocuments" class="accordion-collapse collapse">
                    <div class="card-body">
                        <div class="tab-content px-0">
                            @if($tenantContext->canAccess('sales_deliveries'))
                            <div class="tab-pane fade" id="soDeliveriesTab">
                                <div class="table-responsive">
                                    <table class="table m-0" id="soDeliveriesTable">
                                        <thead>
                                            <tr>
                                                <th>DN#</th>
                                                <th>Warehouse</th>
                                                <th>Status</th>
                                                <th>Dispatch Date</th>
                                                <th>Delivery Date</th>
                                                <th class="text-end">Items</th>
                                                <th>Created By</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                            @if($tenantContext->canAccess('sales_returns'))
                            <div class="tab-pane fade" id="soReturnsTab">
                                <div class="table-responsive">
                                    <table class="table m-0" id="soReturnsTable">
                                        <thead>
                                            <tr>
                                                <th>Return #</th>
                                                <th>Status</th>
                                                <th>Return Date</th>
                                                <th class="text-end">Items</th>
                                                <th>Created By</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                            @if($tenantContext->canAccess('proforma_invoices') && Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId))
                            <div class="tab-pane fade" id="soProformasTab">
                                <div class="table-responsive">
                                    <table class="table m-0" id="soProformasTable">
                                        <thead>
                                            <tr>
                                                <th>Proforma #</th>
                                                <th>Date</th>
                                                <th>Status</th>
                                                <th class="text-end">Total</th>
                                                <th>Created By</th>
                                                <th></th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <div class="card" id="soDetails">
                <div class="card-body">
                    <div class="d-flex justify-content-end mb-4">
                        <div class="d-flex gap-2" id="soBadges"></div>
                    </div>

                    <div class="row g-3 mb-4">
                        @if(Service_CompanySettings::isMultiWarehouseEnabled(tenantContext()->companyId))
                        <div class="col-md-4">
                            <h6 class="mb-0">Warehouse</h6>
                            <p class="mb-0" id="warehouse">-</p>
                        </div>
                        @endif
                        <div class="col-md-4">
                            <h6 class="mb-0">Customer</h6>
                            <p class="mb-0" id="soCustomer">-</p>
                        </div>
                        <div class="col-md-4 d-none" id="quoteDateRow">
                            <h6 class="mb-0">Quote Date</h6>
                            <p class="mb-0" id="quoteDate">-</p>
                        </div>
                        <div class="col-md-4 d-none" id="validUntilRow">
                            <h6 class="mb-0">Valid Until</h6>
                            <p class="mb-0" id="validUntil">-</p>
                        </div>
                        <div class="col-md-4" id="orderDateRow">
                            <h6 class="mb-0">Order Date</h6>
                            <p class="mb-0" id="orderDate">-</p>
                        </div>
                        <div class="col-md-4 d-none" id="convertedAtRow">
                            <h6 class="mb-0">Converted On</h6>
                            <p class="mb-0" id="convertedAt">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Expected Delivery</h6>
                            <p class="mb-0" id="expectedDate">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Reference</h6>
                            <p class="mb-0" id="soReference">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Payment Terms</h6>
                            <p class="mb-0" id="paymentTerms">-</p>
                        </div>
                        <div class="col-md-4 d-none" id="leadRefRow">
                            <h6 class="mb-0">Lead Ref#</h6>
                            <p class="mb-0" id="soLeadLink">-</p>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h6 class="mb-0">Notes</h6>
                        <p class="mb-0" id="soNotes">-</p>
                    </div>

                    <div class="mb-8 d-none" id="soTermsWrap">
                        <div class="d-flex align-items-center gap-1 mb-1" role="button"
                             data-bs-toggle="collapse" data-bs-target="#soTermsContent" aria-expanded="false">
                            <h6 class="mb-0">Terms &amp; Conditions</h6>
                            <i class="bx bx-chevron-down fs-5 text-secondary"></i>
                        </div>
                        <div class="collapse" id="soTermsContent">
                            <div class="small" id="soTerms"></div>
                        </div>
                    </div>

                    <div class="table-responsive border border-bottom-0 border-top-0 rounded">
                        <table class="table m-0" id="lineItemsTable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Ordered</th>
                                    <th class="text-end d-none" id="deliveredColHeader">Delivered</th>
                                    <th class="text-end d-none" id="returnedColHeader">Returned</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody><tr><td colspan="8" class="text-center">No data</td></tr></tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end pt-4">
                        <table class="table table-borderless w-auto mb-0" id="totalsTable">
                            <tr>
                                <th class="ps-0 text-muted w-px-300">Subtotal</th>
                                <td class="px-0 text-end">₹0.00</td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted w-px-300">Discount</th>
                                <td class="px-0 text-end">₹0.00</td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted w-px-300">Tax</th>
                                <td class="px-0 text-end">₹0.00</td>
                            </tr>
                            <tr class="border-top">
                                <th class="ps-0 w-px-300">Total</th>
                                <td class="px-0 text-end fw-bold">₹0.00</td>
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
<!-- / Content -->

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
    draft: ['Draft', 'secondary'],
    dispatched: ['Dispatched', 'primary'],
    delivered: ['Delivered',  'success'],
    returned: ['Returned', 'warning'],
    lost: ['Lost', 'danger'],
    cancelled:  ['Cancelled',  'secondary'],
};

const refreshSalesOrderDeliveries = async function(soId) {

    @if(!$tenantContext->canAccess('sales_deliveries'))
    return;
    @endif


    try {
        const response = await api.get('/sales/deliveries', { params: { so_id: soId } });
        const { data } = response.data;

        const tbody = document.querySelector('#soDocumentsCard #soDeliveriesTable tbody');
        const badge = document.querySelector('#soDocumentsCard .so-deliveries-tab .badge');

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

        const tbody = document.querySelector('#soDocumentsCard #soReturnsTable tbody');
        const badge = document.querySelector('#soDocumentsCard .so-returns-tab .badge');

        tbody.innerHTML = '';
        badge.innerHTML = '0';

        if (!data || data.length === 0) {
            tbody.innerHTML = `<tr><td colspan="6" class="text-center text-muted py-3">No returns found</td></tr>`;
            return;
        }

        badge.innerHTML = data.length;

        const retStatusMap = {
            draft:      ['Draft',      'secondary'],
            in_transit: ['In Transit', 'warning'],
            received:   ['Received',   'success'],
            cancelled:  ['Cancelled',  'dark'],
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

        const tbody = document.querySelector('#soDocumentsCard #soProformasTable tbody');
        const badge = document.querySelector('#soDocumentsCard .so-proformas-tab .badge');

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
            cancelled: ['Cancelled', 'secondary'],
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


const _recalcPfTotals = function(ctx) {
    const rows = document.querySelectorAll('#pfDrawerItemsBody tr[data-item-idx]');
    let subtotal = 0, taxAmt = 0, discTotal = 0, lineTotal = 0;

    rows.forEach(row => {
        const idx      = parseInt(row.dataset.itemIdx, 10);
        const orig     = ctx.items[idx];
        const qtyInput = row.querySelector('.pf-qty-input');
        const newQty   = parseFloat(unformatNumber(qtyInput?.value)) || 0;
        const origQty  = parseFloat(orig.quantity) || 1;
        const ratio    = origQty !== 0 ? newQty / origQty : 0;

        const itemSubtotal = parseFloat(orig.unit_price) * newQty;
        const itemDisc     = parseFloat(orig.discount_amount || 0) * ratio;
        const itemTax      = parseFloat(orig.tax_amount || 0) * ratio;
        const itemTotal    = parseFloat(orig.line_total) * ratio;

        subtotal  += itemSubtotal;
        discTotal += itemDisc;
        taxAmt    += itemTax;
        lineTotal += itemTotal;

        row.querySelector('.pf-cell-amount').textContent = formatCurrency(itemTotal);
    });

    const roundOff = parseFloat(ctx.round_off_amount || 0);
    const grandTotal = lineTotal + roundOff;

    document.getElementById('pfDSubtotal').textContent   = formatCurrency(subtotal);
    document.getElementById('pfDTax').textContent        = formatCurrency(taxAmt);
    document.getElementById('pfDGrandTotal').textContent = formatCurrency(grandTotal);

    document.getElementById('pfDDiscountRow').classList.toggle('d-none', discTotal <= 0);
    if (discTotal > 0) document.getElementById('pfDDiscount').textContent = '- ' + formatCurrency(discTotal);

    document.getElementById('pfDRoundOffRow').classList.toggle('d-none', roundOff === 0);
    if (roundOff !== 0) document.getElementById('pfDRoundOff').textContent = (roundOff < 0 ? '− ' : '+ ') + formatCurrency(Math.abs(roundOff));
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
                const discAmt  = parseFloat(item.discount_amount || 0);
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
                        <input type="text" class="px-1 form-control text-end ms-auto pf-qty-input" style="width:90px;"
                               value="${formatQty(item.quantity)}" placeholder="0"
                               oninput="_recalcPfTotals(JSON.parse(document.getElementById('addProformaForm').dataset.ctx))" />
                        ${item.uom_code ? `<span class="fs-tiny mt-1 d-block text-primary fw-semibold">UOM: ${item.uom_code}</span>` : ''}
                    </td>
                    <td class="p-2 text-end">${formatCurrency(item.unit_price)}</td>
                    <td class="p-2 text-end">${discAmt > 0 ? formatCurrency(discAmt) : '—'}</td>
                    <td class="p-2">${taxLabel}</td>
                    <td class="p-2 text-end fw-semibold pf-cell-amount">${formatCurrency(item.line_total)}</td>
                </tr>`;
            });
            tbody.innerHTML = html;
        }

        // Initial totals
        const discTotal = parseFloat(ctx.discount_total || 0);
        const roundOff  = parseFloat(ctx.round_off_amount || 0);
        document.getElementById('pfDSubtotal').textContent   = formatCurrency(ctx.subtotal);
        document.getElementById('pfDTax').textContent        = formatCurrency(ctx.tax_amount);
        document.getElementById('pfDGrandTotal').textContent = formatCurrency(ctx.grand_total);
        document.getElementById('pfDDiscountRow').classList.toggle('d-none', discTotal <= 0);
        if (discTotal > 0) document.getElementById('pfDDiscount').textContent = '- ' + formatCurrency(discTotal);
        document.getElementById('pfDRoundOffRow').classList.toggle('d-none', roundOff === 0);
        if (roundOff !== 0) document.getElementById('pfDRoundOff').textContent = (roundOff < 0 ? '− ' : '+ ') + formatCurrency(Math.abs(roundOff));

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


document.getElementById('pfSaveBtn')?.addEventListener('click', async function() {
    const form = document.getElementById('addProformaForm');
    const ctx  = JSON.parse(form.dataset.ctx || '{}');

    // Build items from DOM — proportional recalc on qty change
    const rows = document.querySelectorAll('#pfDrawerItemsBody tr[data-item-idx]');
    let subtotal = 0, taxAmt = 0, discItemTotal = 0, grandLineTotal = 0;
    const items = Array.from(rows).map(row => {
        const idx    = parseInt(row.dataset.itemIdx, 10);
        const orig   = ctx.items[idx];
        const newQty = parseFloat(unformatNumber(row.querySelector('.pf-qty-input')?.value)) || 0;
        const origQty = parseFloat(orig.quantity) || 1;
        const ratio  = origQty !== 0 ? newQty / origQty : 0;

        const taxableAmt = parseFloat(orig.taxable_amount || 0) * ratio;
        const itemTax    = parseFloat(orig.tax_amount || 0) * ratio;
        const discAmt    = parseFloat(orig.discount_amount || 0) * ratio;
        const lineTotal  = parseFloat(orig.line_total) * ratio;

        subtotal      += parseFloat(orig.unit_price) * newQty;
        taxAmt        += itemTax;
        discItemTotal += discAmt;
        grandLineTotal += lineTotal;

        return {
            sales_order_item_id:    orig.sales_order_item_id,
            product_id:             orig.product_id,
            product_name:           orig.product_name,
            sku:                    orig.sku,
            description:            orig.description,
            quantity:               newQty,
            unit_price:             parseFloat(orig.unit_price),
            discount_amount:        discAmt,
            discount_info:          orig.discount_info,
            taxable_amount:         taxableAmt,
            tax_amount:             itemTax,
            tax_info:               orig.tax_info,
            line_total:             lineTotal,
            uom_code:               orig.uom_code,
            product_uom_id:         orig.product_uom_id,
            tax_classification_type: orig.tax_classification_type,
            tax_classification_code: orig.tax_classification_code,
        };
    });

    const roundOff   = parseFloat(ctx.round_off_amount || 0);
    const grandTotal = grandLineTotal + roundOff;

    const payload = {
        sales_order_id:               parseInt(document.getElementById('pfFormSoId').value),
        proforma_number:              document.getElementById('pfNumberPreview').value.trim(),
        proforma_number_suggested:    document.getElementById('pfNumberSuggested').value,
        proforma_date:                document.getElementById('pfProformaDate').value,
        payment_terms:                document.getElementById('pfPaymentTermsDisplay').value.trim() || null,
        notes:                        document.getElementById('pfNotes').value.trim() || null,
        subtotal:                     subtotal,
        item_discount_total:          discItemTotal,
        subtotal_after_item_discount: subtotal - discItemTotal,
        order_discount_amount:        parseFloat(ctx.order_discount_amount || 0),
        discount_total:               parseFloat(ctx.discount_total || 0),
        discount_info:                ctx.discount_info,
        tax_amount:                   taxAmt,
        round_off_amount:             roundOff,
        adjustment_label:             ctx.adjustment_label || null,
        adjustment_amount:            parseFloat(ctx.adjustment_amount || 0),
        grand_total:                  grandTotal,
        billing_address_snapshot:     ctx.billing_address_snapshot,
        shipping_address_snapshot:    ctx.shipping_address_snapshot,
        invoice_terms:                getPfTermsValue(),
        items:                        items,
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
    
    if (action === 'download') {
        bootstrap.Modal.getInstance(document.getElementById('pfPickerModal'))?.hide();
        executeProformaAction('download', pfId);
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
    const isDownload = action === 'download';
    document.getElementById('pfPickerTitle').textContent = isDownload ? 'Download Proforma Invoice' : 'Send Proforma Invoice';

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
            <button class="btn btn-icon ${isDownload ? 'text-primary' : 'text-warning'}"
                title="${isDownload ? 'Download' : 'Send'}"
                onclick="pfPickerSendBtn(this, '${action}', ${pf.id})">
                <i class="bx ${isDownload ? 'bx-download' : 'bx-send'} fs-5"></i>
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


const renderSODetailsSection = async function(soDetails) {

    _soDetails = soDetails;

    const soDetailsWrapper = document.querySelector("#soDetails");
    const badgeWrap = soDetailsWrapper.querySelector('#soBadges');
    badgeWrap.innerHTML = '';

    const soStatus = soDetails.status;
    const isQuotationDoc = soDetails.origin_type === 'quotation';

    const _sidebarQuotations = document.querySelector('a.menu-link[href="/sales/quotations/"]')?.closest('.menu-item');
    const _sidebarOrders     = document.querySelector('a.menu-link[href="/sales/orders/"]')?.closest('.menu-item');
    const _highlightQuotations = isQuotationDoc && soStatus === 'draft';
    if (_sidebarQuotations) _sidebarQuotations.classList.toggle('active', _highlightQuotations);
    if (_sidebarOrders)     _sidebarOrders.classList.toggle('active', !_highlightQuotations);

    const soDocumentsCard = document.getElementById('soDocumentsCard');
    if (soDocumentsCard) {
        const isDraft = soStatus.toLowerCase() === 'draft';
        soDocumentsCard.classList.toggle('d-none', isDraft);
        if (!isDraft) {
            refreshSalesOrderDeliveries(soDetails.id);
            refreshSalesOrderReturns(soDetails.id);
            refreshSalesOrderProformas(soDetails.id, soDetails.status);
        }
    }

    // Dynamic page heading and doc number label
    // isOpenQuotation: only before conversion; once confirmed it becomes an order even if origin_type='quotation'
    const isOpenQuotation = isQuotationDoc && soDetails.status === 'draft';
    const pageHeadingEl = document.getElementById('soPageHeading');
    if (pageHeadingEl) pageHeadingEl.textContent = isOpenQuotation ? 'Quotation' : 'Sales Order';
    document.title = isOpenQuotation ? 'Quotation' : 'Sales Order';
    const soDocCodeEl = document.getElementById('soDocCode');
    if (soDocCodeEl) soDocCodeEl.textContent = soDetails.so_number ? `— #${soDetails.so_number}` : '';

    const docLabel = isOpenQuotation ? 'Quotation' : 'Sales Order';

    const statusMap = {
        draft:                 [isQuotationDoc ? 'Open' : 'Draft', 'warning'],
        confirmed:             ['Confirmed',            'primary'],
        cancelled:             ['Cancelled',            'danger'],
        partially_dispatched:  ['Partially Dispatched', 'info'],
        dispatched:            ['Dispatched',           'info'],
        partially_delivered:   ['Partially Delivered',  'success'],
        delivered:             ['Delivered',            'success'],
    };

    if (statusMap[soStatus]) {
        badgeWrap.insertAdjacentHTML('beforeend',
            `<span class="badge bg-label-${statusMap[soStatus][1]}">${statusMap[soStatus][0]}</span>`
        );
    }

    const warehouseEl = soDetailsWrapper.querySelector('#warehouse');
    if (warehouseEl) warehouseEl.innerHTML = soDetails.source_warehouse_name || '-';
    soDetailsWrapper.querySelector('#soCustomer').innerHTML   = soDetails.customer_name || '-';
    soDetailsWrapper.querySelector('#expectedDate').innerHTML = formatMySqlDate(soDetails.expected_delivery_date);
    soDetailsWrapper.querySelector('#soReference').innerHTML = soDetails.reference || '-';
    soDetailsWrapper.querySelector('#paymentTerms').innerHTML = soDetails.payment_terms || '-';
    soDetailsWrapper.querySelector('#soNotes').innerHTML      = soDetails.notes || '-';

    // Terms & conditions — quotation phase shows quotation_terms, order phase shows so_terms.
    // Server-side sanitized HTML — safe to inject.
    const termsHtml = isQuotationDoc && soStatus === 'draft' ? (soDetails.quotation_terms || '') : (soDetails.so_terms || '');
    const hasTerms = !isHtmlEmpty(termsHtml);
    document.getElementById('soTermsWrap').classList.toggle('d-none', !hasTerms);
    document.getElementById('soTerms').innerHTML = hasTerms ? termsHtml : '';

    // Date display: quotations show quote_date; converted quotes show both; orders show order_date
    const quoteDateRow   = document.getElementById('quoteDateRow');
    const validUntilRow  = document.getElementById('validUntilRow');
    const orderDateRow   = document.getElementById('orderDateRow');
    const convertedAtRow = document.getElementById('convertedAtRow');

    if (isQuotationDoc) {
        quoteDateRow.classList.remove('d-none');
        soDetailsWrapper.querySelector('#quoteDate').innerHTML = formatMySqlDate(soDetails.quote_date);

        if (soDetails.valid_until) {
            validUntilRow.classList.remove('d-none');
            soDetailsWrapper.querySelector('#validUntil').innerHTML = formatMySqlDate(soDetails.valid_until);
        } else {
            validUntilRow.classList.add('d-none');
        }

        if (soDetails.converted_at) {
            orderDateRow.classList.remove('d-none');
            soDetailsWrapper.querySelector('#orderDate').innerHTML = formatMySqlDate(soDetails.order_date);
            convertedAtRow.classList.remove('d-none');
            soDetailsWrapper.querySelector('#convertedAt').innerHTML = formatMySqlDate(soDetails.converted_at);
        } else {
            orderDateRow.classList.add('d-none');
            convertedAtRow.classList.add('d-none');
        }
    } else {
        quoteDateRow.classList.add('d-none');
        validUntilRow.classList.add('d-none');
        convertedAtRow.classList.add('d-none');
        orderDateRow.classList.remove('d-none');
        soDetailsWrapper.querySelector('#orderDate').innerHTML = formatMySqlDate(soDetails.order_date);
    }

    // Lead row — shown only when SO was created from a CRM lead
    const leadRefRowEl = soDetailsWrapper.querySelector('#leadRefRow');
    leadRefRowEl.classList.add('d-none');
    if (soDetails.lead_id) {
        leadRefRowEl.querySelector('#soLeadLink').innerHTML = `<a href="/crm/leads/${soDetails.lead_id}/" class="text-primary">${soDetails.lead_name || 'Lead #' + soDetails.lead_id}</a>`;
        leadRefRowEl.classList.remove('d-none');
    }

    const tbody = soDetailsWrapper.querySelector('#lineItemsTable tbody');
    tbody.innerHTML = '';

    // Delivered/Returned columns only visible once confirmed
    const showDeliveryColumns = soStatus !== 'draft';
    document.getElementById('deliveredColHeader')?.classList.toggle('d-none', !showDeliveryColumns);
    document.getElementById('returnedColHeader')?.classList.toggle('d-none', !showDeliveryColumns);

    (soDetails.line_items || []).forEach(item => {
        const uomCode = item.uom_code || '';

        const discountAmt = parseFloat(item.discount_amount || 0);
        const discDisplay = discountAmt > 0 ? formatCurrency(discountAmt) : '—';

        // Tax: show label(s) from tax_info
        const taxInfoArr = Array.isArray(item.tax_info) ? item.tax_info : [];
        const taxLabel = taxInfoArr.map(t => t.name).filter(Boolean).join(', ') || '—';

        const deliveredQty = parseFloat(item.delivered_qty || 0);
        const returnedQty  = parseFloat(item.returned_qty  || 0);
        const colHidden    = showDeliveryColumns ? '' : 'd-none';

        tbody.insertAdjacentHTML('beforeend', `
            <tr>
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
                <td class="text-end fw-semibold">${formatCurrency(item.line_total)}</td>
            </tr>
        `);
    });

    // Read stored rounded values directly — no JS arithmetic to avoid rounding drift
    const totalsTable        = document.getElementById('totalsTable');
    const itemDiscTotal      = parseFloat(soDetails.item_discount_total || 0);
    const orderDiscAmt       = parseFloat(soDetails.order_discount_amount || 0);
    const subAfterItemDisc   = parseFloat(soDetails.subtotal_after_item_discount || 0);
    const taxAmt             = parseFloat(soDetails.tax_amount || 0);

    totalsTable.innerHTML = `
        <tr>
            <th class="ps-0 text-muted w-px-300">Subtotal</th>
            <td class="px-0 text-end">${formatCurrency(soDetails.subtotal)}</td>
        </tr>
        ${itemDiscTotal > 0 ? `
        <tr>
            <th class="ps-0 text-muted w-px-300">Item Discounts</th>
            <td class="px-0 text-end text-danger">- ${formatCurrency(itemDiscTotal)}</td>
        </tr>
        <tr>
            <th class="ps-0 text-muted w-px-300">Subtotal After Discount</th>
            <td class="px-0 text-end">${formatCurrency(subAfterItemDisc)}</td>
        </tr>` : ''}
        ${orderDiscAmt > 0 ? `
        <tr>
            <th class="ps-0 text-muted w-px-300">Order Discount</th>
            <td class="px-0 text-end text-danger">- ${formatCurrency(orderDiscAmt)}</td>
        </tr>` : ''}
        <tr>
            <th class="ps-0 text-muted w-px-300">Tax</th>
            <td class="px-0 text-end">${formatCurrency(taxAmt)}</td>
        </tr>
        ${parseFloat(soDetails.round_off_amount || 0) !== 0 ? (() => {
            const ro = parseFloat(soDetails.round_off_amount);
            return `<tr>
                <th class="ps-0 text-muted w-px-300">Round Off</th>
                <td class="px-0 text-end ${ro < 0 ? 'text-danger' : 'text-success'}">${ro < 0 ? '- ' : '+ '}${formatCurrency(Math.abs(ro))}</td>
            </tr>`;
        })() : ''}
        <tr class="border-top w-px-300">
            <th class="ps-0">Total</th>
            <td class="px-0 text-end fw-bold">${formatCurrency(soDetails.grand_total)}</td>
        </tr>
    `;

    // Action Buttons
    let editBtn = '', cancelBtn = '', confirmBtn = '', deliveryBtn = '', instantDeliverBtn = '', createReturnBtn = '', createInvoiceBtn = '';

    // Send dropdown
    const _pfEnabled = @json(Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId) && $tenantContext->canAccess('proforma_invoices'));
    let sendEmailBtn = '';
    @if($tenantContext->canDo('sales_orders', 'send_email'))
    sendEmailBtn = `<div class="dropdown">
        <button class="btn btn-outline-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="icon-base bx bx-envelope icon-sm me-1"></i> Send
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item so-action-btn" data-action="send_email" href="javascript:void(0)">${isQuotationDoc && soStatus === 'draft' ? 'Quotation' : 'Sales Order'}</a></li>
            ${_pfEnabled && soStatus !== 'draft' ? `<li><a class="dropdown-item" href="javascript:void(0)" onclick="openProformaPicker('send')">Proforma Invoice</a></li>` : ''}
            {{-- <li><a class="dropdown-item text-muted" style="pointer-events:none;">Tax Invoice <small>(Coming Soon)</small></a></li> --}}
        </ul>
    </div>`;
    @endif

    // Download dropdown
    let downloadBtn = `<div class="dropdown">
        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="icon-base bx bx-download icon-sm me-1"></i> Download
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><a class="dropdown-item so-action-btn" data-action="pdf-download" href="javascript:void(0)">${isQuotationDoc && soStatus === 'draft' ? 'Quotation' : 'Sales Order'}</a></li>
            ${_pfEnabled && soStatus !== 'draft' ? `<li><a class="dropdown-item" href="javascript:void(0)" onclick="openProformaPicker('download')">Proforma Invoice</a></li>` : ''}
            {{-- <li><a class="dropdown-item text-muted" style="pointer-events:none;">Tax Invoice <small>(Coming Soon)</small></a></li> --}}
        </ul>
    </div>`;

    if (soStatus === 'draft') {
        if (canDo('sales_orders', 'write')) {
            const editAction = isQuotationDoc ? 'edit-quotation' : 'edit';
            editBtn = `<button class="btn btn-warning btn-sm so-action-btn" data-action="${editAction}"><i class="icon-base bx bx-edit icon-sm me-2"></i>Edit</button>`;
        }
        if (canDo('sales_orders', 'confirm')) {
            confirmBtn = `<button class="btn btn-info btn-sm so-action-btn" data-action="confirmed"><i class="icon-base bx bx-like icon-sm me-2"></i>Mark Confirmed</button>`;
        }
        if (canDo('sales_orders', 'cancel')) {
            cancelBtn = `<button class="btn btn-danger btn-sm so-action-btn" data-action="cancel"><i class="icon-base bx bx-x icon-sm me-1"></i>Cancel</button>`;
        }
    } else if (soStatus === 'confirmed') {
        if (canDo('sales_orders', 'cancel')) {
            cancelBtn = `<button class="btn btn-danger btn-sm so-action-btn" data-action="cancel"><i class="icon-base bx bx-x icon-sm me-1"></i>Cancel</button>`;
        }
        if (canDo('sales_deliveries', 'write')) {
            deliveryBtn = `<button class="btn btn-primary btn-sm so-action-btn" data-action="delivery"><i class="icon-base bx bx-package icon-sm me-2"></i>Delivery</button>`;
        }
        @if($tenantContext->canDo('proforma_invoices', 'create') && Service_CompanySettings::isProformaInvoiceEnabled(tenantContext()->companyId))
        createInvoiceBtn = `<div class="dropdown">
            <button class="btn btn-info btn-sm dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false">
                Create Invoice
            </button>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="javascript:void(0)" onclick="openCreateProforma()">Proforma</a></li>
                {{-- <li><a class="dropdown-item text-muted" style="pointer-events:none;"><i class="bx bx-receipt me-1"></i> Tax Invoice <small>(Coming Soon)</small></a></li> --}}
            </ul>
        </div>`;
        @endif
    } else if (soStatus === 'partially_dispatched' || soStatus === 'partially_delivered') {
        if (canDo('sales_deliveries', 'write')) {
            deliveryBtn = `<button class="btn btn-primary btn-sm so-action-btn" data-action="delivery"><i class="icon-base bx bx-package icon-sm me-2"></i>Delivery</button>`;
        }
    }

    const hasReturnable = (soDetails.line_items || []).some(
        item => (parseFloat(item.delivered_qty || 0) - parseFloat(item.returned_qty || 0)) > 0
    );
    if (hasReturnable && canDo('sales_returns', 'write')) {
        createReturnBtn = `<button class="btn btn-outline-warning btn-sm so-action-btn" data-action="create-return"><i class="icon-base bx bx-undo icon-sm me-2"></i>Customer Return</button>`;
    }

    const actionBtnsHtml = `<div class="row"><div class="col-lg-8"><div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            ${editBtn}
            ${confirmBtn}
            ${instantDeliverBtn}
            ${createInvoiceBtn}
            ${deliveryBtn}
            ${cancelBtn}
            ${createReturnBtn}
        </div>
        <div class="d-flex gap-2">
            ${sendEmailBtn}
            ${downloadBtn}
        </div>
    </div></div></div>`;

    document.getElementById('actionButtons').innerHTML = actionBtnsHtml;
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

    const container = document.getElementById('soHistoryTimeline');
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
        const activityType = item.log_type || '';
        const itemMeta = item.meta || {};

        container.insertAdjacentHTML('beforeend', `
            <li class="timeline-item timeline-item-transparent border-dashed">
                <span class="timeline-point timeline-point-info"></span>
                <div class="timeline-event">
                    <div class="timeline-header mb-1">
                        <h6 class="mb-0">${item.title || ''}</h6>
                        <small class="text-body-secondary">${item.performed_by || 'System'}</small>
                    </div>
                    ${renderSOHistoryItemMeta(activityType, itemMeta)}
                    <div class="small text-muted mb-1">
                        <div>${item.date_time || '-'}</div>
                    </div>
                </div>
            </li>
        `);
    });
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

    @if($tenantContext->canAccess('sales_deliveries'))
    const soDocumentsEl = document.getElementById('soDocuments');
    const collapse = new bootstrap.Collapse(soDocumentsEl, { toggle: false });
    const tabs  = document.querySelectorAll('#soDocumentsCard .doc-tab');
    const panes = document.querySelectorAll('#soDocumentsCard .tab-pane');
    let collapseDefaultActiveTab = tabs[0];

    function deactivateAllTabs() {
        tabs.forEach(t => t.classList.remove('active'));
        panes.forEach(p => p.classList.remove('show', 'active'));
    }
    function activateTab(tab) {
        deactivateAllTabs();
        tab.classList.add('active');
        document.querySelector(tab.dataset.bsTarget).classList.add('show', 'active');
    }

    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
            collapseDefaultActiveTab = this;
            activateTab(this);
            if (!soDocumentsEl.classList.contains('show')) collapse.show();
        });
    });

    soDocumentsEl.addEventListener('shown.bs.collapse', () => activateTab(collapseDefaultActiveTab));
    soDocumentsEl.addEventListener('hidden.bs.collapse', () => {
        collapseDefaultActiveTab = tabs[0];
        deactivateAllTabs();
    });

    document.querySelector('#soDocumentsCard .accordion-toggle').addEventListener('click', function () {
        collapse.toggle();
        this.querySelector('i').classList.toggle('bx-chevron-up');
        this.querySelector('i').classList.toggle('bx-chevron-down');
    });
    @endif
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
    'pdf-download': (soId) => { window.location.href = `/sales/orders/${soId}/pdf?mode=download`; },
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
