-- MyParkingManager License System Migration
-- Adds subscription-based access control with 30-day trial

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
    metadata JSON NULL COMMENT 'Additional license data',
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
    details JSON NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_install_id (install_id),
    INDEX idx_action (action),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add installation tracking to config (skip if already exists)
-- MySQL 5.7 compatible - no IF NOT EXISTS
SET @column_exists = (
    SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'users' 
    AND COLUMN_NAME = 'can_manage_license'
);

SET @sql = IF(@column_exists = 0,
    'ALTER TABLE users ADD COLUMN can_manage_license BOOLEAN DEFAULT FALSE COMMENT ''Admin-only license management''',
    'SELECT ''Column can_manage_license already exists'' AS message'
);

PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Update admin permissions for license management
UPDATE users 
SET can_manage_license = TRUE 
WHERE role = 'admin';

-- Check if license data exists, if not, initialize for existing installations
-- Give existing installations a 7-day grace period to enter license
INSERT INTO license_instances (
    id, 
    install_id, 
    installed_at, 
    trial_expires_at, 
    status,
    metadata
)
SELECT 
    UUID(),
    UUID(), -- Generate unique install ID
    NOW(),
    DATE_ADD(NOW(), INTERVAL 7 DAY), -- 7-day grace period for existing installs
    'trial',
    JSON_OBJECT('migration_note', 'Existing installation - 7 day grace period')
FROM dual
WHERE NOT EXISTS (SELECT 1 FROM license_instances LIMIT 1);

-- Add audit entry for migration
INSERT INTO license_audit (install_id, action, new_status, details)
SELECT 
    install_id,
    'migration_grace_period',
    'trial',
    JSON_OBJECT('note', 'Migrated existing installation with 7-day grace period')
FROM license_instances
WHERE metadata LIKE '%migration_note%';