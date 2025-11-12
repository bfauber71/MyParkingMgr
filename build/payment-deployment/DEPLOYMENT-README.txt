================================================================================
MyParkingManager v2.3.8 - COMPLETE DEPLOYMENT PACKAGE
================================================================================

WHAT'S NEW IN THIS VERSION:
✓ License status fix (shared-hosting safe architecture)
✓ Guest pass logo fix (uses same source as violation tickets)
✓ Guest pass expiration styling (large black text, no border)
✓ All v2.3.8 features included

================================================================================
CRITICAL FIXES INCLUDED
================================================================================

1. LICENSE STATUS FIX
   - License data embedded in app-config.php
   - No separate endpoints needed
   - Works even if MySQL temporarily unavailable
   - Shared-hosting safe architecture

2. GUEST PASS LOGO FIX
   - Now loads from printer settings (same as violation tickets)
   - Displays the saved property logo
   - No more app icon showing instead of logo

3. GUEST PASS EXPIRATION STYLING
   - Large black text (60px font)
   - No border box
   - Transparent background
   - Stays within 1/2 page cutoff

================================================================================
DEPLOYMENT TO 2clv.com
================================================================================

1. BACKUP FIRST
   - Backup your current database
   - Download current files as backup

2. EXTRACT PACKAGE
   - Extract MyParkingManager-v2.3.8-Complete.zip
   - You will see these folders:
     * api/
     * assets/
     * includes/
     * sql/

3. UPLOAD ALL FILES
   Upload everything to your web root:
   
   Root Directory:
   - .htaccess
   - index.html
   - guest-pass-print.html
   - violations-print.html
   - router.php
   
   Folders (upload entire folders):
   - api/ (all files)
   - assets/ (all files)
   - includes/ (all files)
   - sql/ (all files)

4. FILE PERMISSIONS
   - Folders: 755
   - PHP files: 644
   - .htaccess: 644

5. DATABASE MIGRATIONS (if needed)
   Run these SQL files in order if not already run:
   - sql/add-guest-pass-expiration.sql
   - sql/add-ticket-status.sql

6. CLEAR BROWSER CACHE
   - Hard refresh: Ctrl+F5 (Windows) or Cmd+Shift+R (Mac)
   - Or clear cache in browser settings

================================================================================
VERIFICATION CHECKLIST
================================================================================

After deployment, verify:

✓ Login works
✓ License status badge appears (TRIAL/EXPIRED or nothing)
✓ Settings → Users loads all users
✓ Import/Export modals appear
✓ Guest pass shows property logo (not app icon)
✓ Guest pass expiration is large black text
✓ Violation tickets print with logo

================================================================================
TROUBLESHOOTING
================================================================================

If license status doesn't show:
- Check that api/app-config.php was uploaded
- Check that includes/database.php was uploaded
- Hard refresh browser (Ctrl+F5)

If guest pass logo doesn't show:
- Verify you have uploaded a logo in Settings → Printer Settings
- Check that guest-pass-print.html was uploaded
- Make sure you're logged in when viewing guest pass

If modals don't appear:
- Verify index.html was uploaded
- Clear browser cache completely
- Try different browser

================================================================================
FILES REMOVED
================================================================================

These old files are NO LONGER USED and were removed:
- api/license-status.php
- api/license-status-v2.php

If you have these files on your server, you can safely delete them.

================================================================================
VERSION INFORMATION
================================================================================

Version: 2.3.8
Build Date: November 11, 2025
Package Size: 160 KB
Total Files: 94

Includes:
- License system with 30-day trial
- Guest pass generation with expiration tracking
- Ticket status management (active/closed)
- CSV import/export with property filtering
- User management with search
- Violation ticket printing
- ZPL thermal printer support

================================================================================
SUPPORT
================================================================================

Check browser console (F12) for JavaScript errors
Check server error logs for PHP errors

This package is production-ready for 2clv.com shared hosting.

================================================================================
