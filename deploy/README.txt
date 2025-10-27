===============================================
MyParkingManager v2.3.6 Deployment Packages
===============================================

This directory contains two installation packages:

1. myparkingmanager-v2.3.6-full.zip (144 KB)
   - Complete fresh installation
   - All 84 files included
   - Use for new installations

2. myparkingmanager-v2.3.6-update.zip (102 KB)
   - Update package only
   - 64 essential files
   - Use to upgrade from v2.3.5 or earlier

===============================================
WHEN TO USE EACH PACKAGE
===============================================

USE FULL INSTALLATION IF:
✓ Installing MyParkingManager for the first time
✓ Starting a new project
✓ Need setup wizard included
✓ Need complete documentation

USE UPDATE PACKAGE IF:
✓ Already running v2.3.5 or earlier
✓ Just need the latest fixes and features
✓ Want to minimize upload time
✓ Already have setup completed

===============================================
WHAT'S IN EACH PACKAGE
===============================================

FULL INSTALLATION INCLUDES:
- All API endpoints (40 files)
- All frontend assets
- Setup wizard (setup-wizard.php)
- Complete SQL schema (sql/install.sql)
- All migration files
- Configuration samples
- Complete documentation (INSTALLATION.txt)
- Admin tools
- License management

UPDATE PACKAGE INCLUDES:
- All API endpoints (40 files)
- Updated frontend assets
- SQL migrations only (2 files)
- Update instructions (UPGRADE-INSTRUCTIONS.txt)
- .htaccess and core files
- No setup wizard (not needed for updates)

===============================================
VERSION 2.3.6 FEATURES
===============================================

NEW IN v2.3.6:
✓ User property assignment
  - Assign specific properties to users
  - Users only see their assigned properties
  - Enhanced access control

NEW IN v2.3.5:
✓ Fixed login 500 error
✓ Fixed API routing issues
✓ Fixed printer logo upload
✓ MySQL compatibility improvements

===============================================
INSTALLATION QUICK START
===============================================

FRESH INSTALL (Full Package):
1. Extract myparkingmanager-v2.3.6-full.zip
2. Upload all files to your web server
3. Create MySQL database
4. Import sql/install.sql
5. Run setup-wizard.php in browser
6. Delete setup-wizard.php after setup
7. Login and start using

UPDATE INSTALL (Update Package):
1. Backup your database
2. Extract myparkingmanager-v2.3.6-update.zip
3. Upload files (overwrite existing)
4. Run 2 SQL migrations in phpMyAdmin:
   - sql/fix-printer-settings-column-size.sql
   - sql/add-user-assigned-properties-table.sql
5. Clear browser cache
6. Login and test

===============================================
SQL SCHEMA STATUS
===============================================

The install.sql file is COMPLETE and includes:

✓ All core tables (users, properties, vehicles, etc.)
✓ user_assigned_properties table (v2.3.6)
✓ custom_ticket_text in properties (v2.3.4)
✓ printer_settings with LONGTEXT (v2.3.5)
✓ All indexes and foreign keys
✓ Sample data for testing
✓ Default violation types

No additional migrations needed for fresh installs!

===============================================
SUPPORT
===============================================

For installation help:
- Read INSTALLATION.txt (full package)
- Read UPGRADE-INSTRUCTIONS.txt (update package)
- Check phpMyAdmin for database errors
- Enable PHP error display for troubleshooting

Version: 2.3.6
Release: October 27, 2025
