-- Phase 3: operations center, audit trail and notification delivery log.
-- Production-safe additive migration. Does not delete or update existing transactional data.

SET @has_whatsapp_opt_in := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'whatsapp_opt_in');
SET @sql_whatsapp_opt_in := IF(@has_whatsapp_opt_in = 0, 'ALTER TABLE bookings ADD COLUMN whatsapp_opt_in TINYINT(1) NOT NULL DEFAULT 0 AFTER phone', 'SELECT 1');
PREPARE stmt_whatsapp_opt_in FROM @sql_whatsapp_opt_in;
EXECUTE stmt_whatsapp_opt_in;
DEALLOCATE PREPARE stmt_whatsapp_opt_in;

CREATE TABLE IF NOT EXISTS audit_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    booking_id INT NULL,
    action VARCHAR(80) NOT NULL,
    entity_type VARCHAR(50) NULL,
    entity_id BIGINT NULL,
    description VARCHAR(500) NOT NULL,
    metadata TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_booking_created (booking_id, created_at),
    INDEX idx_audit_user_created (user_id, created_at),
    INDEX idx_audit_action_created (action, created_at),
    CONSTRAINT fk_audit_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    CONSTRAINT fk_audit_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS notification_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NULL,
    channel ENUM('email','sms','whatsapp') NOT NULL,
    recipient VARCHAR(190) NOT NULL,
    event_key VARCHAR(80) NOT NULL,
    status ENUM('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
    provider VARCHAR(60) NULL,
    provider_message_id VARCHAR(190) NULL,
    error_message VARCHAR(500) NULL,
    payload TEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    sent_at TIMESTAMP NULL,
    INDEX idx_notification_booking_created (booking_id, created_at),
    INDEX idx_notification_status_created (status, created_at),
    INDEX idx_notification_event_created (event_key, created_at),
    CONSTRAINT fk_notification_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add settings only when the exact setting does not already exist.
INSERT IGNORE INTO settings (setting_group, setting_key, setting_value)
VALUES
('notifications', 'whatsapp_provider', 'callmebot'),
('notifications', 'whatsapp_customer_enabled', '0'),
('notifications', 'whatsapp_template_booking_received', 'booking_received'),
('notifications', 'whatsapp_template_booking_confirmed', 'booking_confirmed'),
('notifications', 'whatsapp_template_payment_received', 'payment_received'),
('notifications', 'whatsapp_template_pickup_reminder', 'pickup_reminder'),
('notifications', 'whatsapp_template_admin_new_booking', 'admin_new_booking'),
('notifications', 'whatsapp_template_admin_payment_received', 'admin_payment_received'),
('notifications', 'whatsapp_template_admin_status_changed', 'admin_status_changed');

SELECT 'Phase 3 operations migration complete.' AS status;
