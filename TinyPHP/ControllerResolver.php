<?php
class TinyPHP_ControllerResolver
{
    private static $cache = []; // in-memory cache

    /**
     * Resolve a controller
     *
     * @param string $module
     * @param string $controller
     * @param array $moduleDirs Mapping from module name to directory
     * @return object Controller instance
     * @throws Exception
     */
    public static function resolve(string $module, string $controller, string $modulePath, bool $forceRefresh = false)
    {
        list($file, $class) = self::resolveFile($module, $controller, $modulePath, $forceRefresh);

        require_once $file;

        if (!class_exists($class)) {
            throw new TinyPHP_Exception('[Controller Error] Controller class not found: "'.$class.'"', 500);
        }

        // Instantiate and cache
        $controllerObj = new $class;

        if (!$controllerObj instanceof TinyPHP_Controller) {
            throw new TinyPHP_Exception('[Controller Error] Invalid controller inheritance: "'.$class.'". All controllers must extend "TinyPHP_Controller"', 500);
        }

        return $controllerObj;
    }


    private static function resolveFile(string $moduleName, string $controllerName, string $modulePath, bool $forceRefresh = false) {

        $cacheKey = strtolower($moduleName . '_' . $controllerName);

        if (!$forceRefresh && isset(self::$cache[$cacheKey])) {
            return self::$cache[$cacheKey];
        }

        $bareControllerClassName = ucfirst($controllerName) . 'Controller';
        $controllerClassName = ($moduleName !== 'app' ? ucfirst($moduleName) . '_' : '') . $bareControllerClassName;

        $controllerDir = rtrim($modulePath, '/');
        $controllerFile = $controllerDir . '/' . ucfirst($controllerName) . 'Controller.php';

        if (!file_exists($controllerFile)) {
            //throw new \Exception("Controller file not found: $controllerFile");
            throw new TinyPHP_Exception("[Controller Error] Controller file not found: ".normalizePath($controllerFile), 404);
        }

        self::$cache[$cacheKey] = [$controllerFile, $controllerClassName];

        return self::$cache[$cacheKey];
    }
}