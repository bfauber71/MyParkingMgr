<?php
/**
 * Security Functions and Middleware
 * Enhanced security layer for MyParkingManager
 */

class Security {
    private static $csrfTokenName = 'csrf_token';
    private static $csrfTokenLifetime = 3600; // 1 hour
    
    /**
     * Generate CSRF token
     */
    public static function generateCsrfToken() {
        if (!Session::has(self::$csrfTokenName) || self::isTokenExpired()) {
            $token = bin2hex(random_bytes(32));
            Session::set(self::$csrfTokenName, $token);
            Session::set(self::$csrfTokenName . '_time', time());
        }
        return Session::get(self::$csrfTokenName);
    }
    
    /**
     * Validate CSRF token
     */
    public static function validateCsrfToken($token) {
        if (!$token || !Session::has(self::$csrfTokenName)) {
            return false;
        }
        
        if (self::isTokenExpired()) {
            self::regenerateCsrfToken();
            return false;
        }
        
        return hash_equals(Session::get(self::$csrfTokenName), $token);
    }
    
    /**
     * Check if CSRF token is expired
     */
    private static function isTokenExpired() {
        $tokenTime = Session::get(self::$csrfTokenName . '_time', 0);
        return (time() - $tokenTime) > self::$csrfTokenLifetime;
    }
    
    /**
     * Regenerate CSRF token
     */
    public static function regenerateCsrfToken() {
        Session::remove(self::$csrfTokenName);
        Session::remove(self::$csrfTokenName . '_time');
        return self::generateCsrfToken();
    }
    
    /**
     * Validate request method and CSRF for state-changing operations
     */
    public static function validateRequest($allowedMethods = ['POST'], $skipCsrf = false) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        if (!in_array($method, $allowedMethods)) {
            http_response_code(405);
            jsonResponse(['error' => 'Method not allowed'], 405);
        }
        
        // Skip CSRF for GET requests (they should be idempotent)
        if ($method === 'GET' || $method === 'OPTIONS' || $skipCsrf) {
            return true;
        }
        
        // PRODUCTION SECURITY: Never skip CSRF validation
        // CSRF protection is ALWAYS enforced for all POST/PUT/DELETE requests
        
        // Get CSRF token from headers or body
        $headers = getallheaders();
        $csrfToken = null;
        
        if (isset($headers['X-CSRF-Token'])) {
            $csrfToken = $headers['X-CSRF-Token'];
        } elseif (isset($headers['x-csrf-token'])) {
            $csrfToken = $headers['x-csrf-token'];
        } else {
            // Use the cached JSON input
            $data = getJsonInput();
            $csrfToken = $data['csrf_token'] ?? null;
        }
        
        if (!self::validateCsrfToken($csrfToken)) {
            http_response_code(403);
            jsonResponse(['error' => 'Invalid or missing CSRF token'], 403);
        }
        
        return true;
    }
    
    /**
     * Set security headers
     */
    public static function setSecurityHeaders() {
        // Prevent clickjacking
        header('X-Frame-Options: SAMEORIGIN');
        
        // Prevent MIME type sniffing
        header('X-Content-Type-Options: nosniff');
        
        // Enable XSS protection in browsers
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Content Security Policy
        $csp = "default-src 'self'; " .
               "script-src 'self' 'unsafe-inline'; " .
               "style-src 'self' 'unsafe-inline'; " .
               "img-src 'self' data: https:; " .
               "font-src 'self' data:; " .
               "connect-src 'self'; " .
               "frame-ancestors 'self'; " .
               "form-action 'self'; " .
               "base-uri 'self';";
        
        header("Content-Security-Policy: $csp");
        
        // Permissions Policy (formerly Feature Policy)
        header("Permissions-Policy: geolocation=(), microphone=(), camera=()");
        
        // HSTS (HTTP Strict Transport Security) - only on HTTPS
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        }
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data, $type = 'text') {
        if (is_array($data)) {
            return array_map(function($item) use ($type) {
                return self::sanitizeInput($item, $type);
            }, $data);
        }
        
        switch ($type) {
            case 'email':
                return filter_var($data, FILTER_SANITIZE_EMAIL);
                
            case 'int':
                return filter_var($data, FILTER_SANITIZE_NUMBER_INT);
                
            case 'float':
                return filter_var($data, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
                
            case 'url':
                return filter_var($data, FILTER_SANITIZE_URL);
                
            case 'html':
                // Allow basic HTML but strip dangerous tags
                $allowed = '<p><br><strong><em><u><h1><h2><h3><h4><h5><h6><ul><ol><li><blockquote>';
                return strip_tags($data, $allowed);
                
            case 'sql':
                // For SQL identifiers (table/column names)
                return preg_replace('/[^a-zA-Z0-9_]/', '', $data);
                
            case 'alphanumeric':
                return preg_replace('/[^a-zA-Z0-9\s\-_]/', '', $data);
                
            case 'phone':
                return preg_replace('/[^0-9\-\+\(\)\s]/', '', $data);
                
            case 'text':
            default:
                // Remove any potential XSS vectors while preserving text
                $data = str_replace(['<', '>', '"', "'", '&'], ['&lt;', '&gt;', '&quot;', '&#x27;', '&amp;'], $data);
                return trim($data);
        }
    }
    
    /**
     * Validate input data
     */
    public static function validateInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            
            // Check required fields
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = "$field is required";
                continue;
            }
            
            // Skip validation if field is optional and empty
            if (empty($value) && (!isset($rule['required']) || !$rule['required'])) {
                continue;
            }
            
            // Type validation
            if (isset($rule['type'])) {
                switch ($rule['type']) {
                    case 'email':
                        if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
                            $errors[$field] = "$field must be a valid email address";
                        }
                        break;
                        
                    case 'int':
                        if (!is_numeric($value) || (int)$value != $value) {
                            $errors[$field] = "$field must be an integer";
                        }
                        break;
                        
                    case 'float':
                        if (!is_numeric($value)) {
                            $errors[$field] = "$field must be a number";
                        }
                        break;
                        
                    case 'url':
                        if (!filter_var($value, FILTER_VALIDATE_URL)) {
                            $errors[$field] = "$field must be a valid URL";
                        }
                        break;
                        
                    case 'phone':
                        if (!preg_match('/^[\d\s\-\+\(\)]{10,}$/', $value)) {
                            $errors[$field] = "$field must be a valid phone number";
                        }
                        break;
                        
                    case 'date':
                        if (!strtotime($value)) {
                            $errors[$field] = "$field must be a valid date";
                        }
                        break;
                }
            }
            
            // Length validation
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = "$field must be at least {$rule['min_length']} characters";
            }
            
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = "$field must not exceed {$rule['max_length']} characters";
            }
            
            // Pattern validation
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field] = "$field has invalid format";
            }
            
            // Custom validation function
            if (isset($rule['custom']) && is_callable($rule['custom'])) {
                $customError = $rule['custom']($value, $data);
                if ($customError !== true) {
                    $errors[$field] = $customError;
                }
            }
        }
        
        return empty($errors) ? true : $errors;
    }
    
    /**
     * Rate limiting
     */
    public static function checkRateLimit($identifier, $maxAttempts = 60, $windowMinutes = 1) {
        $cacheKey = 'rate_limit_' . md5($identifier);
        $attempts = Session::get($cacheKey, []);
        $now = time();
        $window = $windowMinutes * 60;
        
        // Remove old attempts outside the window
        $attempts = array_filter($attempts, function($timestamp) use ($now, $window) {
            return ($now - $timestamp) < $window;
        });
        
        if (count($attempts) >= $maxAttempts) {
            http_response_code(429);
            jsonResponse([
                'error' => 'Too many requests. Please try again later.',
                'retry_after' => $window - ($now - min($attempts))
            ], 429);
        }
        
        $attempts[] = $now;
        Session::set($cacheKey, $attempts);
        
        return true;
    }
    
    /**
     * Generate secure random string
     */
    public static function generateRandomString($length = 32) {
        return bin2hex(random_bytes($length / 2));
    }
    
    /**
     * Hash password securely
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }
    
    /**
     * Verify password
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Sanitize file upload
     */
    public static function sanitizeFileUpload($file, $allowedTypes = [], $maxSize = 5242880) {
        // Check if file was uploaded
        if (!isset($file['error']) || is_array($file['error'])) {
            return ['error' => 'Invalid file upload'];
        }
        
        // Check upload errors
        switch ($file['error']) {
            case UPLOAD_ERR_OK:
                break;
            case UPLOAD_ERR_INI_SIZE:
            case UPLOAD_ERR_FORM_SIZE:
                return ['error' => 'File too large'];
            case UPLOAD_ERR_NO_FILE:
                return ['error' => 'No file uploaded'];
            default:
                return ['error' => 'Unknown upload error'];
        }
        
        // Check file size
        if ($file['size'] > $maxSize) {
            return ['error' => 'File exceeds maximum allowed size'];
        }
        
        // Check MIME type
        $finfo = new finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        
        if (!empty($allowedTypes) && !in_array($mimeType, $allowedTypes)) {
            return ['error' => 'File type not allowed'];
        }
        
        // Generate safe filename
        $extension = pathinfo($file['name'], PATHINFO_EXTENSION);
        $safeFilename = self::generateRandomString(16) . '.' . preg_replace('/[^a-zA-Z0-9]/', '', $extension);
        
        return [
            'success' => true,
            'filename' => $safeFilename,
            'mime_type' => $mimeType,
            'size' => $file['size']
        ];
    }
    
    /**
     * Log security events
     */
    public static function logSecurityEvent($event, $details = []) {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'event' => $event,
            'ip' => getClientIp(),
            'user_id' => Session::userId(),
            'details' => $details
        ];
        
        // Log to file or database
        error_log('[SECURITY] ' . json_encode($logEntry));
        
        // For critical events, could also send alerts
        if (in_array($event, ['brute_force_attempt', 'sql_injection_attempt', 'xss_attempt'])) {
            // Send alert to admin
        }
    }
    
    /**
     * Detect and prevent SQL injection attempts
     */
    public static function detectSqlInjection($input) {
        $patterns = [
            '/(\bunion\b.*\bselect\b)/i',
            '/(\bselect\b.*\bfrom\b.*\bwhere\b)/i',
            '/(\binsert\b.*\binto\b)/i',
            '/(\bupdate\b.*\bset\b)/i',
            '/(\bdelete\b.*\bfrom\b)/i',
            '/(\bdrop\b.*\btable\b)/i',
            '/(\bcreate\b.*\btable\b)/i',
            '/(\balter\b.*\btable\b)/i',
            '/(\bexec\b|\bexecute\b)/i',
            '/(\bscript\b.*\b>\b)/i',
            '/(\b--\b|\/\*|\*\/)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::logSecurityEvent('sql_injection_attempt', ['input' => $input]);
                return true;
            }
        }
        
        return false;
    }
    
    /**
     * Detect and prevent XSS attempts
     */
    public static function detectXss($input) {
        $patterns = [
            '/<script[^>]*>.*?<\/script>/si',
            '/<iframe[^>]*>.*?<\/iframe>/si',
            '/javascript:/i',
            '/on\w+\s*=/i', // Event handlers
            '/<embed[^>]*>/i',
            '/<object[^>]*>/i',
            '/data:text\/html/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $input)) {
                self::logSecurityEvent('xss_attempt', ['input' => $input]);
                return true;
            }
        }
        
        return false;
    }
}

// Middleware function to be called at the start of each API endpoint
function securityMiddleware() {
    // Set security headers
    Security::setSecurityHeaders();
    
    // Rate limiting (60 requests per minute per IP)
    $identifier = getClientIp() . ':' . $_SERVER['REQUEST_URI'];
    Security::checkRateLimit($identifier, 60, 1);
    
    // PRODUCTION SECURITY: Always validate CSRF for state-changing operations
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'POST' || $method === 'PUT' || $method === 'DELETE') {
        Security::validateRequest([$method]);
    }
}

// Helper function to get CSRF token endpoint
function getCsrfTokenEndpoint() {
    Session::start();
    $token = Security::generateCsrfToken();
    jsonResponse(['token' => $token]);
}
?>