# ManageMyParking v2.0 - Deployment Instructions

## Package Contents

**ManageMyParking-v2.0-PRODUCTION.zip** - Payment System v2.0 (755 KB)
- Complete payment processing features
- Stripe/Square/PayPal integration with QR codes
- Manual payment recording (cash, check, card)
- Payment status tracking
- Defuse PHP Encryption for API keys

**ManageMyParking-v1.1-WORKING.zip** - Baseline v1.1 (694 KB)
- Your current production version
- Verified working on shared hosting
- Fallback if v2.0 has issues

## Critical Configuration Checklist

### 1. Session Cookie Path (MANDATORY)
File: `includes/session.php` line 33
```php
'path' => '/',  // MUST be '/' not base_path
```

### 2. Database Configuration
File: `config.php`
```php
// Remove development settings:
// - NO 'unix_socket' parameter
// - Use 'host' => 'localhost' or your MySQL hostname
// - Use standard MySQL port 3306

define('DB_HOST', 'localhost');  // Your hosting MySQL server
define('DB_NAME', 'your_database_name');
define('DB_USER', 'your_database_user');
define('DB_PASS', 'your_database_password');
```

### 3. Base Path Configuration
File: `config.php`
```php
define('BASE_PATH', '/your-subdirectory');  // Or '/' for root
```

### 4. Payment Settings (Optional)
File: `payment_config.php`
- Configure Stripe/Square/PayPal API keys
- API keys stored encrypted in database
- See PAYMENT_SYSTEM_README.md for details

## Installation Steps

### Fresh Installation
1. Extract zip to your hosting account
2. Edit `config.php` with your database credentials
3. Import `sql/COMPLETE-INSTALL.sql` via phpMyAdmin
4. Access application in browser
5. Login with admin credentials from SQL file
6. Configure payment settings in Settings > Payments (optional)

### Upgrading from v1.1
1. Backup your current installation
2. Backup your database
3. Extract v2.0 zip to new directory
4. Copy your `config.php` from v1.1 to v2.0
5. **VERIFY** session.php has `'path' => '/'`
6. Run migration: `sql/payment-system-migration.sql`
7. Test in browser
8. Clear browser cookies
9. Login and verify all features work

## Browser Cookie Clearing (REQUIRED)

After uploading session.php fix, users must clear browser cookies:

**Chrome/Edge:**
1. Press F12 → Application tab
2. Cookies → (your domain)
3. Right-click → Clear
4. Refresh page

**Firefox:**
1. Press F12 → Storage tab
2. Cookies → (your domain)
3. Right-click → Delete All
4. Refresh page

**Safari:**
1. Develop menu → Show Web Inspector
2. Storage tab → Cookies
3. Delete all for your domain
4. Refresh page

## Known Production Issues

### Issue: 401 Unauthorized on API Calls
**Cause:** Session cookie path set to subdirectory instead of '/'
**Fix:** Edit includes/session.php line 33 to use `'path' => '/'`
**Action:** Users must clear browser cookies after fix

### Issue: SQL Errors "Column not found: property"
**Cause:** Some API files expect different column naming
**Status:** Resolved by restoring v1.1 baseline API files
**Files:** violations-search.php, tickets-list.php, violations-export.php, vehicles-export.php

## Testing Checklist

1. ✅ Login works
2. ✅ Vehicle search returns results
3. ✅ Create ticket works
4. ✅ Violation search displays data
5. ✅ User management works
6. ✅ Payment recording works (v2.0)
7. ✅ QR code generation works (v2.0)
8. ✅ Ticket status management works (v2.0)

## Support Documentation

- `PAYMENT_SYSTEM_README.md` - Payment features guide
- `PAYMENT_TESTING_GUIDE.md` - Testing payment flows
- `ENCRYPTION_UPGRADE_GUIDE.md` - Defuse encryption details
- `replit.md` - Complete system architecture

## Rollback Procedure

If v2.0 has issues:
1. Delete v2.0 files
2. Restore database from backup
3. Extract ManageMyParking-v1.1-WORKING.zip
4. Restore your config.php
5. Verify session.php has `'path' => '/'`
6. Clear browser cookies
7. Login and verify

## Production Environment Requirements

- PHP 8.0+ (8.3+ recommended)
- MySQL 5.7+ or MariaDB 10.2+
- Apache with mod_rewrite OR Nginx
- HTTPS enabled (recommended)
- PDO, PDO_MySQL, JSON, session, mbstring, GD extensions

## Contact

For issues or questions, refer to the documentation in the package or check error logs on your server.
