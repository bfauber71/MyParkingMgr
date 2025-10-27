# MyParkingManager - Shared Hosting Edition

Complete PHP and MySQL vehicle and property management system designed for shared hosting deployment.

## 🚀 Quick Start

### New Installation (Recommended: Use Setup Wizard)
1. Upload all files to your hosting via FTP
2. Create a MySQL database using cPanel/phpMyAdmin
3. **Navigate to `http://yoursite.com/path/setup-wizard.php`** in your browser
4. Follow the comprehensive setup wizard:
   - Step 1: Configure database connection
   - Step 2: Install database schema automatically
   - Step 3: Create your first admin user
   - Step 4: Complete setup
5. **Delete or rename setup files** for security (recommended):
   - `setup.php`
   - `setup-wizard.php`
   - `setup-test-db.php`
6. Access your site and login with the admin credentials you created

### Alternative: Manual Installation
1. Upload all files to your hosting via FTP
2. Create a MySQL database using cPanel
3. Copy `config-sample.php` to `config.php` and edit with your settings:
   - Set `app_url` to your full application URL
   - Set `base_path` to your subdirectory (e.g., `/parking`, `/myapp`) or empty for root
   - Configure database credentials
4. Import `sql/install.sql` via phpMyAdmin (now without default admin)
5. Run `setup-wizard.php` to create your first admin user
6. Login with the admin credentials you create during setup

### Migrating to Different Environment
If you need to move your installation to a new server or change paths:
1. Navigate to `http://yoursite.com/path/setup.php`
2. Enter the setup token (default: `reconfigure`, or custom token from config.php)
3. Update your configuration settings
4. Test database connection
5. Save new configuration

### Updating Existing Installation
If you already have MyParkingManager installed and need to add new features:
1. **Backup your database first!** (Export in phpMyAdmin)
2. Upload any new/updated files via FTP (do NOT overwrite config.php)
3. Run appropriate migration script in phpMyAdmin:
   - For v2.0 upgrade: `sql/migrate-v2-database-module.sql`
   - For full migration: `sql/migrate.sql`
4. Configuration changes are preserved in your existing config.php

## 📋 Features

### Core Functionality
- **Vehicle Management** - 14-field tracking system with search, CSV import/export
- **Property Management** - Multi-property support with up to 3 contacts each
- **User Management** - Granular permission matrix across all modules
- **Violation System** - Printable tickets, history tracking, type management, search & reports
- **Database Administration** - User management, CSV operations, bulk actions, violation reports

### Permission System (v2.0)
Granular permissions for all five modules (Vehicles, Properties, Users, Violations, Database):
- **View** - Read-only access to module data
- **Edit** - Modify existing records (implies view permission)
- **Create/Delete** - Add new and delete records (implies edit and view)

### Recent Features (v2.0 - October 2025)
- ✅ **Setup Wizard** - Visual configuration tool for installation and migration
- ✅ **Configuration Management** - Update settings without manual file editing
- ✅ **Granular Permission Matrix** - Per-user, per-module, per-action control
- ✅ **Login Attempt Limiting** - 5 tries, 10-minute lockout with countdown
- ✅ **Database Administration Tab** - Consolidated admin functions
- ✅ **Bulk Operations** - Delete by property, find/remove duplicates
- ✅ **Violation Search & Reports** - Advanced filtering, CSV export, print view
- ✅ **Mobile-First Responsive Design** - Optimized for all screen sizes
- ✅ **Enhanced Security** - HTTPS-first, secure session cookies

## 🔧 System Requirements

- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **Web Server:** Apache with .htaccess support
- **Hosting:** Standard shared hosting (cPanel compatible)

**No command-line access, Composer, or Node.js required!**

## 📁 File Structure

```
/                           # Installation root (any directory)
├── api/                    # API endpoints
├── includes/               # Core PHP classes and helpers
├── assets/                 # CSS, JavaScript assets
├── css/                    # Stylesheets
├── admin/                  # Admin configuration pages
├── sql/                    # Database scripts
│   ├── install.sql        # Fresh installation
│   ├── migrate.sql        # Full migration
│   └── migrate-v2-*.sql   # Incremental updates
├── index.html              # Main application
├── license.html            # License management page
├── violations-*.html       # Violation management pages
├── setup.php               # Setup wizard (delete after install)
├── setup-test-db.php       # Database test endpoint
├── config.php              # Configuration (edit or use setup.php)
├── config-sample.php       # Configuration template
├── index.php               # Router and entry point
└── .htaccess               # Apache configuration
```

## 🔒 Security Features

- Bcrypt password hashing
- PDO prepared statements (SQL injection prevention)
- XSS protection via htmlspecialchars
- HTTP-only session cookies with HTTPS-first config
- Granular permission-based access control
- Login attempt limiting with lockout
- Comprehensive audit logging
- Apache security headers
- Setup wizard authentication for reconfiguration

## 📦 Database Tables

- `users` - User accounts
- `user_permissions` - Granular permission matrix (v2.0)
- `properties` - Property information
- `property_contacts` - Up to 3 contacts per property
- `user_assigned_properties` - Property access assignments
- `vehicles` - 14-field vehicle records
- `violations` - Violation types/categories
- `violation_tickets` - Issued violation tickets
- `violation_ticket_items` - Ticket violation details
- `login_attempts` - Login security tracking (v2.0)
- `audit_logs` - Activity tracking
- `sessions` - Session management (optional)

## 🚨 Important Notes

### First Time Setup
1. The default admin password is `admin123` - **change it immediately**
2. Use the setup wizard (`setup.php`) for guided configuration
3. Delete or rename `setup.php` after installation for security
4. Ensure file permissions allow PHP to read all files

### Configuration Variables
All configuration is centralized in `config.php`:
- **app_url** - Full URL to your application (e.g., https://yourdomain.com/path)
- **base_path** - Subdirectory path (e.g., /parking, /myapp) or empty string for root
- **db** - Database host, port, name, username, password
- **setup_token** - Required to access setup.php after initial configuration

### Reconfiguration
To update configuration after installation:
1. Navigate to `setup.php` in your browser
2. Authenticate with your setup token (default: "reconfigure")
3. Update settings and save
4. For additional security, change the setup_token in config.php

### Production Deployment
For deployment to any environment:
1. Use setup wizard for initial configuration, OR
2. Manually edit config.php with correct app_url and base_path
3. The application uses server-provided configuration (no auto-detection)
4. Base path is injected into frontend automatically

### Troubleshooting
- **Login issues:** Clear browser cache and cookies, check login_attempts table
- **Configuration errors:** Use setup.php to test and update settings
- **Missing features:** Run appropriate migration script (see sql/ folder)
- **Database errors:** Check user has CREATE, ALTER, INSERT, SELECT permissions
- **Routing issues:** Verify .htaccess is working and mod_rewrite is enabled
- **Setup locked:** Check console for output; use setup_token from config.php

## 📖 Documentation

- `replit.md` - Technical architecture and system design
- `sql/` folder - Database schema and migration scripts
- Inline comments in code files

## 🆘 Support

For issues with:
- **Initial setup:** Use the setup wizard at `setup.php`
- **Database setup:** Contact your hosting provider
- **Configuration:** Use setup.php or edit config.php directly
- **File uploads:** Check FTP credentials and permissions
- **Missing features:** Ensure correct migration script has been run

## 📝 License & Credits

MyParkingManager v2.0 - Designed for small to medium-sized organizations using shared hosting environments.
Built with vanilla PHP, MySQL, HTML, CSS, and JavaScript - no frameworks, no build tools.
