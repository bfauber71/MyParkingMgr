<?php
/**
 * License Management System
 * Handles subscription validation and feature access control
 */

class License {
    
    const STATUS_TRIAL = 'trial';
    const STATUS_EXPIRED = 'expired';
    const STATUS_LICENSED = 'licensed';
    
    const MAX_VALIDATION_ATTEMPTS = 5; // Per hour
    const KEY_LENGTH = 32; // License key character length
    
    private static $instance = null;
    private static $licenseData = null;
    
    /**
     * Get or create installation ID
     */
    public static function getInstallId() {
        $configFile = __DIR__ . '/../config.php';
        $config = require $configFile;
        
        // Check if install_id exists in config
        if (isset($config['install_id']) && !empty($config['install_id'])) {
            return $config['install_id'];
        }
        
        // Generate new install ID if not exists
        $installId = self::generateUUID();
        
        // We can't write to config here, so return generated ID
        // Setup wizard should handle saving this
        return $installId;
    }
    
    /**
     * Initialize license system (called during setup)
     */
    public static function initialize($installId = null) {
        if (!$installId) {
            $installId = self::generateUUID();
        }
        
        $licenseId = self::generateUUID();
        $trialExpiresAt = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        try {
            // Check if already initialized
            $existing = Database::queryOne(
                "SELECT id FROM license_instances WHERE install_id = ? LIMIT 1",
                [$installId]
            );
            
            if ($existing) {
                return ['success' => true, 'install_id' => $installId, 'already_exists' => true];
            }
            
            // Create new license instance
            $sql = "INSERT INTO license_instances 
                    (id, install_id, installed_at, trial_expires_at, status) 
                    VALUES (?, ?, NOW(), ?, 'trial')";
            
            Database::execute($sql, [$licenseId, $installId, $trialExpiresAt]);
            
            // Log the initialization
            self::auditLog($installId, 'trial_started', null, 'trial', [
                'trial_days' => 30,
                'expires_at' => $trialExpiresAt
            ]);
            
            return [
                'success' => true,
                'install_id' => $installId,
                'trial_expires_at' => $trialExpiresAt
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }
    
    /**
     * Get current license status
     */
    public static function getStatus() {
        if (self::$licenseData !== null) {
            return self::$licenseData;
        }
        
        $installId = self::getInstallId();
        
        try {
            $license = Database::queryOne(
                "SELECT * FROM license_instances WHERE install_id = ? LIMIT 1",
                [$installId]
            );
            
            if (!$license) {
                // Not initialized yet - should only happen during setup
                return [
                    'status' => 'not_initialized',
                    'is_valid' => true, // Allow access during setup
                    'message' => 'License system not initialized'
                ];
            }
            
            // Check if trial has expired
            $now = new DateTime();
            $trialExpires = new DateTime($license['trial_expires_at']);
            $daysRemaining = $now->diff($trialExpires)->days;
            
            if ($license['status'] === 'licensed' && !empty($license['license_key_hash'])) {
                // Valid license
                self::$licenseData = [
                    'status' => 'licensed',
                    'is_valid' => true,
                    'license_key_prefix' => $license['license_key_prefix'],
                    'activated_at' => $license['activated_at'],
                    'customer_email' => $license['customer_email']
                ];
            } elseif ($now < $trialExpires && $license['status'] === 'trial') {
                // Trial still active
                self::$licenseData = [
                    'status' => 'trial',
                    'is_valid' => true,
                    'days_remaining' => $daysRemaining,
                    'expires_at' => $license['trial_expires_at'],
                    'show_warning' => $daysRemaining <= 7
                ];
            } else {
                // Trial expired, no license
                self::$licenseData = [
                    'status' => 'expired',
                    'is_valid' => false,
                    'expired_at' => $license['trial_expires_at'],
                    'days_expired' => abs($daysRemaining)
                ];
                
                // Update status if needed
                if ($license['status'] !== 'expired') {
                    Database::execute(
                        "UPDATE license_instances SET status = 'expired' WHERE install_id = ?",
                        [$installId]
                    );
                    self::auditLog($installId, 'trial_expired', 'trial', 'expired');
                }
            }
            
            // Update last validated timestamp
            Database::execute(
                "UPDATE license_instances SET last_validated_at = NOW() WHERE install_id = ?",
                [$installId]
            );
            
            return self::$licenseData;
        } catch (Exception $e) {
            // Database error - allow access but log error
            error_log("License validation error: " . $e->getMessage());
            return [
                'status' => 'error',
                'is_valid' => true, // Fail open to prevent lockout
                'error' => 'License validation error'
            ];
        }
    }
    
    /**
     * Validate and activate a license key
     */
    public static function activateLicense($licenseKey, $customerEmail = null) {
        $installId = self::getInstallId();
        
        // Rate limiting check
        if (!self::checkRateLimit($installId)) {
            return [
                'success' => false,
                'error' => 'Too many attempts. Please try again later.'
            ];
        }
        
        // Validate key format
        if (!self::validateKeyFormat($licenseKey)) {
            self::logAttempt($installId, $licenseKey, false, 'Invalid key format');
            return [
                'success' => false,
                'error' => 'Invalid license key format'
            ];
        }
        
        // Generate key hash for storage
        $keyHash = password_hash($licenseKey, PASSWORD_BCRYPT);
        $keyPrefix = substr($licenseKey, 0, 10);
        
        try {
            // Check if key is already used by another installation
            $existingUse = Database::queryOne(
                "SELECT install_id FROM license_instances 
                 WHERE license_key_prefix = ? AND install_id != ? LIMIT 1",
                [$keyPrefix, $installId]
            );
            
            if ($existingUse) {
                self::logAttempt($installId, $licenseKey, false, 'Key already in use');
                return [
                    'success' => false,
                    'error' => 'This license key is already in use by another installation'
                ];
            }
            
            // Get current license
            $license = Database::queryOne(
                "SELECT * FROM license_instances WHERE install_id = ? LIMIT 1",
                [$installId]
            );
            
            if (!$license) {
                return [
                    'success' => false,
                    'error' => 'Installation not found. Please run setup first.'
                ];
            }
            
            // Verify the license key is valid for this installation
            if (!self::verifyLicenseKey($licenseKey, $installId)) {
                self::logAttempt($installId, $licenseKey, false, 'Invalid key for installation');
                return [
                    'success' => false,
                    'error' => 'License key is not valid for this installation'
                ];
            }
            
            // Update license record
            $sql = "UPDATE license_instances SET 
                    license_key_hash = ?,
                    license_key_prefix = ?,
                    status = 'licensed',
                    activated_at = NOW(),
                    customer_email = ?,
                    metadata = JSON_SET(COALESCE(metadata, '{}'), '$.activation_date', NOW())
                    WHERE install_id = ?";
            
            Database::execute($sql, [$keyHash, $keyPrefix, $customerEmail, $installId]);
            
            // Log successful activation
            self::logAttempt($installId, $licenseKey, true, 'License activated');
            self::auditLog($installId, 'license_activated', $license['status'], 'licensed', [
                'customer_email' => $customerEmail,
                'key_prefix' => $keyPrefix
            ]);
            
            // Clear cached license data
            self::$licenseData = null;
            
            return [
                'success' => true,
                'message' => 'License successfully activated!',
                'status' => 'licensed'
            ];
            
        } catch (Exception $e) {
            self::logAttempt($installId, $licenseKey, false, 'Database error');
            error_log("License activation error: " . $e->getMessage());
            return [
                'success' => false,
                'error' => 'Failed to activate license. Please try again.'
            ];
        }
    }
    
    /**
     * Verify license key is valid for installation
     */
    private static function verifyLicenseKey($key, $installId) {
        // Use cryptographic validation from LicenseKeys class
        require_once __DIR__ . '/license-keys.php';
        
        // Validate the key signature
        $validation = LicenseKeys::validateKey($key, $installId);
        
        if (!$validation['valid']) {
            error_log("License validation failed: " . $validation['error']);
            return false;
        }
        
        // Check if key has been revoked (if tracking issued keys)
        try {
            // Check if license_keys_issued table exists
            $tableExists = Database::queryOne("SHOW TABLES LIKE 'license_keys_issued'");
            
            if ($tableExists) {
                $keyHash = hash('sha256', $key);
                $issuedKey = Database::queryOne(
                    "SELECT is_active, revoked_at FROM license_keys_issued 
                     WHERE key_hash = ? LIMIT 1",
                    [$keyHash]
                );
                
                if ($issuedKey) {
                    // Key exists in our records, check if active
                    if (!$issuedKey['is_active'] || $issuedKey['revoked_at']) {
                        error_log("License key has been revoked");
                        return false;
                    }
                }
                // If key doesn't exist in records, it might be a newly generated key
                // In production, you'd want to reject unknown keys
            }
        } catch (Exception $e) {
            // Table might not exist yet, continue with validation
            error_log("Could not check key revocation status: " . $e->getMessage());
        }
        
        return true;
    }
    
    /**
     * Check if feature is available based on license status
     */
    public static function hasFeatureAccess($feature) {
        $status = self::getStatus();
        
        // Always allow essential features
        $essentialFeatures = [
            'login',
            'logout', 
            'view_license',
            'activate_license',
            'view_profile'
        ];
        
        if (in_array($feature, $essentialFeatures)) {
            return true;
        }
        
        // Check if license is valid
        return isset($status['is_valid']) && $status['is_valid'] === true;
    }
    
    /**
     * Get restricted features list
     */
    public static function getRestrictedFeatures() {
        return [
            'vehicles_manage' => 'Vehicle Management',
            'violations_manage' => 'Violation System',
            'properties_manage' => 'Property Management',
            'users_manage' => 'User Management',
            'export_data' => 'Data Export',
            'bulk_operations' => 'Bulk Operations',
            'reports' => 'Reports & Analytics'
        ];
    }
    
    /**
     * Check rate limiting for validation attempts
     */
    private static function checkRateLimit($installId) {
        $oneHourAgo = date('Y-m-d H:i:s', strtotime('-1 hour'));
        
        $attempts = Database::queryOne(
            "SELECT COUNT(*) as count FROM license_attempts 
             WHERE install_id = ? AND attempted_at > ? AND success = FALSE",
            [$installId, $oneHourAgo]
        );
        
        return !$attempts || $attempts['count'] < self::MAX_VALIDATION_ATTEMPTS;
    }
    
    /**
     * Log license validation attempt
     */
    private static function logAttempt($installId, $key, $success, $errorMessage = null) {
        $keyPrefix = substr($key, 0, 20); // Store only first 20 chars
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
        
        Database::execute(
            "INSERT INTO license_attempts 
             (install_id, attempted_key, ip_address, user_agent, success, error_message) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [$installId, $keyPrefix, $ipAddress, $userAgent, $success, $errorMessage]
        );
    }
    
    /**
     * Log license audit event
     */
    private static function auditLog($installId, $action, $oldStatus, $newStatus, $details = null) {
        $userId = Session::user()['id'] ?? null;
        
        Database::execute(
            "INSERT INTO license_audit 
             (install_id, action, old_status, new_status, user_id, details) 
             VALUES (?, ?, ?, ?, ?, ?)",
            [$installId, $action, $oldStatus, $newStatus, $userId, json_encode($details)]
        );
    }
    
    /**
     * Validate key format
     */
    private static function validateKeyFormat($key) {
        // Format: XXXX-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX (35 chars with dashes)
        return preg_match('/^[A-Z0-9]{4}(-[A-Z0-9]{4}){7}$/', $key);
    }
    
    /**
     * Generate UUID v4
     */
    private static function generateUUID() {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        
        return sprintf('%08s-%04s-%04s-%04s-%12s',
            bin2hex(substr($data, 0, 4)),
            bin2hex(substr($data, 4, 2)),
            bin2hex(substr($data, 6, 2)),
            bin2hex(substr($data, 8, 2)),
            bin2hex(substr($data, 10, 6))
        );
    }
    
    /**
     * Generate a license key
     */
    public static function generateLicenseKey() {
        $segments = [];
        $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        
        for ($i = 0; $i < 8; $i++) {
            $segment = '';
            for ($j = 0; $j < 4; $j++) {
                $segment .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $segments[] = $segment;
        }
        
        return implode('-', $segments);
    }
}