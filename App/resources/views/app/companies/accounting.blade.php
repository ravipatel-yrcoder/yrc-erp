@extends('layouts.app')
@section('title', 'Accounting Settings')

@section('content')
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @includeOnce('partial.app.settings-sidebar')

            <div class="col settings-content">

                <div class="d-flex justify-content-end mb-4">
                    <button type="button" class="btn btn-sm btn-primary" id="saveBtn" onclick="saveSettings()">Save Changes</button>
                </div>
                <form id="settingsForm" novalidate>

                    {{-- Legal & Tax --}}
                    <div class="card shadow-none bg-transparent border mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Legal &amp; Tax</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-12 col-md-6 form-control-validation">
                                    <label class="form-label">Legal Name</label>
                                    <input type="text" class="form-control" name="legal[legal_name]" />
                                </div>
                                <div class="col-12 col-md-4 form-control-validation">
                                    <label class="form-label">GSTIN</label>
                                    <input type="text" class="form-control" name="legal[gstin]" placeholder="22AAAAA0000A1Z5" maxlength="15" />
                                </div>
                                <div class="col-12 col-md-4 form-control-validation">
                                    <label class="form-label">PAN</label>
                                    <input type="text" class="form-control" name="legal[pan]" placeholder="AAAAA0000A" maxlength="10" />
                                </div>
                                <div class="col-12 col-md-4 form-control-validation">
                                    <label class="form-label">TAN</label>
                                    <input type="text" class="form-control" name="legal[tan]" placeholder="AAAA00000A" maxlength="10" />
                                </div>
                                <div class="col-12 col-md-6 form-control-validation">
                                    <label class="form-label">CIN</label>
                                    <input type="text" class="form-control" name="legal[cin]" placeholder="U12345AB1234ABC123456" maxlength="21" />
                                </div>
                            </div>

                            <hr class="my-4">

                            <p class="text-muted small fw-semibold text-uppercase mb-3" style="letter-spacing:.05em;font-size:.7rem;">Signature</p>
                            <div class="row g-4">
                                <div class="col-12 col-md-6">
                                    <div class="mb-3">
                                        <img id="signaturePreview" src="" style="max-height:80px;max-width:220px;display:none;object-fit:contain;border:1px solid var(--bs-border-color);padding:6px;border-radius:6px;" alt="Signature">
                                        <div id="signaturePlaceholder" class="align-items-center justify-content-center text-muted small" style="display:flex;width:220px;height:80px;background:var(--bs-tertiary-bg);border:1px dashed var(--bs-border-color);border-radius:6px;">No signature uploaded</div>
                                    </div>
                                    <input type="file" class="form-control form-control-sm" id="signatureInput" accept="image/jpeg,image/png,image/webp" style="max-width:280px;">
                                    <div class="form-text">PNG or JPG, max 2 MB.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Accounting & Invoicing --}}
                    <div class="card shadow-none bg-transparent border mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Accounting &amp; Invoicing</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-12 col-md-4 form-control-validation">
                                    <label class="form-label required">Round-off Mode</label>
                                    <select class="select2 form-select" id="roundOffMode" name="invoicing[round_off_mode]">
                                        <option value="manual">Manual - toggle per order</option>
                                        <option value="auto">Auto - apply on every order</option>
                                        <option value="off">Off - disabled</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4 form-control-validation" id="roundToField">
                                    <label class="form-label required">Round To</label>
                                    <select class="select2 form-select" id="roundOffRoundTo" name="invoicing[round_off_round_to]">
                                        <option value="0.01">0.01</option>
                                        <option value="0.05">0.05</option>
                                        <option value="0.10">0.10</option>
                                        <option value="0.50">0.50</option>
                                        <option value="1.00">1.00</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4 form-control-validation" id="roundMethodField">
                                    <label class="form-label required">Rounding Method</label>
                                    <select class="select2 form-select" id="roundOffMethod" name="invoicing[round_off_method]">
                                        <option value="nearest">Nearest</option>
                                        <option value="up">Always Up</option>
                                        <option value="down">Always Down</option>
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
    initSelect2('#roundOffMode');
    initSelect2('#roundOffRoundTo');
    initSelect2('#roundOffMethod');
    await loadSettings();
    $('#roundOffMode').on('change', toggleRoundOffFields);
});

async function loadSettings() {
    try {
        const res  = await api.get('/company/settings/accounting');
        const data = res.data.data;
        populateForm(data);
    } catch (err) {
        notyf.error('Failed to load accounting settings.');
    }
}

function populateForm(data) {
    const legal     = data.legal     || {};
    const invoicing = data.invoicing || {};

    const form = document.getElementById('settingsForm');
    ['legal_name', 'gstin', 'pan', 'tan', 'cin'].forEach(function (field) {
        const el = form.querySelector('[name="legal[' + field + ']"]');
        if (el) el.value = legal[field] || '';
    });

    const sigPreview     = document.getElementById('signaturePreview');
    const sigPlaceholder = document.getElementById('signaturePlaceholder');
    if (legal.signature_path) {
        sigPreview.src               = legal.signature_path;
        sigPreview.style.display     = 'block';
        sigPlaceholder.style.display = 'none';
    } else {
        sigPreview.src               = '';
        sigPreview.style.display     = 'none';
        sigPlaceholder.style.display = 'flex';
    }

    $('#roundOffMode').val(invoicing.mode || 'manual').trigger('change');
    $('#roundOffRoundTo').val(String(parseFloat(invoicing.round_to || 1).toFixed(2))).trigger('change');
    $('#roundOffMethod').val(invoicing.method || 'nearest').trigger('change');
    toggleRoundOffFields();
}

function toggleRoundOffFields() {
    const isOff = $('#roundOffMode').val() === 'off';
    document.getElementById('roundToField').style.opacity     = isOff ? '0.4' : '1';
    document.getElementById('roundMethodField').style.opacity = isOff ? '0.4' : '1';
    $('#roundOffRoundTo').prop('disabled', isOff).trigger('change');
    $('#roundOffMethod').prop('disabled',  isOff).trigger('change');
}

async function saveSettings() {
    const form    = document.getElementById('settingsForm');
    const saveBtn = document.getElementById('saveBtn');

    cleanFormInputFeedback(form);
    setButtonLoading(saveBtn, true);

    // formDataToObject already parses bracket notation into a nested object
    const payload = formDataToObject(new FormData(form));
    if (!payload.legal)     payload.legal     = {};
    if (!payload.invoicing) payload.invoicing = {};

    const signatureInput = document.getElementById('signatureInput');
    if (signatureInput && signatureInput.files.length > 0) {
        const files = await readFilesAsBase64(signatureInput);
        if (files.length > 0) payload.legal.signature_file = files[0];
    }

    try {
        const res = await api.post('/company/settings/accounting', payload);

        // Sync global round-off config so SO drawer picks up new settings immediately
        if (window.sysDefaultConfig) {
            const inv = res.data.data.invoicing || {};
            window.sysDefaultConfig.roundOff = {
                mode:    inv.mode,
                roundTo: parseFloat(inv.round_to),
                method:  inv.method,
            };
        }

        notyf.success(res.data.message || 'Accounting settings saved.');
        await loadSettings();
    } catch (err) {
        handleApiError(err, form);
    } finally {
        setButtonLoading(saveBtn, false);
    }
}
</script>
@endpush
