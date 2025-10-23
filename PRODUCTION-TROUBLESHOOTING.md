# Production Troubleshooting Guide

## Issue: Menus Don't Work on Production

If menus don't appear or clicking tabs does nothing after deploying to https://2clv.com/jrk, follow these debugging steps:

### Step 1: Check Browser Console for Errors

1. Open your deployed site: https://2clv.com/jrk
2. **Open Browser Developer Tools:**
   - Chrome/Edge: Press `F12` or `Ctrl+Shift+I` (Windows) / `Cmd+Option+I` (Mac)
   - Firefox: Press `F12` or `Ctrl+Shift+K` (Windows) / `Cmd+Option+K` (Mac)
3. Click the **Console** tab
4. Look for any error messages (shown in red)

**Common Issues to Look For:**

#### ❌ Error: "Failed to load resource: 404"
**Problem:** CSS or JavaScript files not loading
**Solution:** 
- Check that `jrk/public/assets/style.css` and `app.js` were uploaded
- Verify file permissions (should be 644)
- Check .htaccess is present and working

#### ❌ Error: "Unexpected token '<'"  
**Problem:** Server returning HTML instead of JavaScript (routing issue)
**Solution:**
- Verify .htaccess file is in the `jrk/` directory
- Check Apache mod_rewrite is enabled on your server
- Contact host if .htaccess isn't being processed

#### ❌ Error: "Cannot read property 'addEventListener' of null"
**Problem:** DOM elements not found
**Solution:**
- Check that `index.html` was uploaded correctly
- Verify all HTML files are complete

### Step 2: Verify Console Logging

With the console open, you should see these messages when the page loads:

```
Setting up event listeners...
Found tab buttons: 3
Event listeners setup complete
```

After logging in, you should see:
```
Switching to tab: vehicles
Activated tab button: vehicles
Activated tab content: vehicles
```

**If you DON'T see these messages:**
- JavaScript file isn't loading or has a syntax error
- Check for red error messages in console
- Verify `app.js` file uploaded correctly

### Step 3: Test Login

1. Try logging in with: `admin` / `admin123`
2. Watch the console for messages
3. Check for API errors:

**Expected on successful login:**
- Dashboard should appear
- You should see "Switching to tab: vehicles" in console
- 3 tab buttons should appear at top: Vehicles, Properties, Users

**If login fails:**
- Check console for error message
- Verify database connection in `config.php`
- Check that `install.sql` was imported to database
- Verify database credentials are correct

### Step 4: Test Tab Clicking

After logging in:

1. Click on the "Properties" tab
2. Watch the console - you should see:
   ```
   Tab clicked: properties
   Switching to tab: properties
   Activated tab button: properties
   Activated tab content: properties
   ```

**If nothing happens when you click:**
- Event listeners not attached properly
- Check for JavaScript errors in console before login
- Hard refresh the page: `Ctrl+F5` (Windows) / `Cmd+Shift+R` (Mac)

### Step 5: Check File Permissions

On your server via FTP or cPanel File Manager, verify:

```
jrk/                          (755)
├── api/                      (755)
│   └── *.php                 (644)
├── includes/                 (755)  
│   └── *.php                 (644)
├── public/                   (755)
│   ├── assets/               (755)
│   │   ├── app.js           (644)
│   │   └── style.css        (644)
│   └── index.html           (644)
├── config.php               (644)
├── index.php                (644)
└── .htaccess                (644)
```

### Step 6: Verify .htaccess is Working

Create a test file `jrk/test.txt` with content "Test"

Try accessing: https://2clv.com/jrk/test.txt

**If you see "Test":** .htaccess is working  
**If you see 404 or error:** .htaccess not being processed - contact your hosting provider

### Step 7: Check Database Connection

Look in the browser console after login attempt for:

```
Error: Failed to fetch
```

This means the API can't connect to the database.

**Verify in `jrk/config.php`:**
```php
'host' => 'localhost',        // Usually 'localhost'
'database' => 'your_db_name', // Your actual database name
'username' => 'your_db_user', // Your actual database username  
'password' => 'your_db_pass', // Your actual database password
```

**Test database connection:**
Create `jrk/db-test.php`:
```php
<?php
require_once 'config.php';
require_once 'includes/database.php';
try {
    $db = Database::getInstance();
    echo "Database connected successfully!";
} catch (Exception $e) {
    echo "Database error: " . $e->getMessage();
}
```

Access: https://2clv.com/jrk/db-test.php
Delete the test file after checking.

## Quick Checklist

- [ ] All files uploaded to `jrk/` folder
- [ ] `.htaccess` file present in `jrk/`
- [ ] `install.sql` imported to database
- [ ] `config.php` has correct database credentials
- [ ] Browser console shows no red errors
- [ ] CSS and JS files loading (check Network tab)
- [ ] Can log in with admin/admin123
- [ ] Console shows "Setting up event listeners..."
- [ ] Console shows "Found tab buttons: 3"
- [ ] Tabs appear at top after login
- [ ] Clicking tabs shows console messages

## Still Not Working?

Send me:
1. **Screenshot of browser console** (with any red errors)
2. **What you see after login** (screenshot)
3. **Any error messages** from the page or console
4. **Your hosting provider name** (to check compatibility)

I'll help you debug the specific issue!
