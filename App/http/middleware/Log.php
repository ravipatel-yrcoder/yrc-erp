<?php
class Middleware_Log extends TinyPHP_Middleware {

    protected array $except = [];

    protected function process(TinyPHP_Request $request, Closure $next) {
        return $next($request);
    }
}