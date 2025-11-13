-- ============================================
-- Create violations table (violation types)
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

-- Insert default violation types
INSERT INTO violations (id, name, fine_amount, tow_deadline_hours, is_active, display_order) VALUES
(UUID(), 'No Parking', 50.00, 48, TRUE, 1),
(UUID(), 'Expired Tags/Registration', 75.00, 72, TRUE, 2),
(UUID(), 'Unauthorized Parking', 100.00, 24, TRUE, 3),
(UUID(), 'Blocking Traffic/Fire Lane', 150.00, 12, TRUE, 4),
(UUID(), 'Reserved Space Violation', 50.00, 48, TRUE, 5),
(UUID(), 'Abandoned Vehicle', 200.00, 48, TRUE, 6);

SELECT 'Violations table created successfully!' AS status;
