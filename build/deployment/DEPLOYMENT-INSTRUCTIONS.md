# MyParkingManager Deployment Instructions

## Prerequisites

1. **Web Server**: Apache or Nginx with PHP support
2. **PHP**: Version 7.4 or higher (8.0+ recommended)
3. **MySQL**: Version 5.7+ or MariaDB 10.2+
4. **Required PHP Extensions**:
   - pdo
   - pdo_mysql
   - json
   - session
   - mbstring

## Step 1: Upload Files

Upload all files from the `deployment` folder to your web server via FTP/SFTP.

## Step 2: Create MySQL Database

1. Log into your hosting control panel (cPanel, Plesk, etc.)
2. Navigate to MySQL Databases
3. Create a new database (e.g., `myparkingmanager`)
4. Create a new MySQL user with a strong password
5. Grant ALL PRIVILEGES to the user on the database
6. **Save these credentials - you'll need them in the next step!**

## Step 3: Configure Database Connection

### Option A: Use Setup Wizard (Recommended)

1. Navigate to `https://yourdomain.com/setup.php` in your browser
2. Follow the on-screen instructions
3. Enter your MySQL database credentials
4. The wizard will create tables and configure the system automatically

### Option B: Manual Configuration

1. Copy `config-template.php` to `config.php`
2. Edit `config.php` and set your database credentials:
   ```php
   'db' => [
       'host' => 'localhost',  // Usually 'localhost' for shared hosting
       'port' => '3306',
       'database' => 'your_database_name',
       'username' => 'your_db_username',
       'password' => 'your_db_password',
       'charset' => 'utf8mb4',
   ],
   ```
3. Run the SQL setup files in your MySQL database:
   - Upload `sql/schema.sql` via phpMyAdmin or MySQL command line
   - Upload `sql/sample-data.sql` (optional - creates test data)

## Step 4: Set File Permissions

Set the following permissions (via FTP or SSH):

```bash
chmod 644 config.php          # Read-only after configuration
chmod 755 assets/             # Web-accessible assets
chmod 755 api/                # API endpoints
```

## Step 5: Verify Installation

1. Navigate to your website URL
2. You should see the login page
3. Default credentials (created during setup):
   - Username: `admin`
   - Password: `admin123`
   - **CHANGE THIS IMMEDIATELY after first login!**

## Step 6: Security Configuration

### For Production Servers with HTTPS:

Edit `config.php`:
```php
'session' => [
    'secure' => true,  // Enforce HTTPS for sessions
],
```

### Recommended .htaccess for Apache:

The `.htaccess` file is included in the deployment. It provides:
- URL rewriting for clean API endpoints
- Security headers
- HTTPS enforcement (optional)

## Troubleshooting

### "MySQL server is not running"
- **Cause**: Incorrect database host in config.php
- **Solution**: Verify your MySQL hostname with your hosting provider (usually `localhost`)

### "Access denied for user"
- **Cause**: Incorrect username or password
- **Solution**: Double-check database credentials in cPanel/Plesk

### "Unknown database"
- **Cause**: Database doesn't exist
- **Solution**: Create the database in cPanel/Plesk first

### "500 Internal Server Error"
- **Cause**: PHP configuration or file permissions
- **Solution**: Check PHP error logs and file permissions

## Support

For issues or questions:
1. Check the troubleshooting section above
2. Review your hosting provider's PHP/MySQL documentation
3. Verify all prerequisites are met
