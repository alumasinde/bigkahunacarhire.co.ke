-- =========================================================
-- Paystack InlineJS Popup V2: server initialize + resumeTransaction
-- Adds the access code returned by Paystack initialization.
-- Safe to run more than once.
-- =========================================================

SET @db := DATABASE();

SET @sql := IF(
    EXISTS (SELECT 1 FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND COLUMN_NAME='paystack_access_code'),
    'SELECT 1',
    'ALTER TABLE payments ADD COLUMN paystack_access_code VARCHAR(120) DEFAULT NULL AFTER authorization_url'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Paystack resumeTransaction migration complete.' AS status;
