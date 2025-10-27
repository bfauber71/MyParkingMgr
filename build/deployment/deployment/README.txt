MyParkingManager v2.3.4 - Deployment Packages
==============================================

RELEASE DATE: October 27, 2025

NEW IN v2.3.4:
--------------
* Property-specific custom ticket text field
* Custom text displays on violation tickets below fine total
* Black & white ticket design for thermal printers
* Fine section: 3px solid black border (enhanced contrast)
* Tow warning: 3px double border with underline + ⚠ symbols
* Custom property text: 2px solid border, center-aligned
* All color removed from tickets for optimal thermal printing
* Printer settings now built into main Settings tab (no popup)

AVAILABLE PACKAGES:
------------------

1. myparkingmanager-v2.3.4-full.zip (~150 KB)
   - Complete application with all files
   - Includes setup wizard, diagnostic tools, license generators
   - Best for new installations
   
2. myparkingmanager-v2.3.4-minimal.zip (~140 KB)
   - Production-ready deployment without test/diagnostic files
   - Excludes: setup-wizard.php, diagnostic.php, README.md, test files
   - Best for clean production deployments
   
3. myparkingmanager-v2.3.4-update.zip (~30 KB)
   - RECOMMENDED UPGRADE PACKAGE
   - Contains only changed files:
     * index.html (709 lines, added custom_ticket_text field)
     * assets/app-secure.js (2,287 lines, property form handling)
     * violations-print.html (394 lines, B/W design + custom text)
     * api/properties-create.php (saves custom_ticket_text)
     * api/properties-update.php (updates custom_ticket_text)
     * api/violations-ticket.php (returns custom_ticket_text)
     * sql/add-custom-ticket-text.sql (migration for existing DBs)
     * sql/install.sql (updated schema for new installs)
   - Fastest way to upgrade from v2.3.0-v2.3.3
   
4. myparkingmanager-v2.3.4-docs.zip (~3 KB)
   - Documentation only: README.md
   - Reference material

UPGRADE INSTRUCTIONS (v2.3.0-v2.3.3 → v2.3.4):
-----------------------------------------------

OPTION A: Quick Update (Recommended - 3 minutes)
1. Download myparkingmanager-v2.3.4-update.zip
2. Extract the ZIP file
3. Upload these 8 files to your server, overwriting existing:
   - index.html
   - assets/app-secure.js
   - violations-print.html
   - api/properties-create.php
   - api/properties-update.php
   - api/violations-ticket.php
   - sql/add-custom-ticket-text.sql (NEW FILE)
   - sql/install.sql
4. Run database migration:
   - Open phpMyAdmin or MySQL client
   - Select your database
   - Import sql/add-custom-ticket-text.sql
   - This adds the custom_ticket_text column to properties table
5. Clear browser cache (Ctrl+F5 or Cmd+Shift+R)
6. Verify: Edit a property - you should see "Custom Ticket Text" field

OPTION B: Full Reinstall (5-10 minutes)
1. Backup your config.php file
2. Backup your database
3. Download myparkingmanager-v2.3.4-full.zip or -minimal.zip
4. Delete all files EXCEPT config.php and .htaccess
5. Extract and upload new files
6. Restore your config.php
7. Run sql/add-custom-ticket-text.sql if upgrading existing DB
8. Clear browser cache
9. Verify installation

NEW FEATURES USAGE:
------------------

Property-Specific Custom Ticket Text:
- Go to Properties tab
- Click "Add Property" or edit existing property
- Find "Custom Ticket Text" field below address
- Enter property-specific text (e.g., "For towing call: 555-1234")
- This text appears on all violation tickets for that property
- Displays below the fine total in a bordered box

Black & White Thermal Ticket Design:
- All tickets now print in pure black and white
- Enhanced contrast for thermal printers
- Fine amount: Bold with 3px solid border
- Tow warning: Bold with 3px double border and underline
- Custom property text: 2px solid border, centered
- No colors - optimal for receipt printers

Printer Settings in Settings Tab:
- Go to Settings tab (no longer popup window)
- Configure ticket size (width, height, units)
- Upload top and bottom logos
- Enable/disable logo display
- Save and reset buttons
- Settings apply to all ticket reprints

TECHNICAL DETAILS:
-----------------

Changes in v2.3.4:
- index.html: Added custom_ticket_text field to property form (709 lines)
- app-secure.js: Updated property form handler (2,287 lines, +29 lines)
- violations-print.html: B/W design + custom text display (394 lines)
- properties-create.php: Saves custom_ticket_text (102 lines)
- properties-update.php: Updates custom_ticket_text (128 lines)
- violations-ticket.php: Returns custom_ticket_text (100 lines)
- sql/install.sql: Added custom_ticket_text column to properties
- sql/add-custom-ticket-text.sql: Migration for existing installations

Database Changes:
- properties table: Added custom_ticket_text TEXT column
- Migration file provided for existing installations
- No data loss - new column nullable

System Requirements:
- PHP 7.4+ (8.0+ recommended)
- MySQL 5.7+ or MariaDB 10.2+
- Apache with mod_rewrite OR Nginx
- 10 MB disk space
- Modern web browser

FILE VERIFICATION:
-----------------
Verify package integrity using SHA256 checksums in checksums.txt

SUPPORT:
-------
For installation issues or questions, contact your system administrator.

LICENSE:
-------
MyParkingManager is proprietary software with a 30-day trial period.
License activation required after trial. See LICENSE-SYSTEM-GUIDE.md

VERSION HISTORY:
---------------
v2.3.4 (Oct 27, 2025) - Property custom text, B/W ticket design
v2.3.3 (Oct 27, 2025) - Version display, logo, violation search fixes
v2.3.2 (Oct 27, 2025) - Quick ticket creation & reprint features
v2.3.1 (Oct 27, 2025) - Print functionality & Settings tab
v2.3.0 (Oct 27, 2025) - Complete functionality fixes, API method corrections
v2.2.x - Database module improvements  
v2.1.x - Violation management enhancements
v2.0.x - License system implementation
v1.x.x - Initial release
