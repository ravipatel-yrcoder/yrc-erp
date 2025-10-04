<?php $__env->startSection('title', 'Welcome'); ?>
<?php $__env->startSection('bodyClasses', 'antialiased bg-gradient-to-br from-white to-slate-100 dark:from-slate-950 dark:to-slate-900 min-h-screen'); ?>

<?php $__env->startSection('content'); ?>
<div class="relative flex min-h-screen flex-col items-center justify-center px-6">
    <!-- Top-right auth/links placeholder (optional) -->
    <div class="absolute right-6 top-6 flex items-center gap-4 text-sm">
      <!-- Example placeholders; replace with real routes -->
      <a href="<?php echo e(url('/login')); ?>" class="text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">Log in</a>
      <a href="<?php echo e(url('/register')); ?>" class="inline-flex items-center rounded-lg border px-3 py-1.5 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-800">
        Register
      </a>
      <?php if(Service_Auth::check()): ?>
      <a href="javascript:void(0);" id="logout_btn" class="inline-flex items-center rounded-lg border px-3 py-1.5 text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:border-slate-700 dark:hover:bg-slate-800">Logout</a>
      <?php endif; ?>
    </div>

    <!-- Logo / Title -->
    <div class="mb-10 text-center">
      <!-- Simple mark (you can swap with your SVG/logo) -->
      <div class="mx-auto mb-6 h-16 w-16 rounded-2xl bg-gradient-to-br from-rose-500 to-orange-400 shadow-lg"></div>
      <h1 class="text-3xl font-semibold tracking-tight text-slate-900 dark:text-white">
        Welcome
      </h1>
      <p class="mt-2 text-slate-600 dark:text-slate-300">
        Your PHP MVC app with Blade & Eloquent is up and running.
      </p>
    </div>

    <!-- Card --> 
    <div class="w-full max-w-2xl group block rounded-xl border border-slate-200 p-6 hover:border-slate-300 hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800">
        <div class="">
            
            <a href="javascript:void(0);">
                <div class="text-lg font-semibold text-slate-900 dark:text-white">
                    PHP MVC Framework
                </div>
                <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                    A lightweight PHP MVC framework with Blade & Eloquent.  
                    Perfect for building modern SaaS and ERP apps.
                </p>
                <span class="mt-3 inline-flex items-center text-sm text-rose-600 group-hover:underline dark:text-rose-400">
                    View on GitHub →
                </span>
            </a>

        </div>
    </div>

    <p class="mt-6 text-center text-sm text-slate-500 dark:text-slate-400">Edit this template at <code>resources/views/index.blade.php</code>.</p>


    <!-- Footer -->
    <div class="mt-10 text-center text-sm text-slate-500 dark:text-slate-400">Happy coding!</div>
  </div>
<?php $__env->stopSection(); ?>

<?php if(Service_Auth::check()): ?>
  <?php $__env->startPush('scripts'); ?>
  <script>
  const logoutBtn = document.getElementById('logout_btn');
  logoutBtn.addEventListener('click', async (e) => {
      e.preventDefault();
      try {
          const response = await axios.post('/api/auth/logout', {}, {headers: {'X-Client-Type': 'web'}});
          if (response.data.status === 'success') {
              window.location.href = '/login';
          } else {
              alert(response.data.message);
          }
      } catch (err) {

        if (err.response && err.response.data) {
            alert(err.response.data.message);
        } else {
            alert("Server unreachable. Please try again.");
        }
      }
  });  
  </script>
  <?php $__env->stopPush(); ?>
<?php endif; ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Code Base\Major\YRC-SaaS\App\resources\views\app\front/home.blade.php ENDPATH**/ ?>