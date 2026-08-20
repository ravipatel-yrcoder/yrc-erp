@extends('layouts.app')
@section('title', 'Sales Order Settings')

@section('content')
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @includeOnce('app.settings.sidebar')

            <div class="col settings-content">

                @includeOnce('app.settings.documents-tabs')

                <div class="d-flex justify-content-end mb-4">
                    <button type="button" class="btn btn-sm btn-primary" id="saveBtn" onclick="saveSettings()">Save Changes</button>
                </div>

                {{-- PDF Template --}}
                <div class="card shadow-none bg-transparent border mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">PDF Template</h6>
                    </div>
                    <div class="card-body">
                        @if(count($templates) > 0)
                        <div class="row g-4">
                            @foreach($templates as $slug => $tpl)
                            <div class="col-12 col-md-4">
                                <div class="doc-template-card {{ $current === $slug ? 'selected' : '' }}"
                                     data-slug="{{ $slug }}"
                                     onclick="selectTemplate(this)">
                                    <div class="doc-template-thumb">
                                        @if(!empty($tpl['thumbnail']))
                                            <img src="{{ $tpl['thumbnail'] }}" alt="{{ $tpl['label'] }}">
                                        @else
                                            <div class="doc-template-thumb-placeholder">
                                                <i class="bx bx-file-blank"></i>
                                                <span>{{ $tpl['label'] }}</span>
                                            </div>
                                        @endif
                                        <div class="doc-template-check"><i class="bx bx-check-circle"></i></div>
                                    </div>
                                    <div class="doc-template-info">
                                        <div class="doc-template-name">{{ $tpl['label'] }}</div>
                                        <div class="doc-template-desc">{{ $tpl['description'] ?? '' }}</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                        @else
                        <p class="text-muted mb-0 small">No templates configured for this document type.</p>
                        @endif
                    </div>
                </div>

                {{-- Sequence --}}
                <div class="card shadow-none bg-transparent border mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Document Sequence</h6>
                    </div>
                    <div class="card-body">
                        @if(!empty($sequence))
                        <p class="text-muted small mb-3">Sales Orders and Quotations share the same number sequence.</p>
                        <div class="row g-3 align-items-end">
                            <div class="col-auto">
                                <label class="form-label small fw-semibold mb-1">Prefix / Pattern</label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="text" class="form-control form-control-sm" id="seqPattern"
                                           value="{{ $sequence['pattern'] }}" maxlength="20" style="width:150px;"
                                           oninput="updateSeqPreview()">
                                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertSeqToken('{YY}')">{YY}</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertSeqToken('{YYYY}')">{YYYY}</button>
                                    <button type="button" class="btn btn-xs btn-outline-secondary" onclick="insertSeqToken('{MM}')">{MM}</button>
                                </div>
                            </div>
                            <div class="col-auto">
                                <label class="form-label small fw-semibold mb-1">Number Width</label>
                                <select class="form-select form-select-sm" id="seqPadding" style="width:110px;" onchange="updateSeqPreview()">
                                    @foreach([4,5,6,7,8,9] as $p)
                                    <option value="{{ $p }}" {{ $sequence['padding'] == $p ? 'selected' : '' }}>{{ $p }} digits</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-auto">
                                <label class="form-label small fw-semibold mb-1">Counter Reset</label>
                                <select class="form-select form-select-sm" id="seqResetPeriod" style="width:130px;">
                                    <option value="none"    {{ ($sequence['reset_period'] ?? 'none') === 'none'    ? 'selected' : '' }}>Never</option>
                                    <option value="yearly"  {{ ($sequence['reset_period'] ?? 'none') === 'yearly'  ? 'selected' : '' }}>Yearly</option>
                                    <option value="monthly" {{ ($sequence['reset_period'] ?? 'none') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                </select>
                            </div>
                            <div class="col-auto">
                                <label class="form-label small fw-semibold mb-1">Preview</label>
                                <div><code class="text-primary" id="seqPreview"></code></div>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>

                {{-- Content Sections --}}
                <div class="card shadow-none bg-transparent border mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Content Sections</h6>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small mb-3">Choose which sections appear on the PDF for this document type.</p>
                        <div class="d-flex flex-column gap-3">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showAmountInWords" name="show_amount_in_words" value="1">
                                <label class="form-check-label" for="showAmountInWords">
                                    <span class="fw-medium">Amount in Words</span>
                                    <span class="d-block text-muted small">Display the total amount spelled out in words on the PDF.</span>
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showSignature" name="show_signature" value="1">
                                <label class="form-check-label" for="showSignature">
                                    <span class="fw-medium">Signature Block</span>
                                    <span class="d-block text-muted small">Show a signature area at the bottom of the PDF.</span>
                                </label>
                            </div>
                            {{-- Jurisdiction toggle — commented out for now, enable when PDF support is ready
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="showJurisdiction" name="show_jurisdiction" value="1">
                                <label class="form-check-label" for="showJurisdiction">
                                    <span class="fw-medium">Jurisdiction</span>
                                    <span class="d-block text-muted small">Display the jurisdiction / governing law text (configured in General settings) at the bottom of the PDF.</span>
                                </label>
                            </div>
                            --}}
                        </div>
                    </div>
                </div>

                {{-- Terms & Conditions --}}
                <div class="card shadow-none bg-transparent border mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Default Terms &amp; Conditions</h6>
                    </div>
                    <div class="card-body">
                        <span class="d-block text-muted small mb-2">Prefilled on every new sales order. Printed at the bottom of the PDF.</span>
                        <textarea id="termsInput"></textarea>
                    </div>
                </div>

                {{-- Declaration --}}
                <div class="card shadow-none bg-transparent border mb-4">
                    <div class="card-header">
                        <h6 class="card-title mb-0">Declaration</h6>
                    </div>
                    <div class="card-body">
                        <span class="d-block text-muted small mb-2">Declaration text printed below the T&amp;C on the PDF.</span>
                        <textarea id="declarationInput"></textarea>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
'use strict';

const _docApiSlug = 'sales-order';
const _sequence   = @json($sequence ?? []);
let   _currentTpl = '{{ $current ?? '' }}';
const _editors    = {};

function selectTemplate(el) {
    document.querySelectorAll('.doc-template-card').forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    _currentTpl = el.dataset.slug;
}

const _now = new Date();
const _yy   = String(_now.getFullYear()).slice(-2);
const _yyyy = String(_now.getFullYear());
const _mm   = String(_now.getMonth() + 1).padStart(2, '0');

function updateSeqPreview() {
    const pattern = (document.getElementById('seqPattern')?.value || '');
    const padding = parseInt(document.getElementById('seqPadding')?.value || '7', 10);
    const formatted = pattern.replace(/\{YY\}/g, _yy).replace(/\{YYYY\}/g, _yyyy).replace(/\{MM\}/g, _mm);
    const el = document.getElementById('seqPreview');
    if (el) el.textContent = formatted + '1'.padStart(padding, '0');
}

function insertSeqToken(token) {
    const input = document.getElementById('seqPattern');
    if (!input) return;
    const s = input.selectionStart, e = input.selectionEnd;
    input.value = input.value.substring(0, s) + token + input.value.substring(e);
    input.selectionStart = input.selectionEnd = s + token.length;
    input.focus();
    updateSeqPreview();
}

function initEditor(key, selector) {
    if (!_editors[key]) {
        _editors[key] = Jodit.make(selector, {
            height: 260,
            buttons: 'bold,italic,underline,strikethrough,|,ul,ol,|,left,center,right,|,hr,|,undo,redo',
            toolbarAdaptive: false, showCharsCounter: false, showWordsCounter: false,
            showXPathInStatusbar: false, addNewLine: false,
            askBeforePasteHTML: false, defaultActionOnPaste: 'insert_clear_html',
        });
    }
}

function getEditorValue(key) {
    const html = _editors[key] ? _editors[key].value : '';
    return isHtmlEmpty(html) ? '' : html;
}

function setEditorValue(key, value) {
    if (_editors[key]) _editors[key].value = value || '';
}

document.addEventListener('DOMContentLoaded', async function() {
    initEditor('terms',       '#termsInput');
    initEditor('declaration', '#declarationInput');
    updateSeqPreview();

    try {
        const resp = await api.get('/company/settings/doc/' + _docApiSlug);
        const data = resp.data.data;

        if (data.pdf_template) {
            _currentTpl = data.pdf_template;
            document.querySelectorAll('.doc-template-card').forEach(c => {
                c.classList.toggle('selected', c.dataset.slug === data.pdf_template);
            });
        }

        if (data.sequence && data.sequence.pattern !== undefined) {
            const p = document.getElementById('seqPattern');
            if (p) p.value = data.sequence.pattern;
            const pd = document.getElementById('seqPadding');
            if (pd) pd.value = data.sequence.padding || 7;
            const rp = document.getElementById('seqResetPeriod');
            if (rp) rp.value = data.sequence.reset_period || 'none';
            updateSeqPreview();
        }

        const toggles = {show_amount_in_words: 'showAmountInWords', show_signature: 'showSignature'};
        // show_jurisdiction commented out — enable when PDF support is ready
        Object.entries(toggles).forEach(([key, id]) => {
            const el = document.getElementById(id);
            if (el && data[key] !== undefined) el.checked = !!data[key];
        });

        setEditorValue('terms',       data.terms);
        setEditorValue('declaration', data.declaration);
    } catch (err) {
        console.error('Failed to load settings', err);
    }
});

async function saveSettings() {
    const saveBtn = document.getElementById('saveBtn');
    setButtonLoading(saveBtn, true);
    try {
        await api.post('/company/settings/doc/' + _docApiSlug, {
            pdf_template:         _currentTpl,
            sequence: {
                sequence_key:  _sequence.sequence_key || 'sales_orders',
                pattern:       document.getElementById('seqPattern')?.value || '',
                padding:       parseInt(document.getElementById('seqPadding')?.value || '7', 10),
                reset_period:  document.getElementById('seqResetPeriod')?.value || 'none',
            },
            show_amount_in_words: document.getElementById('showAmountInWords').checked ? 1 : 0,
            show_signature:       document.getElementById('showSignature').checked ? 1 : 0,
            // show_jurisdiction commented out — enable when PDF support is ready
            terms:                getEditorValue('terms'),
            declaration:          getEditorValue('declaration'),
        });
        notyf.success('Sales Order settings saved.');
    } catch (err) {
        handleApiError(err);
    } finally {
        setButtonLoading(saveBtn, false);
    }
}
</script>
@endpush
