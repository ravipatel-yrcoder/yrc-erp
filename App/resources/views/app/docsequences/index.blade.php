@extends('layouts.app')
@section('title', 'Document Sequences')

@section('content')
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @includeOnce('partial.app.settings-sidebar')

            <div class="col settings-content">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-1">Document Sequences</h5>
                        <p class="text-muted mb-0" style="font-size:0.875rem;">Configure the numbering prefix and counter width for each document type. Changes apply to all new documents company-wide.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" id="saveBtn" onclick="saveSequences()">Save Changes</button>
                </div>

                <div id="seqErrorBox" class="form-glob-feedback mb-3"></div>

                <div class="card shadow-none bg-transparent border">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="sequencesTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width:170px;">Document Type</th>
                                        <th>Prefix / Pattern</th>
                                        <th style="width:120px;">Number Width</th>
                                        <th style="width:150px;">Counter Reset</th>
                                        <th style="width:190px;">Preview</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sequences as $seq)
                                    <tr data-key="{{ $seq['sequence_key'] }}">
                                        <td class="fw-semibold" style="font-size:0.875rem;">{{ $seq['label'] }}</td>
                                        <td>
                                            <div class="d-flex align-items-center gap-2">
                                                <input type="text"
                                                       class="form-control form-control-sm pattern-input"
                                                       value="{{ $seq['pattern'] }}"
                                                       maxlength="20"
                                                       placeholder="e.g. SO or SO-{YYYY}"
                                                       style="width:150px;"
                                                       oninput="updatePreview(this.closest('tr'))">
                                                <button type="button" class="btn btn-xs btn-outline-secondary token-btn" onclick="insertToken(this, '{YY}')">{YY}</button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary token-btn" onclick="insertToken(this, '{YYYY}')">{YYYY}</button>
                                                <button type="button" class="btn btn-xs btn-outline-secondary token-btn" onclick="insertToken(this, '{MM}')">{MM}</button>
                                            </div>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm padding-select" style="width:110px;" onchange="updatePreview(this.closest('tr'))">
                                                @foreach([4,5,6,7,8,9] as $p)
                                                <option value="{{ $p }}" {{ $seq['padding'] == $p ? 'selected' : '' }}>{{ $p }} digits</option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select class="form-select form-select-sm reset-period-select" style="width:130px;" onchange="clearSequenceErrors()">
                                                <option value="none"    {{ ($seq['reset_period'] ?? 'none') === 'none'    ? 'selected' : '' }}>Never</option>
                                                <option value="yearly"  {{ ($seq['reset_period'] ?? 'none') === 'yearly'  ? 'selected' : '' }}>Yearly</option>
                                                <option value="monthly" {{ ($seq['reset_period'] ?? 'none') === 'monthly' ? 'selected' : '' }}>Monthly</option>
                                            </select>
                                        </td>
                                        <td>
                                            <code class="preview-text text-primary" style="font-size:0.8rem;"></code>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <p class="text-muted mt-3" style="font-size:0.8rem;">
                    <i class="bx bx-info-circle me-1"></i>
                    Supported pattern tokens: <code>{YY}</code> = 2-digit year &nbsp;|&nbsp; <code>{YYYY}</code> = 4-digit year &nbsp;|&nbsp; <code>{MM}</code> = 2-digit month.
                    The counter is always appended at the end.
                    Yearly reset requires <code>{YYYY}</code> or <code>{YY}</code> in the pattern; monthly reset also requires <code>{MM}</code> — this ensures numbers stay unique across periods.
                </p>

            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.btn-xs {
    padding: 2px 7px;
    font-size: 0.72rem;
    line-height: 1.5;
    border-radius: 4px;
    font-family: monospace;
    white-space: nowrap;
}
</style>
@endpush

@push('scripts')
<script>
const now = new Date();
const previewYY   = String(now.getFullYear()).slice(-2);
const previewYYYY = String(now.getFullYear());
const previewMM   = String(now.getMonth() + 1).padStart(2, '0');

function buildPreview(pattern, padding) {
    let formatted = pattern
        .replace(/\{YY\}/g,   previewYY)
        .replace(/\{YYYY\}/g, previewYYYY)
        .replace(/\{MM\}/g,   previewMM);
    return formatted + '1'.padStart(padding, '0');
}

function updatePreview(row) {
    const pattern = row.querySelector('.pattern-input').value;
    const padding = parseInt(row.querySelector('.padding-select').value, 10);
    row.querySelector('.preview-text').textContent = buildPreview(pattern, padding);
}

function insertToken(btn, token) {
    const row   = btn.closest('tr');
    const input = row.querySelector('.pattern-input');
    const start = input.selectionStart;
    const end   = input.selectionEnd;
    input.value = input.value.substring(0, start) + token + input.value.substring(end);
    input.selectionStart = input.selectionEnd = start + token.length;
    input.focus();
    updatePreview(row);
}

// Initialise all previews on load
document.querySelectorAll('#sequencesTable tbody tr').forEach(row => updatePreview(row));

function clearSequenceErrors() {
    const box = document.getElementById('seqErrorBox');
    box.classList.remove('has-feedback');
    box.innerHTML = '';
}

function showSequenceErrors(errors) {
    const messages = Object.values(errors);
    if (!messages.length) return;

    const box = document.getElementById('seqErrorBox');
    box.innerHTML = messages.map(m => `<div class="invalid-feedback">${m}</div>`).join('');
    box.classList.add('has-feedback');
    box.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
}

async function saveSequences() {
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';
    clearSequenceErrors();

    const sequences = [];
    document.querySelectorAll('#sequencesTable tbody tr').forEach(row => {
        sequences.push({
            sequence_key:  row.dataset.key,
            pattern:       row.querySelector('.pattern-input').value,
            padding:       parseInt(row.querySelector('.padding-select').value, 10),
            reset_period:  row.querySelector('.reset-period-select').value,
        });
    });

    try {
        const response = await api.post('/company/settings/doc-sequences', { sequences });
        notyf.success(response.data.message || 'Document sequences saved.');
    } catch (err) {
        if (err.response?.data?.errors && Object.keys(err.response.data.errors).length) {
            showSequenceErrors(err.response.data.errors);
            notyf.error(err.response.data.message || 'Validation failed.');
        } else {
            handleApiError(err);
        }
    } finally {
        btn.disabled = false;
        btn.textContent = 'Save Changes';
    }
}
</script>
@endpush
