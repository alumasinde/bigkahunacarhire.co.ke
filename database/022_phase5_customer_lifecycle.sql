-- Phase 5: customer lifecycle, secure guest booking links and reminder automation.
-- Idempotent so a deployment interrupted after ALTER TABLE can be safely retried.
SET @db := DATABASE();

SET @has_public_token_hash := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='bookings' AND COLUMN_NAME='public_token_hash');
SET @sql_public_token_hash := IF(@has_public_token_hash=0,
  'ALTER TABLE bookings ADD COLUMN public_token_hash CHAR(64) NULL AFTER booking_ref',
  'SELECT 1');
PREPARE stmt_public_token_hash FROM @sql_public_token_hash; EXECUTE stmt_public_token_hash; DEALLOCATE PREPARE stmt_public_token_hash;

SET @has_public_token_created_at := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='bookings' AND COLUMN_NAME='public_token_created_at');
SET @sql_public_token_created_at := IF(@has_public_token_created_at=0,
  'ALTER TABLE bookings ADD COLUMN public_token_created_at DATETIME NULL AFTER public_token_hash',
  'SELECT 1');
PREPARE stmt_public_token_created_at FROM @sql_public_token_created_at; EXECUTE stmt_public_token_created_at; DEALLOCATE PREPARE stmt_public_token_created_at;

SET @has_public_token_index := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='bookings' AND INDEX_NAME='uniq_booking_public_token'
);
SET @sql_public_token_index := IF(@has_public_token_index=0,
  'CREATE UNIQUE INDEX uniq_booking_public_token ON bookings(public_token_hash)',
  'SELECT 1');
PREPARE stmt_public_token_index FROM @sql_public_token_index; EXECUTE stmt_public_token_index; DEALLOCATE PREPARE stmt_public_token_index;

INSERT INTO settings (setting_group, setting_key, setting_value) VALUES
('notifications','whatsapp_template_payment_due','payment_due'),
('notifications','whatsapp_template_return_reminder','return_reminder'),
('notifications','whatsapp_template_rental_completed','rental_completed'),
('notifications','whatsapp_template_review_request','review_request'),
('notifications','whatsapp_template_admin_payment_due','admin_payment_due'),
('notifications','whatsapp_payment_due_enabled','1'),
('notifications','whatsapp_return_reminders_enabled','1'),
('notifications','whatsapp_post_rental_enabled','1'),
('notifications','whatsapp_payment_due_hours','2'),
('notifications','whatsapp_return_reminder_hours','4'),
('notifications','whatsapp_review_delay_hours','24')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);

SELECT 'Phase 5 customer lifecycle migration complete.' AS status;
