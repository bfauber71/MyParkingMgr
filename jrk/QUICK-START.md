# ManageMyParking - Quick Start Guide

## 🎯 Choose Your Path

### Path A: Fresh Installation (New Users)
**Time Required:** 5-10 minutes

1. **Upload Files**
   - FTP into your hosting account
   - Upload the entire `jrk/` folder to your web directory
   - Example: Upload to `public_html/jrk/`

2. **Create Database**
   - Log in to cPanel
   - Go to MySQL Databases
   - Create a new database (e.g., `managemyparking`)
   - Create a user and assign to the database
   - Note your database credentials

3. **Import Schema**
   - Open phpMyAdmin
   - Select your new database
   - Click "Import" tab
   - Choose file: `jrk/sql/install.sql`
   - Click "Go"

4. **Access Application**
   - Browse to: `https://yourdomain.com/jrk/`
   - Login: `admin` / `admin123`
   - **Change password immediately!**

---

### Path B: Update Existing Installation (Current Users)
**Time Required:** 2-3 minutes

⚠️ **IMPORTANT: Backup your database first!**

1. **Backup Database**
   - phpMyAdmin → Select database → Export → Go
   - Save the downloaded .sql file

2. **Upload New Files**
   - FTP the updated files to your existing `jrk/` folder
   - Overwrite existing files when prompted

3. **Run Migration**
   - phpMyAdmin → Select database → SQL tab
   - Copy contents of `jrk/sql/migrate.sql`
   - Paste and click "Go"

4. **Verify Update**
   - Log in to your application
   - Check Violations tab (Admin only)
   - Test violation history on vehicles

📖 **Full instructions:** See `MIGRATION-GUIDE.md`

---

## ✅ Post-Installation Checklist

- [ ] Changed admin password from default
- [ ] Created test property
- [ ] Added test vehicle
- [ ] Created at least one user with property assignment
- [ ] Tested violation ticket creation
- [ ] Verified violation history displays
- [ ] Tested CSV import/export

---

## 🔧 Configuration for Subdirectory Deployment

If deploying to `https://2clv.com/jrk`:

1. Open `jrk/.htaccess`
2. Find line: `RewriteBase /`
3. Change to: `RewriteBase /jrk/`
4. Save and upload

---

## 🆘 Common Issues

### "Can't connect to database"
- Check database credentials in hosting panel
- Verify database user has proper permissions

### "404 - Page not found"
- Check `.htaccess` RewriteBase setting
- Verify mod_rewrite is enabled on server

### "Login not working"
- Clear browser cookies
- Try different browser
- Verify database was imported correctly

### "Violations tab missing"
- Run migration script: `jrk/sql/migrate.sql`
- Verify logged in as Admin role

---

## 📞 Getting Help

1. Check `MIGRATION-GUIDE.md` for database updates
2. Review `README.md` for system requirements
3. Contact your hosting provider for server issues
4. Review `replit.md` for technical architecture details
