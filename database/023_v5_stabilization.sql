-- Big Kahuna V5 stabilization / audit fixes.
-- Safe and idempotent: every schema change checks for the existing result first.

SET @db := DATABASE();

-- Persist the separate damage acknowledgement collected by the booking form.
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

-- SEO management is used by SeoController and must exist in the RBAC seed.
INSERT INTO permissions (name, description)
VALUES ('seo.manage', 'Create, edit and delete SEO landing pages')
ON DUPLICATE KEY UPDATE description=VALUES(description);

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r CROSS JOIN permissions p
WHERE r.name IN ('super_admin','manager') AND p.name='seo.manage'
  AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id=r.id AND rp.permission_id=p.id);

-- Meta can retry a webhook. Provider message IDs are globally unique, so
-- protect the inbox from duplicate inbound rows.
SET @has_wa_provider_unique := (
  SELECT COUNT(*) FROM information_schema.STATISTICS
  WHERE TABLE_SCHEMA=@db AND TABLE_NAME='whatsapp_messages' AND INDEX_NAME='uq_wa_provider_message'
);
SET @sql_wa_provider_unique := IF(@has_wa_provider_unique=0,
  'ALTER TABLE whatsapp_messages ADD UNIQUE KEY uq_wa_provider_message (provider_message_id)',
  'SELECT 1');
PREPARE stmt_wa_provider_unique FROM @sql_wa_provider_unique; EXECUTE stmt_wa_provider_unique; DEALLOCATE PREPARE stmt_wa_provider_unique;

SELECT 'V5 stabilization migration complete.' AS status;
