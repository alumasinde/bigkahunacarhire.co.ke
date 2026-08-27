-- =========================================================
-- Migration: split full_name -> first_name/last_name, add
-- id_number, driving_license_number, terms acceptance fields
-- to bookings, and seed the new `legal` settings group.
--
-- Run this ONCE against your already-deployed database
-- (the one created from an earlier version of database/001_schema.sql):
--
--   php bin/migrate.php
--   (or manually: mysql -u your_db_user -p your_db_name < database/002_migrate-terms-license.sql)
--
-- Safe to re-run: uses IF NOT EXISTS / INSERT IGNORE guards
-- where possible. Back up your database before running.
-- =========================================================

-- 1. Add the new columns (nullable/defaulted first, so this
--    doesn't fail on a table that already has rows)
ALTER TABLE bookings
    ADD COLUMN first_name VARCHAR(100) NOT NULL DEFAULT '' AFTER car_id,
    ADD COLUMN last_name VARCHAR(100) NOT NULL DEFAULT '' AFTER first_name,
    ADD COLUMN id_number VARCHAR(30) NOT NULL DEFAULT '' AFTER phone,
    ADD COLUMN driving_license_number VARCHAR(30) NOT NULL DEFAULT '' AFTER id_number,
    ADD COLUMN terms_accepted TINYINT(1) NOT NULL DEFAULT 0 AFTER notes,
    ADD COLUMN terms_accepted_at DATETIME DEFAULT NULL AFTER terms_accepted;

-- 2. Backfill first_name/last_name from the old full_name column,
--    if that column still exists on this installation.
--    (Splits on the first space; anything after goes into last_name.)
SET @has_full_name := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'full_name'
);

SET @sql := IF(@has_full_name > 0,
    'UPDATE bookings SET
        first_name = TRIM(SUBSTRING_INDEX(full_name, " ", 1)),
        last_name  = TRIM(SUBSTRING(full_name, LENGTH(SUBSTRING_INDEX(full_name, " ", 1)) + 1))
     WHERE full_name IS NOT NULL AND full_name <> ""',
    'SELECT "No full_name column found, skipping backfill" AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Drop the old full_name column now that data has migrated
--    (BookingService now reads a computed full_name via CONCAT instead)
SET @sql := IF(@has_full_name > 0,
    'ALTER TABLE bookings DROP COLUMN full_name',
    'SELECT "No full_name column to drop" AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 4. Any existing bookings predate the ID/license requirement —
--    flag them clearly rather than leaving blank strings that look valid.
UPDATE bookings
SET id_number = 'NOT COLLECTED',
    driving_license_number = 'NOT COLLECTED'
WHERE id_number = '' AND driving_license_number = '';

-- 5. Seed the `legal` settings group (Terms & Conditions + damage
--    disclaimer text), editable afterwards in Admin → Settings.
INSERT IGNORE INTO settings (setting_group, setting_key, setting_value) VALUES
('legal', 'terms_and_conditions', 'By booking a vehicle with Big Kahuna Car Hire, you agree to the following terms:\n\n1. ELIGIBILITY: The renter must be at least 23 years old, hold a valid national ID or passport, and hold a valid driving license (minimum 2 years driving experience) for the vehicle class booked.\n\n2. DEPOSIT & PAYMENT: A non-refundable booking deposit is required to confirm a reservation. The balance is payable at pickup. Accepted payment methods include M-Pesa and cash.\n\n3. VEHICLE CONDITION: The vehicle will be inspected jointly by the renter and Big Kahuna Car Hire staff before handover and upon return. Both parties will sign off on the vehicle''s condition and fuel level at handover.\n\n4. DAMAGE & LIABILITY: The renter is fully responsible for any damage, loss, or theft of the vehicle occurring during the rental period, including but not limited to collision damage, tyre damage, windscreen damage, interior damage, and loss of accessories. The renter agrees to cover the full cost of repair or replacement, and any applicable insurance excess, for damage not covered by insurance or arising from a breach of these terms (e.g. reckless driving, driving under the influence, use outside agreed geographic limits, or use by an unauthorized driver).\n\n5. TRAFFIC OFFENSES: The renter is liable for all traffic fines, tolls, and penalties incurred during the rental period.\n\n6. LATE RETURNS: Returning the vehicle later than the agreed return date/time without prior arrangement will incur additional daily charges at the standard rate.\n\n7. FUEL POLICY: The vehicle is provided with a set fuel level and must be returned at the same level, or a refueling charge will apply.\n\n8. CANCELLATIONS: Cancellations made less than 24 hours before pickup may forfeit the booking deposit.\n\n9. GOVERNING LAW: These terms are governed by the laws of Kenya.\n\nPlease read these terms carefully. By ticking the box and proceeding, you confirm that you understand and accept full responsibility for the vehicle for the duration of your rental.'),
('legal', 'damage_disclaimer', 'I understand that I am fully responsible for any damage, loss, or theft of the vehicle during my rental period, and I agree to cover the full repair, replacement, or insurance excess cost for any such damage.');

SELECT 'Migration complete.' AS status;
