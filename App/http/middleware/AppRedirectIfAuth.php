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
 *      redirected back to the page they came from, or /dashboard as fallback.
 *    - Intended URL is resolved from (in priority order):
 *        a) ?redirect= query param  — set by Middleware_AppAuth on server-side redirects
 *        b) HTTP_REFERER header     — set by the browser on JS-initiated redirects (window.location.href)
 *        c) /dashboard              — fallback
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

    private array $guestPaths = [
        '/login', '/register', '/forgot-password', '/reset-password',
    ];

    protected function process(TinyPHP_Request $request, Closure $next) {

        if( !auth()->check() ) {

            // Renew access token silently if has valid refresh token
            $refreshToken = cookie("refresh_token");
            if ($refreshToken) {
                auth()->renewAccessToken($refreshToken, "web");
            }
        }

        if( auth()->check() ) {

            $controllerName = $request->getControllerName();
            $actionName = $request->getActionName();

            if( isset($this->redirectIfAuthenticated[$controllerName]) && in_array($actionName, $this->redirectIfAuthenticated[$controllerName]) ) {
                redirect($this->resolveIntendedUrl($request));
            }
        }


        return $next($request);
    }

    private function resolveIntendedUrl(TinyPHP_Request $request): string {
        // Priority 1: explicit ?redirect= param (set by Middleware_AppAuth)
        $param = $request->getInput("redirect", "String", "");
        if ($param && $this->isSafePath($param)) {
            return $param;
        }

        // Priority 2: HTTP Referer (browser sets this on window.location.href navigations)
        $referer = $_SERVER['HTTP_REFERER'] ?? '';
        if ($referer) {
            $path = parse_url($referer, PHP_URL_PATH) ?? '';
            if ($path && $this->isSafePath($path) && !in_array(rtrim($path, '/'), $this->guestPaths)) {
                return $path;
            }
        }

        return '/dashboard/';
    }

    private function isSafePath(string $path): bool {
        // Must be a root-relative path (starts with exactly one slash, no protocol)
        return (bool) preg_match('/^\/[^\/]/', $path);
    }
}