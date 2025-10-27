================================================================================
MyParkingManager v2.3.0 - Deployment Packages
================================================================================

Release Date: October 27, 2025
Version: 2.3.0

This release includes critical fixes for dynamic path handling, ensuring the
application works correctly with ANY installation path (root or subdirectory).

================================================================================
PACKAGE DESCRIPTIONS
================================================================================

1. myparkingmanager-v2.3.0-full.zip (522 KB)
   - Complete installation package
   - Includes all application files, SQL scripts, documentation,
     deployment packages, and license key generation tools
   - NEW: All public assets (HTML, CSS, JS) are now at root level
   - Use for: Fresh installations, vendor/reseller distributions
   - Extract to your web server directory and run setup.php

2. myparkingmanager-v2.3.0-update.zip (19 KB)
   - Update package with changed files only
   - Contains: index.php, admin/path-settings.html,
     includes/config-loader.php, setup.php, setup-test-db.php,
     includes/database.php, api/violations-manage.php,
     includes/license-keys.php
   - SAFE: Does NOT include config.php - preserves your settings
   - Use for: Updating existing v2.2.x installations to v2.3.0
   - IMPORTANT: Backup your installation before applying
   - Extract over existing installation (your config.php is preserved)

3. myparkingmanager-v2.3.0-minimal.zip (131 KB)
   - Clean installation package for end-users
   - Excludes: deployment packages, license generators, vendor guides
   - Includes: All core application files, SQL scripts, user documentation
   - NEW: All public assets (HTML, CSS, JS) are now at root level
   - Use for: Production deployments, customer installations
   - Best for: End-user installations without vendor tools

4. myparkingmanager-v2.3.0-docs.zip (11 KB)
   - Documentation only
   - Includes: README.md, CHANGELOG.md, LICENSE-SYSTEM-GUIDE.md,
     deployment guide, VERSION, config-sample.php
   - Use for: Reference, documentation review

================================================================================
WHAT'S NEW IN v2.3.0
================================================================================

CRITICAL FIXES:
✓ Flattened public directory - all assets now at installation root
✓ Fixed static asset routing (no more /public/* references)
✓ Resolved admin page asset loading issues  
✓ Corrected path handling to prevent double-path errors
✓ All installations now work with ANY custom path configuration

DIRECTORY STRUCTURE CHANGE:
✓ Public assets (index.html, assets/, css/) moved to root level
✓ No more nested public/ directory
✓ Cleaner, more standard web application structure
✓ Direct access to assets from installation root

DYNAMIC PATH SUPPORT:
✓ Completely removed hardcoded "jrk" path references
✓ Application installs in ANY directory (user-configurable)
✓ Admin pages dynamically detect and use correct paths
✓ Static assets load correctly regardless of base_path setting

INSTALLATION IMPROVEMENTS:
✓ No default admin credentials - setup wizard required
✓ Enhanced database error handling with user-friendly messages
✓ Fixed setup.php 500 errors with improved validation
✓ Created missing setup-test-db.php for connection testing

FEATURES:
✓ 30-day trial period with manual license key activation
✓ HMAC-signed license keys tied to installation IDs
✓ Violation fines and tow deadline management
✓ Conditional towing text on printed tickets
✓ Printer configuration with custom logo support
✓ Flexible shared hosting deployment

================================================================================
INSTALLATION INSTRUCTIONS
================================================================================

FRESH INSTALLATION:
1. Extract myparkingmanager-v2.3.0-full.zip to your web directory
2. Configure web server to point to the extracted directory
3. Navigate to http://yourdomain.com/setup.php
4. Follow the setup wizard to configure database and admin account
5. Delete setup.php and setup-wizard.php after completion

UPDATING FROM v2.2.x:
1. BACKUP your current installation and database
2. Download myparkingmanager-v2.3.0-update.zip
3. Extract over your existing installation
4. Your config.php will be preserved (NOT included in update)
5. Test the application functionality
6. Clear browser cache if assets don't load properly
7. Verify admin pages and static assets load correctly

CUSTOM PATH INSTALLATIONS:
- The application auto-detects its installation path
- Set base_path in config.php for subdirectory installations
- Examples:
  - Root: base_path => ''
  - Subdirectory: base_path => '/parking'
  - Nested: base_path => '/apps/parking/v2'

================================================================================
SYSTEM REQUIREMENTS
================================================================================

- PHP 7.4 or higher (PHP 8.0+ recommended)
- MySQL 5.7+ or MariaDB 10.2+
- Apache or Nginx web server
- mod_rewrite (Apache) or equivalent URL rewriting
- 50MB minimum disk space
- SSL certificate recommended for production

================================================================================
SHA256 CHECKSUMS
================================================================================

4d534f9bf28266170675d25f067fb1b5b87866c4fdc091a2cce7435f46321fa6  myparkingmanager-v2.3.0-docs.zip
31d4d50686967dfc439401e82755232e15a848237b96ab2afb0662529056497a  myparkingmanager-v2.3.0-full.zip
84da67e42f4bdc8e8925d656b1a636aa1820a0bee3c46f8448e3f560b6341ca4  myparkingmanager-v2.3.0-minimal.zip
f0fb14aa406fe921e92f2425ca301ca9760bcf6c2953fabc85d5b7feb7d370a0  myparkingmanager-v2.3.0-update.zip

Verify package integrity:
  sha256sum -c CHECKSUMS.txt

================================================================================
SUPPORT & DOCUMENTATION
================================================================================

- Full documentation: See README.md in the docs package
- License system guide: LICENSE-SYSTEM-GUIDE.md
- Changelog: CHANGELOG.md
- Deployment guide: deploy/README-DEPLOYMENT.md

For technical support, refer to the documentation files included in the
package or contact your system administrator.

================================================================================
SECURITY NOTES
================================================================================

- ALWAYS delete setup.php and setup-wizard.php after installation
- Use strong passwords for admin accounts
- Configure LICENSE_SECRET_KEY environment variable for production
- Enable HTTPS/SSL for production deployments
- Regularly backup your database
- Keep config.php file permissions restricted (chmod 600)

================================================================================
LICENSE
================================================================================

This software uses a subscription-based licensing system:
- 30-day trial period from first installation
- License keys required for continued use after trial
- License keys are installation-specific (tied to installation ID)
- Contact your vendor for license key activation

================================================================================
