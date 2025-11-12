-- Fix printer_settings table to support larger logos
-- Change setting_value from TEXT (~65KB) to LONGTEXT (4GB)
-- This allows base64-encoded images to be stored

ALTER TABLE printer_settings 
MODIFY COLUMN setting_value LONGTEXT;
