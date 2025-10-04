<?php

abstract class TinyPHP_Session {

    public static function regenerate($_deleteOldSession=false)
    {
    	return session_regenerate_id($_deleteOldSession);
    }

    public static function getSessionId() {
        return session_id();
    }

    public static function init() {
        
        if (session_status() !== PHP_SESSION_ACTIVE) {

            ini_set('session.cookie_httponly', 1);
            ini_set('session.use_strict_mode', 1);
            ini_set('session.cookie_samesite', 'Strict');

            if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
                ini_set('session.cookie_secure', 1);
            }

            session_start();
        }


        // Periodic session regeneration
        if (!isset($_SESSION['last_regeneration'])) {
            $_SESSION['last_regeneration'] = time();
        } elseif (time() - $_SESSION['last_regeneration'] > 300) {
            session_regenerate_id(true);
            $_SESSION['last_regeneration'] = time();
        }

        // CSRF protection token
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    public static function destroy($key='') {
    	
        if(empty($key))
    	{
            $_SESSION = [];
            if (ini_get("session.use_cookies")) {
                $params = session_get_cookie_params();
                setcookie(session_name(), '', time() - 42000,
                    $params["path"], $params["domain"],
                    $params["secure"], $params["httponly"]
                );
            }

        	session_destroy();
    	}
    	else
    	{
    		if (isset($_SESSION[$key])) {
    			unset($_SESSION[$key]);
    		}
    	}
    }

    public static function set($name, $value) {

        $_SESSION[$name] = $value;
    }

    public static function get($name, $default=null) {
        return $_SESSION[$name] ?? $default;
    }

    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    /**
     * Validate CSRF token
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Regenerate CSRF token
     */
    public static function regenerateCSRFToken() {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        return $_SESSION['csrf_token'];
    }

}

?>