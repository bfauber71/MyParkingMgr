# ManageMyParking

## Overview

ManageMyParking v2.3.8 is a production-ready PHP-based vehicle and property management system designed for shared hosting environments. It provides comprehensive parking violation tracking, vehicle management, property administration, and user management with robust role-based access control. The system targets property managers, parking administrators, security personnel, and property owners. Key capabilities include managing multiple properties, tracking vehicles and violations, resident information management, detailed audit logging, guest pass generation with expiration tracking, ticket status management, and user search functionality. It features a subscription-based licensing system with a 30-day trial and supports flexible deployment across various hosting configurations. The system integrates Zebra ZPL for thermal printing of violation tickets and guest passes, including automatic logo conversion and dynamic layout adjustments.

## User Preferences

Preferred communication style: Simple, everyday language.

**CRITICAL DATABASE REQUIREMENT:**
- **MySQL ONLY** - Never use PostgreSQL for any project
- Always use MySQL 5.7+ or MariaDB 10.2+
- No PostgreSQL, no database conversions

## System Architecture

### UI/UX Decisions

The frontend is a Single Page Application (SPA) built with pure JavaScript (ES6+) and mobile-first responsive CSS, avoiding external frameworks. It features streamlined navigation with 3 main tabs (Vehicles, Properties, Settings) and sub-tabs for settings. Accessibility is a priority, with increased contrast on text and elements, adhering to WCAG guidelines. Ticket designs are optimized for thermal printers, utilizing black and white schemes with bold text and border styles for emphasis, and dynamic logo integration via ZPL. The navbar is fixed at the top of the screen and includes a real-time clock respecting the configured timezone.

### Technical Implementations

The backend is built with PHP 8.3+ (minimum 7.4) using a procedural architecture with some OOP elements, following a RESTful API design pattern and session-based authentication.

**Core Architectural Components:**

-   **Configuration System:** `ConfigLoader` for dynamic path resolution and environment-based configurations.
-   **Database Layer:** PDO-based MySQL connectivity with prepared statements for SQL injection prevention.
-   **Authentication & Authorization:** Session-based user authentication, role-based access control (RBAC), login attempt rate limiting, and license-based feature access.
-   **License System:** HMAC-SHA256 cryptographic signing, 30-day trial, and installation-specific/universal license keys.
-   **API Structure:** RESTful endpoints under `/api` using JSON, with CSRF token validation and credential-based session management.
-   **Security:** Password hashing (bcrypt/Argon2), session token validation, global security headers (HSTS, CSP), input validation (HTML escaping, prepared statements), and CSRF protection.
-   **ZPL Image Conversion:** `ZPLImageConverter` class handles automatic conversion of various image formats (PNG, JPG, GIF, WEBP) to ZPL ^GF format for thermal printer logos, including transparency handling and resizing.

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
-   **Non-Resident/Guest Vehicle Tracking:** Support for marking vehicles as resident or guest, including tracking the "guest of" apartment number.
-   **Ticket Type System:** Differentiation between 'WARNING' and 'VIOLATION' ticket types, selectable during creation and displayed on tickets.
-   **Zebra ZQ510 Mobile Printer Integration:** Generation of ZPL (Zebra Programming Language) code for thermal tickets, with options to download ZPL files or view code, optimized for the Zebra ZQ510.
-   **Timezone Configuration:** Configurable timezone setting for the system, affecting the real-time clock display and violation ticket timestamps.
-   **Streamlined Unknown Plate Workflow:** When searching for a vehicle that doesn't exist, displays a "Create Ticket for [PLATE]" button that pre-fills the Add Vehicle form with the searched plate number. After creating the vehicle, automatically opens the Create Ticket modal with the newly added vehicle selected, streamlining the ticket creation process for unknown plates.
-   **Guest Pass Generation System (v2.3.8):** Create temporary guest vehicle records with automatic 7-day expiration tracking. Includes professional letter-size guest pass printing with property logo (upper left), property information (upper right), vehicle details, and expiration date in pure black and white design for optimal printing. EXPIRED status displayed in red in vehicle search for expired guest passes. Form has single "Visiting Apt/Unit" field for cleaner UX. Expiration date displays and prints in MM-DD-YYYY format (US standard). Apartment number displays only once under "Visiting" field.
-   **Ticket Status Management (v2.3.8):** Close parking tickets by marking fines as "collected" or "dismissed". Includes dedicated Ticket Status screen with status filters (Active/Closed), property filtering, and comprehensive audit logging. Database tracks status, fine_disposition, closed_at, and closed_by_user_id for full accountability.
-   **User Management Search (v2.3.8):** Functional user search by username or email with real-time filtering. Search input with Enter key support, Search/Show All/Clear buttons properly wired with event listeners. Backend API supports search parameter with SQL injection prevention via prepared statements.

### System Design Choices

The system uses **MySQL 5.7+ / MariaDB 10.2+** as its database. Core tables include `users`, `properties`, `vehicles`, `property_contacts`, `user_assigned_properties`, `audit_logs`, `sessions`, and `violation_tickets`. The schema is relational with foreign key constraints, supports multi-property deployments, and includes an audit trail. HTTPS is mandatory for production.

**Database Schema Extensions (v2.3.8):**
-   **vehicles table:** Added `expiration_date` DATE field for guest pass tracking with index
-   **violation_tickets table:** Added `status` ENUM('active','closed'), `fine_disposition` ENUM('collected','dismissed'), `closed_at` DATETIME, `closed_by_user_id` INT with foreign key to users table

## External Dependencies

### Required PHP Extensions
-   `pdo`
-   `pdo_mysql`
-   `json`
-   `session`
-   `mbstring`
-   `gd` (for ZPL Image Conversion)

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