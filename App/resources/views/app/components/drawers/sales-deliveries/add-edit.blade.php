<style>
#dnAddNewAddressBtn {
    left: 0;
    bottom: -20px;
}
</style>

<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditSalesDelivery" aria-labelledby="addEditSalesDeliveryTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 65%;">

    <div class="offcanvas-header border-bottom">
        <h5 id="addEditSalesDeliveryTitle" class="offcanvas-title">New Delivery Note</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- BODY -->
    <div class="offcanvas-body">
        <form id="addEditSalesDeliveryForm">

            <input type="hidden" id="dnFormId" value="" />
            <input type="hidden" name="dn_number_suggested" id="dnNumberSuggested" value="" />
            <input type="hidden" name="sales_order_id" id="dnSalesOrderId" value="" />
            <input type="hidden" name="customer_id" id="dnCustomerId" value="" />

            <div class="form-glob-feedback"></div>

            <!-- General Information -->
            <div class="mb-6">
                <div class="row g-4">

                    <!-- Sales Order (read-only) -->
                    <div class="col-md-6">
                        <label class="form-label">Sales Order</label>
                        <input type="text" class="form-control" id="dnSoDisplay" readonly disabled placeholder="—" />
                    </div>

                    <!-- DN Number -->
                    <div class="col-md-3">
                        <label class="form-label required">DN Number</label>
                        <input type="text" class="form-control" name="dn_number" id="dnNumber" placeholder="DN Number" />
                    </div>

                    <!-- Location -->
                    <div class="col-md-3">
                        <label class="form-label required">Location</label>
                        <select class="form-select" name="location_id" id="dnLocationId"></select>
                    </div>

                    <!-- Dispatch Date -->
                    <div class="col-md-3">
                        <label class="form-label required">Dispatch Date</label>
                        <input type="text" class="form-control" name="dispatch_date" id="dnDispatchDate" placeholder="Dispatch Date" />
                    </div>

                    <!-- Delivery Date -->
                    <div class="col-md-3">
                        <label class="form-label">Delivery Date</label>
                        <input type="text" class="form-control" name="delivery_date" id="dnDeliveryDate" placeholder="Delivery Date" />
                    </div>

                    <!-- Delivery Method -->
                    <div class="col-md-3">
                        <label class="form-label">Delivery Method</label>
                        <select class="form-select" name="fulfilment_type" id="dnDeliveryMethod">
                            <option value="pickup">Pickup</option>
                            <option value="shipment">Shipment</option>
                        </select>
                    </div>

                </div>
            </div>

            <!-- Shipment Section (shown only when Delivery Method = Shipment) -->
            <div class="mb-7 position-relative d-none" id="dnShipmentSection">
                <h6 class="mb-3 text-uppercase text-muted small fw-semibold">Shipment Details</h6>
                <div class="row g-4">

                    <div class="col-md-6">
                        <label class="form-label">Carrier</label>
                        <input type="text" class="form-control" name="carrier" placeholder="e.g. FedEx, DHL" />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Tracking Number</label>
                        <input type="text" class="form-control" name="tracking_number" placeholder="Tracking number" />
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Delivery Address</label>
                        <select class="form-select" name="delivery_address_id" id="dnDeliveryAddressId">
                            <option value="">Select address...</option>
                        </select>
                        <a href="javascript:void(0);" id="dnAddNewAddressBtn" class="mt-1 position-absolute fs-13">Add new address</a>
                    </div>

                </div>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2" placeholder="Delivery notes..."></textarea>
            </div>

            <!-- Line Items -->
            <div class="items-section-feedback form-section-feedback"></div>
            <div class="mb-7">
                <h6 class="text-uppercase text-muted mb-3">Line Items</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="dnItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="p-2">Product</th>
                                <th class="p-2 text-end" style="width:110px">Ordered</th>
                                <th class="p-2 text-end" style="width:110px">Remaining</th>
                                <th class="p-2 text-end" style="width:130px">Dispatch Qty</th>
                                <th class="p-2" style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="dnItemsTbody">
                            <tr id="dnNoItemsRow">
                                <td colspan="5" class="text-center text-muted py-3">Select a sales order to load items</td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 d-none" id="dnAddItemBtn">
                        <i class="bx bx-plus"></i> Add Item
                    </button>
                </div>
            </div>

        </form>
    </div>
    <!-- / BODY -->

    <!-- FOOTER -->
    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" class="btn btn-secondary btn-sm" id="saveDnDraftBtn">Save as Draft</button>
            <button type="button" class="btn btn-primary btn-sm" id="dispatchNowBtn">Dispatch Now</button>
            <button type="button" class="btn btn-success btn-sm" id="deliverNowBtn">Deliver Now</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
    <!-- / FOOTER -->

</div>

@include('app.components.drawers.customers.address')

<script>
let _dnFormContext = null;
let _dnLoadedSoItemIds = new Set();

const openDeliveryFormDrawer = function(dnId = 0, soId = 0) {

    const title = dnId > 0 ? 'Edit Delivery Note' : 'New Delivery Note';
    document.getElementById('addEditSalesDeliveryTitle').innerHTML = title;

    const drawerEl = document.getElementById('addEditSalesDelivery');
    const formEl = document.getElementById('addEditSalesDeliveryForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector('#dnFormId').value = '';
    formEl.querySelector('#dnSalesOrderId').value = '';
    formEl.querySelector('#dnCustomerId').value = '';
    formEl.querySelector('#dnNumberSuggested').value = '';
    document.getElementById('dnSoDisplay').value = '';
    document.getElementById('dnShipmentSection').classList.add('d-none');
    _dnLoadedSoItemIds = new Set();
    renderDnItems([]);

    // Date pickers
    initDatePicker('#dnDispatchDate', dnId > 0 ? {} : { defaultDate: 'today' });
    initDatePicker('#dnDeliveryDate', {});

    // Location Select2
    initSelect2('#dnLocationId', {
        dropdownParent: drawerEl,
        placeholder: 'Choose location',
        allowClear: false,
    });

    // Delivery method Select2
    const deliveryMethodChange = function(_this) {
        document.getElementById('dnShipmentSection').classList.toggle('d-none', _this.value !== 'shipment');
    };
    initSelect2('#dnDeliveryMethod', {
        dropdownParent: drawerEl,
        minimumResultsForSearch: -1,
        onChange: deliveryMethodChange,
    });

    // Delivery address Select2
    initSelect2('#dnDeliveryAddressId', {
        dropdownParent: drawerEl,
        placeholder: 'Select address...',
        allowClear: true,
    });

    if (dnId > 0) {
        formEl.querySelector('#dnFormId').value = dnId;
        loadDnFormContext(dnId, 0);
    } else {
        loadDnFormContext(0, soId);
    }

    new bootstrap.Offcanvas(drawerEl).show();
};


const loadDnFormContext = async function(dnId = 0, soId = 0) {

    try {
        const params = new URLSearchParams();
        if (dnId > 0) params.append('id', dnId);
        if (soId > 0) params.append('so_id', soId);

        const response = await api.get('/sales-deliveries/form-context?' + params.toString());
        const { data } = response.data;
        _dnFormContext = data;

        // Suggested DN number
        document.getElementById('dnNumber').value = data.suggested_dn_number || '';
        document.getElementById('dnNumberSuggested').value = data.suggested_dn_number || '';

        // Populate locations dropdown
        const locationSelect = jQuery('#dnLocationId');
        locationSelect.empty().append('<option value="">Choose location</option>');
        (data.locations || []).forEach(loc => locationSelect.append(new Option(loc.name, loc.id)));

        // Populate SO info and auto-select location
        const soInfo = data.so_info || {};
        if (soInfo.id) {
            document.getElementById('dnSalesOrderId').value = soInfo.id;
            document.getElementById('dnCustomerId').value = soInfo.customer_id || '';
            document.getElementById('dnSoDisplay').value = soInfo.so_number ? `${soInfo.so_number} — ${soInfo.customer_name || ''}` : '';
            if (soInfo.location_id) {
                locationSelect.val(soInfo.location_id).trigger('change');
            }
        }

        // Populate delivery addresses
        const addresses = data.customer_addresses || [];
        const addrSelect = jQuery('#dnDeliveryAddressId');
        addrSelect.empty().append('<option value="">Select address...</option>');
        addresses.forEach(addr => addrSelect.append(new Option(addr.label, addr.id)));
        addrSelect.trigger('change');

        // Populate form for edit
        if (dnId > 0 && data.dn_details) {
            populateDnForm(data.dn_details);
        }

        // Render line items
        if (dnId > 0 && data.dn_details && data.dn_details.items) {
            // Edit mode: render only items already saved on this DN,
            // pre-filled with their saved dispatched_qty.
            const dispatchedMap = {};
            data.dn_details.items.forEach(dnItem => {
                dispatchedMap[dnItem.sales_order_item_id] = parseFloat(dnItem.dispatched_qty);
            });

            const editItems = data.so_items
                .filter(soItem => dispatchedMap[soItem.id] !== undefined)
                .map(soItem => ({ ...soItem, prefillQty: dispatchedMap[soItem.id] }));

            renderDnItems(editItems);

        } else {
            // New mode: render all SO items with remaining qty.
            const deliverable = (data.so_items || []).filter(item => item.remaining_qty > 0);
            renderDnItems(deliverable);
        }

    } catch (error) {
        notyf.error('Failed to load form context');
    }
};


const populateDnForm = function(details) {

    document.getElementById('dnSalesOrderId').value = details.sales_order_id || '';

    if (details.location_id) {
        jQuery('#dnLocationId').val(details.location_id).trigger('change');
    }

    const form = document.getElementById('addEditSalesDeliveryForm');
    ['dn_number', 'carrier', 'tracking_number', 'notes'].forEach(field => {
        const el = form.querySelector(`[name="${field}"]`);
        if (el) el.value = details[field] || '';
    });

    const method = details.fulfilment_type || 'pickup';
    jQuery('#dnDeliveryMethod').val(method).trigger('change');

    datePickerSetDate('#dnDispatchDate', details.dispatch_date);
    datePickerSetDate('#dnDeliveryDate', details.delivery_date);

    if (details.delivery_address_id) {
        jQuery('#dnDeliveryAddressId').val(details.delivery_address_id).trigger('change');
    }

    /*if (details.dn_number) {
        document.getElementById('dnNumber').value = details.dn_number;
    }*/
};


const _dnToggleAddItemBtn = function() {
    const soItems = (_dnFormContext && _dnFormContext.so_items) ? _dnFormContext.so_items : [];
    const hasMore = soItems.some(item => !_dnLoadedSoItemIds.has(String(item.id)));
    document.getElementById('dnAddItemBtn').classList.toggle('d-none', !hasMore);
};


const renderDnItems = function(soItems) {

    const tbody = document.getElementById('dnItemsTbody');
    tbody.innerHTML = '';
    _dnLoadedSoItemIds = new Set();

    if (!soItems || soItems.length === 0) {
        tbody.innerHTML = '<tr id="dnNoItemsRow"><td colspan="5" class="text-center text-muted py-3">No items to dispatch</td></tr>';
        _dnToggleAddItemBtn();
        return;
    }

    soItems.forEach(item => {
        _dnLoadedSoItemIds.add(String(item.id));
        dnAppendItemRow(item);
    });

    _dnToggleAddItemBtn();
};


const dnAppendItemRow = function(item) {

    const tbody = document.getElementById('dnItemsTbody');
    const remaining = parseFloat(item.remaining_qty || 0);

    const row = document.createElement('tr');
    row.dataset.soItemId = item.id;
    row.dataset.remaining = remaining;

    row.innerHTML = `
        <td>
            <input type="hidden" name="dn_items_placeholder[sales_order_item_id]" value="${item.id}" />
            <input type="hidden" name="dn_items_placeholder[product_id]" value="${item.product_id}" />
            <input type="hidden" name="dn_items_placeholder[uom_code]" value="${item.uom_code || ''}" />
            <div class="fw-medium">${item.product_name}</div>
            ${item.description ? `<small class="text-muted">${item.description}</small>` : ''}
        </td>
        <td class="text-end">
            ${formatQty(item.ordered_qty)}
            <span class="fs-tiny fw-semibold ms-1">${item.uom_code || ''}</span>
        </td>
        <td class="text-end ${remaining <= 0 ? 'text-danger' : ''}">
            ${formatQty(remaining)}
            <span class="fs-tiny fw-semibold ms-1">${item.uom_code || ''}</span>
        </td>
        <td class="text-end">
            <input type="number" class="form-control form-control-sm text-end dn-qty-input"
                   value="${item.prefillQty !== undefined ? item.prefillQty : remaining}"
                   min="0" step="0.01"
                   ${remaining <= 0 ? 'disabled' : ''} />
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger dn-remove-row-btn" title="Remove">
                <i class="bx bx-trash text-danger cursor-pointer"></i>
            </button>
        </td>
    `;

    row.querySelector('.dn-remove-row-btn').addEventListener('click', function() {
        _dnLoadedSoItemIds.delete(String(item.id));
        row.remove();
        if (document.getElementById('dnItemsTbody').children.length === 0) {
            renderDnItems([]);
        } else {
            _dnToggleAddItemBtn();
        }
    });

    tbody.appendChild(row);
};


const collectDnItems = function() {
    const rows = document.querySelectorAll('#dnItemsTbody tr[data-so-item-id]');
    const items = [];
    rows.forEach((row, index) => {
        const soItemId = row.dataset.soItemId;
        const productId = row.querySelector('[name$="[product_id]"]').value;
        const uomCode = row.querySelector('[name$="[uom_code]"]').value;
        const qtyInput = row.querySelector('.dn-qty-input');
        const qty = qtyInput ? parseFloat(qtyInput.value) || 0 : 0;
        items.push({
            sales_order_item_id: soItemId,
            product_id: productId,
            uom_code: uomCode,
            dispatched_qty: qty,
        });
    });
    return items;
};




// Add Item button
document.getElementById('dnAddItemBtn').addEventListener('click', function() {

    // Prevent opening a second temp row
    if (document.getElementById('dnAddItemTempRow')) return;

    const soItems = (_dnFormContext && _dnFormContext.so_items) ? _dnFormContext.so_items : [];
    const available = soItems.filter(item => !_dnLoadedSoItemIds.has(String(item.id)));

    const tbody = document.getElementById('dnItemsTbody');
    const noRow = document.getElementById('dnNoItemsRow');
    if (noRow) noRow.remove();

    // Build options
    const optionsHtml = available.map(item =>
        `<option value="${item.id}">${item.product_name} (Remaining: ${formatQty(item.remaining_qty)} ${item.uom_code || ''})</option>`
    ).join('');

    const tempRow = document.createElement('tr');
    tempRow.id = 'dnAddItemTempRow';
    tempRow.innerHTML = `
        <td colspan="4">
            <select class="form-select form-select-sm" id="dnAddItemSelect">
                <option value=""></option>
                ${optionsHtml}
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger" id="dnCancelAddItemBtn" title="Remove">
                <i class="bx bx-trash text-danger cursor-pointer"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tempRow);

    // Hide Add Item button while temp row is open
    document.getElementById('dnAddItemBtn').classList.add('d-none');

    // Cancel temp row
    document.getElementById('dnCancelAddItemBtn').addEventListener('click', function() {
        tempRow.remove();
        if (document.getElementById('dnItemsTbody').children.length === 0) {
            renderDnItems([]);
        } else {
            _dnToggleAddItemBtn();
        }
    });

    // Select2 on the dropdown
    initSelect2(document.getElementById('dnAddItemSelect'), {
        dropdownParent: jQuery('#addEditSalesDelivery'),
        placeholder: 'Select a product...',
        allowClear: false,
        onChange: function(_this) {
            const selectedId = _this.value;
            if (!selectedId) return;
            const item = soItems.find(i => String(i.id) === String(selectedId));
            if (item) {
                tempRow.remove();
                dnAppendItemRow(item);
                _dnLoadedSoItemIds.add(String(item.id));
                _dnToggleAddItemBtn();
            }
        },
    });
});


// Add new delivery address
document.getElementById('dnAddNewAddressBtn').addEventListener('click', function() {
    const customerId = document.getElementById('dnCustomerId').value;
    if (!customerId) {
        notyf.error('No customer associated with this delivery');
        return;
    }
    openCustomerAddressModal(customerId, 'shipping', {
        onSaved: function(addr) {
            const addrSelect = jQuery('#dnDeliveryAddressId');
            addrSelect.append(new Option(addr.label, addr.id));
            addrSelect.val(addr.id).trigger('change');
        },
    });
});


// Submit handler
const submitDeliveryForm = async function(status) {

    const formEl = document.getElementById('addEditSalesDeliveryForm');
    const dnId = document.getElementById('dnFormId').value;

    try {

        cleanFormInputFeedback(formEl);

        const formData = new FormData(formEl);
        const payload = formDataToObject(formData);

        // Remove placeholder keys from hidden inputs in item rows, replace with clean array
        Object.keys(payload).forEach(k => { if (k.startsWith('dn_items_placeholder')) delete payload[k]; });
        payload.items = collectDnItems();
        payload.status = status;

        const url = dnId ? `/sales-deliveries/${dnId}` : '/sales-deliveries';
        const response = await api.post(url, payload);
        const { data, message } = response.data;

        const savedDnId = data?.dn_id || dnId;

        notyf.success(message);

        bootstrap.Offcanvas.getInstance(document.getElementById('addEditSalesDelivery'))?.hide();
        formEl.reset();

        document.dispatchEvent(new CustomEvent('deliveryFormSaved', { detail: { dnId: savedDnId } }));

        if (typeof salesDeliveriesDt !== 'undefined') salesDeliveriesDt.ajax.reload(null, false);

    } catch (error) {
        handleApiError(error, formEl);
    }
};


document.getElementById('saveDnDraftBtn').addEventListener('click', () => submitDeliveryForm('draft'));

document.getElementById('dispatchNowBtn').addEventListener('click', () => {
    showConfirmation(
        'Stock will be deducted from inventory immediately.',
        'question',
        { text: 'Dispatch', class: 'btn-primary',  callback: () => submitDeliveryForm('dispatched') },
        { text: 'Cancel' }
    );
});

document.getElementById('deliverNowBtn').addEventListener('click', () => {
    showConfirmation(
        'This will dispatch and mark the delivery as completed in one step.',
        'question',
        { text: 'Deliver', class: 'btn-success', callback: () => submitDeliveryForm('delivered') },
        { text: 'Cancel' }
    );
});
</script>