-- MyParkingManager Database Installation
-- MySQL 8.0+ Required
-- Run this file in phpMyAdmin or MySQL command line

-- Create Database (if needed)
CREATE DATABASE IF NOT EXISTS myparkingmanager CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE myparkingmanager;

-- Drop existing tables (if reinstalling)
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS property_contacts;
DROP TABLE IF EXISTS user_assigned_properties;
DROP TABLE IF EXISTS user_permissions;
DROP TABLE IF EXISTS login_attempts;
DROP TABLE IF EXISTS properties;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS sessions;

-- Users Table
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed',
    role ENUM('admin', 'user', 'operator') NOT NULL DEFAULT 'user',
    email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Permissions Table (Permission Matrix)
CREATE TABLE user_permissions (
    id VARCHAR(36) PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    module ENUM('vehicles', 'users', 'properties', 'violations', 'database') NOT NULL,
    can_view BOOLEAN NOT NULL DEFAULT FALSE,
    can_edit BOOLEAN NOT NULL DEFAULT FALSE,
    can_create_delete BOOLEAN NOT NULL DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_module (user_id, module),
    INDEX idx_user_id (user_id),
    INDEX idx_module (module)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login Attempts Table (Security)
CREATE TABLE login_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(255) NOT NULL,
    ip_address VARCHAR(45) NOT NULL,
    attempt_count INT NOT NULL DEFAULT 1,
    locked_until TIMESTAMP NULL DEFAULT NULL,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_ip_address (ip_address),
    INDEX idx_locked_until (locked_until),
    UNIQUE KEY unique_username_ip (username, ip_address)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Properties Table
CREATE TABLE properties (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    address TEXT,
    custom_ticket_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Assigned Properties (Many-to-Many)
CREATE TABLE user_assigned_properties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    property_id VARCHAR(36) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_property (user_id, property_id),
    INDEX idx_user_id (user_id),
    INDEX idx_property_id (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Property Contacts (Up to 3 per property)
CREATE TABLE property_contacts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id VARCHAR(36) NOT NULL,
    name VARCHAR(255) NOT NULL,
    phone VARCHAR(50),
    email VARCHAR(255),
    position TINYINT UNSIGNED NOT NULL COMMENT '0=first, 1=second, 2=third',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    UNIQUE KEY unique_property_position (property_id, position),
    INDEX idx_property_id (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vehicles Table (14 fields)
CREATE TABLE vehicles (
    id VARCHAR(36) PRIMARY KEY,
    property VARCHAR(255) NOT NULL,
    tag_number VARCHAR(100),
    plate_number VARCHAR(100),
    state VARCHAR(50),
    make VARCHAR(100),
    model VARCHAR(100),
    color VARCHAR(50),
    year VARCHAR(10),
    apt_number VARCHAR(50),
    owner_name VARCHAR(255),
    owner_phone VARCHAR(50),
    owner_email VARCHAR(255),
    reserved_space VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property) REFERENCES properties(name) ON DELETE RESTRICT ON UPDATE CASCADE,
    INDEX idx_property (property),
    INDEX idx_tag_number (tag_number),
    INDEX idx_plate_number (plate_number),
    INDEX idx_owner_name (owner_name),
    INDEX idx_apt_number (apt_number),
    FULLTEXT INDEX ft_search (tag_number, plate_number, make, model, owner_name, apt_number)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Violations Reference Table
CREATE TABLE violations (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_order TINYINT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Violation Tickets Table
CREATE TABLE violation_tickets (
    id VARCHAR(36) PRIMARY KEY,
    vehicle_id VARCHAR(36) NOT NULL,
    property VARCHAR(255) NOT NULL,
    issued_by_user_id VARCHAR(36) NOT NULL,
    issued_by_username VARCHAR(255) NOT NULL,
    issued_at DATETIME NOT NULL,
    custom_note TEXT,
    vehicle_year VARCHAR(10),
    vehicle_color VARCHAR(50),
    vehicle_make VARCHAR(100),
    vehicle_model VARCHAR(100),
    tag_number VARCHAR(100),
    plate_number VARCHAR(100),
    property_name VARCHAR(255),
    property_address TEXT,
    property_contact_name VARCHAR(255),
    property_contact_phone VARCHAR(50),
    property_contact_email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE,
    FOREIGN KEY (issued_by_user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_property (property),
    INDEX idx_issued_by (issued_by_user_id),
    INDEX idx_issued_at (issued_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Violation Ticket Items (Join Table)
CREATE TABLE violation_ticket_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(36) NOT NULL,
    violation_id VARCHAR(36),
    description TEXT NOT NULL,
    display_order TINYINT UNSIGNED DEFAULT 0,
    FOREIGN KEY (ticket_id) REFERENCES violation_tickets(id) ON DELETE CASCADE,
    FOREIGN KEY (violation_id) REFERENCES violations(id) ON DELETE SET NULL,
    INDEX idx_ticket_id (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Logs Table
CREATE TABLE audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36),
    username VARCHAR(255) NOT NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id VARCHAR(36),
    details JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_username (username),
    INDEX idx_action (action),
    INDEX idx_entity_type (entity_type),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions Table (Optional - for database-backed sessions)
CREATE TABLE sessions (
    id VARCHAR(255) PRIMARY KEY,
    user_id VARCHAR(36),
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload LONGTEXT NOT NULL,
    last_activity INT NOT NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SEED DATA
-- ============================================

-- SECURITY NOTE: No default admin user is created for security reasons.
-- Please create your first admin user through the application's setup process
-- or use the following template with a STRONG, UNIQUE password:
--
-- INSERT INTO users (id, username, password, role) VALUES
-- (UUID(), 'your_admin_username', '$2y$10$YourBcryptHashHere', 'admin');
--
-- INSERT INTO user_permissions (id, user_id, module, can_view, can_edit, can_create_delete) VALUES
-- (UUID(), 'user_id_from_above', 'vehicles', TRUE, TRUE, TRUE),
-- (UUID(), 'user_id_from_above', 'users', TRUE, TRUE, TRUE),
-- (UUID(), 'user_id_from_above', 'properties', TRUE, TRUE, TRUE),
-- (UUID(), 'user_id_from_above', 'violations', TRUE, TRUE, TRUE),
-- (UUID(), 'user_id_from_above', 'database', TRUE, TRUE, TRUE);

-- Insert Sample Properties
INSERT INTO properties (id, name, address) VALUES
('660e8400-e29b-41d4-a716-446655440001', 'Sunset Apartments', '123 Sunset Boulevard, Los Angeles, CA 90001'),
('660e8400-e29b-41d4-a716-446655440002', 'Harbor View Complex', '456 Harbor Drive, San Diego, CA 92101'),
('660e8400-e29b-41d4-a716-446655440003', 'Mountain Ridge', '789 Mountain Road, Denver, CO 80201');

-- Insert Property Contacts
INSERT INTO property_contacts (property_id, name, phone, email, position) VALUES
('660e8400-e29b-41d4-a716-446655440001', 'Manager Office', '555-0100', 'sunset@example.com', 0),
('660e8400-e29b-41d4-a716-446655440002', 'Front Desk', '555-0200', 'harbor@example.com', 0),
('660e8400-e29b-41d4-a716-446655440003', 'Admin Office', '555-0300', 'mountain@example.com', 0);

-- Insert Sample Vehicles
INSERT INTO vehicles (id, property, tag_number, plate_number, state, make, model, color, year, apt_number, owner_name, owner_phone, owner_email, reserved_space) VALUES
('770e8400-e29b-41d4-a716-446655440001', 'Sunset Apartments', 'PKG001', 'ABC123', 'CA', 'Toyota', 'Camry', 'Silver', '2020', '101', 'John Doe', '555-0111', 'john@example.com', 'A-15'),
('770e8400-e29b-41d4-a716-446655440002', 'Harbor View Complex', 'PKG002', 'XYZ789', 'CA', 'Honda', 'Accord', 'Black', '2021', '205', 'Jane Smith', '555-0222', 'jane@example.com', 'B-22'),
('770e8400-e29b-41d4-a716-446655440003', 'Mountain Ridge', 'PKG003', 'DEF456', 'CO', 'Ford', 'F-150', 'Blue', '2019', '304', 'Bob Johnson', '555-0333', 'bob@example.com', 'C-10');

-- Insert Default Violations
INSERT INTO violations (id, name, display_order) VALUES
('880e8400-e29b-41d4-a716-446655440001', 'Expired Parking Permit', 1),
('880e8400-e29b-41d4-a716-446655440002', 'No Parking Permit Displayed', 2),
('880e8400-e29b-41d4-a716-446655440003', 'Parked in Reserved Space', 3),
('880e8400-e29b-41d4-a716-446655440004', 'Parked in Fire Lane', 4),
('880e8400-e29b-41d4-a716-446655440005', 'Parked in Handicapped Space Without Permit', 5),
('880e8400-e29b-41d4-a716-446655440006', 'Blocking Dumpster/Loading Zone', 6),
('880e8400-e29b-41d4-a716-446655440007', 'Double Parked', 7),
('880e8400-e29b-41d4-a716-446655440008', 'Parked Over Line/Taking Multiple Spaces', 8),
('880e8400-e29b-41d4-a716-446655440009', 'Abandoned Vehicle', 9),
('880e8400-e29b-41d4-a716-446655440010', 'Commercial Vehicle in Residential Area', 10);

-- Printer Settings Table
CREATE TABLE printer_settings (
    id VARCHAR(36) PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert Default Printer Settings
INSERT INTO printer_settings (id, setting_key, setting_value) VALUES
(UUID(), 'ticket_width', '2.5'),
(UUID(), 'ticket_height', '6'),
(UUID(), 'ticket_unit', 'in'),
(UUID(), 'logo_top', NULL),
(UUID(), 'logo_bottom', NULL),
(UUID(), 'logo_top_enabled', 'false'),
(UUID(), 'logo_bottom_enabled', 'false');

-- ============================================
-- INSTALLATION COMPLETE
-- ============================================

-- Verify installation
SELECT 'Installation Complete!' AS status;
SELECT COUNT(*) AS user_count FROM users;
SELECT COUNT(*) AS property_count FROM properties;
SELECT COUNT(*) AS vehicle_count FROM vehicles;
