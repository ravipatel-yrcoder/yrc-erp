<?php $__env->startSection('title', 'Register'); ?>
<?php $__env->startSection('bodyClasses', 'antialiased bg-gradient-to-br from-white to-slate-100 dark:from-slate-950 dark:to-slate-900 min-h-screen'); ?>

<?php $__env->startSection('content'); ?>

<div class="relative flex min-h-screen flex-col items-center justify-center px-6">
  <!-- Logo/Icon link to Home -->
  <a href="<?php echo e(url('/')); ?>" class="absolute top-8 flex flex-col items-center">
    <div class="mx-auto h-14 w-14 rounded-2xl bg-gradient-to-br from-rose-500 to-orange-400 shadow-lg"></div>
  </a>

  <!-- Card -->
  <div class="w-full max-w-md rounded-xl border border-slate-200 p-6 shadow-lg dark:border-slate-800 dark:bg-slate-900">
    <h1 class="mb-6 text-center text-2xl font-semibold text-slate-900 dark:text-white">Register</h1>

    <!-- Register form -->
    <p id="errorMsg" class="mt-2 text-red-500 text-sm"></p>
    <form id="registerForm" method="POST" class="space-y-4">
      <?php echo csrfField(); ?>

      <!-- Name -->
      <div>
        <label for="name" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          Full Name
        </label>
        <input type="text" id="name" name="name" required
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-rose-500 focus:ring focus:ring-rose-500 focus:ring-opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
          placeholder="John Doe">
      </div>

      <!-- Email -->
      <div>
        <label for="email" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          Email Address
        </label>
        <input type="email" id="email" name="email" required
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-rose-500 focus:ring focus:ring-rose-500 focus:ring-opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
          placeholder="your@email.com">
      </div>

      <!-- Password -->
      <div>
        <label for="password" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          Password
        </label>
        <input type="password" id="password" name="password" required
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-rose-500 focus:ring focus:ring-rose-500 focus:ring-opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
          placeholder="••••••••">
      </div>

      <!-- Confirm Password -->
      <div>
        <label for="password_confirmation" class="block text-sm font-medium text-slate-700 dark:text-slate-300">
          Confirm Password
        </label>
        <input type="password" id="password_confirmation" name="password_confirmation" required
          class="mt-1 block w-full rounded-lg border border-slate-300 bg-white px-3 py-2 text-sm text-slate-900 placeholder-slate-400 shadow-sm focus:border-rose-500 focus:ring focus:ring-rose-500 focus:ring-opacity-50 dark:border-slate-700 dark:bg-slate-800 dark:text-white"
          placeholder="••••••••">
      </div>

      <!-- Submit -->
      <button type="submit"
        class="w-full rounded-lg bg-gradient-to-br from-rose-500 to-orange-400 px-4 py-2 text-white shadow-lg hover:opacity-90">
        Register
      </button>
    </form>

    <!-- Back to login link -->
    <p class="mt-6 text-center text-sm text-slate-600 dark:text-slate-300">
      Already have an account?
      <a href="<?php echo e(url('/login')); ?>" class="text-rose-600 hover:underline dark:text-rose-400">
        Log in
      </a>
    </p>
  </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startPush('scripts'); ?>
<script>
const registerForm = document.getElementById('registerForm');
const errorMsg = document.getElementById('errorMsg');

registerForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const name = document.getElementById('name').value.trim();
    const email = document.getElementById('email').value.trim();
    const password = document.getElementById('password').value.trim();
    const password_confirmation = document.getElementById('password_confirmation').value.trim();

    if (password !== password_confirmation) {
        errorMsg.textContent = 'Passwords do not match.';
        return;
    }

    try {
        const response = await axios.post('/api/auth/register', new URLSearchParams({ name, email, password, password_confirmation }), {
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
        });

        if (response.data.status === 'success') {
            errorMsg.textContent = '';
            alert(response.data.message || 'Registration successful!');
            window.location.href = '/login';
        } else {
            errorMsg.textContent = response.data.message || 'Registration failed';
        }

    } catch (err) {
        if (err.response) {
            errorMsg.textContent = err.response.data.message || 'Registration failed';
        } else {
            errorMsg.textContent = 'Server unreachable. Please try again.';
        }
    }
});
</script>
<?php $__env->stopPush(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Code Base\Frameworks\TinyPHP\App\resources\views\app\auth/register.blade.php ENDPATH**/ ?>