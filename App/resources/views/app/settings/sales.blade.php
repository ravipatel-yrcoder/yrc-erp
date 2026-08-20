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

                            </div>
                        </div>
                    </div>

                    {{-- Proforma Invoice --}}
                    <div class="card shadow-none bg-transparent border mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Proforma Invoice</h6>
                        </div>
                        <div class="card-body">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="proformaInvoiceEnabled" name="proforma_invoice"
                                       value="1" {{ $salesSettings['proforma_invoice'] ? 'checked' : '' }} />
                                <label class="form-check-label" for="proformaInvoiceEnabled">
                                    <span class="fw-medium">Enable Proforma Invoice</span>
                                    <span class="d-block text-muted small mt-1">When enabled, a Proforma Invoices tab appears on confirmed Sales Orders so you can issue proforma invoices for advance payment, export, or LC arrangements.</span>
                                </label>
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

document.addEventListener('DOMContentLoaded', function() {
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
            proforma_invoice:      document.getElementById('proformaInvoiceEnabled').checked ? 1 : 0,
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
