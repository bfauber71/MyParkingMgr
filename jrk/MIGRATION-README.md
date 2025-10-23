# Database Migration Files - Quick Reference

## 📋 Which File Should I Use?

### For New Installations
✅ **Use:** `sql/install.sql`  
Creates complete database from scratch with sample data.

### For Updating Existing Database
✅ **Use:** `sql/migrate-simple.sql` ← **RECOMMENDED**  
Works with standard shared hosting permissions.

❌ **Skip:** `sql/migrate.sql`  
Requires advanced database permissions. Use simple version instead.

### Optional: After Simple Migration Succeeds
⭐ **Optional:** `sql/migrate-add-foreign-keys.sql`  
Adds extra data protection. If it fails, that's okay!

---

## 🚨 Got Error #1044?

**See:** `TROUBLESHOOTING-ERROR-1044.md` for the fix!

**Quick solution:**
1. Use `migrate-simple.sql` instead
2. It works with basic hosting permissions
3. Problem solved! ✅

---

## 📂 File Details

| File | Purpose | When to Use |
|------|---------|-------------|
| `install.sql` | Fresh installation | New database setup |
| `migrate-simple.sql` | Update existing DB | **Use this for updates** |
| `migrate.sql` | Advanced migration | Skip - needs elevated permissions |
| `migrate-add-foreign-keys.sql` | Add FK constraints | Optional - after simple migration |

---

## ✅ Migration Checklist

- [ ] Backed up database in phpMyAdmin
- [ ] Ran `migrate-simple.sql`
- [ ] Saw "Migration Complete!" message
- [ ] Verified 10 violations were created
- [ ] Logged in and tested Violations tab
- [ ] Checked violation history on vehicles

---

## 📖 Full Documentation

- **Quick start:** `QUICK-START.md`
- **Step-by-step migration:** `MIGRATION-GUIDE.md`
- **Error #1044 fix:** `TROUBLESHOOTING-ERROR-1044.md`
- **System overview:** `README.md`
