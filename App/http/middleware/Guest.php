<?php
class Middleware_Guest extends TinyPHP_Middleware {

    protected array $except = [];

    protected function process(TinyPHP_Request $request, Closure $next) {
        
        // Redirect to dashboard when user is loggedin and try to access front/login, register, forgotpassword, resetpassword, etc
        if( auth()->check() ) {

            $controllerName = $request->getControllerName();
            $actionName = $request->getActionName();

            if( strtoupper($controllerName) == "AUTH" ) {

                $guestActions = ["login", "register", "forgotpassword", "resetpassword"];
                if( in_array($actionName, $guestActions) ) {
                    redirect("/dashboard");        
                }
            }
        }
        
        return $next($request);
    }
}