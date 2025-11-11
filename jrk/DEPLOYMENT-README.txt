================================================================================
MyParkingManager v2.3.8 - COMPLETE DISTRIBUTION PACKAGE
================================================================================

WHAT'S INCLUDED:
- Complete application with all features
- License system with 30-day trial
- Guest pass generation with expiration tracking
- Ticket status management (active/closed)
- CSV import/export with property filtering
- User management with search
- All bug fixes and improvements

DEPLOYMENT INSTRUCTIONS FOR 2clv.com:

1. BACKUP YOUR CURRENT INSTALLATION
   - Backup your database
   - Download current files as backup

2. EXTRACT THE PACKAGE
   - Extract MyParkingManager-v2.3.8-Distribution.zip
   - You will see the mpm-dist/ folder

3. UPLOAD ALL FILES
   Upload the contents of mpm-dist/ to your web root:
   
   Root Directory:
   - .htaccess
   - index.html
   - guest-pass-print.html
   - violations-print.html
   - router.php
   
   Folders (upload entire folders):
   - api/
   - assets/
   - includes/
   - sql/

4. SET FILE PERMISSIONS
   - Folders: 755
   - PHP files: 644
   - .htaccess: 644

5. DATABASE MIGRATIONS
   Run these SQL files in order (if not already run):
   - sql/add-guest-pass-expiration.sql
   - sql/add-ticket-status.sql

6. TEST THE INSTALLATION
   - Visit your site URL
   - Login with your credentials
   - Check: Settings → Users (should load all users)
   - Check: Settings → Database Operations
   - Click "Import Vehicles CSV" (should show modal)
   - Click "Export Vehicles CSV" (should show modal)

TROUBLESHOOTING:

If you get a 500 error:
- Check PHP error logs
- Verify all includes/ files uploaded correctly
- Ensure database credentials are correct in includes/database.php

If modals don't appear:
- Hard refresh browser (Ctrl+F5 or Cmd+Shift+R)
- Clear browser cache
- Verify index.html was uploaded

If license status fails:
- Verify includes/license.php exists
- Check that all includes/ files are present

SUPPORT:
Check browser console (F12) for JavaScript errors
Check server error logs for PHP errors

================================================================================
Version: 2.3.8
Build Date: November 11, 2025
================================================================================
