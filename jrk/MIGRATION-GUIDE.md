# Database Migration Guide

## Purpose
This guide helps you update an existing ManageMyParking database to include all the latest features and fields, including the violation tracking system.

## When to Use This
Use this migration script if:
- You have an existing ManageMyParking installation
- You're getting errors about missing tables or columns
- You need to add the violation tracking system to an existing database
- You want to ensure your database has all the latest fields

## Migration Steps

### Step 1: Backup Your Database
**IMPORTANT:** Always backup your database before running migrations!

In phpMyAdmin:
1. Select your database (usually `managemyparking`)
2. Click the "Export" tab
3. Choose "Quick" export method
4. Click "Go" to download the backup
5. Save this file in a safe location

### Step 2: Run the Migration Script

#### Option A: Using phpMyAdmin (Recommended for Shared Hosting)
1. Log in to phpMyAdmin via cPanel
2. Select your database (e.g., `managemyparking`)
3. Click the "SQL" tab at the top
4. Open the file `jrk/sql/migrate-simple.sql`
5. Copy the entire contents
6. Paste into the SQL query box
7. Click "Go" to execute

**Note:** Use `migrate-simple.sql` instead of `migrate.sql` - it's designed for shared hosting with limited permissions.

#### Option B: Using MySQL Command Line
```bash
mysql -u your_username -p managemyparking < jrk/sql/migrate-simple.sql
```

### Step 2b: Add Foreign Keys (Optional)
Foreign keys provide extra data protection but aren't required. If your hosting supports them:
1. In phpMyAdmin SQL tab
2. Copy contents of `jrk/sql/migrate-add-foreign-keys.sql`
3. Paste and click "Go"
4. **If this fails, it's okay!** The app works fine without foreign keys.

### Step 3: Verify Migration Success

After running the migration, you should see:
- "Migration Complete!" message
- Count of violations in the database
- List of all tables with their sizes

Check that these tables exist:
- ✅ users
- ✅ properties
- ✅ property_contacts
- ✅ user_assigned_properties
- ✅ vehicles
- ✅ violations (NEW)
- ✅ violation_tickets (NEW)
- ✅ violation_ticket_items (NEW)
- ✅ audit_logs
- ✅ sessions

## What This Migration Adds

### New Tables
1. **violations** - Stores violation types that can be selected when creating tickets
2. **violation_tickets** - Records each violation ticket issued
3. **violation_ticket_items** - Links specific violations to each ticket

### New Features Enabled
- Violation type management (Admin only)
- Printable violation tickets
- Violation history tracking
- Up to 100 violations displayed per vehicle

### Default Violations Added
The migration automatically adds these 10 common parking violations:
1. Expired Parking Permit
2. No Parking Permit Displayed
3. Parked in Reserved Space
4. Parked in Fire Lane
5. Parked in Handicapped Space Without Permit
6. Blocking Dumpster/Loading Zone
7. Double Parked
8. Parked Over Line/Taking Multiple Spaces
9. Abandoned Vehicle
10. Commercial Vehicle in Residential Area

## Troubleshooting

### Error: "Table already exists"
This is normal and safe - the script checks for existing tables and skips creation if they already exist.

### Error: "Foreign key constraint fails"
This may happen if you have orphaned data. Options:
1. Clean up orphaned records manually
2. Temporarily disable foreign key checks (advanced users only)

### Error: "Access denied" or Error #1044
This usually means limited database permissions. **Solution:**

1. Use `migrate-simple.sql` instead of `migrate.sql`
2. The simple version only needs basic permissions:
   - CREATE (for tables)
   - INSERT (for default data)
   - SELECT (for verification)
   - INDEX (for performance)

3. If still getting errors, ask your hosting provider to grant these permissions to your database user

4. As a last resort, you can create the tables manually using the SQL from `install.sql` and just copy the violation-related CREATE TABLE statements

## Safe to Run Multiple Times
This migration script is **idempotent**, meaning you can run it multiple times safely. It will:
- Skip creating tables that already exist
- Skip adding columns that already exist
- Skip adding indexes that already exist
- Only insert default violations if the table is empty

## Need Help?
If you encounter issues:
1. Check your database user has sufficient permissions
2. Verify you're connected to the correct database
3. Review the phpMyAdmin error messages
4. Restore from backup if needed and try again
5. Contact your hosting provider for database support

## After Migration
Once migration is complete:
1. Log in to ManageMyParking
2. Go to the Violations tab (Admin users only)
3. Verify the default violations are listed
4. Test creating a violation ticket
5. Check vehicle violation history displays correctly
