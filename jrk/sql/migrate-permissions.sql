-- ============================================================================
-- ManageMyParking: Permission Matrix Migration
-- ============================================================================
-- This script migrates from role-based to permission-matrix authorization
-- 
-- FEATURES:
-- - Creates user_permissions table with granular permissions
-- - Migrates existing roles to permission matrix
-- - Admin users get all permissions
-- - User/Operator roles get view-only by default
-- 
-- TO APPLY: Run in phpMyAdmin or MySQL client
-- TO ROLLBACK: See rollback section at bottom
-- ============================================================================

-- Create user_permissions table
CREATE TABLE IF NOT EXISTS `user_permissions` (
    `id` VARCHAR(36) PRIMARY KEY DEFAULT (UUID()),
    `user_id` VARCHAR(36) NOT NULL,
    `module` ENUM('vehicles', 'users', 'properties', 'violations') NOT NULL,
    `can_view` BOOLEAN NOT NULL DEFAULT FALSE,
    `can_edit` BOOLEAN NOT NULL DEFAULT FALSE,
    `can_create_delete` BOOLEAN NOT NULL DEFAULT FALSE,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Constraints
    UNIQUE KEY `unique_user_module` (`user_id`, `module`),
    INDEX `idx_user_id` (`user_id`),
    INDEX `idx_module` (`module`),
    
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================================
-- SEED DEFAULT PERMISSIONS BASED ON EXISTING ROLES
-- ============================================================================

-- Admin users: ALL permissions on ALL modules
INSERT INTO user_permissions (user_id, module, can_view, can_edit, can_create_delete)
SELECT 
    u.id,
    'vehicles',
    TRUE,
    TRUE,
    TRUE
FROM users u
WHERE LOWER(u.role) = 'admin'
ON DUPLICATE KEY UPDATE
    can_view = TRUE,
    can_edit = TRUE,
    can_create_delete = TRUE;

INSERT INTO user_permissions (user_id, module, can_view, can_edit, can_create_delete)
SELECT 
    u.id,
    'users',
    TRUE,
    TRUE,
    TRUE
FROM users u
WHERE LOWER(u.role) = 'admin'
ON DUPLICATE KEY UPDATE
    can_view = TRUE,
    can_edit = TRUE,
    can_create_delete = TRUE;

INSERT INTO user_permissions (user_id, module, can_view, can_edit, can_create_delete)
SELECT 
    u.id,
    'properties',
    TRUE,
    TRUE,
    TRUE
FROM users u
WHERE LOWER(u.role) = 'admin'
ON DUPLICATE KEY UPDATE
    can_view = TRUE,
    can_edit = TRUE,
    can_create_delete = TRUE;

INSERT INTO user_permissions (user_id, module, can_view, can_edit, can_create_delete)
SELECT 
    u.id,
    'violations',
    TRUE,
    TRUE,
    TRUE
FROM users u
WHERE LOWER(u.role) = 'admin'
ON DUPLICATE KEY UPDATE
    can_view = TRUE,
    can_edit = TRUE,
    can_create_delete = TRUE;

-- User role: FULL permissions on vehicles only (matches legacy behavior)
-- Legacy: "User" role could manage vehicles and create violations for assigned properties
INSERT INTO user_permissions (user_id, module, can_view, can_edit, can_create_delete)
SELECT 
    u.id,
    'vehicles',
    TRUE,
    TRUE,
    TRUE
FROM users u
WHERE LOWER(u.role) = 'user'
ON DUPLICATE KEY UPDATE
    can_view = TRUE,
    can_edit = TRUE,
    can_create_delete = TRUE;

-- Operator role: VIEW-ONLY on vehicles only (matches legacy behavior)
-- Legacy: "Operator" role had view-only access to vehicles
INSERT INTO user_permissions (user_id, module, can_view, can_edit, can_create_delete)
SELECT 
    u.id,
    'vehicles',
    TRUE,
    FALSE,
    FALSE
FROM users u
WHERE LOWER(u.role) = 'operator'
ON DUPLICATE KEY UPDATE
    can_view = TRUE,
    can_edit = FALSE,
    can_create_delete = FALSE;

-- ============================================================================
-- VERIFICATION QUERIES (Run these to check results)
-- ============================================================================

-- View all user permissions
-- SELECT u.username, u.role, up.module, up.can_view, up.can_edit, up.can_create_delete
-- FROM users u
-- LEFT JOIN user_permissions up ON u.id = up.user_id
-- ORDER BY u.username, up.module;

-- Count permissions by role
-- SELECT u.role, COUNT(*) as permission_count
-- FROM users u
-- INNER JOIN user_permissions up ON u.id = up.user_id
-- GROUP BY u.role;

-- Find users without permissions (should be empty)
-- SELECT u.id, u.username, u.role
-- FROM users u
-- LEFT JOIN user_permissions up ON u.id = up.user_id
-- WHERE up.id IS NULL;

-- ============================================================================
-- ROLLBACK INSTRUCTIONS
-- ============================================================================
-- To rollback this migration, run:
-- DROP TABLE IF EXISTS user_permissions;
--
-- Note: The users.role column is retained for backward compatibility
-- and can be removed in a future migration once the permission system
-- is fully tested and deployed.
-- ============================================================================
