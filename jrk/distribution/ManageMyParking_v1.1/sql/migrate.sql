-- ManageMyParking Database Migration Script
-- This script safely updates existing databases to the latest schema
-- Safe to run multiple times - checks for existence before making changes
-- Run this in phpMyAdmin or MySQL command line on your existing database

-- Make sure you're using the correct database
USE managemyparking;

-- ============================================
-- CREATE MISSING TABLES
-- ============================================

-- Create violations table if it doesn't exist
CREATE TABLE IF NOT EXISTS violations (
    id VARCHAR(36) PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    display_order TINYINT UNSIGNED DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_is_active (is_active),
    INDEX idx_display_order (display_order)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create violation_tickets table if it doesn't exist
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
    property_name VARCHAR(255),
    property_address TEXT,
    property_contact_name VARCHAR(255),
    property_contact_phone VARCHAR(50),
    property_contact_email VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_vehicle_id (vehicle_id),
    INDEX idx_property (property),
    INDEX idx_issued_by (issued_by_user_id),
    INDEX idx_issued_at (issued_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create violation_ticket_items table if it doesn't exist
CREATE TABLE IF NOT EXISTS violation_ticket_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(36) NOT NULL,
    violation_id VARCHAR(36),
    description TEXT NOT NULL,
    display_order TINYINT UNSIGNED DEFAULT 0,
    INDEX idx_ticket_id (ticket_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create sessions table if it doesn't exist
CREATE TABLE IF NOT EXISTS sessions (
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
-- ADD MISSING COLUMNS TO EXISTING TABLES
-- ============================================

-- Add columns to users table if missing
-- Note: MySQL doesn't support IF NOT EXISTS for columns in older versions
-- This uses a stored procedure approach for compatibility

DELIMITER $$

-- Procedure to add column if it doesn't exist
DROP PROCEDURE IF EXISTS add_column_if_not_exists$$
CREATE PROCEDURE add_column_if_not_exists(
    IN tableName VARCHAR(128),
    IN columnName VARCHAR(128),
    IN columnDefinition VARCHAR(512)
)
BEGIN
    DECLARE column_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO column_exists
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = tableName
        AND COLUMN_NAME = columnName;
    
    IF column_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD COLUMN `', columnName, '` ', columnDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- ============================================
-- VERIFY AND ADD MISSING INDEXES
-- ============================================

-- Add indexes to violation_tickets if missing
DELIMITER $$

DROP PROCEDURE IF EXISTS add_index_if_not_exists$$
CREATE PROCEDURE add_index_if_not_exists(
    IN tableName VARCHAR(128),
    IN indexName VARCHAR(128),
    IN indexDefinition VARCHAR(512)
)
BEGIN
    DECLARE index_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO index_exists
    FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = tableName
        AND INDEX_NAME = indexName;
    
    IF index_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD INDEX `', indexName, '` ', indexDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- ============================================
-- ADD FOREIGN KEYS IF MISSING
-- ============================================

DELIMITER $$

DROP PROCEDURE IF EXISTS add_foreign_key_if_not_exists$$
CREATE PROCEDURE add_foreign_key_if_not_exists(
    IN tableName VARCHAR(128),
    IN constraintName VARCHAR(128),
    IN foreignKeyDefinition VARCHAR(512)
)
BEGIN
    DECLARE fk_exists INT DEFAULT 0;
    
    SELECT COUNT(*) INTO fk_exists
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE TABLE_SCHEMA = DATABASE()
        AND TABLE_NAME = tableName
        AND CONSTRAINT_NAME = constraintName
        AND CONSTRAINT_TYPE = 'FOREIGN KEY';
    
    IF fk_exists = 0 THEN
        SET @sql = CONCAT('ALTER TABLE `', tableName, '` ADD CONSTRAINT `', constraintName, '` ', foreignKeyDefinition);
        PREPARE stmt FROM @sql;
        EXECUTE stmt;
        DEALLOCATE PREPARE stmt;
    END IF;
END$$

DELIMITER ;

-- Add foreign keys to violation_tickets
CALL add_foreign_key_if_not_exists(
    'violation_tickets',
    'fk_vt_vehicle_id',
    'FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE'
);

CALL add_foreign_key_if_not_exists(
    'violation_tickets',
    'fk_vt_issued_by_user_id',
    'FOREIGN KEY (issued_by_user_id) REFERENCES users(id) ON DELETE CASCADE'
);

-- Add foreign keys to violation_ticket_items
CALL add_foreign_key_if_not_exists(
    'violation_ticket_items',
    'fk_vti_ticket_id',
    'FOREIGN KEY (ticket_id) REFERENCES violation_tickets(id) ON DELETE CASCADE'
);

CALL add_foreign_key_if_not_exists(
    'violation_ticket_items',
    'fk_vti_violation_id',
    'FOREIGN KEY (violation_id) REFERENCES violations(id) ON DELETE SET NULL'
);

-- ============================================
-- SEED DEFAULT VIOLATIONS IF TABLE IS EMPTY
-- ============================================

-- Check if violations table is empty and add default violations
INSERT INTO violations (id, name, display_order)
SELECT * FROM (
    SELECT '880e8400-e29b-41d4-a716-446655440001' AS id, 'Expired Parking Permit' AS name, 1 AS display_order
    UNION ALL SELECT '880e8400-e29b-41d4-a716-446655440002', 'No Parking Permit Displayed', 2
    UNION ALL SELECT '880e8400-e29b-41d4-a716-446655440003', 'Parked in Reserved Space', 3
    UNION ALL SELECT '880e8400-e29b-41d4-a716-446655440004', 'Parked in Fire Lane', 4
    UNION ALL SELECT '880e8400-e29b-41d4-a716-446655440005', 'Parked in Handicapped Space Without Permit', 5
    UNION ALL SELECT '880e8400-e29b-41d4-a716-446655440006', 'Blocking Dumpster/Loading Zone', 6
    UNION ALL SELECT '880e8400-e29b-41d4-a716-446655440007', 'Double Parked', 7
    UNION ALL SELECT '880e8400-e29b-41d4-a716-446655440008', 'Parked Over Line/Taking Multiple Spaces', 8
    UNION ALL SELECT '880e8400-e29b-41d4-a716-446655440009', 'Abandoned Vehicle', 9
    UNION ALL SELECT '880e8400-e29b-41d4-a716-446655440010', 'Commercial Vehicle in Residential Area', 10
) AS tmp
WHERE NOT EXISTS (SELECT 1 FROM violations LIMIT 1);

-- ============================================
-- CLEANUP PROCEDURES
-- ============================================

DROP PROCEDURE IF EXISTS add_column_if_not_exists;
DROP PROCEDURE IF EXISTS add_index_if_not_exists;
DROP PROCEDURE IF EXISTS add_foreign_key_if_not_exists;

-- ============================================
-- VERIFICATION
-- ============================================

SELECT 'Migration Complete!' AS status;
SELECT COUNT(*) AS violation_count FROM violations;
SELECT COUNT(*) AS violation_ticket_count FROM violation_tickets;

-- Show current table structure
SELECT 
    TABLE_NAME,
    TABLE_ROWS,
    ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS 'Size_MB'
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;
