<?php
/**
 * Middleware_AppRedirectIfAuth
 *
 * This middleware handles two main responsibilities:
 *
 * 1. Silent Token Renewal:
 *    - If the request has an expired/missing access token but a valid refresh token,
 *      it will attempt to renew the access token automatically.
 *
 * 2. Redirect Authenticated Users from Guest Pages:
 *    - If the user is already authenticated and tries to access a guest-only page
 *      (e.g., login, register, forgot password, reset password), they will be
 *      redirected to the home/dashboard page instead.
 *
 * Usage:
 * - Attach this middleware to all routes of the specific module.
 * - Configure the $redirectIfAuthenticated array to list controller/actions
 *   that should redirect when the user is logged in.
 */
class Middleware_AppRedirectIfAuth extends TinyPHP_Middleware {

    protected array $except = [];
    
    private array $redirectIfAuthenticated = [
        "auth" =>  ["login", "register", "forgotpassword", "resetpassword"]
    ];

    protected function process(TinyPHP_Request $request, Closure $next) {
        
        if( !Service_Auth::check() ) {
            
            // Renew access token silently if has valid refresh token
            $refreshToken = cookie("refresh_token");
            if ($refreshToken) {
                Service_Auth::renewAccessToken($refreshToken, "web");
            }
        }


        if( Service_Auth::check() ) {

            $controllerName = $request->getControllerName();
            $actionName = $request->getActionName();

            if( isset($this->redirectIfAuthenticated[$controllerName]) && in_array($actionName, $this->redirectIfAuthenticated[$controllerName]) ) {
                redirect("/about-us");
            }
        }


        return $next($request);
    }
}