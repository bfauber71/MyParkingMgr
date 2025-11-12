# Encryption Upgrade Guide - Defuse PHP Encryption

## Overview

The Payment System v2.0 now uses **Defuse PHP Encryption** for secure storage of API keys and secrets. This replaces the placeholder XOR encryption and provides production-grade security.

**Status:** ✅ Integrated and ready for deployment

---

## What Changed

### Before (Placeholder)
- Simple XOR encoding
- **NOT secure for production**
- Used for development/testing only

### After (Production-Ready)
- **Defuse PHP Encryption** library
- Industry-standard authenticated encryption
- Secure for handling real payment credentials

---

## Installation Steps

### Step 1: Install Defuse PHP Encryption

#### Option A: Using Composer (Recommended)
```bash
cd jrk-payments
composer require defuse/php-encryption
```

This will:
- Download the library to `vendor/` directory
- Create `composer.json` and `composer.lock`
- Generate autoloader

#### Option B: Manual Installation (Shared Hosting)
If your hosting doesn't support Composer:

1. Download the PHAR file:
   ```bash
   wget https://github.com/defuse/php-encryption/releases/download/v2.4.0/defuse-crypto.phar
   ```

2. Place it in your lib directory:
   ```bash
   mv defuse-crypto.phar lib/defuse-crypto.phar
   ```

3. The `CryptoHelper.php` will automatically detect and load it

#### Option C: System-Wide Installation
If you have access to `/var/www/lib/`:
```bash
sudo wget -O /var/www/lib/defuse-crypto.phar \
  https://github.com/defuse/php-encryption/releases/download/v2.4.0/defuse-crypto.phar
```

---

### Step 2: Generate Encryption Key

**⚠️ CRITICAL:** Run this only ONCE during initial setup!

```bash
cd jrk-payments
php generate-encryption-key.php
```

**Output:**
```
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
ManageMyParking - Encryption Key Generator
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━

🔐 Generating secure encryption key...
✓ Encryption key generated successfully!

━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
Key saved to: config/encryption.key
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
```

---

### Step 3: Secure the Encryption Key

#### Required Actions:

1. **Set Restrictive Permissions:**
   ```bash
   chmod 600 config/encryption.key
   chown www-data:www-data config/encryption.key
   ```

2. **Add to .gitignore:**
   ```bash
   echo 'config/encryption.key' >> .gitignore
   echo 'config/encryption.key.backup.*' >> .gitignore
   ```

3. **Backup Securely:**
   ```bash
   # Copy to secure location OUTSIDE web server
   cp config/encryption.key ~/secure-backups/encryption.key.$(date +%Y%m%d)
   ```

4. **Move Outside Web Root (Recommended):**
   ```bash
   # Move key to secure location
   sudo mkdir -p /secure/keys
   sudo mv config/encryption.key /secure/keys/parking-encryption.key
   sudo chmod 600 /secure/keys/parking-encryption.key
   sudo chown www-data:www-data /secure/keys/parking-encryption.key
   ```

   Then update `lib/CryptoHelper.php`:
   ```php
   // Change line ~32:
   $keyFile = '/secure/keys/parking-encryption.key';
   ```

---

### Step 4: Re-Enter API Keys

Since you've generated a new encryption key, you need to re-enter all API keys:

1. Login to application
2. Go to **Settings → Payments**
3. For each property with payment settings:
   - Select property
   - Re-enter Stripe/Square/PayPal API keys
   - Click **Save Payment Settings**

The system will now encrypt keys using the new Defuse encryption.

---

## Verification

### Test Encryption is Working

```bash
cd jrk-payments
php -r "
require_once 'lib/CryptoHelper.php';
\$encrypted = CryptoHelper::encrypt('test123');
\$decrypted = CryptoHelper::decrypt(\$encrypted);
echo \$decrypted === 'test123' ? '✓ Encryption working\n' : '✗ Encryption failed\n';
"
```

**Expected output:** `✓ Encryption working`

### Check API Integration

1. Configure Stripe test keys in Settings
2. Create a test violation ticket ($10 fine)
3. Click "💰 Payment" button
4. Generate payment link
5. **Expected:** Payment link generated successfully (no encryption errors)

---

## Migration from Old System

If you already have API keys encrypted with the old XOR system:

### Option A: Clean Migration (Recommended)

1. **Before generating new key:**
   - Document all your API keys
   - Take screenshots of Settings → Payments

2. **Generate new encryption key:**
   ```bash
   php generate-encryption-key.php
   ```

3. **Re-enter all API keys** in Settings → Payments

### Option B: Database Migration (Advanced)

If you want to migrate existing encrypted data:

```sql
-- Backup payment_settings table
CREATE TABLE payment_settings_backup AS SELECT * FROM payment_settings;

-- Clear encrypted fields (will need to re-enter)
UPDATE payment_settings 
SET api_key_encrypted = NULL,
    api_secret_encrypted = NULL,
    webhook_secret_encrypted = NULL;
```

Then re-enter API keys through the UI.

---

## Security Best Practices

### ✅ DO:
- Generate encryption key ONCE only
- Store backup of encryption.key in secure location
- Use restrictive file permissions (600)
- Move encryption.key outside web root
- Add encryption.key to .gitignore
- Use separate keys for dev/staging/production
- Rotate encryption keys periodically (every 1-2 years)

### ❌ DON'T:
- Commit encryption.key to version control
- Share encryption key via email/chat
- Store encryption key in publicly accessible location
- Regenerate key without backing up old one
- Use same key across multiple installations

---

## Troubleshooting

### Error: "Encryption key file not found"

**Cause:** `config/encryption.key` doesn't exist

**Solution:**
```bash
php generate-encryption-key.php
```

### Error: "Invalid encryption key"

**Cause:** Encryption key file is corrupted or wrong format

**Solution:**
```bash
# Backup existing key
mv config/encryption.key config/encryption.key.old

# Generate new key
php generate-encryption-key.php

# Re-enter all API keys in Settings
```

### Error: "Defuse PHP Encryption library not found"

**Cause:** Library not installed

**Solution:**
```bash
# Option 1: Composer
composer require defuse/php-encryption

# Option 2: Manual PHAR
wget -O lib/defuse-crypto.phar \
  https://github.com/defuse/php-encryption/releases/download/v2.4.0/defuse-crypto.phar
```

### Error: "Failed to decrypt data"

**Possible causes:**
1. Wrong encryption key loaded
2. Data encrypted with different key
3. Corrupted encrypted data

**Solution:**
```bash
# Check which key is being used
php -r "
require_once 'lib/CryptoHelper.php';
echo CryptoHelper::isAvailable() ? 'Key loaded OK\n' : 'Key not available\n';
"

# If key is wrong, re-enter API keys in Settings
```

---

## File Structure

After installation, your file structure should be:

```
jrk-payments/
├── config/
│   └── encryption.key          # Generated encryption key (chmod 600)
├── lib/
│   ├── CryptoHelper.php        # Encryption wrapper class
│   └── defuse-crypto.phar      # Defuse library (if using PHAR)
├── vendor/                      # If using Composer
│   └── defuse/php-encryption/
├── generate-encryption-key.php  # Key generation script
├── composer.json                # If using Composer
└── .gitignore                   # Must include encryption.key
```

---

## Production Deployment Checklist

Before going live with real money:

- [ ] Defuse PHP Encryption installed
- [ ] Encryption key generated
- [ ] Key permissions set (chmod 600)
- [ ] Key moved outside web root
- [ ] Key backed up securely
- [ ] Key added to .gitignore
- [ ] All API keys re-entered and encrypted
- [ ] Encryption test passed
- [ ] Payment link generation tested
- [ ] HTTPS enabled
- [ ] Live API keys configured (not test keys)

---

## Support

### Documentation
- **Defuse PHP Encryption:** https://github.com/defuse/php-encryption
- **API Documentation:** https://github.com/defuse/php-encryption/blob/master/docs/Tutorial.md

### Quick Reference

```php
// Encrypt
$ciphertext = CryptoHelper::encrypt($plaintext);

// Decrypt
$plaintext = CryptoHelper::decrypt($ciphertext);

// Check availability
$available = CryptoHelper::isAvailable();
```

---

## Conclusion

The encryption upgrade provides production-grade security for your payment API keys. Follow this guide carefully during deployment, and your payment system will be secure and ready for handling real transactions.

**Remember:** Losing your encryption key means losing access to encrypted API keys. Always maintain secure backups!
