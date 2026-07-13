@extends('layouts.app')

@section('title', 'Forgot Password')
@section('bodyClasses', 'antialiased bg-gradient-to-br from-white to-slate-100 dark:from-slate-950 dark:to-slate-900 min-h-screen')

@section('content')

<div class="authentication-wrapper authentication-cover">
  <!-- Logo -->
  <a href="javascript:void(0);" class="app-brand auth-cover-brand gap-2">
    <span class="app-brand-logo demo">
      <img src="{{asset('/assets/img/logo.png')}}" alt="Zentraq"/>
    </span>    
  </a>
  <!-- /Logo -->

  <div class="authentication-inner row m-0">
      <!-- /Left Text -->
      <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center p-5">
        <div class="w-100 d-flex justify-content-center">
          <img src="{{asset('/assets/img/illustrations/girl-unlock-password-light')}}" class="img-fluid" alt="Login image" width="700" data-app-dark-img="illustrations/boy-with-rocket-dark.png" data-app-light-img="illustrations/girl-unlock-password-light" />          
        </div>
      </div>
      <!-- /Left Text -->

      <!-- Forgot Password -->
      <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
        <div class="w-px-400 mx-auto mt-sm-12 mt-8">
          <h4 class="mb-1">Forgot Password? 🔒</h4>
          <p class="mb-6">Enter your email and we'll send you instructions to reset your password</p>
          <div id="errorMsg" class="alert alert-danger d-none mb-4 py-2" role="alert"></div>
          <div id="successMsg" class="alert alert-success d-none mb-4 py-2" role="alert"></div>
          <form id="formAuthentication" class="mb-6" method="GET">
            <div class="mb-6 form-control-validation">
              <label for="email" class="form-label">Email</label>
              <input
                type="text"
                class="form-control"
                id="email"
                name="email"
                placeholder="Enter your email"
                autofocus />
            </div>            
            <button class="btn btn-primary d-flex w-100">Send Reset Link</button>
          </form>
          <div class="text-center">
            <a href="/login/" class="d-flex align-items-center justify-content-center">
              <i class="bx bx-chevron-left icon-20px scaleX-n1-rtl me-1_5 align-top"></i>
              Back to login
            </a>
          </div>
        </div>
      </div>
      <!-- /Forgot Password -->
    </div>

</div>
@endsection

@push('scripts')
<script>
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const form       = document.getElementById('formAuthentication');
  const errorMsg   = document.getElementById('errorMsg');
  const successMsg = document.getElementById('successMsg');

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const email = document.getElementById('email').value.trim();

      errorMsg.classList.add('d-none');
      successMsg.classList.add('d-none');

      if (!email) {
        errorMsg.textContent = 'Please enter your email address.';
        errorMsg.classList.remove('d-none');
        return;
      }

      const btn = form.querySelector('button');
      setButtonLoading(btn, true);

      try {
        await api.post('/auth/forgot-password', { email });
        successMsg.textContent = 'If that email is registered, you will receive a reset link shortly.';
        successMsg.classList.remove('d-none');
        setButtonLoading(btn, false);
        btn.disabled = true;
        btn.textContent = 'Sent';
      } catch (err) {
        const message = err.response?.data?.message;
        errorMsg.textContent = message || 'Something went wrong. Please try again.';
        errorMsg.classList.remove('d-none');
        setButtonLoading(btn, false);
      }
    });
  }
});
</script>
@endpush