# ManageMyParking - Shared Hosting Edition

## Project Overview

Complete PHP + MySQL vehicle and property management system restructured for **shared hosting deployment** (FTP-only, no frameworks, no build tools).

**Status:** Ready for Deployment  
**Deployment Target:** https://2clv.com/jrk (or any shared hosting)

## What Changed

**From:** Laravel 11 + React (required Composer, Node.js, command-line access)  
**To:** Plain PHP + Vanilla JavaScript (FTP upload only)

## Architecture

### Backend
- **Language:** Plain PHP 7.4+ (no framework)
- **Database:** MySQL 5.7+ with PDO
- **Authentication:** PHP sessions with password_hash/password_verify
- **Routing:** Custom front controller pattern

### Frontend
- **No Build Required:** Vanilla HTML/CSS/JavaScript
- **No Dependencies:** Works immediately after upload
- **Modern UI:** Responsive dark theme

## Project Structure

```
jrk/                         # Upload this entire folder via FTP
├── api/                     # API endpoint files
│   ├── login.php           # Authentication
│   ├── vehicles-search.php # Vehicle search
│   ├── vehicles-create.php # Vehicle creation
│   └── ...                 # Other endpoints
├── includes/               # Core PHP libraries
│   ├── database.php        # PDO database layer
│   ├── session.php         # Session management
│   ├── helpers.php         # Helper functions
│   └── router.php          # URL routing
├── public/                 # Frontend assets
│   ├── index.html          # Single-page app
│   └── assets/
│       ├── style.css       # Vanilla CSS
│       └── app.js          # Vanilla JavaScript
├── sql/
│   └── install.sql         # Database schema + seed data
├── config.php              # Configuration (edit for your hosting)
├── .htaccess               # Apache rewrite rules
├── index.php               # Front controller
├── INSTALLATION-GUIDE.md   # Complete cPanel/phpMyAdmin guide
└── README.txt              # Quick start
```

## Key Features

✅ **No Framework Dependencies** - Pure PHP, no Composer required  
✅ **No Build Tools** - No Node.js, npm, or webpack needed  
✅ **FTP Upload Only** - Works on any shared hosting  
✅ **cPanel Compatible** - Uses phpMyAdmin for database setup  
✅ **Complete Role-Based System:**
- **Admin:** Full access to vehicles, properties, and users (CRUD all)
- **User:** Manage vehicles only (CRUD vehicles for assigned properties)
- **Operator:** View-only access to vehicles (read-only)

✅ **Full-Featured Management:**
- Vehicle management (14 fields, search, edit, delete, export)
- Property management (create, delete)
- User management (create, delete, role assignment)
- Tabbed navigation interface
- Audit logging for all operations

## Deployment Package

**File:** `managemyparking-shared-hosting.zip` (32KB)  
**Contains:** 33 files ready for FTP upload

## Quick Deployment

1. **Download** package from Replit file browser
2. **Upload** `jrk/` folder to your web server via FTP
3. **Create** MySQL database in cPanel
4. **Import** `jrk/sql/install.sql` via phpMyAdmin
5. **Edit** `jrk/config.php` with database credentials
6. **Visit** https://2clv.com/jrk
7. **Login** with admin/admin123
8. **Change password** immediately!

See **INSTALLATION-GUIDE.md** for detailed step-by-step instructions.

## Testing on Replit

A PHP test server is running on port 5000 with **DEMO MODE** enabled.

✅ **Demo Features:**
- Auto-login as Admin user (bypasses database requirement)
- Shows all 3 tabs: Vehicles, Properties, Users
- Displays sample data (3 vehicles, 3 properties, 3 users)
- Search and filter work with demo data
- Full UI/UX preview without MySQL database

⚠️ **Note:** Demo mode ONLY works on Replit/localhost. On production (https://2clv.com/jrk), the app uses real MySQL database authentication and data.

## Default Credentials

**Admin Account:**
- Username: `admin`
- Password: `admin123`

⚠️ **Change immediately after first login!**

## What Works Immediately

- ✅ FTP upload and go
- ✅ phpMyAdmin database import
- ✅ Edit config.php with text editor
- ✅ Works on HTTP (HTTPS optional)
- ✅ No command-line access needed
- ✅ No special server configuration

## Security Features

- PDO prepared statements (SQL injection prevention)
- Password hashing with bcrypt
- PHP session security (HTTP-only cookies)
- XSS prevention with htmlspecialchars
- Role-based access control
- Comprehensive audit logging
- Apache security headers in .htaccess

## Sample Data Included

**Properties:** 3 sample properties  
**Vehicles:** 3 sample vehicles  
**Users:** 1 admin user

## Documentation

- **README.txt** - Quick start guide
- **INSTALLATION-GUIDE.md** - Complete cPanel/phpMyAdmin instructions with troubleshooting
- **config.php** - Inline configuration comments

## Recent Changes

**2025-10-23 (Latest):** Fixed property contacts and dropdown display
- **CRITICAL FIX:** Corrected properties API endpoint (properties.php) - was using non-existent helper functions
- **FEATURE:** Added property contacts display in Properties tab (shows Primary Contact, Phone, Email)
- **FIX:** Properties dropdown now properly populated (was showing blank due to broken API)
- Updated both `/api/properties` and `/api/properties-list` to include contact information via JOIN
- Enhanced demo mode with contact data for all 3 sample properties
- Property contacts now displayed in 3 new columns: Primary Contact, Contact Phone, Contact Email

**2025-10-23:** Fixed routing and environment auto-detection
- **CRITICAL FIX:** Added environment auto-detection for base paths (Replit vs production)
- **SECURITY FIX:** Added property access control to prevent unauthorized vehicle creation
- Fixed MIME type detection for CSS/JS files (proper browser rendering)
- Fixed API base path auto-detection in JavaScript
- Added tabbed navigation (Vehicles, Properties, Users)
- Implemented role-based menu visibility and permissions
- Created user management UI and API (Admin only)
- Created property management UI and API (Admin only)
- Fixed session cookie configuration for HTTP/HTTPS compatibility

**2025-10-22:** Complete restructure for shared hosting
- Removed Laravel framework and all dependencies
- Created plain PHP backend with PDO
- Built custom router and session handler
- Created vanilla JavaScript frontend (no build required)
- Generated complete SQL installation file
- Wrote comprehensive cPanel/phpMyAdmin deployment guide

## User Preferences

User required deployment to shared hosting without custom installations (no Composer, no command-line access, FTP-only).

## Architecture Decisions

1. **Plain PHP** instead of Laravel for zero-dependency deployment
2. **PDO with prepared statements** for database security
3. **Custom router** for RESTful URL structure
4. **PHP sessions** for authentication (no JWT complexity)
5. **Vanilla JS frontend** to eliminate build tools
6. **cPanel/phpMyAdmin** workflow for non-technical deployment
7. **Auto-detect HTTPS** for seamless HTTP→HTTPS migration
8. **Single SQL file** for one-click database installation
