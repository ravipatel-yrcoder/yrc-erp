<?php
class Middleware_Csrf extends TinyPHP_Middleware {

    protected array $except = [];

    protected function process(TinyPHP_Request $request, Closure $next) {

        $requestMethod = $request->getMethod();

        // CSRF does not require other then this request methods
        if( !in_array($requestMethod, ["post", "put", "patch", "delete"]) ) {
            return $next($request);
        }

        // If Authorization, do not require CSRF validation. Consider it as safe request
        $csrf = $request->getCsrfToken();

        if ( !$csrf || !TinyPHP_Session::validateCSRFToken($csrf) ) {
            
            if( $request->expectedJson() ) {
                response([], "Your session has expired or the request could not be processed", 403)->sendJson();
            } else {
                throw new TinyPHP_Exception("Session has expired", 403);
            }
        }

        return $next($request);
    }

}