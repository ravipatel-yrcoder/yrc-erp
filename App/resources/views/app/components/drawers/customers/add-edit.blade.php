<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditCustomer" aria-labelledby="addEditCustomerDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 45%;">

    <div class="offcanvas-header">
        <h5 id="addEditCustomerDrawerTitle" class="offcanvas-title">Add customer</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditCustomerForm">
            <input type="hidden" id="customer_id" value="" />

            <!-- Customer type -->
            <div class="mb-3">
                <label class="form-label">Customer type</label>
                <div class="d-flex gap-4">
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="customer_type" id="cust_type_company" value="company" checked />
                        <label class="form-check-label" for="cust_type_company">Company</label>
                    </div>
                    <div class="form-check">
                        <input class="form-check-input" type="radio" name="customer_type" id="cust_type_individual" value="individual" />
                        <label class="form-check-label" for="cust_type_individual">Individual</label>
                    </div>
                </div>
            </div>

            <!-- Contact person -->
            <div class="mb-3">
                <div class="row g-2">
                    <div class="col-md-3">
                        <label class="form-label">Salutation</label>
                        <select class="select2 form-select" name="salutation" id="cust_salutation">
                            <option value=""></option>
                            <option value="Mr.">Mr.</option>
                            <option value="Mrs.">Mrs.</option>
                            <option value="Ms.">Ms.</option>
                            <option value="Miss">Miss</option>
                            <option value="Dr.">Dr.</option>
                            <option value="Prof.">Prof.</option>
                        </select>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label required" id="cust_first_name_label">First name</label>
                        <input type="text" name="first_name" id="cust_first_name" class="form-control" placeholder="First name" />
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Last name</label>
                        <input type="text" name="last_name" id="cust_last_name" class="form-control" placeholder="Last name" />
                    </div>
                </div>
            </div>

            <!-- Company name -->
            <div class="mb-3">
                <label class="form-label required" id="cust_company_name_label">Company name</label>
                <input type="text" name="company_name" id="cust_company_name" class="form-control" placeholder="e.g. Acme Corp" />
            </div>

            <!-- Display name -->
            <div class="mb-4">
                <label class="form-label required">Display name</label>
                <select class="select2 form-select" id="cust_display_name_select">
                    <option value=""></option>
                </select>
                <input type="text" id="cust_display_name_manual" class="form-control mt-2" style="display:none;" placeholder="Enter display name" />
                <input type="hidden" name="display_name" id="cust_display_name_hidden" />
            </div>

            <!-- Email & Phone -->
            <div class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">Email</label>
                        <input type="email" name="email" id="cust_email_input" class="form-control" placeholder="billing@company.com" />
                        <p id="cust_email_dup_msg" class="text-warning small mt-1 mb-0"></p>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Phone</label>
                        <input type="text" name="phone" id="cust_phone_input" class="form-control" placeholder="12345 67890" />
                        <p id="cust_phone_dup_msg" class="text-warning small mt-1 mb-0"></p>
                    </div>
                </div>
            </div>

            <!-- PAN & GSTIN -->
            <div class="mb-4">
                <div class="row">
                    <div class="col-md-6">
                        <label class="form-label">PAN</label>
                        <input type="text" name="pan" class="form-control" placeholder="" />
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">GSTIN</label>
                        <input type="text" name="gstin" class="form-control" placeholder="" />
                    </div>
                </div>
            </div>

            <!-- Notes -->
            <div class="mb-4">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="2"></textarea>
            </div>

            <!-- Tabs -->
            <div class="nav-align-top">
                <ul class="nav nav-tabs shadow" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#cust-tab-general">General</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#cust-tab-addresses">Addresses</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#cust-tab-contacts">Contacts</button>
                    </li>
                </ul>

                <div class="tab-content px-0">

                    <!-- General tab -->
                    <div class="tab-pane fade show active" id="cust-tab-general" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label">Customer group</label>
                                    <select class="select2 form-select" name="customer_group_id" data-placeholder="Choose group" data-allow-clear="true">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label">Payment terms</label>
                                    <select class="select2 form-select" name="payment_term_id" data-placeholder="Choose terms" data-allow-clear="true">
                                        <option></option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label">Currency</label>
                                    <select class="select2 form-select" name="currency_code" data-placeholder="Choose currency" data-allow-clear="true">
                                        <option></option>
                                        @foreach (getCurrencies() as $currencyCode => $currency)
                                            <option value="{{ $currencyCode }}">{{ $currencyCode }} &ndash; {{ $currency['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-4">
                                    <label class="form-label">Credit limit</label>
                                    <input type="number" name="credit_limit" class="form-control" placeholder="0.00" min="0" step="0.01" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Addresses tab -->
                    <div class="tab-pane fade px-0" id="cust-tab-addresses" role="tabpanel">
                        <div class="row">
                            <div class="col-md-6">
                                <h6>Billing address</h6>
                                <div class="mb-3">
                                    <label class="form-label">Attention</label>
                                    <input type="text" name="billing_address[attention]" class="form-control" placeholder="Attention" />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Country</label>
                                    <select class="select2 form-select" name="billing_address[country]" data-placeholder="Country" data-allow-clear="true">
                                        <option></option>
                                        @foreach (getCountries() as $countryCode => $countryName)
                                            <option value="{{ $countryCode }}">{{ $countryName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address line 1</label>
                                    <textarea class="form-control" name="billing_address[address_line1]" rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address line 2</label>
                                    <textarea class="form-control" name="billing_address[address_line2]" rows="2"></textarea>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label">City</label>
                                        <input type="text" name="billing_address[city]" class="form-control" placeholder="City" />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">State</label>
                                        <input type="text" name="billing_address[state]" class="form-control" placeholder="State" />
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Postal code</label>
                                        <input type="text" name="billing_address[postal_code]" class="form-control" placeholder="Postal code" />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="billing_address[phone]" class="form-control" placeholder="Phone" />
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <h6>Shipping address <small>(<a href="javascript:void(0);" id="cust_copy_billing_to_shipping">Copy billing</a>)</small></h6>
                                <div class="mb-3">
                                    <label class="form-label">Attention</label>
                                    <input type="text" name="shipping_address[attention]" class="form-control" placeholder="Attention" />
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Country</label>
                                    <select class="select2 form-select" name="shipping_address[country]" data-placeholder="Country" data-allow-clear="true">
                                        <option></option>
                                        @foreach (getCountries() as $countryCode => $countryName)
                                            <option value="{{ $countryCode }}">{{ $countryName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address line 1</label>
                                    <textarea class="form-control" name="shipping_address[address_line1]" rows="2"></textarea>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Address line 2</label>
                                    <textarea class="form-control" name="shipping_address[address_line2]" rows="2"></textarea>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label">City</label>
                                        <input type="text" name="shipping_address[city]" class="form-control" placeholder="City" />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">State</label>
                                        <input type="text" name="shipping_address[state]" class="form-control" placeholder="State" />
                                    </div>
                                </div>
                                <div class="row mb-3">
                                    <div class="col-6">
                                        <label class="form-label">Postal code</label>
                                        <input type="text" name="shipping_address[postal_code]" class="form-control" placeholder="Postal code" />
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="shipping_address[phone]" class="form-control" placeholder="Phone" />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Contacts tab -->
                    <div class="tab-pane fade px-0" id="cust-tab-contacts" role="tabpanel">
                        <p class="text-muted">Contacts management coming soon.</p>
                    </div>

                </div>
            </div>

            <div class="form-check pt-4">
                <input class="form-check-input" type="checkbox" value="active" name="status" checked />
                <label class="form-check-label">Active</label>
            </div>

        </form>
    </div>

    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditCustomer" class="btn btn-primary btn-sm w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>


@push('scripts')
<script>
const cust_address_fields = ["attention", "country", "address_line1", "address_line2", "city", "state", "postal_code", "phone"];
const CUST_DN_MANUAL = '__manual__';

const custDebounce = (fn, delay) => {
    let timer;
    return function(...args) {
        clearTimeout(timer);
        timer = setTimeout(() => fn.apply(this, args), delay);
    };
};


// --- Duplicate detection ---

const checkCustomerDuplicate = async (field, value) => {
    const msgEl = document.getElementById(`cust_${field}_dup_msg`);
    const customerId = document.querySelector('#addEditCustomerForm input#customer_id').value || 0;
    try {
        const response = await api.get('/customers/check-duplicate', { params: { field, value, customer_id: customerId } });
        const { data } = response.data;
        msgEl.textContent = data.exists ? `A customer with this ${field} already exists: ${data.customer.display_name}. You can still save if intentional.` : '';
    } catch (e) {
        msgEl.textContent = '';
    }
};

document.getElementById('cust_email_input').addEventListener('blur', custDebounce(async () => {
    const val = document.getElementById('cust_email_input').value.trim();
    if (!val) { document.getElementById('cust_email_dup_msg').textContent = ''; return; }
    await checkCustomerDuplicate('email', val);
}, 300));

document.getElementById('cust_phone_input').addEventListener('blur', custDebounce(async () => {
    const val = document.getElementById('cust_phone_input').value.trim();
    if (!val) { document.getElementById('cust_phone_dup_msg').textContent = ''; return; }
    await checkCustomerDuplicate('phone', val);
}, 300));


// --- Display name select ---

const buildDisplayNameOptions = () => {
    const type        = document.querySelector('#addEditCustomerForm input[name="customer_type"]:checked')?.value || 'company';
    const salutation  = jQuery('#cust_salutation').val()?.trim() || '';
    const firstName   = document.getElementById('cust_first_name').value.trim();
    const lastName    = document.getElementById('cust_last_name').value.trim();
    const companyName = document.getElementById('cust_company_name').value.trim();

    const seen = new Set();
    const options = [];
    const add = (val) => {
        if (val && !seen.has(val)) { seen.add(val); options.push({ id: val, text: val }); }
    };

    const fullName   = [firstName, lastName].filter(Boolean).join(' ');
    const formalName = [salutation, firstName, lastName].filter(Boolean).join(' ');

    if (type === 'individual') {
        add(fullName);
        add(formalName);
        if (firstName && lastName) add(`${lastName}, ${firstName}`);
        add(companyName);
        if (fullName && companyName) add(`${fullName} (${companyName})`);
    } else {
        add(companyName);
        add(fullName);
        if (fullName && companyName) add(`${fullName} (${companyName})`);
    }

    options.push({ id: CUST_DN_MANUAL, text: '— Enter manually...' });
    return options;
};

const refreshDisplayNameSelect = (forceSelect = null) => {
    const drawerEl  = document.getElementById('addEditCustomer');
    const $select   = jQuery('#cust_display_name_select');
    const prevVal   = $select.val();
    const options   = buildDisplayNameOptions();
    const realOpts  = options.filter(o => o.id !== CUST_DN_MANUAL);

    let newVal;
    if (forceSelect !== null) {
        const exists = options.some(o => o.id === forceSelect);
        newVal = exists ? forceSelect : (forceSelect ? CUST_DN_MANUAL : '');
    } else {
        const prevStillValid = prevVal && prevVal !== CUST_DN_MANUAL && options.some(o => o.id === prevVal);
        if (prevVal === CUST_DN_MANUAL)   newVal = CUST_DN_MANUAL;
        else if (prevStillValid)          newVal = prevVal;
        else                              newVal = realOpts.length > 0 ? realOpts[0].id : '';
    }

    try { $select.select2('destroy'); } catch(e) {}
    $select.empty().append(new Option('', '', false, false));
    options.forEach(opt => $select.append(new Option(opt.text, opt.id, false, opt.id === newVal)));
    $select.select2({
        dropdownParent: jQuery(drawerEl),
        minimumResultsForSearch: Infinity,
        placeholder: '— Select display name —',
        allowClear: false,
    });
    if (newVal) $select.val(newVal).trigger('change');

    // Fallback: stored value not in generated options → fill manual input
    if (forceSelect && forceSelect !== CUST_DN_MANUAL && newVal === CUST_DN_MANUAL) {
        const manualEl = document.getElementById('cust_display_name_manual');
        manualEl.style.display = '';
        manualEl.value = forceSelect;
        document.getElementById('cust_display_name_hidden').value = forceSelect;
    }
};

jQuery('#cust_display_name_select').on('change', function() {
    const val      = jQuery(this).val();
    const isManual = val === CUST_DN_MANUAL;
    const manualEl = document.getElementById('cust_display_name_manual');
    if (isManual) {
        if (!manualEl.value) {
            const firstRealOpt = buildDisplayNameOptions().find(o => o.id !== CUST_DN_MANUAL);
            manualEl.value = firstRealOpt?.id || '';
            document.getElementById('cust_display_name_hidden').value = manualEl.value;
        }
        manualEl.style.display = '';
    } else {
        manualEl.style.display = 'none';
        document.getElementById('cust_display_name_hidden').value = val || '';
    }
});

document.getElementById('cust_display_name_manual').addEventListener('input', function() {
    document.getElementById('cust_display_name_hidden').value = this.value.trim();
});

const custDnDebounce = custDebounce(() => refreshDisplayNameSelect(), 300);
document.getElementById('cust_company_name').addEventListener('input', custDnDebounce);
document.getElementById('cust_first_name').addEventListener('input', custDnDebounce);
document.getElementById('cust_last_name').addEventListener('input', custDnDebounce);
jQuery('#cust_salutation').on('change', () => refreshDisplayNameSelect());

document.querySelectorAll('#addEditCustomerForm input[name="customer_type"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const isIndividual = this.value === 'individual';
        const companyLabel = document.getElementById('cust_company_name_label');
        companyLabel.className   = isIndividual ? 'form-label' : 'form-label required';
        companyLabel.textContent = isIndividual ? 'Company name (optional)' : 'Company name';
        refreshDisplayNameSelect();
    });
});


// --- Form helpers ---

const setCustFieldValue = function(selector, value) {
    const el = document.querySelector(selector);
    if (!el) return;
    if (el.tagName.toLowerCase() === "select") {
        el.value = value ?? "";
        el.dispatchEvent(new Event("change", { bubbles: true }));
    } else {
        el.value = value ?? "";
    }
};

const populateCustomerForm = function(details) {
    if (!details || Object.keys(details).length === 0) return;

    const { id, salutation, first_name, last_name, company_name, display_name,
            email, phone, customer_type, pan, gstin, currency_code,
            payment_term_id, customer_group_id, credit_limit, notes, status,
            billing_address, shipping_address } = details;

    // Customer type (triggers label updates)
    const typeRadio = document.querySelector(`#addEditCustomerForm input[name="customer_type"][value="${customer_type || 'company'}"]`);
    if (typeRadio) {
        typeRadio.checked = true;
        typeRadio.dispatchEvent(new Event('change', { bubbles: true }));
    }

    setCustFieldValue("#addEditCustomer input#customer_id", id);
    jQuery("#addEditCustomer select[name='salutation']").val(salutation || '').trigger('change');
    setCustFieldValue("#addEditCustomer input[name='first_name']", first_name || '');
    setCustFieldValue("#addEditCustomer input[name='last_name']", last_name || '');
    setCustFieldValue("#addEditCustomer input[name='company_name']", company_name || '');
    setCustFieldValue("#addEditCustomer input[name='email']", email);
    setCustFieldValue("#addEditCustomer input[name='phone']", phone);
    setCustFieldValue("#addEditCustomer input[name='pan']", pan);
    setCustFieldValue("#addEditCustomer input[name='gstin']", gstin);
    setCustFieldValue("#addEditCustomer textarea[name='notes']", notes);
    setCustFieldValue("#addEditCustomer select[name='currency_code']", currency_code || null);
    setCustFieldValue("#addEditCustomer select[name='payment_term_id']", payment_term_id || null);
    setCustFieldValue("#addEditCustomer select[name='customer_group_id']", customer_group_id || null);
    setCustFieldValue("#addEditCustomer input[name='credit_limit']", credit_limit || "");

    refreshDisplayNameSelect(display_name || null);

    cust_address_fields.forEach(field => {
        let billingVal  = (billing_address  && billing_address[field])  ? billing_address[field]  : (field === "country" ? null : "");
        let shippingVal = (shipping_address && shipping_address[field]) ? shipping_address[field] : (field === "country" ? null : "");
        setCustFieldValue(`#addEditCustomer [name="billing_address[${field}]"]`,  billingVal);
        setCustFieldValue(`#addEditCustomer [name="shipping_address[${field}]"]`, shippingVal);
    });

    jQuery("#addEditCustomer input[name='status']").prop("checked", status === "active");
};

const openCustomerFormDrawer = async function(id = 0) {
    const title = id > 0 ? "Edit customer" : "Add customer";
    document.getElementById("addEditCustomerDrawerTitle").innerHTML = title;

    const drawerEl = document.getElementById('addEditCustomer');
    const formEl   = document.getElementById('addEditCustomerForm');

    cleanFormInputFeedback(formEl);
    formEl.reset();
    formEl.querySelector("input#customer_id").value = '';

    // Reset name section labels to company defaults
    document.getElementById('cust_company_name_label').className   = 'form-label required';
    document.getElementById('cust_company_name_label').textContent  = 'Company name';

    // Reset display name section
    document.getElementById('cust_display_name_manual').style.display = 'none';
    document.getElementById('cust_display_name_manual').value         = '';
    document.getElementById('cust_display_name_hidden').value         = '';

    document.getElementById('cust_email_dup_msg').textContent = '';
    document.getElementById('cust_phone_dup_msg').textContent = '';

    try {
        const response = await api.get('/customers/form-context', { params: { id } });
        const { data } = response.data;
        const paymentTerms   = data.paymentTerms    || [];
        const customerGroups = data.customerGroups  || [];
        const customerDetails = data.customerDetails || {};

        jQuery("#addEditCustomer select[name='salutation']").select2({
            dropdownParent: jQuery(drawerEl),
            allowClear: true,
            minimumResultsForSearch: Infinity,
            placeholder: '—',
        });

        initSelect2("#addEditCustomer select[name='payment_term_id']", {
            dropdownParent: drawerEl,
            placeholder: "Choose terms",
            allowClear: true,
            data: buildSelect2Options(paymentTerms)
        });

        initSelect2("#addEditCustomer select[name='customer_group_id']", {
            dropdownParent: drawerEl,
            placeholder: "Choose group",
            allowClear: true,
            data: buildSelect2Options(customerGroups)
        });

        if (customerDetails && Object.keys(customerDetails).length > 0) {
            populateCustomerForm(customerDetails);
        } else {
            refreshDisplayNameSelect(); // initialize empty state
        }

        new bootstrap.Offcanvas(drawerEl).show();

    } catch (error) {
        handleApiError(error);
    }
};

document.getElementById('saveAddEditCustomer').addEventListener('click', async function() {
    const formEl = document.getElementById('addEditCustomerForm');
    try {
        const id = formEl.querySelector('input#customer_id').value || '';
        let endpoint = '/customers';
        if (id) endpoint += `/${id}`;

        cleanFormInputFeedback(formEl);

        // Sync display_name hidden field from current select/manual state
        const dnVal = jQuery('#cust_display_name_select').val();
        if (dnVal === CUST_DN_MANUAL) {
            document.getElementById('cust_display_name_hidden').value = document.getElementById('cust_display_name_manual').value.trim();
        } else {
            document.getElementById('cust_display_name_hidden').value = dnVal || '';
        }

        const formData = new FormData(formEl);
        const payload  = formDataToObject(formData);

        const response = await api.post(endpoint, payload);
        const { code, message } = response.data;

        notyf.success(message);

        if (code == 201 || code == 200) {
            if (typeof customersDt !== 'undefined') {
                customersDt.ajax.reload();
            }
            bootstrap.Offcanvas.getInstance(document.getElementById('addEditCustomer')).hide();
            formEl.reset();
        }

    } catch (error) {
        handleApiError(error, formEl);
    }
});

document.getElementById('cust_copy_billing_to_shipping').addEventListener('click', function() {
    cust_address_fields.forEach(field => {
        const billingEl = document.querySelector(`#addEditCustomer [name="billing_address[${field}]"]`);
        if (!billingEl) return;
        const value = field === "country" ? (billingEl.value || null) : billingEl.value;
        setCustFieldValue(`#addEditCustomer [name="shipping_address[${field}]"]`, value);
    });
});
</script>
@endpush