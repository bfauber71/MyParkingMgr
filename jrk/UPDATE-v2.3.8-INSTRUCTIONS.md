# MyParkingManager v2.3.8 Update Instructions

## New Features in v2.3.8

### 1. Guest Pass Generation System
- Create guest vehicle records with automatic 7-day expiration
- Print professional guest passes on letter-size paper
- Includes property logo, vehicle information, and expiration date
- Automatic EXPIRED status display in vehicle search

### 2. Ticket Status Management
- Close tickets by marking fines as "Collected" or "Dismissed"
- Filter tickets by status (Active/Closed)
- Track who closed tickets and when
- Complete audit trail for ticket lifecycle

### 3. Streamlined Unknown Plate Workflow
- "Create Ticket for [PLATE]" button when searching unknown plates
- Automatic form pre-filling for faster ticket creation
- Seamless transition from vehicle creation to ticket creation

## Installation Steps

### Step 1: Backup Your Database
```sql
mysqldump -u username -p database_name > backup_$(date +%Y%m%d).sql
```

### Step 2: Upload Files via FTP/SFTP
Upload these files to your server, replacing existing files:
- `index.html` (updated navigation)
- `assets/app-secure.js` (updated JavaScript)
- `guest-pass-print.html` (new file)
- `api/guest-pass-create.php` (new file)
- `api/tickets-list.php` (new file)
- `api/ticket-close.php` (new file)

### Step 3: Run Database Migrations
Execute these SQL files in order using phpMyAdmin or MySQL command line:

1. **Add Guest Pass Expiration Field:**
```bash
mysql -u username -p database_name < sql/add-guest-pass-expiration.sql
```

2. **Add Ticket Status Management:**
```bash
mysql -u username -p database_name < sql/add-ticket-status.sql
```

**Or via phpMyAdmin:**
1. Log into phpMyAdmin
2. Select your database
3. Click "SQL" tab
4. Copy and paste the contents of `sql/add-guest-pass-expiration.sql`
5. Click "Go"
6. Repeat for `sql/add-ticket-status.sql`

### Step 4: Verify Installation
1. Clear your browser cache (Ctrl+Shift+Delete)
2. Log into MyParkingManager
3. Verify new menu items appear: "Guest Pass" and "Ticket Status"
4. Test creating a guest pass
5. Test ticket status management

## Database Schema Changes

### vehicles table
- Added `expiration_date` DATE field (nullable)
- Indexed for performance

### violation_tickets table
- Added `status` VARCHAR(20) field (default: 'active')
- Added `fine_disposition` VARCHAR(20) field (nullable)
- Added `closed_at` DATETIME field (nullable)
- Added `closed_by_user_id` VARCHAR(36) field (nullable)
- Added indexes for status and fine_disposition

## Features Overview

### Guest Pass Generation
1. Navigate to "Guest Pass" from main menu
2. Select property
3. Enter vehicle information
4. Click "Save & Print Guest Pass"
5. Guest pass opens in new window (ready to print)
6. Expiration date set automatically to 7 days from creation

### Ticket Status Management
1. Navigate to "Ticket Status" from main menu
2. Filter by status (Active/Closed) and/or property
3. Click "Collected" or "Dismissed" for active tickets
4. Closed tickets show status and disposition

### Guest Pass Status in Vehicle Search
- Guest pass vehicles show expiration date in "Guest Pass" column
- Expired passes display in red with "EXPIRED" text
- Makes it easy to identify vehicles with expired guest passes

## Permissions
- Guest Pass creation requires "Vehicles: Create/Delete" permission
- Ticket Status management requires "Database: Create/Delete" permission

## Troubleshooting

### SQL Migration Errors
If you encounter errors during migration:
- Error "Duplicate column" = Already installed, skip that migration
- Error "Table doesn't exist" = Check database connection
- Check constraint errors on MySQL < 8.0.16 = Remove CHECK CONSTRAINT lines from SQL

### Guest Pass Not Printing
- Ensure pop-up blockers are disabled
- Check browser allows new windows from your domain
- Try different browser (Chrome, Firefox, Safari)

### Expired Status Not Showing
- Clear browser cache
- Ensure SQL migration for expiration_date completed successfully
- Check browser console for JavaScript errors

## Support
For issues or questions, contact support or refer to the documentation.

## Version History
- v2.3.7 - ZPL Logo Integration, Streamlined Unknown Plate Workflow
- v2.3.8 - Guest Pass Generation, Ticket Status Management (Current)
