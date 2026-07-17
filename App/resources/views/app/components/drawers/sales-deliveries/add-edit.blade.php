
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
                    @if(Service_CompanySettings::isMultiWarehouseEnabled(tenantContext()->companyId))
                    <div class="col-md-3">
                        <label class="form-label required">Warehouse</label>
                        <select class="form-select" name="warehouse_id" id="dnWarehouseId"></select>
                    </div>
                    @endif

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
                            <option value="ship">Shipment</option>
                        </select>
                    </div>

                </div>
            </div>

            <!-- Shipment Section (shown only when Delivery Method = Shipment) -->
            <div class="mb-7 d-none" id="dnShipmentSection">
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
                        <div class="mt-1">
                            <a href="javascript:void(0);" id="dnAddNewAddressBtn" class="fs-13">Add new address</a>
                            <a href="javascript:void(0);" id="dnEditAddressBtn" class="fs-13 d-none">Edit address</a>
                        </div>

                        <input type="hidden" name="shipping_address_json" id="dnShippingAddressJson" />
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

@includeOnce('app.components.drawers.inventory.product-serial-lot-picker')
@includeOnce('app.components.drawers.customers.address')

<script>
let _dnFormContext      = null;
let _dnLoadedSoItemIds  = new Set();
let _dnAddrSource       = null;   // 'so' | 'customer' | null
let _dnAddrData         = {};     // current address object
let _dnAddrCustomerAddresses = [];

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
    _dnAddrSource = null;
    _dnAddrData   = {};
    _dnAddrCustomerAddresses = [];
    _dnResetAddressUI();
    renderDnItems([]);

    // Date pickers
    initDatePicker('#dnDispatchDate', dnId > 0 ? {} : { defaultDate: 'today' });
    initDatePicker('#dnDeliveryDate', {});

    // Location Select2
    initSelect2('#dnWarehouseId', {
        dropdownParent: drawerEl,
        placeholder: 'Choose location',
        allowClear: false,
        onChange: function() { clearAllDnSerials(true); },
    });

    // Delivery method Select2
    const deliveryMethodChange = function(_this) {
        document.getElementById('dnShipmentSection').classList.toggle('d-none', _this.value !== 'ship');
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
        onChange: _dnDeliveryAddressChanged,
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

        const response = await api.get('/sales/deliveries/form-context?' + params.toString());
        const { data } = response.data;
        _dnFormContext = data;

        // Suggested DN number
        document.getElementById('dnNumber').value = data.suggested_dn_number || '';
        document.getElementById('dnNumberSuggested').value = data.suggested_dn_number || '';

        // Populate locations dropdown
        const locationSelect = jQuery('#dnWarehouseId');
        locationSelect.empty().append('<option value="">Choose location</option>');
        (data.locations || []).forEach(loc => locationSelect.append(new Option(loc.name, loc.id)));

        // Populate SO info and auto-select location
        const soInfo = data.so_info || {};
        if (soInfo.id) {
            document.getElementById('dnSalesOrderId').value = soInfo.id;
            document.getElementById('dnCustomerId').value = soInfo.customer_id || '';
            document.getElementById('dnSoDisplay').value = soInfo.so_number ? `${soInfo.so_number} — ${soInfo.customer_name || ''}` : '';
            if (soInfo.source_warehouse_id) {
                locationSelect.val(soInfo.source_warehouse_id).trigger('change');
            }
            // Pre-set delivery method from SO when creating a new DN
            if (!(dnId > 0) && soInfo.delivery_type) {
                jQuery('#dnDeliveryMethod').val(soInfo.delivery_type).trigger('change');
            }
        }

        // Populate delivery addresses
        _dnAddrCustomerAddresses = data.customer_addresses || [];
        const addrSelect = jQuery('#dnDeliveryAddressId');
        addrSelect.empty().append('<option value="">Select address...</option>');

        // Prepend SO shipping address as a special option when SO has delivery_type = ship
        let soAddressPreselect = false;
        if (soInfo.delivery_type === 'ship' && soInfo.shipping_address) {
            const sa = soInfo.shipping_address;
            const parts = [sa.address_line1, sa.address_line2, sa.city, sa.state, sa.country].filter(Boolean);
            addrSelect.append(new Option('From Sales Order — ' + parts.join(', '), '_so_address'));
            soAddressPreselect = true;
        }

        _dnAddrCustomerAddresses.forEach(addr => addrSelect.append(new Option(addr.label, addr.id)));
        addrSelect.trigger('change');

        // Pre-select SO address when opening a new DN for an SO with ship delivery type
        // The change handler sets _dnAddrSource, _dnAddrData, calls _dnSyncAddressJson,
        // and toggles the Add/Edit links automatically.
        if (soAddressPreselect && !(dnId > 0)) {
            addrSelect.val('_so_address').trigger('change');
        }

        // Populate form for edit
        if (dnId > 0 && data.dn_details) {
            populateDnForm(data.dn_details);
        }

        // Render line items
        if (dnId > 0 && data.dn_details && data.dn_details.items) {
            // Edit mode: render only items already saved on this DN,
            // pre-filled with their saved dispatched_qty and serial assignments.
            const dispatchedMap = {};
            const serialsMap    = {};
            const dnItemIdMap   = {};
            data.dn_details.items.forEach(dnItem => {
                dispatchedMap[dnItem.sales_order_item_id] = parseFloat(dnItem.dispatched_qty);
                serialsMap[dnItem.sales_order_item_id]    = dnItem.serials || [];
                dnItemIdMap[dnItem.sales_order_item_id]   = dnItem.id;
            });

            const editItems = data.so_items
                .filter(soItem => dispatchedMap[soItem.id] !== undefined)
                .map(soItem => ({
                    ...soItem,
                    prefillQty:     dispatchedMap[soItem.id],
                    prefillSerials: serialsMap[soItem.id] || [],
                    dnItemId:       dnItemIdMap[soItem.id] || 0,
                }));

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

    if (details.warehouse_id) {
        jQuery('#dnWarehouseId').val(details.warehouse_id).trigger('change');
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

    // Restore saved shipping address snapshot into address state and dropdown
    if (details.shipping_address_snapshot) {
        _dnAddrSource = 'snapshot';
        _dnAddrData   = details.shipping_address_snapshot;
        const snap  = details.shipping_address_snapshot;
        const parts = [snap.address_line1, snap.address_line2, snap.city, snap.state].filter(Boolean);
        const label = parts.length > 0 ? ('Saved address — ' + parts.join(', ')) : 'Saved address';
        const addrSelect = jQuery('#dnDeliveryAddressId');
        addrSelect.append(new Option(label, '_snapshot'));
        addrSelect.val('_snapshot').trigger('change');
    }
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


/* ===================================================
   DN SERIAL PICKER — helpers
=================================================== */
const updateDnItemSerialBadge = function(rowEl) {
    const trigger = rowEl.querySelector('.dn-serial-trigger');
    if (!trigger || trigger.classList.contains('d-none')) return;
    const qty           = parseInt(rowEl.querySelector('.dn-qty-input')?.value) || 0;
    const selectedCount = rowEl.querySelectorAll('.dn-serial-inputs input').length;
    const badge         = trigger.querySelector('.dn-serial-badge');
    const label         = trigger.querySelector('.dn-serial-label');
    const complete      = qty > 0 && selectedCount >= qty;
    badge.className     = 'dn-serial-badge ms-1 badge ' + (complete ? 'bg-label-success' : 'bg-label-secondary');
    badge.style.fontSize = '11px';
    badge.textContent   = selectedCount + ' / ' + qty;
    label.textContent   = complete ? 'Serials' : 'Assign Serials';
    label.className     = 'dn-serial-label small ' + (complete ? 'text-success' : 'text-muted');
};

const clearAllDnSerials = function(showNotice) {
    let hadSerials = false;
    document.querySelectorAll('#dnItemsTbody tr[data-so-item-id]').forEach(function(row) {
        const inputs = row.querySelector('.dn-serial-inputs');
        if (inputs && inputs.children.length > 0) hadSerials = true;
        if (inputs) inputs.innerHTML = '';
        updateDnItemSerialBadge(row);
    });
    if (showNotice && hadSerials) {
        window.notyf.error('Warehouse changed — serial selections cleared');
    }
};


const dnAppendItemRow = function(item) {

    const tbody     = document.getElementById('dnItemsTbody');
    const remaining = parseFloat(item.remaining_qty || 0);
    const isSerial  = (item.stock_tracking_method || '') === 'serial';

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
            <div class="dn-serial-trigger mt-1 d-none" data-product-id="${item.product_id}" data-product-name="${item.product_name || ''}" data-dn-item-id="${item.dnItemId || 0}">
                <a href="javascript:void(0);" class="dn-open-serial-picker text-decoration-none d-flex align-items-center">
                    <i class="bx bx-barcode me-1 text-muted"></i><span class="dn-serial-label text-muted small">Assign Serials</span>
                    <span class="dn-serial-badge ms-1 badge badge-sm bg-label-secondary" style="font-size:11px;">0 / 0</span>
                </a>
                <div class="dn-serial-error text-danger d-none" style="font-size:11px;"></div>
            </div>
            <div class="dn-serial-inputs"></div>
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
                   value="${item.prefillQty !== undefined ? parseNum(item.prefillQty) : parseNum(remaining)}"
                   min="0" step="1"
                   ${remaining <= 0 ? 'disabled' : ''} />
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger dn-remove-row-btn" title="Remove">
                <i class="bx bx-trash text-danger cursor-pointer"></i>
            </button>
        </td>
    `;

    // Wire up serial trigger if product is serial-tracked
    if (isSerial) {
        const triggerEl = row.querySelector('.dn-serial-trigger');
        triggerEl.classList.remove('d-none');

        // Pre-fill serial selections (edit mode)
        const prefillSerials = item.prefillSerials || [];
        if (prefillSerials.length > 0) {
            const inputsEl = row.querySelector('.dn-serial-inputs');
            prefillSerials.forEach(function(sn) {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.value = sn;
                inputsEl.appendChild(inp);
            });
            updateDnItemSerialBadge(row);
        } else {
            updateDnItemSerialBadge(row);
        }

        // Update badge when qty changes
        row.querySelector('.dn-qty-input').addEventListener('input', function() {
            updateDnItemSerialBadge(row);
        });

        // Open serial picker on click
        row.querySelector('.dn-open-serial-picker').addEventListener('click', function() {
            let warehouseId, locationText;
            if (window.sysDefaultConfig.multiWarehouse) {
                warehouseId  = parseInt(jQuery('#dnWarehouseId').val()) || 0;
                if (!warehouseId) { window.notyf.error('Please select a warehouse first'); return; }
                locationText = jQuery('#dnWarehouseId option:selected').text() || '—';
            } else {
                const defaultLoc = (_dnFormContext && _dnFormContext.locations && _dnFormContext.locations.length > 0) ? _dnFormContext.locations[0] : null;
                warehouseId  = defaultLoc ? defaultLoc.id : 0;
                locationText = defaultLoc ? defaultLoc.name : '—';
            }
            const qty          = parseInt(row.querySelector('.dn-qty-input')?.value) || 0;
            const current      = Array.from(row.querySelectorAll('.dn-serial-inputs input')).map(function(i) { return i.value; });
            const dnItemId     = parseInt(triggerEl.dataset.dnItemId) || 0;

            openSerialPicker({
                productId:     item.product_id,
                productName:   item.product_name,
                qty,
                warehouseId,
                dnItemId,
                warehouseLabel: locationText,
                currentSerials: current,
                onConfirm: function(selected) {
                    const inputsEl = row.querySelector('.dn-serial-inputs');
                    inputsEl.innerHTML = '';
                    selected.forEach(function(sn) {
                        const inp = document.createElement('input');
                        inp.type  = 'hidden';
                        inp.value = sn;
                        inputsEl.appendChild(inp);
                    });
                    const errorEl = row.querySelector('.dn-serial-error');
                    if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('d-none'); }
                    updateDnItemSerialBadge(row);
                },
            });
        });
    }

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
    rows.forEach((row) => {
        const soItemId  = row.dataset.soItemId;
        const productId = row.querySelector('[name$="[product_id]"]').value;
        const uomCode   = row.querySelector('[name$="[uom_code]"]').value;
        const qtyInput  = row.querySelector('.dn-qty-input');
        const qty       = qtyInput ? parseFloat(qtyInput.value) || 0 : 0;
        const serialInputs = row.querySelectorAll('.dn-serial-inputs input');
        const serialNumbers = Array.from(serialInputs).map(i => i.value).filter(Boolean);
        const item = {
            sales_order_item_id: soItemId,
            product_id:          productId,
            uom_code:            uomCode,
            dispatched_qty:      qty,
        };
        if (serialNumbers.length > 0) {
            item.serial_numbers = serialNumbers;
        }
        items.push(item);
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


/* ===================================================
   DELIVERY ADDRESS HELPERS
=================================================== */

const _dnResetAddressUI = function() {
    document.getElementById('dnAddNewAddressBtn').classList.remove('d-none');
    document.getElementById('dnEditAddressBtn').classList.add('d-none');
    document.getElementById('dnShippingAddressJson').value = '';
};

const _dnSyncAddressJson = function() {
    document.getElementById('dnShippingAddressJson').value =
        (_dnAddrData && Object.keys(_dnAddrData).length > 0)
            ? JSON.stringify(_dnAddrData)
            : '';
};

// Address dropdown change — one link at a time: Add when nothing selected, Edit when selected
// Called via onChange in initSelect2 so Select2-triggered changes are captured correctly.
const _dnDeliveryAddressChanged = function(_this) {
    const val = _this.value;

    if (!val) {
        _dnAddrSource = null;
        _dnAddrData   = {};
        document.getElementById('dnAddNewAddressBtn').classList.remove('d-none');
        document.getElementById('dnEditAddressBtn').classList.add('d-none');
        document.getElementById('dnShippingAddressJson').value = '';
        return;
    }

    document.getElementById('dnAddNewAddressBtn').classList.add('d-none');
    document.getElementById('dnEditAddressBtn').classList.remove('d-none');

    if (val === '_so_address') {
        _dnAddrSource = 'so';
        const soInfo  = (_dnFormContext && _dnFormContext.so_info) ? _dnFormContext.so_info : {};
        _dnAddrData   = soInfo.shipping_address || {};
    } else if (val === '_snapshot') {
        _dnAddrSource = 'snapshot';
        // _dnAddrData already set by populateDnForm — do not overwrite
    } else {
        _dnAddrSource = 'customer';
        _dnAddrData   = _dnAddrCustomerAddresses.find(a => String(a.id) === String(val)) || {};
    }
    _dnSyncAddressJson();
};

// Edit address click — uses the customer address modal for both SO-local and customer addresses
document.getElementById('dnEditAddressBtn').addEventListener('click', function() {
    if (_dnAddrSource === 'customer') {
        const addrId     = jQuery('#dnDeliveryAddressId').val();
        const customerId = document.getElementById('dnCustomerId').value;
        if (!customerId) { notyf.error('No customer associated with this delivery'); return; }
        openCustomerAddressModal(customerId, 'shipping', {
            editId:      addrId,
            prefillData: _dnAddrData,
            onSaved: function(addr) {
                _dnAddrData = addr;
                // Refresh dropdown option label in-place
                const addrSelect = jQuery('#dnDeliveryAddressId');
                addrSelect.find(`option[value="${addrId}"]`).text(addr.label);
                const idx = _dnAddrCustomerAddresses.findIndex(a => String(a.id) === String(addrId));
                if (idx !== -1) _dnAddrCustomerAddresses[idx] = addr;
                _dnSyncAddressJson();
            },
        });
    } else {
        // SO or snapshot address: local edit only, changes stay on this delivery note
        openCustomerAddressModal(null, 'shipping', {
            mode:        'so_local',
            prefillData: _dnAddrData,
            onSaved: function(addr) {
                _dnAddrData = addr;
                _dnSyncAddressJson();
            },
        });
    }
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
            _dnAddrCustomerAddresses.push(addr);
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

        // Validate serial assignments before dispatching or delivering
        if (status === 'dispatched' || status === 'delivered') {
            let serialError = false;
            document.querySelectorAll('#dnItemsTbody tr[data-so-item-id]').forEach(function(row) {
                const trigger = row.querySelector('.dn-serial-trigger');
                if (!trigger || trigger.classList.contains('d-none')) return;
                const qty           = parseInt(row.querySelector('.dn-qty-input')?.value) || 0;
                const selectedCount = row.querySelectorAll('.dn-serial-inputs input').length;
                if (selectedCount !== qty) {
                    serialError = true;
                    const errorEl = row.querySelector('.dn-serial-error');
                    if (errorEl) {
                        errorEl.textContent = 'Assign ' + qty + ' serial number' + (qty !== 1 ? 's' : '') + ' before dispatching';
                        errorEl.classList.remove('d-none');
                    }
                }
            });
            if (serialError) {
                showFormGlobalFeedback(formEl, 'Please assign serial numbers to all serial-tracked items before dispatching.', 'error');
                return;
            }
        }

        const url = dnId ? `/sales/deliveries/${dnId}` : '/sales/deliveries';
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


document.getElementById('saveDnDraftBtn').addEventListener('click', async function() {
    var btn = this;
    setButtonLoading(btn, true);
    try {
        await submitDeliveryForm('draft');
    } finally {
        setButtonLoading(btn, false);
    }
});

document.getElementById('dispatchNowBtn').addEventListener('click', function() {
    var btn = this;
    showConfirmation(
        'Stock will be deducted from inventory immediately.',
        'question',
        {
            text: 'Dispatch', class: 'btn-primary',
            callback: async function() {
                setButtonLoading(btn, true);
                try {
                    await submitDeliveryForm('dispatched');
                } finally {
                    setButtonLoading(btn, false);
                }
            }
        },
        { text: 'Cancel' }
    );
});

document.getElementById('deliverNowBtn').addEventListener('click', function() {
    var btn = this;
    showConfirmation(
        'This will dispatch and mark the delivery as completed in one step.',
        'question',
        {
            text: 'Deliver', class: 'btn-success',
            callback: async function() {
                setButtonLoading(btn, true);
                try {
                    await submitDeliveryForm('delivered');
                } finally {
                    setButtonLoading(btn, false);
                }
            }
        },
        { text: 'Cancel' }
    );
});
</script>