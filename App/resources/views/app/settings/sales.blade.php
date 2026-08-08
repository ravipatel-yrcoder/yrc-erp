@extends('layouts.app')
@section('title', 'Sales Settings')

@section('content')
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @includeOnce('app.settings.sidebar')

            <div class="col settings-content">

                <div class="d-flex justify-content-end mb-4">
                    <button type="button" class="btn btn-sm btn-primary" id="saveBtn" onclick="saveSettings()">Save Changes</button>
                </div>

                <form id="salesSettingsForm" novalidate>

                    {{-- Quotations --}}
                    <div class="card shadow-none bg-transparent border mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Quotations</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">

                                <div class="col-12">
                                    <label class="form-label fw-semibold mb-1">Default Validity Period</label>
                                    <div class="input-group input-group-sm" style="max-width: 200px;">
                                        <input type="number" class="form-control form-control-sm" id="quoteValidityDays" name="quote_validity_days"
                                               min="0" max="365" value="{{ $salesSettings['quote_validity_days'] }}" />
                                        <span class="input-group-text">days</span>
                                    </div>
                                    <span class="d-block text-muted small mt-1">Valid Until date is auto-calculated when creating a new quotation. Set to 0 to leave it blank.</span>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold mb-1">Default Terms &amp; Conditions</label>
                                    <span class="d-block text-muted small mb-2">Prefilled on every new quotation. Printed at the bottom of the quotation PDF.</span>
                                    <textarea id="quotationTermsInput">{{ $salesSettings['quotation_terms'] }}</textarea>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- Sales Orders --}}
                    <div class="card shadow-none bg-transparent border mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Sales Orders</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">

                                <div class="col-12">
                                    <label class="form-label fw-semibold mb-1">Default Terms &amp; Conditions</label>
                                    <span class="d-block text-muted small mb-2">Prefilled on every new sales order and applied when a quotation is converted to an order. Printed at the bottom of the sales order PDF.</span>
                                    <textarea id="soTermsInput">{{ $salesSettings['sales_order_terms'] }}</textarea>
                                </div>

                            </div>
                        </div>
                    </div>

                    {{-- Customers --}}
                    <div class="card shadow-none bg-transparent border mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Customers</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">

                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="customerGstRequired" name="customer_gst_required"
                                               value="1" {{ $salesSettings['customer_gst_required'] ? 'checked' : '' }} />
                                        <label class="form-check-label" for="customerGstRequired">
                                            <span class="fw-medium">Require GST Number</span>
                                            <span class="d-block text-muted small mt-1">When enabled, a GST number must be provided when creating or editing a customer. GST numbers are always checked for uniqueness within your company.</span>
                                        </label>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <label class="form-label fw-semibold mb-1">Customer Search Fields</label>
                                    <span class="d-block text-muted small mb-2">Fields used to search customers in quotation and sales order forms. Leave empty to search across all fields.</span>
                                    <select id="customerSearchBy" name="customer_search_by" class="select2 form-select" multiple style="max-width: 400px;">
                                        <option value="name"  {{ in_array('name',  $salesSettings['customer_search_by']) ? 'selected' : '' }}>Display Name</option>
                                        <option value="gstin" {{ in_array('gstin', $salesSettings['customer_search_by']) ? 'selected' : '' }}>GST Number</option>
                                        <option value="email" {{ in_array('email', $salesSettings['customer_search_by']) ? 'selected' : '' }}>Email</option>
                                        <option value="phone" {{ in_array('phone', $salesSettings['customer_search_by']) ? 'selected' : '' }}>Phone</option>
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

const _termsEditors = {};

function initTermsEditor(key, selector) {
    if (!_termsEditors[key]) {
        _termsEditors[key] = Jodit.make(selector, {
            height: 260,
            buttons: 'bold,italic,underline,strikethrough,|,ul,ol,|,left,center,right,|,hr,|,undo,redo',
            toolbarAdaptive: false,
            showCharsCounter: false,
            showWordsCounter: false,
            showXPathInStatusbar: false,
            addNewLine: false,
            askBeforePasteHTML: false,
            defaultActionOnPaste: 'insert_clear_html',
        });
    }
}

function getTermsValue(key, selector) {
    const html = _termsEditors[key] ? _termsEditors[key].value : document.querySelector(selector).value;
    return isHtmlEmpty(html) ? '' : html;
}

document.addEventListener('DOMContentLoaded', function() {
    initTermsEditor('quotation', '#quotationTermsInput');
    initTermsEditor('sales_order', '#soTermsInput');

    initSelect2('#customerSearchBy', {placeholder: 'Any field (searches all)'});
});

async function saveSettings() {

    const form    = document.getElementById('salesSettingsForm');
    const saveBtn = document.getElementById('saveBtn');

    cleanFormInputFeedback(form);

    const searchBy = jQuery('#customerSearchBy').val() || [];

    setButtonLoading(saveBtn, true);
    try {
        await api.post('/company/settings/sales', {
            quote_validity_days:   parseInt(document.getElementById('quoteValidityDays').value, 10) || 0,
            customer_gst_required: document.getElementById('customerGstRequired').checked ? 1 : 0,
            customer_search_by:    searchBy,
            quotation_terms:       getTermsValue('quotation', '#quotationTermsInput'),
            so_terms:              getTermsValue('sales_order', '#soTermsInput'),
        });
        notyf.success('Sales settings saved.');
        window.location.reload();
    } catch (err) {
        handleApiError(err, form);
        setButtonLoading(saveBtn, false);
    }
}
</script>
@endpush
