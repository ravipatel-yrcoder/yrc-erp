<?php $__env->startSection('title', 'Register'); ?>
<?php $__env->startSection('bodyClasses', 'antialiased bg-gradient-to-br from-white to-slate-100 dark:from-slate-950 dark:to-slate-900 min-h-screen'); ?>

<?php $__env->startSection('content'); ?>
<!--<body class="bg-gray-100 flex items-center justify-center min-h-screen">-->

<div class="bg-white shadow-lg rounded-lg p-8 w-full max-w-md">
    <h1 class="text-2xl font-semibold text-center text-gray-800 mb-6">Login to Your Account</h1>

    <form method="POST" action="/login" class="space-y-4">        
        <?php echo csrfField(); ?>
        <!-- Email -->
        <div>
            <label for="email" class="block text-gray-600 mb-1">Email Address</label>
            <input type="email" id="email" name="email" required placeholder="you@example.com"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        <!-- Password -->
        <div>
            <label for="password" class="block text-gray-600 mb-1">Password</label>
            <input type="password" id="password" name="password" required placeholder="••••••••"
                class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:outline-none">
        </div>

        <!-- Remember me -->
        <div class="flex items-center">
            <input type="checkbox" id="remember" name="remember" class="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded">
            <label for="remember" class="ml-2 text-gray-700 text-sm">Remember me</label>
        </div>

        <button type="submit"
            class="w-full bg-blue-600 text-white py-2 px-4 rounded-lg hover:bg-blue-700 transition-colors font-medium">
            Login
        </button>
    </form>

    <div class="mt-6 text-center text-sm text-gray-600 space-y-2">
        <p><a href="/forgot-password" class="text-blue-600 hover:underline">Forgot your password?</a></p>
        <p>Don't have an account? <a href="/register" class="text-blue-600 hover:underline">Sign up</a></p>
    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.app', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Code Base\Frameworks\TinyPHP\App\resources\views\app\authentication/register.blade.php ENDPATH**/ ?>