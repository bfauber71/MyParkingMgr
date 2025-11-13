================================================================================
STEP-BY-STEP DATABASE UPDATE FOR PHPMYADMIN
================================================================================

Since phpMyAdmin stops at the first error, run these files ONE AT A TIME.

INSTRUCTIONS:
-------------
1. Open phpMyAdmin
2. Select your database
3. Click "SQL" tab
4. Run each file below IN ORDER
5. If you get "Duplicate column" error, SKIP that file (column already exists)
6. Continue with the next file

FILES TO RUN (in order):
------------------------
01-add-custom-note.sql
02-add-issued-by-columns.sql
03-add-vehicle-snapshot.sql
04-add-tag-plate.sql
05-add-property-snapshot.sql
06-add-ticket-management.sql
07-add-payment-columns.sql
08-create-payment-tables.sql

AFTER ALL FILES:
----------------
Clear browser cache and test violations search!
