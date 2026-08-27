-- Big Kahuna: distinguish deposit vs balance payments and improve receipts.
SET @db := DATABASE();

SET @sql := IF(
  EXISTS (SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA=@db AND TABLE_NAME='payments' AND COLUMN_NAME='payment_purpose'),
  'SELECT 1',
  'ALTER TABLE payments ADD COLUMN payment_purpose VARCHAR(30) NOT NULL DEFAULT ''deposit'' AFTER channel'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE payments
SET payment_purpose = CASE
  WHEN reference LIKE 'KAHUNA-BAL-%' THEN 'balance'
  ELSE 'deposit'
END
WHERE payment_purpose IS NULL OR payment_purpose = '' OR payment_purpose = 'deposit';

SELECT 'Payment purpose migration complete.' AS status;
