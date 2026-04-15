@extends('layouts.app')
@section('title', 'Login')

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
        <img
          src="{{asset('/assets/img/illustrations/boy-with-rocket-light.png')}}" class="img-fluid" alt="Login image"
          width="700" data-app-dark-img="illustrations/boy-with-rocket-dark.png" data-app-light-img="illustrations/boy-with-rocket-light.png" />
      </div>
    </div>
    <!-- /Left Text -->

    <!-- Login -->
    <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
      <div class="w-px-400 mx-auto mt-sm-12 mt-8">
        <h4 class="mb-1">Welcome to {{config('app.name')}}! 👋</h4>
        <p class="mb-6">Sign in to your account to continue</p>

        <form id="formAuthentication" class="mb-6" method="POST">
          <div class="mb-6 form-control-validation">
            <label for="email" class="form-label">Email or Username</label>
            <input
              type="text"
              class="form-control"
              id="email"
              name="email"
              placeholder="Enter your email or username"
              autofocus />
          </div>
          <div class="form-password-toggle form-control-validation">
            <label class="form-label" for="password">Password</label>
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
          <div class="my-7">
            <div class="d-flex justify-content-between">
              <div class="form-check mb-0">
                <input class="form-check-input" type="checkbox" id="remember-me" />
                <label class="form-check-label" for="remember-me">Remember Me</label>
              </div>
              {{-- <a href="auth-forgot-password-cover.html">
                <p class="mb-0">Forgot Password?</p>
              </a> --}}
            </div>
          </div>
          <button class="btn btn-primary d-grid w-100">Sign in</button>
        </form>

        {{-- <p class="text-center">
          <span>New on our platform?</span>
          <a href="auth-register-cover.html">
            <span>Create an account</span>
          </a>
        </p> --}}

        <div class="divider my-6">
          <div class="divider-text">or</div>
        </div>

        <div class="d-flex justify-content-center">
          <a href="javascript:;" class="btn btn-sm btn-icon rounded-circle btn-text-facebook me-1_5">
            <i class="icon-base bx bxl-facebook-circle icon-20px"></i>
          </a>

          <a href="javascript:;" class="btn btn-sm btn-icon rounded-circle btn-text-twitter me-1_5">
            <i class="icon-base bx bxl-twitter icon-20px"></i>
          </a>

          <a href="javascript:;" class="btn btn-sm btn-icon rounded-circle btn-text-github me-1_5">
            <i class="icon-base bx bxl-github icon-20px"></i>
          </a>

          <a href="javascript:;" class="btn btn-sm btn-icon rounded-circle btn-text-google-plus">
            <i class="icon-base bx bxl-google icon-20px"></i>
          </a>
        </div>
      </div>
    </div>
    <!-- /Login -->
  </div>
</div>

<!-- / Content -->
@endsection

@push('scripts')
<script>
/**
 * Pages Authentication
 */
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  const formAuthentication = document.querySelector('#formAuthentication');
  const errorMsg = document.getElementById('errorMsg'); // assuming this element exists

  if (formAuthentication && typeof FormValidation !== 'undefined') {
    const fv = FormValidation.formValidation(formAuthentication, {
      fields: {
        email: {
          validators: {
            notEmpty: {
              message: 'Please enter your email'
            },
            emailAddress: {
              message: 'Please enter a valid email address'
            }
          }
        },
        password: {
          validators: {
            notEmpty: {
              message: 'Please enter your password'
            },
            stringLength: {
              min: 6,
              message: 'Password must be more than 6 characters'
            }
          }
        }
      },
      plugins: {
        trigger: new FormValidation.plugins.Trigger(),
        bootstrap5: new FormValidation.plugins.Bootstrap5({
          eleValidClass: '',
          rowSelector: '.form-control-validation'
        }),
        submitButton: new FormValidation.plugins.SubmitButton(),
        autoFocus: new FormValidation.plugins.AutoFocus()
      },
      init: instance => {
        instance.on('plugins.message.placed', e => {
          if (e.element.parentElement.classList.contains('input-group')) {
            e.element.parentElement.insertAdjacentElement('afterend', e.messageElement);
          }
        });
      }
    });

    fv.on('core.form.valid', async () => {

    const formEl = document.getElementById('formAuthentication');

    cleanFormInputFeedback(formEl);

    const formData = new FormData(formEl);
    const payload  = formDataToObject(formData);

    try {

      const res = await api.post('/auth/login', payload, {
        headers: { 'X-Client-Type': 'web' }
      });

      const { status, message } = res.data;

      if (status === 'success') {

        notyf.success(message || 'Login successful');
        window.location.href = '/dashboard';

      } else {

        notyf.error(message || 'Login failed');

      }

    } catch (err) {

      handleApiError(err, formEl);

    }
  });
  }

  // Two-step verification input mask (if used)
  const numeralMaskElements = document.querySelectorAll('.numeral-mask');
  const formatNumeral = value => value.replace(/\D/g, '');

  numeralMaskElements.forEach(numeralMaskEl => {
    numeralMaskEl.addEventListener('input', event => {
      numeralMaskEl.value = formatNumeral(event.target.value);
    });
  });
});
</script>
@endpush