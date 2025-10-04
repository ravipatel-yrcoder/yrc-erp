<?php
/**
 * Middleware_AppAuth
 *
 * This middleware is responsible for protecting application routes
 * that require authentication. It ensures that the incoming request
 * has a valid access token and silently renews it if possible.
 *
 * Responsibilities:
 * 1. Access Token Validation:
 *    - Validates the `access_token` from cookies.
 *
 * 2. Silent Token Renewal:
 *    - If the access token is missing or expired, attempts to renew it
 *      using the `refresh_token` cookie.
 *
 * 3. Enforce Authentication:
 *    - If no valid token is available (access or refresh), the user
 *      is redirected to the login page.
 *
 * Exceptions:
 * - The $except property allows certain controllers/actions
 *   (e.g., "front", "auth") to bypass authentication.
 *
 * Usage:
 * - Attach this middleware to routes or globally for areas that
 *   require authenticated users.
 */

class Middleware_AppAuth extends TinyPHP_Middleware {

    protected array $except = [
        "front" => "*",
        "auth" => "*",
    ];

    protected function process(TinyPHP_Request $request, Closure $next) {

        if( !Service_Auth::check() ) {

            // Refresh Access Token using Refresh Token
            $refreshToken = cookie("refresh_token");
            if ($refreshToken) {
                Service_Auth::renewAccessToken($refreshToken, "web");
            }
        }


        if( !Service_Auth::check() ) {
            redirect("/login");
        }
        
        return $next($request);
    }
}