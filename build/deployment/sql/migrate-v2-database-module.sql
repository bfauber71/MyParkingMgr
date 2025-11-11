-- ============================================================================
-- MyParkingManager: v2.0 Database Module Migration
-- ============================================================================
-- This script adds the Database module and login attempt limiting
-- 
-- FEATURES:
-- - Adds 'database' to module ENUM
-- - Creates login_attempts table
-- - Grants admin users database module permissions
-- 
-- TO APPLY: Run in phpMyAdmin or MySQL client
-- TO ROLLBACK: See rollback section at bottom
-- ============================================================================

-- Add 'database' to module ENUM in user_permissions table
-- Note: This requires recreating the column due to MySQL ENUM limitations
ALTER TABLE user_permissions 
MODIFY COLUMN module ENUM('vehicles', 'users', 'properties', 'violations', 'database') NOT NULL;

-- Create login_attempts table if it doesn't exist
CREATE TABLE IF NOT EXISTS login_attempts (
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

-- Grant database module permissions to all admin users
INSERT INTO user_permissions (id, user_id, module, can_view, can_edit, can_create_delete)
SELECT 
    UUID(),
    u.id,
    'database',
    TRUE,
    TRUE,
    TRUE
FROM users u
WHERE LOWER(u.role) = 'admin'
ON DUPLICATE KEY UPDATE
    can_view = TRUE,
    can_edit = TRUE,
    can_create_delete = TRUE;

-- ============================================================================
-- VERIFICATION QUERIES (Run these to check results)
-- ============================================================================

-- View all admin database permissions
-- SELECT u.username, u.role, up.module, up.can_view, up.can_edit, up.can_create_delete
-- FROM users u
-- LEFT JOIN user_permissions up ON u.id = up.user_id
-- WHERE u.role = 'admin' AND up.module = 'database'
-- ORDER BY u.username;

-- Check if login_attempts table was created
-- SHOW TABLES LIKE 'login_attempts';

-- ============================================================================
-- ROLLBACK INSTRUCTIONS
-- ============================================================================
-- To rollback this migration:
-- 
-- 1. Remove database permissions:
-- DELETE FROM user_permissions WHERE module = 'database';
-- 
-- 2. Drop login_attempts table:
-- DROP TABLE IF EXISTS login_attempts;
-- 
-- 3. Revert module ENUM (requires recreating column):
-- ALTER TABLE user_permissions 
-- MODIFY COLUMN module ENUM('vehicles', 'users', 'properties', 'violations') NOT NULL;
-- ============================================================================
