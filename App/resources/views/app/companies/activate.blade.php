@extends('layouts.app')
@section('title', 'Activating your account')

@section('content')
<div class="flex-grow-1 container-fluid d-flex align-items-center justify-content-center" style="min-height:80vh;">
  <div class="text-center" style="max-width:440px;">

    {{-- Loading state --}}
    <div id="activateLoading">
      <div class="spinner-border text-primary mb-4" role="status" style="width:3rem;height:3rem;">
        <span class="visually-hidden">Activating…</span>
      </div>
      <h5 class="mb-2">Activating your account…</h5>
      <p class="text-muted">Please wait a moment.</p>
    </div>

    {{-- Success state --}}
    <div id="activateSuccess" class="d-none">
      <div class="mb-4">
        <i class="bx bx-check-circle text-success" style="font-size:4rem;"></i>
      </div>
      <h4 class="mb-2">Account Activated!</h4>
      <p class="text-muted mb-4">Your 14-day free trial has started. Redirecting to your dashboard…</p>
      <div class="spinner-border spinner-border-sm text-secondary" role="status"></div>
    </div>

    {{-- Error state --}}
    <div id="activateError" class="d-none">
      <div class="mb-4">
        <i class="bx bx-x-circle text-danger" style="font-size:4rem;"></i>
      </div>
      <h4 class="mb-2" id="activateErrorTitle">Activation Failed</h4>
      <p class="text-muted mb-4" id="activateErrorMsg">This activation link is invalid or has already been used.</p>
      <a href="/register" class="btn btn-primary">Sign up again</a>
    </div>

  </div>
</div>
@endsection

@push('scripts')
<script>
'use strict';

document.addEventListener('DOMContentLoaded', async function () {

  const loadingEl = document.getElementById('activateLoading');
  const successEl = document.getElementById('activateSuccess');
  const errorEl   = document.getElementById('activateError');
  const errorMsg  = document.getElementById('activateErrorMsg');
  const errorTitle = document.getElementById('activateErrorTitle');

  function showError(title, message) {
    loadingEl.classList.add('d-none');
    errorTitle.textContent = title || 'Activation Failed';
    errorMsg.textContent   = message || 'This activation link is invalid or has already been used.';
    errorEl.classList.remove('d-none');
  }

  // Extract token from URL
  const params   = new URLSearchParams(window.location.search);
  const rawToken = params.get('token');

  if (!rawToken) {
    showError('Invalid Link', 'No activation token found in the URL.');
    return;
  }

  try {
    const res = await api.post('/companies/activate', { token: rawToken }, {
      headers: { 'X-Client-Type': 'web' }
    });

    if (res.data.status === 'success') {
      loadingEl.classList.add('d-none');
      successEl.classList.remove('d-none');
      // Tokens are already set as httpOnly cookies by the API (web client type)
      setTimeout(() => { window.location.href = '/dashboard'; }, 1500);
    } else {
      showError('Activation Failed', res.data.message);
    }
  } catch (err) {
    const message = err.response?.data?.errors
      ? Object.values(err.response.data.errors).flat().join(' ')
      : (err.response?.data?.message || 'Server unreachable. Please try again.');
    showError('Activation Failed', message);
  }
});
</script>
@endpush
