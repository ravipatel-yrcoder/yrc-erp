@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
@php
    $ctx         = tenantContext();
    $user        = auth()->user();
    $isAdminView = $ctx->isCompanyUser || $ctx->isAdminRole;

    // Operator: per-feature access flags passed to JS via data attributes
    $hasSalesOrders      = $ctx->hasRoleModule('sales')          && $ctx->canAccess('sales_orders');
    $hasSalesDelivery    = $ctx->hasRoleModule('sales')          && $ctx->canAccess('sales_deliveries');
    $hasPurchaseOrders   = $ctx->hasRoleModule('purchasing')      && $ctx->canAccess('purchase_orders');
    $hasPurchaseReceipts = $ctx->hasRoleModule('purchasing')      && $ctx->canAccess('purchase_receipts');
    $hasCrmLeads         = $ctx->hasRoleModule('crm')            && $ctx->canAccess('crm_leads');
    $hasActivities       = $ctx->canAccess('activities');
    $hasManufacturing    = $ctx->hasRoleModule('manufacturing')   && $ctx->canAccess('manufacturing_orders');

    // Admin: module card flags
    $hasSales     = $ctx->hasRoleModule('sales')     && $ctx->canAccess('sales_orders');
    $hasCrm       = $ctx->hasRoleModule('crm')       && $ctx->canAccess('crm_leads');
    $hasPurchase  = $ctx->hasRoleModule('purchasing') && $ctx->canAccess('purchase_orders');
    $hasInventory = $ctx->hasRoleModule('inventory')  && $ctx->canAccess('inventory_adjustments');
    $hasCustomers = $ctx->canAccess('customers');
    // $hasManufacturing already set above (shared with operator flags)

@endphp

<style>
.kpi-avatar { width: 25px !important; height: 25px !important; }
.kpi-avatar .avatar-initial { font-size: 20px; }
.dash-pulse {
    display: inline-block;
    width: 8px; height: 8px;
    border-radius: 50%;
    background: #dc3545;
    animation: dash-pulse-anim 1.5s ease-in-out infinite;
    flex-shrink: 0;
}
@keyframes dash-pulse-anim {
    0%, 100% { opacity: 1; transform: scale(1); }
    50%       { opacity: 0.4; transform: scale(0.8); }
}
.dash-work-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.65rem 1.25rem;
    border-bottom: 1px solid var(--bs-border-color);
    text-decoration: none;
    color: inherit;
    transition: background 0.15s;
}
.dash-work-row:last-child { border-bottom: none; }
.dash-work-row:hover { background: rgba(0,0,0,.03); }
.dash-section-header {
    padding: 0.5rem 1.25rem;
    font-size: 0.7rem;
    font-weight: 700;
    letter-spacing: 0.06em;
    text-transform: uppercase;
    border-bottom: 1px solid var(--bs-border-color);
    display: flex;
    align-items: center;
    gap: 0.5rem;
}
.dash-section-overdue {
    background: rgba(220, 53, 69, 0.07);
    color: #dc3545;
}
.dash-section-today { color: #0d6efd; }
.dash-section-pending { color: #6c757d; }
.dash-perf-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 0.7rem 1.25rem;
    border-bottom: 1px solid var(--bs-border-color);
}
.dash-perf-row:last-child { border-bottom: none; }
.dash-perf-val {
    font-size: 1.1rem;
    font-weight: 700;
    min-width: 2.5rem;
    text-align: right;
}
.overdue-bg { background: rgba(220, 53, 69, 0.04); }
</style>

<div class="container-fluid py-4">

    {{-- Header --}}
    <div class="d-flex align-items-center mb-4">
        <div>
            <h4 class="fw-bold mb-1">{{ $greeting }}, {{ $user->name }}</h4>
            <p class="text-muted mb-0 small">Today is {{ date('l, j M Y') }}</p>
        </div>
    </div>

    {{-- Loading --}}
    <div id="dashboard-loading" class="d-flex justify-content-center py-5">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading…</span>
        </div>
    </div>

    <div id="dashboard-content" class="d-none">

        @if($isAdminView)
        {{-- ═══════════════════════════════════════════════════════════════
             ADMIN VIEW — module KPI cards + chart + business alerts
        ════════════════════════════════════════════════════════════════ --}}
        @php $hasAnyCard = $hasCrm || $hasSales || $hasPurchase || $hasCustomers || $hasManufacturing; @endphp

        @if($hasAnyCard)
        {{-- Date range filter --}}
        <div class="d-flex align-items-center gap-2 flex-wrap mb-4">
            <div class="btn-group btn-group-sm">
                <button type="button" class="btn btn-outline-primary dash-preset" data-preset="today">Today</button>
                <button type="button" class="btn btn-outline-primary dash-preset" data-preset="this_month">This Month</button>
                <button type="button" class="btn btn-outline-primary dash-preset" data-preset="last_month">Last Month</button>
                <button type="button" class="btn btn-primary dash-preset active" data-preset="this_year">This Year</button>
                <button type="button" class="btn btn-outline-primary dash-preset" data-preset="custom">Custom</button>
            </div>
            <div id="dash-custom-range-wrap" style="display:none;">
                <input type="text" id="dash-custom-range" class="form-control form-control-sm w-px-250" placeholder="Pick date range">
            </div>
        </div>

        {{-- Outer row: left (8) = KPI cards + chart | right (4) = Business Alerts --}}
        <div class="row g-4">

            {{-- Left column --}}
            <div class="col-12 col-lg-9">

                {{-- KPI cards --}}
                <div class="row g-3 mb-4">

                    @if($hasCrm)
                    <div class="col-12 col-md-6 col-lg">
                        <div class="card h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-2 mb-4">
                                    <div class="avatar kpi-avatar flex-shrink-0">
                                        <span class="avatar-initial rounded-circle bg-label-success"><i class="bx bx-stats"></i></span>
                                    </div>
                                    <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;">CRM</span>
                                    <a href="/crm/pipeline/" class="ms-auto small fw-semibold text-success text-decoration-none">View →</a>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold text-success" id="dash-crm-won-revenue">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Won Revenue</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold" id="dash-crm-pipeline">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Pipeline Value</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold" id="dash-crm-conversion">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Conversion Rate</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold" id="dash-crm-active-leads">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Active Leads (All Time)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($hasSales)
                    <div class="col-12 col-md-6 col-lg">
                        <div class="card h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-2 mb-4">
                                    <div class="avatar kpi-avatar flex-shrink-0">
                                        <span class="avatar-initial rounded-circle bg-label-warning"><i class="bx bx-cart"></i></span>
                                    </div>
                                    <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;">Sales</span>
                                    <a href="/sales/orders/" class="ms-auto small fw-semibold text-warning text-decoration-none">View →</a>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold text-warning" id="dash-sales-revenue">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Sales</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold text-danger" id="dash-sales-returns">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Returns</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold" id="dash-sales-quotations">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Quotation Pipeline</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold" id="dash-sales-avg-order">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Avg. Order Value</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold" id="dash-sales-total-orders">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Total Orders</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($hasManufacturing)
                    <div class="col-12 col-md-6 col-lg">
                        <div class="card h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-2 mb-4">
                                    <div class="avatar kpi-avatar flex-shrink-0">
                                        <span class="avatar-initial rounded-circle bg-label-info"><i class="bx bx-cog"></i></span>
                                    </div>
                                    <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;">Manufacturing</span>
                                    <a href="/manufacturing/orders/" class="ms-auto small fw-semibold text-info text-decoration-none">View →</a>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold" id="dash-mfg-open">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Open MOs (All Time)</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold text-success" id="dash-mfg-completed">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Completed MOs</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold text-danger" id="dash-mfg-overdue">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Overdue MOs (All Time)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($hasPurchase)
                    <div class="col-12 col-md-6 col-lg">
                        <div class="card h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-2 mb-4">
                                    <div class="avatar kpi-avatar flex-shrink-0">
                                        <span class="avatar-initial rounded-circle bg-label-danger"><i class="bx bx-package"></i></span>
                                    </div>
                                    <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;">Purchasing</span>
                                    <a href="/purchase/orders/" class="ms-auto small fw-semibold text-danger text-decoration-none">View →</a>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold" id="dash-po-open">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Open POs (All Time)</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold text-danger" id="dash-po-overdue">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Overdue Receipts (All Time)</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    @if($hasCustomers)
                    <div class="col-12 col-md-6 col-lg">
                        <div class="card h-100">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center gap-2 mb-4">
                                    <div class="avatar kpi-avatar flex-shrink-0">
                                        <span class="avatar-initial rounded-circle bg-label-primary"><i class="bx bx-group"></i></span>
                                    </div>
                                    <span class="fw-semibold text-muted small text-uppercase" style="letter-spacing:.04em;">Customers</span>
                                    <a href="/customers/" class="ms-auto small fw-semibold text-primary text-decoration-none">View →</a>
                                </div>
                                <div class="d-flex flex-column gap-2">
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold" id="dash-cust-active">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">Active Customers (All Time)</span>
                                    </div>
                                    <div class="d-flex justify-content-between">
                                        <span class="fw-bold text-primary" id="dash-cust-new">—</span>
                                        <span class="text-muted" style="font-size:0.72rem;">New Customers</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                </div>{{-- /KPI cards row --}}

                {{-- Sales by Month chart --}}
                @if($hasSales)
                <div class="card">
                    <div class="card-header d-flex align-items-center justify-content-between border-bottom py-3">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-bar-chart-alt-2 text-warning me-2"></i>
                            <h6 class="mb-0 fw-semibold">Sales by Month</h6>
                        </div>
                        <select id="chart-year-select" class="form-select form-select-sm w-auto">
                            @foreach($chartYears as $yr)
                            <option value="{{ $yr }}" {{ $yr == date('Y') ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="card-body">
                        <div id="sales-month-chart"></div>
                    </div>
                </div>
                @endif

            </div>{{-- /left column --}}

            {{-- Right column: Business Alerts --}}
            <div class="col-12 col-lg-3">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center border-bottom py-3">
                        <i class="bx bx-bell text-primary me-2"></i>
                        <h6 class="mb-0 fw-semibold">Business Alerts</h6>
                    </div>
                    <div class="card-body p-0">

                        {{-- Revenue this month --}}
                        @if($hasSales)
                        <div class="px-3 py-3 border-bottom">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-muted small mb-1">Revenue This Month</div>
                                    <div class="fw-bold fs-5" id="dash-alert-revenue">—</div>
                                </div>
                                <div id="dash-alert-revenue-trend"></div>
                            </div>
                        </div>
                        @endif

                        {{-- Alert rows --}}
                        @if($hasSalesDelivery)
                        <a href="/sales/orders/?delivery=overdue" class="dash-work-row text-decoration-none text-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-cart text-danger" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Orders Overdue for Delivery</span>
                            </div>
                            <span class="badge bg-danger rounded-pill" id="dash-alert-delivery">—</span>
                        </a>
                        @endif

                        @if($hasSalesOrders)
                        <a href="/sales/orders/?status=pending_dispatch" class="dash-work-row text-decoration-none text-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-package text-info" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Orders Pending Dispatch</span>
                            </div>
                            <span class="badge bg-info rounded-pill" id="dash-alert-dispatch">—</span>
                        </a>
                        <a href="/sales/quotations/?expiry=expired" class="dash-work-row text-decoration-none text-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-x-circle text-danger" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Expired Quotations</span>
                            </div>
                            <span class="badge bg-danger rounded-pill" id="dash-alert-expired-quotations">—</span>
                        </a>
                        <a href="/sales/quotations/?expiry=soon" class="dash-work-row text-decoration-none text-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-time-five text-warning" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Quotations Expiring (7 days)</span>
                            </div>
                            <span class="badge bg-warning rounded-pill" id="dash-alert-expiring-quotations">—</span>
                        </a>
                        @endif

                        @if($hasPurchaseOrders)
                        <a href="/purchase/orders/" class="dash-work-row text-decoration-none text-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-box text-warning" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Open Purchase Orders</span>
                            </div>
                            <span class="badge bg-warning rounded-pill" id="dash-alert-open-pos">—</span>
                        </a>
                        <a href="/purchase/receipts/" class="dash-work-row text-decoration-none text-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-import text-primary" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Pending PO Receipts</span>
                            </div>
                            <span class="badge bg-primary rounded-pill" id="dash-alert-pending-receipts">—</span>
                        </a>
                        @endif

                        @if($hasManufacturing)
                        <a href="/manufacturing/orders/" class="dash-work-row text-decoration-none text-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-cog text-info" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Open Manufacturing Orders</span>
                            </div>
                            <span class="badge bg-info rounded-pill" id="dash-alert-open-mos">—</span>
                        </a>
                        <a href="/manufacturing/orders/" class="dash-work-row text-decoration-none text-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-error text-danger" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Overdue Manufacturing Orders</span>
                            </div>
                            <span class="badge bg-danger rounded-pill" id="dash-alert-overdue-mos">—</span>
                        </a>
                        @endif

                        <a href="{{ $hasActivities ? '/activities/?status=pending' : 'javascript:void(0);' }}" class="dash-work-row text-decoration-none text-body">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-task text-secondary" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Pending Activities</span>
                            </div>
                            <span class="badge bg-secondary rounded-pill" id="dash-alert-activities">—</span>
                        </a>

                    </div>
                </div>
            </div>{{-- /right column --}}

        </div>{{-- /outer row --}}

        {{-- Second row: Top Customers + Leads by Month --}}
        @if($hasCrm || $hasCustomers)
        <div class="row g-4 mt-0">

            @if($hasCustomers)
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center border-bottom py-3">
                        <i class="bx bx-trophy text-primary me-2"></i>
                        <h6 class="mb-0 fw-semibold">Top 10 Customers by Revenue</h6>
                        <span class="ms-auto badge bg-label-secondary small">All Time</span>
                    </div>
                    <div class="card-body">
                        <div id="top-customers-chart"></div>
                    </div>
                </div>
            </div>
            @endif

            @if($hasCrm)
            <div class="col-12 col-lg-6">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center justify-content-between border-bottom py-3">
                        <div class="d-flex align-items-center">
                            <i class="bx bx-stats text-success me-2"></i>
                            <h6 class="mb-0 fw-semibold">Leads by Month</h6>
                        </div>
                        <select id="leads-chart-year-select" class="form-select form-select-sm w-auto">
                            @foreach($chartYears as $yr)
                            <option value="{{ $yr }}" {{ $yr == date('Y') ? 'selected' : '' }}>{{ $yr }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="card-body">
                        <div id="leads-month-chart"></div>
                    </div>
                </div>
            </div>
            @endif

        </div>{{-- /second row --}}
        @endif

        @endif



        @else
        {{-- ═══════════════════════════════════════════════════════════════
             OPERATOR VIEW — My Work + My Performance
        ════════════════════════════════════════════════════════════════ --}}
        <div class="row g-4">

            {{-- My Work ─────────────────────────────────────────────── --}}
            <div class="col-12 col-lg-7">
                <div class="card h-100">
                    <div class="card-header d-flex align-items-center border-bottom py-3">
                        <i class="bx bx-task text-primary me-2"></i>
                        <h6 class="mb-0 fw-semibold">My Work</h6>
                    </div>
                    <div class="card-body p-0">

                        {{-- OVERDUE ── red tint, pulsing dot, shown first --}}
                        <div class="dash-section-header dash-section-overdue overdue-bg">
                            <span class="dash-pulse"></span>
                            Overdue
                        </div>
                        <div class="overdue-bg">
                            <a href="{{ $hasActivities ? '/activities/?due=overdue' : 'javascript:void(0);' }}" class="dash-work-row">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bx bx-time-five text-danger" style="font-size:1.1rem;"></i>
                                    <span class="small fw-medium">Overdue Activities</span>
                                </div>
                                <span class="badge bg-danger rounded-pill" id="op-overdue-activities">—</span>
                            </a>
                            @if($hasSalesOrders)
                            <a href="/sales/quotations/?expiry=expired" class="dash-work-row">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bx bx-x-circle text-danger" style="font-size:1.1rem;"></i>
                                    <span class="small fw-medium">Expired Quotations</span>
                                </div>
                                <span class="badge bg-danger rounded-pill" id="op-overdue-expired-quotations">—</span>
                            </a>
                            @endif
                            @if($hasSalesDelivery)
                            <a href="/sales/orders/?delivery=overdue" class="dash-work-row">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bx bx-cart text-danger" style="font-size:1.1rem;"></i>
                                    <span class="small fw-medium">Orders Overdue for Delivery</span>
                                </div>
                                <span class="badge bg-danger rounded-pill" id="op-overdue-deliveries">—</span>
                            </a>
                            @endif
                            @if($hasPurchaseReceipts)
                            <a href="/purchase/orders/" class="dash-work-row">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bx bx-package text-danger" style="font-size:1.1rem;"></i>
                                    <span class="small fw-medium">Delayed Receipts</span>
                                </div>
                                <span class="badge bg-danger rounded-pill" id="op-overdue-receipts">—</span>
                            </a>
                            @endif
                            @if($hasManufacturing)
                            <a href="/manufacturing/orders/" class="dash-work-row">
                                <div class="d-flex align-items-center gap-3">
                                    <i class="bx bx-cog text-danger" style="font-size:1.1rem;"></i>
                                    <span class="small fw-medium">Overdue Manufacturing Orders</span>
                                </div>
                                <span class="badge bg-danger rounded-pill" id="op-overdue-mos">—</span>
                            </a>
                            @endif
                        </div>

                        {{-- TODAY --}}
                        <div class="dash-section-header dash-section-today">
                            <i class="bx bx-sun" style="font-size:0.85rem;"></i>
                            Today
                        </div>
                        <a href="{{ $hasActivities ? '/activities/?due=today' : 'javascript:void(0);' }}" class="dash-work-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-bell text-primary" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Due Activities</span>
                            </div>
                            <span class="badge bg-primary rounded-pill" id="op-today-activities">—</span>
                        </a>
                        @if($hasSalesOrders)
                        <a href="/sales/quotations/?expiry=today" class="dash-work-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-alarm text-primary" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Quotations Expiring Today</span>
                            </div>
                            <span class="badge bg-primary rounded-pill" id="op-today-expiring-quotations">—</span>
                        </a>
                        @endif
                        @if($hasSalesDelivery)
                        <a href="/sales/orders/?delivery=due_today" class="dash-work-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-cart-alt text-primary" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Orders Overdue for Delivery</span>
                            </div>
                            <span class="badge bg-primary rounded-pill" id="op-today-deliveries">—</span>
                        </a>
                        @endif
                        @if($hasPurchaseReceipts)
                        <a href="/purchase/receipts/" class="dash-work-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-import text-primary" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Due Receipts</span>
                            </div>
                            <span class="badge bg-primary rounded-pill" id="op-today-receipts">—</span>
                        </a>
                        @endif

                        {{-- PENDING --}}
                        <div class="dash-section-header dash-section-pending">
                            <i class="bx bx-list-ul" style="font-size:0.85rem;"></i>
                            Pending
                        </div>
                        @if($hasCrmLeads)
                        <a href="/crm/leads/" class="dash-work-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-user-check text-success" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Open Leads</span>
                            </div>
                            <span class="badge bg-secondary rounded-pill" id="op-pending-leads">—</span>
                        </a>
                        @endif
                        @if($hasSalesOrders)
                        <a href="/sales/quotations/?expiry=soon" class="dash-work-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-time-five text-warning" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Expiring Quotations (7 days)</span>
                            </div>
                            <span class="badge bg-warning rounded-pill" id="op-pending-expiring-quotations">—</span>
                        </a>
                        <a href="/sales/orders/?status=open" class="dash-work-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-receipt text-warning" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Open Sales Orders</span>
                            </div>
                            <span class="badge bg-secondary rounded-pill" id="op-pending-so">—</span>
                        </a>
                        @endif
                        @if($hasPurchaseOrders)
                        <a href="/purchase/orders/" class="dash-work-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-box text-warning" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Open Purchase Orders</span>
                            </div>
                            <span class="badge bg-secondary rounded-pill" id="op-pending-po">—</span>
                        </a>
                        @endif
                        @if($hasManufacturing)
                        <a href="/manufacturing/orders/" class="dash-work-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-cog text-info" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Open Manufacturing Orders</span>
                            </div>
                            <span class="badge bg-secondary rounded-pill" id="op-pending-mos">—</span>
                        </a>
                        @endif
                        <a href="{{ $hasActivities ? '/activities/?due=unscheduled' : 'javascript:void(0);' }}" class="dash-work-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-calendar-x text-secondary" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Unscheduled Activities</span>
                            </div>
                            <span class="badge bg-secondary rounded-pill" id="op-pending-unscheduled">—</span>
                        </a>

                    </div>
                </div>
            </div>

            {{-- My Performance ──────────────────────────────────────── --}}
            <div class="col-12 col-lg-5">
                <div class="card h-100">
                    <div class="card-header border-bottom py-3">
                        <div class="d-flex align-items-center mb-2">
                            <i class="bx bx-trending-up text-success me-2"></i>
                            <h6 class="mb-0 fw-semibold">My Performance</h6>
                        </div>
                        <div class="btn-group btn-group-sm w-100" role="group">
                            <button type="button" class="btn btn-outline-primary perf-toggle" data-period="today">Today</button>
                            <button type="button" class="btn btn-outline-primary perf-toggle" data-period="week">This Week</button>
                            <button type="button" class="btn btn-primary perf-toggle active" data-period="month">This Month</button>
                        </div>
                    </div>
                    <div class="card-body p-0">

                        @if($hasCrmLeads)
                        <div class="dash-perf-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-trophy text-success" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Won Leads</span>
                            </div>
                            <span class="dash-perf-val text-success" id="op-perf-won">—</span>
                        </div>
                        <div class="dash-perf-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-x-circle text-danger" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Lost Leads</span>
                            </div>
                            <span class="dash-perf-val text-danger" id="op-perf-lost">—</span>
                        </div>
                        @endif

                        @if($hasSalesOrders)
                        <div class="dash-perf-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-cart text-warning" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Sales Orders Created</span>
                            </div>
                            <span class="dash-perf-val text-warning" id="op-perf-so">—</span>
                        </div>
                        @endif

                        @if($hasSalesDelivery)
                        <div class="dash-perf-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-check-circle text-info" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Deliveries Completed</span>
                            </div>
                            <span class="dash-perf-val text-info" id="op-perf-deliveries">—</span>
                        </div>
                        @endif

                        <div class="dash-perf-row">
                            <div class="d-flex align-items-center gap-3">
                                <i class="bx bx-list-check text-primary" style="font-size:1.1rem;"></i>
                                <span class="small fw-medium">Completed Activities</span>
                            </div>
                            <span class="dash-perf-val text-primary" id="op-perf-activities">—</span>
                        </div>

                    </div>
                </div>
            </div>

        </div>
        @endif

    </div>{{-- /dashboard-content --}}
</div>
@endsection

@push('scripts')
@if($isAdminView)
<link rel="stylesheet" href="{{ asset('assets/vendor/libs/apex-charts/apex-charts.css') }}">
<script src="{{ asset('assets/vendor/libs/apex-charts/apexcharts.js') }}"></script>
@endif
<script>
(function () {

    var IS_ADMIN = {{ $isAdminView ? 'true' : 'false' }};

    function setText(id, value) {
        var el = document.getElementById(id);
        if (el && value !== null && value !== undefined) el.textContent = value;
    }

    // ── Admin date range ───────────────────────────────────────────────────
    const localDate = d => `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;

    function getPresetDates(preset) {
        const now = new Date();
        switch (preset) {
            case 'today':
                return { from: localDate(now), to: localDate(now) };
            case 'this_month': {
                const from = new Date(now.getFullYear(), now.getMonth(), 1);
                const to   = new Date(now.getFullYear(), now.getMonth() + 1, 0);
                return { from: localDate(from), to: localDate(to) };
            }
            case 'last_month': {
                const from = new Date(now.getFullYear(), now.getMonth() - 1, 1);
                const to   = new Date(now.getFullYear(), now.getMonth(), 0);
                return { from: localDate(from), to: localDate(to) };
            }
            case 'this_year':
                return { from: `${now.getFullYear()}-01-01`, to: `${now.getFullYear()}-12-31` };
            default:
                return null;
        }
    }

    var adminDateFrom = `${new Date().getFullYear()}-01-01`;
    var adminDateTo   = `${new Date().getFullYear()}-12-31`;

    // ── Admin view ─────────────────────────────────────────────────────────
    async function loadAdmin(dateFrom, dateTo) {
        dateFrom = dateFrom || adminDateFrom;
        dateTo   = dateTo   || adminDateTo;
        try {
            const res = await api.get('/dashboard/summary', { params: { date_from: dateFrom, date_to: dateTo } });
            const d = res.data.data;

            if (d.crm) {
                setText('dash-crm-pipeline',     d.crm.pipeline_value_fmt);
                setText('dash-crm-won-revenue',  d.crm.won_revenue_fmt);
                setText('dash-crm-conversion',   d.crm.conversion_rate !== null ? d.crm.conversion_rate + '%' : '—');
                setText('dash-crm-active-leads', d.crm.active_leads);
            }
            if (d.sales) {
                setText('dash-sales-revenue',      d.sales.confirmed_revenue_fmt);
                setText('dash-sales-returns',      d.sales.returns_total_fmt);
                setText('dash-sales-quotations',   d.sales.quotation_pipeline_fmt);
                setText('dash-sales-avg-order',    d.sales.avg_order_value_fmt);
                setText('dash-sales-total-orders', d.sales.total_orders);
            }
            if (d.purchasing) {
                setText('dash-po-open',    d.purchasing.open_po_count);
                setText('dash-po-overdue', d.purchasing.overdue_receipts);
            }
            if (d.manufacturing) {
                setText('dash-mfg-open',      d.manufacturing.open_count);
                setText('dash-mfg-completed', d.manufacturing.completed_count);
                setText('dash-mfg-overdue',   d.manufacturing.overdue_count);
            }
            if (d.customers) {
                setText('dash-cust-active', d.customers.active_count);
                setText('dash-cust-new',    d.customers.new_count);
            }
            const ba = d.business_alerts;
            if (ba) {
                if (ba.revenue) {
                    setText('dash-alert-revenue', ba.revenue.this_month_fmt);
                    var trendEl = document.getElementById('dash-alert-revenue-trend');
                    if (trendEl) {
                        if (ba.revenue.pct_change !== null) {
                            var isUp  = ba.revenue.direction === 'up';
                            var color = isUp ? 'success' : 'danger';
                            var icon  = isUp ? 'bx-trending-up' : 'bx-trending-down';
                            trendEl.innerHTML = '<span class="badge bg-' + color + ' d-flex align-items-center gap-1">'
                                + '<i class="bx ' + icon + '"></i>'
                                + Math.abs(ba.revenue.pct_change) + '%</span>';
                        } else {
                            trendEl.innerHTML = '<span class="badge bg-secondary">No prior data</span>';
                        }
                    }
                }
                setText('dash-alert-delivery',              ba.delivery_alerts);
                setText('dash-alert-dispatch',              ba.pending_dispatch);
                setText('dash-alert-expired-quotations',    ba.expired_quotations);
                setText('dash-alert-expiring-quotations',   ba.expiring_quotations);
                setText('dash-alert-open-pos',              ba.open_pos);
                setText('dash-alert-pending-receipts',      ba.pending_receipts);
                setText('dash-alert-open-mos',              ba.open_mos);
                setText('dash-alert-overdue-mos',           ba.overdue_mos);
                setText('dash-alert-activities',            ba.open_activities);
            }

            show();
        } catch (err) {
            handleApiError(err);
        }
    }

    // ── Preset buttons ─────────────────────────────────────────────────────
    function setActivePreset(preset) {
        document.querySelectorAll('.dash-preset').forEach(function(btn) {
            btn.classList.remove('active', 'btn-primary');
            btn.classList.add('btn-outline-primary');
        });
        var active = document.querySelector('.dash-preset[data-preset="' + preset + '"]');
        if (active) {
            active.classList.add('active', 'btn-primary');
            active.classList.remove('btn-outline-primary');
        }
        document.getElementById('dash-custom-range-wrap').style.display = preset === 'custom' ? '' : 'none';
    }

    document.querySelectorAll('.dash-preset').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var preset = btn.dataset.preset;
            setActivePreset(preset);
            if (preset === 'custom') return; // wait for date picker
            var dates = getPresetDates(preset);
            adminDateFrom = dates.from;
            adminDateTo   = dates.to;
            loadAdmin(dates.from, dates.to);
        });
    });

    // ── Operator view ──────────────────────────────────────────────────────
    var currentPeriod = 'month';

    async function loadOperator(period) {
        period = period || currentPeriod;
        try {
            const res = await api.get('/dashboard/operator-summary', { params: { period } });
            const d = res.data.data;

            // My Work — set once, doesn't change with period
            const mw = d.my_work;
            setText('op-overdue-activities',  mw.overdue.activities);
            setText('op-overdue-deliveries',  mw.overdue.deliveries);
            setText('op-overdue-receipts',    mw.overdue.receipts);
            setText('op-overdue-mos',         mw.overdue.mfg_orders);
            setText('op-today-activities',    mw.today.activities);
            setText('op-today-deliveries',    mw.today.deliveries);
            setText('op-today-receipts',      mw.today.receipts);
            setText('op-pending-leads',       mw.pending.leads);
            setText('op-overdue-expired-quotations',    mw.pending.expired_quotations);
            setText('op-today-expiring-quotations',     mw.pending.expiring_today_quotations);
            setText('op-pending-expiring-quotations',   mw.pending.expiring_quotations);
            setText('op-pending-so',                   mw.pending.sales_orders);
            setText('op-pending-po',          mw.pending.purchase_orders);
            setText('op-pending-mos',         mw.pending.mfg_orders);
            setText('op-pending-unscheduled', mw.pending.unscheduled_activities);

            // Performance
            const p = d.performance;
            setText('op-perf-won',        p.won_leads);
            setText('op-perf-lost',       p.lost_leads);
            setText('op-perf-so',         p.sales_orders);
            setText('op-perf-deliveries', p.deliveries_completed);
            setText('op-perf-activities', p.completed_activities);

            show();
        } catch (err) {
            handleApiError(err);
        }
    }

    async function loadPerformanceOnly(period) {
        try {
            const res = await api.get('/dashboard/operator-summary', { params: { period } });
            const p = res.data.data.performance;
            setText('op-perf-won',        p.won_leads);
            setText('op-perf-lost',       p.lost_leads);
            setText('op-perf-so',         p.sales_orders);
            setText('op-perf-deliveries', p.deliveries_completed);
            setText('op-perf-activities', p.completed_activities);
        } catch (err) {
            handleApiError(err);
        }
    }

    function show() {
        document.getElementById('dashboard-loading').classList.add('d-none');
        document.getElementById('dashboard-content').classList.remove('d-none');
    }

    // ── Period toggle ──────────────────────────────────────────────────────
    document.querySelectorAll('.perf-toggle').forEach(function (btn) {
        btn.addEventListener('click', function () {
            document.querySelectorAll('.perf-toggle').forEach(function (b) {
                b.classList.remove('active', 'btn-primary');
                b.classList.add('btn-outline-primary');
            });
            btn.classList.add('active', 'btn-primary');
            btn.classList.remove('btn-outline-primary');

            // Reset performance values while loading
            ['op-perf-won','op-perf-lost','op-perf-so','op-perf-deliveries','op-perf-activities']
                .forEach(function (id) { setText(id, '…'); });

            currentPeriod = btn.dataset.period;
            loadPerformanceOnly(currentPeriod);
        });
    });

    // ── Sales chart ────────────────────────────────────────────────────────
    var _salesChart = null;

    function initSalesChart(monthData) {
        if (_salesChart) { _salesChart.destroy(); _salesChart = null; }
        var primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--bs-primary').trim() || '#696cff';
        _salesChart = new ApexCharts(document.getElementById('sales-month-chart'), {
            chart:  { type: 'bar', height: 280, toolbar: { show: false }, parentHeightOffset: 0 },
            series: [{ name: 'Revenue', data: monthData }],
            xaxis:  { categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] },
            yaxis:  { labels: { formatter: function(v) { return formatCurrency(v, { decimals: 0 }); } } },
            plotOptions: { bar: { borderRadius: 4, columnWidth: '55%' } },
            dataLabels: { enabled: false },
            colors: [primaryColor],
            grid:   { borderColor: '#f1f1f1', padding: { top: 0 } },
            tooltip: { y: { formatter: function(v) { return formatCurrency(v); } } },
        });
        _salesChart.render();
    }

    async function loadSalesChart(year) {
        try {
            const res = await api.get('/dashboard/sales-by-month', { params: { year: year } });
            initSalesChart(res.data.data.months);
        } catch (err) {
            console.error('Sales chart load failed', err);
        }
    }

    var chartYearSelect = document.getElementById('chart-year-select');
    if (chartYearSelect) {
        chartYearSelect.addEventListener('change', function() {
            loadSalesChart(parseInt(this.value, 10));
        });
    }

    // ── Top Customers chart ────────────────────────────────────────────────
    var _topCustomersChart = null;

    function initTopCustomersChart(customers) {
        if (_topCustomersChart) { _topCustomersChart.destroy(); _topCustomersChart = null; }
        var primaryColor = getComputedStyle(document.documentElement).getPropertyValue('--bs-primary').trim() || '#696cff';
        var names    = customers.map(function(c) { return c.name; });
        var revenues = customers.map(function(c) { return c.revenue; });
        _topCustomersChart = new ApexCharts(document.getElementById('top-customers-chart'), {
            chart:  { type: 'bar', height: Math.max(200, names.length * 36), toolbar: { show: false } },
            series: [{ name: 'Revenue', data: revenues }],
            xaxis:  {
                categories: names,
                labels: { formatter: function(v) { return formatCurrency(v, { decimals: 0 }); } },
            },
            plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '60%' } },
            dataLabels: { enabled: false },
            colors: [primaryColor],
            grid:   { borderColor: '#f1f1f1' },
            tooltip: { y: { formatter: function(v) { return formatCurrency(v); } } },
        });
        _topCustomersChart.render();
    }

    async function loadTopCustomers() {
        try {
            const res = await api.get('/dashboard/top-customers');
            initTopCustomersChart(res.data.data.customers);
        } catch (err) {
            console.error('Top customers chart load failed', err);
        }
    }

    // ── Leads by Month chart ───────────────────────────────────────────────
    var _leadsChart = null;

    function initLeadsChart(data) {
        if (_leadsChart) { _leadsChart.destroy(); _leadsChart = null; }
        _leadsChart = new ApexCharts(document.getElementById('leads-month-chart'), {
            chart:  { type: 'bar', height: 280, toolbar: { show: false }, parentHeightOffset: 0 },
            series: [
                { name: 'New',  data: data.new  },
                { name: 'Won',  data: data.won  },
                { name: 'Lost', data: data.lost },
            ],
            xaxis:  { categories: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'] },
            plotOptions: { bar: { borderRadius: 3, columnWidth: '65%', grouped: true } },
            dataLabels: { enabled: false },
            colors: [
                getComputedStyle(document.documentElement).getPropertyValue('--bs-primary').trim()  || '#696cff',
                getComputedStyle(document.documentElement).getPropertyValue('--bs-success').trim()  || '#71dd37',
                getComputedStyle(document.documentElement).getPropertyValue('--bs-danger').trim()   || '#ff3e1d',
            ],
            grid:   { borderColor: '#f1f1f1', padding: { top: 0 } },
            legend: { show: true, position: 'top' },
            tooltip: { shared: true, intersect: false },
        });
        _leadsChart.render();
    }

    async function loadLeadsChart(year) {
        try {
            const res = await api.get('/dashboard/leads-by-month', { params: { year: year } });
            initLeadsChart(res.data.data);
        } catch (err) {
            console.error('Leads chart load failed', err);
        }
    }

    var leadsChartYearSelect = document.getElementById('leads-chart-year-select');
    if (leadsChartYearSelect) {
        leadsChartYearSelect.addEventListener('change', function() {
            loadLeadsChart(parseInt(this.value, 10));
        });
    }

    // ── Boot ───────────────────────────────────────────────────────────────
    document.addEventListener('DOMContentLoaded', function () {
        if (IS_ADMIN) {
            initDatePicker('#dash-custom-range', {
                mode: 'range',
                static: false,
                onClose: function(selectedDates) {
                    if (selectedDates.length < 2) return;
                    adminDateFrom = localDate(selectedDates[0]);
                    adminDateTo   = localDate(selectedDates[selectedDates.length - 1]);
                    loadAdmin(adminDateFrom, adminDateTo);
                }
            });
            loadAdmin();
            if (chartYearSelect) {
                loadSalesChart(parseInt(chartYearSelect.value, 10));
            }
            loadTopCustomers();
            if (leadsChartYearSelect) {
                loadLeadsChart(parseInt(leadsChartYearSelect.value, 10));
            }
        } else {
            loadOperator('month');
        }
    });

})();
</script>
@endpush
