#!/bin/bash
#
# Build Payment System v2.0 Deployment Package
# Creates a production-ready distribution for ManageMyParking with Payment System
#

echo "Building ManageMyParking v2.0 Payment System Deployment Package..."

# Create build directory
BUILD_DIR="build/payment-deployment"
rm -rf "$BUILD_DIR"
mkdir -p "$BUILD_DIR"

# Copy main application files
echo "Copying application files..."
cp -r jrk-payments/* "$BUILD_DIR/"
# Copy hidden files except .git
shopt -s dotglob
for file in jrk-payments/.*; do
    if [ -f "$file" ] && [[ $(basename "$file") != ".git"* ]]; then
        cp "$file" "$BUILD_DIR/" 2>/dev/null || true
    fi
done
shopt -u dotglob

# Create production config from template
echo "Creating production config template..."
if [ -f "$BUILD_DIR/config.php" ]; then
    mv "$BUILD_DIR/config.php" "$BUILD_DIR/config-example.php"
    cat > "$BUILD_DIR/config.php" << 'EOF'
<?php
/**
 * ManageMyParking v2.0 - Production Configuration Template
 * 
 * INSTRUCTIONS:
 * 1. Rename this file or create a copy as config.php
 * 2. Update all database settings with your MySQL credentials
 * 3. Change base_path if not installed in root directory
 * 4. Keep this file secure - do not commit to version control
 */

return [
    'base_path' => '/',  // Change to '/subdirectory' if not in root
    
    'db' => [
        'host' => 'localhost',
        'port' => '3306',
        'database' => 'YOUR_DATABASE_NAME',
        'username' => 'YOUR_DATABASE_USER',
        'password' => 'YOUR_DATABASE_PASSWORD',
        'charset' => 'utf8mb4',
        'unix_socket' => '',  // Leave empty for standard TCP connection
    ],
    
    'session' => [
        'name' => 'mmp_session',
        'lifetime' => 86400,
        'secure' => true,      // Set to true for HTTPS (recommended)
        'httponly' => true,
        'samesite' => 'Strict',
    ],
    
    'app' => [
        'name' => 'ManageMyParking',
        'version' => '2.0',
        'timezone' => 'America/New_York',  // Change to your timezone
    ],
];
EOF
fi

# Remove development-only files
echo "Removing development files..."
rm -f "$BUILD_DIR/router.php"
rm -f "$BUILD_DIR/.gitignore"
rm -f "$BUILD_DIR/.replit"
rm -f "$BUILD_DIR/replit.nix"
rm -rf "$BUILD_DIR/.git"

# Remove any test or backup files
echo "Cleaning up temporary files..."
find "$BUILD_DIR" -name "*.bak" -delete
find "$BUILD_DIR" -name "*~" -delete
find "$BUILD_DIR" -name ".DS_Store" -delete
find "$BUILD_DIR" -name "*.old" -delete
find "$BUILD_DIR" -name "*.tmp" -delete
find "$BUILD_DIR" -name "Thumbs.db" -delete

# Create necessary directories with proper permissions markers
echo "Setting up directory structure..."
mkdir -p "$BUILD_DIR/qrcodes"
touch "$BUILD_DIR/qrcodes/.htaccess"
cat > "$BUILD_DIR/qrcodes/.htaccess" << 'EOF'
# Allow access to QR code images
<IfModule mod_rewrite.c>
    RewriteEngine Off
</IfModule>
Options -Indexes
EOF

# Create deployment instructions
echo "Creating deployment documentation..."
cat > "$BUILD_DIR/INSTALLATION.txt" << 'EOF'
==========================================
ManageMyParking v2.0 with Payment System
INSTALLATION INSTRUCTIONS
==========================================

REQUIREMENTS:
- PHP 7.4+ (PHP 8.0+ recommended)
- MySQL 5.7+ or MariaDB 10.2+
- Apache or Nginx web server
- HTTPS enabled (required for payment processing)
- PHP Extensions: pdo, pdo_mysql, json, session, mbstring, gd

INSTALLATION STEPS:

1. UPLOAD FILES
   - Upload all files to your web hosting via FTP/SFTP
   - Recommended location: public_html/ or public_html/parking/

2. CREATE DATABASE
   - Create a MySQL database via cPanel or phpMyAdmin
   - Create a database user with ALL privileges
   - Note your database name, username, and password

3. CONFIGURE APPLICATION
   - Edit config.php with your database credentials
   - Set base_path if installed in subdirectory
   - Set timezone to your local timezone
   - Ensure session.secure is true for HTTPS

4. SET PERMISSIONS
   - Ensure qrcodes/ directory is writable (chmod 755)
   - Verify .htaccess files are present

5. ACCESS APPLICATION
   - Navigate to: https://yourdomain.com/
   - Login with default credentials (from v1.1 setup)
   - Go to Settings → Payments
   - Click "Install Payment System" button
   - Wait for confirmation message

6. CONFIGURE PAYMENT PROCESSOR
   - Go to Settings → Payments
   - Select a property from dropdown
   - Choose payment processor (Stripe/Square/PayPal)
   - Enter API keys (use test mode for testing)
   - Enable desired payment options
   - Save settings

7. TEST PAYMENT SYSTEM
   - Follow PAYMENT_TESTING_GUIDE.md for complete testing
   - Test with Stripe test mode before going live
   - Test card: 4242 4242 4242 4242

SECURITY NOTES:
- HTTPS is REQUIRED for payment processing
- Protect config.php from public access
- Before production: Upgrade encryption system
  (See PAYMENT_SYSTEM_README.md for details)
- Regularly backup your database

SUPPORT:
- Full documentation: PAYMENT_SYSTEM_README.md
- Testing guide: PAYMENT_TESTING_GUIDE.md
- Implementation details: IMPLEMENTATION_SUMMARY.md

==========================================
EOF

# Create .htaccess for security
cat > "$BUILD_DIR/.htaccess" << 'EOF'
# ManageMyParking Security Configuration

# Enable HTTPS redirect (uncomment when HTTPS is configured)
# RewriteEngine On
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Protect sensitive files
<FilesMatch "^(config\.php|config-example\.php|\.htaccess)$">
    Order allow,deny
    Deny from all
</FilesMatch>

# Disable directory listing
Options -Indexes

# Set default document
DirectoryIndex index.html index.php

# PHP security settings
<IfModule mod_php7.c>
    php_flag display_errors Off
    php_flag log_errors On
    php_value upload_max_filesize 10M
    php_value post_max_size 10M
</IfModule>

# Security headers
<IfModule mod_headers.c>
    Header set X-Content-Type-Options "nosniff"
    Header set X-Frame-Options "SAMEORIGIN"
    Header set X-XSS-Protection "1; mode=block"
</IfModule>
EOF

# Get version from index.html
VERSION=$(grep -oP "v\d+\.\d+\.\d+" "$BUILD_DIR/index.html" | head -1)
if [ -z "$VERSION" ]; then
    VERSION="v2.0"
fi

# Create version info
echo "Creating version file..."
cat > "$BUILD_DIR/VERSION.txt" << EOF
ManageMyParking Payment System ${VERSION}
Built: $(date '+%Y-%m-%d %H:%M:%S')
Package: Production Deployment

Features:
- Complete Payment System v2.0
- Stripe/Square/PayPal integration
- QR code payment support
- Manual payment recording (cash/check/card)
- Payment status tracking
- Webhook support
- Complete audit trail

Documentation:
- INSTALLATION.txt - Setup instructions
- PAYMENT_SYSTEM_README.md - Complete system documentation
- PAYMENT_TESTING_GUIDE.md - Testing procedures
- IMPLEMENTATION_SUMMARY.md - Technical details
EOF

# Create zip package
cd build
ZIP_NAME="ManageMyParking-PaymentSystem-${VERSION}-$(date +%Y%m%d).zip"
echo "Creating zip package: $ZIP_NAME"
zip -r "$ZIP_NAME" payment-deployment/ -q

# Calculate size
SIZE=$(du -h "$ZIP_NAME" | cut -f1)

echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "✓ DEPLOYMENT PACKAGE CREATED SUCCESSFULLY"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "Package: build/$ZIP_NAME"
echo "Size: $SIZE"
echo ""
echo "CONTENTS:"
echo "  ✓ Complete application files"
echo "  ✓ Payment System v2.0"
echo "  ✓ Database migration scripts"
echo "  ✓ API endpoints (7 payment APIs)"
echo "  ✓ Complete documentation"
echo "  ✓ Installation instructions"
echo "  ✓ Security configurations"
echo ""
echo "NEXT STEPS:"
echo "  1. Extract the zip file"
echo "  2. Review INSTALLATION.txt"
echo "  3. Upload to web hosting"
echo "  4. Configure database settings"
echo "  5. Follow PAYMENT_TESTING_GUIDE.md"
echo ""
echo "⚠️  IMPORTANT SECURITY NOTES:"
echo "  • HTTPS is REQUIRED for payment processing"
echo "  • Use test mode for all testing"
echo "  • Upgrade encryption before production"
echo "  • See PAYMENT_SYSTEM_README.md for details"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
