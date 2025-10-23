# ManageMyParking - Shared Hosting Edition

Complete PHP and MySQL vehicle and property management system designed for shared hosting deployment.

## 🚀 Quick Start

### New Installation
1. Upload all files from the `jrk/` folder to your hosting via FTP
2. Create a MySQL database using cPanel
3. Import `jrk/sql/install.sql` via phpMyAdmin
4. Access your site and login with: `admin` / `admin123`
5. **Important:** Change the admin password immediately!

### Updating Existing Installation
If you already have ManageMyParking installed and need to add new features:
1. **Backup your database first!** (Export in phpMyAdmin)
2. Upload any new/updated files via FTP
3. Run `jrk/sql/migrate.sql` in phpMyAdmin
4. See `MIGRATION-GUIDE.md` for detailed instructions

## 📋 Features

### Core Functionality
- **Vehicle Management** - 14-field tracking system with search, CSV import/export
- **Property Management** - Multi-property support with up to 3 contacts each
- **User Management** - Role-based access control (Admin, User, Operator)
- **Violation System** - Printable tickets, history tracking, type management

### Role Permissions
- **Admin** - Full access to all features and data
- **User** - Manage vehicles for assigned properties, create violations
- **Operator** - View-only access to vehicle information

### Recent Features (October 2025)
- ✅ Violation history tracking - View up to 100 past violations per vehicle
- ✅ Violation type management - Admin-only CRUD for violation categories
- ✅ Printable violation tickets - 2.5" × 6" format
- ✅ Enhanced search UX - Empty state with clear button

## 🔧 System Requirements

- **PHP:** 7.4 or higher
- **MySQL:** 5.7 or higher
- **Web Server:** Apache with .htaccess support
- **Hosting:** Standard shared hosting (cPanel compatible)

**No command-line access, Composer, or Node.js required!**

## 📁 File Structure

```
jrk/
├── api/                    # API endpoints
├── includes/               # Core PHP classes and helpers
├── public/                 # Frontend assets
│   ├── index.html         # Main application
│   └── assets/            # CSS, JavaScript, images
├── sql/                    # Database scripts
│   ├── install.sql        # Fresh installation
│   └── migrate.sql        # Update existing database
├── index.php              # Router and entry point
└── .htaccess              # Apache configuration
```

## 🔒 Security Features

- Bcrypt password hashing
- PDO prepared statements (SQL injection prevention)
- XSS protection via htmlspecialchars
- HTTP-only session cookies
- Role-based access control
- Comprehensive audit logging
- Apache security headers

## 📦 Database Tables

- `users` - User accounts and roles
- `properties` - Property information
- `property_contacts` - Up to 3 contacts per property
- `user_assigned_properties` - Property access assignments
- `vehicles` - 14-field vehicle records
- `violations` - Violation types/categories
- `violation_tickets` - Issued violation tickets
- `violation_ticket_items` - Ticket violation details
- `audit_logs` - Activity tracking
- `sessions` - Session management (optional)

## 🚨 Important Notes

### First Time Setup
1. The default admin password is `admin123` - **change it immediately**
2. Configure your `.htaccess` RewriteBase if deploying to a subdirectory
3. Ensure file permissions allow PHP to read all files

### Production Deployment
For deployment to https://2clv.com/jrk:
1. Upload entire `jrk/` folder to your server
2. Update `.htaccess` RewriteBase to `/jrk/`
3. The app auto-detects the base path

### Troubleshooting
- **Login issues:** Clear browser cache and cookies
- **Missing features:** Run the migration script (see MIGRATION-GUIDE.md)
- **Database errors:** Check user has CREATE, ALTER, INSERT, SELECT permissions
- **Routing issues:** Verify .htaccess is working and mod_rewrite is enabled

## 📖 Documentation

- `MIGRATION-GUIDE.md` - How to update existing databases
- `replit.md` - Technical architecture and system design

## 🆘 Support

For issues with:
- **Database setup:** Contact your hosting provider
- **File uploads:** Check FTP credentials and permissions
- **Missing features:** Ensure migration script has been run

## 📝 License & Credits

Designed for small to medium-sized organizations using shared hosting environments.
Built with vanilla PHP, MySQL, HTML, CSS, and JavaScript - no frameworks, no build tools.
