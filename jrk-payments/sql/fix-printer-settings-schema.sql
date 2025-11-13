-- Fix printer_settings table schema mismatch
-- 
-- PROBLEM: Table has property-specific columns (property_id, printer_model, label_width, etc.)
-- SOLUTION: Recreate as key-value table (setting_key, setting_value)
-- 
-- This is safe to run since the table is empty (2 rows of test data)

-- Drop the wrong table structure
DROP TABLE IF EXISTS printer_settings;

-- Create correct key-value structure
CREATE TABLE printer_settings (
    id VARCHAR(36) PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value LONGTEXT,
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
(UUID(), 'logo_bottom_enabled', 'false'),
(UUID(), 'timezone', 'America/New_York');

-- Verify the fix
SELECT 'Printer settings table fixed successfully!' AS status;
SELECT * FROM printer_settings;
