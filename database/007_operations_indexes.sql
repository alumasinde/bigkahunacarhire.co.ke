-- =========================================================
-- Phase 6: Operations indexes
-- Safe to re-run.
-- =========================================================

SET @schema := DATABASE();

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='bookings' AND INDEX_NAME='idx_bookings_car_dates')=0,
  'ALTER TABLE bookings ADD INDEX idx_bookings_car_dates (car_id, pickup_date, return_date)',
  'SELECT "idx_bookings_car_dates already exists" AS notice'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='bookings' AND INDEX_NAME='idx_bookings_status_pickup')=0,
  'ALTER TABLE bookings ADD INDEX idx_bookings_status_pickup (status, pickup_date)',
  'SELECT "idx_bookings_status_pickup already exists" AS notice'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='bookings' AND INDEX_NAME='idx_bookings_customer')=0,
  'ALTER TABLE bookings ADD INDEX idx_bookings_customer (customer_id)',
  'SELECT "idx_bookings_customer already exists" AS notice'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='bookings' AND INDEX_NAME='idx_bookings_created')=0,
  'ALTER TABLE bookings ADD INDEX idx_bookings_created (created_at)',
  'SELECT "idx_bookings_created already exists" AS notice'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_booking_status')=0,
  'ALTER TABLE payments ADD INDEX idx_payments_booking_status (booking_id, status)',
  'SELECT "idx_payments_booking_status already exists" AS notice'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql := IF(
  (SELECT COUNT(*) FROM information_schema.STATISTICS
   WHERE TABLE_SCHEMA=@schema AND TABLE_NAME='payments' AND INDEX_NAME='idx_payments_manual_review')=0,
  'ALTER TABLE payments ADD INDEX idx_payments_manual_review (payment_method, status, mpesa_receipt_number)',
  'SELECT "idx_payments_manual_review already exists" AS notice'
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SELECT 'Phase 6 operations indexes ready.' AS status;
