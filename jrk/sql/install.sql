-- ManageMyParking Database Installation
-- MySQL 8.0+ Required
-- Run this file in phpMyAdmin or MySQL command line

-- Create Database (if needed)
CREATE DATABASE IF NOT EXISTS managemyparking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE managemyparking;

-- Drop existing tables (if reinstalling)
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS vehicles;
DROP TABLE IF EXISTS property_contacts;
DROP TABLE IF EXISTS user_assigned_properties;
DROP TABLE IF EXISTS properties;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS sessions;

-- Users Table
CREATE TABLE users (
    id VARCHAR(36) PRIMARY KEY,
    username VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed',
    role ENUM('admin', 'user', 'operator') NOT NULL DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Properties Table
CREATE TABLE properties (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) UNIQUE NOT NULL,
    address TEXT,
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

-- Insert Admin User (password: admin123)
INSERT INTO users (id, username, password, role) VALUES
('550e8400-e29b-41d4-a716-446655440000', 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

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

-- ============================================
-- INSTALLATION COMPLETE
-- ============================================

-- Verify installation
SELECT 'Installation Complete!' AS status;
SELECT COUNT(*) AS user_count FROM users;
SELECT COUNT(*) AS property_count FROM properties;
SELECT COUNT(*) AS vehicle_count FROM vehicles;
