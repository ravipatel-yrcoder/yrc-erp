@extends('layouts.app')
@section('title', 'Purchase Order')

@section('content')
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">        
    
    <div id="actionButtons"></div>
    
    <div class="row g-4">
        <div class="col-lg-8" id="poDetails">
            <div class="card mb-4">
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

            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title m-0 me-2">Activity Timeline</h5>
                    <div class="dropdown">
                        <button class="btn text-body-secondary p-0" type="button" id="timelineWapper" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" fdprocessedid="h2a62n">
                            <i class="icon-base bx bx-dots-vertical-rounded icon-lg"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-end" aria-labelledby="timelineWapper">
                            <a class="dropdown-item" href="javascript:void(0);">Select All</a>
                            <a class="dropdown-item" href="javascript:void(0);">Refresh</a>
                            <a class="dropdown-item" href="javascript:void(0);">Share</a>
                        </div>
                    </div>
                </div>
                <div class="card-body pt-2">
                    <ul class="timeline mb-0">
                        <li class="timeline-item timeline-item-transparent">
                            <span class="timeline-point timeline-point-primary"></span>
                            <div class="timeline-event">
                            <div class="timeline-header mb-3">
                                <h6 class="mb-0">12 Invoices have been paid</h6>
                                <small class="text-body-secondary">12 min ago</small>
                            </div>
                            <p class="mb-2">Invoices have been paid to the company</p>
                            <div class="d-flex align-items-center mb-1">
                                <div class="badge bg-lighter rounded-2">
                                <img src="https://demos.themeselection.com/sneat-bootstrap-html-laravel-admin-template/demo/assets/img/icons/misc/pdf.png" alt="img" width="15" class="me-2">
                                <span class="h6 mb-0 text-body">invoices.pdf</span>
                                </div>
                            </div>
                            </div>
                        </li>
                        <li class="timeline-item timeline-item-transparent">
                            <span class="timeline-point timeline-point-success"></span>
                            <div class="timeline-event">
                            <div class="timeline-header mb-3">
                                <h6 class="mb-0">Client Meeting</h6>
                                <small class="text-body-secondary">45 min ago</small>
                            </div>
                            <p class="mb-2">Project meeting with john @10:15am</p>
                            <div class="d-flex justify-content-between flex-wrap gap-2">
                                <div class="d-flex flex-wrap align-items-center">
                                <div class="avatar avatar-sm me-2">
                                    <img src="https://demos.themeselection.com/sneat-bootstrap-html-laravel-admin-template/demo/assets/img/avatars/1.png" alt="Avatar" class="rounded-circle">
                                </div>
                                <div>
                                    <p class="mb-0 small fw-medium">Lester McCarthy (Client)</p>
                                    <small>CEO of ThemeSelection</small>
                                </div>
                                </div>
                            </div>
                            </div>
                        </li>
                        <li class="timeline-item timeline-item-transparent">
                            <span class="timeline-point timeline-point-info"></span>
                            <div class="timeline-event">
                            <div class="timeline-header mb-3">
                                <h6 class="mb-0">Create a new project for client</h6>
                                <small class="text-body-secondary">2 Day Ago</small>
                            </div>
                            <p class="mb-2">6 team members in a project</p>
                            <ul class="list-group list-group-flush">
                                <li class="list-group-item d-flex justify-content-between align-items-center flex-wrap p-0">
                                <div class="d-flex flex-wrap align-items-center">
                                    <ul class="list-unstyled users-list d-flex align-items-center avatar-group m-0 me-2">
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" class="avatar pull-up" aria-label="Vinnie Mostowy" data-bs-original-title="Vinnie Mostowy">
                                        <img class="rounded-circle" src="https://demos.themeselection.com/sneat-bootstrap-html-laravel-admin-template/demo/assets/img/avatars/5.png" alt="Avatar">
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" class="avatar pull-up" aria-label="Allen Rieske" data-bs-original-title="Allen Rieske">
                                        <img class="rounded-circle" src="https://demos.themeselection.com/sneat-bootstrap-html-laravel-admin-template/demo/assets/img/avatars/12.png" alt="Avatar">
                                    </li>
                                    <li data-bs-toggle="tooltip" data-popup="tooltip-custom" data-bs-placement="top" class="avatar pull-up" aria-label="Julee Rossignol" data-bs-original-title="Julee Rossignol">
                                        <img class="rounded-circle" src="https://demos.themeselection.com/sneat-bootstrap-html-laravel-admin-template/demo/assets/img/avatars/6.png" alt="Avatar">
                                    </li>
                                    <li class="avatar">
                                        <span class="avatar-initial rounded-circle pull-up text-heading" data-bs-toggle="tooltip" data-bs-placement="bottom" data-bs-original-title="3 more">+3</span>
                                    </li>
                                    </ul>
                                </div>
                                </li>
                            </ul>
                        </div>
                    </li>
                    </ul>
                </div>
                </div>

        </div>
    
    </div>

</div>
<!-- / Content -->

@include('app.components.drawers.purchase-orders.add-edit')
@include('app.components.drawers.purchase-orders.receive')

@endsection

@push('scripts')
<script>
const renderPODetailsSection = async function(poDetails) {

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

        // Receiving badge (derived from received_qty)
        const hasReceived = poDetails.line_items?.some(
            item => parseFloat(item.received_qty) > 0
        );

        badgeWrap.insertAdjacentHTML(
            'beforeend',
            `<span class="badge bg-label-${hasReceived ? 'info' : 'warning'}">
                ${hasReceived ? 'Partially Received' : 'Not Received'}
            </span>`
        );
    }    

    poDetailsWrapper.querySelector('#poVendor').innerHTML = poDetails.vendor_name || '-';
    poDetailsWrapper.querySelector('#orderDate').innerHTML = formatMySqlDate(poDetails.order_date);
    poDetailsWrapper.querySelector('#expectedDate').innerHTML = formatMySqlDate(poDetails.expected_delivery_date);
    poDetailsWrapper.querySelector('#paymentTerms').innerHTML = poDetails.payment_terms || '-';
    poDetailsWrapper.querySelector('#notes').innerHTML = poDetails.notes || '-';
    
    const tbody = poDetailsWrapper.querySelector('#lineItemsTable tbody');
    tbody.innerHTML = '';

    let grandTotal = 0;
    let taxTotal = 0;

    (poDetails.line_items || []).forEach(item => {

        grandTotal += parseFloat(item.line_total);
        taxTotal += parseFloat(item.tax_amount || 0);

        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>
                    <div class="fw-medium">${item.product_name}</div>
                    ${item.description ? `<small class="text-muted">${item.description}</small>` : ''}
                </td>
                <td class="text-end">${formatQty(item.ordered_qty)}</td>
                <td class="text-end">${formatQty(item.received_qty)}</td>
                <td class="text-end">${formatCurrency(item.unit_price)}</td>
                <td class="text-end">${formatCurrency(item.tax_amount)}</td>
                <td class="text-end fw-semibold">${formatCurrency(item.line_total)}</td>
            </tr>
        `);
    });
    
    const subTotal = grandTotal - taxTotal;
    const totalsTable = document.getElementById('totalsTable');
    totalsTable.innerHTML = `
        <tr>
            <th class="ps-0 text-muted">Subtotal</th>
            <td class="px-0 text-end">${formatCurrency(subTotal)}</td>
        </tr>
        <tr>
            <th class="ps-0 text-muted">Tax</th>
            <td class="px-0 text-end">${formatCurrency(taxTotal)}</td>
        </tr>
        <tr class="border-top">
            <th class="ps-0">Total</th>
            <td class="px-0 text-end fw-bold">${formatCurrency(grandTotal)}</td>
        </tr>
    `;


    // Action Buttons
    let editBtn = sendEmailBtn = issuedBtn = cancelBtn = receiveBtn = ``;    
    let printBtn = `<button class="btn btn-secondary btn-sm po-action-btn" id="printButton" data-action="print"><i class="icon-base bx bx-printer icon-sm me-2"></i>Print</button>`;
    if( poStatus !== 'cancelled' && poStatus !== 'closed' ) {
        editBtn = `<button class="btn btn-warning btn-sm po-action-btn" id="editButton" data-action="edit"><i class="icon-base bx bx-edit icon-sm me-2"></i>Edit</button>`;
        sendEmailBtn = `<button class="btn btn-info btn-sm po-action-btn" id="sendEmailButton" data-action="send_email"><i class="icon-base bx bx-envelope icon-sm me-2"></i>Send email</button>`;
    }

    if( poStatus === 'draft' ) {
        issuedBtn = `<button class="btn btn-success btn-sm po-action-btn" id="markConfirmedButton" data-action="confirmed"><i class="icon-base bx bx-like icon-sm me-2"></i>Mark confirmed</button>`;
    }

    if( poStatus === 'draft' || poStatus === 'confirmed' ) {
        cancelBtn = `<button class="btn btn-danger btn-sm po-action-btn" id="cancelButton" data-action="cancel"><i class="icon-base bx bx-x icon-sm me-1"></i>Cancel</button>`;
    }

    if( poStatus === 'confirmed' ) {
        receiveBtn = `<button class="btn btn-primary btn-sm po-action-btn" id="receiveButton" data-action="receive"><i class="icon-base bx bx-import icon-sm me-1"></i>Receive</button>`;
    }

    const actionBtnsHtml = `<div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">            
            ${editBtn}            
            ${sendEmailBtn}
            ${receiveBtn}
            ${issuedBtn}
            ${printBtn}
            ${cancelBtn}
        </div>
    </div>`;

    const actionButtonsEl = document.getElementById('actionButtons');
    actionButtonsEl.innerHTML = actionBtnsHtml;
}

const refreshPurchaseOrderDetails = async function(poId) {

    try {

        const response = await axios.get(`/api/purchase-orders/${poId}`);
        const { data } = response.data;
        const poDetails = data.po_details;

        renderPODetailsSection(poDetails);

    } catch (error) {
        //console.log(error);
        notyf.error("Unable to load purchase order details");
    }
}

document.addEventListener('DOMContentLoaded', async () => {

    const poId = "{{ request()->getInput('id') ?? '' }}";
    if (!poId) return;

    refreshPurchaseOrderDetails(poId);
});


const updatePurchaseOrderStatus = async function(poId, status, notes='') {

    try {

        const response = await axios.post(`/api/purchase-orders/${poId}/status`, {status, notes});
        const { data } = response.data;
        
        let message = "Status updated successfully";
        if( status === "confirmed" ) {
            message = "Purchase order approved/confirmed successfully";
        }

        notyf.success(message);

        refreshPurchaseOrderDetails(poId);

    } catch (error) {
        //console.log(error);
        notyf.error("Failed to update status");
    }

}


const actionHandlers = {
    edit: (poId) => openPurchaseOrderFormDrawer(poId),
    send_email: (poId) => alert("Send email"),
    confirmed: (poId) => updatePurchaseOrderStatus(poId, "confirmed", "PO Confirmed by user"),
    cancel: (poId) => alert("Cancel"),
    print: (poId) => alert("Print"),
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

</script>
@endpush