# ManageMyParking (MyParkingManager)

## Overview

ManageMyParking is a professional PHP-based vehicle and property management system designed for shared hosting environments. The application provides comprehensive parking violation tracking, vehicle management, and property administration with role-based access control. It features a subscription-based licensing system with a 30-day trial period and supports flexible deployment across various hosting configurations.

The system is built to handle multiple properties, track vehicles and violations, manage resident information, and provide detailed audit logging. It's designed for property managers, parking administrators, security personnel, and property owners.

## Recent Changes

### October 27, 2025 - v2.3.0 Critical Fix Release

**FIXED: "Nothing Clicks" After Login Issue**
- Root cause: index.html was loading incomplete JavaScript file (app.js)
- Missing functions: loadVehiclesSection(), loadUsersSection(), loadViolationsManagementSection()
- Solution: Changed index.html to load complete app-secure.js file
- Added 200+ lines of missing code for vehicle/user/violation management
- All tabs and buttons now functional, data loads correctly from database

**Additional Fixes:**
- Fixed infinite redirect loop in .htaccess causing 500 errors
- Added api/csrf-token.php endpoint for CSRF token generation
- Endpoint whitelist in database.php for setup wizard compatibility
- Complete path handling for any installation directory
- Cleaned up all outdated deployment packages and temporary files
- Removed old deploy/packages folder with outdated builds

**Deployment Packages Rebuilt (All Fresh):**
- myparkingmanager-v2.3.0-full.zip (141 KB)
- myparkingmanager-v2.3.0-minimal.zip (132 KB)
- myparkingmanager-v2.3.0-update.zip (15 KB) - Critical fix
- myparkingmanager-v2.3.0-docs.zip (11 KB)
- All packages verified with SHA256 checksums
- Complete documentation in deployment/README.txt and QUICK-FIX-GUIDE.txt

## User Preferences

Preferred communication style: Simple, everyday language.

## System Architecture

### Frontend Architecture

**Technology Stack:**
- Pure JavaScript (ES6+) with no framework dependencies
- Mobile-first responsive CSS design
- Dynamic configuration system with automatic path detection
- CSRF token-based security for all API requests

**Key Design Patterns:**
- Single Page Application (SPA) architecture with page-based routing
- Dynamic base path resolution for flexible installation (root or subdirectory)
- Configuration auto-detection via `config.js` loader
- HTML escaping for XSS prevention
- Toast notification system for user feedback

**Core Components:**
- `app.js` / `app-secure.js`: Main application logic with API integration
- `config.js`: Dynamic path and configuration detection
- `style.css`: Mobile-first responsive styling
- Separate pages for license management, violations management, and ticket printing

### Backend Architecture

**Technology Stack:**
- PHP 8.3+ (minimum 7.4)
- Procedural PHP architecture with some OOP components
- Session-based authentication
- RESTful API design pattern

**Core Architectural Components:**

1. **Configuration System:**
   - `ConfigLoader` class for dynamic path resolution
   - Installation path auto-detection
   - Environment-based configuration support
   - Separation of sample and production configs

2. **Database Layer:**
   - `Database` class with singleton pattern (note: diagnostic shows getInstance() issues)
   - PDO-based MySQL connectivity
   - Prepared statements for SQL injection prevention

3. **Authentication & Authorization:**
   - Session-based user authentication
   - Role-based access control (RBAC)
   - Rate limiting on login attempts
   - License-based feature access control

4. **License System:**
   - HMAC-SHA256 cryptographic signing
   - 30-day trial period from installation
   - Installation-specific and universal license keys
   - Feature restriction enforcement post-trial

5. **API Structure:**
   - RESTful endpoints under `/api` directory
   - JSON request/response format
   - CSRF token validation
   - Credential-based session management

**Key Files:**
- `includes/database.php`: Database connectivity layer
- `includes/session.php`: Session management
- `includes/config-loader.php`: Dynamic configuration
- `includes/license-keys.php`: License validation logic
- `api/*.php`: RESTful API endpoints

### Data Storage

**Database: MySQL 5.7+ / MariaDB 10.2+**

**Core Tables:**
- `users`: User accounts with role assignment
- `properties`: Property records
- `vehicles`: Vehicle registration and tracking
- `property_contacts`: Contact information per property
- `user_assigned_properties`: User-property relationships
- `audit_logs`: Comprehensive activity logging
- `sessions`: Session management

**Additional Tables (implied from features):**
- Violation types with fine amounts and tow deadlines
- Violation records linked to vehicles
- License activation tracking

**Design Principles:**
- Relational schema with foreign key constraints
- Audit trail for all critical operations
- Support for multi-property deployments
- Migration scripts for version upgrades (`sql/migrate.sql`, `sql/migrate-v2-database-module.sql`)

### Security Architecture

1. **Authentication:**
   - Password hashing (bcrypt/Argon2)
   - Session token validation
   - Login attempt rate limiting
   - Account lockout mechanism

2. **Authorization:**
   - Role-based permissions (admin, manager, staff)
   - Feature gating based on license status
   - Property-level access control

3. **Input Validation:**
   - HTML escaping on output
   - Prepared SQL statements
   - CSRF token validation
   - Parameter sanitization

4. **License Security:**
   - HMAC-SHA256 signature verification
   - Installation ID binding
   - Configurable secret key via environment

## External Dependencies

### Required PHP Extensions
- `pdo`: Database abstraction
- `pdo_mysql`: MySQL connectivity
- `json`: JSON encoding/decoding
- `session`: Session management
- `mbstring`: Multi-byte string handling

### Database
- **MySQL**: 5.7+ (hosted at mysql.2clv.com in production)
- **Alternative**: MariaDB 10.2+
- Database name: `managemyparking`
- User authentication required

### Web Server Requirements
- Apache or Nginx
- mod_rewrite (Apache) or equivalent URL rewriting
- PHP-FPM support
- HTTPS recommended for production

### Deployment Platform
- Shared hosting compatible
- Flexible installation path support
- Target deployment: https://2clv.com/jrk
- FTP/SFTP for file uploads
- cPanel/phpMyAdmin for database management

### Third-Party Integrations
- None identified (self-contained system)
- Potential for SMTP email integration (not yet implemented)
- Logo upload support for violation tickets (local file storage)

### Development Tools
- Setup wizard for installation (`setup-wizard.php`, `setup.php`)
- Diagnostic tool for system verification (`diagnostic.php`)
- License key generator scripts
- Deployment packaging system with checksums
- SQL migration scripts for version updates