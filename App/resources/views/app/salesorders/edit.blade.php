@extends('layouts.app')
@section('title', 'Sales Order')

@section('content')
<!-- Content -->
<div class="container-fluid flex-grow-1 container-p-y">

    <div id="actionButtons"></div>

    <div class="row g-4">
        <div class="col-lg-8">

            <div class="card" id="soDetails">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-8">
                        <h5 class="mb-0" id="soNumber">Sales Order <strong>#0000000</strong></h5>
                        <div class="d-flex gap-2" id="soBadges"></div>
                    </div>

                    <div class="row g-3 mb-4">
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

            <div class="card h-100">
                <div class="card-header d-flex justify-content-between">
                    <h5 class="card-title m-0 me-2">Activity Timeline</h5>
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

@endsection

@push('scripts')
<script>
const renderSODetailsSection = async function(soDetails) {

    _soDetails = soDetails;

    const soDetailsWrapper = document.querySelector("#soDetails");
    soDetailsWrapper.querySelector('#soNumber strong').innerHTML = `#${soDetails.so_number}`;

    const badgeWrap = soDetailsWrapper.querySelector('#soBadges');
    badgeWrap.innerHTML = '';

    const soStatus = soDetails.status;
    const statusMap = {
        draft:     ['Draft',     'warning'],
        confirmed: ['Confirmed', 'primary'],
        cancelled: ['Cancelled', 'danger'],
    };

    if (statusMap[soStatus]) {
        badgeWrap.insertAdjacentHTML('beforeend',
            `<span class="badge bg-label-${statusMap[soStatus][1]}">${statusMap[soStatus][0]}</span>`
        );
    }

    soDetailsWrapper.querySelector('#soCustomer').innerHTML   = soDetails.customer_name || '-';
    soDetailsWrapper.querySelector('#orderDate').innerHTML    = formatMySqlDate(soDetails.order_date);
    soDetailsWrapper.querySelector('#expectedDate').innerHTML = formatMySqlDate(soDetails.expected_delivery_date);
    soDetailsWrapper.querySelector('#soReference').innerHTML = soDetails.reference || '-';
    soDetailsWrapper.querySelector('#paymentTerms').innerHTML = soDetails.payment_terms || '-';
    soDetailsWrapper.querySelector('#soNotes').innerHTML      = soDetails.notes || '-';

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
    let editBtn = cancelBtn = confirmBtn = ``;
    let printBtn = `<button class="btn btn-secondary btn-sm so-action-btn" data-action="print"><i class="icon-base bx bx-printer icon-sm me-2"></i>Print</button>`;

    if (soStatus === 'draft') {
        editBtn    = `<button class="btn btn-warning btn-sm so-action-btn" data-action="edit"><i class="icon-base bx bx-edit icon-sm me-2"></i>Edit</button>`;
        confirmBtn = `<button class="btn btn-success btn-sm so-action-btn" data-action="confirmed"><i class="icon-base bx bx-like icon-sm me-2"></i>Mark Confirmed</button>`;
        cancelBtn  = `<button class="btn btn-danger btn-sm so-action-btn" data-action="cancel"><i class="icon-base bx bx-x icon-sm me-1"></i>Cancel</button>`;
    } else if (soStatus === 'confirmed') {
        cancelBtn  = `<button class="btn btn-danger btn-sm so-action-btn" data-action="cancel"><i class="icon-base bx bx-x icon-sm me-1"></i>Cancel Order</button>`;
    }

    const actionBtnsHtml = `<div class="d-flex justify-content-between align-items-center mb-3">
        <div class="d-flex gap-2">
            ${editBtn}
            ${confirmBtn}
            ${printBtn}
            ${cancelBtn}
        </div>
    </div>`;

    document.getElementById('actionButtons').innerHTML = actionBtnsHtml;
}


const refreshSalesOrderDetails = async function(soId) {
    try {
        const response = await api.get(`/sales-orders/${soId}`);
        const { data } = response.data;
        renderSODetailsSection(data.so_details);
    } catch (error) {
        notyf.error("Unable to load sales order details");
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
            const formattedHtml = formatChange(oldVal, newVal);
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
                    html += `<li class="ps-0">Qty: ${formatChange(item.old_qty, item.new_qty)}</li>`;
                }
                if (item.old_unit_price != item.new_unit_price) {
                    html += `<li class="ps-0">Unit Price: ${formatChange(item.old_unit_price, item.new_unit_price)}</li>`;
                }
                if (item.old_discount !== item.new_discount) {
                    html += `<li class="ps-0">Discount: ${formatChange(item.old_discount || 'None', item.new_discount || 'None')}</li>`;
                }
            }
            html += `</ul>`;
        });
    }
    else if (activityType === 'status_changed') {
        html += `<ul class="mt-2 mb-2 ps-7 small">
            <li class="ps-0">${formatChange(ucFirst(meta.old_status_label || meta.old_status || ''), ucFirst(meta.new_status_label || meta.new_status || ''))}</li>
        </ul>`;
        if (meta.notes) {
            html += `<div class="small text-muted ps-7">${meta.notes}</div>`;
        }
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
        const activityType = item.activity_type || '';
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
        const response = await api.get(`/sales-orders/${soId}/history`);
        const { data } = response.data;
        renderSalesOrderHistory(data);
    } catch (error) {
        notyf.error("Unable to load sales order history");
    }
}


const updateSalesOrderStatus = async function(soId, status, notes = '') {
    try {
        const response = await api.post(`/sales-orders/${soId}/status`, { status, notes });
        let message = 'Status updated successfully';
        if (status === 'confirmed') message = 'Sales order confirmed successfully';
        if (status === 'cancelled') message = 'Sales order cancelled';
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
});


const soActionHandlers = {
    edit:      (soId) => openSalesOrderFormDrawer(soId),
    confirmed: (soId) => {
        Swal.fire({
            title: 'Confirm Sales Order?',
            text: 'Once confirmed, this order cannot be edited.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirm',
        }).then(result => {
            if (result.isConfirmed) updateSalesOrderStatus(soId, 'confirmed');
        });
    },
    cancel: (soId) => {
        Swal.fire({
            title: 'Cancel Sales Order?',
            text: 'This action is permanent and cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Yes, Cancel It',
            confirmButtonColor: '#d33',
        }).then(result => {
            if (result.isConfirmed) updateSalesOrderStatus(soId, 'cancelled');
        });
    },
    print: (soId) => alert('Print not implemented yet'),
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
</script>
@endpush
