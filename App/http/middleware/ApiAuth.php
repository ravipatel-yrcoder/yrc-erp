<?php
/**
 * Middleware_ApiAuth
 *
 * Responsibilities:
 * 1. Authentication:
 *    - Validates the JWT Bearer token from the Authorization header.
 *    - Returns 401 if missing or invalid.
 *
 * 2. Subscription Check:
 *    - Verifies the company's subscription is accessible (trial/pilot/active
 *      and not expired).
 *    - Returns 402 if the subscription is expired, cancelled, or suspended.
 *
 * 3. Hydrate TenantContext:
 *    - Builds and hydrates a Service_TenantContext (subscription state +
 *      user accessible feature keys) and stores it via tenantContext() helper
 *      so controllers can access it without re-querying.
 *
 * Exceptions ($except):
 *    - auth::login, auth::refreshToken, auth::register bypass all checks.
 *    - webhooks bypass JWT auth (authenticate via token in URL instead).
 */
class Middleware_ApiAuth extends TinyPHP_Middleware {

    protected array $except = [
        "front"     => "*",
        "auth"      => ["login", "refreshToken", "forgotPassword", "resetPassword"],
        "companies" => ["register", "activate"],
        "webhooks"  => "*",
    ];

    protected function process(TinyPHP_Request $request, Closure $next) {

        $route = $request->getRoute();
        $allowedMethods = array_map('strtoupper', $route["allowed_methods"]);
        $requestMethods = strtoupper($request->getMethod());

        if( !in_array($requestMethods, $allowedMethods) ) {
            return methodNotAllowed();
        }

        // --- 1. Authentication ---
        if (!auth()->check()) {
            return response([], 'Unauthorized access', 401)->sendJson();
        }

        $companyId = (int) auth()->getCompanyId();
        $userId = (int) auth()->user()->id;


        // --- 2. Subscription check ---
        $subService = new Service_Subscription();
        if (!$subService->isAccessible($companyId)) {
            return response([], 'Subscription expired or inactive', 402)->sendJson();
        }

        // --- 3. Hydrate TenantContext ---
        $context = new Service_TenantContext($companyId, $userId);
        $context->hydrate();

        // Store globally — accessible anywhere via tenantContext() helper
        tenantContext($context);


        
        $accessKeys = $route["access_keys"] ?? [];

        // check feature access for non-admin user
        foreach ($accessKeys as $key) {
            if (!$context->canAccess($key)) {
                abort(403, "Access Forbidden", "access_denied");
            }
        }

        return $next($request);
    }
}
