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
✅ **Same Functionality** - All core features preserved:
- Vehicle management (14 fields)
- Multi-property support
- Role-based access control
- Search functionality
- CSV export
- Audit logging

## Deployment Package

**File:** `managemyparking-shared-hosting.zip` (24KB)  
**Contains:** 26 files ready for FTP upload

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

A PHP test server is running on port 5000 for preview purposes only. This is NOT the production deployment - just a preview.

⚠️ **Note:** The database connection will fail on Replit (no MySQL). To actually use the application, deploy to your shared hosting.

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

**2025-10-22:** Complete restructure for shared hosting
- Removed Laravel framework and all dependencies
- Created plain PHP backend with PDO
- Built custom router and session handler
- Created vanilla JavaScript frontend (no build required)
- Generated complete SQL installation file
- Wrote comprehensive cPanel/phpMyAdmin deployment guide
- Fixed session cookie configuration for HTTP/HTTPS compatibility

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
