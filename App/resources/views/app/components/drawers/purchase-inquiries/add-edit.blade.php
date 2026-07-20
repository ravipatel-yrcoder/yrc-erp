<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditPurchaseInquiry" aria-labelledby="addEditPurchaseInquiryDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 55%;">

    <div class="offcanvas-header">
        <h5 id="addEditPurchaseInquiryDrawerTitle" class="offcanvas-title">Add purchase inquiry</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditPurchaseInquiryForm">

            <input type="hidden" id="pi_id" value="" />
            <input type="hidden" name="inquiry_number_suggested" id="piNumberSuggested" value="" />
            <div class="form-glob-feedback"></div>

            <div class="mb-4">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label">Title</label>
                        <input type="text" class="form-control" name="title" placeholder="e.g. Q3 Raw Material Inquiry" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Required By Date</label>
                        <input type="text" class="form-control" name="required_by_date" placeholder="Select date" />
                    </div>
                    <div class="col-md-3">
                        <label class="form-label required">Inquiry Number</label>
                        <input type="text" class="form-control" name="inquiry_number" placeholder="Auto-generated" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Notes <span class="text-muted small">(for vendors)</span></label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Will appear on the RFQ document"></textarea>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Internal Notes</label>
                        <textarea class="form-control" name="internal_notes" rows="2" placeholder="Not shared with vendors"></textarea>
                    </div>
                </div>
            </div>

            <div class="items-section-feedback form-section-feedback"></div>
            <div class="mb-4">
                <h6 class="text-uppercase text-muted mb-3">Items Required</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="pi_line_items">
                        <thead class="table-light">
                            <tr>
                                <th class="p-2" style="width: 42%">Item &amp; Description</th>
                                <th class="p-2 text-end" style="width: 15%">Qty Required</th>
                                <th class="p-2" style="width: 38%">Notes</th>
                                <th class="p-2" style="width: 40px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add_pi_item">+ Add Item</button>
            </div>

        </form>
    </div>

    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditPurchaseInquiry" class="btn btn-primary btn-sm min-w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>

</div>


<style>
#addEditPurchaseInquiry #pi_line_items td.pi-qty-td {
    position: relative;
}
#addEditPurchaseInquiry #pi_line_items .pi-uom-label {
    position: absolute;
    right: 10px;
}
</style>

@push('scripts')
<script>
let piItemIndex                      = 0;
let purchaseInquiryAvailableProducts = [];

const openPurchaseInquiryFormDrawer = async function(id = 0) {
    await refreshPurchaseInquiryForm(id);
    new bootstrap.Offcanvas(document.getElementById('addEditPurchaseInquiry')).show();
};

const refreshPurchaseInquiryForm = async function(id = 0) {

    const drawerEl = document.getElementById('addEditPurchaseInquiry');
    const formEl   = document.getElementById('addEditPurchaseInquiryForm');

    drawerEl.querySelector('#addEditPurchaseInquiryDrawerTitle').innerHTML =
        id > 0 ? 'Edit purchase inquiry' : 'Add purchase inquiry';

    cleanFormInputFeedback(formEl);
    formEl.reset();
    document.getElementById('pi_id').value = '';
    document.getElementById('piNumberSuggested').value = '';
    formEl.querySelector("input[name='inquiry_number']").removeAttribute('readonly');

    try {
        const response = await api.get('/purchase/inquiries/form-context', { params: { id } });
        const { data } = response.data;

        purchaseInquiryAvailableProducts = data.products || [];

        document.querySelector('#addEditPurchaseInquiry #pi_line_items tbody').innerHTML = '';
        piItemIndex = 0;

        const details = data.inquiryDetails || {};
        if (id > 0 && Object.keys(details).length > 0) {
            populatePurchaseInquiryForm(details);
        } else {
            const suggestedNum    = data.suggested_inquiry_number || '';
            const inquiryNumInput = formEl.querySelector("input[name='inquiry_number']");
            if (inquiryNumInput) inquiryNumInput.value = suggestedNum;
            document.getElementById('piNumberSuggested').value = suggestedNum;
            addPIItemRow();
            datePickerSetDate("#addEditPurchaseInquiry [name='required_by_date']", '');
        }

    } catch (err) {
        handleApiError(err);
    }
};

const populatePurchaseInquiryForm = function(details) {

    const drawerEl = document.getElementById('addEditPurchaseInquiry');

    document.getElementById('pi_id').value = details.id || '';

    const inquiryNumEl = drawerEl.querySelector("input[name='inquiry_number']");
    if (inquiryNumEl) {
        inquiryNumEl.value = details.inquiry_number || '';
        inquiryNumEl.setAttribute('readonly', 'readonly');
    }

    jQuery("#addEditPurchaseInquiry [name='title']").val(details.title || '');
    jQuery("#addEditPurchaseInquiry [name='notes']").val(details.notes || '');
    jQuery("#addEditPurchaseInquiry [name='internal_notes']").val(details.internal_notes || '');
    datePickerSetDate("#addEditPurchaseInquiry [name='required_by_date']", details.required_by_date || '');

    const tbody = drawerEl.querySelector('#pi_line_items tbody');
    tbody.innerHTML = '';
    piItemIndex     = 0;

    const items = details.items || [];
    if (items.length > 0) {
        items.forEach(item => {
            tbody.insertAdjacentHTML('beforeend', getPILineItemHtml(item));
            const row = tbody.lastElementChild;
            initPIItemRowSelect2(row);
            jQuery(row).find('select.pi-items').val(item.product_id || null).trigger('change');
        });
    } else {
        addPIItemRow();
    }
};

const getPILineItemHtml = function(item = {}) {

    const { id = '', product_id = '', description = '', required_qty = '', product_uom_id = '', notes = '' } = item;
    const idx = piItemIndex++;

    const productOptions = purchaseInquiryAvailableProducts.map(p =>
        `<option value="${p.id}">${p.name}</option>`
    ).join('');

    return `<tr data-pi-idx="${idx}">
        <td class="ps-0 pe-2">
            <select class="form-select form-select-sm pi-items" name="items[${idx}][product_id]">${productOptions}</select>
            <textarea class="mt-1 form-control form-control-sm" name="items[${idx}][description]" placeholder="Description" rows="2">${description}</textarea>
            <input type="hidden" name="items[${idx}][id]" value="${id}" />
            <input type="hidden" class="pi-uom-id" name="items[${idx}][product_uom_id]" value="${product_uom_id}" />
        </td>
        <td class="px-2 pi-qty-td">
            <input type="text" class="form-control form-control-sm text-end" name="items[${idx}][required_qty]" value="${formatQty(required_qty)}" placeholder="0.00" />
        </td>
        <td class="px-2">
            <textarea class="form-control form-control-sm" name="items[${idx}][notes]" placeholder="Notes" rows="2">${notes}</textarea>
        </td>
        <td class="px-2 text-center">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger pi-remove-item"><i class="bx bx-trash text-danger cursor-pointer"></i></button>
        </td>
    </tr>`;
};

const initPIItemRowSelect2 = function(rowEl) {

    const drawerEl = document.getElementById('addEditPurchaseInquiry');

    initSelect2(rowEl.querySelector('select.pi-items'), {
        dropdownParent: drawerEl,
        placeholder:    'Select product',
        onChange: function(el) {
            const productId = parseInt(jQuery(el).val()) || 0;
            onPIProductChange(el.closest('tr'), productId);
        }
    });
};

const addPIItemRow = function() {
    const tbody = document.querySelector('#addEditPurchaseInquiry #pi_line_items tbody');
    tbody.insertAdjacentHTML('beforeend', getPILineItemHtml());
    initPIItemRowSelect2(tbody.lastElementChild);
};

const onPIProductChange = function(row, productId) {

    const qtyTdEl  = row.querySelector('td.pi-qty-td');
    const uomInput = row.querySelector('.pi-uom-id');

    qtyTdEl.querySelector('span.pi-uom-label')?.remove();

    if (!productId) {
        uomInput.value = '';
        return;
    }

    const product    = purchaseInquiryAvailableProducts.find(p => Number(p.id) === productId);
    const uoms       = product?.uoms || [];
    const defaultUom = uoms.find(u => u.is_base_uom) || uoms[0] || null;

    uomInput.value = defaultUom ? defaultUom.uom_id : '';
    if (defaultUom?.code) {
        qtyTdEl.insertAdjacentHTML('beforeend', `<span class="pi-uom-label fs-tiny mt-1 text-primary fw-semibold">UOM: ${defaultUom.code}</span>`);
    }
};


document.getElementById('add_pi_item').addEventListener('click', addPIItemRow);

document.querySelector('#addEditPurchaseInquiry #pi_line_items').addEventListener('click', function(e) {
    const btn = e.target.closest('.pi-remove-item');
    if (!btn) return;
    const tbody = document.querySelector('#addEditPurchaseInquiry #pi_line_items tbody');
    if (tbody.children.length <= 1) {
        notyf.error('At least one item is required');
        return;
    }
    btn.closest('tr')?.remove();
});

document.getElementById('saveAddEditPurchaseInquiry').addEventListener('click', async function() {

    const btn    = this;
    const formEl = document.getElementById('addEditPurchaseInquiryForm');
    const id     = document.getElementById('pi_id').value || '';

    cleanFormInputFeedback(formEl);
    setButtonLoading(btn, true);

    try {
        const payload  = formDataToObject(new FormData(formEl));
        const response = await api.post(id ? `/purchase/inquiries/${id}` : '/purchase/inquiries', payload);
        const { data, message } = response.data;

        notyf.success(message);

        if (id) {
            if (typeof refreshPurchaseInquiryDetails === 'function') refreshPurchaseInquiryDetails(id);
            if (typeof piDt !== 'undefined') piDt.ajax.reload();
            bootstrap.Offcanvas.getInstance(document.getElementById('addEditPurchaseInquiry')).hide();
        } else {
            window.location.href = `/purchase/inquiries/${data.inquiry_id}/`;
        }

    } catch (err) {
        handleApiError(err, formEl);
    } finally {
        setButtonLoading(btn, false);
    }
});

jQuery(document).ready(function() {
    initDatePicker("#addEditPurchaseInquiry input[name='required_by_date']");
});
</script>
@endpush
