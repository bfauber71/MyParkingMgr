# ManageMyParking (MyParkingManager)

## Overview

ManageMyParking is a PHP-based vehicle and property management system designed for shared hosting environments. Its primary purpose is to provide comprehensive parking violation tracking, vehicle management, and property administration with robust role-based access control. The system targets property managers, parking administrators, security personnel, and property owners. Key capabilities include managing multiple properties, tracking vehicles and violations, resident information management, and detailed audit logging. It features a subscription-based licensing system with a 30-day trial and supports flexible deployment across various hosting configurations.

## Recent Changes

### October 28, 2025 - Version 2.3.7 Release - Production Ready
- **Version Update:** Updated to v2.3.7 with comprehensive cleanup
- **Deprecated Files Removed:**
  - Removed old API endpoints: vehicles-search.php, vehicles-update.php (superseded by v2 versions)
  - Removed development files: test_csrf.html, router.php (dev-only)
  - Removed temporary files: CLEANUP-INSTRUCTIONS.txt, PRODUCTION-FIXES.md
  - Cleaned up deployment directory
- **Setup Utility Files Restored:**
  - setup-test-db.php: AJAX endpoint for testing database connectivity from setup wizard
- **Deployment Packages Created:**
  - End-user deployment package (168K) - Ready for customer installations
  - License key creation package (12K) - Separate secure package for admin use only
- **All Critical Fixes Included:**
  - Vehicle editing with proper field name mapping
  - Property editing without 500 errors
  - Printer settings save correctly
  - System-wide audit log protection
  - All CRUD operations stable
  - Import/Export CSV working
  - Bulk operations functional

### October 28, 2025 - Vehicle Edit, Violations, Import/Export, Bulk Operations & Form Field Fixes
- **Vehicle Editing Complete Fix:**
  - Added `sanitizeInput()` function to helpers.php (was missing, causing fatal errors)
  - Created cache-busted API endpoints (vehicles-update-v2.php, vehicles-search-v2.php, vehicles-get.php)
  - Fixed vehicle search to accept both property IDs and names (handles mixed data)
  - Added State and Reserved Space columns to vehicle display table
  - Edit modal now fetches fresh data from database instead of using cached objects
  - Browser cache prevention: timestamp parameters + cache headers on all vehicle APIs
- **Violations System Fixes:**
  - Fixed violations-create.php property lookup to handle both UUID and name formats
  - Made auditLog() function optional (safe error handling if function doesn't exist)
  - Added Fines column to violation search results
  - Violations search API now calculates total fine amount per ticket (SUM of violation fine_amount)
  - Improved error handling in violation creation (commit happens before audit log failures)
- **Import/Export CSV Fixes:**
  - Fixed FormData key mismatch (JavaScript sent 'file', PHP expected 'csv')
  - Added CSV file validation before upload
  - Enhanced error reporting with detailed row-by-row import errors
  - Added cache-control headers to vehicles-import.php to prevent caching issues
  - Corrected data field references (imported vs count) between API and frontend
- **Bulk Operations Fixes:**
  - Fixed "Delete All Vehicles" button - event handlers now properly attached on Database Ops tab load
  - Fixed "Find Duplicates" button - now works when Database Ops tab is clicked
  - Created setupDatabaseOpsHandlers() function that initializes when Database Ops sub-tab is loaded
  - Removed duplicate/stale setupDatabasePageHandlers() function that was called at wrong time
- **Printer Settings Fix:**
  - Fixed fatal error in printer-settings.php (was calling non-existent logAudit() instead of auditLog())
  - Fixed duplicate if statement and missing braces in audit log wrapper (syntax error preventing saves)
  - Made audit logging optional with function_exists() check to prevent crashes
  - Ticket settings now save successfully without 500 errors
- **System-Wide Audit Log Protection:**
  - Fixed all API endpoints to safely handle auditLog failures
  - Wrapped all auditLog() calls with function_exists() + try/catch blocks
  - Prevents "Unexpected end of JSON input" errors when audit logging fails
  - Affected files: properties-update, properties-create, properties-delete, users-create, users-update, users-delete, vehicles-create, vehicles-delete, vehicles-bulk-delete, vehicles-duplicates, violations-create, violations-add, violations-update, violations-delete, login, logout, license-activate, printer-settings
  - Database commits now always succeed even if audit logging fails
- **Cache Prevention Strategy:**
  - .htaccess now includes PHP cache-control headers to prevent OPcache issues
  - All API responses include no-cache headers
  - API calls include timestamp query parameters for unique URLs
  - Critical for shared hosting environments with aggressive PHP OPcache
- **Vehicle Form Field Name Fix:**
  - Fixed frontend/backend mismatch causing "Property is required" error
  - Frontend was sending `property_id` but backend expected `property`
  - Standardized all form field names to camelCase to match backend expectations
  - Vehicle creation and editing now work correctly

### October 27, 2025 - Production Security & Deployment Fixes
- **HTTPS Enforcement:** Production deployment package enforces HTTPS redirect for security
  - Development .htaccess: HTTPS redirect disabled (for local testing)
  - Production .htaccess: HTTPS redirect enabled (required for security)
- **Fixed API 500 Errors:** Violations and license status APIs now working correctly
- **Deployment Package Fixes:**
  - .htaccess file now included in deployment package
  - Removed 14 /tmp debug writes from printer-settings.php (shared hosting incompatible)
  - Production config.php uses template with placeholders (not hardcoded values)
  - Trial badge display fixed (JavaScript error handling)
  - Properties table rendering fixed (appendChild issue)
  - Violations list loading with detailed error logging

## User Preferences

Preferred communication style: Simple, everyday language.

**CRITICAL DATABASE REQUIREMENT:**
- **MySQL ONLY** - Never use PostgreSQL for any project
- Always use MySQL 5.7+ or MariaDB 10.2+
- No PostgreSQL, no database conversions

## System Architecture

### UI/UX Decisions

The frontend is a Single Page Application (SPA) built with pure JavaScript (ES6+) and mobile-first responsive CSS, avoiding external frameworks. It features a streamlined navigation with 3 main tabs (Vehicles, Properties, Settings) and sub-tabs for settings. Accessibility is a priority, with increased contrast on text and elements, adhering to WCAG guidelines. Ticket designs are optimized for thermal printers, utilizing black and white schemes with bold text and border styles for emphasis.

### Technical Implementations

The backend is built with PHP 8.3+ (minimum 7.4) using a procedural architecture with some OOP elements, following a RESTful API design pattern and session-based authentication.

**Core Architectural Components:**

-   **Configuration System:** `ConfigLoader` for dynamic path resolution and environment-based configurations.
-   **Database Layer:** PDO-based MySQL connectivity with prepared statements for SQL injection prevention.
-   **Authentication & Authorization:** Session-based user authentication, role-based access control (RBAC), login attempt rate limiting, and license-based feature access.
-   **License System:** HMAC-SHA256 cryptographic signing, 30-day trial, and installation-specific/universal license keys.
-   **API Structure:** RESTful endpoints under `/api` using JSON, with CSRF token validation and credential-based session management.
-   **Security:** Password hashing (bcrypt/Argon2), session token validation, global security headers (HSTS, CSP), input validation (HTML escaping, prepared statements), and CSRF protection.

### Feature Specifications

-   **License Status Display:** Dynamic "TRIAL" or "EXPIRED" badges in the app header based on license status.
-   **Deployment Packaging System:** Automated scripts (`build-deployment.sh`, `build-registration.sh`) for creating customer distribution and registration packages, ensuring separation of license management files for security.
-   **Navigation Consolidation:** Main navigation consolidated to 3 tabs (Vehicles, Properties, Settings), with Settings having 4 sub-tabs for improved organization and mobile responsiveness.
-   **Violation Search:** Reorganized form with date range validation and improved styling.
-   **MySQL Database Setup:** Automated MySQL server workflow in development environment, with a default admin user and pre-assigned properties.
-   **User Management:** Fixed user management with correct includes, standardized UI elements, and functional permission presets.
-   **User Property Assignment:** Admins can assign specific properties to users, enforcing data visibility based on assignments.
-   **Property-Specific Ticket Text:** Custom text field for properties to display on violation tickets.
-   **Black & White Ticket Design:** Optimized ticket designs for thermal printers by converting colors to black and white with enhanced borders and text.
-   **Accessibility:** Improved contrast on text and elements for better readability.

### System Design Choices

The system uses **MySQL 5.7+ / MariaDB 10.2+** as its database. Core tables include `users`, `properties`, `vehicles`, `property_contacts`, `user_assigned_properties`, `audit_logs`, and `sessions`. The schema is relational with foreign key constraints, supports multi-property deployments, and includes an audit trail. HTTPS is mandatory for production.

## External Dependencies

### Required PHP Extensions
-   `pdo`
-   `pdo_mysql`
-   `json`
-   `session`
-   `mbstring`

### Database
-   **MySQL**: 5.7+
-   **MariaDB**: 10.2+

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