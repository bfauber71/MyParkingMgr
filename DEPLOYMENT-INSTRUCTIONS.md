# ManageMyParking - Production Deployment Instructions

## 📦 Package Contents

This deployment package (`ManageMyParking-Production-v1.0.zip`) contains the complete, production-ready application with all recent fixes:

### ✅ **Latest Updates Included (Oct 23, 2025):**
- **Fixed:** Session cookie path for subdirectory installs (`/jrk/`)
- **Fixed:** API base path auto-detection from pathname
- **Fixed:** Violation history pagination (5 per page)
- **Fixed:** Server caching with no-cache headers
- **Added:** Cache-busting for JavaScript files

---

## 🚀 Quick Deployment Steps

### **1. Upload Files via FTP**

1. Extract `ManageMyParking-Production-v1.0.zip` on your local computer
2. Connect to your hosting via FTP
3. Upload the **entire `jrk` folder** to your web root (e.g., `public_html/jrk/`)
4. Ensure all files are uploaded correctly

### **2. Configure Database Settings**

Edit `jrk/config.php` and update the database credentials:

```php
'db' => [
    'host' => 'localhost',              // Your MySQL host
    'port' => '3306',                    // Usually 3306
    'database' => 'managemyparking',     // Your database name
    'username' => 'your_db_username',    // Your MySQL username
    'password' => 'your_db_password',    // Your MySQL password
    'charset' => 'utf8mb4',
],
```

### **3. Create Database**

1. Log in to **phpMyAdmin** (via cPanel)
2. Create a new database named `managemyparking`
3. Click the **SQL** tab
4. Copy and paste the contents of `jrk/sql/install.sql`
5. Click **Go** to execute
6. Verify tables were created (should see 7 tables)

### **4. Set File Permissions**

Ensure proper permissions (via FTP or cPanel File Manager):
- Folders: `755`
- Files: `644`
- `.htaccess`: `644`

### **5. Test the Application**

1. Visit: `https://2clv.com/jrk`
2. Log in with default credentials:
   - **Username:** `admin`
   - **Password:** `admin123`
3. **Change the admin password immediately!**

---

## 📁 File Structure

```
jrk/
├── api/                          # API endpoints
│   ├── login.php                 # Authentication
│   ├── vehicles-*.php            # Vehicle management
│   ├── properties-*.php          # Property management
│   ├── users-*.php               # User management
│   ├── violations-*.php          # Violation management
│   └── vehicles-violations-history.php  # Violation history (with pagination)
├── includes/
│   ├── database.php              # Database connection
│   ├── session.php               # Session management (FIXED: cookie path)
│   ├── router.php                # URL routing
│   └── helpers.php               # Utility functions
├── public/
│   ├── assets/
│   │   ├── app.js                # Main JavaScript (FIXED: pathname detection)
│   │   └── style.css             # Styles
│   ├── index.html                # Main application (FIXED: cache-busting)
│   └── violations-print.html     # Printable violation tickets
├── sql/
│   ├── install.sql               # Initial database setup
│   └── migrate-simple.sql        # Migration for violation tracking
├── .htaccess                     # Apache configuration (FIXED: no-cache headers)
├── config.php                    # Application configuration
└── index.php                     # Front controller
```

---

## 🔧 Configuration Notes

### **Base Path Setting**

The application auto-detects the environment:
- **Production (2clv.com):** Uses `/jrk` as base path
- **Development (Replit/localhost):** Uses `/` as base path

This is handled automatically in `config.php`:

```php
$isReplit = getenv('REPL_ID') !== false || PHP_SAPI === 'cli-server';
$basePath = $isReplit ? '' : '/jrk';
```

### **Session Configuration**

Sessions are configured for subdirectory installs with proper cookie scoping:
- **Cookie Path:** Automatically set to `/jrk/` for production
- **HTTPS:** Auto-detected
- **HttpOnly:** Enabled for security
- **Lifetime:** 24 hours

---

## 🎯 Key Features Ready to Use

### **Vehicle Management**
- 14-field vehicle tracking
- Search with live filtering
- CSV import/export
- Violation count indicators
- Violation history modal (5 per page with pagination)

### **Property Management**
- Multi-property support
- 1-3 contacts per property
- Dynamic property assignments

### **Violation System**
- Admin-managed violation types
- Multi-select violation ticketing
- Printable 2.5" × 6" tickets
- Complete violation history tracking
- Pagination support (5 violations per page, up to 100 total)

### **User Management**
- Role-based access (Admin, User, Operator)
- Secure password hashing (bcrypt)
- Property-based access control

### **Audit Logging**
- Comprehensive activity tracking
- User attribution for all actions

---

## 🔐 Security Features

- ✅ Prepared statements (PDO) prevent SQL injection
- ✅ Password hashing with bcrypt
- ✅ Session security with HttpOnly cookies
- ✅ XSS prevention via htmlspecialchars
- ✅ Role-based access control
- ✅ HTTPS auto-detection
- ✅ Protected configuration files via .htaccess

---

## 🐛 Troubleshooting

### **Problem: "Unauthorized" errors on violation history**

**Solution:** This is now fixed in this version. The issue was:
1. Session cookie path wasn't scoped to `/jrk/`
2. JavaScript was calling `/api/...` instead of `/jrk/api/...`
3. Server was caching old JavaScript files

All three issues are resolved in this deployment package.

### **Problem: Violation history doesn't load**

**Cause:** Database migration not run

**Solution:**
1. Log in to phpMyAdmin
2. Run the SQL in `jrk/sql/migrate-simple.sql`
3. This creates the `violation_tickets` and `violation_ticket_items` tables

### **Problem: Changes not appearing after upload**

**Cause:** Server caching

**Solution:**
1. The `.htaccess` now includes no-cache headers for JS/HTML
2. Clear browser cache (Ctrl+Shift+R)
3. If still cached, contact hosting support to clear OPcache

### **Problem: Sessions not persisting**

**Cause:** Incorrect cookie path

**Solution:** Already fixed in this version - `session.php` now auto-detects base path

---

## 📊 Default Users

The `install.sql` creates three default users:

| Username | Password | Role | Access |
|----------|----------|------|--------|
| admin | admin123 | admin | Full access to all features |
| user1 | user123 | user | Manage vehicles for assigned properties |
| operator1 | operator123 | operator | View-only access to vehicles |

**⚠️ IMPORTANT:** Change these passwords immediately after first login!

---

## 📞 Post-Deployment Checklist

- [ ] Database created and install.sql executed
- [ ] config.php updated with correct database credentials
- [ ] Default admin password changed
- [ ] SSL/HTTPS working (optional but recommended)
- [ ] Test login functionality
- [ ] Test vehicle creation
- [ ] Test violation history feature
- [ ] Run migrate-simple.sql for violation tracking
- [ ] Create your properties
- [ ] Create your users
- [ ] Import your vehicle data (if any)

---

## 🎉 You're Ready!

Your ManageMyParking application is now deployed and ready to use!

Visit: **https://2clv.com/jrk**

For support or questions, refer to the included documentation files:
- `INSTALLATION-GUIDE.md` - Detailed setup instructions
- `MIGRATION-GUIDE.md` - Database migration information
- `TROUBLESHOOTING-ERROR-1044.md` - Common database errors

---

**Version:** 1.0 (October 23, 2025)  
**Package:** ManageMyParking-Production-v1.0.zip  
**Deployment:** Shared Hosting (FTP-only, no CLI required)
