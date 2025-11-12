# Library Directory

This directory contains helper classes and encryption utilities for the ManageMyParking Payment System.

## Files

### CryptoHelper.php
Secure encryption wrapper using Defuse PHP Encryption library.

**Purpose:**
- Encrypt sensitive API keys and secrets
- Decrypt stored credentials for payment processing
- Manage encryption key loading

**Usage:**
```php
require_once __DIR__ . '/lib/CryptoHelper.php';

// Encrypt
$ciphertext = CryptoHelper::encrypt('sensitive_data');

// Decrypt
$plaintext = CryptoHelper::decrypt($ciphertext);

// Check if encryption is available
if (CryptoHelper::isAvailable()) {
    // Encryption configured correctly
}
```

**Requirements:**
- Defuse PHP Encryption library (defuse-crypto.phar or via Composer)
- Encryption key file: `config/encryption.key`

**See Also:**
- `../ENCRYPTION_UPGRADE_GUIDE.md` - Complete encryption setup guide
- `../generate-encryption-key.php` - Key generation script

## Installation

### Option 1: Composer (Recommended)
```bash
composer require defuse/php-encryption
```

### Option 2: PHAR File
Download to this directory:
```bash
wget -O lib/defuse-crypto.phar \
  https://github.com/defuse/php-encryption/releases/download/v2.4.0/defuse-crypto.phar
```

CryptoHelper will automatically detect and load the PHAR file.

## Security Notes

- Never commit `config/encryption.key` to version control
- Set restrictive permissions on encryption key (chmod 600)
- Store encryption key outside web root in production
- Backup encryption key securely - lost keys = lost encrypted data

## Support

For detailed setup and troubleshooting, see `../ENCRYPTION_UPGRADE_GUIDE.md`
