-- Big Kahuna V5 stabilization / audit fixes.
-- Production-safe and idempotent. Existing data and existing setting/permission values are never changed.

SET @db := DATABASE();

SET @has_damage_accepted := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='bookings' AND COLUMN_NAME='damage_accepted');
SET @sql_damage_accepted := IF(@has_damage_accepted=0,
  'ALTER TABLE bookings ADD COLUMN damage_accepted TINYINT(1) NOT NULL DEFAULT 0 AFTER terms_accepted_at',
  'SELECT 1');
PREPARE stmt_damage_accepted FROM @sql_damage_accepted; EXECUTE stmt_damage_accepted; DEALLOCATE PREPARE stmt_damage_accepted;

SET @has_damage_accepted_at := (SELECT COUNT(*) FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='bookings' AND COLUMN_NAME='damage_accepted_at');
SET @sql_damage_accepted_at := IF(@has_damage_accepted_at=0,
  'ALTER TABLE bookings ADD COLUMN damage_accepted_at DATETIME NULL AFTER damage_accepted',
  'SELECT 1');
PREPARE stmt_damage_accepted_at FROM @sql_damage_accepted_at; EXECUTE stmt_damage_accepted_at; DEALLOCATE PREPARE stmt_damage_accepted_at;

INSERT IGNORE INTO permissions (name, description)
VALUES ('seo.manage', 'Create, edit and delete SEO landing pages');

INSERT IGNORE INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id
FROM roles r CROSS JOIN permissions p
WHERE r.name IN ('super_admin','manager') AND p.name='seo.manage';

SET @has_wa_provider_unique := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='whatsapp_messages' AND INDEX_NAME='uq_wa_provider_message'
);
SET @sql_wa_provider_unique := IF(@has_wa_provider_unique=0,
  'ALTER TABLE whatsapp_messages ADD UNIQUE KEY uq_wa_provider_message (provider_message_id)',
  'SELECT 1');
PREPARE stmt_wa_provider_unique FROM @sql_wa_provider_unique; EXECUTE stmt_wa_provider_unique; DEALLOCATE PREPARE stmt_wa_provider_unique;

SELECT 'V5 stabilization migration complete.' AS status;
