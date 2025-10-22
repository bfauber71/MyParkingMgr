# ManageMyParking - Quick Start Guide

## What's in This Package

This deployment package contains the complete ManageMyParking application:

```
managemyparking-deployment.tar.gz
├── backend/              # Laravel 11 PHP application
├── frontend/             # React TypeScript application
├── README.md             # Full documentation
├── DEPLOYMENT.md         # Detailed deployment instructions
├── PRODUCTION-NOTES.md   # Frontend integration guide
└── DEPLOYMENT-CHECKLIST.md  # Step-by-step checklist
```

## 5-Minute Quick Start

### Prerequisites
- Server with PHP 8.3+, MySQL 8.0+, Nginx/Apache
- SSH access with sudo privileges
- Domain: 2clv.com pointing to your server

### Installation Commands

```bash
# 1. Extract files
tar -xzf managemyparking-deployment.tar.gz
sudo mkdir -p /var/www/jrk
sudo mv backend frontend /var/www/jrk/

# 2. Install backend dependencies
cd /var/www/jrk/backend
composer install --no-dev --optimize-autoloader

# 3. Configure environment
cp .env.example .env
nano .env  # Edit DB_DATABASE, DB_USERNAME, DB_PASSWORD
php artisan key:generate

# 4. Create database
mysql -u root -p << EOF
CREATE DATABASE managemyparking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'jrkadmin'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';
GRANT ALL PRIVILEGES ON managemyparking.* TO 'jrkadmin'@'localhost';
FLUSH PRIVILEGES;
EOF

# 5. Run migrations
php artisan migrate
php artisan db:seed

# 6. Build frontend
cd /var/www/jrk/frontend
npm install
npm run build

# 7. Set permissions
sudo chown -R www-data:www-data /var/www/jrk
sudo chmod -R 755 /var/www/jrk
sudo chmod -R 775 /var/www/jrk/backend/storage
sudo chmod -R 775 /var/www/jrk/backend/bootstrap/cache

# 8. Optimize Laravel
cd /var/www/jrk/backend
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 9. Configure Nginx (see DEPLOYMENT.md for config)
sudo nano /etc/nginx/sites-available/jrk
sudo ln -s /etc/nginx/sites-available/jrk /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx

# 10. Install SSL
sudo certbot --nginx -d 2clv.com -d www.2clv.com
```

### Default Login
- **URL:** https://2clv.com/jrk
- **Username:** `admin`
- **Password:** `admin123`
- ⚠️ **CHANGE IMMEDIATELY AFTER FIRST LOGIN**

## What Happens After Installation

1. **3 Sample Properties** are created:
   - Sunset Apartments
   - Harbor View Complex
   - Mountain Ridge

2. **Admin Account** is ready to use

3. **Database Tables** (7 total) are created:
   - users (authentication)
   - properties (property info)
   - property_contacts (up to 3 per property)
   - user_assigned_properties (access control)
   - vehicles (14-field tracking)
   - audit_logs (activity tracking)
   - sessions (session storage)

4. **Frontend** is built and ready to serve

## Key Features

✅ **Vehicle Management** - Track 14 fields per vehicle
✅ **Multi-Property** - Unlimited properties with independent management
✅ **Role-Based Access** - Admin, User (property-specific), Operator (read-only)
✅ **Full-Text Search** - Search across all vehicle fields
✅ **CSV Export** - Export vehicle data
✅ **Audit Logging** - Track all user actions
✅ **Property Contacts** - Up to 3 contacts per property

## File Structure on Server

```
/var/www/jrk/
├── backend/
│   ├── app/              # Laravel application code
│   ├── config/           # Configuration files
│   ├── database/         # Migrations and seeders
│   ├── public/           # Web root (Nginx points here)
│   │   └── assets/       # Built frontend files
│   ├── routes/           # API routes
│   └── storage/          # Logs and cache
└── frontend/
    ├── src/              # React source code
    └── package.json      # Frontend dependencies
```

## Important Configuration Files

### Backend (.env)
```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://2clv.com/jrk

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=managemyparking
DB_USERNAME=jrkadmin
DB_PASSWORD=your_secure_password

SESSION_DRIVER=database
SESSION_COOKIE=managemyparking_session
SESSION_SECURE_COOKIE=true
```

### Nginx Configuration
Points to: `/var/www/jrk/backend/public`
Base path: `/jrk`
See DEPLOYMENT.md for complete configuration.

## Testing Your Installation

```bash
# Check if application is accessible
curl -I https://2clv.com/jrk

# Check database connection
cd /var/www/jrk/backend
php artisan tinker
>>> DB::connection()->getPdo();

# Check logs
tail -f /var/www/jrk/backend/storage/logs/laravel.log
```

## Common Issues & Solutions

### Issue: 502 Bad Gateway
**Solution:** PHP-FPM not running
```bash
sudo systemctl status php8.3-fpm
sudo systemctl restart php8.3-fpm
```

### Issue: Database connection failed
**Solution:** Check credentials in .env
```bash
mysql -u jrkadmin -p managemyparking  # Test connection
```

### Issue: Assets not loading
**Solution:** Rebuild frontend
```bash
cd /var/www/jrk/frontend
npm run build
```

### Issue: Permission denied errors
**Solution:** Fix permissions
```bash
sudo chown -R www-data:www-data /var/www/jrk
sudo chmod -R 775 /var/www/jrk/backend/storage
```

## Backup Setup

Create daily automated backups:

```bash
# Create backup script
sudo nano /usr/local/bin/backup-managemyparking.sh

# Add content from DEPLOYMENT.md, then:
sudo chmod +x /usr/local/bin/backup-managemyparking.sh

# Schedule daily backups at 2 AM
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-managemyparking.sh
```

## Security Checklist

- [ ] Change admin password from default
- [ ] Set strong database password
- [ ] Enable HTTPS (SSL certificate)
- [ ] Set APP_DEBUG=false in .env
- [ ] Configure firewall (ports 22, 80, 443)
- [ ] Set up automated backups
- [ ] Restrict database access to localhost

## Next Steps

1. **Login** to https://2clv.com/jrk with admin/admin123
2. **Change Password** immediately
3. **Create Properties** (or use the 3 sample properties)
4. **Add Users** with appropriate roles
5. **Import Vehicles** or create manually
6. **Test Search** and export functionality
7. **Review Audit Logs** for activity tracking

## Documentation

For complete details, see:

- **DEPLOYMENT.md** - Full deployment guide with Nginx/PHP-FPM configuration
- **README.md** - Complete project documentation
- **PRODUCTION-NOTES.md** - Frontend integration details
- **DEPLOYMENT-CHECKLIST.md** - Step-by-step checklist

## Support

### Logs Location
- Laravel: `/var/www/jrk/backend/storage/logs/laravel.log`
- Nginx: `/var/log/nginx/error.log`
- PHP-FPM: `/var/log/php8.3-fpm.log`

### Useful Commands
```bash
# View Laravel logs
tail -f /var/www/jrk/backend/storage/logs/laravel.log

# Restart services
sudo systemctl restart nginx
sudo systemctl restart php8.3-fpm

# Clear Laravel cache
cd /var/www/jrk/backend
php artisan cache:clear
php artisan config:clear
```

---

**Ready to Deploy!**

Extract the package and follow the Quick Start commands above.
For detailed instructions, refer to DEPLOYMENT.md.
