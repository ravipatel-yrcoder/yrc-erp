<?php
class Middleware_ApiAuth extends TinyPHP_Middleware {

    protected array $except = [
        //"index" => "*", // bypass all actions in index controller
        "auth" => ["login", "refreshToken", "register"],
    ];

    protected function process(TinyPHP_Request $request, Closure $next) {
        
        if( !auth()->check() ) {
            return response([], 'Unauthorized access', 401)->sendJson();
        }

        return $next($request);
    }
}