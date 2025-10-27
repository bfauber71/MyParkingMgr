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

2. myparkingmanager-v2.3.0-update.zip (20 KB)
   - Update package with changed files only
   - Contains: index.php, .htaccess, includes/database.php,
     admin/path-settings.html, includes/config-loader.php, setup.php,
     setup-test-db.php, api/violations-manage.php, includes/license-keys.php
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
✓ Fixed infinite redirect loop in .htaccess causing 500 errors
✓ Fixed 500 errors on /api/app-config and /api/csrf-token endpoints
✓ Added endpoint whitelist for setup wizard compatibility
✓ Resolved database requirement blocking pre-setup API calls
✓ Disabled problematic mod_php directives (PHP-FPM compatibility)
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
dc30dbbae904a330f71e751644b6b845d3a4af3eb20a2fe51813fcbca56fe8cf  myparkingmanager-v2.3.0-full.zip
5e63404b2d95d01dc865959afc78b7904bbd200d02c3a0603142b1d65b4a8cc2  myparkingmanager-v2.3.0-minimal.zip
811d4521d53fc1ff8dd3728d85d4f0eda299a415bf711309182b6b791e18cf6b  myparkingmanager-v2.3.0-update.zip

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
