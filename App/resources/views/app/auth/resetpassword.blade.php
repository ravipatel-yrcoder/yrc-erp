@extends('layouts.app')

@section('title', 'Reset Password')
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

  <!-- Card -->
  <div class="authentication-inner row m-0">
    <!-- /Left Text -->
    <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center p-5">
      <div class="w-100 d-flex justify-content-center">
        <img
          src="{{asset('/assets/img/illustrations/boy-with-laptop-light.png')}}" class="img-fluid" alt="Login image"
          width="700" data-app-dark-img="illustrations/boy-with-rocket-dark.png" data-app-light-img="illustrations/boy-with-laptop-light.png" />        
      </div>
    </div>
    <!-- /Left Text -->

    <!-- Reset Password -->
    <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
      <div class="w-px-400 mx-auto mt-sm-12 mt-8">
        <h4 class="mb-1">Reset Password 🔒</h4>
        <p class="mb-6">&nbsp;</p>
        <div id="errorMsg" class="alert alert-danger d-none mb-4 py-2" role="alert"></div>
        <form id="formAuthentication" class="mb-6" method="POST">
          <input type="hidden" name="token" value="{{ $token ?? '' }}">
          <input type="hidden" name="email" value="{{ $email ?? '' }}">
          <div class="mb-6 form-password-toggle form-control-validation">
            <label class="form-label" for="password">New Password</label>
            <div class="input-group input-group-merge">
              <input
                type="password"
                id="password"
                class="form-control"
                name="password"
                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                aria-describedby="password" />
              <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
            </div>
          </div>
          <div class="mb-6 form-password-toggle form-control-validation">
            <label class="form-label" for="password_confirmation">Confirm Password</label>
            <div class="input-group input-group-merge">
              <input
                type="password"
                id="password_confirmation"
                class="form-control"
                name="password_confirmation"
                placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;"
                aria-describedby="password_confirmation" />
              <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
            </div>
          </div>          
          <button class="btn btn-primary d-flex w-100 mb-6">Set new password</button>
          <div class="text-center">
            <a href="/login/">
              <i class="icon-base bx bx-chevron-left scaleX-n1-rtl me-1_5 align-top"></i>
              Back to login
            </a>
          </div>
        </form>
      </div>
    </div>
    <!-- /Reset Password -->
  </div>

</div>
@endsection

@push('scripts')
<script>
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const form     = document.getElementById('formAuthentication');
  const errorMsg = document.getElementById('errorMsg');

  if (form) {
    form.addEventListener('submit', async (e) => {
      e.preventDefault();

      const token                = form.querySelector('[name="token"]').value;
      const email                = form.querySelector('[name="email"]').value;
      const password             = document.getElementById('password').value;
      const password_confirmation = document.getElementById('password_confirmation').value;

      errorMsg.classList.add('d-none');

      if (!token || !email) {
        errorMsg.textContent = 'Invalid or expired reset link. Please request a new one.';
        errorMsg.classList.remove('d-none');
        return;
      }

      cleanFormInputFeedback(form);

      const btn = form.querySelector('button[type="submit"]') || form.querySelector('button');
      setButtonLoading(btn, true);

      try {
        await api.post('/auth/reset-password', { token, email, password, password_confirmation });
        notyf.success('Password reset successfully.');
        window.location.href = '/login';
      } catch (err) {
        const errors  = err.response?.data?.errors || {};
        const message = err.response?.data?.message;

        if (errors.password) {
          showFormInputFeedback(document.getElementById('password'), errors.password, 'invalid');
        }
        if (errors.password_confirmation) {
          showFormInputFeedback(document.getElementById('password_confirmation'), errors.password_confirmation, 'invalid');
        }
        if (errors.token) {
          errorMsg.textContent = errors.token;
          errorMsg.classList.remove('d-none');
        } else if (!errors.password && !errors.password_confirmation) {
          errorMsg.textContent = message || 'Failed to reset password. Please try again.';
          errorMsg.classList.remove('d-none');
        }
        setButtonLoading(btn, false);
      }
    });
  }
});
</script>
@endpush