MyParkingManager v2.0 - Migration Package
==========================================

This package is for UPGRADING existing MyParkingManager/ManageMyParking installations.

IMPORTANT: This package EXCLUDES config.php and .htaccess to preserve your settings.

What's New in v2.0:
-------------------
✓ Rebranded: ManageMyParking → MyParkingManager
✓ Login attempt limiting (5 tries, 10-minute lockout with countdown)
✓ Database module in permission matrix
✓ Database administration tab (consolidates Users, CSV, Bulk Operations)
✓ Bulk delete vehicles by property
✓ Find and remove duplicate vehicles
✓ Enhanced security features

Migration Steps:
----------------

1. BACKUP EVERYTHING
   - Create full backup of your current installation files
   - Export your database using phpMyAdmin (Export > SQL format)
   - Save backups in a safe location OFF the server

2. DATABASE MIGRATION
   - Import sql/migrate-v2-database-module.sql into your database
   - This migration script:
     * Creates login_attempts table
     * Adds 'database' to permissions module ENUM
     * Grants admin users database module permissions
     * Safe to run on existing installations
   
   - Use phpMyAdmin: Import > Choose file > migrate-v2-database-module.sql
   - Verify success: Check that login_attempts table exists

3. FILE UPLOAD
   - Upload all files EXCEPT config.php and .htaccess
   - Your existing config.php and .htaccess will be preserved
   - Overwrite all other files when prompted
   - Files to upload:
     * index.php (updated routing)
     * api/* (all API endpoints, including 2 new ones)
     * includes/* (updated helpers, constants)
     * public/* (rebranded UI, new Database tab)
     * sql/install.sql (for reference, don't run this)

4. VERIFY CONFIGURATION
   - Your existing config.php is preserved
   - If you want HTTPS by default, edit config.php:
     * Change session.secure from 'auto' to true
     * Only if your hosting supports HTTPS
   - No other config changes required

5. TEST THE UPGRADE
   - Clear your browser cache (Ctrl+F5 or Cmd+Shift+R)
   - Navigate to your application URL
   - Log in with your existing credentials
   - Verify the interface shows "MyParkingManager" branding
   - Check for new "Database" tab in navigation
   - Test login attempt limiting (try wrong password 5 times)

6. POST-MIGRATION TASKS
   - Review user permissions in Database tab
   - Admin users automatically get database module permissions
   - Other users default to no database access (add as needed)
   - Test bulk operations with sample data before using in production
   - Verify CSV import/export now located in Database tab

Permission Changes:
-------------------
The Database module controls access to:
- User management (create, edit, delete users)
- CSV import/export operations
- Bulk delete vehicles by property
- Find and remove duplicate vehicles

By default after migration:
- Admin role: Full database module access (all permissions)
- User/Operator roles: No database module access
- Customize permissions per user in Database > Users section

Backward Compatibility:
-----------------------
✓ Existing users, properties, vehicles preserved
✓ Existing permissions upgraded to new matrix system
✓ Legacy role-based access still works as fallback
✓ No data loss, no breaking changes
✓ Violations and audit logs maintained

Troubleshooting:
----------------
- If Database tab doesn't appear, clear browser cache
- If bulk operations don't work, verify migration script ran successfully
- If login locks after 1 try, check that login_attempts table exists
- If you see "ManageMyParking" still, clear browser cache
- For permission errors, verify admin users have database module permissions

Rollback Procedure (if needed):
--------------------------------
1. Restore your file backup from step 1
2. Restore your database backup using phpMyAdmin
3. Clear browser cache
4. Your system will be back to pre-migration state

Files Excluded from This Package:
----------------------------------
- config.php (preserves your database credentials and settings)
- .htaccess (preserves your Apache configuration)

If you need fresh copies of these files, use the FRESH-INSTALL package.

Support:
--------
Original deployment: https://2clv.com/jrk
Documentation: See replit.md for technical architecture

Version: 2.0.0
Migration from: 1.x
Release Date: October 24, 2025
