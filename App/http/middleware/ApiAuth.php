<?php
class Middleware_ApiAuth extends TinyPHP_Middleware {

    protected array $except = [
        //"index" => "*", // bypass all actions in index controller
        "auth" => ["login", "register"],
    ];

    protected function process(TinyPHP_Request $request, Closure $next) {
        
        if( !Service_Auth::check() ) {
            return response([], 'Unauthorized', 401)->sendJson();
        }

        return $next($request);
    }
}