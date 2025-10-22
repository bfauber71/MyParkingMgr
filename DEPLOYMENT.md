# ManageMyParking - Production Deployment Guide

**Deployment Target:** https://2clv.com/jrk

## Server Requirements

### Operating System
- Ubuntu 20.04 LTS or newer
- Debian 11 or newer
- CentOS 8 or newer

### Software Requirements
- PHP 8.3 or newer
- PHP-FPM (FastCGI Process Manager)
- MySQL 8.0 or newer
- Nginx 1.20+ or Apache 2.4+
- Composer (PHP dependency manager)
- Node.js 18+ (for frontend build)
- Supervisor (process management, optional)

### Server Resources
- **CPU:** 2+ cores recommended
- **RAM:** 2GB minimum, 4GB recommended
- **Disk:** 10GB minimum free space
- **Bandwidth:** 100Mbps recommended

## Pre-Deployment Checklist

- [ ] Server meets all requirements
- [ ] MySQL 8.0+ installed and running
- [ ] PHP 8.3+ installed with required extensions
- [ ] Nginx or Apache installed
- [ ] Domain/subdomain configured (2clv.com)
- [ ] SSL certificate obtained
- [ ] Database credentials prepared

## Installation Steps

### 1. Upload Application Files

```bash
# From your local machine
scp -r managemyparking/* user@2clv.com:/var/www/jrk/

# Or using Git
ssh user@2clv.com
cd /var/www
git clone <repository-url> jrk
cd jrk
```

### 2. Install Backend Dependencies

```bash
cd /var/www/jrk/backend
composer install --no-dev --optimize-autoloader
```

### 3. Build Frontend

```bash
cd /var/www/jrk/frontend
npm install
npm run build
```

This will build the React frontend and output production files to `backend/public/assets`.

### 4. Configure Environment

```bash
cd /var/www/jrk/backend
cp .env.example .env
nano .env
```

**Required .env settings:**
```env
APP_NAME=ManageMyParking
APP_ENV=production
APP_DEBUG=false
APP_URL=https://2clv.com/jrk
APP_KEY=  # Will be generated

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=managemyparking
DB_USERNAME=jrkadmin
DB_PASSWORD=YOUR_SECURE_PASSWORD

SESSION_DRIVER=database
SESSION_LIFETIME=1440
SESSION_COOKIE=managemyparking_session
SESSION_SECURE_COOKIE=true

BCRYPT_ROUNDS=10
```

**Generate application key:**
```bash
php artisan key:generate
```

### 5. Set File Permissions

```bash
sudo chown -R www-data:www-data /var/www/jrk
sudo chmod -R 755 /var/www/jrk
sudo chmod -R 775 /var/www/jrk/backend/storage
sudo chmod -R 775 /var/www/jrk/backend/bootstrap/cache
```

### 6. Create Database

```bash
mysql -u root -p

CREATE DATABASE managemyparking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'jrkadmin'@'localhost' IDENTIFIED BY 'SECURE_PASSWORD_HERE';
GRANT ALL PRIVILEGES ON managemyparking.* TO 'jrkadmin'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

### 7. Run Database Migrations

```bash
cd /var/www/jrk/backend
php artisan migrate
php artisan db:seed
```

This will create all tables and seed the admin user and sample properties.

### 8. Optimize Laravel for Production

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

## Nginx Configuration

Create Nginx configuration file:

```bash
sudo nano /etc/nginx/sites-available/jrk
```

**Nginx Configuration:**

```nginx
server {
    listen 80;
    server_name 2clv.com www.2clv.com;
    
    # Redirect to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name 2clv.com www.2clv.com;
    
    root /var/www/jrk/backend/public;
    index index.php index.html;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/2clv.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/2clv.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_prefer_server_ciphers on;
    ssl_ciphers ECDHE-RSA-AES256-GCM-SHA512:DHE-RSA-AES256-GCM-SHA512;
    
    # Base path for application
    location /jrk {
        alias /var/www/jrk/backend/public;
        try_files $uri $uri/ @jrk;
        
        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_pass unix:/run/php/php8.3-fpm.sock;
            fastcgi_param SCRIPT_FILENAME $request_filename;
        }
    }
    
    location @jrk {
        rewrite /jrk/(.*)$ /jrk/index.php?/$1 last;
    }
    
    # Static assets
    location /jrk/assets {
        alias /var/www/jrk/backend/public/assets;
        expires 30d;
        add_header Cache-Control "public, max-age=2592000";
    }
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Upload size limit
    client_max_body_size 50M;
    
    # Compression
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
}
```

**Enable site and restart Nginx:**

```bash
sudo ln -s /etc/nginx/sites-available/jrk /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl restart nginx
```

## PHP-FPM Configuration

Create PHP-FPM pool configuration:

```bash
sudo nano /etc/php/8.3/fpm/pool.d/jrk.conf
```

**PHP-FPM Configuration:**

```ini
[jrk]
user = www-data
group = www-data
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3

php_value[upload_max_filesize] = 50M
php_value[post_max_size] = 50M
php_value[memory_limit] = 256M
php_value[max_execution_time] = 300
php_value[session.save_handler] = files
php_value[session.save_path] = /var/www/jrk/backend/storage/framework/sessions
```

**Restart PHP-FPM:**

```bash
sudo systemctl restart php8.3-fpm
```

## SSL Certificate Installation

**Using Let's Encrypt (Free):**

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d 2clv.com -d www.2clv.com

# Test auto-renewal
sudo certbot renew --dry-run
```

Certbot will automatically configure SSL in your Nginx configuration.

## Database Backup Setup

**Create backup script:**

```bash
sudo nano /usr/local/bin/backup-managemyparking.sh
```

```bash
#!/bin/bash
BACKUP_DIR="/var/backups/managemyparking"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u jrkadmin -p'PASSWORD' managemyparking | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep last 30 days
find $BACKUP_DIR -type f -mtime +30 -delete
```

**Make executable and schedule:**

```bash
sudo chmod +x /usr/local/bin/backup-managemyparking.sh

# Schedule with cron (daily at 2 AM)
sudo crontab -e
# Add line:
0 2 * * * /usr/local/bin/backup-managemyparking.sh
```

## Application Updates

**To update the application:**

```bash
cd /var/www/jrk

# Pull latest code
git pull origin main

# Update backend
cd backend
composer install --no-dev
php artisan migrate
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Update frontend
cd ../frontend
npm install
npm run build

# Restart services
sudo systemctl restart php8.3-fpm
```

## Monitoring & Logs

**Application Logs:**

```bash
# Laravel logs
tail -f /var/www/jrk/backend/storage/logs/laravel.log

# PHP-FPM logs
tail -f /var/log/php8.3-fpm.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

**Health Checks:**
- Monitor database connectivity
- Check disk space usage
- Monitor PHP-FPM status
- Verify session storage

## Security Hardening

1. **Change default admin password** immediately after first login
2. **Restrict database access** to localhost only
3. **Enable firewall** (UFW):
   ```bash
   sudo ufw allow 22
   sudo ufw allow 80
   sudo ufw allow 443
   sudo ufw enable
   ```
4. **Regular security updates:**
   ```bash
   sudo apt update && sudo apt upgrade
   ```
5. **Monitor audit logs** for suspicious activity

## Performance Optimization

1. **Enable PHP OPcache:**
   ```bash
   sudo nano /etc/php/8.3/fpm/php.ini
   # Set:
   opcache.enable=1
   opcache.memory_consumption=128
   opcache.max_accelerated_files=10000
   ```

2. **Database indexing** is already configured in migrations

3. **Laravel caching** is configured via artisan commands above

## Troubleshooting

### Application not loading
- Check Nginx error logs: `sudo tail -f /var/log/nginx/error.log`
- Verify PHP-FPM is running: `sudo systemctl status php8.3-fpm`
- Check file permissions on storage and cache directories

### Database connection errors
- Verify MySQL is running: `sudo systemctl status mysql`
- Check database credentials in `.env`
- Test database connection: `mysql -u jrkadmin -p managemyparking`

### 502 Bad Gateway
- PHP-FPM not running or misconfigured
- Check PHP-FPM socket path matches Nginx configuration
- Restart PHP-FPM: `sudo systemctl restart php8.3-fpm`

## Post-Deployment Verification

1. **Access application:** https://2clv.com/jrk
2. **Login with admin credentials:** admin / admin123
3. **Change admin password immediately**
4. **Create test vehicle record**
5. **Test search functionality**
6. **Test CSV export**
7. **Create test user and verify property access**
8. **Verify audit logs are being created**

## Support & Maintenance

- Regular database backups (automated via cron)
- Monitor application logs for errors
- Keep PHP, MySQL, and system packages updated
- Review audit logs periodically
- Monitor disk space usage

## Production Checklist

- [ ] Application deployed and accessible
- [ ] SSL certificate installed and working
- [ ] Database created and migrated
- [ ] Admin password changed from default
- [ ] Backups configured and tested
- [ ] Logs accessible and monitored
- [ ] Firewall configured
- [ ] All tests passed
- [ ] Performance optimizations applied
- [ ] Documentation reviewed

---

**Deployment Complete!**

Your ManageMyParking application should now be fully functional at https://2clv.com/jrk.
