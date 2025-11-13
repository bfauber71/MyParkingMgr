-- Add custom_note column to violation_tickets table
-- This column should exist in v2.0 but may be missing if migration was incomplete

ALTER TABLE violation_tickets 
ADD COLUMN custom_note TEXT AFTER issued_at;

SELECT 'custom_note column added successfully!' AS status;
