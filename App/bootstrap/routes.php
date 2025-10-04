<?php
$routesDir = APP_PATH."/routes";
$routeFiles = glob($routesDir . '/*.php');

if( $routeFiles )
{
    $router = TinyPHP_Router::getInstance();

    foreach ($routeFiles as $file) {
        
        $routesData = require $file; // Load each module's routes

        $module = trim($routesData["module"] ?? "");
        $prefix = trim($routesData["prefix"] ?? "");
        $routes = $routesData["routes"] ?? [];        
        $routes =  is_array($routes) ? $routes : [];

        if( $module )
        {
            foreach ($routes as $controller => $actions) {

                $controller = trim($controller);
                $actions =  is_array($actions) ? $actions : [];
                foreach ($actions as $actionRoute) {
                    
                    $pattern = trim($actionRoute["pattern"] ?? "");
                    $action  = trim($actionRoute["action"] ?? "");
                    $name = trim($actionRoute["name"] ?? "");
                    $skipPrefix = (boolean) ($actionRoute["skipPrefix"] ?? false);

                    if( $prefix && $skipPrefix === false ) {
                        $pattern = "/" . trim($prefix, "/") . "/" . ltrim($pattern, "/");
                    }

                    if( $controller && $pattern && $action ) {

                        $routeName = "{$module}_{$controller}_{$action}";
                        if( $name !== "" ) {
                            $routeName .= "_{$name}";
                        }

                        $router->addRoute($routeName, new TinyPHP_Route($pattern, ['module' => $module, 'controller' => $controller, 'action' => $action]));
                    }
                }
            }
        }
    }
}