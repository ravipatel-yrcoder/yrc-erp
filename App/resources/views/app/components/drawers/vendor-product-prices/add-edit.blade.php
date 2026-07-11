<div class="offcanvas offcanvas-end offcanvas-stacked" tabindex="-1" id="addEditVendorPrice" aria-labelledby="addEditVendorPriceTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 38%;">

    <div class="offcanvas-header">
        <h5 id="addEditVendorPriceTitle" class="offcanvas-title">Add Vendor Price</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditVendorPriceForm">
            <input type="hidden" id="vp_id" value="" />

            <div class="mb-4">
                <label class="form-label required">Vendor</label>
                <select id="vpVendorSelect" class="form-select" name="vendor_id" placeholder="Search vendor..."></select>
            </div>

            <div class="mb-4">
                <label class="form-label required">Product</label>
                <select id="vpProductSelect" class="form-select" name="product_id" placeholder="Search product..."></select>
            </div>

            {{-- Vendor Product Name and Vendor SKU deferred --}}
            {{-- <div class="row">
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label">Vendor Product Name</label>
                        <input type="text" class="form-control" name="vendor_product_name" placeholder="Vendor's name for this product" />
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="mb-4">
                        <label class="form-label">Vendor SKU</label>
                        <input type="text" class="form-control" name="vendor_product_code" placeholder="Vendor's SKU/code" />
                    </div>
                </div>
            </div> --}}

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-4">
                        <label class="form-label required">Min Qty</label>
                        <input type="text" class="form-control text-end" name="min_qty" value="1" placeholder="1" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-4">
                        <label class="form-label required">Unit Price</label>
                        <input type="text" class="form-control text-end" name="unit_price" placeholder="0.00" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-4">
                        <label class="form-label">Discount %</label>
                        <input type="text" class="form-control text-end" name="discount_amount" placeholder="0.00" value="0" />
                        <input type="hidden" name="discount_type" value="percentage" />
                    </div>
                </div>
            </div>

            <div class="row">
                <div class="col-md-4">
                    <div class="mb-4">
                        <label class="form-label">Lead Time (Days)</label>
                        <input type="number" class="form-control" name="lead_time_days" min="1" value="" placeholder="e.g. 3" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-4">
                        <label class="form-label">Start Date</label>
                        <input type="text" class="form-control" name="start_date" placeholder="From date" />
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="mb-4">
                        <label class="form-label">End Date</label>
                        <input type="text" class="form-control" name="end_date" placeholder="To date" />
                    </div>
                </div>
            </div>

            <div class="form-check pt-2">
                <input class="form-check-input" type="checkbox" value="active" name="status" id="vp_status" checked />
                <label class="form-check-label" for="vp_status">Active</label>
            </div>

        </form>
    </div>

    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveVendorPriceBtn" class="btn btn-primary btn-sm min-w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>

@push('scripts')
<script>
let vpOnSaved             = null;
let vpLockedVendorId      = 0;
let vpLockedProductId     = 0;

const initVpVendorSelect2 = function(drawerEl, prefillId, prefillName) {

    const selectEl = document.getElementById('vpVendorSelect');

    if (prefillId) {
        jQuery(selectEl).empty().append(new Option(prefillName, prefillId, true, true)).trigger('change');
        selectEl.disabled = true;
        return;
    }

    selectEl.disabled = false;
    jQuery(selectEl).empty();
    initSelect2('#vpVendorSelect', {
        dropdownParent: jQuery(drawerEl),
        placeholder: 'Search vendor...',
        minimumInputLength: 0,
        ajax: {
            url: '/api/purchase/orders/vendors/search',
            dataType: 'json',
            delay: 300,
            data: params => ({ q: params.term || '' }),
            processResults: function(response) {
                return {
                    results: (response.data || []).map(v => ({ id: v.id, text: v.display_name }))
                };
            }
        }
    });
};

const initVpProductSelect2 = function(drawerEl, prefillId, prefillName) {

    const selectEl = document.getElementById('vpProductSelect');

    if (prefillId) {
        jQuery(selectEl).empty().append(new Option(prefillName, prefillId, true, true)).trigger('change');
        selectEl.disabled = true;
        return;
    }

    selectEl.disabled = false;
    jQuery(selectEl).empty();
    initSelect2('#vpProductSelect', {
        dropdownParent: jQuery(drawerEl),
        placeholder: 'Search product...',
        minimumInputLength: 1,
        ajax: {
            url: '/api/products/search',
            dataType: 'json',
            delay: 300,
            data: params => ({ q: params.term || '' }),
            processResults: function(response) {
                return {
                    results: (response.data || []).map(p => ({ id: p.id, text: p.name }))
                };
            }
        }
    });
};

const populateVpForm = function(rule) {

    if (!rule) return;

    const formEl = document.getElementById('addEditVendorPriceForm');

    formEl.querySelector('#vp_id').value = rule.id;
    // formEl.querySelector('[name="vendor_product_name"]').value = rule.vendor_product_name || '';
    // formEl.querySelector('[name="vendor_product_code"]').value = rule.vendor_product_code || '';
    formEl.querySelector('[name="min_qty"]').value         = parseFloat(rule.min_qty   || 1);
    formEl.querySelector('[name="unit_price"]').value      = parseFloat(rule.unit_price || 0);
    formEl.querySelector('[name="discount_amount"]').value = parseFloat(rule.discount_amount || 0);
    formEl.querySelector('[name="lead_time_days"]').value  = rule.lead_time_days || '';
    datePickerSetDate('#addEditVendorPrice [name="start_date"]', rule.start_date || null);
    datePickerSetDate('#addEditVendorPrice [name="end_date"]',   rule.end_date   || null);
    jQuery(formEl.querySelector('[name="status"]')).prop('checked', rule.status === 'active');

    // Pre-select vendor (if not locked)
    const vendorSelect = document.getElementById('vpVendorSelect');
    if (!vendorSelect.disabled && rule.vendor_id) {
        jQuery(vendorSelect).empty().append(new Option(rule.vendor_name, rule.vendor_id, true, true)).trigger('change');
    }

    // Pre-select product (if not locked)
    const productSelect = document.getElementById('vpProductSelect');
    if (!productSelect.disabled && rule.product_id) {
        jQuery(productSelect).empty().append(new Option(rule.product_name, rule.product_id, true, true)).trigger('change');
    }
};

const openVendorPriceDrawer = async function(id = 0, options = {}) {

    options = options || {};

    const drawerEl = document.getElementById('addEditVendorPrice');
    const formEl   = document.getElementById('addEditVendorPriceForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector('#vp_id').value = '';

    document.getElementById('addEditVendorPriceTitle').textContent = id > 0 ? 'Edit Vendor Price' : 'Add Vendor Price';

    vpOnSaved         = options.onSaved        || null;
    vpLockedVendorId  = options.prefillVendorId  || 0;
    vpLockedProductId = options.prefillProductId || 0;

    // Re-init date pickers
    initDatePicker('#addEditVendorPrice [name="start_date"]', { allowInput: true });
    initDatePicker('#addEditVendorPrice [name="end_date"]',   { allowInput: true });

    // Init vendor + product selects
    initVpVendorSelect2(drawerEl, vpLockedVendorId, options.prefillVendorName || '');
    initVpProductSelect2(drawerEl, vpLockedProductId, options.prefillProductName || '');

    try {
        if (id > 0) {
            const response = await api.get('/purchase/vendor-prices/form-context', { params: { id } });
            const { data } = response.data;
            populateVpForm(data.rule || null);
        }

        new bootstrap.Offcanvas(drawerEl).show();

    } catch (error) {
        handleApiError(error);
    }
};

document.getElementById('saveVendorPriceBtn').addEventListener('click', async function() {

    const btn    = this;
    const formEl = document.getElementById('addEditVendorPriceForm');
    const id     = formEl.querySelector('#vp_id').value || '';

    cleanFormInputFeedback(formEl);

    const payload = formDataToObject(new FormData(formEl));
    payload.id     = id || '';
    payload.status = formEl.querySelector('[name="status"]').checked ? 'active' : 'inactive';

    // Include locked values (disabled selects don't appear in FormData)
    if (vpLockedVendorId)  payload.vendor_id  = vpLockedVendorId;
    if (vpLockedProductId) payload.product_id = vpLockedProductId;

    setButtonLoading(btn, true);
    try {
        const response = await api.post('/purchase/vendor-prices', payload);
        const { code, message, data } = response.data;

        notyf.success(message);

        if (code === 200 || code === 201) {
            if (typeof vpOnSaved === 'function') vpOnSaved(data || {});

            if (typeof vendorPricesDt !== 'undefined') vendorPricesDt.ajax.reload();

            bootstrap.Offcanvas.getInstance(document.getElementById('addEditVendorPrice')).hide();
            formEl.reset();
        }
    } catch (error) {
        handleApiError(error, formEl);
    } finally {
        setButtonLoading(btn, false);
    }
});
</script>
@endpush
