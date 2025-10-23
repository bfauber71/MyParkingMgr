# ManageMyParking - Shared Hosting Edition

## Overview

ManageMyParking is a complete PHP and MySQL vehicle and property management system designed for shared hosting environments. Its primary purpose is to provide a robust, no-framework solution for managing vehicles, properties, users, and violations, specifically tailored for deployment via FTP without requiring command-line access, Composer, or Node.js. The project aims for broad compatibility with standard shared hosting setups, including cPanel and phpMyAdmin for database management.

Key capabilities include:
- Role-based access control (Admin, User, Operator)
- Comprehensive CRUD operations for vehicles, properties, and users
- Violation ticketing system with printable tickets
- CSV import/export for vehicle data
- Audit logging for all major operations
- Responsive, modern UI with a dark theme

The business vision is to offer an accessible, easy-to-deploy parking management solution for small to medium-sized organizations or properties that utilize shared hosting.

## User Preferences

User required deployment to shared hosting without custom installations (no Composer, no command-line access, FTP-only).

## System Architecture

### UI/UX Decisions
The frontend is built with vanilla HTML, CSS, and JavaScript, ensuring no build tools are required. It features a responsive dark theme and a tabbed navigation interface (Vehicles, Properties, Users, Violations). Role-based menu visibility and permissions are implemented directly in the frontend and enforced by the backend.

**Recent UX Improvements (Oct 23, 2025):**
- Violations tab added for admin users to manage violation types
- Vehicle display defaults to empty state until search is performed
- Clear button added to search bar for quick filter reset

### Technical Implementations
- **Backend Language:** Plain PHP 7.4+ (no framework)
- **Database:** MySQL 5.7+ with PDO for secure database interactions.
- **Authentication:** PHP sessions combined with `password_hash`/`password_verify` for secure user authentication.
- **Routing:** A custom front controller pattern handles URL routing.
- **Frontend:** Vanilla HTML/CSS/JavaScript with no external dependencies or build processes.
- **Security Features:** PDO prepared statements, bcrypt password hashing, HTTP-only session cookies, XSS prevention via `htmlspecialchars`, role-based access control, comprehensive audit logging, and Apache security headers.
- **Role-Based System:**
    - **Admin:** Full CRUD access to vehicles, properties, users, and violations.
    - **User:** Manage vehicles and create violations for assigned properties.
    - **Operator:** View-only access to vehicles.
- **Deployment:** Optimized for FTP-only deployment to shared hosting, compatible with cPanel/phpMyAdmin. Includes a single `install.sql` file for database setup.
- **Environment:** Auto-detection for base paths (Replit vs. production) and HTTPS auto-detection for session cookies.

### Feature Specifications
- **Vehicle Management:** 14 fields, search with clear button, edit, delete, export, CSV import with validation. Empty state displayed until search is performed.
- **Property Management:** Create, edit, delete with 1-3 contacts per property, with transactions for data integrity. Property name changes automatically update vehicle references.
- **User Management:** Create, delete, role assignment (Admin only).
- **Violations Management (Admin Only):** Add, edit, delete violation types; toggle active/inactive status; set display order for violation options.
- **Violation Tickets:** Multi-select violations, printable 2.5" x 6" tickets, with associated database tables and API endpoints. Includes security for property access control.
- **Audit Logging:** Comprehensive logging for all operations.

## External Dependencies

- **MySQL 5.7+:** Database system for storing application data.
- **PHP 7.4+:** Server-side scripting language runtime.
- **Apache (.htaccess):** Web server with rewrite rules for URL handling and security headers.
- **cPanel/phpMyAdmin:** Standard shared hosting tools for database creation and management.