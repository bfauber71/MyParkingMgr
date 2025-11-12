-- MyParkingManager License System Migration
-- MySQL 5.7+ / MariaDB 10.2+ Compatible Version (No JSON, No UUID functions)

-- Create license instances table
CREATE TABLE IF NOT EXISTS license_instances (
    id VARCHAR(36) PRIMARY KEY,
    install_id VARCHAR(36) UNIQUE NOT NULL COMMENT 'Unique installation identifier',
    installed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    trial_expires_at TIMESTAMP NOT NULL COMMENT '30 days from installation',
    license_key_hash VARCHAR(255) UNIQUE NULL COMMENT 'Hashed license key',
    license_key_prefix VARCHAR(10) NULL COMMENT 'First 10 chars for display',
    status ENUM('trial', 'expired', 'licensed') DEFAULT 'trial',
    activated_at TIMESTAMP NULL COMMENT 'When license was activated',
    last_validated_at TIMESTAMP NULL,
    customer_email VARCHAR(255) NULL,
    metadata TEXT NULL COMMENT 'Additional license data',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_install_id (install_id),
    INDEX idx_status (status),
    INDEX idx_trial_expires (trial_expires_at),
    INDEX idx_license_hash (license_key_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- License validation attempts (for security monitoring)
CREATE TABLE IF NOT EXISTS license_attempts (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    install_id VARCHAR(36) NOT NULL,
    attempted_key VARCHAR(20) NOT NULL COMMENT 'First 20 chars only for security',
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    success BOOLEAN DEFAULT FALSE,
    error_message VARCHAR(255) NULL,
    attempted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_install_id (install_id),
    INDEX idx_attempted_at (attempted_at),
    INDEX idx_success (success)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- License audit log for tracking changes
CREATE TABLE IF NOT EXISTS license_audit (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    install_id VARCHAR(36) NOT NULL,
    action VARCHAR(100) NOT NULL COMMENT 'trial_started, license_activated, expired, reactivated',
    old_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NULL,
    user_id VARCHAR(36) NULL,
    details TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_install_id (install_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add can_manage_license column to users table (if it doesn't exist)
-- Using a stored procedure for compatibility
DELIMITER $$

DROP PROCEDURE IF EXISTS add_license_column$$
CREATE PROCEDURE add_license_column()
BEGIN
    DECLARE column_count INT;
    
    SELECT COUNT(*) INTO column_count
    FROM INFORMATION_SCHEMA.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME = 'users'
    AND COLUMN_NAME = 'can_manage_license';
    
    IF column_count = 0 THEN
        ALTER TABLE users ADD COLUMN can_manage_license BOOLEAN DEFAULT FALSE COMMENT 'Admin-only license management';
    END IF;
END$$

DELIMITER ;

CALL add_license_column();
DROP PROCEDURE add_license_column;

-- Update admin permissions for license management
UPDATE users 
SET can_manage_license = TRUE 
WHERE role = 'admin';

-- Initialize license data for existing installations
-- Only insert if no license record exists
INSERT INTO license_instances (
    id, 
    install_id, 
    installed_at, 
    trial_expires_at, 
    status,
    metadata
)
SELECT 
    CONCAT(
        SUBSTRING(MD5(RAND()) FROM 1 FOR 8), '-',
        SUBSTRING(MD5(RAND()) FROM 1 FOR 4), '-',
        SUBSTRING(MD5(RAND()) FROM 1 FOR 4), '-',
        SUBSTRING(MD5(RAND()) FROM 1 FOR 4), '-',
        SUBSTRING(MD5(RAND()) FROM 1 FOR 12)
    ) AS id,
    CONCAT(
        SUBSTRING(MD5(CONCAT(NOW(), RAND())) FROM 1 FOR 8), '-',
        SUBSTRING(MD5(CONCAT(NOW(), RAND())) FROM 1 FOR 4), '-',
        SUBSTRING(MD5(CONCAT(NOW(), RAND())) FROM 1 FOR 4), '-',
        SUBSTRING(MD5(CONCAT(NOW(), RAND())) FROM 1 FOR 4), '-',
        SUBSTRING(MD5(CONCAT(NOW(), RAND())) FROM 1 FOR 12)
    ) AS install_id,
    NOW() AS installed_at,
    DATE_ADD(NOW(), INTERVAL 30 DAY) AS trial_expires_at,
    'trial' AS status,
    'New installation - 30 day trial' AS metadata
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM license_instances LIMIT 1);

-- Add audit entry for migration
INSERT INTO license_audit (install_id, action, new_status, details)
SELECT 
    install_id,
    'trial_started',
    'trial',
    'License system initialized with 30-day trial'
FROM license_instances
WHERE metadata LIKE '%New installation%';
