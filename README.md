# ManageMyParking - Vehicle & Property Management System

**Version:** 2.0 (PHP 8.3 + MySQL Implementation)  
**Deployment Target:** https://2clv.com/jrk

## Overview

ManageMyParking is a comprehensive web-based application for tracking vehicles, managing parking records, and maintaining resident information across multiple properties. Built with PHP 8.3, MySQL 8.0, and React 18.

### Key Features

- **14-Field Vehicle Tracking**: Comprehensive vehicle information including tag number, plate, owner details, and more
- **Multi-Property Support**: Manage unlimited properties with independent operations
- **Role-Based Access Control**: Three-tier system (Admin, User, Operator)
- **Advanced Search**: Full-text search across all vehicle fields
- **CSV Import/Export**: Bulk data operations with error reporting
- **Audit Logging**: Complete tracking of all user actions and system events
- **Property Management**: Up to 3 contacts per property with full contact management

## Technology Stack

### Backend
- **PHP 8.3+** with Laravel 11 framework
- **MySQL 8.0+** with InnoDB storage engine
- **Session-based authentication** with bcrypt password hashing
- **RESTful API** with comprehensive validation

### Frontend
- **React 18+** with TypeScript
- **Vite 6+** for build tooling
- **Tailwind CSS 4** for styling
- **Shadcn UI** components with Radix UI primitives
- **TanStack Query v5** for server state management
- **Wouter** for client-side routing

## Project Structure

```
.
├── backend/                    # Laravel PHP backend
│   ├── app/
│   │   ├── Http/
│   │   │   ├── Controllers/   # API controllers
│   │   │   └── Middleware/    # Authorization middleware
│   │   ├── Models/            # Eloquent models
│   │   └── Services/          # Business logic
│   ├── config/                # Configuration files
│   ├── database/
│   │   ├── migrations/        # Database schema migrations
│   │   └── seeders/           # Database seeders
│   ├── public/                # Public web root
│   ├── routes/                # API routes
│   └── composer.json          # PHP dependencies
│
├── frontend/                   # React frontend
│   ├── src/
│   │   ├── components/        # React components
│   │   ├── pages/             # Page components
│   │   ├── lib/               # Utilities
│   │   └── api/               # API client
│   ├── public/                # Static assets
│   ├── package.json           # Node dependencies
│   └── vite.config.ts         # Vite configuration
│
└── DEPLOYMENT.md              # Deployment guide
```

## Quick Start (Development)

### Prerequisites

- PHP 8.3 or higher
- Composer
- MySQL 8.0 or higher
- Node.js 18 or higher
- npm or pnpm

### Backend Setup

1. **Install PHP dependencies:**
   ```bash
   cd backend
   composer install
   ```

2. **Configure environment:**
   ```bash
   cp .env.example .env
   nano .env  # Edit database credentials and other settings
   php artisan key:generate
   ```

3. **Set up database:**
   ```bash
   # Create database
   mysql -u root -p
   CREATE DATABASE managemyparking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'jrkadmin'@'localhost' IDENTIFIED BY 'YOUR_PASSWORD';
   GRANT ALL PRIVILEGES ON managemyparking.* TO 'jrkadmin'@'localhost';
   FLUSH PRIVILEGES;
   EXIT;

   # Run migrations and seeders
   php artisan migrate
   php artisan db:seed
   ```

4. **Start PHP development server:**
   ```bash
   php artisan serve --host=0.0.0.0 --port=8000
   ```

### Frontend Setup

1. **Install Node dependencies:**
   ```bash
   cd frontend
   npm install
   ```

2. **Start development server:**
   ```bash
   npm run dev
   ```

   Frontend will be available at http://localhost:5000

3. **Build for production:**
   ```bash
   npm run build
   ```

   Production files will be output to `backend/public/assets`

## Default Credentials

**Admin Account:**
- Username: `admin`
- Password: `admin123`

⚠️ **IMPORTANT:** Change this password immediately after first login in production!

## User Roles

### Admin
- Full system access
- User and property management
- Access to all vehicles across all properties
- System configuration and reporting

### User
- Vehicle management for assigned properties only
- CSV import/export for assigned properties
- Search within assigned properties
- View own activity logs

### Operator
- Read-only access to all properties
- Search all vehicle records
- Export vehicle data
- Cannot create, update, or delete any records

## API Endpoints

### Authentication
- `POST /api/login` - User login
- `POST /api/logout` - User logout
- `GET /api/user` - Get current user info

### Vehicles
- `GET /api/vehicles/search` - Search vehicles
- `GET /api/vehicles/:id` - Get vehicle details
- `POST /api/vehicles` - Create vehicle
- `PATCH /api/vehicles/:id` - Update vehicle
- `DELETE /api/vehicles/:id` - Delete vehicle
- `POST /api/vehicles/import` - Import CSV
- `GET /api/vehicles/export` - Export CSV

### Properties (Admin Only)
- `GET /api/properties` - List properties
- `POST /api/properties` - Create property
- `PATCH /api/properties/:id` - Update property
- `DELETE /api/properties/:id` - Delete property

### Users (Admin Only)
- `GET /api/admin/users` - List users
- `POST /api/admin/users` - Create user
- `PATCH /api/admin/users/:id` - Update user
- `DELETE /api/admin/users/:id` - Delete user

## Database Schema

### Tables
- `users` - User accounts and authentication
- `user_assigned_properties` - User-property assignments
- `properties` - Property information
- `property_contacts` - Up to 3 contacts per property
- `vehicles` - Vehicle records (14 fields)
- `audit_logs` - System activity tracking
- `sessions` - Session storage

All tables use UUID primary keys except for junction tables and audit logs.

## CSV Import/Export

### CSV Format
```csv
property,tagNumber,plateNumber,state,make,model,color,year,aptNumber,ownerName,ownerPhone,ownerEmail,reservedSpace
Sunset Apartments,PKG001,ABC123,CA,Toyota,Camry,Silver,2020,101,John Doe,555-0100,john@example.com,A-15
```

- **Property field is required** and must exist in the database
- All other fields are optional
- Maximum file size: 50MB
- Maximum rows: 10,000
- UTF-8 encoding required

## Security Features

- **Bcrypt password hashing** (cost factor: 10)
- **Session-based authentication** with HTTP-only cookies
- **CSRF protection** on all state-changing requests
- **SQL injection prevention** via parameterized queries
- **XSS prevention** via output encoding
- **Rate limiting** on login endpoint
- **Comprehensive audit logging**
- **Role-based authorization** middleware

## Production Deployment

See [DEPLOYMENT.md](./DEPLOYMENT.md) for complete production deployment instructions including:
- Server requirements and configuration
- Nginx/Apache setup
- SSL certificate installation
- Database optimization
- Caching strategies
- Backup procedures
- Monitoring setup

## Development Notes

This project is designed for deployment on traditional PHP hosting with MySQL database. It includes:

- **Laravel 11** for robust backend framework
- **Eloquent ORM** for database operations
- **Database migrations** for schema management
- **Seeders** for initial data
- **Modern React** with TypeScript and Tailwind CSS
- **Shadcn UI** for beautiful, accessible components

## License

MIT License

## Support

For issues or questions regarding deployment, refer to the comprehensive documentation in the `attached_assets/PROJECT-REQUIREMENTS-SPECIFICATION_1761156515536.md` file.
