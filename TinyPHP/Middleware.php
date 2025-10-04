<?php
abstract class TinyPHP_Middleware {
    
    protected array $except = [];

    abstract protected function process(TinyPHP_Request $request, Closure $next);

    public function handle(TinyPHP_Request $request, Closure $next) {

        $controllerName = $request->getControllerName();
        $actionName = $request->getActionName();

        if ($this->shouldBypass($controllerName, $actionName)) {
            return $next($request);
        }

        return $this->process($request, $next);
    }

    protected function shouldBypass(string $controllerName, string $actionName): bool
    {
        if (!isset($this->except[$controllerName])) {
            return false;
        }

        // always make it array
        $rules = (array) $this->except[$controllerName];

        // If wildcard is present
        if (in_array('*', $rules, true)) {
            return true;
        }

        return in_array($actionName, $rules, true);
    }
}