-- =========================================================
-- Phase 9: Customer Experience & Rental Guidance
-- =========================================================

INSERT IGNORE INTO settings (setting_group, setting_key, setting_value) VALUES
('rental','pickup_instructions','Please carry your original ID/passport and valid driving licence. Our team will confirm the pickup point before your rental.'),
('rental','return_instructions','Return the vehicle at the agreed time and location with all keys and accessories.'),
('customer','whatsapp_booking_enabled','1');

SELECT 'Phase 9 customer experience migration complete.' AS status;
