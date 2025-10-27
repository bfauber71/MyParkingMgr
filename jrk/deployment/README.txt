MyParkingManager v2.3.3 - Deployment Packages
==============================================

RELEASE DATE: October 27, 2025

NEW IN v2.3.3:
--------------
* Version number displayed in navigation bar (v2.3.3)
* Stop sign logo in header (40x40px)
* Favicon added to all pages
* Violation search results show complete vehicle descriptions
* Vehicle column format: "ABC123 (2020 Blue Honda Civic)"
* Reprint Ticket button on ALL violation search results
* Fixed violation names display (was showing "N/A")
* Fixed property names display (was showing "N/A")

AVAILABLE PACKAGES:
------------------

1. myparkingmanager-v2.3.3-full.zip (~150 KB)
   - Complete application with all files
   - Includes setup wizard, diagnostic tools, license generators
   - Best for new installations
   
2. myparkingmanager-v2.3.3-minimal.zip (~140 KB)
   - Production-ready deployment without test/diagnostic files
   - Excludes: setup-wizard.php, diagnostic.php, README.md, test files
   - Best for clean production deployments
   
3. myparkingmanager-v2.3.3-update.zip (~27 KB)
   - RECOMMENDED UPGRADE PACKAGE
   - Contains only changed files:
     * assets/app-secure.js (2,150 lines, 66 functions)
     * assets/style.css (1,334 lines, logo & version styling)
     * assets/logo.png (40x40px stop sign logo)
     * index.html (666 lines, version & logo in header)
     * favicon.png (favicon for all pages)
   - Fastest way to upgrade from v2.3.0-v2.3.2
   
4. myparkingmanager-v2.3.3-docs.zip (~3 KB)
   - Documentation only: README.md
   - Reference material

UPGRADE INSTRUCTIONS (v2.3.0-v2.3.2 → v2.3.3):
-----------------------------------------------

OPTION A: Quick Update (Recommended - 2 minutes)
1. Download myparkingmanager-v2.3.3-update.zip
2. Extract the ZIP file
3. Upload these 5 files to your server, overwriting existing:
   - assets/app-secure.js
   - assets/style.css
   - assets/logo.png (NEW FILE)
   - index.html
   - favicon.png (NEW FILE)
4. Clear browser cache (Ctrl+F5 or Cmd+Shift+R)
5. Verify version shows "v2.3.3" in navigation bar
6. Verify logo appears in top right of header
7. Test violation search results display

OPTION B: Full Reinstall (5-10 minutes)
1. Backup your config.php file
2. Backup your database
3. Download myparkingmanager-v2.3.3-full.zip or -minimal.zip
4. Delete all files EXCEPT config.php and .htaccess
5. Extract and upload new files
6. Restore your config.php
7. Clear browser cache
8. Verify installation

NEW FEATURES USAGE:
------------------

Version & Logo Display:
- Version number appears below "MyParkingManager" in navigation
- Stop sign logo appears in top right corner of header
- Favicon shows on all browser tabs

Improved Violation Search Results:
- Go to Database tab → Violation Search section
- Search results now show complete vehicle descriptions
- Vehicle column: "ABC123 (2020 Blue Honda Civic)"
- Violation column: Shows actual violation names
- Property column: Shows actual property names
- "Reprint Ticket" button on every result
- Click reprint to open ticket in new window

TECHNICAL DETAILS:
-----------------

Changes in v2.3.3:
- app-secure.js: Fixed violation search results display (2,150 lines)
- app-secure.js: Enhanced vehicle description builder
- app-secure.js: Fixed field mappings (violation_type → violation_list)
- assets/style.css: Added brand logo and version styling (1,334 lines)
- assets/logo.png: 40x40px stop sign logo (NEW)
- favicon.png: Browser tab icon (NEW)
- index.html: Updated header with version and logo (666 lines)
- Total: 66 functions, improved UX throughout

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
v2.3.3 (Oct 27, 2025) - Version display, logo, violation search fixes
v2.3.2 (Oct 27, 2025) - Quick ticket creation & reprint features
v2.3.1 (Oct 27, 2025) - Print functionality & Settings tab
v2.3.0 (Oct 27, 2025) - Complete functionality fixes, API method corrections
v2.2.x - Database module improvements  
v2.1.x - Violation management enhancements
v2.0.x - License system implementation
v1.x.x - Initial release
