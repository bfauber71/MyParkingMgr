-- Add timezone setting to printer_settings table
-- Run this on existing databases to add the timezone feature

INSERT INTO printer_settings (id, setting_key, setting_value) VALUES
(UUID(), 'timezone', 'America/New_York')
ON DUPLICATE KEY UPDATE setting_value = 'America/New_York';
