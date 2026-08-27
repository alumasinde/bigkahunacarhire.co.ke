-- =========================================================
-- Migration: chauffeur ("with driver") pricing.
--
-- Adds three layers, checked in order when a booking picks the
-- "With Chauffeur" option:
--   1. cars.chauffeur_fee_per_day   — a specific override for one car
--   2. chauffeur_rates              — a rate per car location (e.g.
--                                      Nairobi vs Mombasa)
--   3. settings.general.default_chauffeur_fee_per_day — sitewide fallback
--
-- Run via: php bin/migrate.php
-- (or manually: mysql -u your_db_user -p your_db_name < database/004_chauffeur_pricing.sql)
--
-- Safe to re-run: uses IF NOT EXISTS / information_schema guards.
-- Back up your database before running.
-- =========================================================

-- 1. Per-car override
SET @has_chauffeur_fee := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'cars' AND COLUMN_NAME = 'chauffeur_fee_per_day'
);
SET @sql := IF(@has_chauffeur_fee = 0,
    'ALTER TABLE cars ADD COLUMN chauffeur_fee_per_day DECIMAL(10,2) DEFAULT NULL AFTER price_per_day',
    'SELECT "chauffeur_fee_per_day already exists on cars, skipping" AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 2. Per-location rates (location is free-text on cars, so this is keyed
--    on that same string rather than a foreign key)
CREATE TABLE IF NOT EXISTS chauffeur_rates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    location VARCHAR(120) NOT NULL UNIQUE,
    rate_per_day DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. Sitewide fallback default (editable afterwards in Admin -> Settings
--    -> General), used only when a car has no override and its location
--    has no rate configured either.
INSERT IGNORE INTO settings (setting_group, setting_key, setting_value) VALUES
('general', 'default_chauffeur_fee_per_day', '2000');

SELECT 'Migration complete. Set per-location rates in Admin -> Chauffeur Rates, or a per-car override on any car''s edit page.' AS status;
