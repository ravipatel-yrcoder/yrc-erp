@extends('layouts.app')
@section('title', 'Subscription Inactive')

@section('content')
<div class="flex-grow-1 container-fluid d-flex align-items-center justify-content-center" style="min-height: 80vh;">
    <div class="text-center" style="max-width: 480px;">

        {{-- Icon --}}
        <div class="mb-4">
            @php
                $status = $subscription->status ?? null;
            @endphp

            @if($status === 'past_due')
                <i class="bx bx-error-circle text-warning" style="font-size: 4rem;"></i>
            @elseif($status === 'suspended')
                <i class="bx bx-block text-danger" style="font-size: 4rem;"></i>
            @elseif($status === 'cancelled')
                <i class="bx bx-x-circle text-danger" style="font-size: 4rem;"></i>
            @elseif($status === 'pilot')
                <i class="bx bx-time-five text-warning" style="font-size: 4rem;"></i>
            @else
                {{-- trial expired or no subscription --}}
                <i class="bx bx-time-five text-secondary" style="font-size: 4rem;"></i>
            @endif
        </div>

        {{-- Heading --}}
        <h4 class="mb-2">
            @if($status === 'past_due')
                Payment Overdue
            @elseif($status === 'suspended')
                Account Suspended
            @elseif($status === 'cancelled')
                Subscription Cancelled
            @elseif($status === 'pilot')
                Your Pilot Period Has Ended
            @elseif($status === 'trial')
                Your Free Trial Has Ended
            @else
                Subscription Inactive
            @endif
        </h4>

        {{-- Sub-message --}}
        <p class="text-muted mb-6">
            @if($status === 'past_due')
                Your last payment did not go through. Please update your payment details to continue using {{config('app.name')}}.
            @elseif($status === 'suspended')
                Your account has been suspended. Please contact support to resolve this.
            @elseif($status === 'cancelled')
                Your subscription has been cancelled. Contact support if you'd like to reactivate.
            @elseif($status === 'pilot')
                Your pilot period has ended. Reach out to us and we'll get your subscription activated.
            @elseif($status === 'trial')
                Your 14-day free trial has ended. Please upgrade to a plan to continue.
            @else
                Your subscription is no longer active. Please contact support to restore access.
            @endif
        </p>

        {{-- Actions --}}
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="mailto:{{config('app.support_email', 'support@opsify.in')}}"
               class="btn btn-primary">
                <i class="bx bx-envelope me-1"></i> Contact Support
            </a>
            <button type="button" class="btn btn-outline-secondary" id="expiredLogoutBtn">
                <i class="bx bx-log-out me-1"></i> Log Out
            </button>
        </div>

        {{-- Plan info if available --}}
        @if($subscription)
        <p class="text-muted mt-6 small">
            Plan: <strong>{{ $subscription->plan_name ?? '—' }}</strong>
            &nbsp;&middot;&nbsp;
            Status: <strong>{{ ucfirst(str_replace('_', ' ', $subscription->status)) }}</strong>
        </p>
        @endif

    </div>
</div>

@push('scripts')
<script>
document.getElementById('expiredLogoutBtn').addEventListener('click', async function () {
    try {
        const response = await api.post('/auth/logout', {}, { headers: { 'X-Client-Type': 'web' } });
        if (response.data.status === 'success') {
            window.location.href = '/login';
        } else {
            alert(response.data.message);
        }
    } catch (err) {
        if (err.response && err.response.data) {
            alert(err.response.data.message);
        } else {
            alert('Server unreachable. Please try again.');
        }
    }
});
</script>
@endpush
@endsection
