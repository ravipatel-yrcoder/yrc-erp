@extends('layouts.app')
@section('title', 'Purchase Order')

@section('content')
<!-- Content -->
<div class="container-fluid">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Purchase Receipt</h4>
    </div>

    <div id="actionButtons"></div>
    
    <div class="row g-4">
        <div class="col-lg-8" id="receiptDetails">
            
            <div class="card mb-4">
                <div class="card-header py-0">
                    <div class="d-flex align-items-stretch">
                        <!-- Tabs -->
                        <ul class="nav nav-tabs flex-shrink-0 gap-4" role="tablist">
                            <li class="nav-item">
                                <button class="nav-link doc-tab px-0" data-bs-target="#billsTab" type="button">Bills <span class="badge bg-label-primary ms-1">0</span></button>
                            </li>
                            <li class="nav-item">
                                <button class="nav-link doc-tab px-0" data-bs-target="#poTab" type="button">Purchase Orders <span class="badge bg-label-primary ms-1">0</span></button>
                            </li>
                        </ul>

                        <button class="accordion-toggle flex-grow-1 px-0 border-0 bg-transparent text-end" type="button" aria-label="Toggle">
                            <i class="bx bx-chevron-down fs-4"></i>
                        </button>
                    </div>
                </div>

                <div id="documentsCollapse" class="accordion-collapse collapse">
                    <div class="card-body">
                        <div class="tab-content px-0">

                            <!-- Bills -->
                            <div class="tab-pane fade" id="billsTab">
                                <div class="table-responsive border border-bottom-0 border-top-0 rounded">
                                    <table class="table m-0">
                                    <thead>
                                        <tr>
                                        <th>Bill#</th>
                                        <th>Date</th>
                                        <th>Status</th>
                                        <th>Due Date</th>
                                        <th>Amount</th>
                                        <th>Balance Due</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                        <td>
                                            <a href="#" class="text-primary fw-medium">987458</a>
                                        </td>
                                        <td>29/01/2026</td>
                                        <td>
                                            <span class="badge bg-label-success">PAID</span>
                                        </td>
                                        <td>29/01/2026</td>
                                        <td>₹1,125.00</td>
                                        <td>₹0.00</td>
                                        </tr>
                                    </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Purchase Orders -->
                            <div class="tab-pane fade" id="poTab">
                                <div class="text-muted py-4 text-center">
                                    Purchase order content goes here
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>


            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-8">
                        <h5 class="mb-0" id="receiptNumber">Purchase Receive <strong>#0000000</strong></h5>
                        <div class="d-flex gap-2" id="receiptBadges"></div>
                    </div>

                    <div class="row g-3 mb-4">
                        
                        <div class="col-md-4">
                            <h6 class="mb-0">Vendor</h6>
                            <p class="mb-0" id="poVendor">-</p>
                        </div>

                        <div class="col-md-4">
                            <h6 class="mb-0">Purchase Order</h6>
                            <p class="mb-0" id="poNumber">-</p>
                        </div>

                        <div class="col-md-4">
                            <h6 class="mb-0">Received Date</h6>
                            <p class="mb-0" id="receivedDate">-</p>
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
                                    <th>#</th>
                                    <th>Item</th>
                                    <th class="text-end">Qty</th>
                                </tr>
                            </thead>
                            <tbody><tr><td colspan="3" class="text-center">No data</td></tr></tbody>
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
                    <ul class="timeline timeline-outline  mb-0" id="receiptHistoryTimeline">
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

@include('app.components.drawers.purchase-orders.receive')

@endsection

@push('scripts')
<script>
const renderReceiptDetailsSection = async function(receiptDetails) {

    const receiptDetailsWrapper = document.querySelector("#receiptDetails");
    receiptDetailsWrapper.querySelector('#receiptNumber strong').innerHTML = `#${receiptDetails.receipt_number}`;

    const badgeWrap = receiptDetailsWrapper.querySelector('#receiptBadges');
    badgeWrap.innerHTML = '';

    const receiptStatus = receiptDetails.status;

    const statusMap = {
        draft: ['Draft', 'warning'],
        in_transit: ['In Transit', 'info'],
        received: ['Received', 'success'],
        cancelled: ['Cancelled', 'danger'],
    };

    if (statusMap[receiptStatus]) {
        badgeWrap.insertAdjacentHTML(
            'beforeend',
            `<span class="badge bg-label-${statusMap[receiptStatus][1]}">${statusMap[receiptStatus][0]}</span>`
        );
    }

    const receivedDate = receiptDetails.received_date ? formatMySqlDate(receiptDetails.received_date) : "-"

    receiptDetailsWrapper.querySelector('#poVendor').innerHTML = receiptDetails.vendor_name || '-';
    receiptDetailsWrapper.querySelector('#poNumber').innerHTML = receiptDetails.po_number || "-";
    receiptDetailsWrapper.querySelector('#receivedDate').innerHTML = receivedDate;
    receiptDetailsWrapper.querySelector('#notes').innerHTML = receiptDetails.notes || '-';
    
    const tbody = receiptDetailsWrapper.querySelector('#lineItemsTable tbody');
    tbody.innerHTML = '';

    let count = 1;
    (receiptDetails.line_items || []).forEach(item => {

        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>${count}</td>
                <td>
                    <div class="fw-medium">${item.product_name}</div>
                    ${item.description ? `<small class="text-muted">${item.description}</small>` : ''}
                </td>
                <td class="text-end">${formatQty(item.received_qty)} <span class="fs-tiny fw-semibold">${item.uom_code}</span></td>
            </tr>
        `);

        count++;
    });
    
    
    // Action Buttons
    let editBtn = inTransitBtn = receiveBtn = ``;    
    let printBtn = `<button class="btn btn-secondary btn-sm receipt-action-btn" id="printButton" data-action="print"><i class="icon-base bx bx-printer icon-sm me-2"></i>Print</button>`;
    if( receiptStatus !== 'cancelled' && receiptStatus !== 'received' ) {
        editBtn = `<button class="btn btn-warning btn-sm receipt-action-btn" id="editButton" data-action="edit"><i class="icon-base bx bx-edit icon-sm me-2"></i>Edit</button>`;
    }

    if( receiptStatus === 'draft' ) {
        inTransitBtn = `<button class="btn btn-info btn-sm receipt-action-btn" id="markInTransitButton" data-action="in_transit"><i class="icon-base bx bx-like icon-sm me-2"></i>Mark in transit</button>`;
    }

    if( receiptStatus === 'draft' || receiptStatus === 'in_transit' ) {
        receiveBtn = `<button class="btn btn-success btn-sm receipt-action-btn" id="markConfirmedButton" data-action="received"><i class="icon-base bx bx-like icon-sm me-2"></i>Mark received</button>`;
    }

    

    const actionBtnsHtml = `<div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">            
            ${editBtn}            
            ${inTransitBtn}
            ${receiveBtn}
            ${printBtn}
        </div>
    </div>`;

    const actionButtonsEl = document.getElementById('actionButtons');
    actionButtonsEl.innerHTML = actionBtnsHtml;
}


const receiptFieldChangeFormat = function(oldVal, newVal, data={}) {
    
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


const renderReceiptHistoryItemMeta = function(activityType, meta={}) {
    
    if (!meta || typeof meta !== 'object') return '';

    let html = '';
    if( activityType === "created" ) {
        html = `<ul class="mt-2 mb-2 ps-3 small">`;
            html += `<li>Status: <strong class='text-primary'>${ucFirst(meta.status)}</strong></li>`;
        html += `</ul>`;    
    }
    else if (activityType === "status_changed") {
        html += `<ul class="mt-2 mb-2 ps-7 small">
            <li class="ps-0">${receiptFieldChangeFormat(ucFirst(meta.old_status), ucFirst(meta.new_status))}</li>
        </ul>`;
    }
    else if (activityType === "received") {
        html += `<ul class="mt-2 mb-2 ps-7 small">
            <li class="ps-0">Items: <strong class="text-primary">${meta.items_count}</strong></li>
            <li class="ps-0">Qty: <strong class="text-primary">${meta.quantities}</strong></li>
        </ul>`;
    }
    else if (activityType === "updated_line_items") {
        
        if (Array.isArray(meta) && meta.length > 0) {
            const eventColorMap = { created: 'success', updated: 'warning', deleted: 'danger' };
            html += `<ul class="mt-2 mb-2 ps-3 small">`;
            meta.forEach(item => {
                const badgeColor = eventColorMap[item.event] || 'secondary';
                let qtyHtml = '';
                if (item.event === 'updated') {
                    qtyHtml = receiptFieldChangeFormat(item.old_qty, item.new_qty);
                } else if (item.event === 'created') {
                    qtyHtml = `<span class="text-primary">${item.new_qty}</span>`;
                } else if (item.event === 'deleted') {
                    qtyHtml = `<span class="text-muted text-decoration-line-through">${item.old_qty}</span>`;
                }
                html += `<li><span class="badge bg-label-${badgeColor} me-1">${ucFirst(item.event)}</span>${item.prod_name} ${qtyHtml}</li>`;
            });
            html += `</ul>`;
        }
    }

    return html;
}

const renderReceiptHistory = function(history = []) {

    const container = document.getElementById('receiptHistoryTimeline');
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
                    ${renderReceiptHistoryItemMeta(activityType, item_meta)}
                    <div class="small text-muted mb-1">
                        <div>${item.date_time || '-'}</div>
                    </div>
                </div>
            </li>
        `;

        container.insertAdjacentHTML('beforeend', itemHtml);
    });
}

const refreshReceiptDetails = async function(receiptId) {

    try {

        const response = await api.get(`/purchase/receipts/${receiptId}`);
        const { data } = response.data;
        const receiptDetails = data.receipt_details;

        //console.log(receiptDetails);

        renderReceiptDetailsSection(receiptDetails);

    } catch (error) {
        //console.log(error);
        //notyf.error("Unable to load purchase receive details");
        handleApiError(error);
    }
}


const refreshReceiptHistory = async function(receiptId) {

    try {

        const response = await api.get(`/purchase/receipts/${receiptId}/history`);
        const { data } = response.data;

        renderReceiptHistory(data);            

    } catch (error) {        
        notyf.error("Unable to load receipt history");
    }
}

document.addEventListener('DOMContentLoaded', async () => {

    const receiptId = "{{ request()->getInput('id') ?? '' }}";
    if (!receiptId) return;

    refreshReceiptDetails(receiptId); // Load receipt details
    refreshReceiptHistory(receiptId);  // Load receipt history
});


const updateReceiptOrderStatus = async function(receiptId, status, notes='') {

    try {

        const response = await api.post(`/purchase/receipts/${receiptId}/status`, {status, notes});
        const { data } = response.data;
        
        let message = "Status updated successfully";
        if( status === "received" ) {
            message = "Marked as received";
        }

        notyf.success(message);

        refreshReceiptDetails(receiptId);
        refreshReceiptHistory(receiptId);

    } catch (error) {
                
        //notyf.error("Failed to update status");
        handleApiError(error);
    }

}


// After a receipt is saved (create or edit) from the drawer, refresh this page
document.addEventListener('receiptFormSaved', function(e) {
    const receiptId = e.detail.receiptId || "{{ request()->getInput('id') ?? '' }}";
    if (!receiptId) return;
    refreshReceiptDetails(receiptId);
    refreshReceiptHistory(receiptId);
});


const actionHandlers = {
    edit: (receiptId) => openEditReceivePurchaseFormDrawer(receiptId),
    in_transit: (receiptId) => updateReceiptOrderStatus(receiptId, "in_transit"),
    received: (receiptId) => updateReceiptOrderStatus(receiptId, "received"),
    print: (receiptId) => alert("Print"),
};


document.addEventListener('click', function (e) {

    const btn = e.target.closest('.receipt-action-btn');
    if (!btn) return;

    const receiptId = "{{ request()->getInput('id') ?? '' }}";
    if (!receiptId) return;

    const action = btn.dataset.action;
    if (actionHandlers[action]) {
        actionHandlers[action](receiptId);
    } else {
        console.warn(`No handler registered for action: ${action}`);
    }
});


/*
document.addEventListener('DOMContentLoaded', function () {
  
    const collapseEl = document.getElementById('documentsCollapse');
    const collapse = new bootstrap.Collapse(collapseEl, { toggle: false });

    const tabs = document.querySelectorAll('.doc-tab');
    const panes = document.querySelectorAll('.tab-pane');
    const firstTab = tabs[0];

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
        
            activateTab(this);

            // Expand if collapsed
            if (!collapseEl.classList.contains('show')) {
                collapse.show();
            }
        });
    });

    // Accordion expand → always activate first tab
    collapseEl.addEventListener('shown.bs.collapse', function () {
        activateTab(firstTab);
    });

    // Accordion collapse → deactivate all tabs
    collapseEl.addEventListener('hidden.bs.collapse', function () {
        deactivateAllTabs();
    });

    // Header toggle
    document.querySelector('.accordion-toggle').addEventListener('click', function () {
        collapse.toggle();
        this.querySelector('i').classList.toggle('bx-chevron-up');
        this.querySelector('i').classList.toggle('bx-chevron-down');
    });
});
*/

</script>
@endpush