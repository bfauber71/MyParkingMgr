================================================================================
MyParkingManager v2.3.0 - Deployment Packages
================================================================================

Release Date: October 27, 2025
Version: 2.3.0

This release includes critical fixes for the "nothing clicks" post-login issue
and complete path handling improvements for ANY installation directory.

================================================================================
PACKAGE DESCRIPTIONS
================================================================================

1. myparkingmanager-v2.3.0-full.zip (~523 KB)
   - Complete installation package with all files
   - Includes: Core app, SQL scripts, documentation, license generators
   - Use for: Fresh installations, vendor/reseller distributions
   - Extract to web server and run setup.php

2. myparkingmanager-v2.3.0-update.zip (~14 KB)
   - Critical bug fix update package
   - Contains ONLY changed files:
     * index.html (loads correct JavaScript file)
     * assets/app-secure.js (complete with all functions)
     * .htaccess (path handling fixes)
     * includes/database.php (endpoint whitelist)
     * api/csrf-token.php (CSRF token endpoint)
   - SAFE: Does NOT include config.php - preserves your settings
   - Use for: Updating existing v2.2.x or v2.3.0 installations
   - IMPORTANT: Backup before applying, clear browser cache after

3. myparkingmanager-v2.3.0-minimal.zip (~132 KB)
   - Clean production package for end-users
   - Excludes: Deployment packages, license generators, vendor tools
   - Includes: Core app files, SQL scripts, user documentation
   - Use for: Customer installations, production deployments

4. myparkingmanager-v2.3.0-docs.zip (~11 KB)
   - Documentation only package
   - Includes: README, CHANGELOG, LICENSE-SYSTEM-GUIDE,
     deployment guide, VERSION, config-sample.php
   - Use for: Reference, documentation review

================================================================================
WHAT'S NEW IN v2.3.0 - CRITICAL FIXES
================================================================================

🐛 FIXED: "Nothing Clicks" After Login Issue
   - Root cause: index.html loaded incomplete JavaScript file
   - Missing functions: loadVehiclesSection(), loadUsersSection(), 
     loadViolationsManagementSection()
   - Fix: Changed to load complete app-secure.js with all 200+ lines
   - Result: Vehicles, properties, users now load correctly after login
   - All tabs and buttons now clickable and functional

✓ Fixed infinite redirect loop in .htaccess causing 500 errors
✓ Fixed 500 errors on /api/app-config and /api/csrf-token endpoints
✓ Added endpoint whitelist for setup wizard compatibility
✓ Resolved database requirement blocking pre-setup API calls
✓ Disabled problematic mod_php directives (PHP-FPM compatibility)
✓ Flattened public directory - all assets now at installation root
✓ Fixed static asset routing (no more /public/* references)
✓ Completely removed hardcoded path references
✓ Application installs in ANY directory (user-configurable)
✓ Admin pages dynamically detect and use correct paths

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
1. Extract myparkingmanager-v2.3.0-full.zip OR minimal.zip
2. Upload all files to your web directory
3. Create MySQL database through cPanel/phpMyAdmin
4. Navigate to http://yourdomain.com/setup.php
5. Follow setup wizard to configure database and admin account
6. Login and start managing vehicles/properties

UPDATING FROM v2.2.x or Earlier v2.3.0:
1. BACKUP your installation and database first!
2. Extract myparkingmanager-v2.3.0-update.zip
3. Upload files, overwriting existing (config.php preserved)
4. Clear browser cache (Ctrl+Shift+Delete)
5. Test login - everything should now work
6. If issues persist, check TROUBLESHOOTING section

================================================================================
REQUIREMENTS
================================================================================

Server Requirements:
- PHP: 8.3+ (minimum 7.4, recommended 8.3+)
- MySQL: 5.7+ or MariaDB 10.2+
- Web Server: Apache with mod_rewrite OR Nginx
- HTTPS recommended for production

PHP Extensions Required:
- pdo, pdo_mysql, json, session, mbstring

Hosting Compatibility:
- Shared hosting compatible
- Works from ANY installation path (root or subdirectory)
- No Docker/virtualization required

================================================================================
POST-INSTALLATION
================================================================================

After successful installation:

1. Configure Your Installation:
   - Add properties via Properties tab
   - Create user accounts via Database > Users
   - Assign users to properties (for non-admin users)
   - Configure violation types and fines

2. License Activation:
   - Free 30-day trial starts automatically
   - Contact vendor for license key to continue after trial
   - Activate via License page in admin dashboard

3. Data Import:
   - Use CSV import feature for bulk vehicle data
   - Import format: tag, plate, owner, apt, make, model, color, year, property

4. Security:
   - Change default admin password immediately
   - Restrict database access to application only
   - Enable HTTPS for production deployments
   - Set proper file permissions (644 for files, 755 for directories)

================================================================================
TROUBLESHOOTING
================================================================================

"Nothing clicks" after login:
- Clear browser cache completely (Ctrl+Shift+Delete)
- Ensure update package was applied correctly
- Check that index.html loads app-secure.js
- Open browser console (F12) and check for JavaScript errors

500 Internal Server Error:
- Check .htaccess RewriteBase matches your installation path
- Verify database credentials in config.php
- Check PHP error logs for specific error messages
- Ensure all required PHP extensions are installed

Database connection failed:
- Verify database exists and credentials are correct
- Check if MySQL server is running
- Confirm database user has proper permissions
- Test connection via setup-test-db.php

Can't see vehicles/properties in database:
- Admin/Operator roles: See all data automatically
- Regular users: Must be assigned to properties first
- Check Database > Users > Edit to assign properties
- Verify data exists: check directly in phpMyAdmin

================================================================================
SUPPORT & DOCUMENTATION
================================================================================

Full documentation available in:
- README.md: Overview and quick start guide
- CHANGELOG.md: Version history and changes
- LICENSE-SYSTEM-GUIDE.md: License system documentation
- deploy/README-DEPLOYMENT.md: Deployment guide

For issues or questions:
- Check error logs: PHP error log, browser console (F12)
- Verify requirements are met
- Review troubleshooting section above
- Contact system administrator or vendor

================================================================================
SHA256 CHECKSUMS
================================================================================

Verify package integrity:
  sha256sum -c CHECKSUMS.txt

See CHECKSUMS.txt for package hashes.

================================================================================
VERSION INFORMATION
================================================================================

Release: MyParkingManager v2.3.0
Date: October 27, 2025
Build: Production Release

Previous versions:
- v2.2.x: Initial flattened directory structure
- v2.1.x: License system implementation
- v2.0.x: Multi-property support

================================================================================
