-- ============================================
-- Add Test Data to Existing Installation
-- ManageMyParking v2.0
-- ============================================
-- 
-- This script adds sample test data to an existing installation:
-- - 1 test property (Sunset Apartments)
-- - 10 test vehicles
-- - 30 violation tickets (3 per vehicle)
--
-- Safe to run on existing installations.
-- Uses INSERT IGNORE to avoid duplicates.
-- 
-- ============================================

-- Get admin user ID (assumes 'admin' user exists)
SET @admin_user_id = (SELECT id FROM users WHERE username = 'admin' LIMIT 1);

-- Insert test property
SET @test_property_id = UUID();
INSERT IGNORE INTO `properties` (`id`, `name`, `address`, `custom_ticket_text`) 
VALUES (
    @test_property_id,
    'Sunset Apartments',
    '123 Main Street, Indianapolis, IN 46204',
    'PARKING VIOLATION - UNAUTHORIZED PARKING\nAll vehicles must display valid parking permits.\nContact office: (317) 555-0100'
);

-- Get the property ID (in case it already existed)
SET @test_property_id = (SELECT id FROM properties WHERE name = 'Sunset Apartments' LIMIT 1);

-- Insert test vehicles
SET @vehicle1_id = UUID();
SET @vehicle2_id = UUID();
SET @vehicle3_id = UUID();
SET @vehicle4_id = UUID();
SET @vehicle5_id = UUID();
SET @vehicle6_id = UUID();
SET @vehicle7_id = UUID();
SET @vehicle8_id = UUID();
SET @vehicle9_id = UUID();
SET @vehicle10_id = UUID();

INSERT IGNORE INTO `vehicles` (`id`, `tag_plate`, `state`, `make`, `model`, `color`, `apartment_unit`, `property_id`, `is_resident`, `notes`)
VALUES 
    (@vehicle1_id, 'ABC1234', 'IN', 'Honda', 'Civic', 'Silver', '101', @test_property_id, TRUE, 'Resident vehicle'),
    (@vehicle2_id, 'XYZ5678', 'IN', 'Toyota', 'Camry', 'Blue', '102', @test_property_id, TRUE, 'Resident vehicle'),
    (@vehicle3_id, 'DEF9012', 'IN', 'Ford', 'F-150', 'Black', '103', @test_property_id, TRUE, 'Resident vehicle'),
    (@vehicle4_id, 'GHI3456', 'OH', 'Chevrolet', 'Malibu', 'White', '104', @test_property_id, TRUE, 'Resident vehicle'),
    (@vehicle5_id, 'JKL7890', 'IN', 'Nissan', 'Altima', 'Red', '105', @test_property_id, TRUE, 'Resident vehicle'),
    (@vehicle6_id, 'MNO1234', 'IN', 'Jeep', 'Cherokee', 'Green', '201', @test_property_id, TRUE, 'Resident vehicle'),
    (@vehicle7_id, 'PQR5678', 'KY', 'Dodge', 'Charger', 'Gray', '202', @test_property_id, TRUE, 'Resident vehicle'),
    (@vehicle8_id, 'STU9012', 'IN', 'Hyundai', 'Elantra', 'Black', '203', @test_property_id, TRUE, 'Resident vehicle'),
    (@vehicle9_id, 'VWX3456', 'IN', 'Kia', 'Optima', 'Silver', '204', @test_property_id, TRUE, 'Resident vehicle'),
    (@vehicle10_id, 'YZA7890', 'IL', 'Mazda', 'CX-5', 'Blue', '205', @test_property_id, TRUE, 'Resident vehicle');

-- Get vehicle IDs (in case they already existed by tag_plate)
SET @vehicle1_id = (SELECT id FROM vehicles WHERE tag_plate = 'ABC1234' LIMIT 1);
SET @vehicle2_id = (SELECT id FROM vehicles WHERE tag_plate = 'XYZ5678' LIMIT 1);
SET @vehicle3_id = (SELECT id FROM vehicles WHERE tag_plate = 'DEF9012' LIMIT 1);
SET @vehicle4_id = (SELECT id FROM vehicles WHERE tag_plate = 'GHI3456' LIMIT 1);
SET @vehicle5_id = (SELECT id FROM vehicles WHERE tag_plate = 'JKL7890' LIMIT 1);
SET @vehicle6_id = (SELECT id FROM vehicles WHERE tag_plate = 'MNO1234' LIMIT 1);
SET @vehicle7_id = (SELECT id FROM vehicles WHERE tag_plate = 'PQR5678' LIMIT 1);
SET @vehicle8_id = (SELECT id FROM vehicles WHERE tag_plate = 'STU9012' LIMIT 1);
SET @vehicle9_id = (SELECT id FROM vehicles WHERE tag_plate = 'VWX3456' LIMIT 1);
SET @vehicle10_id = (SELECT id FROM vehicles WHERE tag_plate = 'YZA7890' LIMIT 1);

-- Insert violation tickets (3 per vehicle = 30 total)
-- Vehicle 1 violations
INSERT IGNORE INTO `violation_tickets` (`vehicle_id`, `property_id`, `violation_type`, `violation_date`, `location`, `notes`, `tag_plate`, `ticket_type`, `fine_amount`, `created_by`)
VALUES 
    (@vehicle1_id, @test_property_id, 'Expired Parking Permit', DATE_SUB(NOW(), INTERVAL 15 DAY), 'Parking Lot A - Space 12', 'Permit expired 30 days ago', 'ABC1234', 'WARNING', 0.00, @admin_user_id),
    (@vehicle1_id, @test_property_id, 'Parked in Fire Lane', DATE_SUB(NOW(), INTERVAL 10 DAY), 'Building Entrance', 'Vehicle blocking emergency access', 'ABC1234', 'VIOLATION', 75.00, @admin_user_id),
    (@vehicle1_id, @test_property_id, 'No Parking Permit', DATE_SUB(NOW(), INTERVAL 5 DAY), 'Parking Lot B - Space 45', 'No visible permit displayed', 'ABC1234', 'VIOLATION', 50.00, @admin_user_id);

-- Vehicle 2 violations
INSERT IGNORE INTO `violation_tickets` (`vehicle_id`, `property_id`, `violation_type`, `violation_date`, `location`, `notes`, `tag_plate`, `ticket_type`, `fine_amount`, `created_by`)
VALUES 
    (@vehicle2_id, @test_property_id, 'Blocking Dumpster', DATE_SUB(NOW(), INTERVAL 14 DAY), 'Rear Parking Area', 'Trash service unable to access', 'XYZ5678', 'VIOLATION', 50.00, @admin_user_id),
    (@vehicle2_id, @test_property_id, 'Double Parked', DATE_SUB(NOW(), INTERVAL 8 DAY), 'Parking Lot A - Aisle 3', 'Blocking another vehicle', 'XYZ5678', 'WARNING', 0.00, @admin_user_id),
    (@vehicle2_id, @test_property_id, 'Parked in Reserved Space', DATE_SUB(NOW(), INTERVAL 3 DAY), 'Parking Lot A - Space 1', 'Space reserved for office', 'XYZ5678', 'VIOLATION', 40.00, @admin_user_id);

-- Vehicle 3 violations
INSERT IGNORE INTO `violation_tickets` (`vehicle_id`, `property_id`, `violation_type`, `violation_date`, `location`, `notes`, `tag_plate`, `ticket_type`, `fine_amount`, `created_by`)
VALUES 
    (@vehicle3_id, @test_property_id, 'Overnight Parking Violation', DATE_SUB(NOW(), INTERVAL 20 DAY), 'Visitor Parking', 'No overnight parking allowed', 'DEF9012', 'VIOLATION', 35.00, @admin_user_id),
    (@vehicle3_id, @test_property_id, 'Parked Over Line', DATE_SUB(NOW(), INTERVAL 12 DAY), 'Parking Lot B - Space 22', 'Taking up two spaces', 'DEF9012', 'WARNING', 0.00, @admin_user_id),
    (@vehicle3_id, @test_property_id, 'Abandoned Vehicle', DATE_SUB(NOW(), INTERVAL 7 DAY), 'Parking Lot C - Space 78', 'Vehicle unmoved for 10+ days', 'DEF9012', 'VIOLATION', 100.00, @admin_user_id);

-- Vehicle 4 violations
INSERT IGNORE INTO `violation_tickets` (`vehicle_id`, `property_id`, `violation_type`, `violation_date`, `location`, `notes`, `tag_plate`, `ticket_type`, `fine_amount`, `created_by`)
VALUES 
    (@vehicle4_id, @test_property_id, 'Parking in Handicap Space', DATE_SUB(NOW(), INTERVAL 18 DAY), 'Parking Lot A - HC Space 1', 'No handicap placard displayed', 'GHI3456', 'VIOLATION', 150.00, @admin_user_id),
    (@vehicle4_id, @test_property_id, 'No Parking Permit', DATE_SUB(NOW(), INTERVAL 11 DAY), 'Parking Lot A - Space 34', 'Permit not visible', 'GHI3456', 'WARNING', 0.00, @admin_user_id),
    (@vehicle4_id, @test_property_id, 'Expired Parking Permit', DATE_SUB(NOW(), INTERVAL 4 DAY), 'Parking Lot B - Space 56', 'Permit expired 60 days', 'GHI3456', 'VIOLATION', 50.00, @admin_user_id);

-- Vehicle 5 violations
INSERT IGNORE INTO `violation_tickets` (`vehicle_id`, `property_id`, `violation_type`, `violation_date`, `location`, `notes`, `tag_plate`, `ticket_type`, `fine_amount`, `created_by`)
VALUES 
    (@vehicle5_id, @test_property_id, 'Parked in Fire Lane', DATE_SUB(NOW(), INTERVAL 16 DAY), 'West Building Entrance', 'Blocking fire lane', 'JKL7890', 'VIOLATION', 75.00, @admin_user_id),
    (@vehicle5_id, @test_property_id, 'Blocking Mailbox', DATE_SUB(NOW(), INTERVAL 9 DAY), 'Mailbox Area', 'Mail carrier unable to access', 'JKL7890', 'WARNING', 0.00, @admin_user_id),
    (@vehicle5_id, @test_property_id, 'Parked on Grass', DATE_SUB(NOW(), INTERVAL 2 DAY), 'Front Lawn Area', 'Damaging landscaping', 'JKL7890', 'VIOLATION', 60.00, @admin_user_id);

-- Vehicle 6 violations
INSERT IGNORE INTO `violation_tickets` (`vehicle_id`, `property_id`, `violation_type`, `violation_date`, `location`, `notes`, `tag_plate`, `ticket_type`, `fine_amount`, `created_by`)
VALUES 
    (@vehicle6_id, @test_property_id, 'Loud Exhaust', DATE_SUB(NOW(), INTERVAL 13 DAY), 'Parking Lot A - Space 67', 'Modified exhaust system', 'MNO1234', 'WARNING', 0.00, @admin_user_id),
    (@vehicle6_id, @test_property_id, 'No Parking Permit', DATE_SUB(NOW(), INTERVAL 6 DAY), 'Parking Lot C - Space 89', 'Missing permit', 'MNO1234', 'VIOLATION', 50.00, @admin_user_id),
    (@vehicle6_id, @test_property_id, 'Parking in Visitor Space', DATE_SUB(NOW(), INTERVAL 1 DAY), 'Visitor Lot - Space 5', 'Residents not allowed in visitor parking', 'MNO1234', 'VIOLATION', 35.00, @admin_user_id);

-- Vehicle 7 violations
INSERT IGNORE INTO `violation_tickets` (`vehicle_id`, `property_id`, `violation_type`, `violation_date`, `location`, `notes`, `tag_plate`, `ticket_type`, `fine_amount`, `created_by`)
VALUES 
    (@vehicle7_id, @test_property_id, 'Speed Violation', DATE_SUB(NOW(), INTERVAL 17 DAY), 'Main Drive', 'Excessive speed in parking lot', 'PQR5678', 'WARNING', 0.00, @admin_user_id),
    (@vehicle7_id, @test_property_id, 'Expired Tags', DATE_SUB(NOW(), INTERVAL 10 DAY), 'Parking Lot B - Space 23', 'License plate tags expired', 'PQR5678', 'VIOLATION', 40.00, @admin_user_id),
    (@vehicle7_id, @test_property_id, 'Parked in Reserved Space', DATE_SUB(NOW(), INTERVAL 5 DAY), 'Manager Space', 'Reserved for property manager', 'PQR5678', 'VIOLATION', 40.00, @admin_user_id);

-- Vehicle 8 violations
INSERT IGNORE INTO `violation_tickets` (`vehicle_id`, `property_id`, `violation_type`, `violation_date`, `location`, `notes`, `tag_plate`, `ticket_type`, `fine_amount`, `created_by`)
VALUES 
    (@vehicle8_id, @test_property_id, 'No Parking Permit', DATE_SUB(NOW(), INTERVAL 19 DAY), 'Parking Lot A - Space 11', 'No permit displayed', 'STU9012', 'VIOLATION', 50.00, @admin_user_id),
    (@vehicle8_id, @test_property_id, 'Blocking Driveway', DATE_SUB(NOW(), INTERVAL 12 DAY), 'Unit 203 Driveway', 'Blocking resident access', 'STU9012', 'WARNING', 0.00, @admin_user_id),
    (@vehicle8_id, @test_property_id, 'Inoperable Vehicle', DATE_SUB(NOW(), INTERVAL 6 DAY), 'Parking Lot C - Space 44', 'Flat tire, vehicle not moved in 14 days', 'STU9012', 'VIOLATION', 75.00, @admin_user_id);

-- Vehicle 9 violations
INSERT IGNORE INTO `violation_tickets` (`vehicle_id`, `property_id`, `violation_type`, `violation_date`, `location`, `notes`, `tag_plate`, `ticket_type`, `fine_amount`, `created_by`)
VALUES 
    (@vehicle9_id, @test_property_id, 'Parked Over Line', DATE_SUB(NOW(), INTERVAL 14 DAY), 'Parking Lot B - Space 33', 'Improper parking', 'VWX3456', 'WARNING', 0.00, @admin_user_id),
    (@vehicle9_id, @test_property_id, 'Expired Parking Permit', DATE_SUB(NOW(), INTERVAL 8 DAY), 'Parking Lot A - Space 55', 'Permit expired', 'VWX3456', 'VIOLATION', 50.00, @admin_user_id),
    (@vehicle9_id, @test_property_id, 'Commercial Vehicle', DATE_SUB(NOW(), INTERVAL 3 DAY), 'Parking Lot A - Space 66', 'Commercial vehicles not permitted', 'VWX3456', 'VIOLATION', 60.00, @admin_user_id);

-- Vehicle 10 violations
INSERT IGNORE INTO `violation_tickets` (`vehicle_id`, `property_id`, `violation_type`, `violation_date`, `location`, `notes`, `tag_plate`, `ticket_type`, `fine_amount`, `created_by`)
VALUES 
    (@vehicle10_id, @test_property_id, 'Blocking Walkway', DATE_SUB(NOW(), INTERVAL 11 DAY), 'Sidewalk Area', 'Vehicle on pedestrian walkway', 'YZA7890', 'VIOLATION', 45.00, @admin_user_id),
    (@vehicle10_id, @test_property_id, 'No Parking Permit', DATE_SUB(NOW(), INTERVAL 7 DAY), 'Parking Lot B - Space 77', 'Missing permit', 'YZA7890', 'WARNING', 0.00, @admin_user_id),
    (@vehicle10_id, @test_property_id, 'Parked in Fire Lane', DATE_SUB(NOW(), INTERVAL 2 DAY), 'East Entrance', 'Emergency access blocked', 'YZA7890', 'VIOLATION', 75.00, @admin_user_id);

-- ============================================
-- TEST DATA INSTALLATION COMPLETE!
-- ============================================
-- 
-- Added to your database:
-- - 1 property: Sunset Apartments
-- - 10 vehicles with realistic data
-- - 30 violation tickets (3 per vehicle)
-- - Total fines: $1,735.00
-- 
-- You can now test all features with real-looking data!
-- 
-- ============================================
