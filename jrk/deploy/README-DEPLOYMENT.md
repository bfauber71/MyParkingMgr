# MyParkingManager Deployment Guide

## Version 2.3.0

### Available Packages

#### 1. **mpm-full-v2.3.0.zip** - Complete New Installation
- **Size**: ~135KB
- **Use Case**: Fresh installations
- **Contains**: All files, setup wizard, SQL scripts, documentation
- **Requirements**: 
  - PHP 7.4+
  - MySQL 5.7+ or MariaDB 10.2+
  - Apache/Nginx with mod_rewrite

#### 2. **mpm-update-v2.3.0.zip** - Update Package
- **Size**: ~100KB  
- **Use Case**: Updating existing v2.0+ installations
- **Contains**: Updated files only (no setup wizard or SQL install scripts)
- **Excludes**: config.php, setup files, installation SQL

#### 3. **mpm-minimal-v2.3.0.zip** - Minimal Installation
- **Size**: ~80KB
- **Use Case**: Expert users, custom deployments
- **Contains**: Core application files only
- **Excludes**: Documentation, examples, setup wizard

#### 4. **mpm-docs-v2.3.0.zip** - Documentation Only
- **Size**: ~20KB
- **Contains**: All documentation, guides, and examples
- **Use Case**: Reference, offline documentation

### Installation Instructions

#### New Installation

1. **Upload Files**
   ```bash
   unzip mpm-full-v2.3.0.zip
   # Upload to your web directory (e.g., /var/www/html/parking)
   ```

2. **Set Permissions**
   ```bash
   chmod 755 .
   chmod 644 *.php
   chmod 755 api includes public sql admin
   ```

3. **Create Database**
   ```sql
   CREATE DATABASE myparkingmanager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   GRANT ALL PRIVILEGES ON myparkingmanager.* TO 'mpm_user'@'localhost' IDENTIFIED BY 'strong_password';
   ```

4. **Run Setup Wizard**
   - Navigate to: `https://yourdomain.com/your-path/setup-wizard.php`
   - Follow the multi-step setup process
   - Create your admin account

5. **Secure Installation**
   ```bash
   # After setup, remove setup files
   rm setup-wizard.php setup.php
   chmod 644 config.php
   ```

#### Updating from v2.0+

1. **Backup Current Installation**
   ```bash
   tar -czf backup-$(date +%Y%m%d).tar.gz /path/to/current/installation
   mysqldump myparkingmanager > backup-$(date +%Y%m%d).sql
   ```

2. **Upload Update Package**
   ```bash
   unzip mpm-update-v2.3.0.zip
   # Upload files, preserving config.php
   ```

3. **Run Database Migrations**
   ```sql
   SOURCE sql/add-fine-tow-columns.sql;
   SOURCE sql/add-license-keys-table.sql;
   ```

4. **Clear Cache**
   - Clear browser cache
   - Restart PHP-FPM/Apache if needed

#### Installing in Custom Directory

The application now supports installation in any directory:

1. **Upload to Your Chosen Directory**
   - Root: `/var/www/html/`
   - Subdirectory: `/var/www/html/parking-manager/`
   - Deep path: `/var/www/html/apps/vehicle/manager/`

2. **Configuration Will Auto-Detect**
   - Setup wizard automatically detects installation path
   - No manual path configuration needed

3. **To Relocate Later**
   - Move all files to new location
   - Update config.php:
     ```php
     'base_path' => '/new-path',  // URL path
     'install_path' => '/var/www/html/new-path',  // Server path
     ```

### System Requirements

#### Minimum Requirements
- PHP 7.4+ with extensions:
  - PDO_MySQL
  - JSON
  - Session
  - OpenSSL
  - MBString
- MySQL 5.7+ or MariaDB 10.2+
- Apache 2.4+ with mod_rewrite OR Nginx 1.18+
- 50MB disk space
- 128MB PHP memory limit

#### Recommended Requirements
- PHP 8.0+
- MySQL 8.0+ or MariaDB 10.5+
- HTTPS enabled
- 256MB PHP memory limit
- OPcache enabled

### Security Checklist

- [ ] Remove setup files after installation
- [ ] Set proper file permissions (644 for files, 755 for directories)
- [ ] Enable HTTPS
- [ ] Configure firewall rules
- [ ] Set strong database password
- [ ] Change LICENSE_SECRET_KEY in production
- [ ] Enable PHP error logging (disable display_errors)
- [ ] Regular backups configured
- [ ] Monitor access logs

### Troubleshooting

#### Common Issues

1. **404 Errors on API Calls**
   - Check .htaccess is present and mod_rewrite is enabled
   - Verify base_path in config.php matches your installation directory

2. **Database Connection Failed**
   - Verify database credentials in config.php
   - Check MySQL is running and accessible
   - Ensure database exists and user has privileges

3. **Blank Page After Installation**
   - Check PHP error logs
   - Verify PHP version meets requirements
   - Ensure all required PHP extensions are installed

4. **License System Not Working**
   - Set environment variable: `export MPM_LICENSE_SECRET_KEY=your-secret-key`
   - Run migrations: `SOURCE sql/migrate-license-system.sql`

### Support

For issues or questions:
1. Check the documentation in /docs
2. Review CHANGELOG.md for version-specific changes
3. Check error logs in your PHP error log location

### License

MyParkingManager v2.3.0 - Vehicle and Property Management System
Copyright (c) 2024