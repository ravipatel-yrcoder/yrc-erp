@extends('layouts.app')
@section('title', 'General Settings')

@section('content')
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @includeOnce('partial.app.settings-sidebar')

            <div class="col settings-content">

                <h5 class="mb-5 setting-section-title"><span>Profile</span></h5>

                <form id="profileForm" novalidate>

                    {{-- Company details --}}
                    <div class="row g-4 pb-8">
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">Company Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name" />
                        </div>
                        
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="email" />
                        </div>
                        
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="phone" />
                        </div>

                        <div class="col-12 form-control-validation">
                            <label class="form-label">Address</label>
                            <textarea class="form-control" name="address" rows="3" style="resize:none;"></textarea>
                        </div>
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">City</label>
                            <input type="text" class="form-control" name="city" />
                        </div>
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">State</label>
                            <input type="text" class="form-control" name="state" />
                        </div>
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">Country</label>
                            <select class="select2 form-select" name="country" id="countrySelect" data-placeholder="Select country" data-allow-clear="true">
                                <option></option>
                                @foreach(getCountries() as $code => $name)
                                    <option value="{{ $code }}">{{ $name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">ZIP Code</label>
                            <input type="text" class="form-control" name="zipcode" />
                        </div>
                    </div>

                    {{-- Contact person --}}
                    
                    <h5 class="setting-section-title"><span>Contact Person</span></h6>

                    <div class="row g-4 pb-8">
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">Name</label>
                            <input type="text" class="form-control" name="contact_name" />
                        </div>
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">Email</label>
                            <input type="email" class="form-control" name="contact_email" />
                        </div>
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">Phone</label>
                            <input type="text" class="form-control" name="contact_phone" />
                        </div>
                    </div>

                    <h5 class="setting-section-title"><span>Regional Settings</span></h6>

                    {{-- Regional settings --}}
                    <div class="row g-4 mb-6">
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">Timezone <span class="text-danger">*</span></label>
                            <select class="form-select" name="timezone" id="timezoneSelect">
                                @foreach(getTimezones() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">Currency <span class="text-danger">*</span></label>
                            <select class="select2 form-select" name="currency" id="currencySelect" data-placeholder="Select currency" data-allow-clear="true">
                                <option></option>
                                @foreach(getCurrencies() as $code => $currency)
                                    <option value="{{ $code }}">{{ $code }} &ndash; {{ $currency['name'] }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <h5 class="setting-section-title"><span>Legal & Tax</span></h5>

                    <div class="row g-4 pb-8">
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">Legal Name</label>
                            <input type="text" class="form-control" name="legal_name" />
                        </div>
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">Website</label>
                            <input type="url" class="form-control" name="website" placeholder="https://" />
                        </div>
                        <div class="col-12 col-md-4 form-control-validation">
                            <label class="form-label">GSTIN</label>
                            <input type="text" class="form-control" name="gstin" placeholder="22AAAAA0000A1Z5" maxlength="15" />
                        </div>
                        <div class="col-12 col-md-4 form-control-validation">
                            <label class="form-label">PAN</label>
                            <input type="text" class="form-control" name="pan" placeholder="AAAAA0000A" maxlength="10" />
                        </div>
                        <div class="col-12 col-md-4 form-control-validation">
                            <label class="form-label">TAN</label>
                            <input type="text" class="form-control" name="tan" placeholder="AAAA00000A" maxlength="10" />
                        </div>
                        <div class="col-12 col-md-6 form-control-validation">
                            <label class="form-label">CIN</label>
                            <input type="text" class="form-control" name="cin" placeholder="U12345AB1234ABC123456" maxlength="21" />
                        </div>
                    </div>

                    <h5 class="setting-section-title"><span>Branding</span></h5>

                    <div class="row g-4 pb-8">
                        <div class="col-12 col-md-6">
                            <label class="form-label">Company Logo</label>
                            <div class="mb-2">
                                <img id="logoPreview" src="" style="max-height:80px;max-width:220px;display:none;object-fit:contain;border:1px solid #e0e0e0;padding:6px;border-radius:4px;" alt="Logo">
                                <div id="logoPlaceholder" style="width:220px;height:80px;background:#f8f9fa;border:1px dashed #ccc;display:flex;align-items:center;justify-content:center;border-radius:4px;font-size:12px;color:#aaa;">No logo uploaded</div>
                            </div>
                            <input type="file" class="form-control form-control-sm" id="logoInput" accept="image/jpeg,image/png,image/webp" style="max-width:280px;">
                            <div class="form-text">PNG or JPG, max 2 MB. Displayed on sales documents.</div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label class="form-label">Signature</label>
                            <div class="mb-2">
                                <img id="signaturePreview" src="" style="max-height:80px;max-width:220px;display:none;object-fit:contain;border:1px solid #e0e0e0;padding:6px;border-radius:4px;" alt="Signature">
                                <div id="signaturePlaceholder" style="width:220px;height:80px;background:#f8f9fa;border:1px dashed #ccc;display:flex;align-items:center;justify-content:center;border-radius:4px;font-size:12px;color:#aaa;">No signature uploaded</div>
                            </div>
                            <input type="file" class="form-control form-control-sm" id="signatureInput" accept="image/jpeg,image/png,image/webp" style="max-width:280px;">
                            <div class="form-text">PNG or JPG, max 2 MB. Printed at the bottom of sales documents.</div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-sm btn-primary" id="saveBtn" onclick="saveProfile()">Save Changes</button>
                    </div>

                </form>

            </div>{{-- col --}}

        </div>{{-- row --}}
    </div>{{-- settings-page-content-wrapper --}}    
</div>{{-- container --}}
@endsection

@push('scripts')
<script>
'use strict';

document.addEventListener('DOMContentLoaded', async function () {
    initSelect2('#countrySelect',  { placeholder: 'Select country',  allowClear: true });
    initSelect2('#currencySelect', { placeholder: 'Select currency', allowClear: true });
    await loadProfile();
});

async function loadProfile() {
    try {
        const res  = await api.get('/company/profile');
        const data = res.data.data;
        populateForm(data);
    } catch (err) {
        notyf.error('Failed to load company profile.');
    }
}

function populateForm(data) {
    const form   = document.getElementById('profileForm');
    const fields = ['name', 'email', 'phone', 'address', 'city', 'state', 'zipcode',
                    'contact_name', 'contact_email', 'contact_phone',
                    'legal_name', 'website', 'gstin', 'pan', 'tan', 'cin'];

    fields.forEach(function (field) {
        const el = form.querySelector('[name="' + field + '"]');
        if (el) el.value = data[field] || '';
    });

    $('#countrySelect').val(data.country || null).trigger('change');
    $('#currencySelect').val(data.currency || null).trigger('change');
    document.getElementById('timezoneSelect').value = data.timezone || '';

    const logoPreview     = document.getElementById('logoPreview');
    const logoPlaceholder = document.getElementById('logoPlaceholder');
    if (data.logo_path) {
        logoPreview.src          = data.logo_path;
        logoPreview.style.display = 'block';
        logoPlaceholder.style.display = 'none';
    } else {
        logoPreview.src           = '';
        logoPreview.style.display = 'none';
        logoPlaceholder.style.display = 'flex';
    }

    const sigPreview     = document.getElementById('signaturePreview');
    const sigPlaceholder = document.getElementById('signaturePlaceholder');
    if (data.signature_path) {
        sigPreview.src          = data.signature_path;
        sigPreview.style.display = 'block';
        sigPlaceholder.style.display = 'none';
    } else {
        sigPreview.src           = '';
        sigPreview.style.display = 'none';
        sigPlaceholder.style.display = 'flex';
    }
}

async function saveProfile() {
    const form    = document.getElementById('profileForm');
    const saveBtn = document.getElementById('saveBtn');

    cleanFormInputFeedback(form);
    saveBtn.disabled    = true;
    saveBtn.textContent = 'Saving…';

    const payload = formDataToObject(new FormData(form));

    const logoInput = document.getElementById('logoInput');
    if (logoInput && logoInput.files.length > 0) {
        const files = await readFilesAsBase64(logoInput);
        if (files.length > 0) payload.logo_file = files[0];
    }

    const signatureInput = document.getElementById('signatureInput');
    if (signatureInput && signatureInput.files.length > 0) {
        const files = await readFilesAsBase64(signatureInput);
        if (files.length > 0) payload.signature_file = files[0];
    }

    try {
        const res = await api.post('/company/profile', payload);
        notyf.success(res.data.message || 'Profile updated successfully.');
        await loadProfile();
    } catch (err) {
        handleApiError(err, form);
    } finally {
        saveBtn.disabled    = false;
        saveBtn.textContent = 'Save Changes';
    }
}
</script>
@endpush
