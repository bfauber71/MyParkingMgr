-- Add printer_settings table for custom ticket configurations
-- This allows admins to configure ticket size and logos

CREATE TABLE IF NOT EXISTS printer_settings (
    id VARCHAR(36) PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_setting_key (setting_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Insert default printer settings
INSERT IGNORE INTO printer_settings (id, setting_key, setting_value) VALUES
(UUID(), 'ticket_width', '2.5'),
(UUID(), 'ticket_height', '6'),
(UUID(), 'ticket_unit', 'in'),
(UUID(), 'logo_top', NULL),
(UUID(), 'logo_bottom', NULL),
(UUID(), 'logo_top_enabled', 'false'),
(UUID(), 'logo_bottom_enabled', 'false');
