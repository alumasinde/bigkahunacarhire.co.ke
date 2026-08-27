-- =========================================================
-- Migration: customer self-service accounts, admin reply-to-message,
-- and email/SMS notification settings.
--
-- Run this ONCE against your already-deployed database (the one that
-- already has database/002_migrate-terms-license.sql applied):
--
--   php bin/migrate.php
--   (or manually: mysql -u your_db_user -p your_db_name < database/003_migrate-notifications-accounts.sql)
--
-- Back up your database before running.
-- =========================================================

-- 1. Customers table (self-service portal accounts)
CREATE TABLE IF NOT EXISTS customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL UNIQUE,
    email VARCHAR(150) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    must_change_password TINYINT(1) NOT NULL DEFAULT 1,
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. Link bookings to a customer account
SET @has_customer_id := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'bookings' AND COLUMN_NAME = 'customer_id'
);
SET @sql := IF(@has_customer_id = 0,
    'ALTER TABLE bookings ADD COLUMN customer_id INT DEFAULT NULL AFTER user_id, ADD FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL',
    'SELECT "customer_id already exists on bookings, skipping" AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 3. Historic bookings placed before this migration are left with
--    customer_id = NULL — deliberately NOT auto-creating "phantom" customer
--    accounts here, since we have no real password to give them and a fake
--    placeholder hash would permanently lock them out of that phone number.
--    They'll get a real account automatically the next time they book.

-- 4. Admin reply fields on contact_messages
SET @has_admin_reply := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contact_messages' AND COLUMN_NAME = 'admin_reply'
);
SET @sql := IF(@has_admin_reply = 0,
    'ALTER TABLE contact_messages
        ADD COLUMN admin_reply TEXT DEFAULT NULL,
        ADD COLUMN replied_by INT DEFAULT NULL,
        ADD COLUMN replied_at TIMESTAMP NULL DEFAULT NULL,
        ADD FOREIGN KEY (replied_by) REFERENCES users(id) ON DELETE SET NULL',
    'SELECT "admin_reply already exists on contact_messages, skipping" AS notice'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- 5. Notification settings
INSERT IGNORE INTO settings (setting_group, setting_key, setting_value) VALUES
('notifications', 'email_enabled', '1'),
('notifications', 'sms_enabled', '1'),
('notifications', 'admin_notification_email', 'admin@bigkahunacarhire.co.ke'),
('notifications', 'admin_notification_phone', '254700000000');

SELECT 'Migration complete. IMPORTANT: update admin_notification_email/phone in Admin -> Settings -> Notifications, and configure MAIL_* / AT_* in .env.' AS status;
