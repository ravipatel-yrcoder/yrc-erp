<?php
/**
 * Middleware_AppAuth
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
 *    - If no valid token is available (access or refresh), redirects to /login.
 *
 * 4. Subscription Check:
 *    - Verifies the company's subscription is accessible (trial/pilot/active
 *      and not expired). Redirects to /subscription/expired if not.
 *
 * 5. Hydrate TenantContext:
 *    - Builds and hydrates a Service_TenantContext (subscription state +
 *      user accessible feature keys) and stores it via tenantContext() helper
 *      so controllers and views can access it without re-querying.
 *
 * Exceptions ($except):
 *    - front / auth routes bypass all checks (public pages, login).
 *    - subscriptionexpired bypasses subscription check (prevents redirect loop).
 *    - salesorders::printView bypasses auth (shareable print link).
 */
class Middleware_AppAuth extends TinyPHP_Middleware {

    protected array $except = [
        "front" => "*",
        "auth" => "*",
        "companies" => ["register", "activate"],
        "subscriptionexpired" => "*",
        "salesorders" => "printView",
    ];

    protected function process(TinyPHP_Request $request, Closure $next) {

        // --- 1. Authentication ---
        if (!auth()->check()) {
            $refreshToken = cookie("refresh_token");
            if ($refreshToken) {
                auth()->renewAccessToken($refreshToken, "web");
            }
        }

        if (!auth()->check()) {
            $intendedUrl = $_SERVER['REQUEST_URI'] ?? '';
            $loginUrl = $intendedUrl ? '/login?redirect=' . urlencode($intendedUrl) : '/login';
            redirect($loginUrl);
        }

        $user = auth()->user();
        $companyId = $user->company_id;
        $userId = $user->id;
        
        // --- 2. Subscription check ---
        $subService = new Service_Subscription();
        if (!$subService->isAccessible($companyId)) {
            redirect("/subscription/expired/");
        }        

        // --- 3. Hydrate TenantContext ---
        $context = new Service_TenantContext($companyId, $userId);
        $context->hydrate();

        // Store globally — accessible anywhere via tenantContext() helper
        tenantContext($context);

        $route = $request->getRoute();
        $accessKeys = $route["access_keys"] ?? [];

        // check feature access for non-admin user
        foreach ($accessKeys as $key) {
            if (!$context->canAccess($key)) {
                abort(403, "Access Forbidden");
            }
        }        

        return $next($request);
    }
}
