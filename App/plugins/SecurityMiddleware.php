<?php
class Plugins_SecurityMiddleware {

    public function preDispatch(TinyPHP_Request $request)
    {
        // Set security headers
        $this->setSecurityHeaders();
        
        // Validate CSRF token for POST requests
        if ($request->isPost()) {
            $this->validateCSRF($request);
        }
        
        // Rate limiting check
        $this->checkRateLimit();
    }

    private function setSecurityHeaders()
    {
        // Prevent clickjacking
        header('X-Frame-Options: DENY');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection
        header('X-XSS-Protection: 1; mode=block');
        
        // Strict transport security (HTTPS only)
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
        
        // Content Security Policy
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline'; img-src 'self' data:;");
    }

    private function validateCSRF(TinyPHP_Request $request)
    {
        // Skip CSRF validation for API endpoints
        if ($request->getModuleName() === 'api') {
            return;
        }

        $token = $request->getParam('csrf_token') ?: $_POST['csrf_token'] ?? null;
        
        if (!$token || !TinyPHP_Session::validateCSRFToken($token)) {
            http_response_code(403);
            die('CSRF token validation failed');
        }
    }

    private function checkRateLimit()
    {
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'rate_limit_' . $ip;
        
        $current = TinyPHP_Session::get($key) ?: 0;
        $time = time();
        
        // Allow 100 requests per minute
        if ($current > 100) {
            http_response_code(429);
            die('Rate limit exceeded');
        }
        
        TinyPHP_Session::set($key, $current + 1);
        
        // Reset counter every minute
        if ($time % 60 === 0) {
            TinyPHP_Session::set($key, 1);
        }
    }

    public function postDispatch(TinyPHP_Request $request)
    {
        // Regenerate CSRF token after each request
        if ($request->isPost()) {
            TinyPHP_Session::regenerateCSRFToken();
        }
    }
}
?>
