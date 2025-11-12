-- Add user_assigned_properties table
-- This table manages which users have access to which properties
-- Required for property-based access control

CREATE TABLE IF NOT EXISTS user_assigned_properties (
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

-- Assign all existing properties to all admin users
-- This ensures admins can see all data immediately
INSERT IGNORE INTO user_assigned_properties (user_id, property_id)
SELECT u.id, p.id 
FROM users u
CROSS JOIN properties p
WHERE u.role = 'admin';
