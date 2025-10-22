# ManageMyParking - Complete Project Requirements Specification
## PHP 8.3 + MySQL Implementation

**Version**: 2.0 (PHP/MySQL Rebuild)  
**Deployment Target**: https://2clv.com/jrk  
**Original Project**: 2clvpark Property & Vehicle Management System

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Business Requirements](#business-requirements)
3. [Technical Stack](#technical-stack)
4. [Database Schema (MySQL)](#database-schema-mysql)
5. [Authentication & Authorization](#authentication--authorization)
6. [Feature Specifications](#feature-specifications)
7. [API Endpoints](#api-endpoints)
8. [User Interface Requirements](#user-interface-requirements)
9. [Data Import/Export](#data-importexport)
10. [Security Requirements](#security-requirements)
11. [Deployment Specifications](#deployment-specifications)
12. [Testing Requirements](#testing-requirements)

---

## Project Overview

### Application Name
**ManageMyParking** - Professional property and vehicle management system

### Purpose
Web-based application for tracking vehicles, managing parking records, and maintaining resident information across multiple properties.

### Target Users
- Property managers
- Parking administrators
- Security personnel
- Property owners

### Core Value Proposition
Centralized vehicle and parking management with role-based access control, comprehensive search capabilities, bulk data operations, and detailed audit logging.

---

## Business Requirements

### Primary Objectives

1. **Vehicle Management**
   - Track comprehensive vehicle information (14 fields per vehicle)
   - Support multiple properties
   - Enable quick vehicle lookup and verification
   - Maintain historical records

2. **Multi-Property Support**
   - Manage unlimited properties
   - Property-specific user access
   - Property contact management
   - Independent property operations

3. **User Management**
   - Three-tier role system (Admin, User, Operator)
   - Property-based access control
   - Audit trail of user actions
   - Secure authentication

4. **Data Operations**
   - Bulk CSV import
   - Filtered CSV export
   - Advanced search capabilities
   - Real-time data updates

5. **Reporting & Analytics**
   - Dashboard with statistics
   - Vehicle distribution by property
   - User activity tracking
   - Audit log reporting

### Key Stakeholder Requirements

**Property Managers Need:**
- Quick vehicle lookups by plate, tag, or owner
- Ability to add/edit vehicle records
- Export vehicle lists for their properties
- Manage contact information for properties

**Administrators Need:**
- User account management
- Property creation and configuration
- System-wide access
- Audit log review
- Reporting dashboard

**Operators Need:**
- Read-only access to all records
- Quick search capabilities
- No ability to modify data
- Access to all properties

---

## Technical Stack

### Backend

**Language & Framework**
- PHP 8.3+
- Framework: **Laravel 11** (recommended) or Symfony 7
- PHP-FPM for process management

**Database**
- MySQL 8.0+
- InnoDB storage engine
- UTF-8mb4 character set

**Session Management**
- PHP native sessions or Laravel session driver
- Database-backed sessions
- 24-hour session timeout

**Authentication**
- Laravel Sanctum or traditional PHP sessions
- Bcrypt password hashing
- CSRF protection
- Rate limiting on login attempts

### Frontend

**Framework**
- React 18+
- TypeScript (optional but recommended)
- Single Page Application (SPA)

**State Management**
- TanStack Query v5 for server state
- React hooks for local state

**UI Framework**
- Tailwind CSS 4+
- Shadcn UI components
- Radix UI primitives
- Lucide React icons

**Routing**
- Wouter (client-side routing)

### Development Tools

**Build Tools**
- Vite 5+ (frontend build)
- Composer (PHP dependencies)
- NPM/PNPM (JavaScript dependencies)

**Code Quality**
- PHPStan or Psalm (PHP static analysis)
- ESLint (JavaScript linting)
- Prettier (code formatting)

---

## Database Schema (MySQL)

### Tables Overview

1. `users` - User accounts and authentication
2. `properties` - Property information and contacts
3. `vehicles` - Vehicle records
4. `audit_logs` - System activity tracking

### Complete Schema Definitions

#### Table: `users`

```sql
CREATE TABLE `users` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `username` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed',
    `role` ENUM('admin', 'user', 'operator') NOT NULL DEFAULT 'user',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columns:**
- `id`: Unique identifier (UUID)
- `username`: Login username (unique, 3-50 characters)
- `password`: Bcrypt hashed password
- `role`: User role (admin/user/operator)
- `created_at`: Account creation timestamp
- `updated_at`: Last modification timestamp

#### Table: `user_assigned_properties`

```sql
CREATE TABLE `user_assigned_properties` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(36) NOT NULL,
    `property_id` VARCHAR(36) NOT NULL,
    `assigned_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_property` (`user_id`, `property_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_property_id` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Purpose:** Many-to-many relationship between users and properties

#### Table: `properties`

```sql
CREATE TABLE `properties` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `name` VARCHAR(255) UNIQUE NOT NULL,
    `address` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columns:**
- `id`: Unique identifier (UUID)
- `name`: Property name (unique, required)
- `address`: Physical address (optional)
- `created_at`: Property creation timestamp
- `updated_at`: Last modification timestamp

#### Table: `property_contacts`

```sql
CREATE TABLE `property_contacts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `property_id` VARCHAR(36) NOT NULL,
    `name` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(50),
    `email` VARCHAR(255),
    `position` TINYINT UNSIGNED NOT NULL COMMENT '0=first, 1=second, 2=third',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_property_position` (`property_id`, `position`),
    INDEX `idx_property_id` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Purpose:** Up to 3 contacts per property (name required, phone/email optional)

**Constraints:**
- Maximum 3 contacts per property (position 0, 1, 2)
- Name is required
- Phone and email are optional

#### Table: `vehicles`

```sql
CREATE TABLE `vehicles` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `property` VARCHAR(255) NOT NULL,
    `tag_number` VARCHAR(100),
    `plate_number` VARCHAR(100),
    `state` VARCHAR(50),
    `make` VARCHAR(100),
    `model` VARCHAR(100),
    `color` VARCHAR(50),
    `year` VARCHAR(10),
    `apt_number` VARCHAR(50),
    `owner_name` VARCHAR(255),
    `owner_phone` VARCHAR(50),
    `owner_email` VARCHAR(255),
    `reserved_space` VARCHAR(100),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`property`) REFERENCES `properties`(`name`) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX `idx_property` (`property`),
    INDEX `idx_tag_number` (`tag_number`),
    INDEX `idx_plate_number` (`plate_number`),
    INDEX `idx_owner_name` (`owner_name`),
    INDEX `idx_apt_number` (`apt_number`),
    
    FULLTEXT INDEX `ft_search` (`tag_number`, `plate_number`, `make`, `model`, `owner_name`, `apt_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columns (14 fields):**
1. `id`: Unique identifier (UUID)
2. `property`: Associated property name (FK to properties.name)
3. `tag_number`: Parking tag/permit number
4. `plate_number`: License plate number
5. `state`: State/province of registration
6. `make`: Vehicle manufacturer
7. `model`: Vehicle model
8. `color`: Vehicle color
9. `year`: Vehicle year
10. `apt_number`: Apartment/unit number
11. `owner_name`: Vehicle owner name
12. `owner_phone`: Owner phone number
13. `owner_email`: Owner email address
14. `reserved_space`: Reserved parking space identifier

**Indexes:**
- Individual indexes on searchable fields
- Full-text index for quick search functionality

#### Table: `audit_logs`

```sql
CREATE TABLE `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(36),
    `username` VARCHAR(255) NOT NULL,
    `action` VARCHAR(100) NOT NULL,
    `entity_type` VARCHAR(50) NOT NULL,
    `entity_id` VARCHAR(36),
    `details` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_username` (`username`),
    INDEX `idx_action` (`action`),
    INDEX `idx_entity_type` (`entity_type`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
```

**Columns:**
- `id`: Auto-incrementing identifier
- `user_id`: User who performed action (nullable if user deleted)
- `username`: Username at time of action
- `action`: Action performed (create, update, delete, login, etc.)
- `entity_type`: Type of entity (vehicle, property, user)
- `entity_id`: ID of affected entity
- `details`: JSON object with additional information
- `ip_address`: User's IP address
- `user_agent`: User's browser/client information
- `created_at`: Timestamp of action

**Logged Actions:**
- User login/logout
- Vehicle create/update/delete
- Property create/update/delete
- User create/update/delete
- CSV imports
- Failed login attempts

### Database Indexes Strategy

**Primary Indexes:**
- All primary keys automatically indexed
- Foreign keys indexed for join performance

**Search Optimization:**
- Full-text index on vehicles table for quick search
- Individual indexes on frequently searched fields

**Performance Considerations:**
- Use `EXPLAIN` to analyze query performance
- Monitor slow query log
- Add covering indexes as needed based on usage patterns

### Data Types Rationale

**VARCHAR(36) for UUIDs:**
- Standard UUID format: `xxxxxxxx-xxxx-xxxx-xxxx-xxxxxxxxxxxx`
- Portable across systems
- Human-readable in exports

**TIMESTAMP vs DATETIME:**
- Using TIMESTAMP for automatic timezone handling
- AUTO_UPDATE for `updated_at` columns

**JSON for audit details:**
- Flexible storage for varying audit information
- Native JSON functions in MySQL 8.0+

**ENUM for role:**
- Fixed set of values
- Database-level constraint
- Better performance than VARCHAR

---

## Authentication & Authorization

### User Roles

#### 1. Admin Role

**Capabilities:**
- Full system access
- User management (create, update, delete users)
- Property management (create, update, delete properties)
- Vehicle management for ALL properties
- Access to all reports and audit logs
- System configuration

**Access Level:** Unrestricted

**Default Admin Account:**
- Username: `admin`
- Password: `admin123` (MUST be changed on first login)

#### 2. User Role

**Capabilities:**
- Vehicle management for ASSIGNED properties only
- View properties they're assigned to
- Search vehicles within their properties
- CSV import/export for their properties
- View their own activity in audit logs

**Access Level:** Property-restricted

**Restrictions:**
- Cannot access other properties
- Cannot manage users
- Cannot create/delete properties
- Cannot view system-wide reports

#### 3. Operator Role

**Capabilities:**
- Read-only access to ALL properties
- Search all vehicle records
- View all property information
- Export vehicle data (read-only)

**Access Level:** Read-only, all properties

**Restrictions:**
- Cannot create, update, or delete ANY records
- Cannot access user management
- Cannot access admin functions
- Cannot import data
- No edit/delete buttons visible in UI

### Authentication Flow

**Login Process:**
1. User submits username and password
2. System validates credentials against database
3. Password verified using bcrypt
4. Session created on successful authentication
5. User data stored in session (id, username, role, assigned properties)
6. Redirect to dashboard/home page

**Session Management:**
- Session ID stored in secure HTTP-only cookie
- Session data stored in database
- 24-hour session timeout (configurable)
- Automatic session cleanup for expired sessions

**Logout Process:**
1. User clicks logout
2. Session destroyed in database
3. Session cookie cleared
4. Redirect to login page

**Security Measures:**
- Bcrypt password hashing (cost factor: 10)
- CSRF token validation on all POST/PUT/DELETE requests
- Rate limiting on login endpoint (5 attempts per 15 minutes)
- Password minimum length: 8 characters
- Session regeneration after login

### Authorization Middleware

**Implementation Requirements:**

```php
// Pseudocode for middleware

// 1. Require Authentication
AuthMiddleware:
    - Check if session exists
    - Verify user is logged in
    - If not, return 401 Unauthorized

// 2. Require Admin Role
AdminMiddleware:
    - Check if user.role === 'admin'
    - If not, return 403 Forbidden

// 3. Property Access Check
PropertyAccessMiddleware:
    - If user.role === 'admin': allow all
    - If user.role === 'operator': allow read-only
    - If user.role === 'user': check assigned properties
    - Filter queries by accessible properties

// 4. Read-Only Check
ReadOnlyMiddleware:
    - If user.role === 'operator': deny write operations
    - Return 403 Forbidden for POST/PUT/DELETE
```

### Route Protection

**Public Routes:**
- POST /api/login
- GET /api/health (optional health check)

**Authenticated Routes:**
- All other API routes require authentication
- Session must be valid

**Admin-Only Routes:**
- GET /api/admin/users
- POST /api/admin/users
- PATCH /api/admin/users/:id
- DELETE /api/admin/users/:id
- POST /api/properties (create)
- PATCH /api/properties/:id (update)
- DELETE /api/properties/:id (delete)

**Property-Filtered Routes:**
- All vehicle endpoints (filtered by user's assigned properties)
- GET /api/properties (filtered by assigned properties for users)

**Operator Restrictions:**
- All write operations (POST/PUT/DELETE) denied
- Only GET requests allowed

---

## Feature Specifications

### 1. Vehicle Management

#### Create Vehicle

**Endpoint:** POST /api/vehicles

**Required Fields:**
- property (must exist in database)

**Optional Fields:**
- tag_number, plate_number, state
- make, model, color, year
- apt_number, owner_name, owner_phone, owner_email
- reserved_space

**Validation:**
- property must exist
- property must be accessible to user
- All text fields: max 255 characters
- Email: valid email format
- Year: 4 digits (optional)

**Business Rules:**
- User must have write access to property
- Operators cannot create vehicles
- Duplicate tag/plate combinations allowed (different properties)
- Timestamps auto-generated

**Success Response:**
```json
{
  "id": "uuid",
  "property": "Sunset Apartments",
  "tag_number": "PKG001",
  "plate_number": "ABC123",
  "state": "CA",
  "make": "Toyota",
  "model": "Camry",
  "color": "Silver",
  "year": "2020",
  "apt_number": "101",
  "owner_name": "John Doe",
  "owner_phone": "555-0100",
  "owner_email": "john@example.com",
  "reserved_space": "A-15",
  "created_at": "2025-01-22T12:00:00Z",
  "updated_at": "2025-01-22T12:00:00Z"
}
```

**Audit Log:**
- Action: "create"
- Entity Type: "vehicle"
- Details: Full vehicle record

#### Update Vehicle

**Endpoint:** PATCH /api/vehicles/:id

**Allowed Updates:**
- Any of the 14 vehicle fields
- Partial updates allowed

**Validation:**
- Same as create
- Vehicle must exist
- User must have write access to vehicle's property

**Business Rules:**
- Cannot change property to one user doesn't have access to
- Operators cannot update
- Timestamps auto-updated

**Audit Log:**
- Action: "update"
- Details: Changed fields (old value → new value)

#### Delete Vehicle

**Endpoint:** DELETE /api/vehicles/:id

**Validation:**
- Vehicle must exist
- User must have write access to vehicle's property

**Business Rules:**
- Operators cannot delete
- Soft delete not required (hard delete)
- Cascade deletion handled by database

**Audit Log:**
- Action: "delete"
- Details: Full vehicle record before deletion

#### View Vehicle Details

**Endpoint:** GET /api/vehicles/:id

**Authorization:**
- User must have access to vehicle's property
- Admins: all vehicles
- Users: vehicles in assigned properties only
- Operators: all vehicles (read-only)

**Response:**
- Full vehicle record
- All 14 fields

### 2. Search Functionality

#### Quick Search

**Endpoint:** GET /api/vehicles/search?q={query}

**Search Behavior:**
- Searches across ALL text fields simultaneously
- Uses MySQL FULLTEXT search or LIKE operator
- Case-insensitive matching
- Partial matches allowed

**Searchable Fields:**
- tag_number, plate_number, state
- make, model, color, year
- apt_number, owner_name, owner_phone, owner_email
- reserved_space

**Example:** `?q=toyota` finds:
- Make: "Toyota"
- Model: "Toyota Camry"
- Color: "Toyota Red"
- Any field containing "toyota"

**Authorization:**
- Results filtered by user's accessible properties

#### Advanced Search

**Endpoint:** GET /api/vehicles/search?{field}={value}&{field2}={value2}...

**Supported Filters:**
- property (exact match)
- tagNumber (partial match)
- plateNumber (partial match)
- state (partial match)
- make (partial match)
- model (partial match)
- color (partial match)
- year (exact match)
- aptNumber (partial match)
- ownerName (partial match)
- ownerPhone (partial match)
- ownerEmail (partial match)
- reservedSpace (partial match)

**Search Logic:**
- Multiple filters: AND logic (all must match)
- Each filter: case-insensitive partial match
- Empty filter value: ignored

**Example:** `?make=toyota&year=2020&property=Sunset Apartments`

**Authorization:**
- Results filtered by accessible properties
- Property filter only shows accessible properties

**UI Behavior:**
- No results shown until search initiated
- "Ready to Search" message on empty state
- Result count shown when active search
- Clear filters option

### 3. Property Management

#### Create Property

**Endpoint:** POST /api/properties

**Required Fields:**
- name (unique)

**Optional Fields:**
- address
- contacts (array of up to 3 contact objects)

**Contact Object:**
```json
{
  "name": "John Doe",      // required
  "phone": "555-0100",     // optional
  "email": "john@example.com"  // optional
}
```

**Validation:**
- name: 1-255 characters, unique
- Maximum 3 contacts per property
- Contact name required if contact provided
- Email validation if provided

**Authorization:**
- Admin only

**Success Response:**
```json
{
  "id": "uuid",
  "name": "New Property",
  "address": "123 Main St",
  "contacts": [
    {
      "name": "John Doe",
      "phone": "555-0100",
      "email": "john@example.com"
    }
  ],
  "created_at": "2025-01-22T12:00:00Z",
  "updated_at": "2025-01-22T12:00:00Z"
}
```

**Audit Log:**
- Action: "create"
- Entity Type: "property"

#### Update Property

**Endpoint:** PATCH /api/properties/:id

**Updatable Fields:**
- name
- address
- contacts

**Validation:**
- Same as create
- If renaming: new name must be unique
- Cannot rename if property has vehicles (optional: allow with cascade update)

**Authorization:**
- Admin only

**Audit Log:**
- Action: "update"
- Details: Changed fields

#### Delete Property

**Endpoint:** DELETE /api/properties/:id

**Pre-Delete Checks:**
1. Count vehicles in property
2. If vehicles exist: return 400 error with count
3. Suggest: "This property has X vehicles. Delete them first."

**Alternative:** Cascade delete (if business allows)

**Authorization:**
- Admin only

**Audit Log:**
- Action: "delete"
- Details: Property details before deletion

#### List Properties

**Endpoint:** GET /api/properties

**Authorization Filtering:**
- Admins: all properties
- Users: only assigned properties
- Operators: all properties (read-only)

**Response:**
```json
[
  {
    "id": "uuid",
    "name": "Sunset Apartments",
    "address": "123 Sunset Blvd",
    "contacts": [...],
    "created_at": "...",
    "updated_at": "..."
  }
]
```

**Include in Response:**
- Vehicle count per property (optional)

### 4. User Management

#### Create User

**Endpoint:** POST /api/admin/users

**Required Fields:**
- username (unique, 3-50 characters)
- password (minimum 8 characters)
- role (admin/user/operator)

**Optional Fields:**
- assignedProperties (array of property names)

**Validation:**
- Username: alphanumeric, unique, 3-50 chars
- Password: minimum 8 characters
- Role: must be valid enum value
- Assigned properties: must exist in database

**Business Rules:**
- Only admins can create users
- Assigned properties required for "user" role
- Assigned properties optional for "operator" role
- Admins don't need assigned properties (access all)

**Authorization:**
- Admin only

**Success Response:**
```json
{
  "id": "uuid",
  "username": "jdoe",
  "role": "user",
  "assignedProperties": ["Sunset Apartments", "Harbor View"],
  "created_at": "2025-01-22T12:00:00Z"
}
```

**Note:** Password never returned in response

**Audit Log:**
- Action: "create"
- Entity Type: "user"
- Details: Username, role, assigned properties (NOT password)

#### Update User

**Endpoint:** PATCH /api/admin/users/:id

**Updatable Fields:**
- password (optional)
- role (optional)
- assignedProperties (optional)

**Validation:**
- Same as create
- Password: only if provided, minimum 8 characters
- Cannot change own role (prevent admin lockout)

**Business Rules:**
- Cannot update last admin to non-admin role
- Changing assigned properties affects access immediately

**Authorization:**
- Admin only

**Audit Log:**
- Action: "update"
- Details: Changed fields (NOT password)

#### Delete User

**Endpoint:** DELETE /api/admin/users/:id

**Pre-Delete Checks:**
1. Cannot delete yourself (prevent admin lockout)
2. Cannot delete last admin user

**Authorization:**
- Admin only

**Audit Log:**
- Action: "delete"
- Details: User details before deletion
- User ID set to NULL after deletion (for log retention)

#### List Users

**Endpoint:** GET /api/admin/users

**Response:**
```json
[
  {
    "id": "uuid",
    "username": "admin",
    "role": "admin",
    "assignedProperties": [],
    "created_at": "2025-01-22T12:00:00Z"
  }
]
```

**Authorization:**
- Admin only

**Note:** Passwords never returned

### 5. CSV Import/Export

#### Import Vehicles (CSV)

**Endpoint:** POST /api/vehicles/import

**Request:**
- Content-Type: multipart/form-data
- Field: "file" (CSV file)
- Max file size: 50MB

**CSV Format:**
```csv
property,tagNumber,plateNumber,state,make,model,color,year,aptNumber,ownerName,ownerPhone,ownerEmail,reservedSpace
Sunset Apartments,PKG001,ABC123,CA,Toyota,Camry,Silver,2020,101,John Doe,555-0100,john@example.com,A-15
```

**CSV Requirements:**
- First row: headers (case-sensitive)
- property field: required
- All other fields: optional
- UTF-8 encoding
- Comma-separated values
- Values with commas: wrap in double quotes

**Processing Logic:**
1. Parse CSV file
2. Validate each row
3. Check property access for each row
4. Import valid rows
5. Collect errors for invalid rows

**Validation Per Row:**
- property must exist in database
- User must have write access to property
- Field length limits enforced
- Email format validated

**Success Response:**
```json
{
  "success": 15,
  "failed": 2,
  "errors": [
    {
      "row": 3,
      "error": "Property 'Unknown Property' not found",
      "data": { "property": "Unknown Property", "..." }
    },
    {
      "row": 7,
      "error": "Invalid email format",
      "data": { "ownerEmail": "invalid-email", "..." }
    }
  ]
}
```

**Authorization:**
- Users: can only import to assigned properties
- Admins: can import to any property
- Operators: cannot import (no write access)

**Audit Log:**
- Action: "csv_import"
- Entity Type: "vehicle"
- Details: Success count, failure count, filename

#### Export Vehicles (CSV)

**Endpoint:** GET /api/vehicles/export

**Query Parameters:**
- Same as search parameters
- Exports current search results/filters

**Examples:**
- `/api/vehicles/export` - All accessible vehicles
- `/api/vehicles/export?property=Sunset Apartments` - One property
- `/api/vehicles/export?q=toyota` - Search results

**Response:**
- Content-Type: text/csv
- Content-Disposition: attachment; filename="vehicles_export_YYYYMMDD_HHMMSS.csv"
- Filename includes timestamp

**CSV Format:**
- Same as import format
- All 14 fields included
- Header row included
- UTF-8 encoding

**Authorization:**
- Filtered by user's accessible properties
- Users: only assigned properties
- Admins: all properties (or filtered)
- Operators: all properties (read-only access)

**Audit Log:**
- Action: "csv_export"
- Entity Type: "vehicle"
- Details: Filter parameters, record count

### 6. Audit Logging

#### View Audit Logs

**Endpoint:** GET /api/admin/audit-logs

**Query Parameters:**
- limit (default: 100, max: 1000)
- offset (pagination)
- username (filter)
- action (filter)
- entity_type (filter)
- startDate (filter by date range)
- endDate (filter by date range)

**Response:**
```json
{
  "logs": [
    {
      "id": 123,
      "username": "admin",
      "action": "create",
      "entity_type": "vehicle",
      "entity_id": "uuid",
      "details": { "property": "Sunset Apartments", "..." },
      "ip_address": "192.168.1.100",
      "created_at": "2025-01-22T12:00:00Z"
    }
  ],
  "total": 500,
  "limit": 100,
  "offset": 0
}
```

**Authorization:**
- Admin only for full logs
- Users: can see their own actions only

**Logged Actions:**
- login, logout, failed_login
- create, update, delete (vehicles, properties, users)
- csv_import, csv_export
- Any administrative action

### 7. Reporting Dashboard

**Endpoint:** GET /api/admin/reports/dashboard

**Statistics to Include:**

1. **Total Counts:**
   - Total vehicles
   - Total properties
   - Total users
   - Total vehicles added this month

2. **Vehicles by Property:**
   ```json
   [
     { "property": "Sunset Apartments", "count": 45 },
     { "property": "Harbor View", "count": 32 }
   ]
   ```

3. **Recent Activity:**
   - Last 10 audit log entries
   - Quick summary of changes

4. **Top Users by Activity:**
   - Users with most actions this month

**Authorization:**
- Admin only

**Caching:**
- Cache results for 5 minutes
- Invalidate on data changes

---

## API Endpoints

### Complete Endpoint List

#### Authentication
- `POST /api/login` - User login
- `POST /api/logout` - User logout
- `GET /api/user` - Get current user info

#### Vehicles
- `GET /api/vehicles/search` - Search vehicles (quick or advanced)
- `GET /api/vehicles/:id` - Get vehicle details
- `POST /api/vehicles` - Create vehicle
- `PATCH /api/vehicles/:id` - Update vehicle
- `DELETE /api/vehicles/:id` - Delete vehicle
- `POST /api/vehicles/import` - Import CSV
- `GET /api/vehicles/export` - Export CSV

#### Properties
- `GET /api/properties` - List properties (filtered by access)
- `POST /api/properties` - Create property (admin only)
- `PATCH /api/properties/:id` - Update property (admin only)
- `DELETE /api/properties/:id` - Delete property (admin only)

#### Users (Admin Only)
- `GET /api/admin/users` - List all users
- `POST /api/admin/users` - Create user
- `PATCH /api/admin/users/:id` - Update user
- `DELETE /api/admin/users/:id` - Delete user

#### Audit Logs (Admin Only)
- `GET /api/admin/audit-logs` - View audit logs

#### Reports (Admin Only)
- `GET /api/admin/reports/dashboard` - Dashboard statistics

### API Response Standards

#### Success Response Format

```json
{
  "data": { ... },
  "message": "Operation successful" // optional
}
```

#### Error Response Format

```json
{
  "error": "Error message",
  "code": "ERROR_CODE",
  "details": { ... } // optional
}
```

**HTTP Status Codes:**
- 200: Success
- 201: Created
- 400: Bad Request (validation error)
- 401: Unauthorized (not logged in)
- 403: Forbidden (insufficient permissions)
- 404: Not Found
- 422: Unprocessable Entity (validation failed)
- 500: Internal Server Error

#### Validation Error Format

```json
{
  "error": "Validation failed",
  "code": "VALIDATION_ERROR",
  "details": {
    "field_name": ["Error message 1", "Error message 2"]
  }
}
```

---

## User Interface Requirements

### Design System

**Theme:**
- Dark mode by default
- Professional, modern aesthetic
- Clean and functional

**Colors:**
- Primary: Modern blue tones
- Backgrounds: Dark grays
- Text: White/light grays with hierarchy
- Success: Green
- Error: Red
- Warning: Orange

**Typography:**
- Headers: Inter font family
- Data fields: Roboto Mono (monospaced for alignment)
- Sizes: Clear hierarchy (h1-h4, body, small)

**Components:**
- Shadcn UI component library
- Tailwind CSS utilities
- Radix UI primitives

### Layout Structure

**App Header:**
- App name: "ManageMyParking"
- Current user display
- Logout button
- Theme toggle (optional)

**Sidebar Navigation:**
- Home (Vehicle Search)
- Add Vehicle (hidden for operators)
- Properties (admin only)
- User Management (admin only)
- Reporting (admin only)

**Main Content Area:**
- Page header with title
- Navigation breadcrumbs
- Back/Home buttons on detail pages
- Content area

### Page Requirements

#### 1. Login Page

**Components:**
- ManageMyParking branding
- Username input field
- Password input field
- Login button
- Error message display
- "Remember me" checkbox (optional)

**Behavior:**
- Clear error messages
- Disable button during submission
- Auto-focus username field
- Enter key submits form

**Validation:**
- Required fields
- Min/max length
- Show validation errors

#### 2. Home/Search Page

**Initial State:**
- "Ready to Search" message
- No vehicles displayed
- Search box visible
- Property filter visible

**Components:**
- Quick search input
- Property dropdown filter (shows accessible properties)
- Advanced search toggle
- Advanced filters panel (14 fields)
- Active filter count badge
- Clear filters button
- Export CSV button
- Import CSV button (hidden for operators)
- Results count (when searching)
- Vehicle cards grid
- Loading skeletons

**Vehicle Card:**
- Display priority (top to bottom):
  1. Year, Color
  2. Make, Model
  3. Plate Number, State
  4. Tag Number
  5. Owner Name, Phone
  6. Owner Email
  7. Apartment Number
  8. Reserved Space
  9. Property name
- Edit button (hidden for operators)
- Delete button (hidden for operators)
- View details button

**Empty State:**
- "No vehicles found" message
- Suggestion to adjust filters
- "Add Your First Vehicle" button (hidden for operators)

**Search Behavior:**
- No results until search initiated
- Quick search: search on typing (debounced)
- Advanced search: search on filter change
- Clear filters returns to empty state

#### 3. Add/Edit Vehicle Page

**Form Layout:**
- Two-column responsive layout
- Logical field grouping:
  - Property & Tag Information
  - Vehicle Details (make, model, year, color)
  - License Information (plate, state)
  - Owner Information (name, phone, email)
  - Location Information (apt, reserved space)

**Components:**
- Property dropdown (required, shows accessible properties)
- All 14 vehicle fields as inputs
- Save button
- Cancel button
- Form validation
- Error messages
- Success toast notification
- Auto-dismiss toast after 2 seconds

**Validation:**
- Required: property
- Email format validation
- Max length enforcement
- Real-time validation feedback

**Behavior:**
- Pre-fill form when editing
- Disable save during submission
- Navigate to vehicle detail on success
- Show validation errors inline

**Accessibility:**
- Operators: redirect or show access denied

#### 4. Vehicle Detail Page

**Layout:**
- Back button
- Home button
- Vehicle information display (all 14 fields)
- Edit button (hidden for operators)
- Delete button (hidden for operators)

**Display Format:**
- Field labels with values
- Empty fields shown as "—" or "Not provided"
- Proper formatting (phone, email clickable)
- Timestamps displayed

**Actions:**
- Edit: Navigate to edit form
- Delete: Confirmation dialog → delete → navigate home
- Back: Return to previous page
- Home: Navigate to home/search

#### 5. Properties Page (Admin Only)

**Layout:**
- Page header with title
- Add Property button
- Property cards grid

**Property Card:**
- Property name
- Address (if provided)
- Contact information (up to 3 contacts)
- Vehicle count
- Edit button
- Delete button

**Add/Edit Property Dialog:**
- Name input (required)
- Address input (optional)
- Contact management:
  - Dynamic contact form (up to 3)
  - Add contact button
  - Remove contact button
  - Name (required per contact)
  - Phone (optional)
  - Email (optional)
- Save button
- Cancel button

**Delete Confirmation:**
- Warning if property has vehicles
- Show vehicle count
- Require confirmation
- Cannot delete if vehicles exist

#### 6. User Management Page (Admin Only)

**Layout:**
- Page header with title
- Add User button
- Users table

**Users Table:**
- Columns: Username, Role, Assigned Properties, Actions
- Sort by username
- Filter by role
- Edit button per row
- Delete button per row

**Add/Edit User Dialog:**
- Username input (required, disabled when editing)
- Password input (required for create, optional for edit)
- Role selector (Admin, User, Operator)
- Property assignment:
  - Multi-select dropdown
  - Conditional on role:
    - Admin: No properties needed
    - User: Required
    - Operator: Optional
- Save button
- Cancel button

**Delete Confirmation:**
- Cannot delete yourself
- Cannot delete last admin
- Confirmation required

#### 7. Reporting Page (Admin Only)

**Layout:**
- Page header with title
- Dashboard cards with statistics
- Charts/graphs

**Statistics Cards:**
- Total Vehicles
- Total Properties
- Total Users
- Vehicles Added This Month

**Vehicles by Property Chart:**
- Bar chart or pie chart
- Property names with counts
- Visual representation

**Recent Activity:**
- Last 10 audit log entries
- Action, User, Timestamp
- Link to full audit logs (if implemented)

### UI/UX Requirements

**Toast Notifications:**
- Success: Green toast, 2-second auto-dismiss
- Error: Red toast, requires manual dismiss
- Info: Blue toast, 3-second auto-dismiss
- Position: Top-right corner

**Loading States:**
- Skeleton loaders for data fetching
- Disabled buttons during operations
- Loading spinners for long operations

**Responsive Design:**
- Mobile-friendly (320px+)
- Tablet optimized (768px+)
- Desktop optimized (1024px+)
- Grid layouts collapse to single column on mobile

**Accessibility:**
- Keyboard navigation
- ARIA labels
- Focus indicators
- Screen reader support
- Semantic HTML

**Data Display:**
- Consistent field ordering across the app
- Monospaced font for vehicle data (alignment)
- Empty states for all lists
- Proper formatting (dates, phone numbers)

---

## Data Import/Export

### CSV Import Specifications

**File Requirements:**
- Format: CSV (Comma-Separated Values)
- Encoding: UTF-8
- Max file size: 50MB
- Max rows: 10,000 (configurable)

**CSV Structure:**

**Header Row (Required):**
```
property,tagNumber,plateNumber,state,make,model,color,year,aptNumber,ownerName,ownerPhone,ownerEmail,reservedSpace
```

**Data Rows:**
- property: Required (must exist in database)
- All other fields: Optional
- Empty cells allowed
- Values with commas: wrap in double quotes
- Line breaks in values: wrap in double quotes

**Example CSV:**
```csv
property,tagNumber,plateNumber,state,make,model,color,year,aptNumber,ownerName,ownerPhone,ownerEmail,reservedSpace
Sunset Apartments,PKG001,ABC123,CA,Toyota,Camry,Silver,2020,101,John Doe,555-0100,john@example.com,A-15
Harbor View,PKG002,XYZ789,CA,Honda,Accord,Blue,2019,205,"Smith, Jane",555-0200,jane@example.com,B-23
Mountain Ridge,PKG003,DEF456,CA,Ford,F-150,Black,2021,301,Bob Johnson,555-0300,bob@example.com,C-42
```

**Import Process:**

1. **File Upload:**
   - User selects CSV file
   - File size validated (max 50MB)
   - File parsed

2. **Validation:**
   - Header row validated (all required columns present)
   - Each row validated:
     - Property exists
     - User has access to property
     - Field lengths within limits
     - Email format valid
     - Required fields present

3. **Import Execution:**
   - Valid rows imported
   - Invalid rows collected with error messages
   - Transaction-based (all or nothing per row)

4. **Results Display:**
   - Success count
   - Failure count
   - Detailed error report:
     - Row number
     - Error message
     - Row data
   - Download errors as CSV (optional)

**Error Handling:**
- Continue on row errors (don't stop entire import)
- Collect all errors for reporting
- Provide actionable error messages

**Success Criteria:**
- At least one row successfully imported
- Error report generated for failures
- Audit log entry created
- UI refreshed with new data

### CSV Export Specifications

**File Generation:**
- Format: CSV (Comma-Separated Values)
- Encoding: UTF-8
- Filename: `vehicles_export_YYYYMMDD_HHMMSS.csv`
- All 14 fields included

**Export Options:**
- Export all accessible vehicles
- Export current search results
- Export filtered results
- Respects user's property access

**CSV Structure:**

**Header Row:**
```
property,tagNumber,plateNumber,state,make,model,color,year,aptNumber,ownerName,ownerPhone,ownerEmail,reservedSpace
```

**Data Formatting:**
- Empty fields: empty cell (not "null" or "N/A")
- Commas in values: wrap in double quotes
- Quotes in values: escape with double quotes
- Line breaks: preserve with quotes

**Download Behavior:**
- Browser download prompt
- Suggested filename with timestamp
- Content-Type: text/csv
- Content-Disposition: attachment

**Authorization:**
- Exports only accessible vehicles
- Admin: all vehicles (or filtered)
- User: assigned properties only
- Operator: all vehicles (read-only access)

---

## Security Requirements

### Password Security

**Hashing:**
- Algorithm: Bcrypt
- Cost factor: 10 (configurable)
- Salt: Auto-generated per password

**Password Policy:**
- Minimum length: 8 characters
- No maximum length (within reason, e.g., 255 chars)
- No complexity requirements (optional: add requirements)
- Password cannot be same as username

**Password Storage:**
- NEVER store plain text passwords
- NEVER log passwords
- NEVER return passwords in API responses
- Hash passwords before database storage

### Session Security

**Session Configuration:**
- HTTP-only cookies (prevent XSS)
- Secure flag (HTTPS only)
- SameSite: Lax or Strict
- Session timeout: 24 hours
- Sliding expiration (refresh on activity)

**Session Storage:**
- Database-backed sessions
- Session ID: Random, cryptographically secure
- Session cleanup: Automated for expired sessions

**Session Fixation Prevention:**
- Regenerate session ID on login
- Destroy old session on logout
- Clear all session data on logout

### CSRF Protection

**Implementation:**
- CSRF token for all state-changing requests
- Token embedded in forms
- Token validated server-side
- Token rotation after use (optional)

**Exclusions:**
- GET requests (should be idempotent anyway)
- Public endpoints

### SQL Injection Prevention

**Primary Defense:**
- Use parameterized queries ALWAYS
- NEVER concatenate user input into SQL
- Use ORM/query builder (Laravel Eloquent)

**Examples:**

**Bad (Vulnerable):**
```php
$sql = "SELECT * FROM vehicles WHERE plate_number = '" . $_GET['plate'] . "'";
```

**Good (Safe):**
```php
$vehicles = Vehicle::where('plate_number', $request->input('plate'))->get();
```

### XSS Prevention

**Output Encoding:**
- HTML encode all user input before display
- JavaScript escape for JS contexts
- Use templating engine auto-escaping (Blade)

**Content Security Policy:**
- Implement CSP headers (optional but recommended)
- Restrict inline scripts
- Whitelist script sources

### Input Validation

**Server-Side Validation:**
- ALWAYS validate on server (never trust client)
- Validate type, format, length, range
- Whitelist allowed characters
- Sanitize input (remove dangerous characters)

**Client-Side Validation:**
- Improves UX
- NOT a security measure
- Always backed by server validation

### File Upload Security

**CSV Upload:**
- Validate file extension (.csv only)
- Validate MIME type
- Validate file size (max 50MB)
- Parse safely (library, not eval)
- Limit row count (max 10,000)
- Scan for malicious content (optional)

**Storage:**
- Store in temporary directory
- Delete after processing
- Don't serve uploaded files directly

### Rate Limiting

**Login Endpoint:**
- Limit: 5 attempts per 15 minutes per IP
- Lockout: 15 minutes after 5 failures
- Clear counter on successful login

**Other Endpoints:**
- General limit: 100 requests per minute per user
- CSV import: 5 uploads per hour
- Adjust based on usage patterns

### Audit Logging

**What to Log:**
- All authentication events (login, logout, failed attempts)
- All data modifications (create, update, delete)
- Administrative actions
- CSV imports/exports
- Access to sensitive data

**What NOT to Log:**
- Passwords (plain or hashed)
- Session tokens
- Other sensitive credentials

**Log Retention:**
- Minimum: 90 days
- Recommended: 1 year
- Compliance: Follow regulatory requirements

### HTTPS Requirements

**Production Deployment:**
- HTTPS only (SSL/TLS certificate)
- Redirect HTTP to HTTPS
- HSTS header (Strict-Transport-Security)
- Valid certificate (Let's Encrypt or commercial)

**Development:**
- HTTP acceptable for localhost
- HTTPS recommended

### Error Handling

**Production Error Messages:**
- Generic messages to users
- Detailed logs server-side
- Never expose:
  - Stack traces
  - Database errors
  - File paths
  - System information

**Examples:**

**Bad:**
```
Error: SQLSTATE[23000]: Integrity constraint violation: 1062 Duplicate entry 'admin' for key 'users.username'
```

**Good:**
```
Username already exists. Please choose a different username.
```

### Additional Security Measures

**Headers:**
- X-Content-Type-Options: nosniff
- X-Frame-Options: SAMEORIGIN
- X-XSS-Protection: 1; mode=block
- Referrer-Policy: no-referrer-when-downgrade

**Database:**
- Principle of least privilege (app user permissions)
- No direct public access to database
- Regular backups
- Connection encryption (SSL/TLS)

**Dependencies:**
- Keep PHP and libraries updated
- Monitor security advisories
- Use Composer for dependency management
- Regular security audits

---

## Deployment Specifications

### Server Requirements

**Operating System:**
- Ubuntu 20.04 LTS or newer
- Debian 11 or newer
- CentOS 8 or newer
- Or equivalent Linux distribution

**Software Stack:**
- PHP 8.3 or newer
- PHP-FPM (FastCGI Process Manager)
- MySQL 8.0 or newer
- Nginx 1.20+ or Apache 2.4+
- Composer (PHP dependency manager)
- Node.js 18+ (for frontend build)
- Supervisor (process management, optional)

**Server Resources:**
- CPU: 2+ cores recommended
- RAM: 2GB minimum, 4GB recommended
- Disk: 10GB minimum free space
- Bandwidth: 100Mbps recommended

### Deployment Path

**Production URL:** https://2clv.com/jrk

**Configuration:**
- Base path: `/jrk`
- Application runs on internal port (e.g., 9000 for PHP-FPM)
- Nginx/Apache reverse proxy to PHP-FPM
- URL rewriting to strip `/jrk` prefix for app

### Directory Structure

```
/var/www/jrk/
├── app/                    # Laravel app directory
├── public/                 # Public web root
│   ├── index.php          # PHP entry point
│   └── assets/            # Built frontend assets
├── storage/               # Laravel storage
│   ├── logs/
│   └── framework/
├── database/
│   ├── migrations/        # Database migrations
│   └── seeders/          # Database seeders
├── resources/
│   └── js/               # React source code
├── .env                  # Environment configuration
├── composer.json         # PHP dependencies
└── package.json          # Node dependencies
```

### Environment Configuration

**File:** `.env`

```env
APP_NAME=ManageMyParking
APP_ENV=production
APP_DEBUG=false
APP_URL=https://2clv.com/jrk

# Database
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=managemyparking
DB_USERNAME=jrkadmin
DB_PASSWORD=SECURE_PASSWORD_HERE

# Session
SESSION_DRIVER=database
SESSION_LIFETIME=1440
SESSION_COOKIE=managemyparking_session
SESSION_SECURE_COOKIE=true

# Security
APP_KEY=base64:GENERATE_32_BYTE_KEY_HERE

# Limits
UPLOAD_MAX_FILESIZE=50M
POST_MAX_SIZE=50M
```

**Generate APP_KEY:**
```bash
php artisan key:generate
```

### Nginx Configuration

**File:** `/etc/nginx/sites-available/2clv-jrk`

```nginx
# Upstream to PHP-FPM
upstream php-fpm {
    server unix:/run/php/php8.3-fpm.sock;
}

server {
    listen 443 ssl http2;
    server_name 2clv.com www.2clv.com;
    
    # SSL Configuration
    ssl_certificate /etc/letsencrypt/live/2clv.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/2clv.com/privkey.pem;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers HIGH:!aNULL:!MD5;
    ssl_prefer_server_ciphers on;
    
    # Application at /jrk path
    location /jrk {
        alias /var/www/jrk/public;
        try_files $uri $uri/ @jrk;
        
        # PHP processing
        location ~ \.php$ {
            include fastcgi_params;
            fastcgi_pass php-fpm;
            fastcgi_param SCRIPT_FILENAME /var/www/jrk/public/index.php;
            fastcgi_param PATH_INFO $fastcgi_path_info;
        }
    }
    
    location @jrk {
        rewrite ^/jrk/(.*)$ /jrk/index.php?/$1 last;
    }
    
    # Static assets
    location ~* ^/jrk/assets/.*\.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
        alias /var/www/jrk/public;
        expires 30d;
        add_header Cache-Control "public, max-age=2592000";
    }
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
    
    # Upload size limit
    client_max_body_size 50M;
    
    # Compression
    gzip on;
    gzip_vary on;
    gzip_types text/plain text/css application/json application/javascript text/xml application/xml;
}
```

### PHP Configuration

**File:** `/etc/php/8.3/fpm/pool.d/jrk.conf`

```ini
[jrk]
user = www-data
group = www-data
listen = /run/php/php8.3-fpm.sock
listen.owner = www-data
listen.group = www-data
listen.mode = 0660

pm = dynamic
pm.max_children = 10
pm.start_servers = 2
pm.min_spare_servers = 1
pm.max_spare_servers = 3

php_value[upload_max_filesize] = 50M
php_value[post_max_size] = 50M
php_value[memory_limit] = 256M
php_value[max_execution_time] = 300
php_value[session.save_handler] = files
php_value[session.save_path] = /var/www/jrk/storage/framework/sessions
```

### Database Setup

```bash
# Create database
mysql -u root -p
CREATE DATABASE managemyparking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'jrkadmin'@'localhost' IDENTIFIED BY 'SECURE_PASSWORD';
GRANT ALL PRIVILEGES ON managemyparking.* TO 'jrkadmin'@'localhost';
FLUSH PRIVILEGES;
EXIT;

# Run migrations
cd /var/www/jrk
php artisan migrate

# Seed initial data
php artisan db:seed
```

### Deployment Process

**1. Upload Files:**
```bash
# From local machine
scp -r managemyparking-php/* user@2clv.com:/var/www/jrk/
```

**2. Install Dependencies:**
```bash
cd /var/www/jrk

# PHP dependencies
composer install --no-dev --optimize-autoloader

# Frontend dependencies
npm install

# Build frontend
npm run build
```

**3. Set Permissions:**
```bash
sudo chown -R www-data:www-data /var/www/jrk
sudo chmod -R 755 /var/www/jrk
sudo chmod -R 775 /var/www/jrk/storage
sudo chmod -R 775 /var/www/jrk/bootstrap/cache
```

**4. Configure Environment:**
```bash
cp .env.example .env
nano .env  # Edit configuration
php artisan key:generate
```

**5. Database Setup:**
```bash
php artisan migrate
php artisan db:seed
```

**6. Cache Optimization:**
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

**7. Restart Services:**
```bash
sudo systemctl restart php8.3-fpm
sudo systemctl reload nginx
```

### Backup Strategy

**Database Backups:**
```bash
# Create backup script
sudo nano /usr/local/bin/backup-managemyparking.sh

#!/bin/bash
BACKUP_DIR="/var/backups/managemyparking"
DATE=$(date +%Y%m%d_%H%M%S)
mkdir -p $BACKUP_DIR

# Database backup
mysqldump -u jrkadmin -p'PASSWORD' managemyparking | gzip > $BACKUP_DIR/db_$DATE.sql.gz

# Keep last 30 days
find $BACKUP_DIR -type f -mtime +30 -delete

# Make executable
sudo chmod +x /usr/local/bin/backup-managemyparking.sh

# Schedule with cron (daily at 2 AM)
sudo crontab -e
0 2 * * * /usr/local/bin/backup-managemyparking.sh
```

**Application Backups:**
- Code: Version control (Git)
- Uploads: Include storage directory in backups
- Configuration: Backup .env file securely

### Monitoring

**Application Logs:**
```bash
# Laravel logs
tail -f /var/www/jrk/storage/logs/laravel.log

# PHP-FPM logs
tail -f /var/log/php8.3-fpm.log

# Nginx logs
tail -f /var/log/nginx/access.log
tail -f /var/log/nginx/error.log
```

**Health Checks:**
- Database connectivity
- Disk space usage
- PHP-FPM status
- Session storage

### SSL Certificate

```bash
# Install Certbot
sudo apt install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot --nginx -d 2clv.com -d www.2clv.com

# Auto-renewal test
sudo certbot renew --dry-run
```

### Updating Application

```bash
# Pull latest code
cd /var/www/jrk
git pull origin main

# Update dependencies
composer install --no-dev
npm install
npm run build

# Run migrations
php artisan migrate

# Clear caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache

# Restart
sudo systemctl restart php8.3-fpm
```

---

## Testing Requirements

### Unit Testing

**Backend (PHP):**
- Framework: PHPUnit
- Coverage: Minimum 70%
- Test all models, controllers, services
- Mock external dependencies

**Frontend (React):**
- Framework: Vitest or Jest
- Coverage: Minimum 60%
- Test critical components
- Test custom hooks

### Integration Testing

**API Endpoints:**
- Test all endpoints
- Test authentication/authorization
- Test validation rules
- Test error handling
- Test edge cases

**Database:**
- Test migrations
- Test seeders
- Test relationships
- Test constraints

### End-to-End Testing

**Critical User Flows:**
1. Login → Create Vehicle → Search → View Details
2. Admin: Create User → Assign Properties
3. CSV Import → Verify Data → Export
4. Property Management: Create → Edit → Delete (with vehicles check)

**Tools:**
- Playwright (recommended)
- Cypress (alternative)

### Manual Testing Checklist

**Authentication:**
- [ ] Login with valid credentials
- [ ] Login with invalid credentials
- [ ] Logout
- [ ] Session timeout
- [ ] Access protected routes when logged out

**Vehicle Management:**
- [ ] Create vehicle (all roles except operator)
- [ ] Update vehicle
- [ ] Delete vehicle
- [ ] View vehicle details
- [ ] Property filtering works

**Search:**
- [ ] Quick search
- [ ] Advanced search (each field)
- [ ] Combined filters
- [ ] Empty results
- [ ] Property filter respects access

**CSV Operations:**
- [ ] Import valid CSV
- [ ] Import with errors (partial success)
- [ ] Export all vehicles
- [ ] Export filtered results

**User Management:**
- [ ] Create admin user
- [ ] Create user with properties
- [ ] Create operator
- [ ] Update user
- [ ] Delete user (with restrictions)

**Property Management:**
- [ ] Create property
- [ ] Add contacts (up to 3)
- [ ] Edit property
- [ ] Delete empty property
- [ ] Try delete property with vehicles (should fail)

**Role-Based Access:**
- [ ] Admin: Access all features
- [ ] User: Only assigned properties
- [ ] Operator: Read-only, all properties
- [ ] Operator: No edit/delete buttons

**UI/UX:**
- [ ] Responsive design (mobile, tablet, desktop)
- [ ] Toast notifications appear and auto-dismiss
- [ ] Loading states work
- [ ] Empty states display correctly
- [ ] Forms validate properly

### Performance Testing

**Load Testing:**
- 100 concurrent users
- Response time < 2 seconds
- Search with 10,000+ vehicles
- CSV import with 5,000 rows

**Database:**
- Query optimization
- Index effectiveness
- Full-text search performance

### Security Testing

**Penetration Testing:**
- SQL injection attempts
- XSS attempts
- CSRF validation
- Session fixation
- Authentication bypass attempts
- Authorization bypass attempts

**Vulnerability Scanning:**
- OWASP Top 10
- Dependency vulnerabilities
- Configuration issues

---

## Implementation Guidelines

### Recommended Technology Choices

**PHP Framework:** Laravel 11
- Built-in authentication
- Eloquent ORM
- Migration system
- Validation rules
- Job queues
- Session management
- CSRF protection

**Alternative:** Symfony 7 (if Laravel not preferred)

### Database Access Layer

**ORM (Laravel Eloquent):**
- Define models for each table
- Define relationships
- Use query builder
- Avoid raw SQL

**Example Model:**
```php
class Vehicle extends Model
{
    protected $fillable = [
        'property', 'tag_number', 'plate_number',
        // ... other fields
    ];
    
    public function propertyRelation()
    {
        return $this->belongsTo(Property::class, 'property', 'name');
    }
}
```

### Authentication Implementation

**Laravel Sanctum (Recommended):**
- API token authentication
- SPA authentication
- Stateful authentication with cookies

**Session-Based (Alternative):**
- Traditional PHP sessions
- Laravel session driver
- Database-backed sessions

### API Structure

**Controllers:**
- Thin controllers (business logic in services)
- Use form requests for validation
- Return consistent JSON responses
- Handle exceptions gracefully

**Services:**
- Business logic layer
- Reusable code
- Testable units

**Repositories (Optional):**
- Data access abstraction
- Easier testing
- Cleaner controllers

### Frontend Integration

**API Communication:**
- Axios or Fetch API
- TanStack Query for caching
- CSRF token in headers
- Session cookie handling

**Build Process:**
- Vite for frontend bundling
- Laravel Mix (alternative)
- Output to public/assets

**Routing:**
- Laravel serves React app
- React Router for client-side routing
- Fallback to index.html for SPA

### Code Quality

**PHP:**
- PSR-12 coding standard
- PHPStan level 5+
- PHP CS Fixer for formatting

**JavaScript:**
- ESLint with React rules
- Prettier for formatting
- TypeScript (recommended)

### Version Control

**Git Strategy:**
- Main branch: production-ready
- Develop branch: integration
- Feature branches for new work
- Pull requests for code review

**Commit Messages:**
- Conventional commits format
- Clear, descriptive messages

### Documentation

**Code Documentation:**
- PHPDoc for all public methods
- JSDoc for complex functions
- README with setup instructions

**API Documentation:**
- OpenAPI/Swagger spec
- Postman collection
- API reference guide

---

## Initial Data Requirements

### Seed Data

**Admin User:**
- Username: `admin`
- Password: `admin123` (hash before storing)
- Role: `admin`
- Assigned Properties: [] (access all)

**Sample Properties:**
1. Sunset Apartments
   - Address: 123 Sunset Boulevard, Los Angeles, CA 90001
   - Contact: Manager Office, 555-0100, sunset@example.com

2. Harbor View Complex
   - Address: 456 Harbor Drive, San Diego, CA 92101
   - Contact: Front Desk, 555-0200, harbor@example.com

3. Mountain Ridge
   - Address: 789 Mountain Road, Denver, CO 80201
   - Contact: Admin Office, 555-0300, mountain@example.com

**Sample Vehicles:** (Optional)
- 2-3 vehicles per property for testing
- Cover various field combinations

### Database Seeder

**File:** `database/seeders/DatabaseSeeder.php`

```php
public function run()
{
    // Create admin user
    User::create([
        'username' => 'admin',
        'password' => bcrypt('admin123'),
        'role' => 'admin',
    ]);
    
    // Create properties
    $properties = [
        ['name' => 'Sunset Apartments', 'address' => '123 Sunset Boulevard, Los Angeles, CA 90001'],
        ['name' => 'Harbor View Complex', 'address' => '456 Harbor Drive, San Diego, CA 92101'],
        ['name' => 'Mountain Ridge', 'address' => '789 Mountain Road, Denver, CO 80201'],
    ];
    
    foreach ($properties as $property) {
        Property::create($property);
    }
    
    // Create property contacts (example for first property)
    PropertyContact::create([
        'property_id' => Property::where('name', 'Sunset Apartments')->first()->id,
        'name' => 'Manager Office',
        'phone' => '555-0100',
        'email' => 'sunset@example.com',
        'position' => 0,
    ]);
}
```

---

## Success Criteria

### Functional Requirements Met

- [ ] All 14 vehicle fields tracked
- [ ] Three-tier role system (Admin, User, Operator)
- [ ] Property-based access control
- [ ] Quick and advanced search
- [ ] CSV import/export
- [ ] User management (admin only)
- [ ] Property management with contacts
- [ ] Audit logging
- [ ] Reporting dashboard
- [ ] Session-based authentication

### Non-Functional Requirements Met

- [ ] Response time < 2 seconds for searches
- [ ] Handles 1000+ vehicle records
- [ ] Handles 100+ concurrent users
- [ ] 99% uptime SLA
- [ ] Mobile-responsive UI
- [ ] WCAG 2.1 Level AA accessibility (optional)

### Security Requirements Met

- [ ] Bcrypt password hashing
- [ ] SQL injection prevention
- [ ] XSS prevention
- [ ] CSRF protection
- [ ] HTTPS only in production
- [ ] Rate limiting
- [ ] Audit logging
- [ ] Secure session management

### Deployment Requirements Met

- [ ] Deployed to https://2clv.com/jrk
- [ ] Database backups configured
- [ ] Monitoring in place
- [ ] SSL certificate valid
- [ ] Error logging configured
- [ ] Production optimizations applied

---

## Project Deliverables

### Source Code
- Complete PHP backend application
- Complete React frontend application
- Database migrations and seeders
- Configuration files

### Documentation
- API documentation
- Database schema documentation
- Deployment guide
- User manual (optional)
- Developer setup guide

### Testing
- Unit tests (70%+ coverage for backend)
- Integration tests
- E2E test suite
- Manual testing checklist

### Deployment Assets
- Nginx configuration
- PHP-FPM configuration
- Environment file template
- Backup scripts
- Update scripts

---

## Appendix

### Glossary

- **Admin**: User role with full system access
- **User**: User role with property-specific access
- **Operator**: User role with read-only access to all properties
- **Property**: A physical location (apartment complex, parking facility, etc.)
- **Vehicle**: A record representing a parked vehicle
- **Audit Log**: A record of system actions
- **CSV**: Comma-Separated Values file format
- **CRUD**: Create, Read, Update, Delete operations

### Abbreviations

- API: Application Programming Interface
- CSRF: Cross-Site Request Forgery
- CSV: Comma-Separated Values
- FK: Foreign Key
- HTTP: HyperText Transfer Protocol
- HTTPS: HTTP Secure
- JSON: JavaScript Object Notation
- ORM: Object-Relational Mapping
- PK: Primary Key
- RBAC: Role-Based Access Control
- REST: Representational State Transfer
- SPA: Single Page Application
- SQL: Structured Query Language
- SSL: Secure Sockets Layer
- TLS: Transport Layer Security
- UI: User Interface
- UX: User Experience
- UUID: Universally Unique Identifier
- XSS: Cross-Site Scripting

### Reference Links

**PHP & Laravel:**
- Laravel Documentation: https://laravel.com/docs
- PHP Manual: https://www.php.net/manual/
- Composer: https://getcomposer.org/

**Database:**
- MySQL Documentation: https://dev.mysql.com/doc/
- Database Design Best Practices

**Frontend:**
- React Documentation: https://react.dev/
- Tailwind CSS: https://tailwindcss.com/
- Shadcn UI: https://ui.shadcn.com/

**Security:**
- OWASP Top 10: https://owasp.org/www-project-top-ten/
- Laravel Security Best Practices

---

## Revision History

| Version | Date | Author | Changes |
|---------|------|--------|---------|
| 2.0 | 2025-01-22 | System | Complete PHP/MySQL specification based on Node.js implementation |
| 1.0 | 2024-10-22 | System | Initial Node.js/PostgreSQL implementation |

---

**END OF SPECIFICATION**

This document serves as the complete blueprint for implementing ManageMyParking with PHP 8.3 and MySQL. All requirements, features, and specifications from the original Node.js/PostgreSQL application have been translated and adapted for the PHP/MySQL stack.
