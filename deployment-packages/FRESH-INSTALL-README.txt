MyParkingManager v2.0 - Fresh Installation Package
====================================================

This package is for NEW installations only.

IMPORTANT: This package includes default configuration files with HTTPS enabled.
If you're upgrading an existing installation, use the MIGRATION package instead.

Installation Steps:
-------------------

1. DATABASE SETUP
   - Create a new MySQL database (recommended name: myparkingmanager)
   - Import sql/install.sql into your database using phpMyAdmin or command line
   - This creates all tables including the new login_attempts and permissions tables

2. CONFIGURATION
   - Edit config.php and update database credentials:
     * db.host (usually 'localhost')
     * db.database (your database name)
     * db.username (your database username)
     * db.password (your database password)
   
   - Review session settings in config.php:
     * session.secure is set to TRUE (requires HTTPS)
     * Change to FALSE only if your hosting doesn't support HTTPS

3. FTP UPLOAD
   - Upload ALL files to your web hosting directory (e.g., public_html/jrk/)
   - Ensure .htaccess file is uploaded (enable "show hidden files" in FTP client)
   - Set permissions if needed (usually 644 for files, 755 for directories)

4. APACHE CONFIGURATION
   - Ensure mod_rewrite is enabled on your server
   - Verify .htaccess is being processed
   - Check that AllowOverride is set to All for your directory

5. FIRST LOGIN
   - Navigate to your installation URL (e.g., https://yourdomain.com/jrk/)
   - Default admin credentials:
     Username: admin
     Password: admin123
   
   - IMPORTANT: Change the admin password immediately after first login!

6. SECURITY CHECKLIST
   - Change default admin password
   - Verify HTTPS is working (green padlock in browser)
   - Test login attempt limiting (should lock after 5 failed attempts)
   - Create additional users with appropriate permissions
   - Set up database backups through your hosting control panel

Features in v2.0:
-----------------
✓ Login attempt limiting (5 tries, 10-minute lockout)
✓ Granular permission matrix with Database module
✓ Database administration tab (Users, CSV, Bulk Operations)
✓ Bulk delete vehicles by property
✓ Find and remove duplicate vehicles
✓ Enhanced security with HTTPS default
✓ Rebranded to MyParkingManager

Database Structure:
-------------------
Tables created by install.sql:
- users (with default admin user)
- user_permissions (granular permissions for all 5 modules)
- properties (empty, add your properties)
- vehicles (empty, import or add manually)
- violation_types (empty, add violation types if needed)
- violation_tickets (violation history)
- login_attempts (tracks failed login attempts)
- audit_log (comprehensive activity logging)

Default Admin Permissions:
--------------------------
The default admin user has full permissions on all modules:
- Vehicles: view, edit, create/delete
- Users: view, edit, create/delete
- Properties: view, edit, create/delete
- Violations: view, edit, create/delete
- Database: view, edit, create/delete

Troubleshooting:
----------------
- If you get "Database connection failed", check config.php credentials
- If you see blank pages, check PHP error logs
- If routing doesn't work, verify .htaccess uploaded and mod_rewrite enabled
- If login fails, check that session.secure matches your HTTPS availability
- For HTTPS issues, contact your hosting provider

Support:
--------
Deployed at: https://2clv.com/jrk
Documentation: See replit.md for technical details

Version: 2.0.0
Release Date: October 24, 2025
