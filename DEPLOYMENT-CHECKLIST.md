# ManageMyParking - Deployment Checklist

## Pre-Deployment Requirements

### Server Setup
- [ ] Ubuntu 20.04+ / Debian 11+ / CentOS 8+
- [ ] PHP 8.3 or newer installed
- [ ] PHP-FPM installed
- [ ] MySQL 8.0+ installed and running
- [ ] Nginx or Apache installed
- [ ] Composer installed globally
- [ ] Node.js 18+ and npm installed
- [ ] SSL certificate ready (Let's Encrypt recommended)
- [ ] 10GB+ free disk space

### Access & Credentials
- [ ] SSH access to server
- [ ] sudo/root privileges
- [ ] MySQL root password
- [ ] Domain configured: 2clv.com
- [ ] DNS pointing to server IP

## Deployment Steps

### Step 1: Upload Files
```bash
# Extract the deployment package
tar -xzf managemyparking-deployment.tar.gz
sudo mkdir -p /var/www/jrk
sudo mv backend frontend /var/www/jrk/
sudo mv README.md DEPLOYMENT.md PRODUCTION-NOTES.md /var/www/jrk/
```
- [ ] Files extracted successfully
- [ ] Files moved to /var/www/jrk

### Step 2: Install Backend Dependencies
```bash
cd /var/www/jrk/backend
composer install --no-dev --optimize-autoloader
```
- [ ] Composer dependencies installed
- [ ] No errors during installation

### Step 3: Configure Environment
```bash
cd /var/www/jrk/backend
cp .env.example .env
nano .env  # Edit database credentials
php artisan key:generate
```
- [ ] .env file created
- [ ] Database credentials configured
- [ ] Application key generated
- [ ] APP_ENV=production
- [ ] APP_DEBUG=false
- [ ] APP_URL=https://2clv.com/jrk

### Step 4: Create Database
```bash
mysql -u root -p
```
```sql
CREATE DATABASE managemyparking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'jrkadmin'@'localhost' IDENTIFIED BY 'YOUR_SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON managemyparking.* TO 'jrkadmin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```
- [ ] Database created
- [ ] Database user created
- [ ] Privileges granted

### Step 5: Run Migrations
```bash
cd /var/www/jrk/backend
php artisan migrate
php artisan db:seed
```
- [ ] Migrations executed successfully
- [ ] All 7 tables created
- [ ] Seeder run (admin user + 3 properties)

### Step 6: Build Frontend
```bash
cd /var/www/jrk/frontend
npm install
npm run build
```
- [ ] npm dependencies installed
- [ ] Frontend built successfully
- [ ] Assets copied to backend/public/assets

### Step 7: Set Permissions
```bash
sudo chown -R www-data:www-data /var/www/jrk
sudo chmod -R 755 /var/www/jrk
sudo chmod -R 775 /var/www/jrk/backend/storage
sudo chmod -R 775 /var/www/jrk/backend/bootstrap/cache
```
- [ ] Ownership set to www-data
- [ ] Permissions configured correctly

### Step 8: Optimize Laravel
```bash
cd /var/www/jrk/backend
php artisan config:cache
php artisan route:cache
php artisan view:cache
```
- [ ] Configuration cached
- [ ] Routes cached
- [ ] Views cached

### Step 9: Configure Nginx
```bash
sudo nano /etc/nginx/sites-available/jrk
```
(See DEPLOYMENT.md for complete Nginx configuration)
```bash
sudo ln -s /etc/nginx/sites-available/jrk /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```
- [ ] Nginx config created
- [ ] Config symlinked
- [ ] Nginx test passed
- [ ] Nginx restarted

### Step 10: Configure PHP-FPM
```bash
sudo nano /etc/php/8.3/fpm/pool.d/jrk.conf
```
(See DEPLOYMENT.md for complete PHP-FPM configuration)
```bash
sudo systemctl restart php8.3-fpm
```
- [ ] PHP-FPM pool configured
- [ ] PHP-FPM restarted

### Step 11: Install SSL Certificate
```bash
sudo apt install certbot python3-certbot-nginx
sudo certbot --nginx -d 2clv.com -d www.2clv.com
```
- [ ] Certbot installed
- [ ] SSL certificate obtained
- [ ] Auto-renewal configured

### Step 12: Configure Backups
```bash
sudo nano /usr/local/bin/backup-managemyparking.sh
# Add backup script from DEPLOYMENT.md
sudo chmod +x /usr/local/bin/backup-managemyparking.sh
sudo crontab -e
# Add: 0 2 * * * /usr/local/bin/backup-managemyparking.sh
```
- [ ] Backup script created
- [ ] Backup script executable
- [ ] Cron job scheduled

## Post-Deployment Testing

### Step 13: Access Application
- [ ] Access https://2clv.com/jrk
- [ ] Page loads without errors
- [ ] No SSL warnings
- [ ] Login page displays correctly

### Step 14: Test Authentication
- [ ] Login with admin/admin123
- [ ] Login successful
- [ ] Dashboard loads
- [ ] User info displays correctly

### Step 15: Change Admin Password
- [ ] Change admin password immediately
- [ ] Logout and login with new password
- [ ] Password change successful

### Step 16: Test Core Features
- [ ] Create test vehicle record
- [ ] Edit vehicle record
- [ ] Search for vehicle
- [ ] Export vehicle CSV
- [ ] View audit logs
- [ ] Test property management
- [ ] Test user management

### Step 17: Test Role-Based Access
- [ ] Create test User with property assignment
- [ ] Login as User - verify access to assigned property only
- [ ] Create test Operator
- [ ] Login as Operator - verify read-only access

### Step 18: Verify Security
- [ ] Check HTTPS is enforced
- [ ] Verify session cookies are HTTP-only
- [ ] Test CSRF protection
- [ ] Verify unauthorized access is blocked
- [ ] Check audit logs are being created

## Monitoring Setup

### Step 19: Configure Monitoring
- [ ] Monitor application logs
- [ ] Monitor PHP-FPM logs
- [ ] Monitor Nginx logs
- [ ] Monitor MySQL slow queries
- [ ] Set up disk space alerts

### Step 20: Configure Firewall
```bash
sudo ufw allow 22
sudo ufw allow 80
sudo ufw allow 443
sudo ufw enable
```
- [ ] Firewall rules added
- [ ] Firewall enabled
- [ ] SSH still accessible

## Production Checklist

### Security
- [ ] Default admin password changed
- [ ] .env file secured (not web-accessible)
- [ ] Database credentials are strong
- [ ] Firewall configured
- [ ] SSL certificate installed
- [ ] HTTPS enforced
- [ ] Debug mode disabled (APP_DEBUG=false)

### Performance
- [ ] PHP OPcache enabled
- [ ] Laravel caches cleared and rebuilt
- [ ] Database indexes verified
- [ ] Nginx gzip compression enabled

### Backups
- [ ] Database backup script configured
- [ ] Cron job scheduled
- [ ] Backup restoration tested
- [ ] Offsite backup configured (recommended)

### Monitoring
- [ ] Application logs accessible
- [ ] Error monitoring configured
- [ ] Uptime monitoring setup (recommended)
- [ ] Disk space monitoring

### Documentation
- [ ] Admin credentials documented (secure location)
- [ ] Database credentials documented (secure location)
- [ ] Deployment date recorded
- [ ] Team members trained

## Troubleshooting

If you encounter issues:

1. **Application not loading**
   - Check Nginx error logs: `sudo tail -f /var/log/nginx/error.log`
   - Check PHP-FPM status: `sudo systemctl status php8.3-fpm`
   - Verify file permissions

2. **Database connection errors**
   - Test MySQL: `mysql -u jrkadmin -p managemyparking`
   - Check .env database credentials
   - Verify MySQL is running

3. **502 Bad Gateway**
   - Check PHP-FPM socket path in Nginx config
   - Restart PHP-FPM: `sudo systemctl restart php8.3-fpm`
   - Check PHP error logs

4. **Assets not loading**
   - Verify frontend build completed
   - Check Nginx alias paths
   - Clear browser cache

## Success Criteria

✅ Application accessible at https://2clv.com/jrk
✅ SSL certificate valid and auto-renewing
✅ Admin login working with new password
✅ All CRUD operations functional
✅ Search working correctly
✅ CSV export working
✅ Role-based access enforced
✅ Audit logs being created
✅ Backups configured and tested
✅ No errors in logs

## Support

For detailed instructions, see:
- **DEPLOYMENT.md** - Complete deployment guide
- **README.md** - Project documentation
- **PRODUCTION-NOTES.md** - Frontend integration guide

---

**Deployment Complete! 🎉**

Your ManageMyParking application is now live at https://2clv.com/jrk
