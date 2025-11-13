-- ============================================
-- Create ALL violation-related tables
-- ============================================

-- 1. Violations table (violation types)
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

-- 2. Violation tickets table (actual tickets issued)
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

-- 3. Violation ticket items table (line items on each ticket)
CREATE TABLE IF NOT EXISTS violation_ticket_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ticket_id VARCHAR(36) NOT NULL,
    violation_id VARCHAR(36),
    description TEXT NOT NULL,
    display_order TINYINT UNSIGNED DEFAULT 0,
    INDEX idx_ticket_id (ticket_id),
    INDEX idx_violation_id (violation_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default violation types
INSERT IGNORE INTO violations (id, name, fine_amount, tow_deadline_hours, is_active, display_order) VALUES
(UUID(), 'No Parking', 50.00, 48, TRUE, 1),
(UUID(), 'Expired Tags/Registration', 75.00, 72, TRUE, 2),
(UUID(), 'Unauthorized Parking', 100.00, 24, TRUE, 3),
(UUID(), 'Blocking Traffic/Fire Lane', 150.00, 12, TRUE, 4),
(UUID(), 'Reserved Space Violation', 50.00, 48, TRUE, 5),
(UUID(), 'Abandoned Vehicle', 200.00, 48, TRUE, 6);

SELECT 'All violation tables created successfully!' AS status;
