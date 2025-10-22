<?php
/**
 * Session Management
 * Database-backed PHP sessions with security
 */

class Session {
    private static $started = false;
    
    /**
     * Start session
     */
    public static function start() {
        if (self::$started) {
            return;
        }
        
        $config = require __DIR__ . '/../config.php';
        $session = $config['session'];
        
        // Auto-detect HTTPS if secure is not explicitly set
        $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') 
                    || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
                    || (!empty($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == 443);
        
        $secure = $session['secure'] === 'auto' ? $isHttps : (bool)$session['secure'];
        
        // Configure session
        ini_set('session.use_cookies', 1);
        ini_set('session.use_only_cookies', 1);
        ini_set('session.cookie_httponly', $session['httponly'] ? 1 : 0);
        ini_set('session.cookie_secure', $secure ? 1 : 0);
        ini_set('session.cookie_samesite', 'Lax');
        ini_set('session.gc_maxlifetime', $session['lifetime'] * 60);
        
        session_name($session['name']);
        session_start();
        self::$started = true;
        
        // Regenerate session ID periodically
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        } else if (time() - $_SESSION['created'] > 1800) {
            session_regenerate_id(true);
            $_SESSION['created'] = time();
        }
    }
    
    /**
     * Get session value
     */
    public static function get($key, $default = null) {
        self::start();
        return $_SESSION[$key] ?? $default;
    }
    
    /**
     * Set session value
     */
    public static function set($key, $value) {
        self::start();
        $_SESSION[$key] = $value;
    }
    
    /**
     * Check if key exists
     */
    public static function has($key) {
        self::start();
        return isset($_SESSION[$key]);
    }
    
    /**
     * Remove session value
     */
    public static function remove($key) {
        self::start();
        unset($_SESSION[$key]);
    }
    
    /**
     * Destroy session
     */
    public static function destroy() {
        self::start();
        $_SESSION = [];
        session_destroy();
    }
    
    /**
     * Get authenticated user ID
     */
    public static function userId() {
        return self::get('user_id');
    }
    
    /**
     * Get authenticated user
     */
    public static function user() {
        return self::get('user');
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        return self::has('user_id') && self::has('user');
    }
    
    /**
     * Login user
     */
    public static function login($user) {
        self::start();
        self::set('user_id', $user['id']);
        self::set('user', $user);
        session_regenerate_id(true);
    }
    
    /**
     * Logout user
     */
    public static function logout() {
        self::destroy();
    }
}
