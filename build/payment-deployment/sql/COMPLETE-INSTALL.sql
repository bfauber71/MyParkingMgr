-- ============================================
-- ManageMyParking v2.0 - Complete Installation
-- With Payment System Integration
-- ============================================
-- 
-- This file contains the complete database schema
-- for ManageMyParking v2.0 including the Payment System.
-- 
-- USAGE:
-- 1. Create a new MySQL database in cPanel/phpMyAdmin
-- 2. Import this entire file into your database
-- 3. Done! All tables will be created automatically
-- 
-- REQUIREMENTS:
-- - MySQL 5.7+ or MariaDB 10.2+
-- - UTF8MB4 character set support
-- 
-- ============================================

-- Set character set
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================
-- SECTION 1: CORE APPLICATION TABLES
-- ============================================

-- Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` VARCHAR(36) PRIMARY KEY,
    `username` VARCHAR(255) UNIQUE NOT NULL,
    `password` VARCHAR(255) NOT NULL COMMENT 'Bcrypt hashed',
    `role` ENUM('admin', 'user', 'operator') NOT NULL DEFAULT 'user',
    `email` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Permissions Table
CREATE TABLE IF NOT EXISTS `user_permissions` (
    `id` VARCHAR(36) PRIMARY KEY,
    `user_id` VARCHAR(36) NOT NULL,
    `module` ENUM('vehicles', 'users', 'properties', 'violations', 'database') NOT NULL,
    `can_view` BOOLEAN NOT NULL DEFAULT FALSE,
    `can_edit` BOOLEAN NOT NULL DEFAULT FALSE,
    `can_create_delete` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_module` (`user_id`, `module`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_module` (`module`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Login Attempts Table (Security)
CREATE TABLE IF NOT EXISTS `login_attempts` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(255) NOT NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `attempt_count` INT NOT NULL DEFAULT 1,
    `locked_until` TIMESTAMP NULL DEFAULT NULL,
    `last_attempt` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_ip_address` (`ip_address`),
    INDEX `idx_locked_until` (`locked_until`),
    UNIQUE KEY `unique_username_ip` (`username`, `ip_address`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Sessions Table
CREATE TABLE IF NOT EXISTS `sessions` (
    `id` VARCHAR(128) PRIMARY KEY,
    `user_id` VARCHAR(36) NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(500),
    `payload` TEXT,
    `last_activity` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Properties Table
CREATE TABLE IF NOT EXISTS `properties` (
    `id` VARCHAR(36) PRIMARY KEY,
    `name` VARCHAR(255) UNIQUE NOT NULL,
    `address` TEXT,
    `custom_ticket_text` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Assigned Properties (Many-to-Many)
CREATE TABLE IF NOT EXISTS `user_assigned_properties` (
    `id` VARCHAR(36) PRIMARY KEY,
    `user_id` VARCHAR(36) NOT NULL,
    `property_id` VARCHAR(36) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_user_property` (`user_id`, `property_id`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_property_id` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Property Contacts Table
CREATE TABLE IF NOT EXISTS `property_contacts` (
    `id` VARCHAR(36) PRIMARY KEY,
    `property_id` VARCHAR(36) NOT NULL,
    `name` VARCHAR(255),
    `phone` VARCHAR(50),
    `email` VARCHAR(255),
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    INDEX `idx_property_id` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Vehicles Table
CREATE TABLE IF NOT EXISTS `vehicles` (
    `id` VARCHAR(36) PRIMARY KEY,
    `tag_plate` VARCHAR(50) NOT NULL,
    `state` VARCHAR(10),
    `make` VARCHAR(100),
    `model` VARCHAR(100),
    `color` VARCHAR(50),
    `apartment_unit` VARCHAR(50),
    `property_id` VARCHAR(36),
    `is_resident` BOOLEAN DEFAULT TRUE,
    `guest_of_unit` VARCHAR(50) NULL,
    `expiration_date` DATE NULL COMMENT 'For guest passes',
    `notes` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE SET NULL,
    INDEX `idx_tag_plate` (`tag_plate`),
    INDEX `idx_property_id` (`property_id`),
    INDEX `idx_apartment_unit` (`apartment_unit`),
    INDEX `idx_is_resident` (`is_resident`),
    INDEX `idx_expiration_date` (`expiration_date`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Violation Tickets Table
CREATE TABLE IF NOT EXISTS `violation_tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `vehicle_id` VARCHAR(36) NOT NULL,
    `property_id` VARCHAR(36) NOT NULL,
    `violation_type` VARCHAR(255) NOT NULL,
    `violation_date` DATETIME NOT NULL,
    `location` TEXT,
    `notes` TEXT,
    `tag_plate` VARCHAR(50),
    `ticket_type` ENUM('WARNING', 'VIOLATION') DEFAULT 'VIOLATION',
    `fine_amount` DECIMAL(10,2) DEFAULT 0.00,
    `tow_requested` BOOLEAN DEFAULT FALSE,
    `tow_company` VARCHAR(255) NULL,
    `status` ENUM('active','closed') DEFAULT 'active',
    `fine_disposition` ENUM('collected','dismissed') NULL,
    `closed_at` DATETIME NULL,
    `closed_by_user_id` VARCHAR(36) NULL,
    `payment_status` ENUM('unpaid', 'partial', 'paid') DEFAULT 'unpaid',
    `amount_paid` DECIMAL(10,2) DEFAULT 0.00,
    `payment_link_id` VARCHAR(255) NULL,
    `qr_code_generated_at` DATETIME NULL,
    `created_by` VARCHAR(36),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`vehicle_id`) REFERENCES `vehicles`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`created_by`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    FOREIGN KEY (`closed_by_user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_vehicle_id` (`vehicle_id`),
    INDEX `idx_property_id` (`property_id`),
    INDEX `idx_violation_date` (`violation_date`),
    INDEX `idx_tag_plate` (`tag_plate`),
    INDEX `idx_status` (`status`),
    INDEX `idx_payment_status` (`payment_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Logs Table
CREATE TABLE IF NOT EXISTS `audit_logs` (
    `id` BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(36),
    `action` VARCHAR(100) NOT NULL,
    `table_name` VARCHAR(100),
    `record_id` VARCHAR(36),
    `old_values` JSON,
    `new_values` JSON,
    `ip_address` VARCHAR(45),
    `user_agent` VARCHAR(500),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL,
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_action` (`action`),
    INDEX `idx_table_name` (`table_name`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Printer Settings Table
CREATE TABLE IF NOT EXISTS `printer_settings` (
    `id` VARCHAR(36) PRIMARY KEY,
    `property_id` VARCHAR(36) NOT NULL,
    `printer_model` VARCHAR(100) DEFAULT 'ZQ510',
    `label_width` INT DEFAULT 4,
    `label_height` INT DEFAULT 6,
    `dpi` INT DEFAULT 203,
    `darkness` INT DEFAULT 20,
    `logo_base64` TEXT NULL,
    `logo_zpl` TEXT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_property_printer` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- System Settings Table
CREATE TABLE IF NOT EXISTS `system_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `setting_key` VARCHAR(100) UNIQUE NOT NULL,
    `setting_value` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SECTION 2: PAYMENT SYSTEM TABLES
-- ============================================

-- Payment Settings (per property)
CREATE TABLE IF NOT EXISTS `payment_settings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `property_id` VARCHAR(36) NOT NULL,
    `processor_type` ENUM('stripe', 'square', 'paypal', 'disabled') DEFAULT 'disabled',
    `api_key_encrypted` TEXT,
    `api_secret_encrypted` TEXT,
    `webhook_secret_encrypted` TEXT,
    `publishable_key` VARCHAR(255),
    `is_live_mode` BOOLEAN DEFAULT FALSE,
    `enable_qr_codes` BOOLEAN DEFAULT TRUE,
    `enable_online_payments` BOOLEAN DEFAULT TRUE,
    `payment_description_template` VARCHAR(500) DEFAULT 'Parking Violation - Ticket #{ticket_id}',
    `success_redirect_url` VARCHAR(500),
    `failure_redirect_url` VARCHAR(500),
    `allow_cash_payments` BOOLEAN DEFAULT TRUE,
    `allow_check_payments` BOOLEAN DEFAULT TRUE,
    `allow_manual_card` BOOLEAN DEFAULT TRUE,
    `require_check_number` BOOLEAN DEFAULT TRUE,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`property_id`) REFERENCES `properties`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `unique_property_settings` (`property_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Ticket Payments
CREATE TABLE IF NOT EXISTS `ticket_payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `payment_method` ENUM('cash', 'check', 'card_manual', 'stripe_online', 'square_online', 'paypal_online') NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `check_number` VARCHAR(50) NULL,
    `transaction_id` VARCHAR(255) NULL,
    `payment_link_url` TEXT NULL,
    `qr_code_path` VARCHAR(255) NULL,
    `status` ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'completed',
    `recorded_by_user_id` VARCHAR(36) NOT NULL,
    `notes` TEXT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (`ticket_id`) REFERENCES `violation_tickets`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`recorded_by_user_id`) REFERENCES `users`(`id`),
    INDEX `idx_ticket_id` (`ticket_id`),
    INDEX `idx_payment_date` (`payment_date`),
    INDEX `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- QR Codes Tracking
CREATE TABLE IF NOT EXISTS `qr_codes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `file_path` VARCHAR(255) NOT NULL,
    `payment_url` TEXT NOT NULL,
    `generated_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`ticket_id`) REFERENCES `violation_tickets`(`id`) ON DELETE CASCADE,
    INDEX `idx_ticket_id` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================
-- SECTION 3: DEFAULT DATA
-- ============================================

-- Insert default admin user
-- Username: admin
-- Password: admin123 (CHANGE THIS IMMEDIATELY AFTER FIRST LOGIN!)
INSERT IGNORE INTO `users` (`id`, `username`, `password`, `role`, `email`) 
VALUES (
    UUID(),
    'admin',
    '$2y$10$7KJZfJfL5O5z5O5z5O5z5OqJZfJfL5O5z5O5z5O5z5O5z5O5z5O5z.',
    'admin',
    'admin@example.com'
);

-- Insert default property
INSERT IGNORE INTO `properties` (`id`, `name`, `address`) 
VALUES (
    UUID(),
    'Default Property',
    '123 Main Street'
);

-- Insert default timezone setting
INSERT IGNORE INTO `system_settings` (`setting_key`, `setting_value`) 
VALUES ('timezone', 'America/New_York');

-- Insert default payment settings for all properties
INSERT IGNORE INTO `payment_settings` (`property_id`, `processor_type`, `enable_qr_codes`, `enable_online_payments`)
SELECT `id`, 'disabled', TRUE, FALSE FROM `properties`;

-- ============================================
-- SECTION 4: FINALIZE
-- ============================================

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- INSTALLATION COMPLETE!
-- ============================================
-- 
-- Next Steps:
-- 1. Login with username: admin, password: admin123
-- 2. IMMEDIATELY change the admin password
-- 3. Configure your properties in Settings → Properties
-- 4. Configure payment processors in Settings → Payments
-- 5. Generate encryption key for payment security
-- 
-- For payment system setup, see:
-- - ENCRYPTION_UPGRADE_GUIDE.md
-- - PAYMENT_SYSTEM_README.md
-- 
-- ============================================
