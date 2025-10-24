# MyParkingManager - Deployment Guide

## Package Contents

This deployment package contains the complete MyParkingManager system for shared hosting environments.

```
MyParkingManager/
├── api/                    # API endpoints (25 files)
├── includes/               # Core PHP libraries
│   ├── database.php       # Database connection
│   ├── helpers.php        # Permission & utility functions
│   ├── router.php         # URL routing
│   └── session.php        # Session management
├── public/                 # Frontend assets
│   ├── assets/
│   │   ├── app.js         # Application JavaScript
│   │   └── style.css      # Responsive dark theme
│   ├── index.html         # Main application
│   └── violations-print.html  # Ticket printing
├── sql/                    # Database scripts
│   ├── install.sql        # Fresh installation
│   └── migrate-permissions.sql  # Upgrade existing system
├── .htaccess              # Apache configuration
├── config.php             # Database configuration
└── index.php              # Application entry point
```

---

## Option A: Fresh Installation

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache with mod_rewrite enabled
- FTP access to shared hosting
- cPanel or phpMyAdmin access

### Step 1: Upload Files
1. Upload all files via FTP to your web directory (e.g., `public_html/jrk/`)
2. Maintain the directory structure exactly as provided

### Step 2: Configure Database
1. Edit `config.php` with your database credentials:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   ```

### Step 3: Create Database
1. Log into cPanel → MySQL Databases
2. Create a new database
3. Create a database user with full privileges
4. Note the database name, username, and password for config.php

### Step 4: Import Database Schema
1. Open phpMyAdmin
2. Select your database
3. Click "Import" tab
4. Upload and execute `sql/install.sql`
5. Verify tables are created (users, vehicles, properties, violations, user_permissions, etc.)

### Step 5: Access Application
1. Navigate to `https://your-domain.com/jrk/`
2. Log in with default credentials:
   - **Username:** `admin`
   - **Password:** `admin123`
3. **IMPORTANT:** Change the admin password immediately!

### Step 6: Create Users
1. Click "Users" tab
2. Create new users with custom permissions:
   - **Admin Preset:** Full access to all modules
   - **View Only Preset:** Read-only access to selected modules
   - **Custom:** Granular control over each module and action

---

## Option B: Upgrade Existing Installation

### Prerequisites
- Existing ManageMyParking installation with legacy role-based permissions
- Backup of database and files completed
- Database access via phpMyAdmin

### Step 1: Backup Everything
```sql
-- In phpMyAdmin, export your database
-- Export → Quick method → Go
```
Also backup all existing files via FTP.

### Step 2: Upload New Files
1. Upload all files via FTP, **overwriting** existing files
2. Keep your existing `config.php` (or update the new one with your credentials)

### Step 3: Run Migration Script
1. Open phpMyAdmin
2. Select your database
3. Click "SQL" tab
4. Open `sql/migrate-permissions.sql` in a text editor
5. Copy and paste the entire script
6. Click "Go" to execute

### Step 4: Verify Migration
```sql
-- Check permissions were created
SELECT u.username, u.role, up.module, up.can_view, up.can_edit, up.can_create_delete
FROM users u
LEFT JOIN user_permissions up ON u.id = up.user_id
ORDER BY u.username, up.module;
```

**Expected results:**
- Admin users: All permissions on all modules
- User role: Full permissions on vehicles only
- Operator role: View-only on vehicles only

### Step 5: Test Application
1. Log out and log back in
2. Verify permissions work correctly:
   - Admin: Sees all 4 tabs (Vehicles, Properties, Users, Violations)
   - User: Sees only Vehicles tab
   - Operator: Sees only Vehicles tab (view-only, no edit/delete buttons)

### Step 6: Customize Permissions (Optional)
1. As admin, go to Users tab
2. Edit any user to customize permissions
3. Use preset buttons or manually select permissions
4. Save and have user log out/in to see changes

---

## Permission Matrix System

### Overview
The new permission system provides granular control over user access:

- **4 Modules:** Vehicles, Users, Properties, Violations
- **3 Action Levels:**
  - **View:** Read-only access
  - **Edit:** Modify existing records (implies View)
  - **Create/Delete:** Add/remove records (implies Edit and View)

### Permission Hierarchy
```
Create/Delete → Edit → View
```
Granting Create/Delete automatically grants Edit and View permissions.

### Legacy Role Mapping
For backward compatibility, legacy roles map to these defaults:

| Role     | Vehicles | Users | Properties | Violations |
|----------|----------|-------|------------|------------|
| Admin    | All      | All   | All        | All        |
| User     | All      | None  | None       | None       |
| Operator | View     | None  | None       | None       |

After migration, you can customize any user's permissions via the Users tab.

---

## Security Features

- **Password Hashing:** bcrypt with automatic salting
- **SQL Injection Protection:** PDO prepared statements
- **XSS Prevention:** Output escaping via htmlspecialchars
- **Session Security:** HTTP-only cookies, auto-timeout
- **Permission Enforcement:** Both frontend and backend validation
- **Audit Logging:** All operations logged with user, timestamp, and details

---

## Troubleshooting

### Issue: Database connection error
**Solution:** Verify credentials in `config.php` match your cPanel database settings.

### Issue: .htaccess not working / 404 errors
**Solution:** Ensure Apache mod_rewrite is enabled. Contact hosting support if needed.

### Issue: Users tab not visible after login
**Solution:** Check user permissions. Only users with "Users" module view permission see this tab.

### Issue: Permission changes not taking effect
**Solution:** Log out completely and log back in to refresh session permissions.

### Issue: Migration script fails
**Solution:** Ensure you're running it on the correct database. Check phpMyAdmin for error messages.

---

## Post-Deployment Checklist

- [ ] Change default admin password
- [ ] Create additional admin user as backup
- [ ] Test login with different user roles
- [ ] Verify permission matrix UI displays correctly
- [ ] Test CRUD operations on vehicles, properties, users
- [ ] Test violation ticket printing
- [ ] Set up regular database backups (cPanel → Backups)
- [ ] Review audit logs periodically (audit_log table)

---

## System Requirements

**Minimum:**
- PHP 7.4+
- MySQL 5.7+
- Apache 2.4+ with mod_rewrite
- 50 MB disk space
- SSL certificate (recommended)

**Recommended:**
- PHP 8.0+
- MySQL 8.0+
- Regular automated backups
- HTTPS enabled

---

## Support & Documentation

For detailed feature documentation, see the application's built-in help or contact your system administrator.

**Database Tables:**
- `users` - User accounts and authentication
- `user_permissions` - Granular permission matrix
- `vehicles` - Vehicle records
- `properties` - Property management
- `violations` - Violation type definitions
- `violation_tickets` - Issued violation records
- `audit_log` - System activity tracking

---

## License & Copyright

ManageMyParking - Vehicle and Property Management System
Designed for shared hosting deployment with PHP and MySQL.
