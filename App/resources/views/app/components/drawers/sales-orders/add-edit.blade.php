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
            <input type="hidden" name="origin_type" id="soOriginType" value="order" />
            <input type="hidden" name="so_number_suggested" id="soNumberSuggested" value="" />
            <input type="hidden" name="lead_id" id="soLeadId" value="" />
            {{-- adjustment feature suspended -- hidden inputs kept so form submit doesn't break if re-enabled later --}}
            {{-- <input type="hidden" name="adjustment_label"  id="soAdjustmentLabelHidden"  value="" /> --}}
            {{-- <input type="hidden" name="adjustment_amount" id="soAdjustmentAmountHidden" value="0" /> --}}

            <div class="form-glob-feedback"></div>

            <!-- DELIVER NOW TOGGLE (new SO only) -->
            <div class="d-none mb-5 pb-4 border-dashed border-start-0 border-end-0 border-top-0 border-1 border-dark-subtle" id="soDeliverNowToggleWrap">
                <div class="d-flex align-items-center gap-3">
                    <div class="form-check form-switch mb-0">
                        <input class="form-check-input" type="checkbox" id="soDeliverNowToggle" role="switch" />
                        <label class="form-check-label fw-medium" for="soDeliverNowToggle">Deliver Now</label>
                    </div>
                    <small class="text-muted">For walk-in / counter sales — stock deducted and order marked delivered immediately.</small>
                </div>
            </div>

            <!-- ============================================ -->
            <!-- GENERAL INFORMATION -->
            <!-- ============================================ -->
            <div class="mb-7">
                <div class="row g-4">

                    <!-- Customer Select -->
                    <div class="col-md-6">
                        <label class="form-label required">Customer</label>
                        <select class="form-select" name="customer_id" id="soCustomerId"></select>
                        <div id="soCustomerLocked" class="d-none mt-1">
                            <small class="text-muted"><i class="bx bx-lock-alt me-1"></i>Customer linked from CRM lead — cannot be changed here.</small>
                        </div>
                        @if(tenantContext()->canDo('customers', 'write'))
                        <div id="soCreateCustomerLink" class="mt-1">
                            <a href="javascript:void(0);" class="fs-13" onclick="soOpenCreateCustomer()"><i class="bx bx-plus me-1"></i>Create new customer</a>
                        </div>
                        @endif
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required">Location</label>
                        <select class="form-select" name="location_id" id="soLocationId"></select>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label required">SO Number</label>
                        <input type="text" class="form-control" name="so_number" id="soNumber" placeholder="SO Number" />
                    </div>

                    <div class="col-md-3 dynamic-col" id="soQuoteDateField">
                        <label class="form-label required">Quote Date</label>
                        <input type="text" class="form-control" name="quote_date" placeholder="Quote Date" />
                    </div>

                    <div class="col-md-3 dynamic-col" id="soOrderDateField">
                        <label class="form-label required">Order Date</label>
                        <input type="text" class="form-control" name="order_date" id="soOrderDate" placeholder="Order Date" />
                    </div>

                    <div class="col-md-3 dynamic-col position-relative">
                        <label class="form-label">Expected Delivery</label>
                        <input type="text" class="form-control" name="expected_delivery_date" placeholder="Expected Delivery" />
                        <a href="javascript:void(0);" id="populateExpectedDate" class="mt-1 position-absolute fs-13">Set today</a>
                    </div>

                    <div class="col-md-3 dynamic-col d-none" id="soValidUntilField">
                        <label class="form-label">Valid Until</label>
                        <input type="text" class="form-control" name="valid_until" placeholder="Valid Until" />
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

                    <div class="col-md-3">
                        <label class="form-label">Delivery Type</label>
                        <select class="form-select" name="delivery_type" id="soDeliveryType">
                            <option value="pickup">Pickup</option>
                            <option value="ship">Shipment</option>
                        </select>
                    </div>

                    <div class="col-md-6 d-none" id="soShippingAddressField">
                        <label class="form-label required">Shipping Address</label>
                        <select class="form-select" name="delivery_address_id" id="soDeliveryAddressId">
                            <option value="">Select address...</option>
                        </select>
                        <div class="mt-1" id="soShipAddrLinks">
                            <a href="javascript:void(0);" id="soAddNewAddressBtn" class="fs-13">Add new address</a>
                            <a href="javascript:void(0);" id="soEditAddressBtn" class="fs-13 d-none ms-2">Edit address</a>
                        </div>
                        <div id="soShipNoCustomerMsg" class="text-muted small mt-1 d-none">Select customer to choose shipping address</div>
                        <input type="hidden" name="shipping_address_json" id="soShippingAddressJson" />
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
                                <div class="d-flex align-items-center gap-2">
                                    <span>Order Discount</span>
                                    <a href="javascript:void(0);" id="clearOrderDiscount" title="Remove order discount"><i class="bx bx-trash text-danger" style="margin-top: 1px;"></i></a>
                                    <!--
                                    <button type="button" class="btn btn-sm btn-icon btn-text-danger p-0 ms-1" id="clearOrderDiscount" title="Remove order discount"><i class="bx bx-x"></i></button>
                                    -->
                                </div>
                            </th>
                            <td class="text-end text-danger" id="soTotalOrderDiscount">-₹0.00</td>
                        </tr>
                        <tr>
                            <th class="ps-0 text-muted fw-normal">Tax</th>
                            <td class="text-end" id="soTotalTax">₹0.00</td>
                        </tr>
                        {{-- Adjustment row suspended — feature under review --}}
                        {{-- <tr id="soAdjustmentRow">
                            ...
                        </tr> --}}
                        <tr id="soRoundOffRow" class="d-none">
                            <th class="ps-0 text-muted fw-normal">Round Off</th>
                            <td class="text-end" id="soTotalRoundOff">₹0.00</td>
                        </tr>
                        <tr class="border-top">
                            <th class="ps-0">Total</th>
                            <td class="text-end fw-bold" id="soTotalAmount">₹0.00</td>
                        </tr>
                    </table>
                    <div class="d-flex justify-content-end gap-2 mt-1">
                        <button type="button" class="d-flex justify-content-center btn btn-sm btn-outline-secondary d-none" id="toggleRoundOffBtn">
                            <i class="bx bx-rotate-right me-1"></i><span id="toggleRoundOffLabel">Apply Round Off</span>
                        </button>
                        <button type="button" class="d-flex justify-content-center btn btn-sm btn-outline-secondary" id="addOrderDiscountBtn">
                            <i class="bx bx-purchase-tag me-1"></i>Add Order Discount
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
            <button type="button" id="saveSalesOrderDeliverBtn" class="btn btn-success btn-sm min-w-px-140 d-none">Deliver Now</button>
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
                    <input type="number" class="form-control" id="discountValueInput" placeholder="0.00" min="0" step="1" />
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
/*
#addEditSalesOrders #so_line_items td.qty-td {
    position: relative;
}
*/

#addEditSalesOrders #so_line_items .uom-label {
    position: absolute;
    right: 10px;    
}
</style>

@includeOnce('app.components.drawers.inventory.product-serial-lot-picker')
@includeOnce('app.components.drawers.customers.address');

@push('scripts')
<script>
let soItemIndex = 0;
let soAvailableProducts = [];
let soApplicableTaxes = [];
let soOrderDiscountInfo = {}; // {type, value}
let soRoundOffEnabled = false; // manual toggle state
let _soCurrentItemTarget = null; // row index being discounted
let _soDrawerContext = null; // { mode: 'lead_quotation', leadId: N } or null

/* ===================================================
   ADJUSTMENT INLINE EDIT — suspended, feature under review
=================================================== */
/*
let soAdjustmentInfo = { label: '', sign: 1, amount: 0 };
const soAdjEnterEditMode = function() { ... };
const soAdjExitEditMode = function() { ... };
const syncAdjustmentState = function() { ... };
const resetAdjustmentState = function() { ... };
document.getElementById('soAdjustmentEditBtn').addEventListener('click', soAdjEnterEditMode);
document.getElementById('soAdjustmentDoneBtn').addEventListener('click', soAdjExitEditMode);
document.getElementById('soAdjustmentAmtInput').addEventListener('input',  syncAdjustmentState);
document.getElementById('soAdjustmentLabelInput').addEventListener('input', syncAdjustmentState);
*/
const resetAdjustmentState = function() {};  // no-op placeholder while feature is suspended

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
        title = id > 0 ? 'Edit Quotation' : 'Create Quotation';
        btn_label = 'Save Quotation';
        jQuery(".dynamic-col").removeClass("col-md-3").addClass("col-md-2");
    } else {
        title = id > 0 ? 'Edit Sales Order' : 'Add Sales Order';
        btn_label = id > 0 ? 'Save' : 'Save as Draft';
        jQuery(".dynamic-col").removeClass("col-md-2").addClass("col-md-3");
    }

    const showDeliverToggle = !(id > 0) && !isQuotationMode;

    drawerEl.querySelector('#addEditSalesOrdersTitle').innerHTML = title;
    drawerEl.querySelector('#saveSalesOrderBtn').innerHTML = btn_label;
    drawerEl.querySelector('#saveSalesOrderConfirmedBtn').style.display = (id > 0 || isQuotationMode) ? 'none' : '';
    drawerEl.querySelector('#soValidUntilField').classList.toggle('d-none', !isQuotationMode);
    drawerEl.querySelector('#soQuoteDateField').classList.toggle('d-none', !isQuotationMode);
    drawerEl.querySelector('#soOrderDateField').classList.toggle('d-none', isQuotationMode);
    drawerEl.querySelector('#soOriginType').value = isQuotationMode ? 'quotation' : 'order';
    drawerEl.querySelector('#populateExpectedDate').classList.toggle('d-none', isQuotationMode);

    // Reset toggle state and hide Deliver Now button
    const toggleEl   = drawerEl.querySelector('#soDeliverNowToggle');
    const toggleWrap = drawerEl.querySelector('#soDeliverNowToggleWrap');
    toggleEl.checked = false;
    toggleWrap.classList.toggle('d-none', !showDeliverToggle);
    drawerEl.querySelector('#saveSalesOrderDeliverBtn').classList.add('d-none');

    cleanFormInputFeedback(formEl);

    // Reset customer lock/disable state
    jQuery('#soCustomerId').prop('disabled', false);
    drawerEl.querySelector('#soCustomerLocked').classList.add('d-none');
    drawerEl.querySelector('#soCreateCustomerLink')?.classList.remove('d-none');

    try {

        formEl.reset();
        formEl.querySelector('#soFormId').value = '';
        formEl.querySelector('#soLeadId').value = '';
        formEl.querySelector('#soOriginType').value = isQuotationMode ? 'quotation' : 'order';

        // Reset delivery type / shipping address
        document.getElementById('soDeliveryType').value = 'pickup';
        document.getElementById('soShippingAddressField').classList.add('d-none');
        _soAddrSource           = null;
        _soAddrData             = {};
        _soAddrCustomerAddresses = [];
        _soResetAddressUI();

        soOrderDiscountInfo = {};
        renderOrderDiscountRow();
        resetAdjustmentState();

        // Reset round-off state and init toggle visibility
        soRoundOffEnabled = false;
        const roToggleBtn = document.getElementById('toggleRoundOffBtn');
        if (roToggleBtn) {
            roToggleBtn.classList.remove('btn-secondary');
            roToggleBtn.classList.add('btn-outline-secondary');
            document.getElementById('toggleRoundOffLabel').textContent = 'Apply Round Off';
        }
        initRoundOffToggle();

        // Build params: pass lead_id when in quotation mode
        const params = { id };
        if (isQuotationMode && _soDrawerContext.leadId) {
            params.lead_id = _soDrawerContext.leadId;
        }

        const response = await api.get('/sales/orders/form-context', { params });
        const { data } = response.data;

        const soDetails = Object.assign({}, data.so_details || {}, {
            customer_shipping_addresses: data.customer_shipping_addresses || [],
        });
        const leadPrefill = data.lead_prefill || {};
        const locations = data.locations || [];
        const paymentTerms = data.payment_terms || [];
        const suggestedSoNumber = data.suggested_so_number ?? '';
        soAvailableProducts = data.products || [];
        soApplicableTaxes = data.taxes || [];
        const recentCustomers = data.recent_customers || [];

        jQuery('#soCustomerId').empty();
        initSOCustomerSelect2(recentCustomers);

        // Location select2
        initSelect2('#addEditSalesOrders select[name="location_id"]', {
            dropdownParent: drawerEl,
            placeholder: 'Choose location',
            data: buildSelect2Options(locations),
            autoSelectSingle: true,
            onChange: function() {
                clearAllSOSerials(true);
            },
        });

        // Payment terms select2
        initSelect2('#addEditSalesOrders select[name="payment_term_id"]', {
            dropdownParent: drawerEl,
            placeholder: 'Choose terms',
            data: buildSelect2Options(paymentTerms),
        });

        // Delivery address select2
        initSelect2('#soDeliveryAddressId', {
            dropdownParent: drawerEl,
            placeholder: 'Select address...',
            allowClear: true,
            onChange: _soDeliveryAddressChanged,
        });

        if (!(id > 0)) {
            const defaultTerm = paymentTerms.find(t => t.is_default == 1);
            if (defaultTerm) {
                jQuery('#addEditSalesOrders select[name="payment_term_id"]').val(defaultTerm.id).trigger('change');
            }
        }

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

            // Default the relevant date field to today
            if (isQuotationMode) {
                datePickerSetDate('#addEditSalesOrders [name="quote_date"]', new Date());
                datePickerSetDate('#addEditSalesOrders [name="order_date"]', '');
            } else {
                datePickerSetDate('#addEditSalesOrders [name="order_date"]', new Date());
                datePickerSetDate('#addEditSalesOrders [name="quote_date"]', '');
            }
            datePickerSetDate('#addEditSalesOrders [name="valid_until"]', '');
        }

        // Prefill from lead (only in quotation mode, new SO)
        if (isQuotationMode && leadPrefill.lead_id && !(id > 0)) {
            
            formEl.querySelector('#soLeadId').value = leadPrefill.lead_id;

            if (leadPrefill.customer_id) {
                // Lead is linked to a customer — prefill and lock
                _soSuppressCustomerAddrFetch = true;
                jQuery('#soCustomerId').append(new Option(leadPrefill.customer_name || '', leadPrefill.customer_id, true, true)).trigger('change');
                _soSuppressCustomerAddrFetch = false;
                jQuery('#soCustomerId').prop('disabled', true);
                drawerEl.querySelector('#soCustomerLocked').classList.remove('d-none');
                drawerEl.querySelector('#soCreateCustomerLink')?.classList.add('d-none');
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
        origin_type = 'order',
        customer_id,
        customer_name,
        location_id,
        so_number,
        reference,
        quote_date,
        order_date,
        valid_until,
        expected_delivery_date,
        payment_term_id,
        notes,
        internal_notes,
        discount_info,
        // adjustment_label and adjustment_amount suspended — feature under review
        line_items = [],
        delivery_type = 'pickup',
        shipping_address_snapshot = null,
        customer_shipping_addresses = [],
    } = soDetails;

    formEl.querySelector('#soFormId').value = id;
    formEl.querySelector('#soOriginType').value = origin_type || 'order';

    // Pre-select customer in Select2 (option guaranteed in recentCustomers by backend)
    _soSuppressCustomerAddrFetch = true;
    jQuery('#soCustomerId').append(new Option(customer_name || '', customer_id || '', true, true)).trigger('change');
    _soSuppressCustomerAddrFetch = false;

    jQuery('#addEditSalesOrders [name="location_id"]').val(location_id).trigger('change');
    jQuery('#addEditSalesOrders [name="payment_term_id"]').val(payment_term_id).trigger('change');

    formEl.querySelector('#soNumber').value = so_number || '';
    formEl.querySelector('#soNumberSuggested').value = suggestedSoNumber;

    jQuery('#addEditSalesOrders [name="reference"]').val(reference || '');
    jQuery('#addEditSalesOrders [name="notes"]').val(notes || '');
    jQuery('#addEditSalesOrders [name="internal_notes"]').val(internal_notes || '');

    datePickerSetDate('#addEditSalesOrders [name="quote_date"]', quote_date || '');
    datePickerSetDate('#addEditSalesOrders [name="order_date"]', order_date || '');
    datePickerSetDate('#addEditSalesOrders [name="valid_until"]', valid_until || '');
    datePickerSetDate('#addEditSalesOrders [name="expected_delivery_date"]', expected_delivery_date || '');

    // Order-level discount
    const soFormEl = document.getElementById('addEditSalesOrdersForm');
    let _discountInput = soFormEl.querySelector('input[name="order_discount_info"]');
    if (discount_info && discount_info.value > 0) {
        soOrderDiscountInfo = discount_info;
        if (!_discountInput) {
            _discountInput = document.createElement('input');
            _discountInput.type = 'hidden';
            _discountInput.name = 'order_discount_info';
            soFormEl.appendChild(_discountInput);
        }
        _discountInput.value = JSON.stringify(discount_info);
    } else if (_discountInput) {
        _discountInput.remove();
    }
    renderOrderDiscountRow();

    // Round-off: restore toggle state when editing an order that already has round-off applied
    const existingRoundOff = parseFloat(soDetails.round_off_amount || 0);
    const roMode = window.sysDefaultConfig?.roundOff?.mode || 'off';
    if (roMode === 'manual' && existingRoundOff !== 0) {
        soRoundOffEnabled = true;
        const roToggleBtn = document.getElementById('toggleRoundOffBtn');
        if (roToggleBtn) {
            roToggleBtn.classList.remove('btn-outline-secondary');
            roToggleBtn.classList.add('btn-secondary');
            document.getElementById('toggleRoundOffLabel').textContent = 'Remove Round Off';
        }
    }

    // Line items
    const tbodyEl = drawerEl.querySelector('#so_line_items tbody');
    tbodyEl.innerHTML = '';
    soItemIndex = 0;

    line_items.forEach(item => {

        const itemHtml = getSOLineItemHtml(item);
        tbodyEl.insertAdjacentHTML('beforeend', itemHtml);
        const newRow = tbodyEl.lastElementChild;

        _newRow = newRow;
        
        initSoRowSelect2(newRow, false);

        const prodId  = item.product_id || null;
        const unitPrice  = parseFloat(item.unit_price) || 0;
        const taxInfo = item.tax_info   || [];
        const taxIds  = taxInfo.map(t => Number(t.id));

        jQuery(newRow).find('select.so-items').val(prodId).trigger('change');
        jQuery(newRow).find('select.so-taxes').val(taxIds).trigger('change');
        // Set saved price directly — jQuery .trigger('change') does not reach native
        // document.addEventListener handlers, so call calcSOLineAmount explicitly instead.
        newRow.querySelector('.so-item-price').value = formatPrice(unitPrice);
        newRow.querySelector('.unit-price-hidden').value = unitPrice;
        calcSOLineAmount(newRow);
    });

    // Adjustment populate — suspended while feature is under review
    resetAdjustmentState();

    // Populate delivery type + shipping address
    const isShip = (delivery_type === 'ship');
    document.getElementById('soDeliveryType').value = delivery_type || 'pickup';
    document.getElementById('soShippingAddressField').classList.toggle('d-none', !isShip);

    _soAddrCustomerAddresses = customer_shipping_addresses || [];
    const addrSelect = jQuery('#soDeliveryAddressId');
    addrSelect.empty().append('<option value="">Select address...</option>');
    _soAddrCustomerAddresses.forEach(addr => addrSelect.append(new Option(addr.label, addr.id)));
    addrSelect.trigger('change');

    if (isShip) soRenderShipAddrState();

    // Restore saved shipping address snapshot
    if (shipping_address_snapshot) {
        const matchedAddr = shipping_address_snapshot.id
            ? _soAddrCustomerAddresses.find(a => String(a.id) === String(shipping_address_snapshot.id))
            : null;
        if (matchedAddr) {
            addrSelect.val(matchedAddr.id).trigger('change');
        } else {
            _soAddrSource = 'snapshot';
            _soAddrData   = shipping_address_snapshot;
            const parts = [shipping_address_snapshot.address_line1, shipping_address_snapshot.address_line2, shipping_address_snapshot.city, shipping_address_snapshot.state].filter(Boolean);
            const label = parts.length > 0 ? ('Saved address — ' + parts.join(', ')) : 'Saved address';
            addrSelect.append(new Option(label, '_snapshot'));
            addrSelect.val('_snapshot').trigger('change');
        }
    }

    recalcSOTotals();
}


/* ===================================================
   LINE ITEM HTML
=================================================== */
const getSOLineItemHtml = function(savedItem = {}) {

    console.log(savedItem);

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
        : '<i class="bx bx-edit-alt"></i>';

    const discountInfoJson = JSON.stringify(discountInfo);

    const qty = formatQty(ordered_qty);
    const price = parseFloat(unit_price) || 0;
    const total = formatCurrency(line_total);

    console.log(qty);

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
            <div class="so-serial-trigger mt-1 d-none" data-idx="${idx}" data-product-id="" data-product-name="">
                <a href="javascript:void(0);" class="so-open-serial-picker text-decoration-none d-flex align-items-center">
                    <i class="bx bx-barcode me-1 text-muted"></i><span class="so-serial-label text-muted small">Assign Serials</span>
                    <span class="so-serial-badge ms-1 badge bg-label-secondary py-1" style="font-size:11px;">0 / 0</span>
                </a>
                <div class="so-serial-error text-danger d-none" style="font-size:11px;"></div>
            </div>
            <div class="so-serial-inputs"></div>
        </td>
        <td class="px-2 qty-td">
            <div class="qty-input-wrapper position-relative">
                <input type="text" class="px-1 form-control text-end so-item-qty" name="so_items[${idx}][qty]" placeholder="1" value="${qty}">                
            </div>
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
                <span class="d-flex align-items-center discount-label">${discountLabel}</span>
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
const initSoRowSelect2 = function(rowEl, resetVal=true) {

    const drawerEl = rowEl.closest('#addEditSalesOrders');
    if (!drawerEl) return;

    const itemSelectEl = rowEl.querySelector('select.so-items');
    const taxSelectEl  = rowEl.querySelector('select.so-taxes');

    if (itemSelectEl) {
        initSelect2(itemSelectEl, {
            dropdownParent: drawerEl,
            placeholder: 'Choose item',
            resetVal: resetVal,
            onChange: function(_this) {

                const row = _this.closest('tr');
                const prodId = _this.value || '';

                // Remove old UOM label
                row.querySelector('td.qty-td .qty-input-wrapper .uom-label')?.remove();

                if (prodId) {

                    const productsMap = new Map(soAvailableProducts.map(p => [Number(p.id), p]));

                    const prod = productsMap.get(Number(prodId));

                    const uomsObj = prod.uoms || {};
                    const baseUom = Object.values(uomsObj).find(u => Number(u.is_base_uom) === 1);

                    if (baseUom) {
                        row.querySelector('.uom-id').value = baseUom.uom_id || '';
                        if (baseUom.code) {
                            row.querySelector('td.qty-td .qty-input-wrapper').insertAdjacentHTML('beforeend',
                                `<span class="uom-label fs-tiny mt-1 text-primary fw-semibold">UOM: ${baseUom.code}</span>`
                            );
                        }
                    }

                    // Default qty to 1 when empty or zero
                    const qtyInput = row.querySelector('.so-item-qty');
                    if (qtyInput && (parseFloat(qtyInput.value) || 0) <= 0) {
                        qtyInput.value = 1;
                        calcSOLineAmount(row);
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

                    // Show serial trigger only when Deliver Now toggle is ON
                    const serialTriggerEl = row.querySelector('.so-serial-trigger');
                    if (serialTriggerEl) {
                        const deliverNowOn = document.getElementById('soDeliverNowToggle')?.checked;
                        if (deliverNowOn && (prod?.stock_tracking_method || '') === 'serial') {
                            serialTriggerEl.dataset.productId   = prodId;
                            serialTriggerEl.dataset.productName = prod.name || '';
                            serialTriggerEl.classList.remove('d-none');
                            updateSOItemSerialBadge(row);
                        } else {
                            serialTriggerEl.classList.add('d-none');
                            clearSOItemSerials(row);
                        }
                    }

                } else {

                    console.log("RESET ROW VALUES");

                    row.querySelector('.uom-id').value = '';
                    row.querySelector('.so-item-qty').value = '';
                    row.querySelector('.so-item-price').value = '';
                    jQuery(row.querySelector('.so-taxes')).val(null).trigger('change');

                    const serialTriggerEl = row.querySelector('.so-serial-trigger');
                    if (serialTriggerEl) {
                        serialTriggerEl.classList.add('d-none');
                        clearSOItemSerials(row);
                    }
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

    console.log("calcSOLineAmount");
    console.log(rowEl);
    _rowEl = rowEl;

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
    console.log("Line total: "+lineTotal);
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

    // Order discount — % applied on post-item-discount subtotal (accounting standard)
    const soNetSubtotal = soSubtotal - soItemDiscounts;
    let orderDiscountAmt = 0;
    if (soOrderDiscountInfo.value > 0) {
        orderDiscountAmt = soOrderDiscountInfo.type === 'percent' ? soNetSubtotal * (parseFloat(soOrderDiscountInfo.value) / 100) : parseFloat(soOrderDiscountInfo.value);
    }

    // Proportionally reduce tax by the order-level discount
    const taxableBase   = soNetSubtotal;
    const discountRatio = taxableBase > 0 ? orderDiscountAmt / taxableBase : 0;
    const adjustedTax   = Math.max(0, soTaxTotal * (1 - discountRatio));

    // Round-off
    const subAfterItemDisc = soSubtotal - soItemDiscounts;
    const preRoundTotal    = subAfterItemDisc - orderDiscountAmt + adjustedTax;
    const roCfg            = window.sysDefaultConfig?.roundOff || {};
    const roMode           = roCfg.mode || 'off';
    let roundOffAmt = 0;
    if (roMode === 'auto' || (roMode === 'manual' && soRoundOffEnabled)) {
        roundOffAmt = computeRoundOff(preRoundTotal, parseFloat(roCfg.roundTo || 1), roCfg.method || 'nearest');
    }

    const soTotal = preRoundTotal + roundOffAmt;

    document.getElementById('soTotalSubtotal').innerHTML = formatCurrency(soSubtotal);
    document.getElementById('soTotalItemDiscounts').innerHTML = `-${formatCurrency(soItemDiscounts)}`;
    document.getElementById('soTotalTax').innerHTML = formatCurrency(adjustedTax);
    document.getElementById('soTotalAmount').innerHTML = formatCurrency(soTotal);
    document.getElementById('soTotalOrderDiscount').innerHTML = `-${formatCurrency(orderDiscountAmt)}`;

    // Round-off row
    const roRow = document.getElementById('soRoundOffRow');
    const roAmtEl = document.getElementById('soTotalRoundOff');
    if (roundOffAmt !== 0) {
        roRow.classList.remove('d-none');
        roAmtEl.innerHTML = (roundOffAmt < 0 ? '-' : '+') + formatCurrency(Math.abs(roundOffAmt));
        roAmtEl.className = 'text-end ' + (roundOffAmt < 0 ? 'text-danger' : 'text-success');
    } else {
        roRow.classList.add('d-none');
    }

    // Sync hidden round_off_amount field for form submission
    let roHidden = document.querySelector('input[name="round_off_amount"]');
    if (!roHidden) {
        roHidden = document.createElement('input');
        roHidden.type = 'hidden';
        roHidden.name = 'round_off_amount';
        document.getElementById('soTotalsTable').closest('form')?.appendChild(roHidden);
    }
    roHidden.value = roundOffAmt;
}


/* ===================================================
   ROUND-OFF HELPERS
=================================================== */
function computeRoundOff(amount, roundTo, method) {
    if (!roundTo || roundTo <= 0) return 0;
    let rounded;
    switch (method) {
        case 'up':   rounded = Math.ceil(amount  / roundTo) * roundTo; break;
        case 'down': rounded = Math.floor(amount / roundTo) * roundTo; break;
        default:     rounded = Math.round(amount / roundTo) * roundTo;
    }
    return parseFloat((rounded - amount).toFixed(4));
}

const initRoundOffToggle = function() {
    const btn    = document.getElementById('toggleRoundOffBtn');
    const roMode = window.sysDefaultConfig?.roundOff?.mode || 'off';
    if (roMode === 'manual') {
        btn.classList.remove('d-none');
    } else {
        btn.classList.add('d-none');
    }
};

document.getElementById('toggleRoundOffBtn')?.addEventListener('click', function() {
    soRoundOffEnabled = !soRoundOffEnabled;
    const label = document.getElementById('toggleRoundOffLabel');
    label.textContent = soRoundOffEnabled ? 'Remove Round Off' : 'Apply Round Off';
    this.classList.toggle('btn-outline-secondary', !soRoundOffEnabled);
    this.classList.toggle('btn-secondary', soRoundOffEnabled);
    recalcSOTotals();
});


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
   CUSTOMER SELECT2
=================================================== */
let _soSuppressCustomerAddrFetch = false;

const initSOCustomerSelect2 = function(recentCustomers) {

    const initialData = recentCustomers.map(c => ({
        id: c.id,
        text: c.display_name,
        email: c.email || '',
        phone: c.phone || '',
    }));

    initSelect2('#soCustomerId', {
        dropdownParent: jQuery('#addEditSalesOrders'),
        placeholder: 'Search or select customer...',
        minimumInputLength: 0,
        resetVal: false,
        ajax: {
            url: '/api/sales/orders/customers/search',
            dataType: 'json',
            delay: 300,
            data: params => ({ q: params.term || '' }),
            transport: function(params, success, failure) {
                if (!params.data.q) {
                    success({ data: initialData });
                    return;
                }
                jQuery.ajax(params).then(success).fail(failure);
            },
            processResults: function(response) {
                return {
                    results: (response.data || []).map(c => ({
                        id: c.id,
                        text: c.text || c.display_name,
                        email: c.email || '',
                        phone: c.phone || '',
                    }))
                };
            },
        },
        templateResult: function(item) {
            if (item.loading) return item.text;
            return jQuery('<div>')
                .append(jQuery('<div>').addClass('fw-semibold').text(item.text))
                .append(jQuery('<small>').addClass('text-muted').text([item.email, item.phone].filter(Boolean).join(' · ')));
        },
        templateSelection: item => item.text || item.id,
    });
};

const soOpenCreateCustomer = function() {
    if (typeof openCustomerFormDrawer !== 'function') return;
    openCustomerFormDrawer(0, {
        mode: 'from_so',
        onSaved: function(customer) {
            const option = new Option(customer.display_name, customer.id, true, true);
            jQuery('#soCustomerId').append(option).trigger('change');
        },
    });
};

jQuery('#soCustomerId').on('change', async function() {
    if (_soSuppressCustomerAddrFetch) return;
    const customerId = jQuery(this).val();
    if (!customerId) return;
    soRenderShipAddrState();
    try {
        const response = await api.get(`/customers/${customerId}/shipping-addresses`);
        _soAddrCustomerAddresses = response.data?.data || [];
        const addrSelect = jQuery('#soDeliveryAddressId');
        addrSelect.empty().append('<option value="">Select address...</option>');
        _soAddrCustomerAddresses.forEach(addr => addrSelect.append(new Option(addr.label, addr.id)));
        addrSelect.trigger('change');
    } catch (e) {}
});


/* ===================================================
   DELIVERY TYPE + SHIPPING ADDRESS
=================================================== */
let _soAddrSource           = null; // 'customer' | 'snapshot' | null
let _soAddrData             = {};
let _soAddrCustomerAddresses = [];

const _soResetAddressUI = function() {
    document.getElementById('soAddNewAddressBtn').classList.remove('d-none');
    document.getElementById('soEditAddressBtn').classList.add('d-none');
    document.getElementById('soShippingAddressJson').value = '';
    _soAddrSource = null;
    _soAddrData   = {};
};

const _soSyncAddressJson = function() {
    document.getElementById('soShippingAddressJson').value =
        (_soAddrData && Object.keys(_soAddrData).length > 0) ? JSON.stringify(_soAddrData) : '';
};

// Enable or disable the address Select2 based on whether a customer is selected
const soRenderShipAddrState = function() {
    const hasCustomer = !!document.getElementById('soCustomerId').value;
    jQuery('#soDeliveryAddressId').prop('disabled', !hasCustomer);
    document.getElementById('soShipNoCustomerMsg').classList.toggle('d-none', hasCustomer);
    document.getElementById('soShipAddrLinks').classList.toggle('d-none', !hasCustomer);
};

// Called via Select2 onChange when address dropdown changes
const _soDeliveryAddressChanged = function(_this) {
    const val = _this.value;
    if (!val) {
        _soAddrSource = null;
        _soAddrData   = {};
        document.getElementById('soAddNewAddressBtn').classList.remove('d-none');
        document.getElementById('soEditAddressBtn').classList.add('d-none');
        document.getElementById('soShippingAddressJson').value = '';
        return;
    }
    document.getElementById('soAddNewAddressBtn').classList.add('d-none');
    document.getElementById('soEditAddressBtn').classList.remove('d-none');
    if (val === '_snapshot') {
        _soAddrSource = 'snapshot';
    } else {
        _soAddrSource = 'customer';
        _soAddrData   = _soAddrCustomerAddresses.find(a => String(a.id) === String(val)) || {};
    }
    _soSyncAddressJson();
};

// Delivery type change
document.addEventListener('change', function(e) {
    if (!e.target.matches('#soDeliveryType')) return;
    const isShip = e.target.value === 'ship';
    document.getElementById('soShippingAddressField').classList.toggle('d-none', !isShip);
    if (isShip) soRenderShipAddrState();
});

// Add new shipping address
document.getElementById('soAddNewAddressBtn').addEventListener('click', function() {
    const customerId = document.getElementById('soCustomerId').value;
    if (!customerId) return;
    openCustomerAddressModal(customerId, 'shipping', {
        onSaved: function(addr) {
            _soAddrCustomerAddresses.push(addr);
            const addrSelect = jQuery('#soDeliveryAddressId');
            addrSelect.append(new Option(addr.label, addr.id));
            addrSelect.val(addr.id).trigger('change');
        },
    });
});

// Edit selected shipping address
document.getElementById('soEditAddressBtn').addEventListener('click', function() {
    const customerId = document.getElementById('soCustomerId').value;
    if (!customerId) return;
    if (_soAddrSource === 'customer') {
        const addrId = jQuery('#soDeliveryAddressId').val();
        openCustomerAddressModal(customerId, 'shipping', {
            editId:      addrId,
            prefillData: _soAddrData,
            onSaved: function(addr) {
                _soAddrData = addr;
                const addrSelect = jQuery('#soDeliveryAddressId');
                addrSelect.find(`option[value="${addrId}"]`).text(addr.label);
                const idx = _soAddrCustomerAddresses.findIndex(a => String(a.id) === String(addrId));
                if (idx !== -1) _soAddrCustomerAddresses[idx] = addr;
                _soSyncAddressJson();
            },
        });
    } else {
        openCustomerAddressModal(null, 'shipping', {
            mode:        'so_local',
            prefillData: _soAddrData,
            onSaved: function(addr) {
                _soAddrData = addr;
                _soSyncAddressJson();
            },
        });
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
        const row = e.target.closest('tr');
        calcSOLineAmount(row);
        const trigger = row.querySelector('.so-serial-trigger');
        if (trigger && !trigger.classList.contains('d-none')) {
            const qty          = parseInt(e.target.value) || 0;
            const selectedCount = row.querySelectorAll('.so-serial-inputs input').length;
            if (selectedCount > 0 && selectedCount !== qty) {
                clearSOItemSerials(row);
                const errorEl = trigger.querySelector('.so-serial-error');
                if (errorEl) {
                    errorEl.textContent = 'Qty changed — please re-select serials';
                    errorEl.classList.remove('d-none');
                }
            } else {
                updateSOItemSerialBadge(row);
            }
        }
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
    document.getElementById('discountModalLabel').innerHTML = 'Apply Item Discount';
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
    document.getElementById('discountModalLabel').innerHTML = 'Apply Order Discount';
    
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
    if (label) label.innerHTML = type === 'percent' ? 'Percent (%)' : 'Amount (₹)';
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
    const label = value > 0 ? (type === 'percent' ? `${value}%` : `₹${value.toFixed(2)}`) : '<i class="bx bx-edit-alt"></i>';

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
            rowEl.querySelector('.discount-label').innerHTML    = label;
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

        // Validate serial numbers are assigned for all serial-tracked items when saving as delivered
        if (statusOverride === 'delivered') {
            let serialErrors = [];
            document.querySelectorAll('#so_line_items tbody tr').forEach(function(row) {
                const trigger = row.querySelector('.so-serial-trigger');
                if (!trigger || trigger.classList.contains('d-none')) return;
                const qty           = parseInt(row.querySelector('.so-item-qty')?.value) || 0;
                const selectedCount = row.querySelectorAll('.so-serial-inputs input').length;
                if (selectedCount !== qty) {
                    const productName = trigger.dataset.productName || 'a product';
                    serialErrors.push(productName);
                    const errorEl = trigger.querySelector('.so-serial-error');
                    if (errorEl) {
                        errorEl.textContent = `Select ${qty} serial number${qty !== 1 ? 's' : ''} before delivering`;
                        errorEl.classList.remove('d-none');
                    }
                }
            });
            if (serialErrors.length > 0) {
                showFormGlobalFeedback(formEl, 'Serial numbers required for: ' + serialErrors.join(', '), 'error');
                return;
            }
        }

        const formData = new FormData(formEl);
        const payload = formDataToObject(formData);

        // Disabled <select> elements are excluded from FormData — restore customer_id manually
        const customerSelectEl = document.getElementById('soCustomerId');
        if (customerSelectEl.disabled && customerSelectEl.value) {
            payload.customer_id = customerSelectEl.value;
        }

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
    submitSalesOrderForm('delivered');
});

document.getElementById('soDeliverNowToggle').addEventListener('change', function() {
    const drawerEl  = document.getElementById('addEditSalesOrders');
    const deliverOn = this.checked;

    // Swap footer buttons
    drawerEl.querySelector('#saveSalesOrderBtn').classList.toggle('d-none', deliverOn);
    drawerEl.querySelector('#saveSalesOrderConfirmedBtn').classList.toggle('d-none', deliverOn);
    drawerEl.querySelector('#saveSalesOrderDeliverBtn').classList.toggle('d-none', !deliverOn);

    if (deliverOn) {
        // Reveal serial triggers for any serial-tracked products already in the table
        document.querySelectorAll('#so_line_items tbody tr').forEach(function(row) {
            const triggerEl = row.querySelector('.so-serial-trigger');
            if (!triggerEl) return;
            const prodSelect = row.querySelector('.so-items');
            if (!prodSelect || !prodSelect.value) return;
            const prod = soAvailableProducts.find(p => String(p.id) === String(prodSelect.value));
            if (prod && prod.stock_tracking_method === 'serial') {
                triggerEl.dataset.productId   = prod.id;
                triggerEl.dataset.productName = prod.name || '';
                triggerEl.classList.remove('d-none');
                updateSOItemSerialBadge(row);
            }
        });
    } else {
        // Hide all serial triggers and clear selections
        clearAllSOSerials(false);
        document.querySelectorAll('#so_line_items tbody tr .so-serial-trigger').forEach(function(el) {
            el.classList.add('d-none');
        });
    }
});

document.getElementById('populateExpectedDate').addEventListener('click', function() {
    datePickerSetDate('#addEditSalesOrders [name="expected_delivery_date"]', new Date());
});


jQuery(document).ready(function() {
    initDatePicker('#addEditSalesOrders input[name="quote_date"]');
    initDatePicker('#addEditSalesOrders input[name="order_date"]', { defaultDate: new Date() });
    initDatePicker('#addEditSalesOrders input[name="valid_until"]');
    initDatePicker('#addEditSalesOrders input[name="expected_delivery_date"]');
});


/* ===================================================
   SO SERIAL PICKER — helpers
=================================================== */
const clearSOItemSerials = function(rowEl) {
    const inputsEl = rowEl.querySelector('.so-serial-inputs');
    if (inputsEl) inputsEl.innerHTML = '';
    const errorEl = rowEl.querySelector('.so-serial-error');
    if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('d-none'); }
    updateSOItemSerialBadge(rowEl);
};

const clearAllSOSerials = function(showNotice) {
    let hadSerials = false;
    document.querySelectorAll('#so_line_items tbody tr').forEach(function(row) {
        const inputs = row.querySelector('.so-serial-inputs');
        if (inputs && inputs.children.length > 0) hadSerials = true;
        clearSOItemSerials(row);
    });
    if (showNotice && hadSerials) {
        window.notyf.error('Location changed — serial selections cleared');
    }
};

const updateSOItemSerialBadge = function(rowEl) {
    const trigger = rowEl.querySelector('.so-serial-trigger');
    if (!trigger || trigger.classList.contains('d-none')) return;
    const qty           = parseInt(rowEl.querySelector('.so-item-qty')?.value) || 0;
    const selectedCount = rowEl.querySelectorAll('.so-serial-inputs input').length;
    const badge         = trigger.querySelector('.so-serial-badge');
    const label         = trigger.querySelector('.so-serial-label');
    const complete      = qty > 0 && selectedCount >= qty;
    badge.className     = 'so-serial-badge ms-1 badge ' + (complete ? 'bg-label-success' : 'bg-label-secondary');
    badge.style.fontSize = '11px';
    badge.textContent   = selectedCount + ' / ' + qty;
    label.textContent   = complete ? 'Serials' : 'Assign Serials';
    label.className     = 'so-serial-label small ' + (complete ? 'text-success' : 'text-muted');
};


/* ===================================================
   SO SERIAL PICKER — open trigger (delegated)
=================================================== */
document.querySelector('#addEditSalesOrders #so_line_items').addEventListener('click', function(e) {
    const link = e.target.closest('.so-open-serial-picker');
    if (!link) return;

    const trigger    = link.closest('.so-serial-trigger');
    const row        = trigger.closest('tr');
    const locationId = jQuery('#addEditSalesOrders select[name="location_id"]').val() || 0;

    if (!locationId) { window.notyf.error('Please select a location first'); return; }

    const productId    = trigger.dataset.productId;
    const productName  = trigger.dataset.productName;
    const qty          = parseInt(row.querySelector('.so-item-qty')?.value) || 0;
    const rowIdx       = row.dataset.index;
    const current      = Array.from(row.querySelectorAll('.so-serial-inputs input')).map(function(i) { return i.value; });
    const locationText = jQuery('#addEditSalesOrders select[name="location_id"] option:selected').text() || '—';

    openSerialPicker({
        productId,
        productName,
        qty,
        locationId,
        locationLabel: locationText,
        currentSerials: current,
        onConfirm: function(selected) {
            const inputsEl = row.querySelector('.so-serial-inputs');
            inputsEl.innerHTML = '';
            selected.forEach(function(sn) {
                const inp  = document.createElement('input');
                inp.type   = 'hidden';
                inp.name   = 'so_items[' + rowIdx + '][serial_numbers][]';
                inp.value  = sn;
                inputsEl.appendChild(inp);
            });
            const errorEl = row.querySelector('.so-serial-error');
            if (errorEl) { errorEl.textContent = ''; errorEl.classList.add('d-none'); }
            updateSOItemSerialBadge(row);
        },
    });
});
</script>
@endpush
