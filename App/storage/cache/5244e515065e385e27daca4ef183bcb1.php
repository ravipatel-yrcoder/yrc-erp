

<?php $__env->startSection('title', 'Server Error'); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
    <div class="text-center px-4">
        <h1 class="text-9xl font-extrabold tracking-tight mb-4 text-red-600">500</h1>
        <h2 class="text-3xl md:text-4xl font-semibold mb-6">Server Error</h2>        
    </div>

    <?php if(isset($errorInfo)): ?>
        <div class="text-left p-5"><pre><?php echo e($errorInfo); ?></pre></div>
    <?php endif; ?>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.error', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Code Base\Frameworks\TinyPHP\TinyPHP\Views/errors/500.blade.php ENDPATH**/ ?>