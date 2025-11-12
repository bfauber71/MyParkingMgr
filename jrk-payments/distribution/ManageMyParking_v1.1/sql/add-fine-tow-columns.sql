-- Add fine_amount and tow_deadline_hours columns to violations table
ALTER TABLE violations
ADD COLUMN fine_amount DECIMAL(10, 2) NULL DEFAULT NULL COMMENT 'Fine amount in dollars' AFTER name,
ADD COLUMN tow_deadline_hours INT NULL DEFAULT NULL COMMENT 'Hours until vehicle can be towed' AFTER fine_amount;

-- Create printer_settings table for custom configurations
CREATE TABLE IF NOT EXISTS printer_settings (
    id VARCHAR(36) PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default printer settings
INSERT INTO printer_settings (id, setting_key, setting_value) VALUES
(UUID(), 'ticket_width', '2.5'),
(UUID(), 'ticket_height', '6'),
(UUID(), 'ticket_unit', 'in'),
(UUID(), 'logo_top', NULL),
(UUID(), 'logo_bottom', NULL),
(UUID(), 'logo_top_enabled', 'false'),
(UUID(), 'logo_bottom_enabled', 'false')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Update some common violations with example fines and tow deadlines
UPDATE violations SET fine_amount = 75.00, tow_deadline_hours = 24 WHERE name LIKE '%Expired%' LIMIT 1;
UPDATE violations SET fine_amount = 100.00, tow_deadline_hours = 12 WHERE name LIKE '%No Tag%' LIMIT 1;
UPDATE violations SET fine_amount = 50.00 WHERE name LIKE '%Improper Parking%' LIMIT 1;
UPDATE violations SET fine_amount = 150.00, tow_deadline_hours = 6 WHERE name LIKE '%Handicap%' LIMIT 1;
UPDATE violations SET fine_amount = 125.00, tow_deadline_hours = 1 WHERE name LIKE '%Fire Lane%' LIMIT 1;