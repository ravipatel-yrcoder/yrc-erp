@extends('layouts.app')
@section('title', 'Purchasing Settings')

@section('content')
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @includeOnce('partial.app.settings-sidebar')

            <div class="col settings-content">

                <div class="d-flex justify-content-end mb-4">
                    <button type="button" class="btn btn-sm btn-primary" id="saveBtn" onclick="saveSettings()">Save Changes</button>
                </div>

                <form id="purchasingSettingsForm" novalidate>

                    {{-- Inquiries & Orders --}}
                    <div class="card shadow-none bg-transparent border mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Inquiries &amp; Orders</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="vendor_quote_comparison"
                                               id="vendorQuoteComparisonCheck" value="1"
                                               {{ $vendor_quote_comparison ? 'checked' : '' }}>
                                        <label class="form-check-label" for="vendorQuoteComparisonCheck">
                                            <span class="fw-medium">Vendor Quote Comparison</span>
                                            <p class="d-block text-muted small mt-1 mb-0">
                                                Enable to allow entering vendor prices on purchase inquiries and compare quotes side-by-side before awarding.
                                                When disabled, you can still send inquiries and award vendors without submitting prices.
                                            </p>
                                        </label>
                                    </div>
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

async function saveSettings() {
    const form    = document.getElementById('purchasingSettingsForm');
    const saveBtn = document.getElementById('saveBtn');

    cleanFormInputFeedback(form);

    const vendorQuoteComparison = document.getElementById('vendorQuoteComparisonCheck').checked ? 1 : 0;

    setButtonLoading(saveBtn, true);
    try {
        const res = await api.post('/company/settings/purchasing', {
            vendor_quote_comparison: vendorQuoteComparison,
        });
        notyf.success(res.data.message || 'Purchasing settings saved.');
        window.location.reload();
    } catch (err) {
        handleApiError(err, form);
        setButtonLoading(saveBtn, false);
    }
}
</script>
@endpush
