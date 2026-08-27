-- =========================================================
-- Phase 7: Rental / Handover Operations
-- =========================================================

CREATE TABLE IF NOT EXISTS rental_inspections (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    car_id INT NOT NULL,
    inspection_type ENUM('checkout','return') NOT NULL,
    inspected_by INT DEFAULT NULL,
    inspected_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    odometer_km DECIMAL(10,1) DEFAULT NULL,
    fuel_level DECIMAL(5,2) DEFAULT NULL,
    condition_notes TEXT,
    damage_notes TEXT,
    photos_json JSON DEFAULT NULL,
    customer_acknowledged TINYINT(1) NOT NULL DEFAULT 0,
    customer_name VARCHAR(200) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE RESTRICT,
    FOREIGN KEY (inspected_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_inspections_booking_type (booking_id, inspection_type),
    INDEX idx_inspections_car_date (car_id, inspected_at)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS rental_charges (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    charge_type ENUM('late_return','extra_mileage','fuel','damage','cleaning','other') NOT NULL,
    description VARCHAR(255) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','paid','waived') NOT NULL DEFAULT 'pending',
    created_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_charges_booking_status (booking_id, status)
) ENGINE=InnoDB;

SET @schema := DATABASE();
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='bookings' AND COLUMN_NAME='checkout_at')=0,
 'ALTER TABLE bookings ADD COLUMN checkout_at DATETIME DEFAULT NULL','SELECT "checkout_at exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='bookings' AND COLUMN_NAME='returned_at')=0,
 'ALTER TABLE bookings ADD COLUMN returned_at DATETIME DEFAULT NULL','SELECT "returned_at exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='bookings' AND COLUMN_NAME='actual_pickup_at')=0,
 'ALTER TABLE bookings ADD COLUMN actual_pickup_at DATETIME DEFAULT NULL','SELECT "actual_pickup_at exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;
SET @sql := IF((SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='bookings' AND COLUMN_NAME='actual_return_at')=0,
 'ALTER TABLE bookings ADD COLUMN actual_return_at DATETIME DEFAULT NULL','SELECT "actual_return_at exists"');
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO settings (setting_group, setting_key, setting_value) VALUES
('rental', 'late_return_grace_minutes', '30'),
('rental', 'extra_mileage_rate_per_km', '0'),
('rental', 'fuel_charge_per_unit', '0'),
('rental', 'require_checkout_inspection', '1'),
('rental', 'require_return_inspection', '1');

SELECT 'Phase 7 rental operations migration complete.' AS status;
