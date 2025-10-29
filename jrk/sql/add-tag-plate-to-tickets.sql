-- Add tag_number and plate_number to violation_tickets table
-- These are snapshot fields that preserve vehicle info at the time of ticketing

ALTER TABLE violation_tickets
ADD COLUMN tag_number VARCHAR(100) AFTER vehicle_model,
ADD COLUMN plate_number VARCHAR(100) AFTER tag_number;
