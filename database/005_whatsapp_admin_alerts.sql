-- =========================================================
-- Migration: WhatsApp admin alerts (new booking / payment received),
-- sent via CallMeBot's free personal API. See app/Services/WhatsAppService.php
-- for the one-time setup steps.
--
-- Run via: php bin/migrate.php
-- (or manually: mysql -u your_db_user -p your_db_name < database/005_whatsapp_admin_alerts.sql)
--
-- Safe to re-run: uses INSERT IGNORE.
-- =========================================================

INSERT IGNORE INTO settings (setting_group, setting_key, setting_value) VALUES
('notifications', 'whatsapp_enabled', '0'),
('notifications', 'admin_whatsapp_phone', '254700000000');

SELECT 'Migration complete. Set CALLMEBOT_APIKEY in .env, fill in the admin''s number in Admin -> Settings -> Notifications, then switch Whatsapp Enabled to Enabled.' AS status;
