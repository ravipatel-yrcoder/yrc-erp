@extends('layouts.app')
@section('title', 'Create your account')

@section('content')
<div class="authentication-wrapper authentication-cover">
  <a href="{{ url('/') }}" class="app-brand auth-cover-brand gap-2">
    <span class="app-brand-logo demo">
      <img src="{{asset('/assets/img/logo.png')}}" alt="{{config('app.name')}}"/>
    </span>
  </a>

  <div class="authentication-inner row m-0">
    {{-- Left illustration --}}
    <div class="d-none d-lg-flex col-lg-7 col-xl-8 align-items-center p-5">
      <div class="w-100 d-flex justify-content-center">
        <img src="{{asset('/assets/img/illustrations/boy-with-rocket-light.png')}}"
             class="img-fluid" alt="Register"
             width="700"
             data-app-dark-img="illustrations/boy-with-rocket-dark.png"
             data-app-light-img="illustrations/boy-with-rocket-light.png" />
      </div>
    </div>

    {{-- Register form --}}
    <div class="d-flex col-12 col-lg-5 col-xl-4 align-items-center authentication-bg p-sm-12 p-6">
      <div class="w-px-400 mx-auto mt-sm-12 mt-8">

        {{-- Header --}}
        <div id="registerFormWrap">
          <h4 class="mb-1">Start your free trial 🚀</h4>
          <p class="mb-6">14 days free, no credit card required</p>

          <form id="registerForm" class="mb-6" method="POST" novalidate>

            {{-- First name + Last name --}}
            <div class="row g-3 mb-4">
              <div class="col-6 form-control-validation">
                <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="first_name" name="first_name"
                       placeholder="First name" autofocus />
              </div>
              <div class="col-6 form-control-validation">
                <label for="last_name" class="form-label">Last Name</label>
                <input type="text" class="form-control" id="last_name" name="last_name"
                       placeholder="Last name" />
              </div>
            </div>

            {{-- Company name --}}
            <div class="mb-4 form-control-validation">
              <label for="company_name" class="form-label">Company Name <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="company_name" name="company_name"
                     placeholder="Your company name" />
            </div>

            {{-- Email --}}
            <div class="mb-4 form-control-validation">
              <label for="email" class="form-label">Work Email <span class="text-danger">*</span></label>
              <input type="email" class="form-control" id="email" name="email"
                     placeholder="you@company.com" />
            </div>

            {{-- Phone --}}
            <div class="mb-4 form-control-validation">
              <label for="phone" class="form-label">Phone <span class="text-danger">*</span></label>
              <input type="text" class="form-control" id="phone" name="phone"
                     placeholder="+91 98765 43210" />
            </div>

            {{-- Country --}}
            <div class="mb-4 form-control-validation">
              <label for="country" class="form-label">Country <span class="text-danger">*</span></label>
              <select class="form-select" id="country" name="country">
                <option value="">Select your country</option>
                @foreach(getCountries() as $code => $name)
                  <option value="{{ $code }}">{{ $name }}</option>
                @endforeach
              </select>
            </div>

            {{-- Password --}}
            <div class="mb-4 form-password-toggle form-control-validation">
              <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <input type="password" class="form-control" id="password" name="password"
                       placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
              </div>
            </div>

            {{-- Confirm password --}}
            <div class="mb-6 form-password-toggle form-control-validation">
              <label for="confirm_password" class="form-label">Confirm Password <span class="text-danger">*</span></label>
              <div class="input-group input-group-merge">
                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                       placeholder="&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;&#xb7;" />
                <span class="input-group-text cursor-pointer"><i class="icon-base bx bx-hide"></i></span>
              </div>
            </div>

            <button type="submit" class="btn btn-primary d-flex w-100" id="registerBtn">
              Create Account
            </button>
          </form>

          <p class="text-center">
            <span>Already have an account?</span>
            <a href="/login"> Sign in</a>
          </p>
        </div>

        {{-- Success state (hidden until form submits) --}}
        <div id="registerSuccessWrap" class="text-center d-none">
          <div class="mb-4">
            <i class="bx bx-envelope-open text-primary" style="font-size:3.5rem;"></i>
          </div>
          <h5 class="mb-2">Check your email</h5>
          <p class="text-muted mb-4">
            We've sent an activation link to <strong id="successEmail"></strong>.
            Click the link in the email to activate your account and start your free trial.
          </p>
          <p class="text-muted small">Didn't get the email? Check your spam folder, or
            <a href="#" id="resendLink">register again</a> to resend it.
          </p>
        </div>

      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
'use strict';

document.addEventListener('DOMContentLoaded', function () {

  const form          = document.getElementById('registerForm');
  const registerBtn   = document.getElementById('registerBtn');
  const formWrap      = document.getElementById('registerFormWrap');
  const successWrap   = document.getElementById('registerSuccessWrap');
  const successEmail  = document.getElementById('successEmail');
  const resendLink    = document.getElementById('resendLink');

  if (form && typeof FormValidation !== 'undefined') {
    const fv = FormValidation.formValidation(form, {
      fields: {
        first_name: {
          validators: {
            notEmpty: { message: 'First name is required' }
          }
        },
        company_name: {
          validators: {
            notEmpty: { message: 'Company name is required' }
          }
        },
        email: {
          validators: {
            notEmpty:     { message: 'Email is required' },
            emailAddress: { message: 'Please enter a valid email address' }
          }
        },
        phone: {
          validators: {
            notEmpty: { message: 'Phone is required' }
          }
        },
        country: {
          validators: {
            notEmpty: { message: 'Country is required' }
          }
        },
        password: {
          validators: {
            notEmpty:     { message: 'Password is required' },
            stringLength: { min: 8, message: 'Password must be at least 8 characters' }
          }
        },
        confirm_password: {
          validators: {
            notEmpty:  { message: 'Please confirm your password' },
            identical: { compare: () => form.querySelector('[name=password]').value, message: 'Passwords do not match' }
          }
        }
      },
      plugins: {
        trigger:      new FormValidation.plugins.Trigger(),
        bootstrap5:   new FormValidation.plugins.Bootstrap5({
          eleValidClass: '',
          rowSelector: '.form-control-validation'
        }),
        submitButton: new FormValidation.plugins.SubmitButton(),
        autoFocus:    new FormValidation.plugins.AutoFocus()
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
      cleanFormInputFeedback(form);
      setButtonLoading(registerBtn, true);

      const payload = formDataToObject(new FormData(form));

      try {
        const res = await api.post('/companies/register', payload, {
          headers: { 'X-Client-Type': 'web' }
        });

        if (res.data.status === 'success') {
          setButtonLoading(registerBtn, false);
          successEmail.textContent = payload.email;
          formWrap.classList.add('d-none');
          successWrap.classList.remove('d-none');
        } else {
          notyf.error(res.data.message || 'Registration failed');
          setButtonLoading(registerBtn, false);
        }
      } catch (err) {
        handleApiError(err, form);
        setButtonLoading(registerBtn, false);
      }
    });
  }

  // "Register again" link on success screen resets back to form
  if (resendLink) {
    resendLink.addEventListener('click', function (e) {
      e.preventDefault();
      successWrap.classList.add('d-none');
      formWrap.classList.remove('d-none');
    });
  }
});
</script>
@endpush
