#!/usr/bin/env php
<?php
/**
 * Generate Encryption Key for Payment System
 * 
 * This script generates a secure encryption key for the Defuse PHP Encryption library.
 * Run this ONCE during initial setup, then secure the generated key file.
 * 
 * Usage:
 *   php generate-encryption-key.php
 * 
 * Output:
 *   Creates config/encryption.key with a secure random key
 * 
 * SECURITY NOTES:
 * - Run this only once during initial setup
 * - Store encryption.key OUTSIDE web root if possible
 * - Add encryption.key to .gitignore (never commit to version control)
 * - Backup encryption.key securely - lost keys = lost data
 * - Keep file permissions restrictive (chmod 600)
 */

require_once __DIR__ . '/lib/CryptoHelper.php';

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
echo "ManageMyParking - Encryption Key Generator\n";
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";

// Check if key already exists
$keyFile = __DIR__ . '/config/encryption.key';
$configDir = __DIR__ . '/config';

if (file_exists($keyFile)) {
    echo "⚠️  WARNING: Encryption key already exists at:\n";
    echo "   $keyFile\n\n";
    echo "Generating a new key will make existing encrypted data unreadable!\n";
    echo "Do you want to overwrite it? (yes/no): ";
    
    $handle = fopen("php://stdin", "r");
    $line = trim(fgets($handle));
    fclose($handle);
    
    if (strtolower($line) !== 'yes') {
        echo "\n❌ Aborted. Existing key preserved.\n";
        exit(0);
    }
    
    // Backup existing key
    $backupFile = $keyFile . '.backup.' . date('YmdHis');
    if (copy($keyFile, $backupFile)) {
        echo "\n✓ Existing key backed up to: $backupFile\n";
    }
}

// Create config directory if it doesn't exist
if (!is_dir($configDir)) {
    if (!mkdir($configDir, 0755, true)) {
        echo "❌ ERROR: Failed to create config directory.\n";
        exit(1);
    }
    echo "✓ Created config directory\n";
}

// Generate new encryption key
try {
    echo "\n🔐 Generating secure encryption key...\n";
    $keyAscii = CryptoHelper::generateKey();
    
    // Write key to file
    if (file_put_contents($keyFile, $keyAscii) === false) {
        echo "❌ ERROR: Failed to write key file.\n";
        exit(1);
    }
    
    // Set restrictive permissions
    chmod($keyFile, 0600);
    
    echo "✓ Encryption key generated successfully!\n\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "Key saved to: $keyFile\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
    echo "⚠️  IMPORTANT SECURITY STEPS:\n\n";
    echo "1. BACKUP this key file immediately\n";
    echo "   - Store backup in a secure location\n";
    echo "   - Lost keys = lost encrypted data!\n\n";
    
    echo "2. SECURE the key file:\n";
    echo "   chmod 600 $keyFile\n";
    echo "   chown www-data:www-data $keyFile\n\n";
    
    echo "3. ADD to .gitignore:\n";
    echo "   echo 'config/encryption.key' >> .gitignore\n";
    echo "   echo 'config/encryption.key.backup.*' >> .gitignore\n\n";
    
    echo "4. MOVE outside web root (recommended):\n";
    echo "   mv config/encryption.key /secure/path/encryption.key\n";
    echo "   Update lib/CryptoHelper.php with new path\n\n";
    
    echo "5. RE-ENCRYPT existing API keys:\n";
    echo "   - Old encrypted data won't work with new key\n";
    echo "   - Re-enter API keys in Settings → Payments\n\n";
    
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n";
    echo "✓ Setup complete. Payment system encryption is now secure.\n";
    echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n";
    
} catch (Exception $e) {
    echo "❌ ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
