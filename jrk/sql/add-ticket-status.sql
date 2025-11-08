-- Add status field to violation_tickets table
-- Migration: Ticket Status Management (active/closed)
-- Date: 2025-11-08

-- Add status field (defaults to 'active' for existing tickets)
ALTER TABLE `violation_tickets` 
ADD COLUMN `status` VARCHAR(20) NOT NULL DEFAULT 'active' 
COMMENT 'Ticket status: active or closed';

-- Add fine_disposition field to track how ticket was closed
ALTER TABLE `violation_tickets` 
ADD COLUMN `fine_disposition` VARCHAR(20) NULL 
COMMENT 'How fine was resolved: collected or dismissed';

-- Add closed_at timestamp
ALTER TABLE `violation_tickets` 
ADD COLUMN `closed_at` DATETIME NULL 
COMMENT 'When ticket was closed';

-- Add closed_by_user_id to track who closed the ticket
ALTER TABLE `violation_tickets` 
ADD COLUMN `closed_by_user_id` VARCHAR(36) NULL 
COMMENT 'User who closed the ticket';

-- Add index for status filtering
ALTER TABLE `violation_tickets` 
ADD INDEX `idx_status` (`status`);

-- Add index for fine disposition filtering
ALTER TABLE `violation_tickets` 
ADD INDEX `idx_fine_disposition` (`fine_disposition`);

-- Add check constraint to ensure valid status
ALTER TABLE `violation_tickets` 
ADD CONSTRAINT `chk_ticket_status` 
CHECK (`status` IN ('active', 'closed'));

-- Add check constraint to ensure valid fine disposition
ALTER TABLE `violation_tickets` 
ADD CONSTRAINT `chk_fine_disposition` 
CHECK (`fine_disposition` IS NULL OR `fine_disposition` IN ('collected', 'dismissed'));
