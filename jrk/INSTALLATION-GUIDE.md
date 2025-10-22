# ManageMyParking - Shared Hosting Installation Guide

## ✅ What You Need

- **Shared hosting account** with cPanel or similar control panel
- **PHP 7.4+** (PHP 8.0+ recommended)
- **MySQL 5.7+** (MySQL 8.0+ recommended)
- **FTP access** or File Manager in cPanel
- **phpMyAdmin** or MySQL database access
- **Domain:** 2clv.com configured to point to your hosting

## 📁 What's in This Package

```
jrk/
├── api/                # API endpoint files
├── includes/           # Core PHP files
├── public/             # Frontend (HTML/CSS/JS)
├── sql/               # Database installation
├── config.php         # Configuration file
├── .htaccess          # Apache configuration
└── index.php          # Main entry point
```

## 🚀 Installation Steps

### Step 1: Upload Files via FTP

1. **Extract the zip file** on your computer
2. **Connect to your server** via FTP (FileZilla, cPanel File Manager, etc.)
3. **Navigate** to your public_html folder
4. **Upload** the entire `jrk` folder
5. **Verify** the structure looks like: `public_html/jrk/`

**Or using cPanel File Manager:**
1. Login to cPanel
2. Open File Manager
3. Navigate to `public_html`
4. Click "Upload" and upload the zip file
5. Right-click the zip → "Extract"
6. Rename extracted folder to `jrk`

### Step 2: Create MySQL Database

**Using cPanel:**
1. Login to cPanel
2. Find "MySQL Databases" or "MySQL Database Wizard"
3. Create a new database:
   - Database name: `managemyparking` (or your choice)
   - Click "Create Database"
4. Create a database user:
   - Username: choose a secure username
   - Password: generate a strong password
   - Click "Create User"
5. Add user to database:
   - Select the user and database
   - Grant "ALL PRIVILEGES"
   - Click "Add"

**Write down:**
- ✓ Database name: ________________
- ✓ Database username: ________________  
- ✓ Database password: ________________
- ✓ Database host: usually `localhost`

### Step 3: Install Database Schema

**Using phpMyAdmin:**
1. Login to cPanel
2. Open "phpMyAdmin"
3. Select your database from the left sidebar
4. Click the "Import" tab
5. Click "Choose File" and select `jrk/sql/install.sql`
6. Click "Go" at the bottom
7. Wait for "Import has been successfully finished"

**Or using SQL tab:**
1. In phpMyAdmin, select your database
2. Click the "SQL" tab
3. Open `jrk/sql/install.sql` in a text editor
4. Copy all the SQL code
5. Paste into the SQL tab
6. Click "Go"

**Verify installation:**
- You should see 7 tables created
- Check "Browse" on the `users` table - should show 1 admin user
- Check `properties` table - should show 3 sample properties
- Check `vehicles` table - should show 3 sample vehicles

### Step 4: Configure Application

1. **Edit config.php** via FTP or File Manager:
   ```
   Navigate to: public_html/jrk/config.php
   Right-click → Edit
   ```

2. **Update database settings:**
   ```php
   'db' => [
       'host' => 'localhost',           // Usually 'localhost'
       'port' => '3306',                // Usually '3306'
       'database' => 'your_db_name',    // Database name from Step 2
       'username' => 'your_db_user',    // Username from Step 2
       'password' => 'your_db_pass',    // Password from Step 2
       'charset' => 'utf8mb4',
   ],
   ```

3. **Update app_url:**
   ```php
   'app_url' => 'https://2clv.com/jrk',
   ```

4. **Save the file**

### Step 5: Test Installation

1. **Visit your application:**
   ```
   https://2clv.com/jrk
   ```

2. **You should see the login page**

3. **Login with default credentials:**
   - Username: `admin`
   - Password: `admin123`

4. **You should see the dashboard** with 3 sample vehicles

### Step 6: Secure Your Application

**IMPORTANT - Do this immediately!**

1. **Change admin password:**
   - Currently, there's no UI for this
   - Use phpMyAdmin to update the password:
     ```sql
     UPDATE users 
     SET password = '$2y$10$YOUR_NEW_BCRYPT_HASH' 
     WHERE username = 'admin';
     ```
   - Or create a new admin user via phpMyAdmin

2. **Enable HTTPS (if not already):**
   - In cPanel, look for "SSL/TLS" or "Let's Encrypt"
   - Install a free SSL certificate
   - Force HTTPS redirect

3. **Update config.php for HTTPS:**
   ```php
   'session' => [
       'secure' => true,  // Now that HTTPS is enabled
   ],
   ```
   
   Or use auto-detection:
   ```php
   'session' => [
       'secure' => 'auto',  // Automatically detects HTTP vs HTTPS
   ],
   ```

4. **Uncomment HTTPS redirect in .htaccess:**
   - Edit `jrk/.htaccess`
   - Remove the `#` from these lines:
     ```
     # RewriteCond %{HTTPS} off
     # RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
     ```

## 📝 Usage

### Login
- Visit: `https://2clv.com/jrk`
- Enter username and password
- Click "Sign In"

### Search Vehicles
- Use the search box to search by tag, plate, owner, apartment, etc.
- Use the property filter to show vehicles from specific properties
- Click "Search" or press Enter

### Add Vehicle
- Click "Add Vehicle" button
- Fill in the form (only Property is required)
- Click "Save Vehicle"

### Export Vehicles
- Click "Export CSV" button
- CSV file will download with all accessible vehicles

## 🔧 Troubleshooting

### Problem: "Database connection failed"
**Solution:**
- Check `config.php` has correct database credentials
- Verify database exists in phpMyAdmin
- Check database user has privileges

### Problem: "404 Not Found" for all pages
**Solution:**
- Check `.htaccess` file exists in `/jrk/` folder
- Verify mod_rewrite is enabled (ask your hosting provider)
- Some hosts require `RewriteBase /jrk/` in .htaccess

### Problem: "500 Internal Server Error"
**Solution:**
- Check PHP version (needs 7.4+)
- Check file permissions (644 for files, 755 for folders)
- Enable error logging in `config.php`:
  ```php
  error_reporting(E_ALL);
  ini_set('display_errors', 1);
  ```
- Check server error logs in cPanel

### Problem: Can't login
**Solution:**
- Verify database has admin user:
  ```sql
  SELECT * FROM users WHERE username = 'admin';
  ```
- Password should start with `$2y$10$` (bcrypt hash)
- Check browser console for errors (F12 → Console tab)

### Problem: Blank page
**Solution:**
- Check if `public/index.html` exists
- Check JavaScript console for errors
- Verify all files uploaded correctly

## 📊 File Permissions

Recommended permissions:
- **Folders:** 755
- **PHP files:** 644
- **config.php:** 600 (if supported)
- **.htaccess:** 644

## 🎯 Sample Data

The installation includes:

**Properties:**
- Sunset Apartments
- Harbor View Complex
- Mountain Ridge

**Admin Account:**
- Username: `admin`
- Password: `admin123`

**Sample Vehicles:**
- 3 vehicles across the 3 properties

## 🔒 Security Notes

1. **Change default password immediately**
2. **Use strong database passwords**
3. **Enable HTTPS**
4. **Keep `config.php` secure** (600 permissions if possible)
5. **Regular backups** of database via phpMyAdmin

## 📦 Backup

**Database Backup:**
1. phpMyAdmin → Select database
2. Click "Export" tab
3. Click "Go"
4. Save the `.sql` file

**File Backup:**
1. Via FTP, download the entire `/jrk/` folder
2. Or use cPanel "Compress" to create a zip file

## 🆘 Need Help?

**Common Issues:**
- Wrong path? Application must be at: `public_html/jrk/`
- Check cPanel error logs
- Verify PHP version in cPanel
- Ask your hosting provider about mod_rewrite

## ✅ Success Checklist

- [ ] Files uploaded to `/jrk/` folder
- [ ] Database created and install.sql imported
- [ ] config.php edited with correct database credentials
- [ ] Can access https://2clv.com/jrk
- [ ] Can login with admin/admin123
- [ ] Dashboard shows 3 sample vehicles
- [ ] Search works
- [ ] Can export CSV
- [ ] Admin password changed
- [ ] HTTPS enabled and forced
- [ ] Backup created

---

**Installation Complete!** 🎉

Your ManageMyParking application is ready to use at:
**https://2clv.com/jrk**
