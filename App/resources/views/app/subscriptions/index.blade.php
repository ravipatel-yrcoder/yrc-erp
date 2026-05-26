@extends('layouts.app')
@section('title', 'Subscription')

@section('content')
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @includeOnce('partial.app.settings-sidebar')

            <div class="col">

    {{-- Skeleton shown while loading --}}
    <div id="subSkeleton">
        <div class="row g-4">
            @for($i = 0; $i < 4; $i++)
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card h-100">
                    <div class="card-body">
                        <div class="placeholder-glow">
                            <span class="placeholder col-6 mb-2 d-block"></span>
                            <span class="placeholder col-4 d-block"></span>
                        </div>
                    </div>
                </div>
            </div>
            @endfor
        </div>
    </div>    

    {{-- Actual content --}}
    <div id="subContent" class="d-none">

        {{-- Info cards row --}}
        <div class="row g-4 mb-6">

            {{-- Plan card --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card shadow-none bg-transparent border h-100">
                    <div class="card-body">
                        <p class="text-muted small text-uppercase mb-1" style="font-size:0.7rem;letter-spacing:.06em;">Plan</p>
                        <h5 class="mb-1" id="planName">—</h5>
                        <span class="badge" id="planStatus">—</span>
                        <div class="mt-2 small text-muted" id="trialInfo" style="display:none;"></div>
                    </div>
                </div>
            </div>

            {{-- Billing card --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card shadow-none bg-transparent border h-100">
                    <div class="card-body">
                        <p class="text-muted small text-uppercase mb-1" style="font-size:0.7rem;letter-spacing:.06em;">Billing</p>
                        <h5 class="mb-1" id="billingPrice">—</h5>
                        <div class="small text-muted" id="billingCycle">—</div>
                        <div class="small text-muted mt-1" id="billingRenews"></div>
                    </div>
                </div>
            </div>

            {{-- Seats card --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card shadow-none bg-transparent border h-100">
                    <div class="card-body">
                        <p class="text-muted small text-uppercase mb-1" style="font-size:0.7rem;letter-spacing:.06em;">Seats</p>
                        <h5 class="mb-1" id="seatsUsed">—</h5>
                        <div class="small text-muted" id="seatsTotal">—</div>
                        <div class="progress mt-2" style="height:4px;" id="seatsProgressWrap">
                            <div class="progress-bar" id="seatsProgressBar" role="progressbar" style="width:0%"></div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Active modules card --}}
            <div class="col-12 col-md-6 col-xl-3">
                <div class="card shadow-none bg-transparent border h-100">
                    <div class="card-body">
                        <p class="text-muted small text-uppercase mb-1" style="font-size:0.7rem;letter-spacing:.06em;">Active Modules</p>
                        <div id="activeModulesList" class="d-flex flex-wrap gap-2 mt-2"></div>
                    </div>
                </div>
            </div>
        </div>

        @if($subPlan->status !== 'pilot')

            {{-- Module switcher (One App only) --}}
            <div id="moduleSwitcherCard" class="card shadow-none bg-transparent border mb-6 d-none">
                <div class="card-header">
                    <h5 class="card-title mb-0">Switch Module</h5>
                    <p class="text-muted small mb-0 mt-1">Your One App plan allows one active module at a time. Select the module you want to use.</p>
                </div>
                <div class="card-body">
                    <div class="row g-3" id="allModulesList"></div>
                    <div class="mt-6">
                        <button type="button" class="btn btn-primary btn-sm" id="switchModuleBtn" disabled>Switch Module</button>
                    </div>
                </div>
            </div>

            {{-- Upgrade CTA (One App only) --}}
            <div id="upgradeCard" class="card shadow-none bg-transparent border  mb-6 d-none">
                <div class="card-body d-flex flex-column flex-md-row align-items-start align-items-md-center gap-3">
                    <div class="flex-grow-1">
                        <h5 class="mb-1">Upgrade to All Apps</h5>
                        <p class="text-muted mb-0">Get access to all modules — CRM, Sales, Inventory, Purchasing — under one plan.</p>
                    </div>
                    <button type="button" class="btn btn-primary btn-sm flex-shrink-0" id="upgradePlanBtn">
                        Upgrade Now
                    </button>
                </div>
            </div>

            {{-- Downgrade card (All Apps only) --}}
            <div id="downgradeCard" class="card shadow-none bg-transparent border  mb-6 d-none">
                <div class="card-header">
                    <h5 class="card-title mb-0">Downgrade to One App</h5>
                    <p class="text-muted small mb-0 mt-1">Select one module to keep. All other modules will be deactivated immediately.</p>
                </div>
                <div class="card-body">
                    <div class="alert alert-warning d-flex align-items-start gap-2 mb-6">
                        <i class="bx bx-error-alt fs-5 mt-1 flex-shrink-0"></i>
                        <div>You will <strong>immediately</strong> lose access to all other modules and their data views. This cannot be undone automatically — contact support to reverse.</div>
                    </div>
                    <div class="row g-3" id="downgradeModuleList"></div>
                    <div class="mt-6">
                        <button type="button" class="btn btn-warning btn-sm" id="downgradeBtn" disabled>Downgrade to One App</button>
                    </div>
                </div>
            </div>

            {{-- Cancel subscription --}}
            <div id="cancelSection" class="text-end mb-2 d-none">
                <button type="button" class="btn btn-sm btn-link text-danger px-0" id="cancelSubBtn">
                    Cancel Subscription
                </button>
            </div>

        @endif

    </div>

            </div>{{-- col --}}
        </div>{{-- row --}}
    </div>{{-- settings-page-content-wrapper --}}
</div>{{-- container --}}
@endsection

@push('scripts')
<script>
'use strict';

let _subData = null;
let _selectedModuleKey = null;

let _selectedDowngradeModuleKey = null;

document.addEventListener('DOMContentLoaded', function () {
    
    loadSummary();

    @if($subPlan->status !== 'pilot')

        document.getElementById('switchModuleBtn').addEventListener('click', switchModule);
        document.getElementById('upgradePlanBtn').addEventListener('click', upgradePlan);
        document.getElementById('downgradeBtn').addEventListener('click', downgradePlan);
        document.getElementById('cancelSubBtn').addEventListener('click', cancelSubscription);

    @endif    
});

async function loadSummary() {
    try {
        
        const res  = await api.get('/subscription/summary');
        _subData   = res.data.data;
        renderSummary(_subData);
        document.getElementById('subSkeleton').classList.add('d-none');
        document.getElementById('subContent').classList.remove('d-none');

    } catch (err) {
        handleApiError(err);
    }
}

function renderSummary(data) {

    // Plan card
    document.getElementById('planName').textContent = data.plan_name || '—';

    const statusEl  = document.getElementById('planStatus');
    const statusMap = {
        trial: ['bg-label-warning', 'Trial'],
        pilot: ['bg-label-info', 'Pilot'],
        active: ['bg-label-success', 'Active'],
        past_due: ['bg-label-danger', 'Past Due'],
        cancelled: ['bg-label-secondary','Cancelled'],
        suspended: ['bg-label-danger', 'Suspended'],
    };
    const [cls, label] = statusMap[data.status] || ['bg-label-secondary', data.status];
    statusEl.className = 'badge ' + cls;
    statusEl.textContent = label;

    if (data.status === 'trial' && data.trial_days_remaining !== null) {
        const el = document.getElementById('trialInfo');
        el.textContent = data.trial_days_remaining > 0 ? data.trial_days_remaining + ' day(s) remaining in trial' : 'Trial has expired';
        el.style.display = '';
    }

    // Billing card
    document.getElementById('billingPrice').textContent = data.agreed_base_price ? formatCurrency(data.agreed_base_price) : 'Free';
    document.getElementById('billingCycle').textContent = data.billing_cycle ? (data.billing_cycle.charAt(0).toUpperCase() + data.billing_cycle.slice(1)) : '—';
    if (data.current_period_end) {
        document.getElementById('billingRenews').textContent = 'Renews ' + formatMySqlDate(data.current_period_end);
    }

    // Seats card
    const seats = data.seats || {};
    document.getElementById('seatsUsed').textContent  = (seats.used_seats || 0) + ' used';
    document.getElementById('seatsTotal').textContent = 'of ' + (seats.total_seats || 0) + ' seats';
    const pct = seats.total_seats > 0 ? Math.min(100, Math.round((seats.used_seats / seats.total_seats) * 100)) : 0;
    const bar = document.getElementById('seatsProgressBar');
    bar.style.width = pct + '%';
    bar.className   = 'progress-bar' + (pct >= 90 ? ' bg-danger' : pct >= 70 ? ' bg-warning' : '');

    // Active modules
    const modListEl = document.getElementById('activeModulesList');
    modListEl.innerHTML = '';
    (data.active_modules || []).forEach(function (m) {
        const badge = document.createElement('span');
        badge.className   = 'badge bg-label-primary';
        badge.textContent = m.name;
        modListEl.appendChild(badge);
    });
    if (!data.active_modules || data.active_modules.length === 0) {
        modListEl.innerHTML = '<span class="text-muted small">None</span>';
    }

    @if($subPlan->status !== 'pilot')

    // One App extras
    if (data.plan_slug === 'one_app') {
        renderModuleSwitcher(data);
        document.getElementById('upgradeCard').classList.remove('d-none');
    }

    // All Apps extras
    if (data.plan_slug === 'all_apps') {
        renderDowngradeCard(data);
    }

    // Cancel — show for any active-ish status
    const cancellableStatuses = ['trial', 'pilot', 'active', 'past_due'];
    if (cancellableStatuses.includes(data.status)) {
        document.getElementById('cancelSection').classList.remove('d-none');
    }

    @endif
}

@if($subPlan->status !== 'pilot')

    function renderModuleSwitcher(data) {

        const card    = document.getElementById('moduleSwitcherCard');
        const listEl  = document.getElementById('allModulesList');

        card.classList.remove('d-none');
        listEl.innerHTML = '';

        const activeKey = (data.active_modules && data.active_modules[0]) ? data.active_modules[0].key : null;

        (data.all_modules || []).forEach(function (m) {
            const isActive = m.key === activeKey;

            const col = document.createElement('div');
            col.className = 'col-6 col-md-3';

            col.innerHTML = `
                <div class="module-option card border cursor-pointer p-3 text-center h-100 ${isActive ? 'border-primary' : ''}"
                    data-key="${m.key}" onclick="selectModule('${m.key}', this)">
                    <i class="${m.icon || 'bx bx-cube'} fs-3 mb-2 ${isActive ? 'text-primary' : 'text-muted'}"></i>
                    <div class="small fw-medium">${m.name}</div>
                    ${isActive ? '<div class="text-primary" style="font-size:0.65rem;">Active</div>' : ''}
                </div>`;

            listEl.appendChild(col);
        });
    }

    function selectModule(key, el) {
        const activeKey = (_subData.active_modules && _subData.active_modules[0])
            ? _subData.active_modules[0].key : null;

        if (key === activeKey) return;

        document.querySelectorAll('.module-option').forEach(function (c) {
            c.classList.remove('border-primary');
            c.querySelector('i').classList.remove('text-primary');
            c.querySelector('i').classList.add('text-muted');
        });

        el.classList.add('border-primary');
        el.querySelector('i').classList.remove('text-muted');
        el.querySelector('i').classList.add('text-primary');

        _selectedModuleKey = key;
        document.getElementById('switchModuleBtn').disabled = false;
    }

    async function switchModule() {
        if (!_selectedModuleKey) return;

        const activeKey = (_subData.active_modules && _subData.active_modules[0])
            ? _subData.active_modules[0].key : null;

        if (_selectedModuleKey === activeKey) {
            notyf.error('This module is already active.');
            return;
        }

        showConfirmation(
            'Switch to this module? Your current module will be deactivated.',
            'warning',
            {
                text: 'Switch',
                callback: async function () {
                    try {
                        await api.post('/subscription/module', { module_key: _selectedModuleKey });
                        notyf.success('Module switched successfully.');
                        _selectedModuleKey = null;
                        loadSummary();
                    } catch (err) {
                        handleApiError(err);
                    }
                }
            }
        );
    }

    async function upgradePlan() {
        showConfirmation(
            'Upgrade to All Apps plan? This will give you access to all modules.',
            'info',
            {
                text: 'Upgrade',
                callback: async function () {
                    try {
                        await api.post('/subscription/upgrade', {});
                        notyf.success('Plan upgraded successfully. Reloading…');
                        setTimeout(function () { window.location.reload(); }, 1200);
                    } catch (err) {
                        handleApiError(err);
                    }
                }
            }
        );
    }

    function renderDowngradeCard(data) {
        const card   = document.getElementById('downgradeCard');
        const listEl = document.getElementById('downgradeModuleList');

        card.classList.remove('d-none');
        listEl.innerHTML = '';
        _selectedDowngradeModuleKey = null;
        document.getElementById('downgradeBtn').disabled = true;

        (data.active_modules || []).forEach(function (m) {
            const col = document.createElement('div');
            col.className = 'col-6 col-md-3';
            col.innerHTML = `
                <div class="downgrade-option card border cursor-pointer p-3 text-center h-100"
                    data-key="${m.key}" onclick="selectDowngradeModule('${m.key}', this)">
                    <i class="${m.icon || 'bx bx-cube'} fs-3 mb-2 text-muted"></i>
                    <div class="small fw-medium">${m.name}</div>
                    <div class="text-muted" style="font-size:0.65rem;">Keep this module</div>
                </div>`;
            listEl.appendChild(col);
        });
    }

    function selectDowngradeModule(key, el) {
        document.querySelectorAll('.downgrade-option').forEach(function (c) {
            c.classList.remove('border-warning');
            c.querySelector('i').classList.remove('text-warning');
            c.querySelector('i').classList.add('text-muted');
        });
        el.classList.add('border-warning');
        el.querySelector('i').classList.remove('text-muted');
        el.querySelector('i').classList.add('text-warning');
        _selectedDowngradeModuleKey = key;
        document.getElementById('downgradeBtn').disabled = false;
    }

    async function downgradePlan() {
        if (!_selectedDowngradeModuleKey) return;

        showConfirmation(
            'Downgrade to One App? You will immediately lose access to all other modules.',
            'warning',
            {
                text: 'Downgrade',
                callback: async function () {
                    try {
                        await api.post('/subscription/downgrade', { module_key: _selectedDowngradeModuleKey });
                        notyf.success('Plan downgraded. Reloading…');
                        setTimeout(function () { window.location.reload(); }, 1200);
                    } catch (err) {
                        handleApiError(err);
                    }
                }
            }
        );
    }

    async function cancelSubscription() {
        showConfirmation(
            'Cancel your subscription? You will lose access to the application immediately.',
            'danger',
            {
                text: 'Cancel Subscription',
                callback: async function () {
                    try {
                        await api.post('/subscription/cancel', {});
                        notyf.success('Subscription cancelled.');
                        setTimeout(function () { window.location.href = '/subscription/expired'; }, 1200);
                    } catch (err) {
                        handleApiError(err);
                    }
                }
            }
        );
    }
@endif
</script>
@endpush
