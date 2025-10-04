

<?php $__env->startSection('title', 'Page Not Found'); ?>

<?php $__env->startSection('content'); ?>
<div class="min-h-screen flex flex-col items-center justify-center bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200">
    <div class="w-full max-w-6xl px-6 py-10">
        
        <div class="text-center mb-8">
            <h1 class="text-6xl font-extrabold tracking-tight mb-8 text-red-600">404 | Not Found</h1>            
        </div>

         <?php if(config('app.debug')): ?>
            
            <?php if(isset($exception)): ?>
                <div class="bg-white dark:bg-gray-800 rounded-xl shadow-lg border border-gray-200 dark:border-gray-700 p-6">
                    
                    <h3 class="font-mono font-semibold text-lg mb-2 text-gray-900 dark:text-gray-100">
                        <?php echo e(get_class($exception)); ?>

                    </h3>

                    
                    <p class="text-red-600 dark:text-red-400 font-medium mb-4">
                        <?php echo nl2br(e($exception->getMessage())); ?>

                    </p>

                    
                    <p class="text-sm text-gray-600 dark:text-gray-400 mb-6">
                        In <span class="font-mono"><?php echo e($exception->getFile()); ?></span> at line 
                        <span class="font-mono"><?php echo e($exception->getLine()); ?></span>
                    </p>

                    
                    <h4 class="font-semibold text-md mb-3 text-gray-900 dark:text-gray-100">Stack Trace:</h4>
                    <div class="overflow-x-auto">
                        <pre class="whitespace-pre-wrap break-words text-xs leading-relaxed bg-gray-100 dark:bg-gray-900 text-gray-700 dark:text-gray-300 p-4 rounded-lg"><?php echo e($exception->getTraceAsString()); ?></pre>
                    </div>
                </div>
            <?php endif; ?>
        <?php endif; ?>

    </div>
</div>
<?php $__env->stopSection(); ?>
<?php echo $__env->make('layouts.error', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH E:\Code Base\Major\YRC-SaaS\TinyPHP\Views\errors/404.blade.php ENDPATH**/ ?>