@extends('layouts.app')
@section('title', 'Email Settings')

@section('content')
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @includeOnce('app.settings.sidebar')

            <div class="col settings-content">

                {{-- SMTP Section --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Outgoing Mail Server</h5>
                    <button type="button" class="btn btn-sm btn-primary" id="saveSmtpBtn" onclick="saveSmtp()">Save</button>
                </div>

                <div class="card shadow-none bg-transparent border mb-5">
                    <div class="card-body">
                        <div id="smtpNoConfigNotice" class="alert alert-warning py-2 px-3 mb-3 small d-none">
                            <i class="bx bx-info-circle me-1"></i>
                            No company SMTP configured. Emails are currently sent via the Zentraq platform mail server.
                            The <strong>Logged-in user</strong> From mode will not work as expected until you configure and save your own SMTP settings below.
                        </div>
                        <form id="smtpForm" novalidate>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label form-label-sm">SMTP Host</label>
                                    <input type="text" class="form-control form-control-sm" name="smtp_host" id="smtpHost" placeholder="smtp.example.com" />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label form-label-sm">Port</label>
                                    <input type="number" class="form-control form-control-sm" name="smtp_port" id="smtpPort" value="587" />
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label form-label-sm">Encryption</label>
                                    <select class="form-select form-select-sm" name="smtp_encryption" id="smtpEncryption">
                                        <option value="tls">TLS</option>
                                        <option value="ssl">SSL</option>
                                        <option value="none">None</option>
                                    </select>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-label-sm">SMTP Username</label>
                                    <input type="text" class="form-control form-control-sm" name="smtp_username" id="smtpUsername" placeholder="user@example.com" autocomplete="off" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-label-sm">SMTP Password</label>
                                    <div class="input-group input-group-sm">
                                        <input type="password" class="form-control form-control-sm" name="smtp_password" id="smtpPassword" autocomplete="new-password" />
                                        <button type="button" class="btn btn-outline-secondary" onclick="togglePasswordVisibility('smtpPassword', this)"><i class="bx bx-show"></i></button>
                                    </div>
                                    <div class="form-text text-muted">Leave blank to keep existing password.</div>
                                </div>
                                <div class="col-md-12"><hr class="my-1 opacity-25" /></div>
                                <div class="col-md-6">
                                    <label class="form-label form-label-sm">Default Sender Name</label>
                                    <input type="text" class="form-control form-control-sm" name="from_name" id="smtpFromName" placeholder="Your Company Name" />
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label form-label-sm">Default Sender Email</label>
                                    <input type="email" class="form-control form-control-sm" name="from_email" id="smtpFromEmail" placeholder="noreply@example.com" />
                                    <div class="form-text text-muted">Global fallback From address.</div>
                                </div>
                                <div class="col-md-12">
                                    <label class="form-label form-label-sm">Test — Send to</label>
                                    <div class="input-group input-group-sm">
                                        <input type="email" class="form-control form-control-sm" id="testSmtpTo" placeholder="your@email.com" />
                                        <button type="button" class="btn btn-outline-secondary" onclick="testSmtp()">Send Test</button>
                                    </div>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                {{-- Per-Document Email --}}
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="fw-bold mb-0">Per-Document Email</h5>
                </div>

                <ul class="nav nav-tabs mb-5" id="docEmailTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabQuote" type="button">SO - Quote</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSo" type="button">Sales Order</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRfq" type="button">Purchase Inquiry</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPo" type="button">Purchase Order</button>
                    </li>
                </ul>

                <div class="tab-content card shadow-none bg-transparent border mb-5">

                    @php
                    $docTokens = [
                        'purchase_order' => ['{po_number}', '{vendor_name}', '{company_name}', '{company_address}', '{order_date}', '{user_name}', '{user_email}', '{user_mobile}'],
                        'purchase_inquiry' => ['{po_number}', '{vendor_name}', '{company_name}', '{company_address}', '{order_date}', '{user_name}', '{user_email}', '{user_mobile}'],
                        'sales_order'    => ['{so_number}', '{customer_name}', '{company_name}', '{company_address}', '{order_date}', '{user_name}', '{user_email}', '{user_mobile}'],
                        'quotation'      => ['{so_number}', '{customer_name}', '{company_name}', '{company_address}', '{order_date}', '{user_name}', '{user_email}', '{user_mobile}'],
                    ];
                    @endphp

                    @foreach([
                        ['tabQuote', 'quotation',      'SO - Quote'],
                        ['tabSo',    'sales_order',    'Sales Order'],
                        ['tabRfq',   'purchase_inquiry', 'Purchase Inquiry'],
                        ['tabPo',    'purchase_order', 'Purchase Order'],
                    ] as [$tabId, $docType, $docLabel])
                    <div class="tab-pane fade {{ $tabId === 'tabQuote' ? 'show active' : '' }} p-4" id="{{ $tabId }}" role="tabpanel" data-doc-type="{{ $docType }}">
                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label form-label-sm fw-semibold">From Address Mode</label>
                                <div class="d-flex gap-3 mt-1">
                                    <div class="form-check">
                                        <input class="form-check-input doc-from-mode" type="radio" name="from_mode_{{ $docType }}" value="" id="fromModeDefault_{{ $docType }}" checked>
                                        <label class="form-check-label" for="fromModeDefault_{{ $docType }}">Use global default</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input doc-from-mode" type="radio" name="from_mode_{{ $docType }}" value="user" id="fromModeUser_{{ $docType }}">
                                        <label class="form-check-label" for="fromModeUser_{{ $docType }}">Logged-in user</label>
                                    </div>
                                    <div class="form-check">
                                        <input class="form-check-input doc-from-mode" type="radio" name="from_mode_{{ $docType }}" value="fixed" id="fromModeFixed_{{ $docType }}">
                                        <label class="form-check-label" for="fromModeFixed_{{ $docType }}">Fixed email</label>
                                    </div>
                                </div>
                                <div class="from-mode-user-hint-{{ $docType }} form-text text-warning mt-1" style="display:none;">
                                    <i class="bx bx-info-circle"></i>
                                    Uses the logged-in user's profile email as the From address. This only works correctly when a <strong>company SMTP is configured</strong> above. Without it, the platform mail server may override this address.
                                </div>
                            </div>
                            <div class="col-md-6 doc-fixed-fields-{{ $docType }}" style="display:none;">
                                <label class="form-label form-label-sm">From Name</label>
                                <input type="text" class="form-control form-control-sm" data-field="from_name" data-doc="{{ $docType }}" placeholder="Sender name" />
                            </div>
                            <div class="col-md-6 doc-fixed-fields-{{ $docType }}" style="display:none;">
                                <label class="form-label form-label-sm">From Email</label>
                                <input type="email" class="form-control form-control-sm" data-field="from_email" data-doc="{{ $docType }}" placeholder="sender@example.com" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label form-label-sm">Default Subject</label>
                                <input type="text" class="form-control form-control-sm" data-field="email_subject" data-doc="{{ $docType }}" placeholder="e.g. {po_number} — {vendor_name}" />
                            </div>
                            <div class="col-md-12">
                                <label class="form-label form-label-sm">Default Body</label>
                                <textarea id="emailBodyEditor_{{ $docType }}" data-field="email_body" data-doc="{{ $docType }}"></textarea>
                            </div>
                            <div class="col-md-12">
                                <div class="d-flex flex-wrap align-items-center gap-2 py-1 px-2 rounded bg-light border">
                                    <span class="text-muted small fw-semibold me-1">Insert token:</span>
                                    @foreach($docTokens[$docType] as $token)
                                    <button type="button"
                                            class="btn btn-sm btn-outline-secondary py-0 px-2"
                                            style="font-size:0.72rem;font-family:monospace;line-height:1.6;"
                                            onclick="insertDocToken('{{ $docType }}', '{{ $token }}')">{{ $token }}</button>
                                    @endforeach
                                    <span class="text-muted small ms-1">— Click to insert at cursor in Subject or Body</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-sm">Default CC <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="email" class="form-control form-control-sm" data-field="email_cc" data-doc="{{ $docType }}" placeholder="cc@example.com" />
                            </div>
                            <div class="col-md-6">
                                <label class="form-label form-label-sm">Default BCC <span class="text-muted fw-normal">(optional)</span></label>
                                <input type="email" class="form-control form-control-sm" data-field="email_bcc" data-doc="{{ $docType }}" placeholder="bcc@example.com" />
                            </div>
                            <div class="col-12 d-flex justify-content-end">
                                <button type="button" class="btn btn-sm btn-primary" onclick="saveDocConfig('{{ $docType }}')">Save {{ $docLabel }} Config</button>
                            </div>
                        </div>
                    </div>
                    @endforeach

                </div>

            </div>{{-- end settings-content --}}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    if (input.type === 'password') {
        input.type = 'text';
        btn.querySelector('i').className = 'bx bx-hide';
    } else {
        input.type = 'password';
        btn.querySelector('i').className = 'bx bx-show';
    }
}

// ─── SMTP dirty-state tracking ────────────────────────────────────────────────
function setSmtpTestButtonState(dirty) {
    const btn = document.querySelector('#smtpForm .btn[onclick="testSmtp()"]');
    if (!btn) return;
    btn.disabled = dirty;
    if (dirty) {
        btn.setAttribute('title', 'Save your settings before sending a test');
        btn.setAttribute('data-bs-toggle', 'tooltip');
        if (!btn._tooltip) btn._tooltip = new bootstrap.Tooltip(btn);
    } else {
        if (btn._tooltip) { btn._tooltip.dispose(); btn._tooltip = null; }
        btn.removeAttribute('title');
        btn.removeAttribute('data-bs-toggle');
    }
}

function markSmtpClean() {
    setSmtpTestButtonState(false);
    document.getElementById('smtpForm')._dirty = false;
}

function markSmtpDirty() {
    setSmtpTestButtonState(true);
    document.getElementById('smtpForm')._dirty = true;
}

async function loadSmtpSettings() {
    try {
        const res = await api.get('/company/settings/email/smtp');
        const d   = res.data?.data || {};
        document.getElementById('smtpHost').value       = d.smtp_host       || '';
        document.getElementById('smtpPort').value       = d.smtp_port       || 587;
        document.getElementById('smtpEncryption').value = d.smtp_encryption || 'tls';
        document.getElementById('smtpUsername').value   = d.smtp_username   || '';
        document.getElementById('smtpPassword').value   = d.smtp_password   || '';
        document.getElementById('smtpFromName').value   = d.from_name       || '';
        document.getElementById('smtpFromEmail').value  = d.from_email      || '';
        const notice = document.getElementById('smtpNoConfigNotice');
        if (notice) notice.classList.toggle('d-none', !!d.smtp_host);
        markSmtpClean();
    } catch (e) {
        handleApiError(e);
    }
}

async function saveSmtp() {
    const btn  = document.getElementById('saveSmtpBtn');
    const form = document.getElementById('smtpForm');
    cleanFormInputFeedback(form);
    const payload = {
        smtp_host:       document.getElementById('smtpHost').value.trim(),
        smtp_port:       document.getElementById('smtpPort').value,
        smtp_encryption: document.getElementById('smtpEncryption').value,
        smtp_username:   document.getElementById('smtpUsername').value.trim(),
        smtp_password:   document.getElementById('smtpPassword').value,
        from_name:       document.getElementById('smtpFromName').value.trim(),
        from_email:      document.getElementById('smtpFromEmail').value.trim(),
    };
    setButtonLoading(btn, true);
    try {
        await api.post('/company/settings/email/smtp', payload);
        notyf.success('Email settings saved.');
        markSmtpClean();
    } catch (e) {
        handleApiError(e, form);
    } finally {
        setButtonLoading(btn, false);
    }
}

async function testSmtp() {
    const btn = document.querySelector('#smtpForm .btn[onclick="testSmtp()"]');
    const to  = document.getElementById('testSmtpTo').value.trim();
    if (!to) { notyf.error('Enter a recipient email for the test.'); return; }
    setButtonLoading(btn, true);
    try {
        await api.post('/company/settings/email/test-smtp', { to });
        notyf.success('Test email sent! Check your inbox.');
    } catch (e) {
        handleApiError(e);
    } finally {
        setButtonLoading(btn, false);
    }
}

// ─── Per-Document Config ───────────────────────────────────────────────────────

let _docConfigs = {};
const _docJoditInstances = {};
const _lastFocusedField  = {};
const _subjectCursorPos  = {};

function initJoditForTab(docType) {
    if (_docJoditInstances[docType]) return;
    const editor = Jodit.make(`#emailBodyEditor_${docType}`, {
        height: 300,
        enter: 'BR',
        buttons: 'bold,italic,underline,strikethrough,|,ul,ol,|,paragraph,|,link,image',
        toolbarAdaptive: false,
        showCharsCounter: false,
        showWordsCounter: false,
        showXPathInStatusbar: false,
        addNewLine: false,
    });
    _docJoditInstances[docType] = editor;
    if (_docConfigs[docType]?.email_body != null) {
        editor.value = _docConfigs[docType].email_body;
    }
    editor.events.on('focus', () => { _lastFocusedField[docType] = 'body'; });
    const subjectInput = document.querySelector(`[data-field="email_subject"][data-doc="${docType}"]`);
    if (subjectInput) {
        subjectInput.addEventListener('focus', () => { _lastFocusedField[docType] = 'subject'; });
        subjectInput.addEventListener('blur', function() { _subjectCursorPos[docType] = this.selectionStart; });
    }
}

function insertDocToken(docType, token) {
    if (_lastFocusedField[docType] === 'body' && _docJoditInstances[docType]) {
        _docJoditInstances[docType].selection.insertHTML(token);
        return;
    }
    const input = document.querySelector(`[data-field="email_subject"][data-doc="${docType}"]`);
    if (!input) return;
    const pos = _subjectCursorPos[docType] !== undefined ? _subjectCursorPos[docType] : input.value.length;
    input.setRangeText(token, pos, pos, 'end');
    _subjectCursorPos[docType] = pos + token.length;
    input.focus();
}

async function loadDocConfigs() {
    try {
        const res = await api.get('/company/settings/email/doc-config');
        _docConfigs = res.data?.data?.configs || {};
        populateDocForms();
    } catch (e) {
        handleApiError(e);
    }
}

function populateDocForms() {
    for (const [docType, cfg] of Object.entries(_docConfigs)) {
        if (!cfg) continue;
        const mode  = cfg.from_mode || '';
        const radio = document.querySelector(`input[name="from_mode_${docType}"][value="${mode}"]`);
        if (radio) radio.checked = true;
        toggleFixedFields(docType, mode);
        document.querySelectorAll(`[data-doc="${docType}"]`).forEach(el => {
            const field = el.dataset.field;
            if (!field || field === 'email_body') return;
            if (cfg[field] != null) el.value = cfg[field];
        });
        if (_docJoditInstances[docType] && cfg.email_body != null) {
            _docJoditInstances[docType].value = cfg.email_body;
        }
    }
}

function toggleFixedFields(docType, mode) {
    const show = mode === 'fixed';
    document.querySelectorAll(`.doc-fixed-fields-${docType}`).forEach(el => {
        el.style.display = show ? '' : 'none';
    });
    const hint = document.querySelector(`.from-mode-user-hint-${docType}`);
    if (hint) hint.style.display = mode === 'user' ? '' : 'none';
}

async function saveDocConfig(docType) {
    const mode     = document.querySelector(`input[name="from_mode_${docType}"]:checked`)?.value || '';
    const getField = field => document.querySelector(`[data-doc="${docType}"][data-field="${field}"]`)?.value?.trim() || '';
    const payload  = {
        document_type:  docType,
        from_mode:      mode,
        from_name:      getField('from_name'),
        from_email:     getField('from_email'),
        email_subject:  getField('email_subject'),
        email_body:     _docJoditInstances[docType]?.value?.trim() || '',
        email_cc:       getField('email_cc'),
        email_bcc:      getField('email_bcc'),
    };
    try {
        await api.post('/company/settings/email/doc-config', payload);
        notyf.success('Config saved.');
    } catch (e) {
        handleApiError(e);
    }
}

document.querySelectorAll('.doc-from-mode').forEach(radio => {
    radio.addEventListener('change', function() {
        toggleFixedFields(this.name.replace('from_mode_', ''), this.value);
    });
});

document.querySelectorAll('#docEmailTabs button[data-bs-toggle="tab"]').forEach(btn => {
    btn.addEventListener('shown.bs.tab', function(e) {
        const paneId  = e.target.getAttribute('data-bs-target');
        const pane    = document.querySelector(paneId);
        const docType = pane?.dataset.docType;
        if (docType) initJoditForTab(docType);
    });
});

document.addEventListener('DOMContentLoaded', function() {
    loadSmtpSettings();
    loadDocConfigs();
    initJoditForTab('quotation');

    document.getElementById('smtpForm').addEventListener('input', markSmtpDirty);
    document.getElementById('smtpForm').addEventListener('change', markSmtpDirty);
});
</script>
@endpush
