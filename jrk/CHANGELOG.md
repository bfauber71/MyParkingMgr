# MyParkingManager Changelog

## Version 2.3.8 (2025-11-08)
### New Features
- **Guest Pass Generation System**: Create guest vehicle records with automatic 7-day expiration tracking
- **Guest Pass Printing**: Professional letter-size guest pass printing with property logo and vehicle information
- **Ticket Status Management**: Close tickets by marking fines as "collected" or "dismissed"
- **Expired Guest Pass Display**: Automatic "EXPIRED" status shown in vehicle search for expired guest passes
- **Streamlined Unknown Plate Workflow**: "Create Ticket for [PLATE]" button when searching unknown vehicles

### Database Changes
- Added `expiration_date` field to vehicles table for guest pass tracking
- Added `status`, `fine_disposition`, `closed_at`, `closed_by_user_id` fields to violation_tickets table
- Added indexes for improved performance on expiration and status queries

### API Endpoints
- `/api/guest-pass-create` - Create guest vehicle with expiration date
- `/api/tickets-list` - List tickets with status and property filtering
- `/api/ticket-close` - Close tickets with audit logging

### UI Enhancements
- New "Guest Pass" menu item in main navigation
- New "Ticket Status" menu item for ticket management
- Guest pass form with property selection and vehicle details
- Ticket status filter (Active/Closed) with bulk operations
- EXPIRED status shown in red for expired guest passes

## Version 2.3.7 (2024-10-28)
### Features
- ZPL Logo Integration for thermal printers
- Streamlined Unknown Plate Workflow
- Automatic transparency handling for thermal printing

## Version 2.3.0 (2024-10-27)
### Major Features
- **Flexible Installation Path System**: Removed all hardcoded path references, application can now be installed in any directory
- **Dynamic Configuration Loader**: Auto-detects installation paths and provides centralized configuration management
- **Frontend Auto-Configuration**: JavaScript automatically adapts to installation location

### Enhancements
- Added ConfigLoader class for dynamic path resolution
- Created frontend config.js for automatic path detection
- Updated all API endpoints to use dynamic paths
- Admin tool for viewing path configuration
- Improved setup wizard with path configuration

## Version 2.2.0 (2024-10-27)
### Major Features
- **Violation Fine Management**: Added fine amounts to violation types
- **Tow Deadline System**: Violations can specify hours until vehicle can be towed
- **Advanced Printer Configuration**: Customizable ticket size and logo support

### New Features
- Total fine calculation on tickets
- Conditional towing warnings (only shows when applicable)
- Custom ticket dimensions (width, height, units)
- Logo placement (top/bottom of tickets)
- Violations management interface for admins

### Improvements
- Smart tow deadline calculation (uses minimum when multiple violations)
- Role-based printer settings access
- Enhanced ticket printing layout

## Version 2.1.0 (2024-10-27)
### Major Features
- **Subscription Licensing System**: 30-day trial with license key activation
- **Cryptographically Signed Keys**: HMAC-SHA256 signed license keys
- **Feature Access Control**: Automatic feature restrictions after trial

### Security Enhancements
- Fixed critical license validation vulnerability
- Proper signature verification for license keys
- Rate limiting on license attempts
- Configurable secret key via environment variables

### New Features
- Installation-specific and universal license keys
- License management UI
- Key generation utilities
- Comprehensive audit logging

## Version 2.0.0 (2024-10-26)
### Major Release
- **Removed Default Credentials**: Enhanced security by eliminating hardcoded admin/admin123
- **Setup Wizard**: Multi-step secure installation process
- **Database Module System**: Proper foreign keys and relationships
- **CSRF Protection**: Added throughout application
- **Enhanced Security**: Password strength requirements, secure session handling

### Features
- Properties management with contacts
- Vehicle tracking and violations
- User permission system
- Bulk operations
- CSV import/export
- Audit logging

## Version 1.0.0 (2024-10-25)
### Initial Release
- Basic vehicle and property management
- User authentication
- Simple violation tracking
- Basic reporting