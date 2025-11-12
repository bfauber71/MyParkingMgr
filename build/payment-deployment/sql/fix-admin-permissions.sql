-- Fix Admin User Permissions
-- Run this in phpMyAdmin if your admin user can't access User Management
--
-- This grants the admin user full permissions to all modules

-- First, get the admin user ID (we'll need it)
SET @admin_user_id = (SELECT id FROM users WHERE username = 'admin' LIMIT 1);

-- Delete any existing permissions for admin (to avoid duplicates)
DELETE FROM user_permissions WHERE user_id = @admin_user_id;

-- Grant admin full permissions to all modules
INSERT INTO user_permissions (id, user_id, module, can_view, can_edit, can_create_delete)
VALUES 
    (UUID(), @admin_user_id, 'vehicles', TRUE, TRUE, TRUE),
    (UUID(), @admin_user_id, 'users', TRUE, TRUE, TRUE),
    (UUID(), @admin_user_id, 'properties', TRUE, TRUE, TRUE),
    (UUID(), @admin_user_id, 'violations', TRUE, TRUE, TRUE),
    (UUID(), @admin_user_id, 'database', TRUE, TRUE, TRUE);

-- Verify the permissions were added
SELECT u.username, p.module, p.can_view, p.can_edit, p.can_create_delete
FROM user_permissions p
JOIN users u ON p.user_id = u.id
WHERE u.username = 'admin';
