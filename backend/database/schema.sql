-- ManageMyParking Database Schema
-- MySQL 8.0+ with InnoDB Engine
-- Character Set: UTF-8mb4

-- Create Database
CREATE DATABASE IF NOT EXISTS managemyparking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE managemyparking;

-- Table: users
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

-- Table: properties
CREATE TABLE `properties` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `name` VARCHAR(255) UNIQUE NOT NULL,
    `address` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX `idx_name` (`name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Table: user_assigned_properties
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

-- Table: property_contacts
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

-- Table: vehicles (14 fields)
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

-- Table: audit_logs
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

-- Table: sessions
CREATE TABLE `sessions` (
    `id` VARCHAR(255) PRIMARY KEY,
    `user_id` VARCHAR(36),
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `payload` LONGTEXT NOT NULL,
    `last_activity` INT NOT NULL,
    
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_last_activity` (`last_activity`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Initial Data

-- Admin User (password: admin123)
INSERT INTO `users` (`id`, `username`, `password`, `role`) VALUES
(UUID(), 'admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- Sample Properties
INSERT INTO `properties` (`id`, `name`, `address`) VALUES
(UUID(), 'Sunset Apartments', '123 Sunset Boulevard, Los Angeles, CA 90001'),
(UUID(), 'Harbor View Complex', '456 Harbor Drive, San Diego, CA 92101'),
(UUID(), 'Mountain Ridge', '789 Mountain Road, Denver, CO 80201');

-- Sample Property Contacts
INSERT INTO `property_contacts` (`property_id`, `name`, `phone`, `email`, `position`)
SELECT p.id, 'Manager Office', '555-0100', 'sunset@example.com', 0
FROM `properties` p WHERE p.name = 'Sunset Apartments';

INSERT INTO `property_contacts` (`property_id`, `name`, `phone`, `email`, `position`)
SELECT p.id, 'Front Desk', '555-0200', 'harbor@example.com', 0
FROM `properties` p WHERE p.name = 'Harbor View Complex';

INSERT INTO `property_contacts` (`property_id`, `name`, `phone`, `email`, `position`)
SELECT p.id, 'Admin Office', '555-0300', 'mountain@example.com', 0
FROM `properties` p WHERE p.name = 'Mountain Ridge';
