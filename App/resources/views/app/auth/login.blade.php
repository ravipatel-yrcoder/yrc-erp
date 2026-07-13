@extends('layouts.app')
@section('title', 'Login')

@section('content')
<div class="authentication-wrapper authentication-cover">
  <!-- Logo -->
  <a href="{{ url('/') }}" class="app-brand auth-cover-brand gap-2">
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

        <div id="errorMsg" class="alert alert-danger d-none mb-4 py-2" role="alert"></div>
        
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
              <a href="{{ url('/forgot-password') }}">
                <p class="mb-0">Forgot Password?</p>
              </a>
            </div>
          </div>          
          <button class="btn btn-primary d-flex w-100">Sign in</button>
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
'use strict';

document.addEventListener('DOMContentLoaded', function () {
  
  const form = document.getElementById('formAuthentication');
  const errorMsg = document.getElementById('errorMsg');
  const btnSignIn = form.querySelector('button');

  form.addEventListener('submit', async (e) => {
    
    e.preventDefault();

    errorMsg.classList.add('d-none');
    setButtonLoading(btnSignIn, true);

    const email    = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value;

    try {
      const res = await api.post('/auth/login', { email, password }, {
        headers: { 'X-Client-Type': 'web' }
      });

      if (res.data.status === 'success') {
        window.location.href = '/dashboard';
      }

    } catch (err) {
      const message = err.response?.data?.message;
      errorMsg.textContent = message || 'Something went wrong. Please try again.';
      errorMsg.classList.remove('d-none');
      setButtonLoading(btnSignIn, false);
    }
  });
});
</script>
@endpush