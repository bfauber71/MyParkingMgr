# ManageMyParking (MyParkingManager)

## Overview

ManageMyParking is a PHP-based vehicle and property management system designed for shared hosting environments. Its primary purpose is to provide comprehensive parking violation tracking, vehicle management, and property administration with robust role-based access control. The system targets property managers, parking administrators, security personnel, and property owners. Key capabilities include managing multiple properties, tracking vehicles and violations, resident information management, and detailed audit logging. It features a subscription-based licensing system with a 30-day trial and supports flexible deployment across various hosting configurations.

## User Preferences

Preferred communication style: Simple, everyday language.

**CRITICAL DATABASE REQUIREMENT:**
- **MySQL ONLY** - Never use PostgreSQL for any project
- Always use MySQL 5.7+ or MariaDB 10.2+
- No PostgreSQL, no database conversions

## Recent Changes

### October 27, 2025 - v2.3.7 MySQL Database Installation & User Management Fixes

**MySQL Database Environment Setup:**
- Installed MariaDB 10.11.13 in Replit environment (MySQL-compatible)
- Created automated MySQL Server workflow (runs on startup)
- Database: myparkingmanager (localhost:3306)
- Credentials: root/[no password] (development only)
- All 12 tables successfully created (users, properties, vehicles, violations, etc.)
- Default admin user: username `admin`, password `admin123`
- Admin users now automatically assigned to all properties

**User Management Fixes:**
- Fixed api/user.php missing includes (session.php, helpers.php) - prevents login errors
- Added setPermissionPreset() JavaScript function for quick permission assignment
- Standardized all checkbox sizes to 16px (one-line height) for consistent UI
- Fixed permission preset buttons (Admin/View Only/Custom) functionality

**Setup Wizard Improvements:**
- Fixed PDO::MYSQL_ATTR_USE_BUFFERED_QUERY configuration in all setup steps
- Updated importSQLFile() to disable foreign key checks during schema installation
- Removed transactions from DDL statements (auto-commit behavior)

### October 27, 2025 - v2.3.6 Production Security Hardening & User Property Assignment

**CRITICAL FIXES:**

1. **Setup Wizard Database Installation Fixed**
   - Added PDO::MYSQL_ATTR_USE_BUFFERED_QUERY to all PDO connections in setup wizard
   - Fixes "SQLSTATE[HY000]: General error 2014" when installing database schema
   - Enables MySQL query buffering for multi-statement SQL execution
   - Applied to all 3 setup steps (database config test, schema install, admin creation)

2. **CSRF Protection Globally Enforced**
   - Removed host-based bypass that skipped validation for "replit" and "localhost" domains
   - Added automatic CSRF validation to `requireAuth()` function (helpers.php)
   - ALL authenticated POST/PUT/DELETE/PATCH endpoints now validate CSRF tokens
   - Fixed request body double-consumption by checking X-CSRF-Token header first

2. **Global Security Headers**
   - Added `Security::setSecurityHeaders()` to index.php main entry point
   - Sets HSTS, CSP, X-Frame-Options, X-Content-Type-Options globally
   - Prevents clickjacking, XSS, and MIME-sniffing attacks

3. **Database Error Leakage Fixed**
   - Fixed 4 API endpoints that leaked database error messages
   - endpoints: users-create.php, users-update.php, vehicles-duplicates.php, vehicles-bulk-delete.php
   - All now return generic "Failed to..." messages instead of raw PDOException details
   - Prevents information disclosure about database structure

**Security Verification Completed:**
- ✅ CSRF protection on ALL authenticated state-changing requests (no bypasses)
- ✅ Security headers (HSTS, CSP) set globally
- ✅ SQL injection prevented via PDO prepared statements + MySQL
- ✅ XSS prevented via sanitize() (htmlspecialchars with ENT_QUOTES)
- ✅ Authentication required on all protected endpoints
- ✅ Password hashing uses bcrypt (PASSWORD_BCRYPT, cost=10)
- ✅ Session security: secure=true, httponly=true (HTTPS mandatory)
- ✅ No debug code in production (display_errors=0, conditional console.log)
- ✅ Error messages don't leak database details
- ✅ MySQL only (no PostgreSQL references)
- ✅ Audit logging for critical operations

**Deployment Requirements:**
- HTTPS is MANDATORY (enforced via secure=true cookie flag)
- MySQL 5.7+ or MariaDB 10.2+
- PHP 7.4+ with required extensions
- X-CSRF-Token header must be sent by frontend (already configured in app-secure.js)

### October 27, 2025 - v2.3.6 User Property Assignment Feature

**New Feature:**
- Added property assignment to user create/edit functionality
- Admins can now assign specific properties to users during user management
- Non-admin users only see data for their assigned properties
- Property assignments displayed as checkboxes in user modal
- Automatic permission enforcement (requires MODULE_USERS, ACTION_VIEW)

**Technical Details:**
- index.html: Added property assignment section to user modal (+8 lines)
- app-secure.js: Added loadUserProperties() and loadUserPermissions() functions (+80 lines)
- app-secure.js: Updated handleUserSubmit() to collect and send assigned_properties
- api/users-assigned-properties.php: New endpoint to fetch user's assigned properties
- api/users-permissions.php: New endpoint to fetch user's permissions
- api/users-create.php: Updated to save assigned properties for new users
- api/users-update.php: Updated to save assigned properties when editing users
- includes/helpers.php: Added saveUserAssignedProperties() function
- sql/add-user-assigned-properties-table.sql: Migration to create table and assign admins

### October 27, 2025 - v2.3.5 Production Server Compatibility Fixes

**Critical Bug Fixes:**
- Fixed login.php missing includes causing 500 errors
- Fixed .htaccess API routing (API calls now work without .php extension)
- Fixed printer_settings table column size (TEXT → LONGTEXT for logo support)
- Fixed deprecated MySQL VALUES() syntax in printer-settings.php for compatibility
- All fixes target production server compatibility issues

**Technical Details:**
- api/login.php: Added required includes (database, session, helpers, security)
- .htaccess: Added API routing rule to append .php extension automatically
- sql/fix-printer-settings-column-size.sql: Migration to support large base64 logos
- sql/install.sql: Updated printer_settings.setting_value to LONGTEXT
- sql/add-printer-settings-table.sql: Updated to use LONGTEXT
- api/printer-settings.php: Replaced VALUES() with parameter binding

### October 27, 2025 - v2.3.4 Property-Specific Ticket Text & B/W Ticket Design

**Property-Specific Custom Ticket Text:**
- Added custom_ticket_text field to properties table and forms
- Property managers can add custom text (e.g., tow company info, payment instructions)
- Custom text displays on violation tickets below the fine total
- Text is property-specific and appears on all tickets for that property
- Migration file: sql/add-custom-ticket-text.sql

**Black & White Ticket Design:**
- Converted all ticket colors to black and white for better thermal printer compatibility
- Replaced color-based emphasis with bold text and border styles
- Fine section: 3px solid black border (was 2px with light background)
- Tow warning: 3px double border with underline (was red text)
- Custom property text: 2px solid border, center-aligned
- Added ⚠ symbols to tow warning for visual emphasis
- All buttons and UI elements now use black/gray instead of colors

**Technical Details:**
- violations-print.html: Updated styling and custom text display
- index.html: +5 lines (added custom_ticket_text field to property form)
- app-secure.js: Updated property form handling (+29 lines)
- properties-create.php: Saves custom_ticket_text field
- properties-update.php: Updates custom_ticket_text field
- violations-ticket.php: Returns custom_ticket_text in ticket data
- sql/install.sql: Added custom_ticket_text column to properties table

### October 27, 2025 - v2.3.3 Accessibility & Contrast Improvements

**Improved Readability:**
- Increased contrast on all instructional and informational text boxes
- Help text color changed from #64748b to #cbd5e1 (51% brighter)
- Subtitle text color changed from #94a3b8 to #cbd5e1 (40% brighter)
- Contact section headings now use #e2e8f0 (bright white)
- Added new .settings-info class with high-contrast borders and text
- Enhanced .bulk-operation-card borders (1px → 2px solid)
- Enhanced .database-subsection borders (added 2px solid #334155)
- Improved .duplicate-group heading contrast (#ef4444 → #fca5a5)
- Added explicit color definitions to all informational elements
- All changes follow WCAG accessibility guidelines for better readability

**Technical Details:**
- style.css: 1,360 lines (was 1,334 lines, +26 lines)
- 8 CSS classes updated for higher contrast
- 1 new CSS class added (.settings-info)

## System Architecture

### Frontend Architecture

The frontend is a Single Page Application (SPA) built with pure JavaScript (ES6+) and mobile-first responsive CSS, avoiding external frameworks. It features dynamic configuration with automatic path detection, CSRF token-based security, HTML escaping for XSS prevention, and a toast notification system for user feedback. Core components include `app.js` (main logic), `config.js` (dynamic configuration), and `style.css` (styling), with dedicated pages for license, violations, and printing.

### Backend Architecture

The backend is built with PHP 8.3+ (minimum 7.4) using a procedural architecture with some OOP elements. It follows a RESTful API design pattern and uses session-based authentication.

**Core Architectural Components:**

1.  **Configuration System:** Utilizes a `ConfigLoader` for dynamic path resolution, supporting environment-based configurations and auto-detection of installation paths (root or subdirectory).
2.  **Database Layer:** Employs a `Database` class with PDO-based MySQL connectivity and prepared statements to prevent SQL injection.
3.  **Authentication & Authorization:** Implements session-based user authentication, role-based access control (RBAC), login attempt rate limiting, and license-based feature access control.
4.  **License System:** Features HMAC-SHA256 cryptographic signing, a 30-day trial, and installation-specific/universal license keys to enforce feature restrictions.
5.  **API Structure:** RESTful endpoints under `/api` use JSON for requests/responses, with CSRF token validation and credential-based session management.

### Data Storage

The system uses **MySQL 5.7+ / MariaDB 10.2+** as its database.

**Core Tables:** `users`, `properties`, `vehicles`, `property_contacts`, `user_assigned_properties`, `audit_logs`, and `sessions`. Additional tables support violation types, violation records, and license activation tracking. The database schema is relational with foreign key constraints, supports multi-property deployments, and includes an audit trail for critical operations. Migration scripts are provided for version upgrades.

### Security Architecture

1.  **Authentication:** Password hashing (bcrypt/Argon2), session token validation, login attempt rate limiting, and account lockout.
2.  **Authorization:** Role-based permissions (admin, manager, staff), feature gating based on license status, and property-level access control.
3.  **Input Validation:** HTML escaping on output, prepared SQL statements, CSRF token validation, and parameter sanitization.
4.  **License Security:** HMAC-SHA256 signature verification, installation ID binding, and configurable secret key.

## External Dependencies

### Required PHP Extensions
-   `pdo`
-   `pdo_mysql`
-   `json`
-   `session`
-   `mbstring`

### Database
-   **MySQL**: 5.7+ (e.g., `mysql.2clv.com`)
-   **MariaDB**: 10.2+
-   Database name: `managemyparking`

### Web Server Requirements
-   Apache or Nginx
-   `mod_rewrite` (Apache) or equivalent
-   PHP-FPM support
-   HTTPS (recommended for production)

### Deployment Platform
-   Shared hosting compatible
-   FTP/SFTP for file transfers
-   cPanel/phpMyAdmin for database management

### Third-Party Integrations
-   None (self-contained system)