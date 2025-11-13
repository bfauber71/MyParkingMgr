-- Add custom_note column
ALTER TABLE violation_tickets ADD COLUMN custom_note TEXT AFTER issued_at;
