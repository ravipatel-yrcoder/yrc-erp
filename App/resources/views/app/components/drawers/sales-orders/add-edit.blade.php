<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditSalesOrders" aria-labelledby="addEditSalesOrdersTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 65%;">

    <div class="offcanvas-header">
        <h5 id="addEditSalesOrdersTitle" class="offcanvas-title">Add Sales Order</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <!-- BODY -->
    <div class="offcanvas-body">
        <form id="addEditSalesOrdersForm">

            <input type="hidden" id="soFormId" value="" />
            <input type="hidden" name="status" value="draft" />
            <input type="hidden" name="so_number_suggested" id="soNumberSuggested" value="" />            
            <input type="hidden" name="lead_id" id="soLeadId" value="" />

            <div class="form-glob-feedback"></div>

            <!-- ============================================ -->
            <!-- GENERAL INFORMATION -->
            <!-- ============================================ -->
            <div class="mb-7">
                <div class="row g-4">

                    <!-- Customer Search -->
                    <div class="col-md-6">
                        <label class="form-label required">Customer</label>
                        <div class="position-relative">
                            <input type="text" class="form-control" id="soCustomerSearch" placeholder="Search customer..." autocomplete="off" />
                            <input type="hidden" name="customer_id" id="soCustomerId" value="" />
                            <ul class="list-group shadow-sm position-absolute w-100 z-3 d-none" id="soCustomerDropdown" style="top: 100%; max-height: 220px; overflow-y: auto;"></ul>
                        </div>
                        <div id="soCustomerLocked" class="d-none mt-1">
                            <small class="text-muted"><i class="bx bx-lock-alt me-1"></i>Customer linked from CRM lead — cannot be changed here.</small>
                        </div>                        
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required">Location</label>
                        <select class="form-select" name="location_id" id="soLocationId"></select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required">SO Number</label>
                        <input type="text" class="form-control" name="so_number" id="soNumber" placeholder="SO Number" />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required">Order Date</label>
                        <input type="text" class="form-control" name="order_date" id="soOrderDate" placeholder="Order Date" />
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Expected Delivery</label>
                        <input type="text" class="form-control" name="expected_delivery_date" placeholder="Expected Delivery" />
                        <a href="javascript:void(0);" id="populateExpectedDate" class="mt-1 position-absolute fs-13">Set today</a>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Payment Terms</label>
                        <select class="form-select" name="payment_term_id"></select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label">Reference</label>
                        <input type="text" class="form-control" name="reference" placeholder="Customer PO#, contract ref, etc." />
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" name="notes" rows="2" placeholder="Notes for the customer"></textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label">Internal Notes</label>
                        <textarea class="form-control" name="internal_notes" rows="2" placeholder="Internal notes (not visible to customer)"></textarea>
                    </div>

                </div>
            </div>

            <!-- ============================================ -->
            <!-- LINE ITEMS -->
            <!-- ============================================ -->
            <div class="items-section-feedback form-section-feedback"></div>
            <div class="mb-4">
                <h6 class="text-uppercase text-muted mb-3">Line Items</h6>
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="so_line_items">
                        <thead class="table-light">
                            <tr>
                                <th class="p-2" style="width: 32%">Items & Description</th>
                                <th class="p-2 text-end" style="width: 9%">Qty</th>
                                <th class="p-2 text-end" style="width: 12%">Unit Price</th>
                                <th class="p-2" style="width: 25%">Tax</th>
                                <th class="p-2 text-end" style="width: 10%">Discount</th>
                                <th class="p-2 text-end" style="width: 10%">Amount</th>
                                <th class="p-2" style="width: 40px"></th>
                            </tr>
                        </thead>
                        <tbody></tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add_so_item">+ Add Item</button>
            </div>

            <!-- ============================================ -->
            <!-- ORDER DISCOUNT + TOTALS SUMMARY -->
            <!-- ============================================ -->
            <div class="row justify-content-end">
                <div class="col-md-5">
                    <table class="table table-sm table-borderless mb-0" id="soTotalsTable">
                        <tr>
                            <th class="ps-0 text-muted fw-normal">Subtotal</th>
                            <td class="text-end" id="soTotalSubtotal">₹0.00</td>
                        </tr>
                        <tr>
                            <th class="ps-0 text-muted fw-normal">Item Discounts</th>
                            <td class="text-end" id="soTotalItemDiscounts">-₹0.00</td>
                        </tr>
                        <tr id="soOrderDiscountRow" class="d-none">
                            <th class="ps-0 text-muted fw-normal">
                                Order Discount
                                <button type="button" class="btn btn-sm btn-icon btn-text-danger p-0 ms-1" id="clearOrderDiscount" title="Remove order discount"><i class="bx bx-x"></i></button>
                            </th>
                            <td class="text-end text-danger" id="soTotalOrderDiscount">-₹0.00</td>
                        </tr>
                        <tr>
                            <th class="ps-0 text-muted fw-normal">Tax</th>
                            <td class="text-end" id="soTotalTax">₹0.00</td>
                        </tr>
                        <tr class="border-top">
                            <th class="ps-0">Total</th>
                            <td class="text-end fw-bold" id="soTotalAmount">₹0.00</td>
                        </tr>
                    </table>
                    <div class="text-end mt-1">
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="addOrderDiscountBtn">
                            <i class="bx bx-percent me-1"></i>Add Order Discount
                        </button>
                    </div>
                </div>
            </div>

        </form>
    </div>

    <!-- FOOTER -->
    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveSalesOrderBtn" class="btn btn-primary btn-sm min-w-px-100">Save as Draft</button>
            <button type="button" id="saveSalesOrderConfirmedBtn" class="btn btn-info btn-sm min-w-px-140">Save as Confirmed</button>
            <button type="button" id="saveSalesOrderDeliverBtn" class="btn btn-success btn-sm min-w-px-140">Save & Deliver</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>

</div>


<!-- ============================================ -->
<!-- DISCOUNT MODAL (shared for item + order) -->
<!-- ============================================ -->
<div class="modal fade stacked-modal" id="discountModal" tabindex="-1" aria-labelledby="discountModalLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-sm modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header py-3">
                <h6 class="modal-title mb-0" id="discountModalLabel">Apply Discount</h6>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="discountModalTarget" value="" /> <!-- 'order' or row index -->
                <div class="mb-3">
                    <label class="form-label">Type</label>
                    <div class="d-flex gap-3">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="discountType" id="discountTypePercent" value="percent">
                            <label class="form-check-label" for="discountTypePercent">Percent (%)</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="discountType" id="discountTypeFixed" value="fixed" checked>
                            <label class="form-check-label" for="discountTypeFixed">Fixed (₹)</label>
                        </div>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label" id="discountValueLabel">Amount (₹)</label>
                    <input type="number" class="form-control" id="discountValueInput" placeholder="0.00" min="0" step="0.01" />
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="applyDiscountBtn">Apply</button>
            </div>
        </div>
    </div>
</div>


<style>
#addEditSalesOrders #so_line_items td.qty-td {
    position: relative;
}
#addEditSalesOrders #so_line_items .uom-label {
    position: absolute;
    right: 10px;
    bottom: 8px;
}
#soCustomerDropdown .list-group-item {
    cursor: pointer;
    font-size: 0.875rem;
    background-color: #fff;
}
#soCustomerDropdown .list-group-item:hover {
    background-color: #f8f9fa;
}
</style>


@push('scripts')
<script>
let soItemIndex = 0;
let soAvailableProducts = [];
let soApplicableTaxes = [];
let soOrderDiscountInfo = {}; // {type, value}
let _soCurrentItemTarget = null; // row index being discounted
let _soDrawerContext = null; // { mode: 'lead_quotation', leadId: N } or null

/* ===================================================
   OPEN DRAWER
=================================================== */
const openSalesOrderFormDrawer = async function(id = 0, context = null) {

    try {
        
        _soDrawerContext = context || null;

        const response = await refreshSalesOrderForm(id);
        
        if( !response ) return;

        const drawerEl = document.getElementById('addEditSalesOrders');
        new bootstrap.Offcanvas(drawerEl).show();

    } catch(err) {}

    
}


/* ===================================================
   LOAD FORM CONTEXT
=================================================== */
const refreshSalesOrderForm = async function(id = 0) {

    const drawerEl = document.getElementById('addEditSalesOrders');
    const formEl = document.getElementById('addEditSalesOrdersForm');
    const isQuotationMode = _soDrawerContext?.mode === 'lead_quotation';

    // Title & footer button labels
    let title = btn_label = '';
    if (isQuotationMode) {
        title = 'Create Quotation';
        btn_label = 'Save as Quotation';
    } else {
        title = id > 0 ? 'Edit Sales Order' : 'Add Sales Order';
        btn_label = id > 0 ? 'Save' : 'Save as Draft';
    }

    
    drawerEl.querySelector('#addEditSalesOrdersTitle').innerHTML = title;
    drawerEl.querySelector('#saveSalesOrderBtn').innerHTML = btn_label;
    drawerEl.querySelector('#saveSalesOrderConfirmedBtn').style.display = (id > 0 || isQuotationMode) ? 'none' : '';
    drawerEl.querySelector('#saveSalesOrderDeliverBtn').style.display = (id > 0 || isQuotationMode) ? 'none' : '';

    cleanFormInputFeedback(formEl);

    // Reset customer lock
    formEl.querySelector('#soCustomerSearch').readOnly = false;
    drawerEl.querySelector('#soCustomerLocked').classList.add('d-none');

    try {

        formEl.reset();
        formEl.querySelector('#soFormId').value = '';
        formEl.querySelector('#soCustomerId').value = '';
        formEl.querySelector('#soLeadId').value = '';
        formEl.querySelector('#soCustomerSearch').value = '';
        
        soOrderDiscountInfo = {};
        renderOrderDiscountRow();

        // Build params: pass lead_id when in quotation mode
        const params = { id };
        if (isQuotationMode && _soDrawerContext.leadId) {
            params.lead_id = _soDrawerContext.leadId;
        }

        const response = await api.get('/sales/orders/form-context', { params });
        const { data } = response.data;

        const soDetails = data.so_details || {};
        const leadPrefill = data.lead_prefill || {};
        const locations = data.locations || [];
        const paymentTerms = data.payment_terms || [];
        const suggestedSoNumber = data.suggested_so_number ?? '';
        soAvailableProducts = data.products || [];
        soApplicableTaxes = data.taxes || [];

        // Location select2
        initSelect2('#addEditSalesOrders select[name="location_id"]', {
            dropdownParent: drawerEl,
            placeholder: 'Choose location',
            data: buildSelect2Options(locations),
        });

        // Payment terms select2
        initSelect2('#addEditSalesOrders select[name="payment_term_id"]', {
            dropdownParent: drawerEl,
            placeholder: 'Choose terms',
            data: buildSelect2Options(paymentTerms),
        });

        // SO Number — pre-fill suggested
        const soNumberInput = formEl.querySelector('#soNumber');
        const soSuggestedInput = formEl.querySelector('#soNumberSuggested');

        const tbodyEl = formEl.querySelector('#so_line_items tbody');
        tbodyEl.innerHTML = '';
        soItemIndex = 0;

        if (!(id > 0)) {

            soNumberInput.value = suggestedSoNumber;
            soSuggestedInput.value = suggestedSoNumber;

            // default one empty row
            const itemHtml = getSOLineItemHtml();
            tbodyEl.insertAdjacentHTML('beforeend', itemHtml);
            initSoRowSelect2(tbodyEl.lastElementChild);

            // Set current date as order date
            datePickerSetDate('#addEditSalesOrders [name="order_date"]', new Date());
        }

        // Prefill from lead (only in quotation mode, new SO)
        if (isQuotationMode && leadPrefill.lead_id && !(id > 0)) {
            
            formEl.querySelector('#soLeadId').value = leadPrefill.lead_id;

            if (leadPrefill.customer_id) {
                // Lead is linked to a customer — prefill and lock
                formEl.querySelector('#soCustomerId').value = leadPrefill.customer_id;
                formEl.querySelector('#soCustomerSearch').value = leadPrefill.customer_name || '';
                formEl.querySelector('#soCustomerSearch').readOnly = true;
                drawerEl.querySelector('#soCustomerLocked').classList.remove('d-none');
            }
            // If no customer_id, leave search open so user can choose/create one
        }

        populateSalesOrderForm(soDetails, suggestedSoNumber);

        recalcSOTotals();

        return true;

    } catch (err) {
        handleApiError(err);        
    }
}


/* ===================================================
   POPULATE FORM (edit mode)
=================================================== */
const populateSalesOrderForm = function(soDetails, suggestedSoNumber = '') {

    if (!soDetails || !soDetails.id) return;

    const drawerEl = document.getElementById('addEditSalesOrders');
    const formEl = document.getElementById('addEditSalesOrdersForm');

    const {
        id,
        status,
        customer_id,
        customer_name,
        location_id,
        so_number,
        reference,
        order_date,
        expected_delivery_date,
        payment_term_id,
        notes,
        internal_notes,
        discount_info,
        line_items = [],
    } = soDetails;

    formEl.querySelector('#soFormId').value    = id;
    formEl.querySelector('#soCustomerId').value = customer_id || '';

    // Show customer name in search input (read-only display)
    formEl.querySelector('#soCustomerSearch').value = customer_name || '';

    jQuery('#addEditSalesOrders [name="location_id"]').val(location_id).trigger('change');
    jQuery('#addEditSalesOrders [name="payment_term_id"]').val(payment_term_id).trigger('change');

    formEl.querySelector('#soNumber').value = so_number || '';
    formEl.querySelector('#soNumberSuggested').value = suggestedSoNumber;

    jQuery('#addEditSalesOrders [name="reference"]').val(reference || '');
    jQuery('#addEditSalesOrders [name="notes"]').val(notes || '');
    jQuery('#addEditSalesOrders [name="internal_notes"]').val(internal_notes || '');

    datePickerSetDate('#addEditSalesOrders [name="order_date"]', order_date || '');
    datePickerSetDate('#addEditSalesOrders [name="expected_delivery_date"]', expected_delivery_date || '');

    // Order-level discount
    if (discount_info && discount_info.value > 0) {
        soOrderDiscountInfo = discount_info;
    }
    renderOrderDiscountRow();

    // Line items
    const tbodyEl = drawerEl.querySelector('#so_line_items tbody');
    tbodyEl.innerHTML = '';
    soItemIndex = 0;

    line_items.forEach(item => {

        const itemHtml = getSOLineItemHtml(item);
        tbodyEl.insertAdjacentHTML('beforeend', itemHtml);
        const newRow = tbodyEl.lastElementChild;
        
        initSoRowSelect2(newRow);

        const prodId  = item.product_id || null;
        const taxInfo = item.tax_info   || [];
        const taxIds  = taxInfo.map(t => Number(t.id));

        jQuery(newRow).find('select.so-items').val(prodId).trigger('change');
        jQuery(newRow).find('select.so-taxes').val(taxIds).trigger('change');
    });

    recalcSOTotals();
}


/* ===================================================
   LINE ITEM HTML
=================================================== */
const getSOLineItemHtml = function(savedItem = {}) {

    const {
        id = '',
        description = '',
        ordered_qty = 0,
        unit_price = '',
        line_total = '0.00',
        discount_info = null,
    } = savedItem;

    const discountInfo = (typeof discount_info === 'object' && discount_info) ? discount_info : {};
    const discountLabel = discountInfo.value > 0
        ? (discountInfo.type === 'percent' ? `${discountInfo.value}%` : `₹${parseFloat(discountInfo.value).toFixed(2)}`)
        : 'No Discount';

    const discountInfoJson = JSON.stringify(discountInfo);

    const qty = formatQty(ordered_qty);
    const price = parseFloat(unit_price) || 0;
    const total = formatCurrency(line_total);

    const productOptions = soAvailableProducts.map(p =>
        `<option value="${p.id}" data-price="${p.sale_price}">${p.name}</option>`
    ).join('');

    const taxOptions = soApplicableTaxes.map(t =>
        `<option value="${t.id}" data-rate="${t.rate}" data-type="${t.tax_type}">${t.name}</option>`
    ).join('');

    const idx = soItemIndex;

    const html = `<tr data-index="${idx}" data-discount-info='${discountInfoJson}'>
        <td class="ps-0 pe-2">
            <select class="form-select so-items select2-field" name="so_items[${idx}][product_id]">
                ${productOptions}
            </select>
            <textarea class="mt-1 form-control" name="so_items[${idx}][description]" rows="1">${description || ''}</textarea>
            <input type="hidden" name="so_items[${idx}][id]" value="${id}" />
        </td>
        <td class="px-2 qty-td">
            <input type="text" class="px-1 form-control text-end so-item-qty" name="so_items[${idx}][qty]" placeholder="1" value="${qty}">
            <input type="hidden" class="uom-id" name="so_items[${idx}][uom_id]" value="" />
        </td>
        <td class="px-2">
            <input type="text" class="px-1 form-control text-end so-item-price" placeholder="0.00" value="${price > 0 ? formatPrice(price) : ''}">
            <input type="hidden" class="unit-price-hidden" name="so_items[${idx}][unit_price]" value="${price}">
        </td>
        <td class="px-2">
            <select class="form-select so-taxes select2-field" name="so_items[${idx}][tax][]" multiple>
                ${taxOptions}
            </select>
        </td>
        <td class="px-2 text-center">
            <button type="button" class="btn btn-sm btn-text-secondary so-item-discount-btn text-nowrap" title="Apply discount">
                <span class="discount-label">${discountLabel}</span>
            </button>
            <input type="hidden" class="discount-info-hidden" name="so_items[${idx}][discount_info]" value='${discountInfoJson}'>
        </td>
        <td class="px-2 text-end fw-semibold line-total">${total}</td>
        <td class="px-2 text-center">
            <button type="button" class="btn btn-sm btn-icon btn-text-danger so-remove-item"><i class="bx bx-trash text-danger cursor-pointer"></i></button>
        </td>
    </tr>`;

    soItemIndex++;
    return html;
}


/* ===================================================
   ROW SELECT2 INIT
=================================================== */
const initSoRowSelect2 = function(rowEl) {

    const drawerEl = rowEl.closest('#addEditSalesOrders');
    if (!drawerEl) return;

    const itemSelectEl = rowEl.querySelector('select.so-items');
    const taxSelectEl  = rowEl.querySelector('select.so-taxes');

    if (itemSelectEl) {
        initSelect2(itemSelectEl, {
            dropdownParent: drawerEl,
            placeholder: 'Choose item',
            onChange: function(_this) {
                const row = _this.closest('tr');
                const prodId = _this.value || '';

                // Remove old UOM label
                row.querySelector('td.qty-td .uom-label')?.remove();

                if (prodId) {

                    const productsMap = new Map(soAvailableProducts.map(p => [Number(p.id), p]));

                    const prod = productsMap.get(Number(prodId));

                    const uomsObj = prod.uoms || {};
                    const baseUom = Object.values(uomsObj).find(u => Number(u.is_base_uom) === 1);

                    if (baseUom) {
                        row.querySelector('.uom-id').value = baseUom.uom_id || '';
                        if (baseUom.code) {
                            row.querySelector('td.qty-td').insertAdjacentHTML('beforeend',
                                `<span class="uom-label fs-tiny mt-1 text-primary fw-semibold">UOM: ${baseUom.code}</span>`
                            );
                        }
                    }

                    // Auto-fill sale price
                    const salePrice = parseFloat(prod?.sale_price) || 0;
                    if (salePrice > 0) {
                        row.querySelector('.so-item-price').value = formatPrice(salePrice);
                        row.querySelector('.unit-price-hidden').value = salePrice;
                    }


                    const taxSelect = row.querySelector('.so-taxes');
                    const taxValues = Object.values(prod?.taxes || {});
                    const taxIds = taxValues.length ? taxValues.map(t => Number(t.tax_id)) : null;
                    jQuery(taxSelect).val(taxIds).trigger('change');

                } else {

                    row.querySelector('.uom-id').value = '';
                    row.querySelector('.so-item-qty').value = '';
                    row.querySelector('.so-item-price').value = '';
                    jQuery(row.querySelector('.so-taxes')).val(null).trigger('change');
                }

                calcSOLineAmount(row);
            }
        });
    }

    if (taxSelectEl) {
        initSelect2(taxSelectEl, {
            dropdownParent: drawerEl,
            placeholder: 'Choose taxes',
            multiple: true,
            onChange: function(_this) {
                calcSOLineAmount(_this.closest('tr'));
            }
        });
    }
}


/* ===================================================
   LINE AMOUNT CALCULATION
=================================================== */
const calcSOLineAmount = function(rowEl) {

    const qtyEl = rowEl.querySelector('.so-item-qty');
    const priceEl = rowEl.querySelector('.so-item-price');
    const taxSelectEl = rowEl.querySelector('.so-taxes');
    const lineTotalEl = rowEl.querySelector('.line-total');

    const qty = parseFloat(qtyEl.value) || 0;
    const unitPrice = parseFloat(unformatNumber(priceEl.value)) || 0;
    const subTotal = qty * unitPrice;

    // Item discount
    const discountInfoStr = rowEl.querySelector('.discount-info-hidden').value || '{}';
    let discountInfo = {};
    try { discountInfo = JSON.parse(discountInfoStr); } catch(e) {}

    let discountAmt = 0;
    if (discountInfo.value > 0) {
        if (discountInfo.type === 'percent') {
            discountAmt = subTotal * (parseFloat(discountInfo.value) / 100);
        } else {
            discountAmt = Math.min(parseFloat(discountInfo.value), subTotal);
        }
    }

    const taxableAmount = subTotal - discountAmt;

    let taxAmount = 0;
    Array.from(taxSelectEl.selectedOptions).forEach(opt => {
        if (opt.dataset.type === 'percentage') {
            taxAmount += taxableAmount * ((parseFloat(opt.dataset.rate) || 0) / 100);
        } else {
            taxAmount += parseFloat(opt.dataset.rate) || 0;
        }
    });

    const lineTotal = taxableAmount + taxAmount;
    lineTotalEl.innerHTML = formatCurrency(lineTotal);

    recalcSOTotals();
}


/* ===================================================
   TOTALS RECALC
=================================================== */
const recalcSOTotals = function() {

    let soSubtotal = 0;
    let soItemDiscounts = 0;
    let soTaxTotal = 0;

    document.querySelectorAll('#so_line_items tbody tr').forEach(rowEl => {

        const qty = parseFloat(rowEl.querySelector('.so-item-qty')?.value) || 0;
        const unitPrice = parseFloat(unformatNumber(rowEl.querySelector('.so-item-price')?.value)) || 0;
        const subTotal = qty * unitPrice;

        const discountInfoStr = rowEl.querySelector('.discount-info-hidden')?.value || '{}';
        let discountInfo = {};
        try { discountInfo = JSON.parse(discountInfoStr); } catch(e) {}

        let discountAmt = 0;
        if (discountInfo.value > 0) {
            discountAmt = discountInfo.type === 'percent' ? subTotal * (parseFloat(discountInfo.value) / 100) : Math.min(parseFloat(discountInfo.value), subTotal);
        }

        const taxableAmount = subTotal - discountAmt;
        let taxAmount = 0;
        const taxSelectEl = rowEl.querySelector('.so-taxes');
        if (taxSelectEl) {
            Array.from(taxSelectEl.selectedOptions).forEach(opt => {
                if (opt.dataset.type === 'percentage') {
                    taxAmount += taxableAmount * ((parseFloat(opt.dataset.rate) || 0) / 100);
                } else {
                    taxAmount += parseFloat(opt.dataset.rate) || 0;
                }
            });
        }

        soSubtotal += subTotal;
        soItemDiscounts += discountAmt;
        soTaxTotal += taxAmount;
    });

    // Order discount
    let orderDiscountAmt = 0;
    if (soOrderDiscountInfo.value > 0) {
        orderDiscountAmt = soOrderDiscountInfo.type === 'percent' ? soSubtotal * (parseFloat(soOrderDiscountInfo.value) / 100) : parseFloat(soOrderDiscountInfo.value);
    }

    const soTotal = soSubtotal - soItemDiscounts - orderDiscountAmt + soTaxTotal;

    document.getElementById('soTotalSubtotal').innerHTML = formatCurrency(soSubtotal);
    document.getElementById('soTotalItemDiscounts').innerHTML = `-${formatCurrency(soItemDiscounts)}`;
    document.getElementById('soTotalTax').innerHTML = formatCurrency(soTaxTotal);
    document.getElementById('soTotalAmount').innerHTML = formatCurrency(soTotal);
    document.getElementById('soTotalOrderDiscount').innerHTML = `-${formatCurrency(orderDiscountAmt)}`;
}


/* ===================================================
   ORDER DISCOUNT ROW RENDER
=================================================== */
const renderOrderDiscountRow = function() {

    const row = document.getElementById('soOrderDiscountRow');
    if (!row) return;

    if (soOrderDiscountInfo && soOrderDiscountInfo.value > 0) {
        row.classList.remove('d-none');
    } else {
        row.classList.add('d-none');
    }

    recalcSOTotals();
}


/* ===================================================
   CUSTOMER SEARCH
=================================================== */
let _soCustomerSearchTimer = null;

const soCustomerSearchInput = document.getElementById('soCustomerSearch');
const soCustomerDropdown    = document.getElementById('soCustomerDropdown');

soCustomerSearchInput.addEventListener('input', function() {

    if (this.readOnly) return;

    clearTimeout(_soCustomerSearchTimer);
    const q = this.value.trim();

    if (q.length < 2) {
        soCustomerDropdown.classList.add('d-none');
        soCustomerDropdown.innerHTML = '';
        document.getElementById('soCustomerId').value = '';
        return;
    }

    _soCustomerSearchTimer = setTimeout(async () => {
        try {
            const response = await api.get('/sales/orders/customers/search', { params: { q } });
            const { data } = response.data;

            soCustomerDropdown.innerHTML = '';

            if (!data || data.length === 0) {
                soCustomerDropdown.innerHTML = `<li class="list-group-item text-muted">No customers found</li>`;
                soCustomerDropdown.classList.remove('d-none');
                return;
            }

            data.forEach(customer => {
                const li = document.createElement('li');
                li.className = 'list-group-item';
                li.innerHTML = `<strong>${customer.display_name}</strong>
                    ${customer.email ? `<small class="text-muted ms-1">${customer.email}</small>` : ''}`;
                li.dataset.id   = customer.id;
                li.dataset.name = customer.display_name;
                li.addEventListener('click', function() {
                    document.getElementById('soCustomerId').value   = this.dataset.id;
                    soCustomerSearchInput.value                      = this.dataset.name;
                    soCustomerDropdown.classList.add('d-none');
                    soCustomerDropdown.innerHTML = '';
                });
                soCustomerDropdown.appendChild(li);
            });

            soCustomerDropdown.classList.remove('d-none');

        } catch (err) {
            // silently ignore search errors
        }
    }, 300);
});

// Close dropdown when clicking outside
document.addEventListener('click', function(e) {
    if (!soCustomerSearchInput.contains(e.target) && !soCustomerDropdown.contains(e.target)) {
        soCustomerDropdown.classList.add('d-none');
    }
});


/* ===================================================
   ADD/REMOVE LINE ITEM
=================================================== */
document.getElementById('add_so_item').addEventListener('click', function() {
    const tbodyEl = document.querySelector('#addEditSalesOrdersForm #so_line_items tbody');
    tbodyEl.insertAdjacentHTML('beforeend', getSOLineItemHtml());
    initSoRowSelect2(tbodyEl.lastElementChild);
});

document.querySelector('#addEditSalesOrdersForm #so_line_items').addEventListener('click', function(e) {
    const removeBtn = e.target.closest('.so-remove-item');
    if (removeBtn) {
        removeBtn.closest('tr')?.remove();
        recalcSOTotals();
    }
});


/* ===================================================
   QTY / PRICE CHANGE
=================================================== */
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('so-item-qty')) {
        calcSOLineAmount(e.target.closest('tr'));
    }
});

document.addEventListener('change', function(e) {
    if (!e.target.classList.contains('so-item-price')) return;
    const raw = unformatNumber(e.target.value);
    e.target.value = formatPrice(raw);
    e.target.closest('tr').querySelector('.unit-price-hidden').value = raw;
    calcSOLineAmount(e.target.closest('tr'));
});


/* ===================================================
   ITEM DISCOUNT BUTTON
=================================================== */
document.querySelector('#so_line_items').addEventListener('click', function(e) {
    const btn = e.target.closest('.so-item-discount-btn');
    if (!btn) return;

    const row = btn.closest('tr');
    _soCurrentItemTarget = row.dataset.index;

    // Pre-fill modal from current discount
    const discountInfoStr = row.querySelector('.discount-info-hidden').value || '{}';
    let info = {};
    try { info = JSON.parse(discountInfoStr); } catch(e) {}

    document.getElementById('discountModalTarget').value = 'item_' + _soCurrentItemTarget;
    document.getElementById('discountValueInput').value  = info.value || '';
    document.querySelector(`input[name="discountType"][value="${info.type || 'fixed'}"]`).checked = true;
    document.getElementById('discountModalLabel').textContent = 'Apply Item Discount';
    updateDiscountModalLabel();

    new bootstrap.Modal(document.getElementById('discountModal')).show();
});


/* ===================================================
   ORDER DISCOUNT BUTTON
=================================================== */
document.getElementById('addOrderDiscountBtn').addEventListener('click', function() {

    document.getElementById('discountModalTarget').value = 'order';
    document.getElementById('discountValueInput').value  = soOrderDiscountInfo.value || '';
    document.querySelector(`input[name="discountType"][value="${soOrderDiscountInfo.type || 'fixed'}"]`).checked = true;
    document.getElementById('discountModalLabel').textContent = 'Apply Order Discount';
    updateDiscountModalLabel();

    new bootstrap.Modal(document.getElementById('discountModal')).show();
});


/* ===================================================
   CLEAR ORDER DISCOUNT
=================================================== */
document.getElementById('clearOrderDiscount').addEventListener('click', function() {
    soOrderDiscountInfo = {};
    renderOrderDiscountRow();
    // Update hidden field
    const soFormEl = document.getElementById('addEditSalesOrdersForm');
    const existing = soFormEl.querySelector('input[name="order_discount_info"]');
    if (existing) existing.remove();
});


/* ===================================================
   DISCOUNT TYPE LABEL UPDATE
=================================================== */
const updateDiscountModalLabel = function() {
    const type  = document.querySelector('input[name="discountType"]:checked')?.value || 'fixed';
    const label = document.getElementById('discountValueLabel');
    if (label) label.textContent = type === 'percent' ? 'Percent (%)' : 'Amount (₹)';
}

document.querySelectorAll('input[name="discountType"]').forEach(radio => {
    radio.addEventListener('change', updateDiscountModalLabel);
});


/* ===================================================
   APPLY DISCOUNT
=================================================== */
document.getElementById('applyDiscountBtn').addEventListener('click', function() {

    const target = document.getElementById('discountModalTarget').value;
    const type = document.querySelector('input[name="discountType"]:checked')?.value || 'fixed';
    const value = parseFloat(document.getElementById('discountValueInput').value) || 0;
    const info = value > 0 ? { type, value } : {};
    const label = value > 0 ? (type === 'percent' ? `${value}%` : `₹${value.toFixed(2)}`) : 'No Discount';

    if (target === 'order') {

        soOrderDiscountInfo = info;
        renderOrderDiscountRow();

        // Store as hidden field in form
        const soFormEl = document.getElementById('addEditSalesOrdersForm');
        let existingInput = soFormEl.querySelector('input[name="order_discount_info"]');
        if (!existingInput) {
            existingInput = document.createElement('input');
            existingInput.type = 'hidden';
            existingInput.name = 'order_discount_info';
            soFormEl.appendChild(existingInput);
        }
        existingInput.value = JSON.stringify(info);

    } else {
        // Item discount
        const rowEl = document.querySelector(`#so_line_items tbody tr[data-index="${_soCurrentItemTarget}"]`);
        if (rowEl) {
            rowEl.querySelector('.discount-info-hidden').value    = JSON.stringify(info);
            rowEl.querySelector('.discount-label').textContent    = label;
            calcSOLineAmount(rowEl);
        }
    }

    bootstrap.Modal.getInstance(document.getElementById('discountModal')).hide();
    _soCurrentItemTarget = null;
});


/* ===================================================
   SAVE BUTTONS
=================================================== */
const submitSalesOrderForm = async function(statusOverride = null, acknowledgedWarning = false) {

    const formEl = document.getElementById('addEditSalesOrdersForm');

    try {

        const id = formEl.querySelector('#soFormId').value || '';
        let apiUrl = '/sales/orders';
        if (id) apiUrl += `/${id}`;

        cleanFormInputFeedback(formEl);

        const formData = new FormData(formEl);
        const payload = formDataToObject(formData);

        if (statusOverride) {
            payload.status = statusOverride;
        }

        if (acknowledgedWarning) {
            payload.acknowledged_warning = true;
        }

        const response = await api.post(apiUrl, payload);
        const { status, code, message, data, warnings } = response.data;

        // Soft warning gate — show confirmation before proceeding
        if (status === 'warning') {
            const listItems = warnings.map(w => `<li>${w}</li>`).join('');
            const html = `<strong>Stock may be insufficient for some items:</strong><ul>${listItems}</ul><p class="fw-semibold text-muted mt-2 mb-0"><small>The order can still be confirmed and fulfilled once stock arrives.</small></p>`;
            showConfirmation(
                html,
                'warning',
                { text: 'Save as Confirmed', class: 'btn-info', callback: () => submitSalesOrderForm("confirmed", true) },
                { text: 'Cancel' },
                { width: '32em', htmlContainer: 'swal-warning' }
            );
            return;
        }

        notyf.success(message);

        if (code === 201 || code === 200) {

            const drawerMode = _soDrawerContext?.mode || "";
            const drawerContextLeadId = _soDrawerContext?.leadId || "";

            if (id) {
                
                // Update mode: refresh detail page
                document.dispatchEvent(new CustomEvent('salesOrderFormSaved', { detail: { soId: id } }));
                bootstrap.Offcanvas.getInstance(document.getElementById('addEditSalesOrders')).hide();
                formEl.reset();

            } else if (drawerMode === 'lead_quotation' && drawerContextLeadId ) {
                
                // Quotation mode from CRM lead: fire event, close drawer, stay on page
                document.dispatchEvent(new CustomEvent('leadQuotationCreated', { detail: {
                    lead_id: drawerContextLeadId,
                    so_id: data.so_id,
                    so_number: data.so_number,
                }}));

                bootstrap.Offcanvas.getInstance(document.getElementById('addEditSalesOrders')).hide();
                formEl.reset();
                _soDrawerContext = null;

            } else {
                
                // Standard create mode: redirect to detail page
                window.location.href = `/sales/orders/${data.so_id}/`;
            }
        }

    } catch (error) {
        handleApiError(error, formEl);
    }
};

document.getElementById('saveSalesOrderBtn').addEventListener('click', function() {
    submitSalesOrderForm();
});

document.getElementById('saveSalesOrderConfirmedBtn').addEventListener('click', function() {
    submitSalesOrderForm('confirmed');
});

document.getElementById('saveSalesOrderDeliverBtn').addEventListener('click', function() {
    showConfirmation(
        'This will create the order and immediately mark all items as delivered. Stock will be deducted. This cannot be undone.',
        'question',
        { text: 'Save & Deliver', class: 'btn-success', callback: () => submitSalesOrderForm('delivered') },
        { text: 'Cancel' }
    );
});

document.getElementById('populateExpectedDate').addEventListener('click', function() {
    datePickerSetDate('#addEditSalesOrders [name="expected_delivery_date"]', new Date());
});


jQuery(document).ready(function() {
    initDatePicker('#addEditSalesOrders input[name="order_date"]', { defaultDate: new Date() });
    initDatePicker('#addEditSalesOrders input[name="expected_delivery_date"]');
});
</script>
@endpush
