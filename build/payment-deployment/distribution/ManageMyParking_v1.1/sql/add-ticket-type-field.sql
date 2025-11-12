-- Add ticket_type field to violation_tickets table
-- Migration: Add WARNING vs VIOLATION ticket types
-- Date: 2025-11-08

-- Add ticket_type field (defaults to VIOLATION for existing tickets)
ALTER TABLE `violation_tickets` 
ADD COLUMN `ticket_type` VARCHAR(20) NOT NULL DEFAULT 'VIOLATION' 
COMMENT 'Type of ticket: VIOLATION or WARNING';

-- Add index for ticket type filtering
ALTER TABLE `violation_tickets` 
ADD INDEX `idx_ticket_type` (`ticket_type`);

-- Add check constraint to ensure valid ticket types
-- Note: MySQL 8.0.16+ supports check constraints
ALTER TABLE `violation_tickets` 
ADD CONSTRAINT `chk_ticket_type` 
CHECK (`ticket_type` IN ('VIOLATION', 'WARNING'));
