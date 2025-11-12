-- Add resident, guest, and guest_of fields to vehicles table
-- Migration: Add non-resident and guest tracking capabilities
-- Date: 2025-11-08

-- Add resident field (defaults to TRUE for existing vehicles)
ALTER TABLE `vehicles` 
ADD COLUMN `resident` TINYINT(1) NOT NULL DEFAULT 1 
COMMENT 'TRUE if vehicle belongs to a resident, FALSE for non-residents';

-- Add guest field (defaults to FALSE)
ALTER TABLE `vehicles` 
ADD COLUMN `guest` TINYINT(1) NOT NULL DEFAULT 0 
COMMENT 'TRUE if vehicle is a guest vehicle';

-- Add guest_of field (same type as apt_number)
ALTER TABLE `vehicles` 
ADD COLUMN `guest_of` VARCHAR(50) NULL 
COMMENT 'Apartment number that guest is visiting';

-- Add index for guest lookups
ALTER TABLE `vehicles` 
ADD INDEX `idx_guest` (`guest`);

-- Add index for resident lookups
ALTER TABLE `vehicles` 
ADD INDEX `idx_resident` (`resident`);
