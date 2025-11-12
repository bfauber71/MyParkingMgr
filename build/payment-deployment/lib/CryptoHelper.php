<?php
/**
 * CryptoHelper - Secure encryption wrapper using Defuse PHP Encryption
 * 
 * This class provides secure encryption/decryption for sensitive data
 * like API keys using the Defuse PHP Encryption library.
 * 
 * Requirements:
 * - Defuse PHP Encryption library (defuse-crypto.phar)
 * - Encryption key stored securely (not in version control)
 * 
 * @see https://github.com/defuse/php-encryption
 */

// Load Defuse PHP Encryption library
// Adjust path if defuse-crypto.phar is located elsewhere
$defuse_path = __DIR__ . '/defuse-crypto.phar';
if (file_exists($defuse_path)) {
    require_once($defuse_path);
} else {
    // Fallback to alternate location
    $defuse_path = '/var/www/lib/defuse-crypto.phar';
    if (file_exists($defuse_path)) {
        require_once($defuse_path);
    } else {
        throw new Exception('Defuse PHP Encryption library not found. Please install defuse-crypto.phar');
    }
}

use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;

class CryptoHelper {
    
    private static $encryptionKey = null;
    
    /**
     * Load encryption key from secure file
     * 
     * @return Key
     * @throws Exception if key file not found or invalid
     */
    private static function loadKey() {
        if (self::$encryptionKey !== null) {
            return self::$encryptionKey;
        }
        
        // Key file should be stored OUTSIDE web root for security
        // and should NOT be in version control
        $keyFile = __DIR__ . '/../config/encryption.key';
        
        if (!file_exists($keyFile)) {
            throw new Exception(
                'Encryption key file not found. ' .
                'Run: php generate-encryption-key.php to create it.'
            );
        }
        
        $keyAscii = file_get_contents($keyFile);
        if ($keyAscii === false) {
            throw new Exception('Failed to read encryption key file.');
        }
        
        try {
            self::$encryptionKey = Key::loadFromAsciiSafeString($keyAscii);
            return self::$encryptionKey;
        } catch (Exception $e) {
            throw new Exception('Invalid encryption key: ' . $e->getMessage());
        }
    }
    
    /**
     * Encrypt a string
     * 
     * @param string $plaintext The text to encrypt
     * @return string Base64-encoded ciphertext
     * @throws Exception on encryption failure
     */
    public static function encrypt($plaintext) {
        if (empty($plaintext)) {
            return '';
        }
        
        try {
            $key = self::loadKey();
            $ciphertext = Crypto::encrypt($plaintext, $key);
            return $ciphertext;
        } catch (Exception $e) {
            error_log('Encryption error: ' . $e->getMessage());
            throw new Exception('Failed to encrypt data: ' . $e->getMessage());
        }
    }
    
    /**
     * Decrypt a string
     * 
     * @param string $ciphertext Base64-encoded ciphertext
     * @return string Decrypted plaintext
     * @throws Exception on decryption failure
     */
    public static function decrypt($ciphertext) {
        if (empty($ciphertext)) {
            return '';
        }
        
        try {
            $key = self::loadKey();
            $plaintext = Crypto::decrypt($ciphertext, $key);
            return $plaintext;
        } catch (Exception $e) {
            error_log('Decryption error: ' . $e->getMessage());
            throw new Exception('Failed to decrypt data: ' . $e->getMessage());
        }
    }
    
    /**
     * Check if encryption is available and properly configured
     * 
     * @return bool True if encryption is available
     */
    public static function isAvailable() {
        try {
            self::loadKey();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }
    
    /**
     * Generate a new encryption key (for setup only)
     * This should be called once during initial setup
     * 
     * @return string ASCII-safe key string
     */
    public static function generateKey() {
        $key = Key::createNewRandomKey();
        return $key->saveToAsciiSafeString();
    }
}
