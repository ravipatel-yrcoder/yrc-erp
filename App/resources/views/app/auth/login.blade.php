@extends('layouts.app')
@section('title', 'Login')

@section('content')
<div class="relative flex min-h-screen flex-col items-center justify-center px-6">
  <!-- Logo/Icon link to Home -->
  <a href="{{ url('/') }}" class="absolute top-8 flex flex-col items-center">
    <div class="mx-auto h-14 w-14 rounded-2xl bg-gradient-to-br from-rose-500 to-orange-400 shadow-lg"></div>
  </a>

  <!-- Card -->
  <div class="w-full max-w-md rounded-xl border border-slate-200 p-6 shadow-lg dark:border-slate-800 dark:bg-slate-900">
    <h1 class="mb-6 text-center text-2xl font-semibold text-slate-900 dark:text-white">Log in</h1>

    <!-- Login form -->
     <p id="errorMsg" class="mt-2 text-red-500 text-sm"></p>
    <form id="loginForm" method="POST" class="space-y-4">
      @csrf
      <!-- Email -->
      <div>
        <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          Email
        </label>
        <input type="email" id="email" name="email"
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-rose-500 focus:ring focus:ring-rose-500 focus:ring-opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
      </div>

      <!-- Password -->
      <div>
        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          Password
        </label>
        <input type="password" id="password" name="password"
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-rose-500 focus:ring focus:ring-rose-500 focus:ring-opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
      </div>

      <!-- Remember Me -->
      <div class="flex items-center justify-between">
        <label class="flex items-center gap-2 text-sm text-slate-600 dark:text-slate-300">
          <input type="checkbox" name="remember" class="rounded border-slate-300 text-rose-600 shadow-sm focus:ring-rose-500 dark:border-slate-700 dark:bg-slate-800">
          Remember me
        </label>
        <a href="{{ url('/forgot-password') }}" class="text-sm text-rose-600 hover:underline dark:text-rose-400">
          Forgot password?
        </a>
      </div>

      <!-- Submit -->
      <button type="submit"
        class="w-full rounded-lg bg-gradient-to-br from-rose-500 to-orange-400 px-4 py-2 text-white shadow-lg hover:opacity-90">
        Log in
      </button>
    </form>

    <!-- Register link -->
    <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-300">
      Don’t have an account?
      <a href="{{ url('/register') }}" class="text-rose-600 hover:underline dark:text-rose-400">
        Register
      </a>
    </p>
  </div>

  <!-- Footer -->
  <!--<div class="mt-10 text-center text-sm text-slate-500 dark:text-slate-400">
    &copy; {{ date('Y') }} Your App. All rights reserved.
  </div>-->
</div>
@endsection

@push('scripts')
<script>
const loginForm = document.getElementById('loginForm');
const errorMsg = document.getElementById('errorMsg');

loginForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();

    try {
        const response = await axios.post('/api/auth/login', new FormData(loginForm), {headers: {'X-Client-Type': 'web'}});
        
        if (response.data.status === 'success') {
            window.location.href = '/about-us';
        } else {
            errorMsg.textContent = response.data.message || 'Login failed';
        }

    } catch (err) {
        
      if (err.response) {
            errorMsg.textContent = err.response.data.message || 'Login failed';
        } else {
            errorMsg.textContent = 'Server unreachable. Please try again.';
        }
    }
});
</script>
@endpush