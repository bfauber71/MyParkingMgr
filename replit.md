# ManageMyParking (MyParkingManager)

## Overview

ManageMyParking is a PHP-based vehicle and property management system designed for shared hosting environments. Its primary purpose is to provide comprehensive parking violation tracking, vehicle management, and property administration with robust role-based access control. The system targets property managers, parking administrators, security personnel, and property owners. Key capabilities include managing multiple properties, tracking vehicles and violations, resident information management, and detailed audit logging. It features a subscription-based licensing system with a 30-day trial and supports flexible deployment across various hosting configurations.

## User Preferences

Preferred communication style: Simple, everyday language.

## Recent Changes

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