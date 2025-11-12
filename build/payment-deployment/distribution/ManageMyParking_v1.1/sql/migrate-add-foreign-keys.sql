-- ManageMyParking - Add Foreign Keys (Optional)
-- Only run this if your hosting supports foreign key constraints
-- If this fails, the app will still work - foreign keys are for data integrity only

USE managemyparking;

-- Try to add foreign keys (may fail on some shared hosting)
-- If these fail, you can safely ignore the errors

-- Foreign keys for violation_tickets
ALTER TABLE violation_tickets 
ADD CONSTRAINT fk_vt_vehicle_id 
FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE;

ALTER TABLE violation_tickets 
ADD CONSTRAINT fk_vt_issued_by_user_id 
FOREIGN KEY (issued_by_user_id) REFERENCES users(id) ON DELETE CASCADE;

-- Foreign keys for violation_ticket_items
ALTER TABLE violation_ticket_items 
ADD CONSTRAINT fk_vti_ticket_id 
FOREIGN KEY (ticket_id) REFERENCES violation_tickets(id) ON DELETE CASCADE;

ALTER TABLE violation_ticket_items 
ADD CONSTRAINT fk_vti_violation_id 
FOREIGN KEY (violation_id) REFERENCES violations(id) ON DELETE SET NULL;

SELECT 'Foreign keys added successfully!' AS status;
