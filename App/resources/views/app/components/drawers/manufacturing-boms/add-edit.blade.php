<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditBom" aria-labelledby="addEditBomTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 60%;">

    <div class="offcanvas-header">
        <h5 id="addEditBomTitle" class="offcanvas-title">Add BOM</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditBomForm">

            <input type="hidden" id="bom_id" value="" />
            <div class="form-glob-feedback"></div>

            <!-- Header fields -->
            <div class="mb-6">
                <div class="row g-4">

                    <div class="col-md-6">
                        <label class="form-label required">Finished Product</label>
                        <select class="form-select" name="product_id" id="bom_product_select"></select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label required">BOM Name</label>
                        <input type="text" class="form-control" name="name" placeholder="e.g. Standard, Export Variant" />
                    </div>

                    <div class="col-md-4">
                        <label class="form-label required">Output Qty</label>
                        <input type="text" class="form-control" name="output_qty" value="" placeholder="1.00" />
                    </div>

                    <div class="col-md-12">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Internal notes"></textarea>
                    </div>

                </div>
            </div>

            <!-- Components -->
            <div class="bom_items-section-feedback form-section-feedback"></div>
            <div class="mb-4">
                <h6 class="text-uppercase text-muted mb-3">Components</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="bom_items_table">
                        <thead class="table-light">
                            <tr>
                                <th class="p-2" style="width: 45%;">Component Product</th>
                                <th class="p-2 text-end" style="width: 15%;">Qty</th>
                                <th class="p-2" style="width: 15%;">UOM</th>
                                <th class="p-2" style="width: 20%;">Notes</th>
                                <th class="p-2" style="width: 40px;"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add_bom_item">+ Add Component</button>
                <div class="mt-12 d-flex flex-column gap-2">
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" name="is_default" id="bom_is_default" value="1" />
                        <label class="form-check-label" for="bom_is_default">Set as default BOM for this product</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" value="active" name="status" id="bom_status" checked />
                        <label class="form-check-label" for="bom_status">Active</label>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveBomBtn" class="btn btn-primary btn-sm min-w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>

</div>

@push('scripts')
<script>
let bomItemIndex = 0;
let bomAvailableProducts = [];

const refreshBomForm = async function(id = 0) {

    const drawerEl = document.getElementById('addEditBom');
    const formEl   = document.getElementById('addEditBomForm');

    drawerEl.querySelector('#addEditBomTitle').textContent = id > 0 ? 'Edit BOM' : 'Add BOM';

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector('#bom_id').value = '';

    try {
        const response = await api.get('/manufacturing/boms/form-context', { params: { id } });
        const { data }   = response.data;
        const bomDetails = data.bom_details || {};

        bomAvailableProducts = data.products || [];

        const productOptions = buildSelect2Options(bomAvailableProducts, { idKey: 'id', textKey: 'name' });
        initSelect2('#addEditBom select[name="product_id"]', {
            dropdownParent: drawerEl,
            placeholder: 'Choose finished product',
            data: productOptions
        });

        const tbodyEl = formEl.querySelector('#bom_items_table tbody');
        tbodyEl.innerHTML = '';
        bomItemIndex = 0;

        if (!(id > 0)) {
            // Add one blank row for new BOM
            tbodyEl.insertAdjacentHTML('beforeend', getBomItemRowHtml());
            initBomRowSelect2(tbodyEl.lastElementChild);
        }

        populateBomForm(bomDetails);

    } catch(err) {
        handleApiError(err);
    }
};


const populateBomForm = function(bomDetails) {

    if (!bomDetails || Object.keys(bomDetails).length === 0) return;

    const drawerEl = document.getElementById('addEditBom');
    const formEl   = drawerEl.querySelector('#addEditBomForm');
    const { id, product_id, name, output_qty, is_default, status, notes, items = [] } = bomDetails;

    formEl.querySelector('#bom_id').value = id || '';
    jQuery('#addEditBom [name="product_id"]').val(product_id).trigger('change');
    jQuery('#addEditBom [name="name"]').val(name || '');
    jQuery('#addEditBom [name="output_qty"]').val(formatQty(output_qty || 1));
    formEl.querySelector('#bom_is_default').checked = !!parseInt(is_default);
    formEl.querySelector('#bom_status').checked = (status || 'active') === 'active';
    jQuery('#addEditBom [name="notes"]').val(notes || '');

    if (Array.isArray(items) && items.length > 0) {
        const tbodyEl = formEl.querySelector('#bom_items_table tbody');
        tbodyEl.innerHTML = '';
        bomItemIndex = 0;

        items.forEach(item => {
            tbodyEl.insertAdjacentHTML('beforeend', getBomItemRowHtml(item));
            const row = tbodyEl.lastElementChild;
            initBomRowSelect2(row);
            jQuery(row).find('select.bom-item-product').val(item.product_id).trigger('change');
        });
    }
};


const getBomItemRowHtml = function(savedItem = {}) {

    const { product_id = '', qty = '', product_uom_id = '', uom_code = '', notes = '' } = savedItem;
    const displayQty = qty ? formatQty(qty) : '';

    const productOptions = bomAvailableProducts.map(p =>
        `<option value="${p.id}">${p.name}</option>`
    ).join('');

    const html = `<tr data-index="${bomItemIndex}">
        <td class="ps-0 pe-2">
            <select class="form-select bom-item-product select2-field" name="bom_items[${bomItemIndex}][product_id]">
                ${productOptions}
            </select>
        </td>
        <td class="px-2">
            <input type="text" class="form-control text-end" name="bom_items[${bomItemIndex}][qty]" value="${displayQty}" placeholder="1.00" />
        </td>
        <td class="px-2">
            <span class="bom-uom-label text-primary fw-semibold small"></span>
            <input type="hidden" class="bom-uom-id" name="bom_items[${bomItemIndex}][uom_id]" value="${product_uom_id || ''}" />
        </td>
        <td class="px-2">
            <input type="text" class="form-control" name="bom_items[${bomItemIndex}][notes]" value="${notes || ''}" placeholder="Optional" />
        </td>
        <td class="px-2 text-center">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger bom-remove-item">
                <i class="bx bx-trash text-danger"></i>
            </button>
        </td>
    </tr>`;

    bomItemIndex++;
    return html;
};


const initBomRowSelect2 = function(rowEl) {

    const drawerEl = rowEl.closest('#addEditBom');
    if (!drawerEl) return;

    const productSelectEl = rowEl.querySelector('select.bom-item-product');
    if (!productSelectEl) return;

    const onProductChange = function(_this) {
        const row      = _this.closest('tr');
        const prodId   = Number(_this.value) || 0;
        const uomLabel = row.querySelector('.bom-uom-label');
        const uomInput = row.querySelector('.bom-uom-id');

        uomLabel.textContent = '';
        uomInput.value       = '';

        if (!prodId) return;

        const productsMap = new Map(bomAvailableProducts.map(p => [Number(p.id), p]));
        const product     = productsMap.get(prodId);
        if (!product) return;

        const baseUom = product.uoms.find(u => Number(u.is_base_uom) === 1) || product.uoms[0];
        if (baseUom) {
            uomLabel.textContent = baseUom.code || '';
            uomInput.value       = baseUom.uom_id || '';
        }
    };

    initSelect2(productSelectEl, {
        dropdownParent: drawerEl,
        placeholder: 'Choose component',
        onChange: onProductChange
    });
};


const openBomFormDrawer = function(id = 0) {
    refreshBomForm(id);
    new bootstrap.Offcanvas(document.getElementById('addEditBom')).show();
};


document.getElementById('saveBomBtn').addEventListener('click', async function() {

    const formEl = document.getElementById('addEditBomForm');
    const id     = formEl.querySelector('#bom_id').value || '';

    cleanFormInputFeedback(formEl);

    const formData = new FormData(formEl);

    // Checkboxes: FormData omits unchecked boxes — set explicit values
    if (!formEl.querySelector('#bom_is_default').checked) {
        formData.set('is_default', '0');
    }
    formData.set('status', formEl.querySelector('#bom_status').checked ? 'active' : 'inactive');

    const payload = formDataToObject(formData);
    const url     = id ? `/manufacturing/boms/${id}` : '/manufacturing/boms';

    try {
        const response = await api.post(url, payload);
        const { message } = response.data;
        notyf.success(message);

        const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('addEditBom'));
        drawer.hide();
        formEl.reset();

        if (typeof bomsDt !== 'undefined') {
            bomsDt.ajax.reload();
        }

    } catch(err) {
        handleApiError(err, formEl);
    }
});


document.getElementById('add_bom_item').addEventListener('click', function() {
    const tbodyEl = document.querySelector('#addEditBomForm #bom_items_table tbody');
    tbodyEl.insertAdjacentHTML('beforeend', getBomItemRowHtml());
    initBomRowSelect2(tbodyEl.lastElementChild);
});


document.querySelector('#addEditBomForm #bom_items_table').addEventListener('click', function(e) {
    const btn = e.target.closest('.bom-remove-item');
    if (!btn) return;
    const row = btn.closest('tr');
    if (row) row.remove();
});
</script>
@endpush
