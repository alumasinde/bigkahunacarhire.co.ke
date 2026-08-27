-- =========================================================
-- Phase 5: Manual M-Pesa payment fallback
--
-- This does NOT fake an STK Push. It gives the site a real manual
-- M-Pesa flow while Daraja credentials are unavailable:
-- customer sends the deposit to the configured business number,
-- enters the M-Pesa transaction code, and staff verifies it.
--
-- Daraja STK Push remains available when valid .env credentials exist.
-- =========================================================

SET @has_payment_method := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'payment_method'
);
SET @sql := IF(@has_payment_method = 0,
    "ALTER TABLE payments ADD COLUMN payment_method ENUM('stk','manual') NOT NULL DEFAULT 'stk' AFTER booking_id",
    'SELECT "payment_method already exists, skipping" AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_manual_recipient := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'manual_recipient'
);
SET @sql := IF(@has_manual_recipient = 0,
    "ALTER TABLE payments ADD COLUMN manual_recipient VARCHAR(40) DEFAULT NULL AFTER phone",
    'SELECT "manual_recipient already exists, skipping" AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_manual_verified_by := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'manual_verified_by'
);
SET @sql := IF(@has_manual_verified_by = 0,
    "ALTER TABLE payments ADD COLUMN manual_verified_by INT DEFAULT NULL AFTER raw_callback",
    'SELECT "manual_verified_by already exists, skipping" AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @has_manual_verified_at := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND COLUMN_NAME = 'manual_verified_at'
);
SET @sql := IF(@has_manual_verified_at = 0,
    "ALTER TABLE payments ADD COLUMN manual_verified_at DATETIME DEFAULT NULL AFTER manual_verified_by",
    'SELECT "manual_verified_at already exists, skipping" AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

INSERT IGNORE INTO settings (setting_group, setting_key, setting_value) VALUES
('mpesa', 'manual_enabled', '1'),
('mpesa', 'manual_recipient_phone', '254700000000'),
('mpesa', 'manual_recipient_name', 'Big Kahuna Car Hire'),
('mpesa', 'manual_instructions', 'Open M-Pesa, choose Send Money, send the exact deposit amount to the configured Big Kahuna number, then enter the M-Pesa transaction code below. Your payment remains pending until our team verifies the transaction.'),
('mpesa', 'stk_enabled', '1');

SELECT 'Manual M-Pesa fallback migration complete.' AS status;
