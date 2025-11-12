<?php
/**
 * License Key Management
 * Handles generation and validation of cryptographically signed license keys
 */

class LicenseKeys {
    
    // Get secret key from configuration or use default
    // In production, this MUST be stored in environment variables or secure config
    private static function getSecretKey() {
        // Try to get from config first
        if (defined('LICENSE_SECRET_KEY')) {
            return LICENSE_SECRET_KEY;
        }
        
        // Try environment variable
        $envKey = getenv('MPM_LICENSE_SECRET_KEY');
        if ($envKey) {
            return $envKey;
        }
        
        // Default key - CHANGE THIS IN PRODUCTION
        // This is only for development/testing
        return 'MPM-2024-Dev-Secret-Key-CHANGE-IN-PRODUCTION-9A8B7C6D';
    }
    
    /**
     * Generate a signed license key for a specific installation
     * 
     * @param string $installId The installation ID
     * @param string $customerEmail The customer's email
     * @return array Contains the license key and metadata
     */
    public static function generateSignedKey($installId, $customerEmail) {
        // Generate random key base (16 chars)
        $keyBase = self::generateRandomString(16);
        
        // Create minimal payload for signature (must match validation)
        $signaturePayload = [
            'key' => $keyBase,
            'install_id' => $installId
        ];
        
        // Generate signature using minimal payload
        $signature = self::generateSignature($signaturePayload);
        
        // Combine key base and signature
        $fullKey = self::formatKey($keyBase . $signature);
        
        // Return full metadata (but signature only uses minimal payload)
        return [
            'key' => $fullKey,
            'key_base' => $keyBase,
            'signature' => $signature,
            'install_id' => $installId,
            'email' => $customerEmail,
            'issued_at' => date('Y-m-d H:i:s'),
            'metadata' => [
                'key' => $keyBase,
                'install_id' => $installId,
                'email' => $customerEmail,
                'issued_at' => time(),
                'version' => '2.0'
            ]
        ];
    }
    
    /**
     * Validate a license key for a specific installation
     * 
     * @param string $licenseKey The license key to validate
     * @param string $installId The installation ID
     * @return array Validation result
     */
    public static function validateKey($licenseKey, $installId) {
        // Remove formatting
        $cleanKey = str_replace('-', '', $licenseKey);
        
        // Check length (16 chars key + 16 chars signature = 32 total)
        if (strlen($cleanKey) !== 32) {
            return [
                'valid' => false,
                'error' => 'Invalid key length'
            ];
        }
        
        // Extract key base and signature
        $keyBase = substr($cleanKey, 0, 16);
        $providedSignature = substr($cleanKey, 16, 16);
        
        // Try to validate with the provided install ID
        $testPayload = [
            'key' => $keyBase,
            'install_id' => $installId
        ];
        
        // Generate expected signature for this install ID
        $expectedSignature = self::generateSignature($testPayload);
        
        // Compare signatures (first 16 chars)
        if (substr($expectedSignature, 0, 16) === $providedSignature) {
            return [
                'valid' => true,
                'key_base' => $keyBase,
                'install_id' => $installId
            ];
        }
        
        // Also try validation without install ID (universal keys)
        $universalPayload = [
            'key' => $keyBase,
            'install_id' => 'UNIVERSAL'
        ];
        
        $universalSignature = self::generateSignature($universalPayload);
        
        if (substr($universalSignature, 0, 16) === $providedSignature) {
            return [
                'valid' => true,
                'key_base' => $keyBase,
                'install_id' => $installId,
                'type' => 'universal'
            ];
        }
        
        return [
            'valid' => false,
            'error' => 'Invalid signature - key not valid for this installation'
        ];
    }
    
    /**
     * Generate a universal license key (works for any installation)
     * 
     * @param string $customerEmail The customer's email
     * @return array Contains the license key and metadata
     */
    public static function generateUniversalKey($customerEmail) {
        // Generate random key base (16 chars)
        $keyBase = self::generateRandomString(16);
        
        // Create minimal payload for signature (must match validation)
        $signaturePayload = [
            'key' => $keyBase,
            'install_id' => 'UNIVERSAL'
        ];
        
        // Generate signature using minimal payload
        $signature = self::generateSignature($signaturePayload);
        
        // Combine key base and signature
        $fullKey = self::formatKey($keyBase . $signature);
        
        // Return full metadata (but signature only uses minimal payload)
        return [
            'key' => $fullKey,
            'key_base' => $keyBase,
            'signature' => $signature,
            'email' => $customerEmail,
            'issued_at' => date('Y-m-d H:i:s'),
            'type' => 'universal',
            'metadata' => [
                'key' => $keyBase,
                'install_id' => 'UNIVERSAL',
                'email' => $customerEmail,
                'issued_at' => time(),
                'version' => '2.0',
                'type' => 'universal'
            ]
        ];
    }
    
    /**
     * Generate HMAC signature for payload
     */
    private static function generateSignature($payload) {
        // Sort payload for consistent hashing
        ksort($payload);
        
        // Create string representation
        $dataString = json_encode($payload);
        
        // Generate HMAC-SHA256 with dynamic secret key
        $hmac = hash_hmac('sha256', $dataString, self::getSecretKey());
        
        // Return first 16 chars in uppercase
        return strtoupper(substr($hmac, 0, 16));
    }
    
    /**
     * Generate random alphanumeric string
     */
    private static function generateRandomString($length) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $string = '';
        
        for ($i = 0; $i < $length; $i++) {
            $string .= $characters[random_int(0, strlen($characters) - 1)];
        }
        
        return $string;
    }
    
    /**
     * Format key with dashes for readability
     */
    private static function formatKey($key) {
        // Format as XXXX-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX-XXXX
        $formatted = '';
        for ($i = 0; $i < strlen($key); $i++) {
            if ($i > 0 && $i % 4 === 0) {
                $formatted .= '-';
            }
            $formatted .= $key[$i];
        }
        return $formatted;
    }
    
    /**
     * Store issued license key in database
     */
    public static function storeIssuedKey($keyData) {
        $sql = "INSERT INTO license_keys_issued 
                (id, key_hash, key_prefix, install_id, customer_email, issued_at, metadata) 
                VALUES (?, ?, ?, ?, ?, NOW(), ?)";
        
        $keyHash = hash('sha256', $keyData['key']);
        $keyPrefix = substr($keyData['key'], 0, 10);
        
        Database::execute($sql, [
            self::generateUUID(),
            $keyHash,
            $keyPrefix,
            $keyData['install_id'] ?? null,
            $keyData['email'],
            json_encode($keyData['metadata'])
        ]);
    }
    
    /**
     * Check if a key has already been issued
     */
    public static function isKeyIssued($key) {
        $keyHash = hash('sha256', $key);
        
        $result = Database::queryOne(
            "SELECT id FROM license_keys_issued WHERE key_hash = ? LIMIT 1",
            [$keyHash]
        );
        
        return $result !== false;
    }
    
    /**
     * Generate UUID
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
}