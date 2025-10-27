# ManageMyParking (MyParkingManager)

## Overview

ManageMyParking is a professional PHP-based vehicle and property management system designed for shared hosting environments. The application provides comprehensive parking violation tracking, vehicle management, and property administration with role-based access control. It features a subscription-based licensing system with a 30-day trial period and supports flexible deployment across various hosting configurations.

The system is built to handle multiple properties, track vehicles and violations, manage resident information, and provide detailed audit logging. It's designed for property managers, parking administrators, security personnel, and property owners.

## Recent Changes

### October 27, 2025 - v2.3.1 Print Functionality & Settings Page

**Print Functionality Implementation:**
- Implemented handleViolationPrint() to create printable 8.5x11 letter-sized output
- Print window opens with formatted table of search results
- @page styling ensures proper letter size (8.5" x 11")
- Table headers repeat on each printed page
- Professional print layout with title, timestamp, and result count
- Print/Close buttons for user convenience

**Settings Tab Added:**
- New "Settings" tab in main navigation
- Settings section with printer configuration access
- Button to open violations-print.html for ticket customization
- Informational content explaining:
  - Violation ticket printer settings (2.5" x 6" thermal tickets)
  - Search results printing (8.5" x 11" letter paper)
  - Difference between ticket and search result printing

**New Functions:**
- loadSettingsSection() - Handles settings page initialization
- Enhanced handleViolationPrint() - Creates printable preview window

**File Stats:**
- app-secure.js: 1,978 lines, 64 functions (was 63)
- index.html: 659 lines (was 612), added Settings section
- Fully functional print workflow for search results

### October 27, 2025 - v2.3.0 Complete Functionality Fixes

**✅ ALL 7 REPORTED ISSUES RESOLVED**

**Violation Modal Fixes:**
- Added Fine Amount ($) input field with decimal support
- Added Towing Deadline (hours) input field
- Fields populate correctly when editing violations
- Form submission includes both fields in API calls

**User Edit Button Fix:**
- Fixed field ID mismatch (userName → userUsername)
- Edit button now opens modal with populated user data
- All user fields display correctly when editing

**Database Page Dropdown Fixes:**
- Bulk Delete property dropdown now populates with all properties
- Violation Search property filter now populates automatically
- Violation Search type filter now populates with violation types
- All dropdowns loaded when Database page opens

**Search UX Improvements:**
- Added Clear button to Find Duplicates section
- Added Clear button to Violation Search section
- Clear buttons reset all filters and results
- Toast notifications confirm actions

**NEW FUNCTIONS ADDED (3):**
- populateDatabaseDropdowns() - Fetches and populates all dropdowns
- handleClearViolationSearch() - Clears all search filters and results
- handleClearDuplicates() - Clears duplicate search results

**UPDATED FUNCTIONS (3):**
- openViolationTypeModal() - Now populates fine_amount and tow_deadline_hours
- handleViolationTypeSubmit() - Includes fine/tow fields in form data
- setupDatabasePageHandlers() - Now async, calls dropdown population

**FILE STATS:**
- app-secure.js: 1,817 lines (was 1,702) - 63 functions total (was 60)
- index.html: 610 lines (updated with new fields and buttons)
- Added 115+ lines of new functionality
- Update package: 20 KB (6 files)

**API Method Fixes:**
- Fixed handleFindDuplicates() to use POST instead of GET
- Fixed handleViolationSearch() to use POST instead of GET
- Both functions now send JSON request bodies matching API expectations
- Improved error handling and user feedback
- app-secure.js: 1,825 lines total

**Previous Updates:**
- Complete form submission handlers for Properties/Users/Vehicles
- All modal and CRUD functions
- Fixed "nothing clicks" after login
- All tabs and data loading functional

**Additional Fixes:**
- Fixed .htaccess RewriteBase path (was /jrk/, now / for root installation)
- Fixed infinite redirect loop in .htaccess causing 500 errors
- Added api/csrf-token.php endpoint for CSRF token generation
- Added clear documentation in .htaccess for subdirectory installations
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