-- ManageMyParking Simple Migration Script
-- Works with basic shared hosting permissions
-- No stored procedures required
-- Safe to run multiple times

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
-- SEED DEFAULT VIOLATIONS (if table is empty)
-- ============================================

-- Insert default violations only if violations table is empty
INSERT IGNORE INTO violations (id, name, display_order) VALUES
('880e8400-e29b-41d4-a716-446655440001', 'Expired Parking Permit', 1),
('880e8400-e29b-41d4-a716-446655440002', 'No Parking Permit Displayed', 2),
('880e8400-e29b-41d4-a716-446655440003', 'Parked in Reserved Space', 3),
('880e8400-e29b-41d4-a716-446655440004', 'Parked in Fire Lane', 4),
('880e8400-e29b-41d4-a716-446655440005', 'Parked in Handicapped Space Without Permit', 5),
('880e8400-e29b-41d4-a716-446655440006', 'Blocking Dumpster/Loading Zone', 6),
('880e8400-e29b-41d4-a716-446655440007', 'Double Parked', 7),
('880e8400-e29b-41d4-a716-446655440008', 'Parked Over Line/Taking Multiple Spaces', 8),
('880e8400-e29b-41d4-a716-446655440009', 'Abandoned Vehicle', 9),
('880e8400-e29b-41d4-a716-446655440010', 'Commercial Vehicle in Residential Area', 10);

-- ============================================
-- VERIFICATION
-- ============================================

SELECT 'Migration Complete!' AS status;
SELECT COUNT(*) AS violation_count FROM violations;
SELECT 
    TABLE_NAME,
    TABLE_ROWS
FROM information_schema.TABLES
WHERE TABLE_SCHEMA = DATABASE()
    AND TABLE_NAME IN ('violations', 'violation_tickets', 'violation_ticket_items')
ORDER BY TABLE_NAME;
