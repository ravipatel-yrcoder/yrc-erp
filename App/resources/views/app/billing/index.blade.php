@extends('layouts.app')
@section('title', 'Billing')

@section('content')
<div class="container-fluid">
    <div class="settings-page-content-wrapper">
        <div class="row g-5">

            @include('partial.app.settings-sidebar')

            <div class="col">

    @php
        $status        = $summary['status'] ?? null;
        $isPilot       = $status === 'pilot';
        $seats         = $summary['seats'] ?? ['total_seats' => 0, 'used_seats' => 0, 'available_seats' => 0];
        $periodEnd     = $summary['current_period_end'] ?? null;
        $trialEndsAt   = $summary['trial_ends_at'] ?? null;
    @endphp

    {{-- Pilot / trial notice --}}
    @if($isPilot || $status === 'trial')
    <div class="alert alert-info d-flex align-items-start gap-2 mb-4">
        <i class="bx bx-info-circle fs-5 mt-1 flex-shrink-0"></i>
        <div>
            @if($isPilot)
                You are on a managed pilot plan. Invoices will appear here once your subscription is activated.
            @else
                You are on a free trial. Invoices will appear here once you subscribe to a plan.
            @endif
        </div>
    </div>
    @endif

    {{-- Summary cards --}}
    <div class="row g-4 mb-4">

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card shadow-none bg-transparent border h-100">
                <div class="card-body">
                    <p class="text-muted small text-uppercase mb-1" style="font-size:0.7rem;letter-spacing:.06em;">Plan</p>
                    <h5 class="mb-1">{{ $summary['plan_name'] ?? '—' }}</h5>
                    @php
                        $badgeClass = match($status) {
                            'active'   => 'bg-label-success',
                            'trial'    => 'bg-label-info',
                            'pilot'    => 'bg-label-warning',
                            'past_due' => 'bg-label-danger',
                            default    => 'bg-label-secondary',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ ucfirst(str_replace('_', ' ', $status ?? '—')) }}</span>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card shadow-none bg-transparent border h-100">
                <div class="card-body">
                    <p class="text-muted small text-uppercase mb-1" style="font-size:0.7rem;letter-spacing:.06em;">Seats</p>
                    <h5 class="mb-1">{{ $seats['used_seats'] }} / {{ $seats['total_seats'] }}</h5>
                    <p class="text-muted small mb-0">{{ $seats['available_seats'] }} available</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card shadow-none bg-transparent border h-100">
                <div class="card-body">
                    <p class="text-muted small text-uppercase mb-1" style="font-size:0.7rem;letter-spacing:.06em;">Billing Cycle</p>
                    <h5 class="mb-1">{{ ucfirst($summary['billing_cycle'] ?? '—') }}</h5>
                    @if($periodEnd)
                        <p class="text-muted small mb-0">Renews {{ date('d M Y', strtotime($periodEnd)) }}</p>
                    @elseif($trialEndsAt)
                        <p class="text-muted small mb-0">Trial ends {{ date('d M Y', strtotime($trialEndsAt)) }}</p>
                    @else
                        <p class="text-muted small mb-0">—</p>
                    @endif
                </div>
            </div>
        </div>

        <div class="col-12 col-md-6 col-xl-3">
            <div class="card shadow-none bg-transparent border h-100">
                <div class="card-body">
                    <p class="text-muted small text-uppercase mb-1" style="font-size:0.7rem;letter-spacing:.06em;">Plan Price</p>
                    <h5 class="mb-1">
                        @if(!empty($summary['agreed_base_price']) && (float) $summary['agreed_base_price'] > 0)
                            ₹{{ number_format((float) $summary['agreed_base_price'], 2) }} / mo
                        @else
                            —
                        @endif
                    </h5>
                </div>
            </div>
        </div>

    </div>

    {{-- Invoice history --}}
    <div class="card shadow-none bg-transparent border">
        <div class="card-header">
            <h5 class="card-title mb-0">Invoice History</h5>
        </div>
        <div class="card-body">
            <div class="text-center py-5 text-muted">
                <i class="bx bx-receipt" style="font-size: 3rem;"></i>
                <p class="mt-3 mb-0">No invoices yet.</p>
                <p class="small">Invoices will appear here once billing is activated.</p>
            </div>
        </div>
    </div>

            </div>{{-- col --}}
        </div>{{-- row --}}
    </div>{{-- settings-page-content-wrapper --}}
</div>{{-- container --}}
@endsection
