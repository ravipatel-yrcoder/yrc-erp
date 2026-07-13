@extends('layouts.app')
@section('title', 'Document Templates')

@section('content')
<style>
.doc-template-card {
    border: 2px solid #e5e7eb;
    border-radius: 8px;
    cursor: pointer;
    transition: border-color 0.15s, box-shadow 0.15s;
    overflow: hidden;
    background: #fff;
}
.doc-template-card:hover {
    border-color: #a5b4fc;    
}
.doc-template-card.selected {
    border-color: #6487E7;    
}
.doc-template-thumb {
    position: relative;
    background: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    height: 200px;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
}
.doc-template-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
}
.doc-template-thumb-placeholder {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    color: #9ca3af;
    gap: 8px;
}
.doc-template-thumb-placeholder i {
    font-size: 3rem;
    color: #d1d5db;
}
.doc-template-thumb-placeholder span {
    font-size: 0.8rem;
    font-weight: 600;
    letter-spacing: 0.5px;
    text-transform: uppercase;
}
.doc-template-check {
    position: absolute;
    top: 8px;
    right: 8px;
    font-size: 1.4rem;
    color: #6487E7;
    display: none;
}
.doc-template-card.selected .doc-template-check {
    display: block;
}
.doc-template-info {
    padding: 12px 14px;
}
.doc-template-name {
    font-weight: 600;
    font-size: 0.9rem;
    color: #111827;
    margin-bottom: 4px;
}
.doc-template-desc {
    font-size: 0.8rem;
    color: #6b7280;
    line-height: 1.5;
}
</style>
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @includeOnce('partial.app.settings-sidebar')

            <div class="col settings-content">

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h5 class="mb-1">Document Templates</h5>
                        <p class="text-muted mb-0" style="font-size:0.875rem;">Choose the PDF layout for each document type. The selected template applies to all downloads company-wide.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-primary" id="saveBtn" onclick="saveTemplates()">Save Changes</button>
                </div>

                <ul class="nav nav-tabs mb-4" id="docTemplateTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tabQuotation" type="button">SO - Quote</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabSo" type="button">Sales Order</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabRfq" type="button">PO - Request For Quote</button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" data-bs-toggle="tab" data-bs-target="#tabPo" type="button">Purchase Order</button>
                    </li>
                </ul>

                <div class="tab-content card shadow-none bg-transparent border mb-5">

                    @foreach([
                        ['tabQuotation', 'quotation',      $registry['quotation']      ?? []],
                        ['tabSo',        'sales_order',    $registry['sales_order']    ?? []],
                        ['tabRfq',       'rfq',            $registry['rfq']            ?? []],
                        ['tabPo',        'purchase_order', $registry['purchase_order'] ?? []],
                    ] as [$tabId, $docTypeKey, $templates])
                    @php $selectedKey = $current[$docTypeKey] ?? 'template_1'; @endphp

                    <div class="tab-pane fade {{ $tabId === 'tabQuotation' ? 'show active' : '' }}" id="{{ $tabId }}" role="tabpanel">
                        <div class="row g-4">
                            @foreach($templates as $slug => $tpl)
                            <div class="col-12 col-md-4">
                                <div class="doc-template-card {{ $selectedKey === $slug ? 'selected' : '' }}"
                                     data-doc-type="{{ $docTypeKey }}"
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
                                        <div class="doc-template-desc">{{ $tpl['description'] }}</div>
                                    </div>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>

                    @endforeach

                </div>

            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
const currentSelections = {
    sales_order:    '{{ $current['sales_order'] }}',
    quotation:      '{{ $current['quotation'] }}',
    purchase_order: '{{ $current['purchase_order'] }}',
    rfq:            '{{ $current['rfq'] }}',
};

function selectTemplate(el) {
    const docType = el.dataset.docType;
    document.querySelectorAll(`.doc-template-card[data-doc-type="${docType}"]`).forEach(c => c.classList.remove('selected'));
    el.classList.add('selected');
    currentSelections[docType] = el.dataset.slug;
}

async function saveTemplates() {
    const btn = document.getElementById('saveBtn');
    btn.disabled = true;
    btn.textContent = 'Saving...';

    try {
        const response = await api.post('/company/settings/doc-templates', {
            so_pdf_template:        currentSelections.sales_order,
            quotation_pdf_template: currentSelections.quotation,
            po_pdf_template:        currentSelections.purchase_order,
            rfq_pdf_template:       currentSelections.rfq,
        });
        notyf.success(response.data.message || 'Template preferences saved.');
    } catch (err) {
        notyf.error('Failed to save preferences. Please try again.');
    } finally {
        btn.disabled = false;
        btn.textContent = 'Save Changes';
    }
}
</script>
@endpush
