================================================================================
MyParkingManager v2.3.8 - GUEST PASS FIXES
================================================================================

ISSUES FIXED:
1. Property logo now displays correctly on guest pass
2. Expiration notice now shows large black letters on white background

================================================================================
CHANGES MADE
================================================================================

1. LOGO DISPLAY FIX (assets/app-secure.js):
   - Changed from relative path to absolute URL
   - Now uses: window.location.origin + basePath + /assets/logo.png
   - Logo will display in upper left corner of guest pass

2. EXPIRATION STYLING FIX (guest-pass-print.html):
   - Changed from white text on black background
   - Now: Large black text (60px) on white background
   - Font size nearly matches "GUEST PASS" title (72px)
   - Print-safe with !important declarations
   - Maintains 6px black border for emphasis

================================================================================
DEPLOYMENT
================================================================================

OPTION 1: QUICK FIX (27 KB)
----------------------------
Upload these 2 files from MyParkingManager-v2.3.8-GuestPassFix.zip:

1. guest-pass-print.html (root directory)
2. assets/app-secure.js (in assets folder)

OPTION 2: COMPLETE PACKAGE (159 KB)
------------------------------------
Use MyParkingManager-v2.3.8-Complete.zip for full deployment
(includes all fixes: license status + guest pass + all features)

================================================================================
WHAT YOU'LL SEE AFTER DEPLOYMENT
================================================================================

When printing a guest pass:

✓ Property logo appears in upper left corner
✓ Expiration notice shows as:
  - Large black text (60px font)
  - White background
  - Heavy black border (6px)
  - Text reads: "EXPIRES: MM-DD-YYYY"
✓ Professional, print-ready appearance

================================================================================
TECHNICAL DETAILS
================================================================================

Logo URL Construction:
- Uses window.location.origin for absolute path
- Includes basePath for subdirectory installations
- Falls back to /assets/logo.png if no property logo

Expiration Box Styling:
- Font-size: 60px (was 24px)
- Background: white (was black)
- Color: #000 (was white)
- Print-safe with -webkit-print-color-adjust: exact

================================================================================
Version: 2.3.8
Fix Date: November 11, 2025
================================================================================
