@extends('layouts.app')
@section('title', 'Sales Order')

@section('content')

<?php
$tenantContext = tenantContext();
?>

<!-- Content -->
<div class="container-fluid">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Order Details</h4>
    </div>

    <div id="actionButtons"></div>

    <div class="row g-4">
        <div class="col-lg-8">

            @if($tenantContext->canAccess('sales'))
            <div class="card mb-4" id="soDocumentsCard">                
                <div class="card-header py-0">
                    <div class="d-flex align-items-stretch">
                        <ul class="nav nav-tabs flex-shrink-0 gap-4" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link doc-tab px-0 so-deliveries-tab" data-bs-target="#soDeliveriesTab" type="button">Deliveries <span class="badge bg-label-primary ms-1">0</span></button>
                            </li>
                        </ul>
                        <button class="accordion-toggle flex-grow-1 px-0 border-0 bg-transparent text-end" type="button" aria-label="Toggle">
                            <i class="bx bx-chevron-down fs-4"></i>
                        </button>
                    </div>
                </div>                

                <div id="soDocuments" class="accordion-collapse collapse">
                    <div class="card-body">
                        <div class="tab-content px-0">
                            <div class="tab-pane fade" id="soDeliveriesTab">
                                <div class="table-responsive">
                                    <table class="table m-0" id="soDeliveriesTable">
                                        <thead>
                                            <tr>
                                                <th>DN#</th>
                                                <th>Location</th>
                                                <th>Status</th>
                                                <th>Dispatch Date</th>
                                                <th>Delivery Date</th>
                                                <th class="text-end">Items</th>
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
            @endif

            <div class="card" id="soDetails">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-8">
                        <h5 class="mb-0" id="soNumber">Sales Order <strong>#0000000</strong></h5>
                        <div class="d-flex gap-2" id="soBadges"></div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <h6 class="mb-0">Location</h6>
                            <p class="mb-0" id="location">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Customer</h6>
                            <p class="mb-0" id="soCustomer">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Order Date</h6>
                            <p class="mb-0" id="orderDate">-</p>
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

                    <div class="table-responsive border border-bottom-0 border-top-0 rounded">
                        <table class="table m-0" id="lineItemsTable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Unit Price</th>
                                    <th class="text-end">Discount</th>
                                    <th class="text-end">Tax</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody><tr><td colspan="6" class="text-center">No data</td></tr></tbody>
                        </table>
                    </div>

                    <div class="d-flex justify-content-end pt-4">
                        <table class="table table-borderless w-auto mb-0" id="totalsTable">
                            <tr>
                                <th class="ps-0 text-muted">Subtotal</th>
                                <td class="px-0 text-end">₹0.00</td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted">Discount</th>
                                <td class="px-0 text-end">₹0.00</td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted">Tax</th>
                                <td class="px-0 text-end">₹0.00</td>
                            </tr>
                            <tr class="border-top">
                                <th class="ps-0">Total</th>
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

@include('app.components.drawers.sales-orders.add-edit')
@include('app.components.drawers.sales-deliveries.add-edit')

<!-- Email Composer Modal -->
<div class="modal fade" id="emailComposerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Send</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="emailComposerForm" novalidate>
                    <div class="mb-3">
                        <label class="form-label" for="emailTo">To <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="emailTo" name="to" placeholder="recipient@example.com" />
                    </div>
                    <div class="mb-3">
                        <label class="form-label" for="emailCc">CC <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="email" class="form-control" id="emailCc" name="cc" placeholder="cc@example.com" />
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
                    <button type="button" class="btn btn-sm btn-primary" id="sendEmailSubmitBtn">
                        <span class="send-label">Send</span>
                        <span class="sending-label d-none"><span class="spinner-border spinner-border-sm me-1" role="status"></span>Sending...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
const dnStatusMap = {
    draft: ['Draft', 'secondary'],
    dispatched: ['Dispatched', 'primary'],
    delivered: ['Delivered',  'success'],
    returned: ['Returned', 'warning'],
    lost: ['Lost', 'danger'],
    cancelled:  ['Cancelled',  'dark'],
};

const refreshSalesOrderDeliveries = async function(soId) {

    @if(!$tenantContext->canAccess('sales'))
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
            tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted py-3">No deliveries found</td></tr>`;
            return;
        }

        badge.innerHTML = data.length;

        let rowsHtml = '';
        data.forEach(item => {
            const s = dnStatusMap[item.status] || [item.status, 'secondary'];
            rowsHtml += `<tr>
                <td><a href="/sales/deliveries/${item.id}/" class="text-primary fw-medium">${item.dn_number}</a></td>
                <td>${item.location ?? '-'}</td>
                <td><span class="badge bg-label-${s[1]}">${s[0]}</span></td>
                <td>${item.dispatch_date ?? '-'}</td>
                <td>${item.delivery_date ?? '-'}</td>
                <td class="text-end">${item.items_count ?? '0'}</td>
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

const renderSODetailsSection = async function(soDetails) {

    _soDetails = soDetails;

    const soDetailsWrapper = document.querySelector("#soDetails");
    soDetailsWrapper.querySelector('#soNumber strong').innerHTML = `#${soDetails.so_number}`;

    const badgeWrap = soDetailsWrapper.querySelector('#soBadges');
    badgeWrap.innerHTML = '';

    const soStatus = soDetails.status;
    const statusMap = {
        draft:                 ['Quotation',            'warning'],
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

    soDetailsWrapper.querySelector('#location').innerHTML = soDetails.location_name || '-';
    soDetailsWrapper.querySelector('#soCustomer').innerHTML   = soDetails.customer_name || '-';
    soDetailsWrapper.querySelector('#orderDate').innerHTML    = formatMySqlDate(soDetails.order_date);
    soDetailsWrapper.querySelector('#expectedDate').innerHTML = formatMySqlDate(soDetails.expected_delivery_date);
    soDetailsWrapper.querySelector('#soReference').innerHTML = soDetails.reference || '-';
    soDetailsWrapper.querySelector('#paymentTerms').innerHTML = soDetails.payment_terms || '-';
    soDetailsWrapper.querySelector('#soNotes').innerHTML      = soDetails.notes || '-';

    // Lead row — shown only when SO was created from a CRM lead
    const leadRefRowEl = soDetailsWrapper.querySelector('#leadRefRow');
    leadRefRowEl.classList.add('d-none');
    if (soDetails.lead_id) {
        leadRefRowEl.querySelector('#soLeadLink').innerHTML = `<a href="/crm/leads/${soDetails.lead_id}/" class="text-primary">${soDetails.lead_name || 'Lead #' + soDetails.lead_id}</a>`;
        leadRefRowEl.classList.remove('d-none');
    }

    const tbody = soDetailsWrapper.querySelector('#lineItemsTable tbody');
    tbody.innerHTML = '';

    (soDetails.line_items || []).forEach(item => {
        const uomCode = item.uom_code || '';
        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>
                    <div class="fw-medium">${item.product_name}</div>
                    ${item.description ? `<small class="text-muted">${item.description}</small>` : ''}
                </td>
                <td class="text-end">${formatQty(item.ordered_qty)} <span class="fs-tiny fw-semibold">${uomCode}</span></td>
                <td class="text-end">${formatCurrency(item.unit_price)}</td>
                <td class="text-end">${formatCurrency(item.discount_amount)}</td>
                <td class="text-end">${formatCurrency(item.tax_amount)}</td>
                <td class="text-end fw-semibold">${formatCurrency(item.line_total)}</td>
            </tr>
        `);
    });

    const totalsTable = document.getElementById('totalsTable');
    totalsTable.innerHTML = `
        <tr>
            <th class="ps-0 text-muted">Subtotal</th>
            <td class="px-0 text-end">${formatCurrency(soDetails.subtotal)}</td>
        </tr>
        <tr>
            <th class="ps-0 text-muted">Discount</th>
            <td class="px-0 text-end">${formatCurrency(soDetails.discount_amount)}</td>
        </tr>
        <tr>
            <th class="ps-0 text-muted">Tax</th>
            <td class="px-0 text-end">${formatCurrency(soDetails.tax_amount)}</td>
        </tr>
        <tr class="border-top">
            <th class="ps-0">Total</th>
            <td class="px-0 text-end fw-bold">${formatCurrency(soDetails.total_amount)}</td>
        </tr>
    `;

    // Action Buttons

    /** Removed Instant Mark Deliver now, this will force user to always create delivery from delivery form */
    let editBtn = cancelBtn = confirmBtn = instantDeliverBtn = deliveryBtn = ``;
    let downloadBtn = `<button class="btn btn-outline-secondary btn-sm so-action-btn" data-action="pdf-download"><i class="icon-base bx bx-download icon-sm me-2"></i>Download</button>`;
    let sendEmailBtn = `<button class="btn btn-outline-primary btn-sm so-action-btn" data-action="send_email"><i class="icon-base bx bx-envelope icon-sm me-2"></i>Send</button>`;

    if (soStatus === 'draft') {
        editBtn = `<button class="btn btn-warning btn-sm so-action-btn" data-action="edit"><i class="icon-base bx bx-edit icon-sm me-2"></i>Edit</button>`;
        
        @if($tenantContext->canAccess('sales'))
        confirmBtn = `<button class="btn btn-info btn-sm so-action-btn" data-action="confirmed"><i class="icon-base bx bx-like icon-sm me-2"></i>Mark Confirmed</button>`;
        @endif
        
        
        cancelBtn = `<button class="btn btn-danger btn-sm so-action-btn" data-action="cancel"><i class="icon-base bx bx-x icon-sm me-1"></i>Cancel</button>`;
        if (!soDetails.has_deliveries) {
            //instantDeliverBtn = `<button class="btn btn-success btn-sm so-action-btn" data-action="instant-deliver"><i class="icon-base bx bx-rocket icon-sm me-2"></i>Confirm & Deliver</button>`;
        }
    } else if (soStatus === 'confirmed') {

        cancelBtn = `<button class="btn btn-danger btn-sm so-action-btn" data-action="cancel"><i class="icon-base bx bx-x icon-sm me-1"></i>Cancel</button>`;
        deliveryBtn = `<button class="btn btn-primary btn-sm so-action-btn" data-action="delivery"><i class="icon-base bx bx-package icon-sm me-2"></i>Delivery</button>`;

        if (!soDetails.has_deliveries) {
            //instantDeliverBtn = `<button class="btn btn-success btn-sm so-action-btn" data-action="instant-deliver"><i class="icon-base bx bx-rocket icon-sm me-2"></i>Mark Deliver</button>`;
        }

    } else if (soStatus === 'partially_dispatched' || soStatus === 'partially_delivered') {
        deliveryBtn = `<button class="btn btn-primary btn-sm so-action-btn" data-action="delivery"><i class="icon-base bx bx-package icon-sm me-2"></i>Delivery</button>`;
    }

    const actionBtnsHtml = `<div class="row"><div class="col-lg-8"><div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            ${editBtn}
            ${confirmBtn}
            ${instantDeliverBtn}
            ${deliveryBtn}
            ${cancelBtn}
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
    else if (activityType === 'email_sent') {
        html = `<ul class="mt-2 mb-2 ps-3 small">
            <li>To: <strong class="text-primary">${meta.to || '-'}</strong></li>
            <li>Subject: <strong class="text-primary">${meta.subject || '-'}</strong></li>
        </ul>`;
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


const updateSalesOrderStatus = async function(soId, newStatus, notes = '', acknowledgedWarning = false) {
    try {
        const payload = { status: newStatus, notes };
        if (acknowledgedWarning) payload.acknowledged_warning = true;

        const response = await api.post(`/sales/orders/${soId}/status`, payload);
        const { status: responseStatus, warnings } = response.data;

        // Soft warning gate — show confirmation before proceeding
        if (responseStatus === 'warning') {
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
    refreshSalesOrderDeliveries(soId);

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
    'pdf-inline':   (soId) => window.open(`/sales/orders/${soId}/pdf?mode=inline`, '_blank'),
    'pdf-download': (soId) => { window.location.href = `/sales/orders/${soId}/pdf?mode=download`; },
    'send_email':   (soId) => openEmailComposer(soId),
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


// ─── Email Composer ───────────────────────────────────────────────────────────

let _joditInstance      = null;
let _emailComposerModal = null;
let _emailDefaultBody   = '';
let _emailSoId          = null;
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

const openEmailComposer = function(soId) {
    _emailSoId = soId;
    const so   = _soDetails || {};

    cleanFormInputFeedback(document.getElementById('emailComposerForm'));

    document.getElementById('emailTo').value      = so.customer_email || '';
    document.getElementById('emailCc').value      = '';
    document.getElementById('emailSubject').value = `Quotation #${so.so_number || ''}`;

    // Clear attachments
    _attachedFiles = [];
    renderEmailAttachmentChips();

    const customerName = so.customer_name || 'Customer';
    _emailDefaultBody = `Dear ${customerName},<br><br>Please find your quotation <strong>#${so.so_number || ''}</strong> enclosed.<br><br>Should you have any questions, please do not hesitate to contact us.<br><br>Regards,<br>The Team`;

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
    const subject = document.getElementById('emailSubject').value.trim();

    const body = _joditInstance ? _joditInstance.value : '';

    sendBtn.querySelector('.send-label').classList.add('d-none');
    sendBtn.querySelector('.sending-label').classList.remove('d-none');
    sendBtn.disabled = true;

    try {
        await api.post(`/sales/orders/${_emailSoId}/send-email`, { to, cc, subject, body, attachments: _attachedFiles });
        notyf.success('Email sent successfully');
        _emailComposerModal.hide();
        refreshSalesOrderHistory(_emailSoId);
    } catch (error) {
        handleApiError(error, form);
    } finally {
        sendBtn.querySelector('.send-label').classList.remove('d-none');
        sendBtn.querySelector('.sending-label').classList.add('d-none');
        sendBtn.disabled = false;
    }
};

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
</script>
@endpush
