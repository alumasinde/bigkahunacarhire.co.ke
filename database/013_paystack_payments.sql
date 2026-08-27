-- =========================================================
-- Paystack online payments for Big Kahuna Car Hire
-- Keeps existing Daraja STK/manual M-Pesa records.
-- =========================================================

SET @db := DATABASE();

SET @sql := (
    SELECT IF(
        EXISTS (
            SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments'
              AND COLUMN_NAME='payment_method'
              AND COLUMN_TYPE LIKE 'enum%'
        ),
        'ALTER TABLE payments MODIFY COLUMN payment_method VARCHAR(30) NOT NULL DEFAULT ''stk''',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND COLUMN_NAME='gateway'),
    'SELECT 1',
    'ALTER TABLE payments ADD COLUMN gateway VARCHAR(30) DEFAULT NULL AFTER payment_method'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND COLUMN_NAME='channel'),
    'SELECT 1',
    'ALTER TABLE payments ADD COLUMN channel VARCHAR(50) DEFAULT NULL AFTER gateway'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND COLUMN_NAME='reference'),
    'SELECT 1',
    'ALTER TABLE payments ADD COLUMN reference VARCHAR(120) DEFAULT NULL AFTER channel'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    EXISTS (SELECT 1 FROM information_schema.STATISTICS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND INDEX_NAME='uq_payments_reference'),
    'SELECT 1',
    'ALTER TABLE payments ADD UNIQUE KEY uq_payments_reference (reference)'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND COLUMN_NAME='gateway_transaction_id'),
    'SELECT 1',
    'ALTER TABLE payments ADD COLUMN gateway_transaction_id VARCHAR(120) DEFAULT NULL AFTER reference'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND COLUMN_NAME='gateway_response'),
    'SELECT 1',
    'ALTER TABLE payments ADD COLUMN gateway_response VARCHAR(255) DEFAULT NULL AFTER result_desc'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND COLUMN_NAME='customer_email'),
    'SELECT 1',
    'ALTER TABLE payments ADD COLUMN customer_email VARCHAR(190) DEFAULT NULL AFTER phone'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND COLUMN_NAME='authorization_url'),
    'SELECT 1',
    'ALTER TABLE payments ADD COLUMN authorization_url TEXT DEFAULT NULL AFTER gateway_transaction_id'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND COLUMN_NAME='metadata'),
    'SELECT 1',
    'ALTER TABLE payments ADD COLUMN metadata JSON DEFAULT NULL AFTER authorization_url'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE payments
SET gateway = CASE
    WHEN payment_method = 'manual' THEN 'mpesa'
    WHEN payment_method = 'stk' THEN 'daraja'
    ELSE gateway
END
WHERE gateway IS NULL;

SELECT 'Paystack payment migration complete.' AS status;
