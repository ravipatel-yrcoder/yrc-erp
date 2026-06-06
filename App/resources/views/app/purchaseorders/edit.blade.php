@extends('layouts.app')
@section('title', 'Purchase Order')

@section('content')
<!-- Content -->
<div class="container-fluid">
        
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Purchase Order Details</h4>
    </div>

    <div id="actionButtons"></div>
    
    <div class="row g-4">
        <div class="col-lg-8">
            
            <div class="card mb-4" id="poDocumentsCard">
                <div class="card-header py-0">
                    <div class="d-flex align-items-stretch">
                        <!-- Tabs -->
                        <ul class="nav nav-tabs flex-shrink-0 gap-4" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link doc-tab px-0 po-receives-tab" data-bs-target="#poReceivesTab" type="button">Receives <span class="badge bg-label-primary ms-1">0</span></button>
                            </li>
                        </ul>

                        <button class="accordion-toggle flex-grow-1 px-0 border-0 bg-transparent text-end" type="button" aria-label="Toggle">
                            <i class="bx bx-chevron-down fs-4"></i>
                        </button>
                    </div>
                </div>

                <div id="poDocuments" class="accordion-collapse collapse">
                    <div class="card-body">
                        <div class="tab-content px-0">

                            <!-- Purchase Orders Recesoves -->
                            <div class="tab-pane fade" id="poReceivesTab">
                                <div class="table-responsive">
                                    <table class="table m-0" id="poReceivesTable">
                                        <thead>
                                            <tr>
                                                <th>Purchase Receive#</th>
                                                <th>Create Date</th>
                                                <th>Status</th>
                                                <th>Received Date</th>
                                                <th class="text-end">Items</th>
                                                <!--<th>Bill</th>-->
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


            <div class="card" id="poDetails">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-8">
                        <h5 class="mb-0" id="poNumber">Purchase Order <strong>#0000000</strong></h5>
                        <div class="d-flex gap-2" id="poBadges"></div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <h6 class="mb-0">Vendor</h6>
                            <p class="mb-0" id="poVendor">-</p>
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
                            <h6 class="mb-0">Payment Terms</h6>
                            <p class="mb-0" id="paymentTerms">-</p>
                        </div>

                        <div class="col-md-4">
                            <h6 class="mb-0">Currency</h6>
                            <p class="mb-0" id="poCurrency">-</p>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h6 class="mb-0">Notes</h6>
                        <p class="mb-0" id="notes">-</p>
                    </div>

                    <div class="table-responsive border border-bottom-0 border-top-0 rounded">
                        <table class="table m-0" id="lineItemsTable">
                            <thead>
                                <tr>
                                    <th>Item</th>
                                    <th class="text-end">Qty</th>
                                    <th class="text-end">Received</th>
                                    <th class="text-end">Unit Cost</th>
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
                    <div class="dropdown">
                        <button class="btn text-body-secondary p-0" type="button" id="timelineWapper" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" fdprocessedid="h2a62n">
                            <i class="icon-base bx bx-dots-vertical-rounded icon-lg"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="timelineWapper">
                            <a class="dropdown-item" href="javascript:void(0);" onClick="alert('Not implemented yet');">Add log</a>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <ul class="timeline timeline-outline  mb-0" id="poHistoryTimeline">
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
                    <div class="mb-3">
                        <label class="form-label" for="poEmailCc">CC <span class="text-muted fw-normal">(optional)</span></label>
                        <input type="email" class="form-control" id="poEmailCc" name="cc" placeholder="cc@example.com" />
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
                    <button type="button" class="btn btn-sm btn-primary" id="poSendEmailSubmitBtn">
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
const renderPODetailsSection = async function(poDetails) {

    _poDetails = poDetails;

    const poDetailsWrapper = document.querySelector("#poDetails");
    poDetailsWrapper.querySelector('#poNumber strong').innerHTML = `#${poDetails.po_number}`;

    const badgeWrap = poDetailsWrapper.querySelector('#poBadges');
    badgeWrap.innerHTML = '';

    const poStatus = poDetails.status;

    const statusMap = {
        draft: ['Draft', 'warning'],
        confirmed: ['Confirmed', 'primary'],
        cancelled: ['Cancelled', 'danger'],        
        closed: ['Closed', 'secondary'],
    };

    if (statusMap[poStatus]) {
        badgeWrap.insertAdjacentHTML(
            'beforeend',
            `<span class="badge bg-label-${statusMap[poStatus][1]}">${statusMap[poStatus][0]}</span>`
        );
    }

    if( poStatus !== "closed" && poStatus !== "draft" ) {

        const poDetailsLineItems = poDetails.line_items || [];
        const allNotReceived = poDetailsLineItems.every(item => parseFloat(item.received_qty) === 0);
        const allFullyReceived = poDetailsLineItems.every(item => parseFloat(item.received_qty) >= parseFloat(item.ordered_qty));

        let badgeLabel = badgeColor = '';
        if (allNotReceived) {
            badgeLabel = 'Not Received';
            badgeColor = 'warning';
        } else if (allFullyReceived) {            
            badgeLabel = 'Received';
            badgeColor = 'success';
        } else {
            badgeLabel = 'Partially Received';
            badgeColor = 'info';
        }

        badgeWrap.insertAdjacentHTML('beforeend', `<span class="badge bg-label-${badgeColor}">${badgeLabel}</span>`);
    }    

    poDetailsWrapper.querySelector('#poVendor').innerHTML = poDetails.vendor_name || '-';
    poDetailsWrapper.querySelector('#orderDate').innerHTML = formatMySqlDate(poDetails.order_date);
    poDetailsWrapper.querySelector('#expectedDate').innerHTML = formatMySqlDate(poDetails.expected_delivery_date);
    poDetailsWrapper.querySelector('#paymentTerms').innerHTML = poDetails.payment_terms || '-';
    const poCurrency = poDetails.currency_code || window.sysDefaultConfig?.currency || 'INR';
    poDetailsWrapper.querySelector('#poCurrency').innerHTML = poCurrency;
    poDetailsWrapper.querySelector('#notes').innerHTML = poDetails.notes || '-';

    const tbody = poDetailsWrapper.querySelector('#lineItemsTable tbody');
    tbody.innerHTML = '';

    let grandTotal = 0;
    let taxTotal = 0;

    (poDetails.line_items || []).forEach(item => {

        const itemUomCode = item.uom_code || "";
        grandTotal += parseFloat(item.line_total);
        taxTotal += parseFloat(item.tax_amount || 0);

        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>
                    <div class="fw-medium">${item.product_name}</div>
                    ${item.description ? `<small class="text-muted">${item.description}</small>` : ''}
                </td>
                <td class="text-end">${formatQty(item.ordered_qty)} <span class="fs-tiny fw-semibold">${itemUomCode}</span></td>
                <td class="text-end">${formatQty(item.received_qty)}</td>
                <td class="text-end">${formatCurrency(item.unit_price, { currency: poCurrency })}</td>
                <td class="text-end">${formatCurrency(item.tax_amount, { currency: poCurrency })}</td>
                <td class="text-end fw-semibold">${formatCurrency(item.line_total, { currency: poCurrency })}</td>
            </tr>
        `);
    });

    const subTotal = grandTotal - taxTotal;
    const totalsTable = document.getElementById('totalsTable');
    totalsTable.innerHTML = `
        <tr>
            <th class="ps-0 text-muted">Subtotal</th>
            <td class="px-0 text-end">${formatCurrency(subTotal, { currency: poCurrency })}</td>
        </tr>
        <tr>
            <th class="ps-0 text-muted">Tax</th>
            <td class="px-0 text-end">${formatCurrency(taxTotal, { currency: poCurrency })}</td>
        </tr>
        <tr class="border-top">
            <th class="ps-0">Total</th>
            <td class="px-0 text-end fw-bold">${formatCurrency(grandTotal, { currency: poCurrency })}</td>
        </tr>
    `;


    // Action Buttons
    let editBtn = issuedBtn = cancelBtn = receiveBtn = ``;
    let sendEmailBtn = `<button class="btn btn-outline-primary btn-sm po-action-btn" id="sendEmailButton" data-action="send_email"><i class="icon-base bx bx-envelope icon-sm me-2"></i>Send</button>`;
    let downloadBtn  = `<button class="btn btn-outline-secondary btn-sm po-action-btn" data-action="pdf-download"><i class="icon-base bx bx-download icon-sm me-2"></i>Download</button>`;

    if( poStatus === 'draft' ) {
        editBtn = `<button class="btn btn-warning btn-sm po-action-btn" id="editButton" data-action="edit"><i class="icon-base bx bx-edit icon-sm me-2"></i>Edit</button>`;
    }

    if( poStatus === 'draft' ) {
        issuedBtn = `<button class="btn btn-success btn-sm po-action-btn" id="markConfirmedButton" data-action="confirmed"><i class="icon-base bx bx-like icon-sm me-2"></i>Mark confirmed</button>`;
    }

    if( poStatus === 'draft' || poStatus === 'confirmed' ) {
        cancelBtn = `<button class="btn btn-danger btn-sm po-action-btn" id="cancelButton" data-action="cancel"><i class="icon-base bx bx-x icon-sm me-1"></i>Cancel</button>`;
    }

    if( poStatus === 'confirmed' || poStatus === 'partially_received' ) {
        receiveBtn = `<button class="btn btn-primary btn-sm po-action-btn" id="receiveButton" data-action="receive"><i class="icon-base bx bx-import icon-sm me-1"></i>Receive</button>`;
    }

    const actionBtnsHtml = `<div class="row"><div class="col-lg-8"><div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            ${editBtn}
            ${receiveBtn}
            ${issuedBtn}
            ${cancelBtn}
        </div>
        <div class="d-flex gap-2">
            ${sendEmailBtn}
            ${downloadBtn}
        </div>
    </div></div></div>`;

    const actionButtonsEl = document.getElementById('actionButtons');
    actionButtonsEl.innerHTML = actionBtnsHtml;
}

const refreshPurchaseOrderDetails = async function(poId) {

    try {

        const response = await api.get(`/purchase/orders/${poId}`);
        const { data } = response.data;
        const poDetails = data.po_details;

        renderPODetailsSection(poDetails);

    } catch (error) {
        //console.log(error);
        notyf.error("Unable to load purchase order details");
    }
}


const formatChange = function(oldVal, newVal, data={}) {
    
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

const renderPoHistoryItemMeta = function(activityType, meta={}) {
    
    if (!meta || typeof meta !== 'object') return '';

    let html = '';
    if( activityType === "created" ) {
        html = `<ul class="mt-2 mb-2 ps-3 small">`;
            html += `<li>Status: <strong class='text-primary'>${ucFirst(meta.status)}</strong></li>`;
        html += `</ul>`;    
    }
    else if( activityType === "updated_details" ) {
        
        html = `<ul class="mt-2 mb-2 ps-3 small">`;
        meta.forEach(item => {            

            let finalOldVal = item.old_val || "";
            let finalNewVal = item.new_val || "";
            if( item.field === "order_date" || item.field === "expected_delivery_date" ) {
                if( finalOldVal ) {
                    finalOldVal = formatMySqlDate(finalOldVal);
                }

                if( finalNewVal ) {
                    finalNewVal = formatMySqlDate(finalNewVal);
                }
            }

            const formattedHtml = formatChange(finalOldVal, finalNewVal);
            if( formattedHtml ) {
                html += `<li>${item.label}: <strong class='text-primary'>${formattedHtml}</strong></li>`;
            }
        });
        html += `</ul>`;
    }
    else if (activityType === "updated_line_items") {
        
        meta.forEach(item => {

            //console.log(item);

            const itemOldUomCode = item.old_uom || "";
            const itemNewUomCode = item.new_uom || "";

            // product header
            html += `<div class="small mb-1">
                        <strong>${item.prod_name}</strong>
                        ${item.event === 'deleted' ? `<span class="badge bg-label-danger ms-1 p-1">Delete</span>` : ''}
                        ${item.event === 'created' ? `<span class="badge bg-label-success ms-1 p-1">Add</span>` : ''}
                        ${item.event === 'updated' ? `<span class="badge bg-label-warning ms-1 p-1">Update</span>` : ''}
                    </div>`;

            html += `<ul class="mt-2 mb-2 ps-7 small">`;    

            // qty
            if (item.event === 'created') {
                html += `<li class="ps-0">Qty: <span class="text-primary">${item.new_qty} <span class="fs-tiny fw-semibold">${itemNewUomCode}</span></span></li>`;
            } else if (item.event === 'deleted') {
                html += `<li class="ps-0">Qty: <span class="text-danger">${item.old_qty} <span class="fs-tiny fw-semibold">${itemOldUomCode}</span></span></li>`;
            } else {
                if( item.old_qty != item.new_qty ) {
                    const changeData = {'type': 'qty', 'oldUomCode': itemOldUomCode, 'newUomCode': itemNewUomCode};
                    html += `<li class="ps-0">Qty: ${formatChange(item.old_qty, item.new_qty, changeData)}</li>`;
                }
            }

            // unit cost
            if (item.event === 'created') {
                html += `<li class="ps-0">Unit Cost: <span class="text-primary">${item.new_unit_cost}</span></li>`;
            } else if (item.event === 'deleted') {
                html += `<li class="ps-0">Unit Cost: <span class="text-muted">${item.old_unit_cost}</span></li>`;
            } else {
                if( item.old_unit_cost != item.new_unit_cost ) {
                    html += `<li class="ps-0">Unit Cost: ${formatChange(item.old_unit_cost, item.new_unit_cost)}</li>`;
                }
            }
            html += `</ul>`;
        });
    }
    else if (activityType === "status_changed") {
        html += `<ul class="mt-2 mb-2 ps-7 small">
            <li class="ps-0">${formatChange(ucFirst(meta.old_status), ucFirst(meta.new_status))}</li>
        </ul>`;
    }
    else if (activityType === "email_sent") {
        html += `<ul class="mt-2 mb-2 ps-3 small">
            <li>To: <strong>${meta.to || '-'}</strong></li>
            ${meta.cc ? `<li>CC: <strong>${meta.cc}</strong></li>` : ''}
            <li>Subject: <strong>${meta.subject || '-'}</strong></li>
        </ul>`;
    }
    else if (activityType === "received") {
        html += `<ul class="mt-2 mb-2 ps-7 small">
            <li class="ps-0">Received items: <strong class="text-primary">${meta.items_count}</strong></li>
            <li class="ps-0">Received Qty: <strong class="text-primary">${meta.quantities}</strong></li>
        </ul>`;
    }

    return html;
}

const renderPurchaseOrderHistory = function(history = []) {

    const container = document.getElementById('poHistoryTimeline');
    if (!container) return;

    container.innerHTML = '';

    if (!Array.isArray(history) || history.length === 0) {
        container.innerHTML = `
            <li class="timeline-item timeline-item-transparent">
                <div class="timeline-event text-muted">
                    No history available
                </div>
            </li>
        `;
        return;
    }

    history.forEach(item => {
        
        //const { date, time } = splitDateTime(item.date_time);
        const activityType = item.log_type || "";
        const item_meta = item.meta || {};
        let finalTitle = item.title || '';
        if( activityType === "received" ) {
            const receipt_number = item_meta.receipt_number || "";
            if( receipt_number ) {
                finalTitle +=" #"+receipt_number;
            }
        }

        const itemHtml = `
            <li class="timeline-item timeline-item-transparent border-dashed">
                <span class="timeline-point timeline-point-info"></span>
                <div class="timeline-event">
                    <div class="timeline-header mb-1">
                        <h6 class="mb-0">${finalTitle}</h6>
                        <small class="text-body-secondary">
                            ${item.performed_by || 'System'}
                        </small>
                    </div>
                    ${renderPoHistoryItemMeta(activityType, item_meta)}
                    <div class="small text-muted mb-1">
                        <div>${item.date_time || '-'}</div>
                    </div>
                </div>
            </li>
        `;

        container.insertAdjacentHTML('beforeend', itemHtml);
    });
}

const refreshPurchaseOrderHistory = async function(poId) {

    try {

        const response = await api.get(`/purchase/orders/${poId}/history`);
        const { data } = response.data;

        renderPurchaseOrderHistory(data);
        
        /*
        const poDetails = data.po_details;
        renderPODetailsSection(poDetails);
        */

    } catch (error) {
        //console.log(error);
        notyf.error("Unable to load purchase order history");
    }
}


const refreshPurchaseOrderReceipts  = async function(poId) {

    try {

        const response = await api.get(`/purchase/receipts`, {params: {po_id: poId}});
        const { data } = response.data;
        
        const tbody = document.querySelector('#poDocumentsCard #poReceivesTable tbody');
        const receiptsCountBadge = document.querySelector('#poDocumentsCard .po-receives-tab .badge');

        tbody.innerHTML = '';
        receiptsCountBadge.innerHTML = '0';

        if (!data || data.length === 0) {
            tbody.innerHTML = `
            <tr>
                <td colspan="7" class="text-center text-muted py-3">No purchase receipts found</td>
            </tr>`;
            return;
        }

        receiptsCountBadge.innerHTML = data.length;

        let rowsHtml = ``;
        data.forEach(item => {
            rowsHtml += `<tr>
                <td><a href="/purchase/receipts/${item.id}/" class="text-primary fw-medium">${item.receipt_number}</a></td>
                <td>${item.create_date ?? '-'}</td>
                <td>
                    <span class="badge bg-label-secondary">
                    ${item.status ?? 'Draft'}
                    </span>
                </td>
                <td>${item.received_date ?? '-'}</td>
                <td class="text-end">${item.items_count ?? '0'}</td>
                <!--<td>-</td>-->
                <td class="text-end">
                    <a href="/purchase/receipts/${item.id}/" class="text-primary"><i class="icon-base bx bx-show"></i></a>
                </td>
            </tr>`;
        });

        tbody.innerHTML = rowsHtml;

    } catch (error) {
        //console.log(error);
        notyf.error("Unable to load purchase order receives");
    }

}


document.addEventListener('DOMContentLoaded', async () => {

    const poId = "{{ request()->getInput('id') ?? '' }}";
    if (!poId) return;

    refreshPurchaseOrderDetails(poId); // Load purchase order details
    refreshPurchaseOrderReceipts(poId); // Load purchase order receipts(receives)
    refreshPurchaseOrderHistory(poId);  // Load purchase order history

    const poDocumentsEl = document.getElementById('poDocuments');
    const collapse = new bootstrap.Collapse(poDocumentsEl, { toggle: false });

    const tabs = document.querySelectorAll('#poDocumentsCard .doc-tab');
    const panes = document.querySelectorAll('#poDocumentsCard .tab-pane');
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

    // Tab click
    tabs.forEach(tab => {
        tab.addEventListener('click', function () {
        
            collapseDefaultActiveTab = this;
            activateTab(this);

            // Expand if collapsed
            if (!poDocumentsEl.classList.contains('show')) {
                collapse.show();
            }
        });
    });

    // Accordion expand → always activate first tab
    poDocumentsEl.addEventListener('shown.bs.collapse', function () {
        activateTab(collapseDefaultActiveTab);
    });

    // Accordion collapse → deactivate all tabs
    poDocumentsEl.addEventListener('hidden.bs.collapse', function () {
        collapseDefaultActiveTab = tabs[0];
        deactivateAllTabs();
    });

    // Header toggle
    document.querySelector('.accordion-toggle').addEventListener('click', function () {
        collapse.toggle();
        this.querySelector('i').classList.toggle('bx-chevron-up');
        this.querySelector('i').classList.toggle('bx-chevron-down');
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


const updatePurchaseOrderStatus = async function(poId, status, notes='') {

    try {

        const response = await api.post(`/purchase/orders/${poId}/status`, {status, notes});
        const { data } = response.data;
        
        let message = "Status updated successfully";
        if( status === "confirmed" ) {
            message = "Purchase order approved/confirmed successfully";
        }

        notyf.success(message);

        refreshPurchaseOrderDetails(poId);
        refreshPurchaseOrderHistory(poId);

    } catch (error) {
        //console.log(error);
        notyf.error("Failed to update status");
    }

}


// After a receipt is saved (create or edit) from the drawer, refresh relevant PO sections
document.addEventListener('receiptFormSaved', function(e) {
    const poId = e.detail.poId || "{{ request()->getInput('id') ?? '' }}";
    if (!poId) return;
    refreshPurchaseOrderDetails(poId);
    refreshPurchaseOrderReceipts(poId);
    refreshPurchaseOrderHistory(poId);
});


const actionHandlers = {
    edit: (poId) => openPurchaseOrderFormDrawer(poId),
    send_email: (poId) => openPoEmailComposer(poId),
    confirmed: (poId) => updatePurchaseOrderStatus(poId, "confirmed", "PO Confirmed by user"),
    cancel: (poId) => cancelPurchaseOrder(poId),
    'pdf-download': (poId) => { window.location.href = `/purchase/orders/${poId}/pdf?mode=download`; },
    receive: (poId) => openReceivePurchaseOrderFormDrawer(poId),
};

document.addEventListener('click', function (e) {

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

const openPoEmailComposer = function(poId) {
    _poEmailPoId = poId;
    const po = _poDetails || {};

    cleanFormInputFeedback(document.getElementById('poEmailComposerForm'));

    document.getElementById('poEmailTo').value      = po.vendor_email || '';
    document.getElementById('poEmailCc').value      = '';
    document.getElementById('poEmailSubject').value = `Purchase Order #${po.po_number || ''}`;

    _poAttachedFiles = [];
    renderPoEmailAttachmentChips();

    const vendorName = po.vendor_name || 'Vendor';
    _poEmailDefaultBody = `Dear ${vendorName},<br><br>Please find attached our Purchase Order <strong>#${po.po_number || ''}</strong> for your reference.<br><br>Kindly confirm receipt and advise the expected delivery date at your earliest convenience.<br><br>Should you have any questions, please do not hesitate to contact us.<br><br>Regards,<br>The Team`;

    if (_poJoditInstance) {
        _poJoditInstance.destruct();
        _poJoditInstance = null;
    }

    _poEmailComposerModal.show();
};

const handlePoSendEmail = async function() {
    const sendBtn = document.getElementById('poSendEmailSubmitBtn');
    const form    = document.getElementById('poEmailComposerForm');

    cleanFormInputFeedback(form);

    const to      = document.getElementById('poEmailTo').value.trim();
    const cc      = document.getElementById('poEmailCc').value.trim();
    const subject = document.getElementById('poEmailSubject').value.trim();
    const body    = _poJoditInstance ? _poJoditInstance.value : '';

    sendBtn.querySelector('.send-label').classList.add('d-none');
    sendBtn.querySelector('.sending-label').classList.remove('d-none');
    sendBtn.disabled = true;

    try {
        await api.post(`/purchase/orders/${_poEmailPoId}/send-email`, { to, cc, subject, body, attachments: _poAttachedFiles });
        notyf.success('Email sent successfully');
        _poEmailComposerModal.hide();
        refreshPurchaseOrderHistory(_poEmailPoId);
    } catch (error) {
        handleApiError(error, form);
    } finally {
        sendBtn.querySelector('.send-label').classList.remove('d-none');
        sendBtn.querySelector('.sending-label').classList.add('d-none');
        sendBtn.disabled = false;
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