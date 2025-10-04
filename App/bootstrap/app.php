<?php

// Init Front Controller
$front = TinyPHP_Front::getInstance();


// Load environment variables
TinyPHP_EnvLoader::load(APP_PATH);


// Register exception hanlder
(new TinyPHP_Exception())->register();


// Load config variables
TinyPHP_ConfigLoader::load(APP_PATH);


// default timezone
date_default_timezone_set(config('app.timezone'));


// Start session
TinyPHP_Session::init();


// Register modules
$front->registerModules(config("modules"));


// Register middlewares
$front->registerMiddlewares(config("middlewares"));


// Map all rountes from the route files
$router = TinyPHP_Router::getInstance();
$router->mapRoutes();


return $front;