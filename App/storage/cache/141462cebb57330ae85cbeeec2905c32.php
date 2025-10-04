<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php if (! empty(trim($__env->yieldContent('title')))): ?>TinyPHP | <?php echo $__env->yieldContent('title'); ?> <?php else: ?> TinyPHP <?php endif; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://cdn.jsdelivr.net/npm/axios/dist/axios.min.js"></script>
    <meta name="color-scheme" content="light dark" />
    <style>
        :root { --ring: 0 0% 0%; }
        @media (prefers-color-scheme: dark) {
        :root { --ring: 0 0% 100%; }
        }
    </style>
    
    <!-- Global JS -->
    <script src="<?php echo e(asset('js/app.js')); ?>"></script>

</head>
<body class="">
    <?php echo $__env->yieldContent('content'); ?>
    
     <!-- View-specific JS stack -->
    <?php echo $__env->yieldPushContent('scripts'); ?>

</body>
</html><?php /**PATH E:\Code Base\Major\YRC-SaaS\App\resources\views/layouts/app.blade.php ENDPATH**/ ?>