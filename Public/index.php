<?php
define('ROOT_PATH', dirname(__DIR__ ));
define('APP_PATH', ROOT_PATH . '/App');
define('TINY_PHP_PATH', ROOT_PATH . '/TinyPHP');
        

// Composer autoload
require ROOT_PATH . '/vendor/autoload.php';

// Custom autoloader
require TINY_PHP_PATH . '/Loader.php';

// Support helper functions
require TINY_PHP_PATH . '/Support/helpers.php';

// Front file
require TINY_PHP_PATH . '/Front.php';

// Bootstrap the app
$app = require APP_PATH . '/bootstrap/app.php';

// Dispatch request
$app->dispatch();

// Destroy
unset($app);