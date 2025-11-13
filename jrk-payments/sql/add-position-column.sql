-- Add position column to property_contacts table
-- This allows ordering contacts (0=first, 1=second, 2=third)

ALTER TABLE property_contacts 
ADD COLUMN position TINYINT UNSIGNED NOT NULL DEFAULT 0 
COMMENT '0=first, 1=second, 2=third' 
AFTER email;

-- Add index for better performance on position ordering
CREATE INDEX idx_property_position ON property_contacts(property_id, position);
