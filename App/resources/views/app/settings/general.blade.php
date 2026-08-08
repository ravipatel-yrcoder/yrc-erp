@extends('layouts.app')
@section('title', 'General Settings')

@section('content')
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @includeOnce('app.settings.sidebar')

            <div class="col settings-content">

                <div class="d-flex justify-content-end mb-4">
                    <button type="button" class="btn btn-sm btn-primary" id="saveBtn" onclick="saveSettings()">Save Changes</button>
                </div>
                <form id="settingsForm" novalidate>

                    {{-- Company --}}
                    <div class="card shadow-none bg-transparent border mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Company</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-12 col-md-6 form-control-validation">
                                    <label class="form-label required">Company Name</label>
                                    <input type="text" class="form-control" name="name" />
                                </div>
                                <div class="col-12 col-md-6 form-control-validation">
                                    <label class="form-label">Business Type</label>
                                    <select class="form-select" name="business_type" id="businessTypeSelect">
                                        @foreach(config('constants.company.business_types') as $bt)
                                            <option value="{{ $bt['key'] }}">{{ $bt['label'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6 form-control-validation">
                                    <label class="form-label">Website</label>
                                    <input type="url" class="form-control" name="website" placeholder="https://" />
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

                            <hr class="my-4">

                            <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:.05em;font-size:.7rem;">Company Logo</p>
                            <div class="row g-4">
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <img id="logoPreview" src="" style="max-height:80px;max-width:220px;display:none;object-fit:contain;border:1px solid var(--bs-border-color);padding:6px;border-radius:6px;" alt="Logo">
                                        <div id="logoPlaceholder" class="align-items-center justify-content-center text-muted small" style="display:flex;width:220px;height:80px;background:var(--bs-tertiary-bg);border:1px dashed var(--bs-border-color);border-radius:6px;">No logo uploaded</div>
                                    </div>
                                    <input type="file" class="form-control form-control-sm" id="logoInput" accept="image/jpeg,image/png,image/webp" style="max-width:280px;">
                                    <div class="form-text">PNG or JPG, max 2 MB.</div>
                                </div>
                            </div>

                            <hr class="my-4">

                            <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:.05em;font-size:.7rem;">Contact Person</p>
                            <div class="row g-4">
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
                        </div>
                    </div>

                    {{-- Regional --}}
                    <div class="card shadow-none bg-transparent border mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Regional</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-12 col-md-6 form-control-validation">
                                    <label class="form-label required">Timezone</label>
                                    <select class="select2 form-select" name="timezone" id="timezoneSelect">
                                        @foreach(getTimezones() as $value => $label)
                                            <option value="{{ $value }}">{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 col-md-6 form-control-validation">
                                    <label class="form-label required">Currency</label>
                                    <select class="select2 form-select" name="currency" id="currencySelect" data-placeholder="Select currency" data-allow-clear="true">
                                        <option></option>
                                        @foreach(getCurrencies() as $code => $currency)
                                            <option value="{{ $code }}">{{ $code }} &ndash; {{ $currency['name'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>
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
    initSelect2('#timezoneSelect');
    await loadSettings();
});

async function loadSettings() {
    try {
        const res  = await api.get('/company/settings/general');
        const data = res.data.data;
        populateForm(data);
    } catch (err) {
        notyf.error('Failed to load general settings.');
    }
}

function populateForm(data) {
    const form   = document.getElementById('settingsForm');
    const fields = ['name', 'email', 'phone', 'address', 'city', 'state', 'zipcode',
                    'website', 'contact_name', 'contact_email', 'contact_phone'];

    fields.forEach(function (field) {
        const el = form.querySelector('[name="' + field + '"]');
        if (el) el.value = data[field] || '';
    });

    $('#countrySelect').val(data.country || null).trigger('change');
    $('#currencySelect').val(data.currency || null).trigger('change');
    $('#timezoneSelect').val(data.timezone || '').trigger('change');
    $('#businessTypeSelect').val(data.business_type || 'general');

    const logoPreview     = document.getElementById('logoPreview');
    const logoPlaceholder = document.getElementById('logoPlaceholder');
    if (data.logo_path) {
        logoPreview.src               = data.logo_path;
        logoPreview.style.display     = 'block';
        logoPlaceholder.style.display = 'none';
    } else {
        logoPreview.src               = '';
        logoPreview.style.display     = 'none';
        logoPlaceholder.style.display = 'flex';
    }
}

async function saveSettings() {
    const form    = document.getElementById('settingsForm');
    const saveBtn = document.getElementById('saveBtn');

    cleanFormInputFeedback(form);
    setButtonLoading(saveBtn, true);

    const payload = formDataToObject(new FormData(form));

    const logoInput = document.getElementById('logoInput');
    if (logoInput && logoInput.files.length > 0) {
        const files = await readFilesAsBase64(logoInput);
        if (files.length > 0) payload.logo_file = files[0];
    }

    try {
        const res = await api.post('/company/settings/general', payload);
        notyf.success(res.data.message || 'Settings saved successfully.');
        await loadSettings();
    } catch (err) {
        handleApiError(err, form);
    } finally {
        setButtonLoading(saveBtn, false);
    }
}
</script>
@endpush
