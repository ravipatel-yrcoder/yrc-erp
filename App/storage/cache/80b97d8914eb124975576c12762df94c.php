<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title><?php if (! empty(trim($__env->yieldContent('title')))): ?>TinyPHP | <?php echo $__env->yieldContent('title'); ?> <?php else: ?> TinyPHP <?php endif; ?></title>

    <script src="https://cdn.tailwindcss.com"></script>
    <meta name="color-scheme" content="light dark" />
    <style>
        :root { --ring: 0 0% 0%; }
        @media (prefers-color-scheme: dark) {
        :root { --ring: 0 0% 100%; }
        }
    </style>
    <?php echo $styleSheets; ?>

    <?php echo $headerScripts; ?>

</head>
<body class="">
    <?php echo $__env->yieldContent('content'); ?>
    <?php echo $footerScripts; ?>

</body>
</html><?php /**PATH E:\Code Base\Frameworks\TinyPHP\TinyPHP\Views/layouts/layout.blade.php ENDPATH**/ ?>