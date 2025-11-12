# SQL Installation Guide
**ManageMyParking v2.0 with Payment System**

---

## 📋 **Quick Start** (Recommended)

For new installations, use the **single consolidated file**:

### **File:** `COMPLETE-INSTALL.sql`

This file contains everything you need:
- ✅ All core application tables
- ✅ Payment system tables
- ✅ Default admin user
- ✅ Default property
- ✅ System settings

**Steps:**
1. Create a new MySQL database in cPanel/phpMyAdmin
2. Import `COMPLETE-INSTALL.sql` into your database
3. Done! All tables created automatically

---

## 🔐 **Default Login Credentials**

After importing the SQL:

- **Username:** `admin`
- **Password:** `admin123`

**⚠️ CRITICAL:** Change this password immediately after first login!

---

## 📁 **SQL Files Directory Structure**

```
sql/
├── COMPLETE-INSTALL.sql          ← USE THIS for new installations
├── README-SQL-INSTALLATION.md    ← This file
├── add-test-data.sql             ← Add sample data to existing installations
├── fix-admin-permissions.sql     ← Fix permission errors (401 Unauthorized)
├── install.sql                   ← Base schema only (no payment system)
│
└── Individual migration files (for upgrades):
    ├── add-guest-pass-expiration.sql
    ├── add-ticket-status.sql
    ├── add-resident-guest-fields.sql
    └── [other migration files]

database/migrations/
└── 002-payment-system.sql        ← Payment tables (included in COMPLETE-INSTALL.sql)
```

---

## 🎯 **Which File Should I Use?**

### **New Installation (Starting Fresh)**
✅ Use: `COMPLETE-INSTALL.sql`
- Complete setup in one file
- Includes payment system
- Includes admin user with full permissions
- Includes test property + 10 vehicles + 30 violations
- Ready to use immediately!

### **Existing Installation - Add Test Data**
✅ Use: `add-test-data.sql`
- Adds sample property (Sunset Apartments)
- Adds 10 test vehicles
- Adds 30 violation tickets
- Safe to run on existing installations
- Perfect for testing/demo purposes

### **Existing Installation - Fix Permission Errors**
✅ Use: `fix-admin-permissions.sql`
- Fixes 401 Unauthorized errors
- Grants admin user full permissions
- Required if you see "Unauthorized" when accessing Users/Violations

### **Upgrading from v1.x to v2.0**
✅ Use: `database/migrations/002-payment-system.sql`
- Adds payment tables to existing database
- Preserves your existing data
- Run this AFTER your base schema exists

### **Individual Feature Upgrades**
Use the specific migration files in `sql/` directory:
- `add-ticket-status.sql` - Adds ticket closing functionality
- `add-guest-pass-expiration.sql` - Adds guest pass features
- `add-resident-guest-fields.sql` - Adds resident/guest tracking
- etc.

---

## 📖 **Installation Methods**

### **Method 1: phpMyAdmin (Most Common)**

1. Login to cPanel → phpMyAdmin
2. Select your database (or create a new one)
3. Click "Import" tab
4. Choose file: `COMPLETE-INSTALL.sql`
5. Click "Go"
6. Wait for "Import has been successfully finished"

### **Method 2: MySQL Command Line**

```bash
mysql -u username -p database_name < COMPLETE-INSTALL.sql
```

### **Method 3: cPanel MySQL Database Wizard**

1. Create database through cPanel
2. Use phpMyAdmin method above

---

## ✅ **Verify Installation**

After importing, check that these tables exist:

**Core Tables:**
- `users`
- `properties`
- `vehicles`
- `violation_tickets`
- `audit_logs`
- `sessions`
- `user_permissions`
- `login_attempts`

**Payment System Tables:**
- `payment_settings`
- `ticket_payments`
- `qr_codes`

**Run this query to verify:**
```sql
SHOW TABLES;
```

You should see **15-20 tables** listed.

---

## 🔧 **Troubleshooting**

### **Error: "Table already exists"**

This means you're trying to install on a database that already has tables.

**Solutions:**
1. Create a fresh database
2. Or drop existing tables first (⚠️ WARNING: This deletes all data!)

### **Error: "Access denied for user"**

Your database user doesn't have proper permissions.

**Solution:**
- Ensure your MySQL user has ALL PRIVILEGES
- Check username/password in cPanel

### **Error: "Unknown collation: utf8mb4_unicode_ci"**

Your MySQL version is too old.

**Solution:**
- Use MySQL 5.7+ or MariaDB 10.2+
- Contact your hosting provider to upgrade

---

## 🔐 **Security Checklist**

After installation:

- [ ] Login and change admin password immediately
- [ ] Delete or rename `web-generate-key.php` after use
- [ ] Generate encryption key for payment system
- [ ] Set `config/encryption.key` to chmod 600
- [ ] Enable HTTPS for production use
- [ ] Review user permissions

---

## 📚 **Next Steps After SQL Installation**

1. **Configure Application**
   - Edit `config.php` with database credentials
   - Set correct timezone in Settings

2. **Setup Payment System**
   - Follow `ENCRYPTION_UPGRADE_GUIDE.md`
   - Generate encryption key
   - Configure payment processors

3. **Test the System**
   - Follow `PAYMENT_TESTING_GUIDE.md`
   - Create test vehicles and tickets
   - Test payment workflows

---

## 📞 **Need Help?**

If you encounter issues:

1. Check MySQL error logs in cPanel
2. Verify MySQL version (must be 5.7+)
3. Ensure database user has ALL PRIVILEGES
4. Review the error message carefully

---

**Files Included in Deployment:**

| File | Purpose | When to Use |
|------|---------|-------------|
| `COMPLETE-INSTALL.sql` | Complete new installation with test data | Fresh setup |
| `add-test-data.sql` | Add sample property, vehicles, violations | Existing installation |
| `fix-admin-permissions.sql` | Fix 401 permission errors | When you see "Unauthorized" |
| `install.sql` | Base schema only | Without payment system |
| `002-payment-system.sql` | Payment tables migration | Upgrade existing v1.x |
| Individual migration files | Specific feature upgrades | As needed |

---

**Installation Time:** 1-2 minutes  
**Database Size:** ~2-5 MB (empty schema)  
**Tables Created:** 15-20 tables  
**Default Users:** 1 (admin)
