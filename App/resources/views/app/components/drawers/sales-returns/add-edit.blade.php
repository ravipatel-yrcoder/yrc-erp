<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditSalesReturn" aria-labelledby="addEditSalesReturnTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 65%;">

    <div class="offcanvas-header border-bottom">
        <h5 id="addEditSalesReturnTitle" class="offcanvas-title">Customer Return</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- BODY -->
    <div class="offcanvas-body">
        <form id="addEditSalesReturnForm" novalidate>

            <input type="hidden" id="retFormId" value="" />
            <input type="hidden" id="retSoId" value="" />
            <input type="hidden" id="retNumberSuggested" value="" />

            <!-- SO Context (read-only) -->
            <div class="row g-3 mb-4 pb-3 border-bottom">
                <div class="col-md-6">
                    <label class="form-label">Sales Order</label>
                    <input type="text" class="form-control" id="retSoDisplay" readonly disabled placeholder="—" />
                </div>
                <div class="col-md-6">
                    <label class="form-label">Customer</label>
                    <input type="text" class="form-control" id="retCustomerDisplay" readonly disabled placeholder="—" />
                </div>
            </div>

            <div class="form-glob-feedback"></div>

            <!-- Return Details -->
            <div class="mb-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label required">Return #</label>
                        <input type="text" class="form-control" name="return_number" id="retNumber" placeholder="Return number" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label required">Return Date</label>
                        <input type="text" class="form-control" name="return_date" id="retReturnDate" placeholder="Return date" />
                    </div>
                    @if(Service_CompanySettings::isMultiWarehouseEnabled(tenantContext()->companyId))
                    <div class="col-md-4">
                        <label class="form-label required">Receive at Warehouse</label>
                        <select class="form-select" name="received_warehouse_id" id="retWarehouseId"></select>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-6">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" id="retNotes" rows="2" placeholder="Optional notes for this return"></textarea>
            </div>

            <!-- Return Items -->
            <div class="items-section-feedback form-section-feedback"></div>
            <div class="mb-7">
                <h6 class="text-uppercase text-muted mb-3">Return Items</h6>
                <!-- RETURN ALL ITEMS TOGGLE (new return only) -->
                <div class="d-none mb-3" id="retReturnAllToggleWrap">
                    <div class="d-flex align-items-center gap-3">
                        <div class="form-check form-switch mb-0">
                            <input class="form-check-input" type="checkbox" id="retReturnAllToggle" role="switch" />
                            <label class="form-check-label fw-medium" for="retReturnAllToggle">Return All Items</label>
                        </div>
                        <small class="text-muted">Add all delivered items from the sales order to the return.</small>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="retItemsTable">
                        <thead class="table-light">
                            <tr>
                                <th class="p-2" style="width:140px">Item</th>
                                <th class="p-2 text-end" style="width:100px">Available</th>
                                <th class="p-2 text-end" style="width:120px">Return Qty</th>
                                <th class="p-2" style="width:190px">Disposition</th>
                                <th class="p-2" style="width:180px">Reason</th>
                                <th class="p-2" style="width:40px"></th>
                            </tr>
                        </thead>
                        <tbody id="retItemsTbody">
                            <tr id="retNoItemsRow">
                                <td colspan="6" class="text-center text-muted py-3">Click "+ Add Item" to add return items</td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2 d-none" id="retAddItemBtn">
                        <i class="bx bx-plus"></i> Add Item
                    </button>
                </div>
            </div>

        </form>
    </div>
    <!-- / BODY -->

    <!-- FOOTER -->
    <div class="offcanvas-footer border-top p-3">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-primary btn-sm" id="saveRetFormBtn">Save as Draft</button>
            <button type="button" class="btn btn-success btn-sm" id="saveRetReceivedBtn">Save as Received</button>
            <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
    <!-- / FOOTER -->

</div>

<script>
let _retFormContext     = null;
let _retLoadedSoItemIds = new Set();
let _retIsEditMode      = false;

const openSalesReturnFormDrawer = function(returnId = 0, soId = 0) {

    _retIsEditMode = returnId > 0;

    const title = _retIsEditMode ? 'Edit Customer Return' : 'Customer Return';
    document.getElementById('addEditSalesReturnTitle').textContent = title;
    document.getElementById('saveRetFormBtn').textContent          = _retIsEditMode ? 'Save Changes' : 'Save as Draft';
    document.getElementById('saveRetReceivedBtn').style.display    = _retIsEditMode ? 'none' : '';

    const drawerEl = document.getElementById('addEditSalesReturn');
    const formEl   = document.getElementById('addEditSalesReturnForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    document.getElementById('retFormId').value          = '';
    document.getElementById('retSoId').value            = '';
    document.getElementById('retNumberSuggested').value = '';
    document.getElementById('retSoDisplay').value       = '';
    document.getElementById('retCustomerDisplay').value = '';
    document.getElementById('retReturnAllToggle').checked = false;
    _retLoadedSoItemIds = new Set();
    _retFormContext     = null;
    renderRetItems([]);

    initDatePicker('#retReturnDate', _retIsEditMode ? {} : { defaultDate: 'today' });
    initSelect2('#retWarehouseId', {
        dropdownParent: drawerEl,
        placeholder: 'Choose Warehouse',
        allowClear: false,
    });

    if (_retIsEditMode) {
        document.getElementById('retFormId').value = returnId;
        loadRetFormContext(returnId, 0);
    } else {
        document.getElementById('retSoId').value = soId;
        loadRetFormContext(0, soId);
    }

    new bootstrap.Offcanvas(drawerEl).show();
};


const loadRetFormContext = async function(returnId = 0, soId = 0) {

    try {
        const params = new URLSearchParams();
        if (returnId > 0) params.append('id', returnId);
        if (soId > 0)     params.append('so_id', soId);

        const res  = await axios.get('/api/sales/returns/form-context?' + params.toString());
        const data = res.data.data;
        _retFormContext = data;

        const so = data.so || {};
        document.getElementById('retSoDisplay').value       = so.so_number || '';
        document.getElementById('retCustomerDisplay').value = so.customer_name || '';

        const locSel = jQuery('#retWarehouseId');
        locSel.empty().append('<option value="">Choose Warehouse</option>');
        (data.locations || []).forEach(loc => locSel.append(new Option(loc.name, loc.id)));

        if (returnId > 0 && data.return_details) {
            populateRetForm(data.return_details, locSel);
        } else {
            document.getElementById('retNumber').value          = data.suggested_return_number || '';
            document.getElementById('retNumberSuggested').value = data.suggested_return_number || '';
            if (so.source_warehouse_id) {
                locSel.val(so.source_warehouse_id).trigger('change');
            } else {
                locSel.trigger('change');
            }
            renderRetItems([]);
        }

        _retToggleAddItemBtn();

    } catch (e) {
        notyf.error('Failed to load form context');
    }
};


const populateRetForm = function(details, locSel) {

    document.getElementById('retNumber').value          = details.return_number || '';
    document.getElementById('retNumberSuggested').value = '';
    document.getElementById('retNotes').value           = details.notes || '';

    datePickerSetDate('#retReturnDate', details.return_date);

    if (details.received_warehouse_id) {
        locSel.val(details.received_warehouse_id).trigger('change');
    } else {
        locSel.trigger('change');
    }

    const prefillItems = (details.items || []).map(ri => {
        const soItem = (_retFormContext.items || []).find(i => i.so_item_id == ri.so_item_id);
        if (!soItem) return null;
        const snById = {};
        (soItem.available_serials || []).forEach(s => { snById[s.id] = s.serial_number; });
        const prefillSerials = (ri.serials || []).map(s => snById[s.serial_id] || s.serial_number).filter(Boolean);
        return {
            ...soItem,
            prefillQty:      parseFloat(ri.return_qty),
            prefillDispId:   ri.return_disposition_id,
            prefillReasonId: ri.return_reason_id,
            prefillSerials,
        };
    }).filter(Boolean);

    renderRetItems(prefillItems, true);
};


const _retToggleAddItemBtn = function() {
    const hasMore = (_retFormContext?.items || []).some(
        item => !_retLoadedSoItemIds.has(String(item.so_item_id)) && item.available_return_qty > 0
    );
    document.getElementById('retAddItemBtn').classList.toggle('d-none', !hasMore);
    const toggleWrap = document.getElementById('retReturnAllToggleWrap');
    if (toggleWrap) toggleWrap.classList.toggle('d-none', _retIsEditMode || !_retFormContext);
};


const _retGetDefaultDispositionId = function() {
    if (!_retFormContext) return '';
    const def = (_retFormContext.dispositions || []).find(d => d.is_default == 1);
    return def ? def.id : ((_retFormContext.dispositions || [])[0]?.id || '');
};

const _retGetDefaultReasonId = function() {
    if (!_retFormContext) return '';
    const def = (_retFormContext.reasons || []).find(r => r.is_default == 1);
    return def ? def.id : '';
};


const renderRetItems = function(items, isPrefill = false) {

    const tbody = document.getElementById('retItemsTbody');
    tbody.innerHTML = '';
    _retLoadedSoItemIds = new Set();

    if (!items || items.length === 0) {
        const msg = isPrefill ? 'No items on this return' : 'Click "+ Add Item" to add return items';
        tbody.innerHTML = `<tr id="retNoItemsRow"><td colspan="6" class="text-center text-muted py-3">${msg}</td></tr>`;
        _retToggleAddItemBtn();
        return;
    }

    items.forEach(item => retAppendItemRow(item));
    _retToggleAddItemBtn();
};


const updateRetItemSerialBadge = function(rowEl) {

    const trigger = rowEl.querySelector('.ret-serial-trigger');
    if (!trigger || trigger.classList.contains('d-none')) return;

    const qty           = Math.round(parseFloat(rowEl.querySelector('.ret-return-qty')?.value) || 0);
    const selectedCount = rowEl.querySelectorAll('.ret-serial-inputs input').length;
    const badge         = trigger.querySelector('.ret-serial-badge');
    const label         = trigger.querySelector('.ret-serial-label');
    const complete      = qty > 0 && selectedCount >= qty;

    badge.className      = 'ret-serial-badge ms-1 badge ' + (complete ? 'bg-label-success' : 'bg-label-secondary');
    badge.style.fontSize = '11px';
    badge.textContent    = selectedCount + ' / ' + qty;
    label.textContent    = complete ? 'Serials' : 'Assign Serials';
    label.className      = 'ret-serial-label small ' + (complete ? 'text-success' : 'text-muted');
};


const retAppendItemRow = function(item) {

    const soItemId     = item.so_item_id;
    const availableQty = Math.round(parseFloat(item.available_return_qty || 0));

    if (_retLoadedSoItemIds.has(String(soItemId))) return;
    if (availableQty <= 0 && item.prefillQty === undefined) return;

    _retLoadedSoItemIds.add(String(soItemId));

    const noRowEl = document.getElementById('retNoItemsRow');
    if (noRowEl) noRowEl.remove();

    const dispositions = (_retFormContext?.dispositions || []);
    const reasons      = (_retFormContext?.reasons      || []);
    const selDispId    = item.prefillDispId   !== undefined ? item.prefillDispId   : _retGetDefaultDispositionId();
    const selReasonId  = item.prefillReasonId !== undefined ? item.prefillReasonId : _retGetDefaultReasonId();
    const rowQty          = item.prefillQty      !== undefined ? Math.round(item.prefillQty) : availableQty;
    const isSerial        = item.stock_tracking_method === 'serial';
    const isNonStockTracked = item.stock_tracking_method === 'none';

    const dispOptions = dispositions.map(d =>
        `<option value="${d.id}" ${d.id == selDispId ? 'selected' : ''}>${d.name}</option>`
    ).join('');

    const reasonOptions = '<option value="">— None —</option>' + reasons.map(r =>
        `<option value="${r.id}" ${r.id == selReasonId ? 'selected' : ''}>${r.name}</option>`
    ).join('');

    const row = document.createElement('tr');
    row.id = `ret-row-${soItemId}`;
    row.dataset.soItemId = soItemId;
    row.innerHTML = `
        <td class="p-2">
            <div class="fw-medium">${item.product_name}</div>
            ${item.uom_code ? `<div class="text-muted" style="font-size:11px;">${item.uom_code}</div>` : ''}
            <div class="ret-serial-trigger mt-1 d-none">
                <a href="javascript:void(0);" class="ret-open-serial-picker text-decoration-none d-flex align-items-center">
                    <i class="bx bx-barcode me-1 text-muted"></i>
                    <span class="ret-serial-label text-muted small">Assign Serials</span>
                    <span class="ret-serial-badge ms-1 badge badge-sm bg-label-secondary" style="font-size:11px;">0 / 0</span>
                </a>
                <div class="ret-serial-error text-danger d-none" style="font-size:11px;"></div>
            </div>
            <div class="ret-serial-inputs"></div>
        </td>
        <td class="p-2 text-end">
            ${formatQty(availableQty)}
            <span class="fs-tiny fw-semibold ms-1">${item.uom_code || ''}</span>
        </td>
        <td class="p-2 text-end">
            <input type="number" class="form-control form-control-sm text-end ret-return-qty"
                   data-so-item-id="${soItemId}"
                   min="1" max="${availableQty}" step="1"
                   value="${rowQty}" style="width:90px;" />
        </td>
        ${isNonStockTracked
            ? `<td class="p-2 text-muted small text-center">—</td>`
            : `<td class="p-2"><select class="form-select form-select-sm ret-disposition-sel" data-so-item-id="${soItemId}">${dispOptions}</select></td>`
        }
        <td class="p-2">
            <select class="form-select form-select-sm ret-reason-sel" data-so-item-id="${soItemId}">${reasonOptions}</select>
        </td>
        <td class="p-2 text-center">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger ret-remove-row-btn" title="Remove">
                <i class="bx bx-trash text-danger cursor-pointer"></i>
            </button>
        </td>
    `;

    document.getElementById('retItemsTbody').appendChild(row);

    const dispSelEl   = row.querySelector('.ret-disposition-sel');
    const reasonSelEl = row.querySelector('.ret-reason-sel');
    if (dispSelEl)   initSelect2(dispSelEl,   { dropdownParent: jQuery('#addEditSalesReturn'), allowClear: false, resetVal: false });
    initSelect2(reasonSelEl, { dropdownParent: jQuery('#addEditSalesReturn'), allowClear: false, resetVal: false });

    if (isSerial) {
        const triggerEl = row.querySelector('.ret-serial-trigger');
        triggerEl.classList.remove('d-none');

        const prefillSerials = item.prefillSerials || [];
        if (prefillSerials.length > 0) {
            const inputsEl = row.querySelector('.ret-serial-inputs');
            prefillSerials.forEach(sn => {
                const inp = document.createElement('input');
                inp.type  = 'hidden';
                inp.value = sn;
                inputsEl.appendChild(inp);
            });
        }
        updateRetItemSerialBadge(row);

        row.querySelector('.ret-return-qty').addEventListener('input', function() {
            updateRetItemSerialBadge(row);
        });

        row.querySelector('.ret-open-serial-picker').addEventListener('click', function() {
            const qty     = Math.round(parseFloat(row.querySelector('.ret-return-qty')?.value) || 0);
            const current = Array.from(row.querySelectorAll('.ret-serial-inputs input')).map(i => i.value);

            openSerialPicker({
                qty,
                productId:        item.product_id,
                productName:      item.product_name,
                warehouseLabel:    'Delivered to customer',
                preloadedSerials: (item.available_serials || []).map(s => s.serial_number),
                currentSerials:   current,
                allowPartial:     false,
                onConfirm: function(selectedSerialNumbers) {
                    const inputsEl = row.querySelector('.ret-serial-inputs');
                    inputsEl.innerHTML = '';
                    selectedSerialNumbers.forEach(sn => {
                        const inp = document.createElement('input');
                        inp.type  = 'hidden';
                        inp.value = sn;
                        inputsEl.appendChild(inp);
                    });
                    const errorEl = row.querySelector('.ret-serial-error');
                    if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('d-none'); }
                    updateRetItemSerialBadge(row);
                },
            });
        });
    }

    row.querySelector('.ret-remove-row-btn').addEventListener('click', function() {
        _retLoadedSoItemIds.delete(String(soItemId));
        row.remove();
        if (document.getElementById('retItemsTbody').children.length === 0) {
            renderRetItems([]);
        } else {
            _retToggleAddItemBtn();
        }
    });

    _retToggleAddItemBtn();
};


const collectRetItems = function() {

    const snToId = {};
    (_retFormContext?.items || []).forEach(ctxItem => {
        (ctxItem.available_serials || []).forEach(s => { snToId[s.serial_number] = s.id; });
    });

    const items = [];
    document.querySelectorAll('#retItemsTbody tr[data-so-item-id]').forEach(row => {
        const soItemId      = parseInt(row.dataset.soItemId);
        const qtyInput      = row.querySelector('.ret-return-qty');
        const dispSel       = row.querySelector('.ret-disposition-sel');
        const reasonSel     = row.querySelector('.ret-reason-sel');
        const serialNumbers = Array.from(row.querySelectorAll('.ret-serial-inputs input')).map(i => i.value).filter(Boolean);

        items.push({
            so_item_id:            soItemId,
            return_qty:            parseFloat(qtyInput?.value) || 0,
            return_disposition_id: parseInt(dispSel?.value) || 0,
            return_reason_id:      parseInt(reasonSel?.value) || 0,
            serial_ids:            serialNumbers.map(sn => snToId[sn]).filter(Boolean),
        });
    });
    return items;
};


const submitReturnForm = async function(status = 'draft') {

    const formEl = document.getElementById('addEditSalesReturnForm');
    const editId = parseInt(document.getElementById('retFormId').value) || 0;

    cleanFormInputFeedback(formEl);

    const returnNumber = document.getElementById('retNumber').value.trim();
    if (!returnNumber) {
        showFormInputFeedback(document.getElementById('retNumber'), 'Return number is required');
        return;
    }

    let serialError = false;
    document.querySelectorAll('#retItemsTbody tr[data-so-item-id]').forEach(row => {
        const trigger = row.querySelector('.ret-serial-trigger');
        if (!trigger || trigger.classList.contains('d-none')) return;

        const required      = Math.round(parseFloat(row.querySelector('.ret-return-qty')?.value) || 0);
        const selectedCount = row.querySelectorAll('.ret-serial-inputs input').length;

        if (selectedCount !== required) {
            serialError = true;
            const errorEl = row.querySelector('.ret-serial-error');
            if (errorEl) {
                errorEl.textContent = `Assign ${required} serial number${required !== 1 ? 's' : ''} before saving`;
                errorEl.classList.remove('d-none');
            }
        }
    });

    if (serialError) {
        showFormSectionFeedback(formEl.querySelector('.items-section-feedback'), 'Please assign serial numbers to all serial-tracked items');
        return;
    }

    const payload = {
        so_id:                   parseInt(document.getElementById('retSoId').value) || 0,
        return_number:           returnNumber,
        return_number_suggested: document.getElementById('retNumberSuggested').value.trim(),
        return_date:             document.getElementById('retReturnDate').value,
        received_warehouse_id:    parseInt(jQuery('#retWarehouseId').val()) || 0,
        notes:                   document.getElementById('retNotes').value.trim(),
        items:                   collectRetItems(),
        status,
    };

    try {
        if (editId) {
            await axios.post(`/api/sales/returns/${editId}`, payload);
            notyf.success('Return updated successfully');
            bootstrap.Offcanvas.getInstance(document.getElementById('addEditSalesReturn'))?.hide();
            document.dispatchEvent(new CustomEvent('returnFormSaved', { detail: { returnId: editId } }));
        } else {
            const res   = await axios.post('/api/sales/returns', payload);
            const newId = res.data.data?.id;
            const msg   = status === 'received' ? 'Return created and received — inventory updated' : 'Return created successfully';
            notyf.success(msg);
            bootstrap.Offcanvas.getInstance(document.getElementById('addEditSalesReturn'))?.hide();
            document.dispatchEvent(new CustomEvent('returnFormSaved', { detail: { returnId: newId } }));
        }
    } catch (e) {
        handleApiError(e, formEl);
    }
};


// Return All Items toggle
document.getElementById('retReturnAllToggle').addEventListener('change', function() {
    if (!this.checked) return;

    const toggle = this;
    if (!_retFormContext) { toggle.checked = false; return; }

    const remaining = (_retFormContext.items || []).filter(item =>
        !_retLoadedSoItemIds.has(String(item.so_item_id)) && item.available_return_qty > 0
    );

    showConfirmation(
        'This will add all returnable items and set quantities to maximum available.',
        'question',
        {
            text: 'Return All', class: 'btn-warning',
            callback: function() {
                // Dismiss any open "Add Item" selector row before populating
                const tempRow = document.getElementById('retAddItemTempRow');
                if (tempRow) tempRow.remove();
                // Add any items not yet in table
                remaining.forEach(item => retAppendItemRow(item));
                // Set all row quantities to their max available
                document.querySelectorAll('#retItemsTbody tr[data-so-item-id]').forEach(row => {
                    const soItemId = parseInt(row.dataset.soItemId);
                    const ctxItem  = (_retFormContext.items || []).find(i => i.so_item_id == soItemId);
                    if (!ctxItem) return;
                    const maxQty   = Math.round(parseFloat(ctxItem.available_return_qty || 0));
                    const qtyInput = row.querySelector('.ret-return-qty');
                    if (qtyInput && maxQty > 0) {
                        qtyInput.value = maxQty;
                        qtyInput.dispatchEvent(new Event('input'));
                    }
                });
            },
        },
        {
            text: 'Cancel',
            callback: function() { toggle.checked = false; },
        }
    );
});


// + Add Item button
document.getElementById('retAddItemBtn').addEventListener('click', function() {

    if (document.getElementById('retAddItemTempRow')) return;

    const soItems   = (_retFormContext?.items || []);
    const available = soItems.filter(item => !_retLoadedSoItemIds.has(String(item.so_item_id)) && item.available_return_qty > 0);

    const tbody = document.getElementById('retItemsTbody');
    const noRow = document.getElementById('retNoItemsRow');
    if (noRow) noRow.remove();

    const optionsHtml = available.map(item =>
        `<option value="${item.so_item_id}">${item.product_name} (Available: ${formatQty(item.available_return_qty)} ${item.uom_code || ''})</option>`
    ).join('');

    const tempRow = document.createElement('tr');
    tempRow.id = 'retAddItemTempRow';
    tempRow.innerHTML = `
        <td colspan="5">
            <select class="form-select form-select-sm" id="retAddItemSelect">
                <option value=""></option>
                ${optionsHtml}
            </select>
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger" id="retCancelAddItemBtn" title="Cancel">
                <i class="bx bx-trash text-danger cursor-pointer"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tempRow);

    document.getElementById('retAddItemBtn').classList.add('d-none');

    document.getElementById('retCancelAddItemBtn').addEventListener('click', function() {
        tempRow.remove();
        if (document.getElementById('retItemsTbody').children.length === 0) {
            renderRetItems([]);
        } else {
            _retToggleAddItemBtn();
        }
    });

    initSelect2(document.getElementById('retAddItemSelect'), {
        dropdownParent: jQuery('#addEditSalesReturn'),
        placeholder: 'Select a product...',
        allowClear: false,
        onChange: function(_this) {
            const selectedId = _this.value;
            if (!selectedId) return;
            const item = soItems.find(i => String(i.so_item_id) === String(selectedId));
            if (item) {
                tempRow.remove();
                retAppendItemRow(item);
                _retToggleAddItemBtn();
            }
        },
    });
});


// Save as Draft
document.getElementById('saveRetFormBtn').addEventListener('click', async function() {
    var btn = this;
    setButtonLoading(btn, true);
    try {
        await submitReturnForm('draft');
    } finally {
        setButtonLoading(btn, false);
    }
});

// Save as Received
document.getElementById('saveRetReceivedBtn').addEventListener('click', async function() {
    var btn = this;
    setButtonLoading(btn, true);
    try {
        await submitReturnForm('received');
    } finally {
        setButtonLoading(btn, false);
    }
});
</script>
