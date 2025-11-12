-- Add custom_ticket_text field to properties table
-- This allows property-specific text to appear on violation tickets below the fine total

ALTER TABLE properties ADD COLUMN custom_ticket_text TEXT DEFAULT NULL;
