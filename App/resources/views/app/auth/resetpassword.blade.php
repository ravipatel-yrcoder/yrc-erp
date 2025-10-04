@extends('layouts.app')

@section('title', 'Reset Password')
@section('bodyClasses', 'antialiased bg-gradient-to-br from-white to-slate-100 dark:from-slate-950 dark:to-slate-900 min-h-screen')

@section('content')

<div class="relative flex min-h-screen flex-col items-center justify-center px-6">
  <!-- Logo/Icon link to Home -->
  <a href="{{ url('/') }}" class="absolute top-8 flex flex-col items-center">
    <div class="mx-auto h-14 w-14 rounded-2xl bg-gradient-to-br from-rose-500 to-orange-400 shadow-lg"></div>
  </a>

  <!-- Card -->
  <div class="w-full max-w-md rounded-xl border border-slate-200 p-6 shadow-lg dark:border-slate-800 dark:bg-slate-900">
    <h1 class="mb-6 text-center text-2xl font-semibold text-slate-900 dark:text-white">Reset Password</h1>

    <!-- Reset Password form -->
    <p id="errorMsg" class="mt-2 text-red-500 text-sm"></p>
    <form id="resetForm" method="POST" class="space-y-4">
      @csrf

      <!-- Hidden token (from password reset link) -->
      <input type="hidden" name="token" value="{{ $token ?? '' }}">

      <!-- Email -->
      <div>
        <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          Email Address
        </label>
        <input type="email" id="email" name="email" required placeholder="you@example.com"
          value="{{ $email ?? '' }}"
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-rose-500 focus:ring focus:ring-rose-500 focus:ring-opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
      </div>

      <!-- New Password -->
      <div>
        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          New Password
        </label>
        <input type="password" id="password" name="password" required placeholder="••••••••"
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-rose-500 focus:ring focus:ring-rose-500 focus:ring-opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
      </div>

      <!-- Confirm Password -->
      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          Confirm Password
        </label>
        <input type="password" id="password_confirmation" name="password_confirmation" required placeholder="••••••••"
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-rose-500 focus:ring focus:ring-rose-500 focus:ring-opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white">
      </div>

      <!-- Submit -->
      <button type="submit"
        class="w-full rounded-lg bg-gradient-to-br from-rose-500 to-orange-400 px-4 py-2 text-white shadow-lg hover:opacity-90">
        Reset Password
      </button>
    </form>

    <!-- Back to login link -->
    <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-300">
      Remembered your password?
      <a href="{{ url('/login') }}" class="text-rose-600 hover:underline dark:text-rose-400">
        Log in
      </a>
    </p>
  </div>
</div>
@endsection

@push('scripts')
<script>
const resetForm = document.getElementById('resetForm');
const errorMsg = document.getElementById('errorMsg');

resetForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const password_confirmation = document.getElementById('password_confirmation').value.trim();
    const token = resetForm.token.value;

    if (password !== password_confirmation) {
        errorMsg.textContent = 'Passwords do not match.';
        return;
    }

    try {
        const response = await axios.post('/api/auth/reset-password', new URLSearchParams({ email, password, password_confirmation, token }), {
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });

        if (response.data.status === 'success') {
            errorMsg.textContent = '';
            alert(response.data.message || 'Password reset successfully!');
            window.location.href = '/login';
        } else {
            errorMsg.textContent = response.data.message || 'Failed to reset password';
        }

    } catch (err) {
        if (err.response) {
            errorMsg.textContent = err.response.data.message || 'Failed to reset password';
        } else {
            errorMsg.textContent = 'Server unreachable. Please try again.';
        }
    }
});
</script>
@endpush