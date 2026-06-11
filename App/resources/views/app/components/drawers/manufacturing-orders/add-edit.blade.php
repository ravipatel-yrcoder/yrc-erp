<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditMo" aria-labelledby="addEditMoTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 55%;">

    <div class="offcanvas-header">
        <h5 id="addEditMoTitle" class="offcanvas-title">New Manufacturing Order</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditMoForm">

            <input type="hidden" id="mo_id" value="" />
            <div class="form-glob-feedback"></div>

            <div class="row g-4">

                {{-- New mode: product + BOM dropdowns --}}
                <div class="col-md-6" id="mo_product_field">
                    <label class="form-label required">Finished Product</label>
                    <select class="form-select" name="product_id" id="mo_product_select"></select>
                </div>

                <div class="col-md-6" id="mo_bom_field">
                    <label class="form-label required">Bill of Materials</label>
                    <select class="form-select" name="bom_id" id="mo_bom_select"></select>
                </div>

                {{-- Edit mode: locked product + BOM display --}}
                <div class="col-md-12 d-none" id="mo_locked_product_bom">
                    <div class="d-flex align-items-center gap-5 p-3 bg-lighter rounded border">
                        <div>
                            <div class="text-muted small mb-1">Finished Product</div>
                            <div class="fw-semibold" id="mo_locked_product_name">—</div>
                        </div>
                        <div>
                            <div class="text-muted small mb-1">Bill of Materials</div>
                            <div class="fw-semibold" id="mo_locked_bom_name">—</div>
                        </div>
                        <div class="ms-auto text-muted small">
                            <i class="bx bx-lock-alt me-1"></i>Locked after creation
                        </div>
                    </div>
                </div>

                <div class="col-md-4">
                    <label class="form-label required">Qty to Produce</label>
                    <input type="text" class="form-control" name="planned_qty" id="mo_planned_qty" placeholder="1.00" />
                </div>

                <div class="col-md-4">
                    <label class="form-label">Scheduled Date</label>
                    <input type="text" class="form-control" name="planned_date" placeholder="Select date" />
                    <div class="form-text">Target date for production to be completed</div>
                </div>

                <div class="col-md-4"></div>

                <div class="col-md-6">
                    <label class="form-label required">Source Warehouse</label>
                    <select class="form-select" name="source_location_id" id="mo_location_select"></select>
                    <div class="form-text">Where raw materials will be taken from</div>
                </div>

                <div class="col-md-6">
                    <label class="form-label required">Destination Warehouse</label>
                    <select class="form-select" name="destination_location_id" id="mo_dest_location_select"></select>
                    <div class="form-text">Where finished goods will be stored</div>
                </div>

                <div class="col-md-12">
                    <label class="form-label">Notes</label>
                    <textarea class="form-control" name="notes" rows="2" placeholder="Internal notes"></textarea>
                </div>

            </div>

            <!-- Components Preview -->
            <div id="mo_bom_preview" class="mt-6 d-none">
                <h6 class="text-uppercase text-muted mb-3">Components</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="p-2">Item</th>
                                <th class="p-2 text-end">Required Qty</th>
                                <th class="p-2">UOM</th>
                            </tr>
                        </thead>
                        <tbody id="mo_bom_preview_body"></tbody>
                    </table>
                </div>
                <p class="text-muted small mt-2 mb-0">Required Qty updates as you change the Qty to Produce above.</p>
            </div>

        </form>
    </div>

    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveMoBtn" class="btn btn-primary btn-sm min-w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>

</div>

@push('scripts')
<script>
let moAvailableProducts = [];
let moCurrentBomItems   = [];
let moCurrentOutputQty  = 1;

const recalcRequiredQtys = function() {
    const plannedQty = parseFloat(document.getElementById('mo_planned_qty').value) || 0;
    const rows = document.querySelectorAll('#mo_bom_preview_body tr');
    moCurrentBomItems.forEach(function(item, idx) {
        if (rows[idx]) {
            const cell = rows[idx].querySelector('td.req-qty');
            if (plannedQty > 0 && moCurrentOutputQty > 0) {
                cell.textContent = formatQty((plannedQty / moCurrentOutputQty) * parseFloat(item.qty));
            } else {
                cell.textContent = '—';
            }
        }
    });
};

const renderComponentRows = function(items, plannedQty) {
    const bodyEl = document.getElementById('mo_bom_preview_body');
    bodyEl.innerHTML = '';
    items.forEach(function(item) {
        const reqQtyText = (plannedQty > 0 && moCurrentOutputQty > 0)
            ? formatQty((plannedQty / moCurrentOutputQty) * parseFloat(item.qty))
            : '—';
        bodyEl.insertAdjacentHTML('beforeend', `
            <tr>
                <td class="p-2">${item.product_name || ''}</td>
                <td class="p-2 text-end req-qty">${reqQtyText}</td>
                <td class="p-2">${item.uom_code || '—'}</td>
            </tr>
        `);
    });
    document.getElementById('mo_bom_preview').classList.remove('d-none');
};

const refreshMoForm = async function(id = 0) {

    const drawerEl = document.getElementById('addEditMo');
    const formEl   = document.getElementById('addEditMoForm');
    const isEdit   = id > 0;

    drawerEl.querySelector('#addEditMoTitle').textContent = isEdit ? 'Edit Manufacturing Order' : 'New Manufacturing Order';

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector('#mo_id').value = '';
    datePickerSetDate('#addEditMo input[name="planned_date"]', '');
    jQuery('#addEditMo select[name="source_location_id"]').val(null).trigger('change');
    jQuery('#addEditMo select[name="destination_location_id"]').val(null).trigger('change');
    document.getElementById('mo_bom_preview').classList.add('d-none');
    document.getElementById('mo_bom_preview_body').innerHTML = '';
    moCurrentBomItems  = [];
    moCurrentOutputQty = 1;

    // Toggle new vs edit mode fields
    document.getElementById('mo_product_field').classList.toggle('d-none', isEdit);
    document.getElementById('mo_bom_field').classList.toggle('d-none', isEdit);
    document.getElementById('mo_locked_product_bom').classList.toggle('d-none', !isEdit);

    try {
        const response = await api.get('/manufacturing/orders/form-context');
        _response = response;
        const ctx = response.data.data;
        moAvailableProducts = ctx.products || [];
        const locations = ctx.locations || [];

        if (!isEdit) {
            const productOptions = buildSelect2Options(moAvailableProducts, { idKey: 'id', textKey: 'name' });
            initSelect2('#addEditMo select[name="product_id"]', {
                dropdownParent: drawerEl,
                placeholder: 'Choose finished product',
                data: productOptions,
                onChange: onMoProductChange
            });
            initSelect2('#addEditMo select[name="bom_id"]', {
                dropdownParent: drawerEl,
                placeholder: 'Choose Bill of Materials',
                data: [],
                onChange: onMoBomChange
            });
        }

        const locationOptions = buildSelect2Options(locations, { idKey: 'id', textKey: 'name' });
        initSelect2('#addEditMo select[name="source_location_id"]', {
            dropdownParent: drawerEl,
            placeholder: 'Select source warehouse',
            data: locationOptions
        });
        initSelect2('#addEditMo select[name="destination_location_id"]', {
            dropdownParent: drawerEl,
            placeholder: 'Select destination warehouse',
            data: locationOptions
        });

        if (!isEdit && locations.length === 1) {
            jQuery('#addEditMo select[name="source_location_id"]').val(locations[0].id).trigger('change');
            jQuery('#addEditMo select[name="destination_location_id"]').val(locations[0].id).trigger('change');
        }

        if (isEdit) {
            const detailRes = await api.get(`/manufacturing/orders/${id}`);
            const mo = detailRes.data.data.mo_details || {};
            populateMoForm(mo);
        }

    } catch(err) {
        handleApiError(err);
    }
};

const populateMoForm = function(mo) {

    if (!mo || !mo.id) return;

    const formEl = document.getElementById('addEditMoForm');
    const { id, product_name, bom_name, source_location_id, destination_location_id,
            planned_qty, planned_date, notes, material_items = [] } = mo;

    formEl.querySelector('#mo_id').value = id || '';
    jQuery('#addEditMo [name="planned_qty"]').val(formatQty(planned_qty || 0));
    datePickerSetDate('#addEditMo input[name="planned_date"]', planned_date || '');
    jQuery('#addEditMo [name="notes"]').val(notes || '');

    if (source_location_id) {
        jQuery('#addEditMo select[name="source_location_id"]').val(source_location_id).trigger('change');
    }
    if (destination_location_id) {
        jQuery('#addEditMo select[name="destination_location_id"]').val(destination_location_id).trigger('change');
    }

    // Show locked product + BOM names
    document.getElementById('mo_locked_product_name').textContent = product_name || '—';
    document.getElementById('mo_locked_bom_name').textContent     = bom_name || '—';

    // Build components from MO snapshot — normalised to {product_name, qty, uom_code}
    // moCurrentOutputQty = original planned_qty so recalcRequiredQtys scales by ratio correctly
    if (material_items.length > 0) {
        moCurrentBomItems = material_items.map(function(mi) {
            return {
                product_name: mi.product_name,
                qty:          mi.planned_qty,
                uom_code:     mi.uom_code
            };
        });
        moCurrentOutputQty = parseFloat(planned_qty) || 1;
        renderComponentRows(moCurrentBomItems, parseFloat(planned_qty) || 0);
    }
};

const onMoProductChange = function(_this) {
    const drawerEl = document.getElementById('addEditMo');
    const prodId   = Number(_this.value) || 0;

    jQuery('#addEditMo select[name="bom_id"]').val(null).trigger('change');
    document.getElementById('mo_bom_preview').classList.add('d-none');
    document.getElementById('mo_bom_preview_body').innerHTML = '';
    moCurrentBomItems  = [];
    moCurrentOutputQty = 1;

    if (!prodId) {
        initSelect2('#addEditMo select[name="bom_id"]', {
            dropdownParent: drawerEl,
            placeholder: 'Choose Bill of Materials',
            data: [],
            onChange: onMoBomChange
        });
        return;
    }

    const product = moAvailableProducts.find(p => Number(p.id) === prodId);
    if (!product) return;

    const bomOptions = buildSelect2Options(product.boms, { idKey: 'id', textKey: 'name' });
    initSelect2('#addEditMo select[name="bom_id"]', {
        dropdownParent: drawerEl,
        placeholder: 'Choose Bill of Materials',
        data: bomOptions,
        onChange: onMoBomChange
    });

    if (product.boms.length === 1) {
        jQuery('#addEditMo select[name="bom_id"]').val(product.boms[0].id).trigger('change');
    }
};

const onMoBomChange = function(_this) {
    const bomId    = Number(_this.value) || 0;
    const prodId   = Number(jQuery('#addEditMo select[name="product_id"]').val()) || 0;
    const previewEl = document.getElementById('mo_bom_preview');
    const bodyEl    = document.getElementById('mo_bom_preview_body');

    bodyEl.innerHTML = '';
    previewEl.classList.add('d-none');
    moCurrentBomItems  = [];
    moCurrentOutputQty = 1;

    if (!bomId || !prodId) return;

    api.get(`/manufacturing/boms/${bomId}`).then(function(response) {
        const bom   = response.data.data.bom_details || {};
        const items = bom.items || [];
        if (!items.length) return;

        moCurrentBomItems  = items;
        moCurrentOutputQty = parseFloat(bom.output_qty) || 1;

        const plannedQty = parseFloat(document.getElementById('mo_planned_qty').value) || 0;
        renderComponentRows(moCurrentBomItems, plannedQty);
    }).catch(function() {});
};

const openMoFormDrawer = function(id = 0) {
    refreshMoForm(id);
    new bootstrap.Offcanvas(document.getElementById('addEditMo')).show();
};

document.getElementById('saveMoBtn').addEventListener('click', async function() {

    const formEl = document.getElementById('addEditMoForm');
    const id     = formEl.querySelector('#mo_id').value || '';

    cleanFormInputFeedback(formEl);

    const payload = formDataToObject(new FormData(formEl));
    const url     = id ? `/manufacturing/orders/${id}` : '/manufacturing/orders';

    try {
        const response = await api.post(url, payload);
        const { message } = response.data;
        notyf.success(message);

        const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('addEditMo'));
        drawer.hide();

        if (typeof moDt !== 'undefined') {
            moDt.ajax.reload();
        }

    } catch(err) {
        handleApiError(err, formEl);
    }
});

jQuery(document).ready(function() {
    initDatePicker('#addEditMo input[name="planned_date"]');
    document.getElementById('mo_planned_qty').addEventListener('input', recalcRequiredQtys);
});
</script>
@endpush
