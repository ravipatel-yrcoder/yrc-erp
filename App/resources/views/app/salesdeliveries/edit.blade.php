@extends('layouts.app')
@section('title', 'Delivery Note')

@section('content')
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="mb-0">Delivery Details</h4>
    </div>

    <div id="dnActionButtons"></div>

    <div class="row g-4">
        <div class="col-lg-8">

            <div class="card" id="dnDetails">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-8">
                        <h5 class="mb-0" id="dnNumber">Delivery Note <strong>#0000000</strong></h5>
                        <div class="d-flex gap-2" id="dnBadges"></div>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <h6 class="mb-0">Sales Order</h6>
                            <p class="mb-0" id="dnSoNumber">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Customer</h6>
                            <p class="mb-0" id="dnCustomer">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Location</h6>
                            <p class="mb-0" id="dnLocation">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Dispatch Date</h6>
                            <p class="mb-0" id="dnDispatchDateDisplay">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Delivery Date</h6>
                            <p class="mb-0" id="dnDeliveryDateDisplay">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Carrier</h6>
                            <p class="mb-0" id="dnCarrier">-</p>
                        </div>
                        <div class="col-md-4">
                            <h6 class="mb-0">Tracking Number</h6>
                            <p class="mb-0" id="dnTrackingNumber">-</p>
                        </div>
                    </div>

                    <div class="mb-8">
                        <h6 class="mb-0">Notes</h6>
                        <p class="mb-0" id="dnNotes">-</p>
                    </div>

                    <div class="table-responsive border border-bottom-0 border-top-0 rounded">
                        <table class="table m-0" id="dnLineItemsTable">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th class="text-end">Ordered Qty</th>
                                    <th class="text-end">Dispatched Qty</th>
                                    <th>Serials / Lots</th>
                                </tr>
                            </thead>
                            <tbody><tr><td colspan="4" class="text-center">No data</td></tr></tbody>
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
                    <ul class="timeline timeline-outline mb-0" id="dnHistoryTimeline">
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

@include('app.components.drawers.sales-deliveries.add-edit')

@endsection

@push('scripts')
<script>
const dnStatusMap = {
    draft:      ['Draft',      'secondary'],
    dispatched: ['Dispatched', 'primary'],
    delivered:  ['Delivered',  'success'],
    returned:   ['Returned',   'warning'],
    lost:       ['Lost',       'danger'],
    cancelled:  ['Cancelled',  'dark'],
};

let _dnDetails = null;

const renderDnDetailsSection = function(dnDetails) {

    _dnDetails = dnDetails;

    const wrapper = document.querySelector("#dnDetails");
    wrapper.querySelector('#dnNumber strong').innerHTML = `#${dnDetails.dn_number}`;

    const badgeWrap = wrapper.querySelector('#dnBadges');
    badgeWrap.innerHTML = '';
    const s = dnStatusMap[dnDetails.status] || [dnDetails.status, 'secondary'];
    badgeWrap.insertAdjacentHTML('beforeend',
        `<span class="badge bg-label-${s[1]}">${s[0]}</span>`
    );

    wrapper.querySelector('#dnSoNumber').innerHTML       = dnDetails.sales_order_id ? `<a href="/sales-orders/${dnDetails.sales_order_id}/">${dnDetails.so_number || 'View SO'}</a>` : '-';
    wrapper.querySelector('#dnCustomer').innerHTML       = dnDetails.customer_name || '-';
    wrapper.querySelector('#dnLocation').innerHTML       = dnDetails.location_name || '-';
    wrapper.querySelector('#dnDispatchDateDisplay').innerHTML   = formatMySqlDate(dnDetails.dispatch_date);
    wrapper.querySelector('#dnDeliveryDateDisplay').innerHTML   = formatMySqlDate(dnDetails.delivery_date);
    wrapper.querySelector('#dnCarrier').innerHTML        = dnDetails.carrier || '-';
    wrapper.querySelector('#dnTrackingNumber').innerHTML = dnDetails.tracking_number || '-';
    wrapper.querySelector('#dnNotes').innerHTML          = dnDetails.notes || '-';

    const tbody = wrapper.querySelector('#dnLineItemsTable tbody');
    tbody.innerHTML = '';

    (dnDetails.items || []).forEach(item => {
        const uomCode   = item.uom_code || '';
        const serials   = (item.serials || []).join(', ') || '-';
        const lots      = (item.lots || []).map(l => `${l.lot_number} (${l.qty})`).join(', ') || '';
        const trackInfo = item.serials?.length ? serials : (lots || '-');

        tbody.insertAdjacentHTML('beforeend', `
            <tr>
                <td>
                    <div class="fw-medium">${item.product_name}</div>
                    ${item.description ? `<small class="text-muted">${item.description}</small>` : ''}
                </td>
                <td class="text-end">${formatQty(item.ordered_qty)} <span class="fs-tiny fw-semibold">${uomCode}</span></td>
                <td class="text-end">${formatQty(item.dispatched_qty)} <span class="fs-tiny fw-semibold">${uomCode}</span></td>
                <td class="text-muted small">${trackInfo}</td>
            </tr>
        `);
    });

    // Action buttons
    const dnStatus = dnDetails.status;
    let dispatchBtn = cancelBtn = deliveredBtn = returnedBtn = lostBtn = editBtn = revertBtn = '';

    if (dnStatus === 'draft') {
        editBtn = `<button class="btn btn-warning btn-sm dn-action-btn" data-action="edit"><i class="icon-base bx bx-edit icon-sm me-2"></i>Edit</button>`;
        dispatchBtn = `<button class="btn btn-primary btn-sm dn-action-btn" data-action="dispatched"><i class="icon-base bx bx-package icon-sm me-2"></i>Dispatch</button>`;
        cancelBtn = `<button class="btn btn-danger btn-sm dn-action-btn" data-action="cancelled"><i class="icon-base bx bx-x icon-sm me-1"></i>Cancel</button>`;
    } else if (dnStatus === 'dispatched') {
        deliveredBtn = `<button class="btn btn-success btn-sm dn-action-btn" data-action="delivered"><i class="icon-base bx bx-check icon-sm me-2"></i>Mark Delivered</button>`;
        returnedBtn  = `<button class="btn btn-warning btn-sm dn-action-btn" data-action="returned"><i class="icon-base bx bx-undo icon-sm me-2"></i>Mark Returned</button>`;
        lostBtn = `<button class="btn btn-danger btn-sm dn-action-btn" data-action="lost"><i class="icon-base bx bx-error icon-sm me-2"></i>Mark Lost</button>`;
        revertBtn = `<button class="btn btn-secondary btn-sm dn-action-btn" data-action="reopen"><i class="icon-base bx bx-undo icon-sm me-2"></i>Revert to Open</button>`;
    } else if (dnStatus === 'delivered') {
        revertBtn = `<button class="btn btn-secondary btn-sm dn-action-btn" data-action="reopen"><i class="icon-base bx bx-undo icon-sm me-2"></i>Revert to Open</button>`;
    }

    document.getElementById('dnActionButtons').innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="d-flex gap-2">
                ${editBtn}
                ${dispatchBtn}
                ${deliveredBtn}
                ${returnedBtn}
                ${lostBtn}
                ${revertBtn}
                ${cancelBtn}
            </div>
        </div>`;
};


const refreshDnDetails = async function(dnId) {
    try {
        const response = await api.get(`/sales-deliveries/${dnId}`);
        const { data } = response.data;
        renderDnDetailsSection(data.dn_details);
    } catch (error) {
        notyf.error("Unable to load delivery note details");
    }
};


const formatDNFieldChange = function(oldVal, newVal, data={}) {
    
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

const renderDNHistoryItemMeta = function(activityType, meta={}) {
    
    if (!meta || typeof meta !== 'object') return '';

    let html = '';
    if( activityType === "created" ) {
        html = `<ul class="mt-2 mb-2 ps-3 small">`;
            html += `<li>Status: <strong class='text-primary'>${ucFirst(meta.status || "")}</strong></li>`;
        html += `</ul>`;    
    }
    else if( activityType === "updated_details" ) {
        
        html = `<ul class="mt-2 mb-2 ps-3 small">`;
        meta.forEach(item => {            

            let finalOldVal = item.old_val || "";
            let finalNewVal = item.new_val || "";
            if( item.field === "dispatch_date" || item.field === "delivery_date" ) {
                if( finalOldVal ) {
                    finalOldVal = formatMySqlDate(finalOldVal);
                }

                if( finalNewVal ) {
                    finalNewVal = formatMySqlDate(finalNewVal);
                }
            }

            const formattedHtml = formatDNFieldChange(finalOldVal, finalNewVal);
            if( formattedHtml ) {
                html += `<li>${item.label}: <strong class='text-primary'>${formattedHtml}</strong></li>`;
            }
        });
        html += `</ul>`;
    }
    else if (activityType === "updated_items") {
        
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
                    html += `<li class="ps-0">Qty: ${formatDNFieldChange(item.old_qty, item.new_qty, changeData)}</li>`;
                }
            }

            // unit cost
            if (item.event === 'created') {
                html += `<li class="ps-0">Unit Cost: <span class="text-primary">${item.new_unit_cost}</span></li>`;
            } else if (item.event === 'deleted') {
                html += `<li class="ps-0">Unit Cost: <span class="text-muted">${item.old_unit_cost}</span></li>`;
            } else {
                if( item.old_unit_cost != item.new_unit_cost ) {
                    html += `<li class="ps-0">Unit Cost: ${formatDNFieldChange(item.old_unit_cost, item.new_unit_cost)}</li>`;
                }
            }
            html += `</ul>`;
        });
    }
    else if (activityType === "status_changed") {
        html += `<ul class="mt-2 mb-2 ps-7 small">
            <li class="ps-0">${formatDNFieldChange(ucFirst(meta.old_status), ucFirst(meta.new_status))}</li>
            ${meta.notes ? `<li class="ps-0">Reason: <span class="text-muted">${meta.notes}</span></li>` : ''}
        </ul>`;
    }

    return html;
}


const renderDnHistory = function(history = []) {

    const container = document.getElementById('dnHistoryTimeline');
    if (!container) return;
    container.innerHTML = '';

    if (!Array.isArray(history) || history.length === 0) {
        container.innerHTML = `<li class="timeline-item timeline-item-transparent"><div class="timeline-event text-muted">No history available</div></li>`;
        return;
    }

    history.forEach(item => {
        
        const item_meta = item.meta || {};
        const activityType = item.log_type || "";
        
        container.insertAdjacentHTML('beforeend', `
            <li class="timeline-item timeline-item-transparent border-dashed">
                <span class="timeline-point timeline-point-info"></span>
                <div class="timeline-event">
                    <div class="timeline-header mb-1">
                        <h6 class="mb-0">${item.title || ''}</h6>
                        <small class="text-body-secondary">${item.performed_by || 'System'}</small>
                    </div>
                    ${renderDNHistoryItemMeta(activityType, item_meta)}
                    <div class="small text-muted mb-1">${item.date_time || '-'}</div>
                </div>
            </li>
        `);
    });
};


const refreshDnHistory = async function(dnId) {
    try {
        const response = await api.get(`/sales-deliveries/${dnId}/history`);
        const { data } = response.data;
        renderDnHistory(data);
    } catch (error) {
        console.log(error);
        notyf.error("Unable to load delivery history");
    }
};


const updateDnStatus = async function(dnId, status, notes = '') {
    try {
        await api.post(`/sales-deliveries/${dnId}/status`, { status, notes });
        const labels = { dispatched: 'Dispatched', delivered: 'Marked as delivered', returned: 'Marked as returned', lost: 'Marked as lost', cancelled: 'Cancelled', draft: 'Reverted to Open' };
        notyf.success(labels[status] || 'Status updated');
        refreshDnDetails(dnId);
        refreshDnHistory(dnId);
    } catch (error) {
        handleApiError(error);
    }
};


const dnActionConfigs = {
    dispatched: { text: 'Stock will be deducted from inventory. This cannot be undone.', icon: 'question', confirmText: 'Dispatch', btnClass: 'btn-primary', input: 'textarea', inputPlaceholder: 'Notes...', inputRequired: false },
    delivered: { text: 'Confirm the customer has received the goods.', icon: 'question', confirmText: 'Mark Delivered', btnClass: 'btn-success', input: 'textarea', inputPlaceholder: 'Notes...', inputRequired: false }, 
    returned: { text: 'Stock will be restored to inventory. This cannot be undone.', icon: 'warning', confirmText: 'Mark Returned', btnClass: 'btn-warning', input: 'textarea', inputPlaceholder: 'Enter reason for return...', inputRequired: true  },
    lost: { text: 'The shipment is confirmed lost. Stock will not be restored.', icon: 'warning',  confirmText: 'Mark Lost', btnClass: 'btn-danger', input: 'textarea',  inputPlaceholder: 'Enter reason for lost...', inputRequired: true  },
    cancelled: { text: 'DN will be cancelled and stock will be restored if it was reduced.', icon: 'warning',  confirmText: 'Yes, Cancel', btnClass: 'btn-danger', input: 'textarea', inputPlaceholder: 'Enter reason for cancellation...', inputRequired: true  },
    reopen: { text: 'This will reopen to draft and restore stock. Reservation will be re-applied.', icon: 'warning', confirmText: 'Yes, Reopen', btnClass: 'btn-primary', input: 'textarea', inputPlaceholder: 'Enter reason for reopening...', inputRequired: true },
};

const dnActionHandlers = {
    edit: (dnId) => openDeliveryFormDrawer(dnId),
    dispatched: (dnId) => confirmDnAction(dnId, 'dispatched'),
    delivered: (dnId) => confirmDnAction(dnId, 'delivered'),
    returned: (dnId) => confirmDnAction(dnId, 'returned'),
    lost: (dnId) => confirmDnAction(dnId, 'lost'),
    cancelled:  (dnId) => confirmDnAction(dnId, 'cancelled'),
    reopen: (dnId) => confirmDnAction(dnId, 'reopen'),
    /*draft: (dnId) => confirmDnAction(dnId, 'draft'),*/
};

const confirmDnAction = function(dnId, status) {
    const cfg = dnActionConfigs[status];
    if (!cfg) return;

    const finalStatus = status == 'reopen' ? 'draft' : status;
    showConfirmation(
        cfg.text,
        cfg.icon,
        { text: cfg.confirmText, class: cfg.btnClass, callback: (note) => updateDnStatus(dnId, finalStatus, note || '') },
        { text: 'Cancel' },
        { input: cfg.input, inputLabel: cfg.inputLabel || "", inputPlaceholder: cfg.inputPlaceholder, inputRequired: cfg.inputRequired }
    );
};


document.addEventListener('click', function(e) {
    const btn = e.target.closest('.dn-action-btn');
    if (!btn) return;
    const dnId = "{{ request()->getInput('id') ?? '' }}";
    if (!dnId) return;
    const action = btn.dataset.action;
    if (dnActionHandlers[action]) dnActionHandlers[action](dnId);
});


document.addEventListener('deliveryFormSaved', function(e) {
    const dnId = e.detail?.dnId || "{{ request()->getInput('id') ?? '' }}";
    if (!dnId) return;
    refreshDnDetails(dnId);
    refreshDnHistory(dnId);
});


document.addEventListener('DOMContentLoaded', async () => {
    const dnId = "{{ request()->getInput('id') ?? '' }}";
    if (!dnId) return;
    refreshDnDetails(dnId);
    refreshDnHistory(dnId);
});
</script>
@endpush
