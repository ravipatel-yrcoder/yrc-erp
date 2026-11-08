@extends('layouts.app')
@section('title', 'Extra Charges')

@section('content')
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @includeOnce('app.settings.sidebar')

            <div class="col settings-content">

                <div class="d-flex justify-content-between align-items-center mb-4 gap-5">
                    <div>
                        <h5 class="mb-1">Extra Charges</h5>
                        <p class="text-muted mb-0" style="font-size:0.875rem;">
                            Define the additional charges available on Sales Orders, Purchase Orders and Proforma Invoices —
                            such as Freight, Packaging, and Insurance. Each charge can have a default tax rate and a
                            refund policy for credit notes.
                        </p>
                    </div>
                    <button class="btn btn-primary btn-sm text-nowrap flex-shrink-0" type="button" id="addExtraChargeBtn">
                        <i class="icon-base bx bx-plus icon-sm"></i> Add Charge
                    </button>
                </div>

                <div class="card shadow-none bg-transparent border">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Name</th>
                                        <th style="width:130px;">Applies To</th>
                                        <th>Default Tax</th>
                                        <th style="width:160px;">Refund Policy</th>
                                        <th style="width:95px;" class="text-center">Landed Cost</th>
                                        <th style="width:85px;" class="text-center">Status</th>
                                        <th style="width:100px;" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="extraChargesBody">
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Loading...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

{{-- Add / Edit Modal --}}
<div class="modal fade" id="extraChargeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-md">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="ecModalTitle">Add Extra Charge</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div id="ecErrorBox" class="form-glob-feedback mb-3"></div>
                <input type="hidden" id="ecId" value="0">

                <div class="mb-3">
                    <label class="form-label form-label-sm fw-semibold">Name <span class="text-danger">*</span></label>
                    <input type="text" id="ecName" name="name" class="form-control form-control-sm" maxlength="150" placeholder="e.g. Freight / Shipping">
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label form-label-sm fw-semibold">Applies To <span class="text-danger">*</span></label>
                        <select id="ecAppliesTo" name="applies_to" class="form-select form-select-sm">
                            <option value="both">Both (Sales &amp; Purchase)</option>
                            <option value="sales">Sales only</option>
                            <option value="purchase">Purchase only</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-sm fw-semibold">Refund Policy <span class="text-danger">*</span></label>
                        <select id="ecRefundPolicy" name="refund_policy" class="form-select form-select-sm">
                            <option value="manual">Manual (user decides per credit note)</option>
                            <option value="never">Never refunded</option>
                            <option value="pro_rata">Pro-rata by returned value</option>
                            <option value="full_only">Refunded on full return only</option>
                        </select>
                    </div>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-md-6">
                        <label class="form-label form-label-sm fw-semibold">Default Tax</label>
                        <select id="ecDefaultTax" name="default_tax_id" class="form-select form-select-sm">
                            <option value="">— None —</option>
                            @foreach($taxes as $tax)
                                <option value="{{ $tax->id }}">{{ $tax->name }} ({{ $tax->rate }}%)</option>
                            @endforeach
                        </select>
                        <div class="form-text">Pre-filled when this charge is added to a document.</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label form-label-sm fw-semibold">SAC Code</label>
                        <input type="text" id="ecSacCode" name="sac_code" class="form-control form-control-sm" maxlength="20" placeholder="e.g. 996812">
                        <div class="form-text">Service Accounting Code for GST (optional).</div>
                    </div>
                </div>

                <div class="mb-3">
                    <div class="form-check mb-2">
                        <input type="checkbox" id="ecLandedCost" class="form-check-input">
                        <label class="form-check-label" for="ecLandedCost">
                            <span class="fw-medium">Affects landed cost</span>
                            <span class="d-block text-muted" style="font-size:0.8rem;">
                                Included in landed cost distribution on Purchase Orders once that feature is activated.
                            </span>
                        </label>
                    </div>
                    <div class="form-check">
                        <input type="checkbox" id="ecIsActive" class="form-check-input" checked>
                        <label class="form-check-label" for="ecIsActive">Active</label>
                    </div>
                </div>

                <div class="mb-2">
                    <label class="form-label form-label-sm fw-semibold">Sort Order</label>
                    <input type="number" id="ecSortOrder" name="sort_order" class="form-control form-control-sm" value="0" min="0" style="max-width:100px;">
                    <div class="form-text">Lower numbers appear first in dropdowns.</div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-label-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-primary" id="ecSaveBtn" onclick="saveExtraCharge()">Save</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
(function () {

    const appliesToLabels = {
        both:     '<span class="badge bg-label-primary">Both</span>',
        sales:    '<span class="badge bg-label-success">Sales</span>',
        purchase: '<span class="badge bg-label-info">Purchase</span>',
    };

    const refundPolicyLabels = {
        never:     '<span class="badge bg-label-secondary">Never</span>',
        pro_rata:  '<span class="badge bg-label-warning">Pro-rata</span>',
        full_only: '<span class="badge bg-label-info">Full return only</span>',
        manual:    '<span class="badge bg-label-primary">Manual</span>',
    };

    function renderRow(ec) {
        const taxCell = ec.default_tax_name
            ? `${ec.default_tax_name} (${ec.default_tax_rate}%)`
            : '<span class="text-muted">—</span>';

        const landedCell = ec.affects_landed_cost == 1
            ? '<i class="bx bx-check-circle text-success fs-5"></i>'
            : '<span class="text-muted">—</span>';

        const statusCell = ec.is_active == 1
            ? '<span class="badge bg-label-success">Active</span>'
            : '<span class="badge bg-label-secondary">Inactive</span>';

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td><span class="fw-semibold">${ec.name}</span></td>
            <td>${appliesToLabels[ec.applies_to] ?? ec.applies_to}</td>
            <td>${taxCell}</td>
            <td>${refundPolicyLabels[ec.refund_policy] ?? ec.refund_policy}</td>
            <td class="text-center">${landedCell}</td>
            <td class="text-center">${statusCell}</td>
            <td class="text-center text-nowrap">
                <div class="d-inline-block">
                    <a href="javascript:void(0);" class="btn text-warning btn-icon item-edit edit-btn" title="Edit">
                        <i class="icon-base bx bxs-edit"></i>
                    </a>
                    <a href="javascript:void(0);" class="btn text-primary btn-icon dropdown-toggle hide-arrow" data-bs-toggle="dropdown">
                        <i class="icon-base bx bx-dots-vertical-rounded"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a href="javascript:void(0);" class="dropdown-item text-danger delete-btn">Delete</a></li>
                    </ul>
                </div>
            </td>`;
        tr.querySelector('.edit-btn').addEventListener('click', () => openEditModal(ec));
        tr.querySelector('.delete-btn').addEventListener('click', () => deleteExtraCharge(ec.id, ec.name));
        return tr;
    }

    async function loadExtraCharges() {
        try {
            const res  = await api.get('/settings/extra-charges', { params: { all: 1 } });
            const list = res.data.data ?? [];
            const tbody = document.getElementById('extraChargesBody');
            tbody.innerHTML = '';
            if (list.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted py-4">No charge types configured yet.</td></tr>';
                return;
            }
            list.forEach(ec => tbody.appendChild(renderRow(ec)));
        } catch (err) {
            handleApiError(err);
        }
    }

    const modalParent = () => jQuery('#extraChargeModal');

    function initModalSelects() {
        initSelect2('#ecAppliesTo',    { dropdownParent: modalParent(), allowClear: false, placeholder: 'Select' });
        initSelect2('#ecRefundPolicy', { dropdownParent: modalParent(), allowClear: false, placeholder: 'Select' });
        initSelect2('#ecDefaultTax',   { dropdownParent: modalParent(), placeholder: '— None —' });
    }

    function resetModal() {
        const modalEl = document.getElementById('extraChargeModal');
        cleanFormInputFeedback(modalEl);
        const errorBox = document.getElementById('ecErrorBox');
        errorBox.innerHTML = '';
        errorBox.classList.remove('has-feedback');

        document.getElementById('ecId').value           = '0';
        document.getElementById('ecName').value         = '';
        document.getElementById('ecSacCode').value      = '';
        document.getElementById('ecLandedCost').checked = false;
        document.getElementById('ecIsActive').checked   = true;
        document.getElementById('ecSortOrder').value    = '0';

        jQuery('#ecAppliesTo').val('both').trigger('change');
        jQuery('#ecRefundPolicy').val('manual').trigger('change');
        jQuery('#ecDefaultTax').val('').trigger('change');

        setButtonLoading(document.getElementById('ecSaveBtn'), false);
    }

    function openAddModal() {
        initModalSelects();
        resetModal();
        document.getElementById('ecModalTitle').textContent = 'Add Extra Charge';
        new bootstrap.Modal(document.getElementById('extraChargeModal')).show();
    }

    function openEditModal(ec) {
        initModalSelects();
        resetModal();
        document.getElementById('ecModalTitle').textContent   = 'Edit Extra Charge';
        document.getElementById('ecId').value                 = ec.id;
        document.getElementById('ecName').value               = ec.name;
        document.getElementById('ecSacCode').value            = ec.sac_code ?? '';
        document.getElementById('ecLandedCost').checked       = ec.affects_landed_cost == 1;
        document.getElementById('ecIsActive').checked         = ec.is_active == 1;
        document.getElementById('ecSortOrder').value          = ec.sort_order ?? 0;
        jQuery('#ecAppliesTo').val(ec.applies_to).trigger('change');
        jQuery('#ecRefundPolicy').val(ec.refund_policy).trigger('change');
        jQuery('#ecDefaultTax').val(ec.default_tax_id ?? '').trigger('change');
        new bootstrap.Modal(document.getElementById('extraChargeModal')).show();
    }

    window.saveExtraCharge = async function () {
        const id      = parseInt(document.getElementById('ecId').value, 10);
        const btn     = document.getElementById('ecSaveBtn');
        const modalEl = document.getElementById('extraChargeModal');
        cleanFormInputFeedback(modalEl);
        const errorBox = document.getElementById('ecErrorBox');
        errorBox.innerHTML = '';
        errorBox.classList.remove('has-feedback');

        const payload = {
            id,
            name:                 document.getElementById('ecName').value,
            applies_to:           document.getElementById('ecAppliesTo').value,
            refund_policy:        document.getElementById('ecRefundPolicy').value,
            default_tax_id:       document.getElementById('ecDefaultTax').value || null,
            sac_code:             document.getElementById('ecSacCode').value,
            affects_landed_cost:  document.getElementById('ecLandedCost').checked ? 1 : 0,
            is_active:            document.getElementById('ecIsActive').checked ? 1 : 0,
            sort_order:           parseInt(document.getElementById('ecSortOrder').value, 10) || 0,
        };

        setButtonLoading(btn, true);
        try {
            await api.post('/settings/extra-charges', payload);
            notyf.success(id > 0 ? 'Extra charge updated.' : 'Extra charge created.');
            bootstrap.Modal.getInstance(modalEl).hide();
            loadExtraCharges();
        } catch (err) {
            handleApiError(err, modalEl);
        } finally {
            setButtonLoading(btn, false);
        }
    };

    window.deleteExtraCharge = function (id, name) {
        showConfirmation(
            `Delete "${name}"?`,
            'warning',
            {
                text: 'Yes, delete',
                class: 'btn-danger',
                callback: async () => {
                    try {
                        await api.delete('/settings/extra-charges', { data: { id } });
                        notyf.success('Extra charge deleted.');
                        loadExtraCharges();
                    } catch (err) {
                        handleApiError(err);
                    }
                }
            },
            { text: 'Cancel' }
        );
    };

    document.addEventListener('DOMContentLoaded', () => {
        loadExtraCharges();
        document.getElementById('addExtraChargeBtn').addEventListener('click', openAddModal);
    });

}());
</script>
@endpush
