# MyParkingManager - Shared Hosting Edition

## Overview

MyParkingManager is a complete PHP and MySQL vehicle and property management system designed for shared hosting environments. Its primary purpose is to provide a robust, no-framework solution for managing vehicles, properties, users, and violations, specifically tailored for deployment via FTP without requiring command-line access, Composer, or Node.js. The project aims for broad compatibility with standard shared hosting setups, including cPanel and phpMyAdmin for database management.

Key capabilities include:
- **Granular permission matrix system** replacing legacy role-based access control
- Customizable per-user permissions for view, edit, and create/delete actions across all five modules (vehicles, users, properties, violations, database)
- **Login attempt limiting** with 5-try limit, 10-minute lockout, and countdown timer
- **Database administration tab** consolidating user management, CSV import/export, and bulk operations
- Comprehensive CRUD operations for vehicles, properties, and users
- Violation ticketing system with printable tickets
- **Bulk vehicle operations** including delete by property and duplicate detection/removal
- Audit logging for all major operations
- Responsive, modern UI with a dark theme
- HTTPS-first security with secure session cookies

The business vision is to offer an accessible, easy-to-deploy parking management solution for small to medium-sized organizations or properties that utilize shared hosting.

## User Preferences

User required deployment to shared hosting without custom installations (no Composer, no command-line access, FTP-only).

## System Architecture

### UI/UX Decisions
The frontend is built with vanilla HTML, CSS, and JavaScript, ensuring no build tools are required. It features a responsive dark theme and a tabbed navigation interface (Vehicles, Properties, Database, Violations). Role-based menu visibility and permissions are implemented directly in the frontend and enforced by the backend.

**v2.0 Major Update (Oct 24, 2025):**
- **Rebranded:** ManageMyParking → MyParkingManager across all files
- **Login Attempt Limiting:** 5-try limit with 10-minute lockout, countdown timer UI, automatic reset after successful login or 1 hour
- **Database Module:** New 5th permission module for administrative functions (replaces Users tab)
- **Database Tab UI:** Consolidates user management, CSV import/export, and new bulk operations in three subsections
- **Bulk Operations:** Delete all vehicles by property, find/remove duplicate vehicles by plate or tag number
- **Enhanced Security:** HTTPS-first configuration, secure session cookies by default, login protection system
- **Migration Support:** Separate deployment packages for fresh installations and upgrades

**Recent UX Improvements (Oct 23, 2025):**
- **Mobile-First Responsive Design:** Complete CSS refactor with mobile-first approach (320px base), tablet optimization (768px+), and desktop enhancements (1024px+). All touch targets meet 44px minimum for optimal mobile usability.
- **Toast Notification System:** Custom notification system replaces browser alerts with styled toasts featuring success/error/warning/info types, 2-second auto-close (except violation ticket verification which persists), manual dismissal, and stacking support.
- Violations tab added for admin users to manage violation types
- Vehicle display defaults to empty state until search is performed
- Clear button added to search bar for quick filter reset
- Violation history tracking: "*Violations Exist" indicator on vehicle cards that displays count and opens history modal showing up to 100 past violations with details
- Fixed API violation_count: Enhanced vehicles-search endpoint with robust error handling, table existence checks, and graceful degradation

### Technical Implementations
- **Backend Language:** Plain PHP 7.4+ (no framework)
- **Database:** MySQL 5.7+ with PDO for secure database interactions.
- **Authentication:** PHP sessions combined with `password_hash`/`password_verify` for secure user authentication.
- **Routing:** A custom front controller pattern handles URL routing.
- **Frontend:** Vanilla HTML/CSS/JavaScript with no external dependencies or build processes. Mobile-first responsive design with 44px minimum touch targets, 16px input font sizes (prevents iOS zoom), and progressive enhancement at 768px and 1024px breakpoints. Custom toast notification system with type-based styling and configurable auto-close.
- **Security Features:** PDO prepared statements, bcrypt password hashing, HTTP-only session cookies, XSS prevention via `htmlspecialchars`, granular permission-based access control, comprehensive audit logging, and Apache security headers.
- **Permission Matrix System (Oct 23, 2025 / Enhanced v2.0):**
    - **Granular Permissions:** Each user has customizable permissions for all five modules (vehicles, users, properties, violations, database) across three action levels:
        - **View:** Read-only access to module data
        - **Edit:** Modify existing records (implies view permission)
        - **Create/Delete:** Add new records and delete existing ones (implies edit and view permissions)
    - **Database Module Permissions:** Controls access to user management, CSV import/export, and bulk operations (admin-only by default)
    - **Permission Hierarchy:** Permissions follow a hierarchical model where create/delete implies edit and view, and edit implies view
    - **Backward Compatibility:** System falls back to legacy role-based permissions if user_permissions table doesn't exist
    - **Legacy Role Mapping:** Admin gets all permissions including database module; User/Operator default to view-only until customized
    - **Database Schema:** `user_permissions` table with user_id FK, module enum (vehicles, users, properties, violations, database), and boolean flags for can_view, can_edit, can_create_delete
    - **Migration Support:** `migrate-permissions.sql` creates permissions table and seeds from existing roles, `migrate-v2-database-module.sql` adds database module to existing installations
- **Login Security (v2.0):**
    - **Attempt Tracking:** `login_attempts` table tracks failed attempts by username and IP address
    - **Lockout Logic:** 5 failed attempts trigger 10-minute lockout with countdown timer displayed on login page
    - **Auto-Reset:** Successful login clears attempts; 1 hour timeout auto-resets lockout
    - **UI Feedback:** Real-time countdown display prevents login attempts during lockout period
- **Deployment:** Optimized for FTP-only deployment to shared hosting, compatible with cPanel/phpMyAdmin. Two deployment packages available:
    - **Fresh Install:** Complete package with `install.sql`, HTTPS-first configuration, default admin account
    - **Migration:** Upgrade package excluding config.php and .htaccess, includes `migrate-v2-database-module.sql`
- **Environment:** Auto-detection for base paths (Replit vs. production), HTTPS-first session cookies (secure=true by default in v2.0).

### Feature Specifications
- **Vehicle Management:** 14 fields, search with clear button, edit, delete, export. Empty state displayed until search is performed. Violation count indicator appears on vehicle cards when violations exist.
- **Property Management:** Create, edit, delete with 1-3 contacts per property, with transactions for data integrity. Property name changes automatically update vehicle references.
- **Database Administration (v2.0 - Admin Only):**
    - **Users Section:** Create, edit, delete users with granular permission matrix UI. Permission matrix displays all 15 possible permissions (5 modules × 3 actions) with preset buttons for Admin (all), View Only, and Custom configurations. Password is optional when editing (leave blank to keep current password). Frontend enforces permission dependencies (edit checkbox auto-checks view, create/delete auto-checks both edit and view).
    - **Import/Export Section:** CSV import with validation (max 10,000 rows), CSV export of all accessible vehicles. Moved from Vehicles tab in v2.0.
    - **Bulk Operations Section:**
        - **Delete by Property:** Select property from dropdown, delete all vehicles for that property with confirmation and audit logging
        - **Find Duplicates:** Search by plate number or tag number, displays duplicate groups with vehicle details, delete individual duplicates with one-click removal
    - **Violation Search & Reports Section (NEW - Oct 24, 2025):**
        - **Advanced Filtering:** Date range (start/end dates), property filter, violation type filter, keyword search (plate, tag, make, model, notes)
        - **Results Display:** Table view showing date/time, property, vehicle details, plate/tag, violations, notes, and issuing user
        - **Print Functionality:** Print-optimized CSS hides UI elements, displays only results table with proper page breaks
        - **CSV Export:** Export search results to CSV with comprehensive headers (Ticket ID, Date/Time, Property, Vehicle Info, Violations, Notes, Issued By)
        - **Result Limits:** 500 violation limit for search display, 10,000 limit for export with warning messages
        - **Security:** Requires DATABASE VIEW permission, enforces property-based access control, audit logging for exports
        - **Mobile Responsive:** Filter form adapts to mobile screens with vertical layout, touch-friendly controls
- **Violations Management (Admin Only):** Add, edit, delete violation types; toggle active/inactive status; set display order for violation options.
- **Violation Tickets:** Multi-select violations, printable 2.5" x 6" tickets, with associated database tables and API endpoints. Includes security for property access control.
- **Violation History Tracking:** Each vehicle displays a "*Violations Exist" button (positioned between plate number and property name) when violations are recorded. Clicking opens a modal showing violations with pagination (5 per page, up to 100 total) in chronological order with date/time, issuing user, violation types, vehicle details, and custom notes. Includes Previous/Next navigation and page counter. Backend includes indexed queries for performance and property-based access control. The vehicles-search API endpoint returns violation_count for each vehicle with robust error handling and graceful degradation if violation_tickets table doesn't exist.
- **Audit Logging:** Comprehensive logging for all operations including bulk deletions and login attempts.

## External Dependencies

- **MySQL 5.7+:** Database system for storing application data.
- **PHP 7.4+:** Server-side scripting language runtime.
- **Apache (.htaccess):** Web server with rewrite rules for URL handling and security headers.
- **cPanel/phpMyAdmin:** Standard shared hosting tools for database creation and management.