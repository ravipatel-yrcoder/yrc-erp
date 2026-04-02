<div class="modal stacked-modal fade" id="customerAddressModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title" id="customerAddressModalTitle">Add Shipping Address</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <div class="modal-body">
                <form id="customerAddressForm">

                    <input type="hidden" id="custAddrCustomerId" value="" />
                    <input type="hidden" id="custAddrAddressType" name="address_type" value="" />

                    <div class="form-glob-feedback mb-3"></div>

                    <div class="row g-3">

                        <div class="col-12">
                            <label class="form-label">Attention</label>
                            <input type="text" class="form-control" name="attention" placeholder="Attention" />
                        </div>

                        <div class="col-12">
                            <label class="form-label">Country / Region</label>
                            <select class="form-select" name="country" id="custAddrCountry">
                                <option value=""></option>
                                @foreach (getCountries() as $countryCode => $countryName)
                                    <option value="{{ $countryCode }}" {{ $countryCode === 'IN' ? 'selected' : '' }}>{{ $countryName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label required">Address</label>
                            <textarea class="form-control mb-2" name="address_line1" rows="2" placeholder="Street 1"></textarea>
                            <textarea class="form-control" name="address_line2" rows="2" placeholder="Street 2"></textarea>
                        </div>

                        <div class="col-12">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" placeholder="City" />
                        </div>

                        <div class="col-6">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" name="state" placeholder="State" />
                        </div>

                        <div class="col-6">
                            <label class="form-label">Pin Code</label>
                            <input type="text" class="form-control" name="postal_code" placeholder="Pin Code" />
                        </div>

                        <div class="col-12">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" placeholder="Phone" />
                        </div>

                    </div>

                    <p class="text-muted small mt-4 mb-0">
                        <strong>Note:</strong> Changes made here will be updated for this customer.
                    </p>

                </form>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn btn-label-secondary btn-sm" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary btn-sm" id="saveCustomerAddressBtn">Save</button>
            </div>

        </div>
    </div>
</div>

<script>
let _custAddrOnSaved = null;

const openCustomerAddressModal = function(customerId, addressType, options) {

    addressType      = addressType || 'shipping';
    options          = options     || {};
    _custAddrOnSaved = options.onSaved || null;

    document.getElementById('custAddrAddressType').value = addressType;

    const typeLabel = addressType === 'billing' ? 'Billing' : 'Shipping';
    document.getElementById('customerAddressModalTitle').textContent = 'Add new ' + typeLabel + ' address';

    const form = document.getElementById('customerAddressForm');
    form.reset();
    cleanFormInputFeedback(form);
    document.querySelector('#customerAddressModal .form-glob-feedback').innerHTML = '';
    document.getElementById('custAddrCustomerId').value = customerId || '';

    initSelect2('#custAddrCountry', {
        dropdownParent: jQuery('#customerAddressModal'),
        placeholder: 'Select country',
        allowClear: true,
    });

    bootstrap.Modal.getOrCreateInstance(document.getElementById('customerAddressModal')).show();
};


const saveCustomerAddressBtnEl = document.getElementById('saveCustomerAddressBtn');
saveCustomerAddressBtnEl.addEventListener('click', async function(e) {

    const formEl = document.getElementById('customerAddressForm');
    const customerId = document.getElementById('custAddrCustomerId').value;

    try {

        if (!customerId) {
            notyf.error('No customer selected');
            return;
        }

        cleanFormInputFeedback(formEl);

        const formData = new FormData(formEl);
        const payload  = formDataToObject(formData);

        const response = await api.post(`/customers/${customerId}/addresses`, payload);
        const { data, message } = response.data;

        notyf.success(message);

        bootstrap.Modal.getInstance(document.getElementById('customerAddressModal'))?.hide();
        formEl.reset();

        if (typeof _custAddrOnSaved === 'function') {
            _custAddrOnSaved(data);
        }

    } catch (error) {
        handleApiError(error, formEl);
    }
});
</script>
