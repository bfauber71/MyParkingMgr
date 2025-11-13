-- ============================================
-- Migrate vehicles table to v2.0 schema
-- ============================================
-- SAFE: This adds/renames columns without losing data
-- Run this in phpMyAdmin SQL tab

-- Step 1: Add missing columns
ALTER TABLE vehicles 
ADD COLUMN plate_number VARCHAR(50) AFTER tag_plate,
ADD COLUMN year VARCHAR(4) AFTER color,
ADD COLUMN owner_name VARCHAR(255) AFTER apartment_unit,
ADD COLUMN owner_phone VARCHAR(50) AFTER owner_name,
ADD COLUMN owner_email VARCHAR(255) AFTER owner_phone,
ADD COLUMN reserved_space VARCHAR(50) AFTER owner_email;

-- Step 2: Add guest flag column (separate from is_resident)
ALTER TABLE vehicles 
ADD COLUMN guest TINYINT(1) DEFAULT 0 AFTER is_resident;

-- Step 3: Add property name column (v2.0 uses names instead of IDs for backward compatibility)
ALTER TABLE vehicles 
ADD COLUMN property VARCHAR(255) AFTER id;

-- Step 4: Populate property column with property names
UPDATE vehicles v
INNER JOIN properties p ON v.property_id = p.id
SET v.property = p.name;

-- Step 5: Make property column required
ALTER TABLE vehicles 
MODIFY COLUMN property VARCHAR(255) NOT NULL;

-- Step 6: Rename columns to match v2.0 API expectations
ALTER TABLE vehicles 
CHANGE COLUMN tag_plate tag_number VARCHAR(50),
CHANGE COLUMN apartment_unit apt_number VARCHAR(50),
CHANGE COLUMN is_resident resident TINYINT(1),
CHANGE COLUMN guest_of_unit guest_of VARCHAR(50);

-- Step 7: Add indexes for better performance
CREATE INDEX idx_tag_number ON vehicles(tag_number);
CREATE INDEX idx_plate_number ON vehicles(plate_number);
CREATE INDEX idx_property ON vehicles(property);
CREATE INDEX idx_expiration ON vehicles(expiration_date);

-- Verification: Show final schema
SELECT 'Migration complete! Verify columns below:' AS status;
DESCRIBE vehicles;

-- Show sample data
SELECT 'Sample vehicle data:' AS status;
SELECT id, property, tag_number, plate_number, make, model, color, apt_number, resident, guest FROM vehicles LIMIT 3;
