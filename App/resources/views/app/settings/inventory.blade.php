@extends('layouts.app')
@section('title', 'Inventory Settings')

@section('content')
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @includeOnce('app.settings.sidebar')

            <div class="col settings-content">

                <div class="d-flex justify-content-end mb-4">
                    <button type="button" class="btn btn-sm btn-primary" id="saveBtn" onclick="saveSettings()">Save Changes</button>
                </div>

                <form id="invSettingsForm" novalidate>

                    {{-- Costing --}}
                    <div class="card shadow-none bg-transparent border mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Inventory Costing</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-12">
                                    <label class="form-label fw-semibold mb-2">Cost Valuation Method</label>
                                    <div class="d-flex flex-column gap-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="cost_method" id="cost_method_standard" value="standard" {{ $cost_method === 'standard' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="cost_method_standard">
                                                <span class="fw-medium">Standard Price</span>
                                                <span class="d-block text-muted small mt-1">The product cost is fixed and set manually on the product form. Stock movements do not change the cost. Best for products with a stable, agreed purchase price.</span>
                                            </label>
                                        </div>
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" name="cost_method" id="cost_method_avco" value="avco" {{ $cost_method === 'avco' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="cost_method_avco">
                                                <span class="fw-medium">Average Cost (AVCO)</span>
                                                <span class="d-block text-muted small mt-1">The product cost is recalculated automatically as a weighted average each time stock is received. Best for commodity products where purchase price varies.</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="form-glob-feedback mt-2"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Warehouses --}}
                    <div class="card shadow-none bg-transparent border mb-4">
                        <div class="card-header">
                            <h6 class="card-title mb-0">Warehouses</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-4">
                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="multi_warehouse"
                                               id="multiWarehouseCheck" value="1"
                                               {{ $multi_warehouse ? 'checked' : '' }}>
                                        <label class="form-check-label" for="multiWarehouseCheck">
                                            <span class="fw-medium">Multi-Warehouse</span>
                                            <p class="d-block text-muted small mt-1 mb-0">
                                                Enable to track stock across multiple warehouses. A warehouse selector will
                                                appear on orders, deliveries, receipts, and stock adjustments.<br>
                                                <p class="mt-3 mb-0"><strong>Note:</strong> to disable, deactivate all warehouses except the default one first.</p>
                                            </p>
                                        </label>
                                    </div>
                                    @if($multi_warehouse)
                                    <div class="mt-3">
                                        <a href="/inv/warehouses/" class="d-flex align-items-center">
                                            <i class="bx bx-right-arrow-alt me-1"></i>Manage Warehouses
                                        </a>
                                    </div>
                                    @endif
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
    const form   = document.getElementById('invSettingsForm');
    const saveBtn = document.getElementById('saveBtn');

    cleanFormInputFeedback(form);

    const selected = form.querySelector('[name="cost_method"]:checked');
    if (!selected) {
        showFormGlobalFeedback(form, 'Please select a cost method.');
        return;
    }

    const multiWh = document.getElementById('multiWarehouseCheck').checked ? 1 : 0;

    setButtonLoading(saveBtn, true);
    try {
        const res = await api.post('/company/settings/inventory', {
            cost_method: selected.value,
            multi_warehouse: multiWh,
        });
        notyf.success(res.data.message || 'Inventory settings saved.');
        window.location.reload();
    } catch (err) {
        handleApiError(err, form);
        setButtonLoading(saveBtn, false);
    }
}
</script>
@endpush
