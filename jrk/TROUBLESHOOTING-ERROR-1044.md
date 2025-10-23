# Fixing Error #1044 - Access Denied

## The Problem
When running `migrate.sql`, you see:
```
#1044 - Access denied for user 'username'@'localhost' to database 'managemyparking'
```

## Why This Happens
The original migration script uses **stored procedures** (advanced MySQL features) that require elevated database permissions. Most shared hosting accounts only grant basic permissions for security reasons.

## ✅ The Solution
Use the simplified migration script instead!

### Quick Fix Steps:

1. **Use the Simple Migration Script**
   - In phpMyAdmin, click the SQL tab
   - Open file: `jrk/sql/migrate-simple.sql`
   - Copy ALL the contents
   - Paste into phpMyAdmin
   - Click "Go"

2. **That's it!** The simple script will:
   - ✅ Create all missing tables
   - ✅ Add 10 default violation types
   - ✅ Work with basic shared hosting permissions
   - ✅ Show "Migration Complete!" when done

### What's Different?
The simple version (`migrate-simple.sql`) does the same job but:
- ❌ No stored procedures
- ❌ No advanced privilege requirements
- ✅ Works with standard shared hosting
- ✅ Only needs: CREATE, INSERT, SELECT permissions

## Optional: Foreign Keys
After the simple migration succeeds, you can **optionally** try adding foreign keys for extra data protection:

1. Run `jrk/sql/migrate-add-foreign-keys.sql` in phpMyAdmin
2. If it fails → **That's okay!** The app works fine without them
3. If it succeeds → Great! You have extra data integrity

## Verification
After running `migrate-simple.sql`, verify success:

1. Check for "Migration Complete!" message
2. Look for violation_count (should show 10)
3. Check your tables list - you should now see:
   - `violations` ✅
   - `violation_tickets` ✅
   - `violation_ticket_items` ✅

4. Log in to ManageMyParking
5. Click "Violations" tab (Admin users only)
6. You should see 10 violation types listed

## Still Having Issues?

### Check Your Database Permissions
Your database user needs these permissions (ask your hosting provider):
- CREATE TABLE
- INSERT
- SELECT
- CREATE INDEX

### Alternative: Manual Table Creation
If even the simple script fails, you can create the tables manually:

1. Open `jrk/sql/install.sql`
2. Find these three CREATE TABLE statements:
   - `CREATE TABLE violations`
   - `CREATE TABLE violation_tickets`
   - `CREATE TABLE violation_ticket_items`
3. Copy each one individually and run in phpMyAdmin
4. Then run just the INSERT statements from `migrate-simple.sql`

## Success!
Once migration completes, all violation features will work:
- ✅ Violations tab (Admin)
- ✅ Create violation tickets
- ✅ Print tickets
- ✅ View violation history on vehicles
- ✅ "*Violations Exist" indicators

## Files Reference
- `migrate-simple.sql` ← **Use this one!**
- `migrate.sql` ← Skip (requires advanced permissions)
- `migrate-add-foreign-keys.sql` ← Optional extra security
