-- ============================================
-- COMPLETE v2.0 Migration Script
-- ============================================
-- Migrates ANY version of ManageMyParking to v2.0
-- IDEMPOTENT: Safe to run multiple times
-- Run this in phpMyAdmin SQL tab
-- ============================================

-- 1. VEHICLES TABLE MIGRATION
-- ============================================

-- Add missing columns if they don't exist
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'plate_number') > 0,
    'SELECT 1',
    'ALTER TABLE vehicles ADD COLUMN plate_number VARCHAR(100) AFTER tag_plate'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'state') > 0,
    'SELECT 1',
    'ALTER TABLE vehicles ADD COLUMN state VARCHAR(50) AFTER plate_number'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'year') > 0,
    'SELECT 1',
    'ALTER TABLE vehicles ADD COLUMN year VARCHAR(10) AFTER color'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'owner_name') > 0,
    'SELECT 1',
    'ALTER TABLE vehicles ADD COLUMN owner_name VARCHAR(255) AFTER year'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'owner_phone') > 0,
    'SELECT 1',
    'ALTER TABLE vehicles ADD COLUMN owner_phone VARCHAR(50) AFTER owner_name'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'owner_email') > 0,
    'SELECT 1',
    'ALTER TABLE vehicles ADD COLUMN owner_email VARCHAR(255) AFTER owner_phone'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'reserved_space') > 0,
    'SELECT 1',
    'ALTER TABLE vehicles ADD COLUMN reserved_space VARCHAR(100) AFTER owner_email'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'guest') > 0,
    'SELECT 1',
    'ALTER TABLE vehicles ADD COLUMN guest TINYINT(1) DEFAULT 0 AFTER is_resident'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'property') > 0,
    'SELECT 1',
    'ALTER TABLE vehicles ADD COLUMN property VARCHAR(255) AFTER id'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Populate property column from property_id if empty
UPDATE vehicles v
INNER JOIN properties p ON v.property_id = p.id
SET v.property = p.name
WHERE v.property IS NULL OR v.property = '';

-- Rename columns to v2.0 names (MySQL doesn't error if column doesn't exist in CHANGE)
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'tag_plate') > 0,
    'ALTER TABLE vehicles CHANGE COLUMN tag_plate tag_number VARCHAR(100)',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'apartment_unit') > 0,
    'ALTER TABLE vehicles CHANGE COLUMN apartment_unit apt_number VARCHAR(50)',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'is_resident') > 0,
    'ALTER TABLE vehicles CHANGE COLUMN is_resident resident TINYINT(1)',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'vehicles' AND column_name = 'guest_of_unit') > 0,
    'ALTER TABLE vehicles CHANGE COLUMN guest_of_unit guest_of VARCHAR(50)',
    'SELECT 1'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add indexes for v2.0
CREATE INDEX IF NOT EXISTS idx_tag_number ON vehicles(tag_number);
CREATE INDEX IF NOT EXISTS idx_plate_number ON vehicles(plate_number);
CREATE INDEX IF NOT EXISTS idx_property ON vehicles(property);
CREATE INDEX IF NOT EXISTS idx_expiration ON vehicles(expiration_date);

-- 2. VIOLATIONS TABLE MIGRATION
-- ============================================

CREATE TABLE IF NOT EXISTS violations (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    fine_amount DECIMAL(10,2) DEFAULT NULL,
    tow_deadline_hours INT DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_order TINYINT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add fine_amount and tow_deadline_hours if missing
SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'violations' AND column_name = 'fine_amount') > 0,
    'SELECT 1',
    'ALTER TABLE violations ADD COLUMN fine_amount DECIMAL(10,2) DEFAULT NULL AFTER name'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'violations' AND column_name = 'tow_deadline_hours') > 0,
    'SELECT 1',
    'ALTER TABLE violations ADD COLUMN tow_deadline_hours INT DEFAULT NULL AFTER fine_amount'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Insert default violations if table is empty
INSERT IGNORE INTO violations (id, name, fine_amount, tow_deadline_hours, is_active, display_order) VALUES
(UUID(), 'No Parking', 50.00, 48, TRUE, 1),
(UUID(), 'Expired Tags/Registration', 75.00, 72, TRUE, 2),
(UUID(), 'Unauthorized Parking', 100.00, 24, TRUE, 3),
(UUID(), 'Blocking Traffic/Fire Lane', 150.00, 12, TRUE, 4),
(UUID(), 'Reserved Space Violation', 50.00, 48, TRUE, 5),
(UUID(), 'Abandoned Vehicle', 200.00, 48, TRUE, 6);

-- 3. VIOLATION TICKETS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS violation_tickets (
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
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_property (property),
    INDEX idx_issued_at (issued_at),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. VIOLATION TICKET ITEMS TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS violation_ticket_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(36) NOT NULL,
    violation_id VARCHAR(36),
    description TEXT NOT NULL,
    display_order TINYINT UNSIGNED DEFAULT 0,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_violation_id (violation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. PROPERTY CONTACTS - ADD POSITION COLUMN
-- ============================================

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'property_contacts' AND column_name = 'position') > 0,
    'SELECT 1',
    'ALTER TABLE property_contacts ADD COLUMN position TINYINT UNSIGNED NOT NULL DEFAULT 0 AFTER email'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

CREATE INDEX IF NOT EXISTS idx_property_position ON property_contacts(property_id, position);

-- 6. PRINTER SETTINGS - ENSURE KEY-VALUE STRUCTURE
-- ============================================

DROP TABLE IF EXISTS printer_settings_old;
CREATE TABLE IF NOT EXISTS printer_settings_old LIKE printer_settings;
INSERT IGNORE INTO printer_settings_old SELECT * FROM printer_settings;

DROP TABLE IF EXISTS printer_settings;

CREATE TABLE printer_settings (
    id VARCHAR(36) PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO printer_settings (id, setting_key, setting_value) VALUES
(UUID(), 'ticket_width', '2.5'),
(UUID(), 'ticket_height', '6'),
(UUID(), 'ticket_unit', 'in'),
(UUID(), 'logo_top', NULL),
(UUID(), 'logo_bottom', NULL),
(UUID(), 'logo_top_enabled', 'false'),
(UUID(), 'logo_bottom_enabled', 'false'),
(UUID(), 'timezone', 'America/New_York');

-- 7. USER ASSIGNED PROPERTIES TABLE
-- ============================================

CREATE TABLE IF NOT EXISTS user_assigned_properties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id VARCHAR(36) NOT NULL,
    property_id VARCHAR(36) NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_property (user_id, property_id),
    INDEX idx_user_id (user_id),
    INDEX idx_property_id (property_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. ENSURE USERS TABLE HAS EMAIL COLUMN
-- ============================================

SET @s = (SELECT IF(
    (SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS 
     WHERE table_name = 'users' AND column_name = 'email') > 0,
    'SELECT 1',
    'ALTER TABLE users ADD COLUMN email VARCHAR(255) AFTER role'
));
PREPARE stmt FROM @s;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- ============================================
-- MIGRATION COMPLETE
-- ============================================

SELECT 'v2.0 Migration Complete!' AS status;
SELECT 'Run DESCRIBE vehicles; to verify vehicles table schema' AS next_step;
