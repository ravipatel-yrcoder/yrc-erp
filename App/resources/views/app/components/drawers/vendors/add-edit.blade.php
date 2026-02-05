<div class="offcanvas offcanvas-end" tabindex="-1" id="addEditVendor" aria-labelledby="addEditVendorDrawerTitle" data-bs-backdrop="static" data-bs-keyboard="false" style="width: 40%;">

    <div class="offcanvas-header">
        <h5 id="addEditVendorDrawerTitle" class="offcanvas-title">Add purchase order</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>

    <div class="offcanvas-body">
        <form id="addEditVendorForm">
            <div>
                <input type="hidden" id="id" value="" />
            </div>
            <div class="mb-4">
                
                <!--
                <label class="form-label d-block mb-3 required">Vendor Type</label>
                <div class="row">
                    <div class="col-md mb-md-0 mb-5">
                        <div class="form-check custom-option custom-option-basic">
                            <label class="form-check-label custom-option-content py-3" for="type_goods">
                                <input name="type" class="form-check-input" type="radio" value="company" id="type_company" checked />
                                <span class="custom-option-header pb-0"><span class="h6 mb-0">Company</span></span>
                                <span class="custom-option-body">
                                    <small>Vendor type is company.</small>
                                </span>
                            </label>
                        </div>
                    </div>
                    <div class="col-md">
                        <div class="form-check custom-option custom-option-basic">
                            <label class="form-check-label custom-option-content py-3" for="type_service">
                                <input name="type" class="form-check-input" type="radio" value="personal" id="type_personal" />
                                <span class="custom-option-header pb-0"><span class="h6 mb-0">Personal</span></span>
                                <span class="custom-option-body">
                                    <small>Vendor type is personal.</small>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </div>
            -->
            <div>
                <div class="row">
                    <div class="col-md">
                        <div class="mb-4" id="company_vendor_name">
                            <label class="form-label required">Company name</label>
                            <input type="text" name="company_name" class="form-control" placeholder="e.g: Company name" />
                        </div>
                        <div class="mb-4 d-none" id="company_vendor_personal">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label required">First name</label>
                                    <input type="text" name="first_name" class="form-control" placeholder="First name" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Last name</label>
                                    <input type="text" name="last_name" class="form-control" placeholder="Last name" />
                                </div>
                            </div>                            
                        </div>
                        <div class="mb-4">
                            <div class="row">
                                <div class="col-md-6">
                                    <label class="form-label required">Email</label>
                                    <input type="email" name="email" class="form-control" placeholder="abc@company.com" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label required">Phone</label>
                                    <input type="phone" name="phone" class="form-control" placeholder="12345 67890" />
                                </div>
                            </div>
                        </div>
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
                    </div>
                </div>                
            </div>
            <div class="mb-4">
                <label class="form-label">Notes</label>
                <textarea class="form-control" name="notes" rows="3"></textarea>
            </div>
            <div>
                <div class="nav-align-top">
                    <ul class="nav nav-tabs shadow" role="tablist">
                        <li class="nav-item">
                            <button type="button" class="nav-link active" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-general" aria-controls="navs-top-general" aria-selected="true">General</button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-addresses" aria-controls="navs-top-addresses" aria-selected="false">Addresses</button>
                        </li>
                        <li class="nav-item">
                            <button type="button" class="nav-link" role="tab" data-bs-toggle="tab" data-bs-target="#navs-top-contacts" aria-controls="navs-top-contacts" aria-selected="false">Contacts</button>
                        </li>                        
                    </ul>
                    <div class="tab-content px-0">
                        <div class="tab-pane fade show active" id="navs-top-general" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">Payment terms</label>
                                        <select class="select2 form-select" name="payment_term_id" placeholder="Payment terms">
                                            <option></option>
                                        </select>                                        
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label">Currency</label>
                                        <select class="select2 form-select" name="currency_code" data-placeholder="Choose currency" data-allow-clear="true">
                                            <option></option>
                                            @foreach (getCurrencies() as $currencyCode => $currency)
                                                <option value="{{ $currencyCode }}">
                                                    {{ $currencyCode }} &ndash; {{ $currency['name'] }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                </div>
                            </div>                            
                        </div>
                        <div class="tab-pane fade px-0" id="navs-top-addresses" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6">
                                    <h6>Billing address</h6>
                                    <div class="mb-4">
                                        <label class="form-label">Attention</label>
                                        <input type="text" name="billing_address[attention]" class="form-control" placeholder="Attention" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Country</label>
                                        <select class="select2 form-select" name="billing_address[country]" placeholder="Country">
                                            <option></option>
                                            @foreach (getCountries() as $countryCode => $countryName)
                                                <option value="{{ $countryCode }}">{{ $countryName }}</option>
                                            @endforeach
                                        </select>                                        
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Address line 1</label>
                                        <textarea class="form-control" name="billing_address[address_line1]"></textarea>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Address line 2</label>
                                        <textarea class="form-control" name="billing_address[address_line2]"></textarea>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">City</label>
                                        <input type="text" name="billing_address[city]" class="form-control" placeholder="City" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">State</label>
                                        <input type="text" name="billing_address[state]" class="form-control" placeholder="State" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Postal code</label>
                                        <input type="text" name="billing_address[postal_code]" class="form-control" placeholder="Postal code" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="billing_address[phone]" class="form-control" placeholder="Phone / Mobile" />
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6>Shipping address (<a href="javascript:void(0);" id="copy_as_billing_address">Copy as billing address</a>)</h6>
                                    <div class="mb-4">
                                        <label class="form-label">Attention</label>
                                        <input type="text" name="shipping_address[attention]" class="form-control" placeholder="Attention" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Country</label>
                                        <select class="select2 form-select" name="shipping_address[country]" placeholder="Country">
                                            <option></option>
                                            @foreach (getCountries() as $countryCode => $countryName)
                                                <option value="{{ $countryCode }}">{{ $countryName }}</option>
                                            @endforeach
                                        </select>                                        
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Address line 1</label>
                                        <textarea class="form-control" name="shipping_address[address_line1]"></textarea>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Address line 2</label>
                                        <textarea class="form-control" name="shipping_address[address_line2]"></textarea>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">City</label>
                                        <input type="text" name="shipping_address[city]" class="form-control" placeholder="City" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">State</label>
                                        <input type="text" name="shipping_address[state]" class="form-control" placeholder="State" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Postal code</label>
                                        <input type="text" name="shipping_address[postal_code]" class="form-control" placeholder="Postal code" />
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label">Phone</label>
                                        <input type="text" name="shipping_address[phone]" class="form-control" placeholder="Phone / Mobile" />
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade px-0" id="navs-top-contacts" role="tabpanel"><p>Yet to implement</p></div>
                    </div>
                  </div>
            </div>
            
            <div class="form-check pt-4">
                <input class="form-check-input" type="checkbox" value="active" name="status" checked />
                <label class="form-check-label"> Active</label>
            </div>

        </form>
    </div>
    <div class="offcanvas-footer">
        <div class="d-flex gap-3">
            <button type="button" id="saveAddEditVendor" class="btn btn-primary btn-sm w-px-100">Save</button>
            <button type="button" class="btn btn-label-secondary btn-sm w-px-100" data-bs-dismiss="offcanvas">Cancel</button>
        </div>
    </div>
</div>


@push('scripts')
<script>
// Defined globally as its being used in populateVendorForm() and Copy As Billing Address feature
const address_fields = ["attention", "country", "address_line1", "address_line2", "city", "state", "postal_code", "phone"];


const setVendorFormFieldValue = function(selector, value) {
    
    const el = document.querySelector(selector);
    if (!el) return;

    const tag = el.tagName.toLowerCase();
    
    if (tag === "select") {
        el.value = value ?? "";
        el.dispatchEvent(new Event("change", { bubbles: true }));
    } else {
        el.value = value ?? "";
    }
}


const buildPaymentTermsSelect2Options = function(terms) {

    let result = [];
    terms.forEach(term => {
        result.push({
            id: term.id,
            text: term.name
        });
    });

    return result;
}


const populateVendorForm = function(vendorDetails) {
    
    if (Object.keys(vendorDetails).length === 0) return;    


    const { id, display_name, email, phone, website, pan, gstin, currency_code, payment_term_id, notes, status, billing_address, shipping_address } = vendorDetails;
    
    setVendorFormFieldValue("#addEditVendor input#id", id);
    setVendorFormFieldValue("#addEditVendor input[name='company_name']", display_name);
    setVendorFormFieldValue("#addEditVendor input[name='email']", email);
    setVendorFormFieldValue("#addEditVendor input[name='phone']", phone);
    setVendorFormFieldValue("#addEditVendor input[name='pan']", pan);
    setVendorFormFieldValue("#addEditVendor input[name='gstin']", gstin);
    //setVendorFormFieldValue("#addEditVendor input[name='website']", website);
    setVendorFormFieldValue("#addEditVendor textarea[name='notes']", notes);
    setVendorFormFieldValue("#addEditVendor select[name='payment_term_id']", payment_term_id || null);
    setVendorFormFieldValue("#addEditVendor select[name='currency_code']", currency_code || null);

    // Billing & Shipping Addresses    
    address_fields.forEach(field => {

        let billingVal = billing_address[field] || "";
        let shippingVal = shipping_address[field] || "";
        if( field === "country" ) {
            billingVal = billingVal || null;
            shippingVal = shippingVal || null;
        }
        
        // Billing
        setVendorFormFieldValue(`#addEditVendor [name="billing_address[${field}]"]`, billingVal);
        
        // Shipping
        setVendorFormFieldValue(`#addEditVendor [name="shipping_address[${field}]"]`, shippingVal);        
    });

    const statusChecked = status == "active" ? true : false;    
    jQuery("#addEditVendor input[name='status']").prop("checked", statusChecked);

}

const openVendorFormDrawer = async function(id=0) {
    
    let title = "Add vendor";
    if( id > 0 ) title = "Edit vendor";
    document.getElementById("addEditVendorDrawerTitle").innerHTML = title;

    const drawerEl = document.getElementById('addEditVendor');
    const formEl = document.getElementById('addEditVendorForm');

    // clean form feedback
    cleanFormInputFeedback(formEl);

    try {

        formEl.reset();        
        formEl.querySelector("input#id").value='';
        
        const payload = {params: {id}};
        const response = await api.get('/vendors/form-context', payload);

        const { data } = response.data;
        const paymentTerms = data.paymentTerms || [];
        const vendorDetails = data.vendorDetails || {};
        
        // init payment terms
        initSelect2("#addEditVendor select[name='payment_term_id']", {dropdownParent: drawerEl, placeholder:"Choose terms", data: buildSelect2Options(paymentTerms)});

        populateVendorForm(vendorDetails);

        new bootstrap.Offcanvas(drawerEl).show();        

    } catch(error) {
        
        handleApiError(error);        
    }    
}

const saveAddEditVendorButton = document.getElementById('saveAddEditVendor');
saveAddEditVendorButton.addEventListener('click', async function(e) {
    
    const formEl = document.getElementById('addEditVendorForm');

    try {

        const id = formEl.querySelector('input#id').value || '';

        let apiPostfix = `/vendors`;
        if( id ) {
            apiPostfix += `/${id}`;
        }
        // clean form input feedback
        cleanFormInputFeedback(formEl);

        const formData = new FormData(formEl);
        const payload = formDataToObject(formData)

        const response = await api.post(apiPostfix, payload);
        const { code, message } = response.data;

        notyf.success(message);

        if( code == 201 || code == 200 ) {

            if( typeof(vendorsDt) != "undefined" ) {
                vendorsDt.ajax.reload()
            }

            const drawer = bootstrap.Offcanvas.getInstance(document.getElementById('addEditVendor'));
            drawer.hide();

            formEl.reset();
        }        

    } catch(error) {

        handleApiError(error, formEl);
    }

});


const copyAsBillingAddressBtn = document.getElementById('copy_as_billing_address');
copyAsBillingAddressBtn.addEventListener('click', async function(e) {
    
    address_fields.forEach(field => {
        
        const billingInput = document.querySelector(`#addEditVendor [name="billing_address[${field}]"]`);
        if (!billingInput) return;

        let value = billingInput.value || "";
        if( field === "country" ) {
            value = value || null;
        }

        setVendorFormFieldValue(`#addEditVendor [name="shipping_address[${field}]"]`, value);
    });

});
</script>
@endpush