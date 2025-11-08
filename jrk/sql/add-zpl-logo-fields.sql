-- Add ZPL-converted logo storage fields to printer_settings table
-- Migration: Add logo_top_zpl, logo_bottom_zpl, and height fields
-- Date: 2025-11-08
-- Description: Stores ZPL ^GF format graphics and dimensions for direct printer output

-- Check if columns already exist before adding
SET @col_exists_top_zpl = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'printer_settings' 
    AND COLUMN_NAME = 'logo_top_zpl');

SET @col_exists_top_height = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'printer_settings' 
    AND COLUMN_NAME = 'logo_top_zpl_height');

SET @col_exists_bottom_zpl = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'printer_settings' 
    AND COLUMN_NAME = 'logo_bottom_zpl');

SET @col_exists_bottom_height = (SELECT COUNT(*) 
    FROM INFORMATION_SCHEMA.COLUMNS 
    WHERE TABLE_SCHEMA = DATABASE() 
    AND TABLE_NAME = 'printer_settings' 
    AND COLUMN_NAME = 'logo_bottom_zpl_height');

-- Add logo_top_zpl column if it doesn't exist
SET @sql_top_zpl = IF(@col_exists_top_zpl = 0,
    'ALTER TABLE printer_settings ADD COLUMN logo_top_zpl MEDIUMTEXT NULL COMMENT ''ZPL ^GF format graphic for top logo'' AFTER logo_top',
    'SELECT ''Column logo_top_zpl already exists'' AS message');

PREPARE stmt FROM @sql_top_zpl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add logo_top_zpl_height column if it doesn't exist
SET @sql_top_height = IF(@col_exists_top_height = 0,
    'ALTER TABLE printer_settings ADD COLUMN logo_top_zpl_height INT NULL COMMENT ''Height in dots of ZPL top logo'' AFTER logo_top_zpl',
    'SELECT ''Column logo_top_zpl_height already exists'' AS message');

PREPARE stmt FROM @sql_top_height;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add logo_bottom_zpl column if it doesn't exist
SET @sql_bottom_zpl = IF(@col_exists_bottom_zpl = 0,
    'ALTER TABLE printer_settings ADD COLUMN logo_bottom_zpl MEDIUMTEXT NULL COMMENT ''ZPL ^GF format graphic for bottom logo'' AFTER logo_bottom',
    'SELECT ''Column logo_bottom_zpl already exists'' AS message');

PREPARE stmt FROM @sql_bottom_zpl;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Add logo_bottom_zpl_height column if it doesn't exist
SET @sql_bottom_height = IF(@col_exists_bottom_height = 0,
    'ALTER TABLE printer_settings ADD COLUMN logo_bottom_zpl_height INT NULL COMMENT ''Height in dots of ZPL bottom logo'' AFTER logo_bottom_zpl',
    'SELECT ''Column logo_bottom_zpl_height already exists'' AS message');

PREPARE stmt FROM @sql_bottom_height;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Note: MEDIUMTEXT can store up to 16MB, which is sufficient for ZPL hex-encoded images
-- A typical 536x200px logo converts to approximately 50-100KB of ZPL data after compression
-- Height values are stored in dots (203 DPI for ZQ510 printer)
