# ManageMyParking (MyParkingManager)

## Overview

ManageMyParking is a PHP-based vehicle and property management system designed for shared hosting environments. Its primary purpose is to provide comprehensive parking violation tracking, vehicle management, and property administration with robust role-based access control. The system targets property managers, parking administrators, security personnel, and property owners. Key capabilities include managing multiple properties, tracking vehicles and violations, resident information management, and detailed audit logging. It features a subscription-based licensing system with a 30-day trial and supports flexible deployment across various hosting configurations.

## Recent Changes

### October 30, 2025 - Reprint Ticket Error Fixed
- **Reprint Ticket Bug Fixed:**
  - Fixed critical bug in violations-ticket.php API endpoint
  - Issue: Ticket stores property ID (UUID), but code was querying by property name
  - Changed query from `WHERE name = ?` to `WHERE id = ?`
  - Reprint ticket now correctly loads property data and custom ticket text
  - Fixes blank or failed ticket reprints

### October 30, 2025 - Find Duplicates Auto-Refresh Fixed
- **Duplicates Auto-Refresh After Edit/Delete:**
  - Added isViewingDuplicates flag to track when user is viewing duplicates
  - After editing a vehicle from duplicates list, automatically refreshes duplicates search
  - After deleting a vehicle from duplicates list, automatically refreshes duplicates search
  - Ensures duplicate list always shows current data (no stale results)
  - Works correctly for both plate and tag duplicate searches
  - Fixes issue where edited/deleted duplicates still appeared until manual refresh

## User Preferences

Preferred communication style: Simple, everyday language.

**CRITICAL DATABASE REQUIREMENT:**
- **MySQL ONLY** - Never use PostgreSQL for any project
- Always use MySQL 5.7+ or MariaDB 10.2+
- No PostgreSQL, no database conversions

## System Architecture

### UI/UX Decisions

The frontend is a Single Page Application (SPA) built with pure JavaScript (ES6+) and mobile-first responsive CSS, avoiding external frameworks. It features streamlined navigation with 3 main tabs (Vehicles, Properties, Settings) and sub-tabs for settings. Accessibility is a priority, with increased contrast on text and elements, adhering to WCAG guidelines. Ticket designs are optimized for thermal printers, utilizing black and white schemes with bold text and border styles for emphasis.

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
-   **Deployment Packaging System:** Automated scripts for creating customer distribution and registration packages, ensuring separation of license management files for security.
-   **Navigation Consolidation:** Main navigation consolidated to 3 tabs (Vehicles, Properties, Settings), with Settings having 4 sub-tabs for improved organization and mobile responsiveness.
-   **Violation Search:** Reorganized form with date range validation and improved styling.
-   **MySQL Database Setup:** Automated MySQL server workflow in development environment, with a default admin user and pre-assigned properties.
-   **User Management:** Functional user management with correct includes, standardized UI elements, and permission presets.
-   **User Property Assignment:** Admins can assign specific properties to users, enforcing data visibility based on assignments.
-   **Property-Specific Ticket Text:** Custom text field for properties to display on violation tickets.
-   **Black & White Ticket Design:** Optimized ticket designs for thermal printers by converting colors to black and white with enhanced borders and text.
-   **Accessibility:** Improved contrast on text and elements for better readability.
-   **Duplicate Vehicle Detection:** Functionality to find and display duplicate vehicles based on tag/plate number, with options to edit or delete duplicates.

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