<?php
/**
 * ===============================
 * CONFIG / ENV / SETTINGS
 * ===============================
 */

/**
 * Retrieve a configuration value using TinyPHP_ConfigLoader.
 *
 * @param string $key
 * @param mixed $default
 * @return mixed
 */
function config(string $key, mixed $default = null): mixed
{
    return TinyPHP_ConfigLoader::get($key, $default);
}

/**
 * ===============================
 * URL / PATH / REDIRECT HELPERS
 * ===============================
 */

/**
 * Generate a full URL for a given path.
 *
 * @param string $path
 * @return string
 */
function url(string $path = ''): string
{
    $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return rtrim($scheme . '://' . $host, '/') . '/' . ltrim($path, '/');
}

/**
 * Generate a full asset URL.
 *
 * @param string $path
 * @return string
 */
function asset(string $path = ''): string
{
    return url(ltrim($path, '/'));
}

/**
 * Generate a secure HTTPS URL.
 *
 * @param string $path
 * @return string
 */
function secureUrl(string $path = ''): string
{
    $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
    return 'https://' . rtrim($host, '/') . '/' . ltrim($path, '/');
}

/**
 * Generate a secure HTTPS asset URL.
 *
 * @param string $path
 * @return string
 */
function secureAsset(string $path = ''): string
{
    return secureUrl('public/' . ltrim($path, '/'));
}

/**
 * Get the path to the public directory.
 *
 * @param string $path
 * @return string
 */
function publicPath(string $path = ''): string
{
    return __DIR__ . '/../../public' . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Get the path to the storage directory.
 *
 * @param string $path
 * @return string
 */
function storagePath(string $path = ''): string
{
    return __DIR__ . '/../../storage' . ($path ? '/' . ltrim($path, '/') : '');
}

/**
 * Redirect to a URL (302 by default).
 *
 * @param string $to
 * @param int $statusCode
 * @return void
 */
function redirect(string $to, int $statusCode = 302): void
{
    header("Location: " . url($to), true, $statusCode);
    exit;
}

/**
 * ===============================
 * ERROR / ABORT HELPERS
 * ===============================
 */

/**
 * Abort execution with a status code and message.
 *
 * @param int $statusCode
 * @param string $message
 * @return void
 */
function abort(int $statusCode = 404, string $message = 'Page Not Found'): void
{
    http_response_code($statusCode);
    die($message);
}

/**
 * Abort execution if a condition is true.
 *
 * @param bool $condition
 * @param int $statusCode
 * @param string $message
 * @return void
 */
function abortIf(bool $condition, int $statusCode = 404, string $message = 'Page Not Found'): void
{
    if ($condition) {
        abort($statusCode, $message);
    }
}

/**
 * ===============================
 * AUTH / SESSION HELPERS
 * ===============================
 */

/**
 * Get the currently authenticated user from session.
 *
 * @return mixed|null
 */
function auth(): Service_Auth {
    static $instance = null;
    if ($instance === null) {
        $instance = new Service_Auth();
    }
    return $instance;
}

/**
 * Get or set session values.
 *
 * @param string|null $key
 * @param mixed|null $default
 * @return mixed
 */
function session(?string $key = null, mixed $default = null): mixed
{
    if ($key === null) {
        return $_SESSION;
    }
    return $_SESSION[$key] ?? $default;
}

/**
 * Get a value from the cookie
 *
 * @param string $name  The cookie name
 * @param mixed $default Default value if cookie not set
 * @return mixed
 */
function cookie(string $name, $default = null) {
    return $_COOKIE[$name] ?? $default;
}

/**
 * ===============================
 * CSRF HELPERS
 * ===============================
 */

/**
 * Generate a CSRF hidden input field for forms.
 *
 * @return string
 */
function csrfField(): string
{
    $token = TinyPHP_Session::generateCSRFToken();
    return '<input type="hidden" name="_token" value="' . $token . '">';
}

/**
 * ===============================
 * REQUEST / RESPONSE HELPERS
 * ===============================
 */

/**
 * Get request parameter or entire request object.
 *
 * @param string|null $key
 * @param mixed|null $default
 * @return mixed|TinyPHP_Request
 */
function request(?string $key = null, mixed $default = null): mixed
{
    static $request;

    if (!$request) {
        $request = TinyPHP_Request::getInstance();
    }

    if ($key !== null) {
        return $request->getInput($key, $default);
    }

    return $request;
}

/**
 * Create a standard API response object.
 *
 * @param array|object $data
 * @param string $message
 * @param int $code
 * @param mixed|null $errors
 * @param mixed|null $meta
 * @return TinyPHP_Response
 */
function response(array $data = [], string $message = '', int $code = 200): TinyPHP_Response
{
    $resp = new TinyPHP_Response($data, $message, $code);
    return $resp;
}

/**
 * ===============================
 * LOGGING HELPERS
 * ===============================
 */

/**
 * Simple logger function to write messages to storage/logs/app.log
 *
 * @param string $message
 * @param string $level
 * @return void
 */
function logger(string $message, string $level = 'info'): void
{
    $logFile = __DIR__ . '/../../storage/logs/app.log';
    $date = date('Y-m-d H:i:s');
    $line = "[$date] " . strtoupper($level) . ": " . $message . PHP_EOL;
    file_put_contents($logFile, $line, FILE_APPEND);
}

/**
 * ===============================
 * PASSWORD HASHING HELPERS
 * ===============================
 */

/**
 * Hash a password using TinyPHP_Hash.
 *
 * @param string $password
 * @return string
 */
function hashPassword(string $password): string
{
    return TinyPHP_Hash::make($password);
}

/**
 * Verify a password against a hash.
 *
 * @param string $password
 * @param string $hash
 * @return bool
 */
function verifyPassword(string $password, string $hash): bool
{
    return TinyPHP_Hash::check($password, $hash);
}

/**
 * Determine if a password hash needs rehashing.
 *
 * @param string $hash
 * @return bool
 */
function needsRehash(string $hash): bool
{
    return TinyPHP_Hash::needsRehash($hash);
}



/**
 * ===============================
 * ROLE-BASED ACCESS HELPERS
 * ===============================
 */

/**
 * Check if the currently logged-in user has a specific role.
 *
 * Usage:
 * if (userHasRole('admin')) { ... }
 *
 * @param string $role
 * @return bool
 */
function userHasRole(string $role): bool
{
    $user = auth();
    if (!$user) return false;

    return isset($user['role']) && $user['role'] === $role;
}

/**
 * Abort execution if the currently logged-in user does not have the required role.
 *
 * Usage:
 * requireRole('admin'); // Aborts with 403 if user is not admin
 *
 * @param string $role
 * @return void
 */
function requireRole(string $role): void
{
    if (!userHasRole($role)) {
        abort(403, 'Forbidden: insufficient permissions');
    }
}

function normalizePath(string $path): string {
    return str_replace(['\\', '//'], '/', $path);
}