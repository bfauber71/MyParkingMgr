# MyParkingManager Deployment Packages

This directory contains deployment packages for MyParkingManager v2.0.

## 📦 Available Packages

### 1. mpm-new-install-v2.0.zip (98KB)
**Purpose:** Complete package for new installations

**Contains:**
- ✅ All application files (API, frontend, includes)
- ✅ Setup wizard files (setup.php, setup-wizard.php)
- ✅ SQL installation scripts
- ✅ Configuration template (config-sample.php)
- ✅ Complete documentation (README.md)
- ✅ Installation guide

**Use When:**
- Installing MyParkingManager for the first time
- Setting up a fresh instance on a new server
- Creating a test/development environment

**Installation Process:**
1. Extract all files to your web directory
2. Create MySQL database
3. Run setup-wizard.php in browser
4. Follow the 4-step setup process
5. Delete setup files after completion

---

### 2. mpm-update-v2.0.zip (72KB)
**Purpose:** Lightweight update package for existing installations

**Contains:**
- ✅ Updated application files only
- ✅ API improvements
- ✅ Frontend updates
- ✅ Security patches
- ✅ Update instructions

**Does NOT Contain:**
- ❌ config.php (preserves your settings)
- ❌ Setup files (not needed for updates)
- ❌ SQL scripts (use original if needed)
- ❌ Installation documentation

**Use When:**
- Updating an existing MyParkingManager installation
- Applying bug fixes and security updates
- Upgrading from v1.x to v2.0

**Update Process:**
1. Backup your database and files
2. Extract files, DO NOT overwrite config.php
3. Run any required SQL migrations
4. Clear browser cache
5. Test all functionality

## 🔒 Security Notes

### Files Removed from Production:
The following development/setup files have been excluded from production packages or should be deleted after installation:

- `diagnostic.php` - Development diagnostic tool
- `setup-test-db.php` - Database testing endpoint
- `SECURITY-AUDIT-REPORT.md` - Internal documentation
- `*.backup` files - Temporary backups
- Setup files (after installation)

## 📋 Version Information

**Current Version:** 2.0  
**Release Date:** October 2024  
**Package Created:** October 24, 2024

### Key Features in v2.0:
- ✅ Secure admin user creation (no default passwords)
- ✅ Granular permission system
- ✅ Enhanced security (CSRF, XSS protection)
- ✅ Violation ticketing system
- ✅ Database administration module
- ✅ Mobile-responsive design

## 🚀 Deployment Checklist

### For New Installations:
- [ ] Verify PHP 7.4+ and MySQL 5.7+ available
- [ ] Create database and user
- [ ] Upload mpm-new-install-v2.0.zip contents
- [ ] Run setup wizard
- [ ] Create admin account
- [ ] Delete setup files
- [ ] Configure permissions

### For Updates:
- [ ] Backup database
- [ ] Backup config.php
- [ ] Backup current installation
- [ ] Upload mpm-update-v2.0.zip contents
- [ ] Skip config.php when uploading
- [ ] Run migrations if needed
- [ ] Test functionality
- [ ] Clear caches

## 📞 Support

For installation support or issues:
1. Check INSTALLATION.txt or UPDATE_GUIDE.txt in packages
2. Refer to main README.md for detailed documentation
3. Verify system requirements are met
4. Check error logs if problems persist