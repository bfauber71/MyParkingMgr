-- Add expiration_date field to vehicles table for guest pass tracking
-- Migration: Guest Pass System
-- Date: 2025-11-08

-- Add expiration_date field (nullable for existing vehicles)
ALTER TABLE `vehicles` 
ADD COLUMN `expiration_date` DATE NULL 
COMMENT 'Guest pass expiration date (7 days from generation)';

-- Add index for expiration date filtering
ALTER TABLE `vehicles` 
ADD INDEX `idx_expiration_date` (`expiration_date`);
