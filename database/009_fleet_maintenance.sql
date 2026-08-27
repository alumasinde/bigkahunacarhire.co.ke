-- =========================================================
-- Phase 8: Fleet, Maintenance & Vehicle Compliance
-- =========================================================

CREATE TABLE IF NOT EXISTS vehicle_maintenance (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    maintenance_type ENUM('service','repair','inspection','tyres','brakes','oil_change','other') NOT NULL DEFAULT 'service',
    title VARCHAR(180) NOT NULL,
    description TEXT,
    service_date DATE DEFAULT NULL,
    due_date DATE DEFAULT NULL,
    odometer_km DECIMAL(10,1) DEFAULT NULL,
    due_odometer_km DECIMAL(10,1) DEFAULT NULL,
    cost DECIMAL(10,2) NOT NULL DEFAULT 0,
    vendor VARCHAR(180) DEFAULT NULL,
    status ENUM('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
    created_by INT DEFAULT NULL,
    completed_by INT DEFAULT NULL,
    completed_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (completed_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_maintenance_car_status (car_id,status),
    INDEX idx_maintenance_due (status,due_date),
    INDEX idx_maintenance_odometer (status,due_odometer_km)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicle_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    document_type ENUM('logbook','insurance','inspection','roadworthy','permit','lease','other') NOT NULL DEFAULT 'other',
    title VARCHAR(180) NOT NULL,
    document_number VARCHAR(100) DEFAULT NULL,
    file_path VARCHAR(255) DEFAULT NULL,
    issued_date DATE DEFAULT NULL,
    expiry_date DATE DEFAULT NULL,
    notes TEXT,
    status ENUM('active','expired','replaced') NOT NULL DEFAULT 'active',
    uploaded_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_documents_car_type (car_id,document_type),
    INDEX idx_documents_expiry (status,expiry_date)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS vehicle_odometer_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    booking_id INT DEFAULT NULL,
    reading_km DECIMAL(10,1) NOT NULL,
    reading_type ENUM('manual','checkout','return','service') NOT NULL DEFAULT 'manual',
    recorded_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    recorded_by INT DEFAULT NULL,
    notes VARCHAR(255) DEFAULT NULL,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL,
    FOREIGN KEY (recorded_by) REFERENCES users(id) ON DELETE SET NULL,
    INDEX idx_odometer_car_date (car_id,recorded_at)
) ENGINE=InnoDB;

INSERT IGNORE INTO settings (setting_group, setting_key, setting_value) VALUES
('fleet','document_expiry_warning_days','30'),
('fleet','maintenance_due_warning_days','14'),
('fleet','default_service_interval_km','10000');

SELECT 'Phase 8 fleet migration complete.' AS status;
