MyParkingManager v2.3.1 - Deployment Packages
==============================================

RELEASE DATE: October 27, 2025

NEW IN v2.3.1:
--------------
* Violation Search Results Print on 8.5x11 letter paper
* Settings tab with printer configuration access  
* Professional print layout with auto-repeating headers
* Informational guides for ticket vs. search result printing

AVAILABLE PACKAGES:
------------------

1. myparkingmanager-v2.3.1-full.zip (146 KB)
   - Complete application with all files
   - Includes setup wizard, diagnostic tools, license generators
   - Best for new installations
   
2. myparkingmanager-v2.3.1-minimal.zip (136 KB)
   - Production-ready deployment without test/diagnostic files
   - Excludes: setup-wizard.php, diagnostic.php, README.md, test files
   - Best for clean production deployments
   
3. myparkingmanager-v2.3.1-update.zip (17 KB)
   - RECOMMENDED UPGRADE PACKAGE
   - Contains only changed files:
     * assets/app-secure.js (1,978 lines, 64 functions)
     * index.html (659 lines, Settings tab added)
   - Fastest way to upgrade from v2.3.0
   
4. myparkingmanager-v2.3.1-docs.zip (3.5 KB)
   - Documentation only: README.md
   - Reference material

UPGRADE INSTRUCTIONS (v2.3.0 → v2.3.1):
---------------------------------------

OPTION A: Quick Update (Recommended - 2 minutes)
1. Download myparkingmanager-v2.3.1-update.zip
2. Extract the ZIP file
3. Upload these 2 files to your server, overwriting existing:
   - assets/app-secure.js
   - index.html
4. Clear browser cache (Ctrl+F5 or Cmd+Shift+R)
5. Test the new Settings tab and print functionality

OPTION B: Full Reinstall (5-10 minutes)
1. Backup your config.php file
2. Backup your database
3. Download myparkingmanager-v2.3.1-full.zip or -minimal.zip
4. Delete all files EXCEPT config.php and .htaccess
5. Extract and upload new files
6. Restore your config.php
7. Clear browser cache
8. Verify installation

NEW FEATURES USAGE:
------------------

Settings Tab:
- Click "Settings" in main navigation
- Access printer configuration for violation tickets
- Read guides for print functionality

Violation Search Results Print:
- Go to Database tab
- Use Violation Search section
- Enter search criteria and click Search
- Click "Print Results" button
- Print window opens with 8.5x11 formatted table
- Click Print or Close

TECHNICAL DETAILS:
-----------------

Changes in v2.3.1:
- app-secure.js: Enhanced handleViolationPrint() function
- app-secure.js: Added loadSettingsSection() function  
- index.html: Added Settings tab navigation
- index.html: Added Settings section content
- Total: 64 functions, 1,978 lines JavaScript
- Print: Letter size (8.5" x 11") with @page CSS
- Print: Table headers repeat on each page

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
v2.3.1 (Oct 27, 2025) - Print functionality & Settings tab
v2.3.0 (Oct 27, 2025) - Complete functionality fixes, API method corrections
v2.2.x - Database module improvements  
v2.1.x - Violation management enhancements
v2.0.x - License system implementation
v1.x.x - Initial release
