# ManageMyParking

## Overview
ManageMyParking v2.3.8 is a PHP-based vehicle and property management system for shared hosting environments. It provides comprehensive parking violation tracking, vehicle and resident management, property administration, and user management with role-based access control. The system targets property managers, parking administrators, security personnel, and property owners. Key capabilities include multi-property management, vehicle and violation tracking, resident information, audit logging, guest pass generation, ticket status management, and user search. It features a subscription-based licensing system with a 30-day trial and integrates Zebra ZPL for thermal printing of violation tickets and guest passes. The latest v2.0 development introduces a comprehensive payment system.

## User Preferences
Preferred communication style: Simple, everyday language.

**CRITICAL DATABASE REQUIREMENT:**
- **MySQL ONLY** - Never use PostgreSQL for any project
- Always use MySQL 5.7+ or MariaDB 10.2+
- No PostgreSQL, no database conversions

## System Architecture

### UI/UX Decisions
The frontend is a pure JavaScript (ES6+) Single Page Application (SPA) with mobile-first responsive CSS, avoiding external frameworks. It features streamlined navigation (Vehicles, Properties, Settings tabs), accessibility (WCAG compliance, increased contrast), and thermal printer-optimized ticket designs using black and white schemes with dynamic logo integration via ZPL. A two-tier fixed layout for header and navbar ensures consistent branding and navigation.

### Technical Implementations
The backend is built with PHP 8.3+ (minimum 7.4) using a procedural architecture with OOP elements, following a RESTful API design pattern and session-based authentication.

**Core Architectural Components:**
-   **Configuration System:** `ConfigLoader` for dynamic path resolution and environment-based configurations.
-   **Database Layer:** PDO-based MySQL connectivity with prepared statements.
-   **Authentication & Authorization:** Session-based authentication, role-based access control (RBAC), login rate limiting, and license-based feature access.
-   **License System:** HMAC-SHA256 cryptographic signing, 30-day trial, and installation-specific/universal license keys.
-   **API Structure:** RESTful JSON endpoints under `/api` with CSRF token validation.
-   **Security:** Password hashing (bcrypt/Argon2), session token validation, global security headers (HSTS, CSP), input validation, and CSRF protection.
-   **ZPL Image Conversion:** `ZPLImageConverter` for automatic conversion of images to ZPL ^GF format for thermal printer logos.

### Feature Specifications
-   **License Management:** Dynamic "TRIAL" or "EXPIRED" badges, dedicated license tab for status and activation.
-   **Navigation:** Consolidated to 3 main tabs (Vehicles, Properties, Settings) with 5 sub-tabs under Settings.
-   **Vehicle Management:** Duplicate vehicle detection, non-resident/guest vehicle tracking with "guest of" apartment, streamlined unknown plate workflow.
-   **Violation Ticketing:** "WARNING" vs. "VIOLATION" ticket types, property-specific ticket text, black & white ticket design for thermal printers, Zebra ZQ510 integration for ZPL generation.
-   **Guest Pass Generation:** Temporary guest vehicle records with automatic 7-day expiration, professional letter-size guest pass printing.
-   **Ticket Status Management:** Close tickets (collected/dismissed), dedicated status screen with filters and audit logging.
-   **User Management:** Functional user search, property assignment for user data visibility.
-   **CSV Import/Export:** Enhanced modals for property-specific filtering during import and export.
-   **Payment System (v2.0):** Fine payment processing with Stripe/Square/PayPal Payment Links (QR code generation), manual payment recording (cash, check, manual card), payment status tracking (unpaid/partial/paid), payment history modal, automatic ticket closure, property-specific payment configuration, Defuse PHP Encryption for API keys, webhook support.

### System Design Choices
Uses **MySQL 5.7+ / MariaDB 10.2+** with a relational schema and foreign key constraints. Core tables include `users`, `properties`, `vehicles`, `violation_tickets`, and audit logs. HTTPS is mandatory for production.
-   **Key Schema Extensions:** `expiration_date` for guest passes, `status`, `fine_disposition`, `closed_at`, `closed_by_user_id` for violation tickets.
-   **v2.0 Payment Schema:** `payment_settings`, `ticket_payments`, `qr_codes` tables, and extensions to `violation_tickets` for payment status and linking.

## External Dependencies

### Required PHP Extensions
-   `pdo`, `pdo_mysql`, `json`, `session`, `mbstring`, `gd`

### Database
-   **MySQL**: 5.7+
-   **MariaDB**: 10.2+

### Web Server Requirements
-   Apache or Nginx (`mod_rewrite` or equivalent)
-   PHP-FPM support
-   HTTPS (recommended)

### Deployment Platform
-   Shared hosting compatible
-   FTP/SFTP
-   cPanel/phpMyAdmin

### Third-Party Integrations
-   None (self-contained system, payment system integrations are internal to v2.0 development)