-- --------------------------------------------------------
-- Host:                         127.0.0.1
-- Server version:               12.3.2-MariaDB - MariaDB Server
-- Server OS:                    Win64
-- HeidiSQL Version:             12.15.0.7171
-- --------------------------------------------------------

/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET NAMES utf8 */;
/*!50503 SET NAMES utf8mb4 */;
/*!40103 SET @OLD_TIME_ZONE=@@TIME_ZONE */;
/*!40103 SET TIME_ZONE='+00:00' */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;

-- Dumping structure for table bigkahuna_carhire_db.audit_logs
CREATE TABLE IF NOT EXISTS `audit_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `action` varchar(80) NOT NULL,
  `entity_type` varchar(50) DEFAULT NULL,
  `entity_id` bigint(20) DEFAULT NULL,
  `description` varchar(500) NOT NULL,
  `metadata` text DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `idx_audit_booking_created` (`booking_id`,`created_at`),
  KEY `idx_audit_user_created` (`user_id`,`created_at`),
  KEY `idx_audit_action_created` (`action`,`created_at`),
  CONSTRAINT `fk_audit_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_audit_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=6 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bigkahuna_carhire_db.audit_logs: ~4 rows (approximately)
INSERT IGNORE INTO `audit_logs` (`id`, `user_id`, `booking_id`, `action`, `entity_type`, `entity_id`, `description`, `metadata`, `ip_address`, `created_at`) VALUES
	(1, 1, NULL, 'booking.created', 'booking', 1, 'New booking request created.', '{"booking_ref":"BK-51F04763"}', '127.0.0.1', '2026-08-25 08:37:05'),
	(2, 1, NULL, 'booking.status_changed', 'booking', 1, 'Booking status changed to Confirmed.', '{"status":"confirmed"}', '127.0.0.1', '2026-08-25 08:38:09'),
	(3, 1, NULL, 'rental.checked_out', 'booking', 1, 'Vehicle checked out and rental started.', NULL, '127.0.0.1', '2026-08-25 08:53:53'),
	(4, 1, NULL, 'booking.created', 'booking', 2, 'New booking request created.', '{"booking_ref":"BK-2F267DF7"}', '127.0.0.1', '2026-08-25 09:17:29'),
	(5, 1, 3, 'booking.created', 'booking', 3, 'New booking request created.', '{"booking_ref":"BK-9D40290F"}', '127.0.0.1', '2026-08-25 17:09:53');

-- Dumping structure for table bigkahuna_carhire_db.bookings
CREATE TABLE IF NOT EXISTS `bookings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_ref` varchar(20) NOT NULL,
  `public_token_hash` char(64) DEFAULT NULL,
  `public_token_created_at` datetime DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `customer_id` int(11) DEFAULT NULL,
  `car_id` int(11) NOT NULL,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `id_number` varchar(30) NOT NULL,
  `driving_license_number` varchar(30) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) NOT NULL,
  `whatsapp_opt_in` tinyint(1) NOT NULL DEFAULT 0,
  `pickup_location` varchar(150) NOT NULL,
  `dropoff_location` varchar(150) NOT NULL,
  `pickup_date` datetime NOT NULL,
  `return_date` datetime NOT NULL,
  `driver_option` enum('self_drive','with_driver') NOT NULL DEFAULT 'self_drive',
  `total_days` int(11) NOT NULL DEFAULT 1,
  `total_price` decimal(10,2) NOT NULL,
  `status` enum('pending','confirmed','ongoing','completed','cancelled') NOT NULL DEFAULT 'pending',
  `notes` text DEFAULT NULL,
  `terms_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `terms_accepted_at` datetime DEFAULT NULL,
  `damage_accepted` tinyint(1) NOT NULL DEFAULT 0,
  `damage_accepted_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  `checkout_at` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `actual_pickup_at` datetime DEFAULT NULL,
  `actual_return_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `booking_ref` (`booking_ref`),
  UNIQUE KEY `uniq_booking_public_token` (`public_token_hash`),
  KEY `user_id` (`user_id`),
  KEY `idx_bookings_car_dates` (`car_id`,`pickup_date`,`return_date`),
  KEY `idx_bookings_status_pickup` (`status`,`pickup_date`),
  KEY `idx_bookings_customer` (`customer_id`),
  KEY `idx_bookings_created` (`created_at`),
  CONSTRAINT `1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`),
  CONSTRAINT `2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `3` FOREIGN KEY (`customer_id`) REFERENCES `customers` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.bookings: ~1 rows (approximately)
INSERT IGNORE INTO `bookings` (`id`, `booking_ref`, `public_token_hash`, `public_token_created_at`, `user_id`, `customer_id`, `car_id`, `first_name`, `last_name`, `id_number`, `driving_license_number`, `email`, `phone`, `whatsapp_opt_in`, `pickup_location`, `dropoff_location`, `pickup_date`, `return_date`, `driver_option`, `total_days`, `total_price`, `status`, `notes`, `terms_accepted`, `terms_accepted_at`, `damage_accepted`, `damage_accepted_at`, `created_at`, `updated_at`, `checkout_at`, `returned_at`, `actual_pickup_at`, `actual_return_at`) VALUES
	(3, 'BK-9D40290F', 'bb9328fa55ef8ecd0f9fb6f9de25dc177ce7c7ae7f053f9a37ee5f85dc72c2e5', '2026-08-25 20:09:53', 1, NULL, 2, 'Amos', 'Masinde', '42334872', 'DL-SDGEY', 'alumasinde@gmail.com', '254725034005', 1, 'JKIA, Nairobi', 'Westlands', '2026-08-25 21:08:00', '2026-08-30 20:08:00', 'self_drive', 5, 60000.00, 'confirmed', '', 1, '2026-08-25 20:09:53', 1, '2026-08-25 20:09:53', '2026-08-25 17:09:53', '2026-08-25 17:11:01', NULL, NULL, NULL, NULL);

-- Dumping structure for table bigkahuna_carhire_db.car_categories
CREATE TABLE IF NOT EXISTS `car_categories` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `slug` varchar(120) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`),
  UNIQUE KEY `slug` (`slug`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.car_categories: ~4 rows (approximately)
INSERT IGNORE INTO `car_categories` (`id`, `name`, `slug`, `description`) VALUES
	(1, 'Economy', 'economy', 'Fuel-efficient cars for city driving'),
	(2, 'SUV', 'suv', '4x4 and SUVs for family and off-road trips'),
	(3, 'Luxury', 'luxury', 'Premium sedans for business and events'),
	(4, 'Van & Minibus', 'van-minibus', 'Group and safari transport');

-- Dumping structure for table bigkahuna_carhire_db.car_images
CREATE TABLE IF NOT EXISTS `car_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `image_path` varchar(255) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  KEY `car_id` (`car_id`),
  CONSTRAINT `1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.car_images: ~0 rows (approximately)

-- Dumping structure for table bigkahuna_carhire_db.cars
CREATE TABLE IF NOT EXISTS `cars` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `category_id` int(11) DEFAULT NULL,
  `name` varchar(150) NOT NULL,
  `slug` varchar(180) NOT NULL,
  `brand` varchar(80) NOT NULL,
  `model` varchar(80) NOT NULL,
  `year` year(4) DEFAULT NULL,
  `transmission` enum('automatic','manual') NOT NULL DEFAULT 'automatic',
  `fuel_type` enum('petrol','diesel','hybrid','electric') NOT NULL DEFAULT 'petrol',
  `seats` tinyint(3) unsigned NOT NULL DEFAULT 4,
  `doors` tinyint(3) unsigned NOT NULL DEFAULT 4,
  `price_per_day` decimal(10,2) NOT NULL,
  `chauffeur_fee_per_day` decimal(10,2) DEFAULT NULL,
  `plate_number` varchar(20) DEFAULT NULL,
  `location` varchar(120) DEFAULT 'Nairobi',
  `description` text DEFAULT NULL,
  `image_path` varchar(255) DEFAULT NULL,
  `status` enum('available','booked','maintenance','retired') NOT NULL DEFAULT 'available',
  `featured` tinyint(1) NOT NULL DEFAULT 0,
  `meta_title` varchar(255) DEFAULT NULL,
  `meta_description` varchar(500) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `slug` (`slug`),
  KEY `category_id` (`category_id`),
  CONSTRAINT `1` FOREIGN KEY (`category_id`) REFERENCES `car_categories` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.cars: ~6 rows (approximately)
INSERT IGNORE INTO `cars` (`id`, `category_id`, `name`, `slug`, `brand`, `model`, `year`, `transmission`, `fuel_type`, `seats`, `doors`, `price_per_day`, `chauffeur_fee_per_day`, `plate_number`, `location`, `description`, `image_path`, `status`, `featured`, `meta_title`, `meta_description`, `created_at`, `updated_at`) VALUES
	(1, 1, 'Toyota Vitz', 'toyota-vitz', 'Toyota', 'Vitz', '2019', 'automatic', 'petrol', 4, 4, 3500.00, NULL, 'KDA 101A', 'Nairobi', 'Compact and fuel-efficient, perfect for city driving and errands.', '/assets/images/cars/vitz.jpg', 'available', 1, 'Hire a Toyota Vitz in Nairobi | Big Kahuna Car Hire', 'Affordable Toyota Vitz self-drive hire in Nairobi from KES 3,500/day.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(2, 2, 'Toyota Land Cruiser Prado', 'toyota-prado', 'Toyota', 'Prado', '2021', 'automatic', 'diesel', 7, 5, 12000.00, NULL, 'KDB 202B', 'Nairobi', 'Rugged and comfortable 4x4, ideal for safaris and family trips.', '/assets/images/cars/prado.jpg', 'booked', 1, 'Toyota Prado Hire Kenya | Big Kahuna Car Hire', 'Hire a Toyota Land Cruiser Prado for safaris and off-road travel in Kenya.', '2026-08-25 07:58:39', '2026-08-25 08:53:53'),
	(3, 3, 'Mercedes-Benz E-Class', 'mercedes-e-class', 'Mercedes-Benz', 'E-Class', '2020', 'automatic', 'petrol', 5, 4, 18000.00, NULL, 'KDC 303C', 'Nairobi', 'Premium sedan for executive travel, weddings and events.', '/assets/images/cars/e-class.jpg', 'available', 1, 'Mercedes E-Class Hire Nairobi | Big Kahuna Car Hire', 'Book a Mercedes-Benz E-Class with chauffeur for executive travel in Nairobi.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(4, 4, 'Toyota Hiace', 'toyota-hiace', 'Toyota', 'Hiace', '2018', 'manual', 'diesel', 14, 4, 9000.00, NULL, 'KDD 404D', 'Nairobi', 'Spacious minibus for group travel, tours and airport transfers.', '/assets/images/cars/hiace.jpg', 'available', 0, 'Toyota Hiace Van Hire Kenya | Big Kahuna Car Hire', 'Hire a 14-seater Toyota Hiace for group travel and tours in Kenya.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(5, 1, 'Nissan Note', 'nissan-note', 'Nissan', 'Note', '2020', 'automatic', 'petrol', 4, 4, 3800.00, NULL, 'KDE 505E', 'Mombasa', 'Reliable hatchback with great fuel economy for daily use.', '/assets/images/cars/note.jpg', 'available', 0, 'Nissan Note Hire Mombasa | Big Kahuna Car Hire', 'Affordable Nissan Note self-drive hire available in Mombasa.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(6, 2, 'Subaru Forester', 'subaru-forester', 'Subaru', 'Forester', '2019', 'automatic', 'petrol', 5, 5, 8000.00, NULL, 'KDF 606F', 'Nairobi', 'All-wheel-drive SUV, confident on tarmac and rough roads alike.', '/assets/images/cars/forester.jpg', 'available', 1, 'Subaru Forester Hire Nairobi | Big Kahuna Car Hire', 'Hire a Subaru Forester SUV for city and upcountry travel in Kenya.', '2026-08-25 07:58:39', '2026-08-25 07:58:39');

-- Dumping structure for table bigkahuna_carhire_db.chauffeur_rates
CREATE TABLE IF NOT EXISTS `chauffeur_rates` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `location` varchar(120) NOT NULL,
  `rate_per_day` decimal(10,2) NOT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `location` (`location`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.chauffeur_rates: ~0 rows (approximately)

-- Dumping structure for table bigkahuna_carhire_db.contact_messages
CREATE TABLE IF NOT EXISTS `contact_messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(150) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `subject` varchar(200) DEFAULT NULL,
  `message` text NOT NULL,
  `status` enum('new','read','replied') NOT NULL DEFAULT 'new',
  `admin_reply` text DEFAULT NULL,
  `replied_by` int(11) DEFAULT NULL,
  `replied_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `replied_by` (`replied_by`),
  CONSTRAINT `1` FOREIGN KEY (`replied_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.contact_messages: ~0 rows (approximately)

-- Dumping structure for table bigkahuna_carhire_db.customers
CREATE TABLE IF NOT EXISTS `customers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `email` varchar(150) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `must_change_password` tinyint(1) NOT NULL DEFAULT 1,
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.customers: ~0 rows (approximately)

-- Dumping structure for table bigkahuna_carhire_db.notification_claims
CREATE TABLE IF NOT EXISTS `notification_claims` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `channel` enum('email','sms','whatsapp') NOT NULL,
  `event_key` varchar(80) NOT NULL,
  `claimed_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_notification_claim` (`booking_id`,`channel`,`event_key`),
  KEY `idx_notification_claimed_at` (`claimed_at`),
  CONSTRAINT `fk_notification_claim_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bigkahuna_carhire_db.notification_claims: ~0 rows (approximately)

-- Dumping structure for table bigkahuna_carhire_db.notification_logs
CREATE TABLE IF NOT EXISTS `notification_logs` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) DEFAULT NULL,
  `channel` enum('email','sms','whatsapp') NOT NULL,
  `recipient` varchar(190) NOT NULL,
  `event_key` varchar(80) NOT NULL,
  `status` enum('queued','sent','failed','skipped') NOT NULL DEFAULT 'queued',
  `provider` varchar(60) DEFAULT NULL,
  `provider_message_id` varchar(190) DEFAULT NULL,
  `error_message` varchar(500) DEFAULT NULL,
  `payload` text DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `sent_at` timestamp NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_notification_booking_created` (`booking_id`,`created_at`),
  KEY `idx_notification_status_created` (`status`,`created_at`),
  KEY `idx_notification_event_created` (`event_key`,`created_at`),
  CONSTRAINT `fk_notification_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=29 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bigkahuna_carhire_db.notification_logs: ~28 rows (approximately)
INSERT IGNORE INTO `notification_logs` (`id`, `booking_id`, `channel`, `recipient`, `event_key`, `status`, `provider`, `provider_message_id`, `error_message`, `payload`, `created_at`, `sent_at`) VALUES
	(1, NULL, 'email', 'albertmasinde@outlook.com', 'booking_received', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 08:37:07', NULL),
	(2, NULL, 'sms', '254725034005', 'booking_received', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 08:37:07', NULL),
	(3, NULL, 'email', 'admin@bigkahunacarhire.co.ke', 'admin_new_booking', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 08:37:09', NULL),
	(4, NULL, 'sms', '254700000000', 'admin_new_booking', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 08:37:09', NULL),
	(5, NULL, 'email', 'albertmasinde@outlook.com', 'booking_confirmed', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 08:38:11', NULL),
	(6, NULL, 'sms', '254725034005', 'booking_confirmed', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 08:38:11', NULL),
	(7, NULL, 'email', 'albertmasinde@outlook.com', 'payment_received', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 08:53:17', NULL),
	(8, NULL, 'sms', '254725034005', 'payment_received', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 08:53:17', NULL),
	(9, NULL, 'email', 'admin@bigkahunacarhire.co.ke', 'admin_payment_received', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 08:53:19', NULL),
	(10, NULL, 'sms', '254700000000', 'admin_payment_received', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 08:53:19', NULL),
	(11, NULL, 'email', 'albertmasinde@outlook.com', 'booking_ongoing', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 08:53:55', NULL),
	(12, NULL, 'sms', '254725034005', 'booking_ongoing', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 08:53:55', NULL),
	(13, NULL, 'email', 'albertmasinde@outlook.com', 'booking_received', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 09:17:31', NULL),
	(14, NULL, 'sms', '254725034005', 'booking_received', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 09:17:31', NULL),
	(15, NULL, 'email', 'admin@bigkahunacarhire.co.ke', 'admin_new_booking', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 09:17:33', NULL),
	(16, NULL, 'sms', '254700000000', 'admin_new_booking', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 09:17:33', NULL),
	(17, NULL, 'email', 'albertmasinde@outlook.com', 'payment_received', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 09:17:59', NULL),
	(18, NULL, 'sms', '254725034005', 'payment_received', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 09:17:59', NULL),
	(19, NULL, 'email', 'admin@bigkahunacarhire.co.ke', 'admin_payment_received', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 09:18:01', NULL),
	(20, NULL, 'sms', '254700000000', 'admin_payment_received', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 09:18:01', NULL),
	(21, 3, 'email', 'alumasinde@gmail.com', 'booking_received', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 17:09:55', NULL),
	(22, 3, 'sms', '254725034005', 'booking_received', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 17:09:55', NULL),
	(23, 3, 'email', 'admin@bigkahunacarhire.co.ke', 'admin_new_booking', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 17:09:57', NULL),
	(24, 3, 'sms', '254700000000', 'admin_new_booking', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 17:09:57', NULL),
	(25, 3, 'email', 'alumasinde@gmail.com', 'payment_received', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 17:11:03', NULL),
	(26, 3, 'sms', '254725034005', 'payment_received', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 17:11:03', NULL),
	(27, 3, 'email', 'admin@bigkahunacarhire.co.ke', 'admin_payment_received', 'failed', 'mail', NULL, 'PHP mail() returned failure — check your server\'s mail transfer agent (MTA) configuration.', NULL, '2026-08-25 17:11:05', NULL),
	(28, 3, 'sms', '254700000000', 'admin_payment_received', 'failed', 'africastalking', NULL, 'SMS is not configured. Set AT_USERNAME / AT_API_KEY in .env (Africa\'s Talking).', NULL, '2026-08-25 17:11:05', NULL);

-- Dumping structure for table bigkahuna_carhire_db.payments
CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `payment_method` varchar(30) NOT NULL DEFAULT 'stk',
  `gateway` varchar(30) DEFAULT NULL,
  `channel` varchar(50) DEFAULT NULL,
  `payment_purpose` varchar(30) NOT NULL DEFAULT 'deposit',
  `reference` varchar(120) DEFAULT NULL,
  `gateway_transaction_id` varchar(120) DEFAULT NULL,
  `authorization_url` text DEFAULT NULL,
  `paystack_access_code` varchar(120) DEFAULT NULL,
  `metadata` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`metadata`)),
  `checkout_request_id` varchar(60) DEFAULT NULL,
  `merchant_request_id` varchar(60) DEFAULT NULL,
  `phone` varchar(20) NOT NULL,
  `customer_email` varchar(190) DEFAULT NULL,
  `manual_recipient` varchar(40) DEFAULT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
  `mpesa_receipt_number` varchar(30) DEFAULT NULL,
  `result_code` varchar(10) DEFAULT NULL,
  `result_desc` varchar(255) DEFAULT NULL,
  `gateway_response` varchar(255) DEFAULT NULL,
  `raw_callback` text DEFAULT NULL,
  `manual_verified_by` int(11) DEFAULT NULL,
  `manual_verified_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `checkout_request_id` (`checkout_request_id`),
  UNIQUE KEY `uq_payments_reference` (`reference`),
  KEY `idx_payments_booking_status` (`booking_id`,`status`),
  KEY `idx_payments_manual_review` (`payment_method`,`status`,`mpesa_receipt_number`),
  CONSTRAINT `1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.payments: ~1 rows (approximately)
INSERT IGNORE INTO `payments` (`id`, `booking_id`, `payment_method`, `gateway`, `channel`, `payment_purpose`, `reference`, `gateway_transaction_id`, `authorization_url`, `paystack_access_code`, `metadata`, `checkout_request_id`, `merchant_request_id`, `phone`, `customer_email`, `manual_recipient`, `amount`, `status`, `mpesa_receipt_number`, `result_code`, `result_desc`, `gateway_response`, `raw_callback`, `manual_verified_by`, `manual_verified_at`, `created_at`, `updated_at`) VALUES
	(3, 3, 'online', 'paystack', 'mobile_money', 'deposit', 'KAHUNA-BK-9D40290F-0FCB940C', '6491996274', 'https://checkout.paystack.com/1af5hqwztflw4jf', '1af5hqwztflw4jf', NULL, NULL, NULL, '254725034005', 'alumasinde@gmail.com', NULL, 18000.00, 'completed', NULL, '0', 'Payment confirmed by Paystack', 'Approved', '{"source":"browser_verify","data":{"id":6491996274,"domain":"test","status":"success","reference":"KAHUNA-BK-9D40290F-0FCB940C","receipt_number":"10101","amount":1800000,"message":null,"gateway_response":"Approved","paid_at":"2026-08-25T17:10:53.000Z","created_at":"2026-08-25T17:10:43.000Z","channel":"mobile_money","currency":"KES","ip_address":"102.203.101.204","metadata":{"booking_id":"3","booking_reference":"BK-9D40290F","payment_id":"3","custom_filters":{"recurring":"false"},"customer_phone":"254725034005"},"log":{"start_time":1787677845,"time_spent":7,"attempts":1,"errors":0,"success":false,"mobile":false,"input":[],"history":[{"type":"action","message":"Attempted to pay with mobile money","time":7}]},"fees":27000,"fees_split":null,"authorization":{"authorization_code":"AUTH_w0jicgpgog","bin":"071XXX","last4":"X000","exp_month":"12","exp_year":"9999","channel":"mobile_money","card_type":"","bank":"M-PESA","country_code":"KE","brand":"M-pesa","reusable":false,"signature":null,"account_name":null,"mobile_money_number":"0710000000","receiver_bank_account_number":null,"receiver_bank":null},"customer":{"id":393881928,"first_name":null,"last_name":null,"email":"alumasinde@gmail.com","customer_code":"CUS_5ifyl0cioduut92","phone":null,"metadata":null,"risk_action":"default","international_format_phone":null},"plan":null,"split":[],"order_id":null,"paidAt":"2026-08-25T17:10:53.000Z","createdAt":"2026-08-25T17:10:43.000Z","requested_amount":1800000,"pos_transaction_data":null,"source":null,"fees_breakdown":null,"connect":null,"transaction_date":"2026-08-25T17:10:43.000Z","plan_object":[],"subaccount":[]}}', NULL, NULL, '2026-08-25 17:10:41', '2026-08-25 17:11:01');

-- Dumping structure for table bigkahuna_carhire_db.permissions
CREATE TABLE IF NOT EXISTS `permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=10 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.permissions: ~8 rows (approximately)
INSERT IGNORE INTO `permissions` (`id`, `name`, `description`, `created_at`) VALUES
	(1, 'cars.view', 'View fleet', '2026-08-25 07:58:39'),
	(2, 'cars.manage', 'Create/edit/delete cars', '2026-08-25 07:58:39'),
	(3, 'bookings.view', 'View bookings', '2026-08-25 07:58:39'),
	(4, 'bookings.manage', 'Update booking status', '2026-08-25 07:58:39'),
	(5, 'users.manage', 'Manage admin users', '2026-08-25 07:58:39'),
	(6, 'settings.manage', 'Manage site/SEO settings', '2026-08-25 07:58:39'),
	(7, 'messages.view', 'View contact messages', '2026-08-25 07:58:39'),
	(8, 'seo.manage', 'Create, edit and delete SEO landing pages', '2026-08-25 07:58:49');

-- Dumping structure for table bigkahuna_carhire_db.rental_charges
CREATE TABLE IF NOT EXISTS `rental_charges` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `charge_type` enum('late_return','extra_mileage','fuel','damage','cleaning','other') NOT NULL,
  `description` varchar(255) NOT NULL,
  `amount` decimal(10,2) NOT NULL,
  `status` enum('pending','paid','waived') NOT NULL DEFAULT 'pending',
  `created_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `idx_charges_booking_status` (`booking_id`,`status`),
  CONSTRAINT `1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.rental_charges: ~0 rows (approximately)

-- Dumping structure for table bigkahuna_carhire_db.rental_inspections
CREATE TABLE IF NOT EXISTS `rental_inspections` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `booking_id` int(11) NOT NULL,
  `car_id` int(11) NOT NULL,
  `inspection_type` enum('checkout','return') NOT NULL,
  `inspected_by` int(11) DEFAULT NULL,
  `inspected_at` datetime NOT NULL DEFAULT current_timestamp(),
  `odometer_km` decimal(10,1) DEFAULT NULL,
  `fuel_level` decimal(5,2) DEFAULT NULL,
  `condition_notes` text DEFAULT NULL,
  `damage_notes` text DEFAULT NULL,
  `photos_json` longtext CHARACTER SET utf8mb4 COLLATE utf8mb4_bin DEFAULT NULL CHECK (json_valid(`photos_json`)),
  `customer_acknowledged` tinyint(1) NOT NULL DEFAULT 0,
  `customer_name` varchar(200) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `inspected_by` (`inspected_by`),
  KEY `idx_inspections_booking_type` (`booking_id`,`inspection_type`),
  KEY `idx_inspections_car_date` (`car_id`,`inspected_at`),
  CONSTRAINT `1` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`),
  CONSTRAINT `3` FOREIGN KEY (`inspected_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.rental_inspections: ~0 rows (approximately)

-- Dumping structure for table bigkahuna_carhire_db.reviews
CREATE TABLE IF NOT EXISTS `reviews` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `source` varchar(30) NOT NULL,
  `external_id` varchar(255) NOT NULL,
  `reviewer_name` varchar(180) NOT NULL,
  `reviewer_photo` text DEFAULT NULL,
  `rating` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `title` varchar(500) DEFAULT NULL,
  `comment` text DEFAULT NULL,
  `review_date` datetime NOT NULL,
  `review_url` text DEFAULT NULL,
  `owner_reply` text DEFAULT NULL,
  `raw_json` longtext DEFAULT NULL,
  `is_visible` tinyint(1) NOT NULL DEFAULT 1,
  `synced_at` timestamp NULL DEFAULT current_timestamp(),
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_review_source_external` (`source`,`external_id`),
  KEY `idx_reviews_visible_date` (`is_visible`,`review_date`),
  KEY `idx_reviews_source` (`source`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.reviews: ~0 rows (approximately)

-- Dumping structure for table bigkahuna_carhire_db.role_permissions
CREATE TABLE IF NOT EXISTS `role_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `permission_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_role_permission` (`role_id`,`permission_id`),
  KEY `permission_id` (`permission_id`),
  CONSTRAINT `1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`permission_id`) REFERENCES `permissions` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.role_permissions: ~16 rows (approximately)
INSERT IGNORE INTO `role_permissions` (`id`, `role_id`, `permission_id`) VALUES
	(1, 1, 4),
	(2, 1, 3),
	(3, 1, 2),
	(4, 1, 1),
	(5, 1, 7),
	(6, 1, 6),
	(7, 1, 5),
	(8, 2, 4),
	(9, 2, 3),
	(10, 2, 2),
	(11, 2, 1),
	(12, 2, 7),
	(15, 3, 4),
	(16, 3, 3),
	(17, 3, 1),
	(18, 1, 8),
	(19, 3, 7),
	(20, 2, 8);

-- Dumping structure for table bigkahuna_carhire_db.roles
CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(50) NOT NULL,
  `description` varchar(255) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `name` (`name`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.roles: ~3 rows (approximately)
INSERT IGNORE INTO `roles` (`id`, `name`, `description`, `created_at`, `updated_at`) VALUES
	(1, 'super_admin', 'Full system access', '2026-08-25 07:58:38', '2026-08-25 07:58:38'),
	(2, 'manager', 'Manages fleet, bookings and content', '2026-08-25 07:58:38', '2026-08-25 07:58:38'),
	(3, 'staff', 'Front-desk booking staff', '2026-08-25 07:58:38', '2026-08-25 07:58:38');

-- Dumping structure for table bigkahuna_carhire_db.schema_migrations
CREATE TABLE IF NOT EXISTS `schema_migrations` (
  `migration` varchar(255) NOT NULL,
  `applied_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`migration`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.schema_migrations: ~22 rows (approximately)
INSERT IGNORE INTO `schema_migrations` (`migration`, `applied_at`) VALUES
	('001_schema.sql', '2026-08-25 07:58:39'),
	('002_migrate-terms-license.sql', '2026-08-25 07:58:39'),
	('003_migrate-notifications-accounts.sql', '2026-08-25 07:58:40'),
	('004_chauffeur_pricing.sql', '2026-08-25 07:58:40'),
	('005_whatsapp_admin_alerts.sql', '2026-08-25 07:58:40'),
	('006_manual_mpesa_payments.sql', '2026-08-25 07:58:42'),
	('007_operations_indexes.sql', '2026-08-25 07:58:45'),
	('008_rental_operations.sql', '2026-08-25 07:58:47'),
	('009_fleet_maintenance.sql', '2026-08-25 07:58:48'),
	('010_customer_experience.sql', '2026-08-25 07:58:48'),
	('011_seo_growth.sql', '2026-08-25 07:58:48'),
	('012_dynamic_seo.sql', '2026-08-25 07:58:51'),
	('013_paystack_payments.sql', '2026-08-25 07:58:56'),
	('014_paystack_resume_transaction.sql', '2026-08-25 07:58:57'),
	('015_paystack_settings.sql', '2026-08-25 07:58:57'),
	('016_payment_purpose_receipts.sql', '2026-08-25 07:58:57'),
	('017_external_reviews.sql', '2026-08-25 07:58:58'),
	('018_public_legal_pages.sql', '2026-08-25 07:58:58'),
	('019_v2_cleanup.sql', '2026-08-25 07:58:58'),
	('020_phase3_operations.sql', '2026-08-25 07:58:59'),
	('021_phase4_whatsapp_ops.sql', '2026-08-25 07:59:00'),
	('022_phase5_customer_lifecycle.sql', '2026-08-25 07:59:01'),
	('023_v5_stabilization.sql', '2026-08-25 08:04:29'),
	('024_v51_notification_claims.sql', '2026-08-25 08:32:57');

-- Dumping structure for table bigkahuna_carhire_db.seo_page_content
CREATE TABLE IF NOT EXISTS `seo_page_content` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `heading` varchar(255) NOT NULL,
  `body` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_content_page_sort` (`page_id`,`sort_order`),
  KEY `idx_content_page` (`page_id`,`is_active`,`sort_order`),
  CONSTRAINT `1` FOREIGN KEY (`page_id`) REFERENCES `seo_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.seo_page_content: ~15 rows (approximately)
INSERT IGNORE INTO `seo_page_content` (`id`, `page_id`, `heading`, `body`, `sort_order`, `is_active`) VALUES
	(1, 1, 'Car hire for Nairobi trips', 'Choose a rental around the purpose of your trip. Self-drive can suit business meetings, city errands and independent travel, while chauffeur-driven hire can suit airport transfers, events and days when you prefer not to drive. Check the live fleet for vehicle specifications and advertised daily rates before booking.', 10, 1),
	(2, 1, 'Nairobi pickup areas', 'Big Kahuna can serve trips around Nairobi locations such as the CBD, Westlands, Kilimani, Karen, Gigiri and Syokimau, subject to the agreed pickup arrangement. If you are arriving through JKIA, share your flight details when requesting pickup so the team can confirm the practical handover point.', 20, 1),
	(3, 2, 'Car hire for Mombasa and Coast travel', 'A rental car can make it easier to move between Mombasa, hotels and beach destinations along the Coast. Choose a vehicle based on passenger count, luggage, road conditions and whether you want to drive yourself or travel with a chauffeur.', 10, 1),
	(4, 2, 'Mombasa pickup areas', 'Pickup can be discussed for Mombasa CBD, Nyali, Bamburi, Shanzu and other agreed Coast locations. For airport arrivals, provide your arrival time and flight details so the team can confirm the pickup arrangement before you travel.', 20, 1),
	(5, 3, 'JKIA car hire for Nairobi travel', 'If your trip starts at Jomo Kenyatta International Airport, plan the vehicle and handover around your arrival time. Self-drive and chauffeur options may be available depending on the vehicle and rental requirements.', 10, 1),
	(6, 3, 'What to include in an airport booking', 'Include your arrival date, approximate landing time, flight details, preferred vehicle and contact number. Confirm the pickup point, identification requirements and any deposit before payment or collection.', 20, 1),
	(7, 4, 'Mombasa airport car hire', 'For Coast trips arriving by air, choose a vehicle that fits your passengers, luggage and itinerary. Pickup or delivery arrangements should be confirmed with the team before your arrival.', 10, 1),
	(8, 4, 'From Mombasa airport to your Coast destination', 'Tell the team whether you are heading to Mombasa, Nyali, Bamburi, Shanzu, South Coast or another destination. This helps confirm the most practical vehicle and handover arrangement for your trip.', 20, 1),
	(9, 5, 'Self-drive car hire in Kenya', 'Self-drive rental gives you control over your itinerary and can work well for business travel, holidays and road trips. Vehicle eligibility, identification, driving licence, deposit, permitted routes and other terms should be confirmed before collection.', 10, 1),
	(10, 6, 'Car hire with a driver', 'Chauffeur-driven rental can be useful for airport transfers, business schedules, events and private trips. Tell the team your dates, route, passenger needs and preferred vehicle so the arrangement and rate can be confirmed.', 10, 1),
	(11, 7, 'Airport car hire in Kenya', 'Airport rentals work best when the booking includes the arrival airport, date, flight time, passenger count and preferred vehicle. Confirm whether the vehicle will be delivered, collected or handled by a chauffeur before travelling.', 10, 1),
	(12, 8, 'SUV rental for family and business travel', 'SUVs can provide additional passenger space and luggage capacity for family trips, business travel and longer journeys. Compare seating, transmission, fuel type, daily rate and availability before booking.', 10, 1),
	(13, 9, '4x4 hire for road trips', 'If you are planning a longer Kenyan road trip, discuss the route and intended use before booking. Confirm vehicle suitability, insurance terms, permitted routes and any mileage or usage restrictions.', 10, 1),
	(14, 10, 'Long-term and monthly car hire', 'Monthly rental can suit projects, extended business stays and temporary vehicle needs. Longer rentals may have different rates and terms, so confirm duration, mileage, maintenance responsibilities and payment arrangements before committing.', 10, 1),
	(15, 11, 'Prepare before you collect your rental', 'Have your identification and valid driving licence ready for self-drive hire. Depending on the vehicle and rental terms, additional verification, deposit or payment requirements may apply. Confirm the exact requirements for your booking before travelling to the pickup point.', 10, 1);

-- Dumping structure for table bigkahuna_carhire_db.seo_page_faqs
CREATE TABLE IF NOT EXISTS `seo_page_faqs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `question` varchar(500) NOT NULL,
  `answer` text NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_faq_page_sort` (`page_id`,`sort_order`),
  KEY `idx_faq_page` (`page_id`,`is_active`,`sort_order`),
  CONSTRAINT `1` FOREIGN KEY (`page_id`) REFERENCES `seo_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=32 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.seo_page_faqs: ~31 rows (approximately)
INSERT IGNORE INTO `seo_page_faqs` (`id`, `page_id`, `question`, `answer`, `sort_order`, `is_active`) VALUES
	(1, 1, 'Do you offer self-drive car hire in Nairobi?', 'Self-drive options are available subject to vehicle availability and rental requirements.', 10, 1),
	(2, 1, 'Can I hire a car with a driver in Nairobi?', 'Chauffeur-driven options can be requested for business, airport transfers, events or private trips.', 20, 1),
	(3, 1, 'Can I arrange pickup near JKIA?', 'Ask the team about airport pickup or delivery arrangements when booking.', 30, 1),
	(4, 2, 'Do you offer car hire in Mombasa?', 'Contact Big Kahuna to confirm current vehicle availability and pickup arrangements in Mombasa.', 10, 1),
	(5, 2, 'Can I hire a car for a Mombasa holiday?', 'Rental options can be suitable for beach holidays, family trips and business travel, subject to availability and rental requirements.', 20, 1),
	(6, 2, 'Can I request a driver in Mombasa?', 'Chauffeur-driven hire can be requested where available.', 30, 1),
	(7, 3, 'Can I arrange car hire for an airport arrival at JKIA?', 'Share your flight time and preferred vehicle so the team can confirm the pickup or delivery arrangement.', 10, 1),
	(8, 3, 'What should I provide for airport pickup?', 'Provide your arrival date, approximate time, flight details and preferred vehicle.', 20, 1),
	(9, 4, 'Can I request a car for airport pickup in Mombasa?', 'Contact Big Kahuna with your arrival details and preferred vehicle so the team can confirm current arrangements.', 10, 1),
	(10, 4, 'Is chauffeur-driven airport transport available?', 'Chauffeur-driven airport transport can be requested where available. Confirm the service and price before booking.', 20, 1),
	(11, 5, 'What do I need for self-drive car hire in Kenya?', 'Requirements vary by vehicle and customer. Confirm identification, driving licence, deposit and other terms before booking.', 10, 1),
	(12, 5, 'Can I take a self-drive rental outside Nairobi?', 'Ask about your intended route before booking. Longer-distance use may be subject to vehicle and rental terms.', 20, 1),
	(13, 6, 'Can I hire a car with a driver for a full day?', 'Chauffeur hire can be arranged around the trip and vehicle required. Confirm the applicable rate before booking.', 10, 1),
	(14, 6, 'Is chauffeur hire available for airport transfers?', 'Airport transfers can be requested. Provide flight details so the team can confirm the arrangement.', 20, 1),
	(15, 7, 'Can I arrange airport pickup in advance?', 'Yes. Share your arrival date, time, flight details and preferred vehicle so the team can confirm the arrangement.', 10, 1),
	(16, 7, 'Do you offer self-drive and chauffeur airport options?', 'Both can be requested where available. Confirm the vehicle, pickup arrangement and price before booking.', 20, 1),
	(17, 8, 'What SUV options are available?', 'Availability changes with bookings. Browse the live fleet for current vehicles, specifications and advertised daily rates.', 10, 1),
	(18, 8, 'Are SUVs available for self-drive?', 'Self-drive availability depends on the vehicle and rental requirements.', 20, 1),
	(19, 9, 'Can I hire a 4x4 for a Kenya road trip?', 'Ask about your route and intended use. Vehicle availability, insurance terms and permitted routes should be confirmed before booking.', 10, 1),
	(20, 9, 'Are 4x4 vehicles available with a driver?', 'Chauffeur-driven options may be available depending on the vehicle and dates.', 20, 1),
	(21, 10, 'Do you offer monthly car rental?', 'Longer-term hire can be requested. Contact Big Kahuna with the vehicle type, start date and rental period for current pricing and terms.', 10, 1),
	(22, 10, 'Is monthly car hire cheaper than daily rental?', 'Longer rentals may have different rates depending on vehicle, duration, mileage and rental terms.', 20, 1),
	(23, 11, 'What documents should I prepare?', 'Prepare valid identification and a valid driving licence where self-drive is requested. Additional verification may apply.', 10, 1),
	(24, 11, 'Is a security deposit required?', 'A deposit may apply depending on the vehicle and rental terms. Confirm the amount before booking.', 20, 1),
	(25, 11, 'Do international visitors need additional documents?', 'Requirements can differ for visitors. Confirm accepted driving documents and identification before collection.', 30, 1),
	(26, 12, 'How much does car hire cost in Kenya?', 'Prices vary by vehicle, duration, driving option and other terms. Browse the live fleet for advertised rates.', 10, 1),
	(27, 12, 'Can I hire a car without a driver?', 'Self-drive options are available subject to vehicle availability and rental requirements.', 20, 1),
	(28, 12, 'Can I hire a car with a driver?', 'Chauffeur-driven options can be requested for airport transfers, business travel, events and private trips.', 30, 1),
	(29, 12, 'Can I collect a car at an airport?', 'Airport pickup or delivery can be requested; provide flight details so the team can confirm the arrangement.', 40, 1),
	(30, 12, 'How do I book?', 'Browse the fleet, choose a vehicle and submit your booking details.', 50, 1),
	(31, 12, 'Do you accept M-Pesa?', 'Where enabled for a booking, M-Pesa payment can be initiated through the booking flow.', 60, 1);

-- Dumping structure for table bigkahuna_carhire_db.seo_page_related
CREATE TABLE IF NOT EXISTS `seo_page_related` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_id` int(11) NOT NULL,
  `label` varchar(160) NOT NULL,
  `target_key` varchar(180) NOT NULL,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_related_page_sort` (`page_id`,`sort_order`),
  KEY `idx_related_page` (`page_id`,`sort_order`),
  CONSTRAINT `1` FOREIGN KEY (`page_id`) REFERENCES `seo_pages` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=45 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.seo_page_related: ~44 rows (approximately)
INSERT IGNORE INTO `seo_page_related` (`id`, `page_id`, `label`, `target_key`, `sort_order`) VALUES
	(1, 1, 'Car Hire at JKIA', 'airports/jkia', 10),
	(2, 1, 'Self Drive Car Hire', 'services/self-drive', 20),
	(3, 1, 'Car Hire With Driver', 'services/chauffeur', 30),
	(4, 1, 'View Fleet', 'fleet', 40),
	(5, 2, 'Mombasa Airport Car Hire', 'airports/mombasa', 10),
	(6, 2, '4x4 Car Hire', 'services/4x4-hire', 20),
	(7, 2, 'Car Hire With Driver', 'services/chauffeur', 30),
	(8, 2, 'View Fleet', 'fleet', 40),
	(9, 3, 'Car Hire in Nairobi', 'locations/nairobi', 10),
	(10, 3, 'Self Drive Car Hire', 'services/self-drive', 20),
	(11, 3, 'Car Hire With Driver', 'services/chauffeur', 30),
	(12, 3, 'Book a Car', 'book', 40),
	(13, 4, 'Car Hire in Mombasa', 'locations/mombasa', 10),
	(14, 4, '4x4 Car Hire', 'services/4x4-hire', 20),
	(15, 4, 'Car Hire With Driver', 'services/chauffeur', 30),
	(16, 4, 'Book a Car', 'book', 40),
	(17, 5, 'Car Hire in Nairobi', 'locations/nairobi', 10),
	(18, 5, 'Car Hire in Mombasa', 'locations/mombasa', 20),
	(19, 5, 'Rental Requirements', 'requirements', 30),
	(20, 5, 'View Fleet', 'fleet', 40),
	(21, 6, 'Car Hire in Nairobi', 'locations/nairobi', 10),
	(22, 6, 'Car Hire in Mombasa', 'locations/mombasa', 20),
	(23, 6, 'JKIA Car Hire', 'airports/jkia', 30),
	(24, 6, 'Book a Car', 'book', 40),
	(25, 7, 'JKIA Car Hire', 'airports/jkia', 10),
	(26, 7, 'Mombasa Airport Car Hire', 'airports/mombasa', 20),
	(27, 7, 'Car Hire With Driver', 'services/chauffeur', 30),
	(28, 7, 'Book a Car', 'book', 40),
	(29, 8, '4x4 Car Hire', 'services/4x4-hire', 10),
	(30, 8, 'Car Hire in Nairobi', 'locations/nairobi', 20),
	(31, 8, 'Car Hire in Mombasa', 'locations/mombasa', 30),
	(32, 8, 'View Fleet', 'fleet', 40),
	(33, 9, 'SUV Car Hire', 'services/suv-hire', 10),
	(34, 9, 'Car Hire in Nairobi', 'locations/nairobi', 20),
	(35, 9, 'Car Hire in Mombasa', 'locations/mombasa', 30),
	(36, 9, 'View Fleet', 'fleet', 40),
	(37, 10, 'Car Hire in Nairobi', 'locations/nairobi', 10),
	(38, 10, 'Car Hire in Mombasa', 'locations/mombasa', 20),
	(39, 10, 'View Fleet', 'fleet', 30),
	(40, 10, 'Contact Us', 'contact', 40),
	(41, 11, 'Self Drive Car Hire', 'services/self-drive', 10),
	(42, 11, 'Car Hire in Nairobi', 'locations/nairobi', 20),
	(43, 11, 'Car Hire in Mombasa', 'locations/mombasa', 30),
	(44, 11, 'Book a Car', 'book', 40);

-- Dumping structure for table bigkahuna_carhire_db.seo_pages
CREATE TABLE IF NOT EXISTS `seo_pages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `page_key` varchar(180) NOT NULL,
  `page_type` enum('location','airport','service','guide','faq') NOT NULL,
  `name` varchar(160) NOT NULL,
  `slug` varchar(160) NOT NULL,
  `title` varchar(255) NOT NULL,
  `meta_description` varchar(500) NOT NULL,
  `h1` varchar(255) NOT NULL,
  `intro` text NOT NULL,
  `areas_json` text DEFAULT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `is_indexable` tinyint(1) NOT NULL DEFAULT 1,
  `sort_order` int(11) NOT NULL DEFAULT 0,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `page_key` (`page_key`),
  UNIQUE KEY `uniq_page_type_slug` (`page_type`,`slug`),
  KEY `idx_seo_pages_active` (`is_active`,`is_indexable`,`page_type`)
) ENGINE=InnoDB AUTO_INCREMENT=13 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.seo_pages: ~12 rows (approximately)
INSERT IGNORE INTO `seo_pages` (`id`, `page_key`, `page_type`, `name`, `slug`, `title`, `meta_description`, `h1`, `intro`, `areas_json`, `is_active`, `is_indexable`, `sort_order`, `created_at`, `updated_at`) VALUES
	(1, 'locations/nairobi', 'location', 'Nairobi', 'nairobi', 'Car Hire in Nairobi | Self Drive & Chauffeur Cars | Big Kahuna', 'Car hire in Nairobi for business, holidays, airport trips and everyday travel. Browse self-drive and chauffeur-driven cars and book with Big Kahuna Car Hire.', 'Car Hire in Nairobi', 'Looking for reliable car hire in Nairobi? Browse our vehicle range for self-drive and chauffeur-driven trips, with convenient booking and support.', '["Nairobi CBD","Westlands","Kilimani","Karen","Gigiri","Syokimau"]', 1, 1, 10, '2026-08-25 07:58:49', '2026-08-25 07:58:49'),
	(2, 'locations/mombasa', 'location', 'Mombasa', 'mombasa', 'Car Hire in Mombasa | Self Drive & Chauffeur Cars | Big Kahuna', 'Car hire in Mombasa for holidays, business and airport travel. Explore self-drive and chauffeur-driven vehicle options with Big Kahuna Car Hire.', 'Car Hire in Mombasa', 'Planning a trip to the Coast? Explore car hire options for Mombasa, airport travel, beach holidays and business trips, with self-drive and chauffeur options.', '["Mombasa CBD","Nyali","Bamburi","Shanzu","Likoni","South Coast"]', 1, 1, 20, '2026-08-25 07:58:49', '2026-08-25 07:58:49'),
	(3, 'airports/jkia', 'airport', 'JKIA', 'jkia', 'Car Hire at JKIA | Nairobi Airport Car Rental | Big Kahuna', 'Looking for car hire near Jomo Kenyatta International Airport? Explore vehicle options, pickup arrangements and chauffeur services with Big Kahuna Car Hire.', 'Car Hire at JKIA', 'Arriving or departing through Jomo Kenyatta International Airport? Plan your Nairobi transport with a rental car or chauffeur-driven option.', '["Jomo Kenyatta International Airport","Syokimau","Nairobi","Embakasi"]', 1, 1, 30, '2026-08-25 07:58:49', '2026-08-25 07:58:49'),
	(4, 'airports/mombasa', 'airport', 'Mombasa Airport', 'mombasa', 'Mombasa Airport Car Hire | Car Rental at Moi Airport | Big Kahuna', 'Plan your Coast trip with car hire near Mombasa airport. Explore self-drive and chauffeur-driven options with Big Kahuna Car Hire.', 'Mombasa Airport Car Hire', 'Arriving at Mombasa airport? Arrange your Coast transport around your arrival time, preferred vehicle and travel plans.', '["Moi International Airport","Mombasa","Nyali","Bamburi","South Coast"]', 1, 1, 40, '2026-08-25 07:58:49', '2026-08-25 07:58:49'),
	(5, 'services/self-drive', 'service', 'Self Drive Car Hire', 'self-drive', 'Self Drive Car Hire in Kenya | Nairobi & Mombasa | Big Kahuna', 'Self-drive car hire in Kenya with vehicle options for city travel, business trips, holidays and road trips. Browse the Big Kahuna fleet.', 'Self Drive Car Hire in Kenya', 'Prefer to drive yourself? Explore self-drive vehicle options for Nairobi, Mombasa and longer Kenyan trips, subject to availability and rental requirements.', '["Nairobi","Mombasa","Kenya"]', 1, 1, 50, '2026-08-25 07:58:49', '2026-08-25 07:58:49'),
	(6, 'services/chauffeur', 'service', 'Chauffeur Car Hire', 'chauffeur', 'Car Hire With Driver in Kenya | Nairobi & Mombasa | Big Kahuna', 'Hire a car with a professional driver in Kenya for airport transfers, business travel, events and private trips in Nairobi, Mombasa and beyond.', 'Car Hire With Driver in Kenya', 'Choose chauffeur-driven transport when you want a comfortable trip without having to drive. Request a vehicle and driver for business, airport transfers, events or private travel.', '["Nairobi","Mombasa","Kenya"]', 1, 1, 60, '2026-08-25 07:58:49', '2026-08-25 07:58:49'),
	(7, 'services/airport-car-hire', 'service', 'Airport Car Hire', 'airport-car-hire', 'Airport Car Hire in Kenya | JKIA & Mombasa | Big Kahuna', 'Airport car hire for Nairobi JKIA and Mombasa travel. Arrange self-drive or chauffeur-driven transport around your flight schedule.', 'Airport Car Hire in Kenya', 'Make airport arrivals easier with a rental car or chauffeur-driven option arranged around your flight schedule.', '["JKIA","Mombasa Airport","Nairobi","Mombasa"]', 1, 1, 70, '2026-08-25 07:58:49', '2026-08-25 07:58:49'),
	(8, 'services/suv-hire', 'service', 'SUV Car Hire', 'suv-hire', 'SUV Car Hire in Kenya | Nairobi & Mombasa | Big Kahuna', 'SUV car hire in Kenya for families, business travel and road trips. Browse Big Kahuna SUVs and 4x4 options.', 'SUV Car Hire in Kenya', 'SUVs are a practical choice for families, business trips and longer journeys. Browse available SUVs and compare specifications and daily rates.', '["Nairobi","Mombasa","Kenya"]', 1, 1, 80, '2026-08-25 07:58:49', '2026-08-25 07:58:49'),
	(9, 'services/4x4-hire', 'service', '4x4 Car Hire', '4x4-hire', '4x4 Car Hire in Kenya | Safari & Road Trip Vehicles | Big Kahuna', '4x4 and rugged vehicle hire in Kenya for road trips, family travel and safari-oriented journeys. Browse available Big Kahuna vehicles.', '4x4 Car Hire in Kenya', 'Planning a longer Kenyan road trip or need a more capable vehicle? Explore available 4x4 and SUV options and confirm route requirements before booking.', '["Nairobi","Mombasa","Kenya"]', 1, 1, 90, '2026-08-25 07:58:49', '2026-08-25 07:58:49'),
	(10, 'services/monthly-car-hire', 'service', 'Monthly Car Hire', 'monthly-car-hire', 'Monthly Car Hire in Kenya | Long Term Car Rental | Big Kahuna', 'Monthly and longer-term car hire options in Kenya for business, projects and extended stays. Ask Big Kahuna for current vehicle availability and rates.', 'Monthly Car Hire in Kenya', 'Need a vehicle for weeks or months rather than days? Ask about longer-term rental options, mileage and pricing.', '["Nairobi","Mombasa","Kenya"]', 1, 1, 100, '2026-08-25 07:58:49', '2026-08-25 07:58:49'),
	(11, 'requirements', 'guide', 'Rental Requirements', 'requirements', 'Car Hire Requirements in Kenya | Big Kahuna Car Hire', 'Learn what to prepare before hiring a car in Kenya, including identification, driving licence, booking information, deposits and vehicle-specific requirements.', 'Car Hire Requirements in Kenya', 'Requirements can vary by vehicle, rental duration and customer profile. Use this guide as a starting point and confirm exact requirements before paying or collecting a vehicle.', '["Kenya","Nairobi","Mombasa"]', 1, 1, 110, '2026-08-25 07:58:49', '2026-08-25 07:58:49'),
	(12, 'faq', 'faq', 'Frequently Asked Questions', 'faq', 'Car Hire FAQs in Kenya | Nairobi & Mombasa | Big Kahuna', 'Answers to common questions about car hire in Kenya, including self-drive, chauffeur hire, airport pickup, deposits and bookings.', 'Car Hire FAQs', 'Find quick answers about booking, self-drive, chauffeur hire, airport arrangements and rental requirements.', '["Kenya","Nairobi","Mombasa"]', 1, 1, 120, '2026-08-25 07:58:49', '2026-08-25 07:58:49');

-- Dumping structure for table bigkahuna_carhire_db.sessions
CREATE TABLE IF NOT EXISTS `sessions` (
  `id` varchar(128) NOT NULL,
  `user_id` int(11) DEFAULT NULL,
  `ip_address` varchar(45) DEFAULT NULL,
  `user_agent` varchar(255) DEFAULT NULL,
  `payload` longtext DEFAULT NULL,
  `last_activity` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.sessions: ~10 rows (approximately)
INSERT IGNORE INTO `sessions` (`id`, `user_id`, `ip_address`, `user_agent`, `payload`, `last_activity`) VALUES
	('4f0bik3i2o9f11o9dflmd9ja7b', NULL, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', 1787649664),
	('c1tirps5hg25dth77r535nvsns', NULL, '127.0.0.1', 'curl/8.13.0', '', 1787650494),
	('dmk9e7f6l3jrgal3qcd40nishd', NULL, '127.0.0.1', 'curl/8.13.0', 'csrf_token|s:64:"50481beb18560de1f8d8ba447000434bbbf0a71512a7fd559f0404f75661365a";', 1787650724),
	('ijcsdhefm2k12ht8oq7clhgtcq', NULL, '127.0.0.1', 'curl/8.13.0', '', 1787650328),
	('oadn051ljaddldasjo4b9ilqbb', NULL, '127.0.0.1', 'curl/8.13.0', '', 1787650738),
	('oqtqipivsodbpnatrvj9k5dtab', NULL, '127.0.0.1', 'curl/8.13.0', '', 1787650400),
	('pmodicucf3fjt3u85r05u8gen0', NULL, '127.0.0.1', 'curl/8.13.0', '', 1787650494),
	('q7rn5u42fg0o4b38b4sc2rkc07', NULL, '127.0.0.1', 'Mozilla/5.0 (Linux; Android 15; Pixel 9) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/152.0.0.0 Mobile Safari/537.36', 'csrf_token|s:64:"910db6046eaff2453130eee71bb824395ed0a2ba2385ccd22cc17232c261853c";', 1787866743),
	('u0ii0lpgps0mdrho2g0u0edm5h', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'csrf_token|s:64:"5225d78ada50edf694bff5074abf842734db0ade2647cc05cebd4c5ec81eea45";user_id|i:1;first_name|s:3:"Big";last_name|s:6:"Kahuna";role|s:11:"super_admin";role_id|i:1;last_booking_id|i:2;last_booking_token|s:64:"1162c9d409a4e7451102f4a7e659d09710b943022ccc3994d84a126635b63e2f";', 1787651088),
	('ur888s89q6sdm47rlr25g5j9ko', NULL, '::1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', '', 1787645282),
	('vg3s01f11o959fqh1f1pl4lp1d', 1, '127.0.0.1', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/151.0.0.0 Safari/537.36', 'csrf_token|s:64:"b68eaeadf88003e1f263213a532d70404e0c240879ca47a4c8738cf334bd322b";user_id|i:1;first_name|s:3:"Big";last_name|s:6:"Kahuna";role|s:11:"super_admin";role_id|i:1;last_booking_id|i:3;last_booking_token|s:64:"70d626d5d451aa313dd95151f9622d945cacd239e5714a5ade9f567ba41258a9";', 1787679086);

-- Dumping structure for table bigkahuna_carhire_db.settings
CREATE TABLE IF NOT EXISTS `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_group` varchar(50) NOT NULL DEFAULT 'general',
  `setting_key` varchar(150) NOT NULL,
  `setting_value` text DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uniq_group_key` (`setting_group`,`setting_key`)
) ENGINE=InnoDB AUTO_INCREMENT=132 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.settings: ~109 rows (approximately)
INSERT IGNORE INTO `settings` (`id`, `setting_group`, `setting_key`, `setting_value`, `created_at`, `updated_at`) VALUES
	(1, 'general', 'site_name', 'Big Kahuna Car Hire', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(2, 'general', 'tagline', 'Ride the Wave. Drive the Kahuna.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(3, 'general', 'phone_primary', '+254 700 000 000', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(4, 'general', 'phone_secondary', '+254 733 000 000', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(5, 'general', 'email', 'info@bigkahunacarhire.co.ke', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(6, 'general', 'address', 'Mombasa Road, Nairobi, Kenya', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(7, 'general', 'working_hours', 'Mon - Sun: 6:00 AM - 10:00 PM', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(8, 'general', 'currency', 'KES', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(9, 'general', 'facebook_url', 'https://facebook.com/bigkahunacarhire', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(10, 'general', 'instagram_url', 'https://instagram.com/bigkahunacarhire', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(11, 'general', 'twitter_url', 'https://x.com/bigkahunacarhire', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(12, 'general', 'whatsapp_number', '254700000000', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(13, 'general', 'google_maps_embed', '', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(14, 'seo', 'default_meta_title', 'Big Kahuna Car Hire | Self-Drive & Chauffeur Car Rental in Kenya', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(15, 'seo', 'default_meta_description', 'Big Kahuna Car Hire offers affordable self-drive and chauffeur-driven car rentals across Kenya. Economy cars, SUVs, luxury sedans and vans — book online today.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(16, 'seo', 'default_meta_keywords', 'car hire kenya, car rental nairobi, self drive cars kenya, suv hire kenya, big kahuna car hire', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(17, 'seo', 'og_image', '/assets/images/og-default.jpg', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(18, 'seo', 'google_analytics_id', '', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(19, 'seo', 'google_site_verification', '', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(20, 'seo', 'robots', 'index, follow', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(21, 'seo', 'home_title', 'Big Kahuna Car Hire | Self-Drive & Chauffeur Car Rental in Kenya', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(22, 'seo', 'home_description', 'Book affordable, reliable self-drive and chauffeur car rentals in Kenya. Economy, SUV, luxury and van options with 24/7 support.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(23, 'seo', 'fleet_title', 'Our Fleet | Big Kahuna Car Hire', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(24, 'seo', 'fleet_description', 'Browse our full fleet of economy cars, SUVs, luxury sedans and vans available for hire across Kenya.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(25, 'seo', 'about_title', 'About Us | Big Kahuna Car Hire', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(26, 'seo', 'about_description', 'Learn about Big Kahuna Car Hire, a Kenyan-owned car rental company committed to safe, affordable and reliable transport.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(27, 'seo', 'contact_title', 'Contact Us | Big Kahuna Car Hire', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(28, 'seo', 'contact_description', 'Get in touch with Big Kahuna Car Hire for bookings, quotes and support. Call, WhatsApp or visit us in Nairobi.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(29, 'seo', 'booking_title', 'Book a Car | Big Kahuna Car Hire', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(30, 'seo', 'booking_description', 'Reserve your car online in minutes with Big Kahuna Car Hire. Fast confirmation, flexible pickup and drop-off.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(31, 'notifications', 'email_enabled', '1', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(32, 'notifications', 'sms_enabled', '1', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(33, 'notifications', 'admin_notification_email', 'admin@bigkahunacarhire.co.ke', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(34, 'notifications', 'admin_notification_phone', '254700000000', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(35, 'legal', 'terms_and_conditions', 'By booking a vehicle with Big Kahuna Car Hire, you agree to the following terms:\n\n1. ELIGIBILITY: The renter must be at least 23 years old, hold a valid national ID or passport, and hold a valid driving license (minimum 2 years driving experience) for the vehicle class booked.\n\n2. DEPOSIT & PAYMENT: A non-refundable booking deposit is required to confirm a reservation. The balance is payable at pickup. Accepted payment methods include M-Pesa and cash.\n\n3. VEHICLE CONDITION: The vehicle will be inspected jointly by the renter and Big Kahuna Car Hire staff before handover and upon return. Both parties will sign off on the vehicle\'s condition and fuel level at handover.\n\n4. DAMAGE & LIABILITY: The renter is fully responsible for any damage, loss, or theft of the vehicle occurring during the rental period, including but not limited to collision damage, tyre damage, windscreen damage, interior damage, and loss of accessories. The renter agrees to cover the full cost of repair or replacement, and any applicable insurance excess, for damage not covered by insurance or arising from a breach of these terms (e.g. reckless driving, driving under the influence, use outside agreed geographic limits, or use by an unauthorized driver).\n\n5. TRAFFIC OFFENSES: The renter is liable for all traffic fines, tolls, and penalties incurred during the rental period.\n\n6. LATE RETURNS: Returning the vehicle later than the agreed return date/time without prior arrangement will incur additional daily charges at the standard rate.\n\n7. FUEL POLICY: The vehicle is provided with a set fuel level and must be returned at the same level, or a refueling charge will apply.\n\n8. CANCELLATIONS: Cancellations made less than 24 hours before pickup may forfeit the booking deposit.\n\n9. GOVERNING LAW: These terms are governed by the laws of Kenya.\n\nPlease read these terms carefully. By ticking the box and proceeding, you confirm that you understand and accept full responsibility for the vehicle for the duration of your rental.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(36, 'legal', 'damage_disclaimer', 'I understand that I am fully responsible for any damage, loss, or theft of the vehicle during my rental period, and I agree to cover the full repair, replacement, or insurance excess cost for any such damage.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(37, 'paystack', 'enabled', '1', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(38, 'paystack', 'deposit_percentage', '30', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(39, 'paystack', 'display_label', 'Pay securely', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(40, 'paystack', 'checkout_description', 'Pay your booking deposit securely using the payment methods available through Paystack.', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(41, 'paystack', 'channels', 'card,mobile_money,bank_transfer', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(42, 'mpesa', 'enabled', '0', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(43, 'mpesa', 'stk_enabled', '0', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(44, 'mpesa', 'manual_enabled', '0', '2026-08-25 07:58:39', '2026-08-25 07:58:39'),
	(49, 'general', 'default_chauffeur_fee_per_day', '2000', '2026-08-25 07:58:40', '2026-08-25 07:58:40'),
	(50, 'notifications', 'whatsapp_enabled', '0', '2026-08-25 07:58:40', '2026-08-25 07:58:40'),
	(51, 'notifications', 'admin_whatsapp_phone', '254700000000', '2026-08-25 07:58:40', '2026-08-25 07:58:40'),
	(52, 'mpesa', 'manual_recipient_phone', '254700000000', '2026-08-25 07:58:42', '2026-08-25 07:58:42'),
	(53, 'mpesa', 'manual_recipient_name', 'Big Kahuna Car Hire', '2026-08-25 07:58:42', '2026-08-25 07:58:42'),
	(54, 'mpesa', 'manual_instructions', 'Open M-Pesa, choose Send Money, send the exact deposit amount to the configured Big Kahuna number, then enter the M-Pesa transaction code below. Your payment remains pending until our team verifies the transaction.', '2026-08-25 07:58:42', '2026-08-25 07:58:42'),
	(57, 'rental', 'late_return_grace_minutes', '30', '2026-08-25 07:58:47', '2026-08-25 07:58:47'),
	(58, 'rental', 'extra_mileage_rate_per_km', '0', '2026-08-25 07:58:47', '2026-08-25 07:58:47'),
	(59, 'rental', 'fuel_charge_per_unit', '0', '2026-08-25 07:58:47', '2026-08-25 07:58:47'),
	(60, 'rental', 'require_checkout_inspection', '1', '2026-08-25 07:58:47', '2026-08-25 07:58:47'),
	(61, 'rental', 'require_return_inspection', '1', '2026-08-25 07:58:47', '2026-08-25 07:58:47'),
	(62, 'fleet', 'document_expiry_warning_days', '30', '2026-08-25 07:58:48', '2026-08-25 07:58:48'),
	(63, 'fleet', 'maintenance_due_warning_days', '14', '2026-08-25 07:58:48', '2026-08-25 07:58:48'),
	(64, 'fleet', 'default_service_interval_km', '10000', '2026-08-25 07:58:48', '2026-08-25 07:58:48'),
	(65, 'rental', 'pickup_instructions', 'Please carry your original ID/passport and valid driving licence. Our team will confirm the pickup point before your rental.', '2026-08-25 07:58:48', '2026-08-25 07:58:48'),
	(66, 'rental', 'return_instructions', 'Return the vehicle at the agreed time and location with all keys and accessories.', '2026-08-25 07:58:48', '2026-08-25 07:58:48'),
	(68, 'general', 'support_hours', 'Daily', '2026-08-25 07:58:48', '2026-08-25 07:58:48'),
	(69, 'general', 'price_range', 'KES 5,000 - KES 20,000', '2026-08-25 07:58:48', '2026-08-25 07:58:48'),
	(70, 'general', 'opening_hours', 'Mo-Su 08:00-20:00', '2026-08-25 07:58:48', '2026-08-25 07:58:48'),
	(71, 'general', 'latitude', '', '2026-08-25 07:58:48', '2026-08-25 07:58:48'),
	(72, 'general', 'longitude', '', '2026-08-25 07:58:48', '2026-08-25 07:58:48'),
	(82, 'reviews', 'enabled', '1', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(83, 'reviews', 'home_limit', '6', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(84, 'reviews', 'google_enabled', '1', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(85, 'reviews', 'google_account_id', '', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(86, 'reviews', 'google_location_id', '', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(87, 'reviews', 'google_place_id', '', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(88, 'reviews', 'google_review_url', '', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(89, 'reviews', 'tripadvisor_enabled', '1', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(90, 'reviews', 'tripadvisor_location_id', '', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(91, 'reviews', 'tripadvisor_review_url', '', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(92, 'reviews', 'request_enabled', '1', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(93, 'legal', 'privacy_title', 'Privacy Policy', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(94, 'legal', 'privacy_last_updated', '16 August 2026', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(95, 'legal', 'privacy_meta_title', 'Privacy Policy | Big Kahuna Car Hire', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(96, 'legal', 'privacy_meta_description', 'Learn how Big Kahuna Car Hire collects, uses, protects and manages customer information, booking data, payment information and review integrations.', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(97, 'legal', 'privacy_policy', 'Big Kahuna Car Hire respects your privacy and is committed to protecting personal information provided when you browse our website, request a quote, make a booking, contact us, create a customer account, make a payment, or interact with our review services.\n\n1. INFORMATION WE COLLECT\n\nWe may collect information needed to provide car rental services, including your first name, last name, phone number, email address, identification or passport details, driving licence details, booking dates, pickup and drop-off locations, selected vehicle, payment information, communications with our team, and information required for vehicle handover and return.\n\nWe may also collect technical information such as IP address, browser/device information, pages visited and security or diagnostic information when you use our website.\n\n2. HOW WE USE YOUR INFORMATION\n\nWe use information to process and manage bookings, verify rental eligibility, communicate about reservations, provide customer support, process payments, manage vehicle handovers and returns, prevent fraud or misuse, maintain accounting and operational records, improve our website and services, and comply with applicable legal obligations.\n\n3. PAYMENTS\n\nOnline payments are processed through Paystack. Big Kahuna Car Hire does not store your full card number or payment authentication credentials on our own servers. Payment processing is handled by the payment provider according to its own privacy and security practices. We retain transaction information needed to identify and reconcile a payment with a booking.\n\n4. GOOGLE BUSINESS PROFILE AND REVIEWS\n\nBig Kahuna Car Hire may connect its Google Business Profile to our administrative system to retrieve reviews associated with our own business listing and display them on this website. This connection is authorized by an administrator who has permission to manage the Big Kahuna Car Hire Business Profile.\n\nGoogle Business Profile review information may include reviewer name, rating, review text, review date, review URL and related review metadata. We use this information to manage and display customer feedback and do not sell Google data or use it for unrelated advertising.\n\n5. TRIPADVISOR AND OTHER REVIEW SERVICES\n\nWhere enabled, we may use an authorized third-party review service to retrieve and display reviews relating to Big Kahuna Car Hire. The information displayed is used for reputation management and customer information. Third-party services remain subject to their own terms and privacy policies.\n\n6. SHARING INFORMATION\n\nWe may share information with service providers who help us operate the business, such as payment, hosting, communication, analytics, security and authorized review services. We may also disclose information where required by law, legal process, or to protect the rights, property or safety of Big Kahuna Car Hire, our customers or others.\n\nWe do not sell customer personal information.\n\n7. DATA SECURITY\n\nWe use reasonable technical and organizational safeguards to protect information against unauthorized access, loss, misuse or alteration. Access to administrative systems is restricted according to user permissions, and sensitive API credentials are kept server-side.\n\n8. DATA RETENTION\n\nWe retain booking, payment, customer and operational records for as long as reasonably necessary for business, accounting, dispute resolution, security and legal purposes. Review data may be retained while the relevant review integration is enabled or while it is needed for website and reputation management.\n\n9. COOKIES AND SESSION DATA\n\nOur website may use cookies or server-side session data required for login, booking workflows, security, preferences and normal website operation. Where analytics or other optional technologies are enabled, the relevant information may be described in our cookie or site settings.\n\n10. YOUR RIGHTS\n\nDepending on applicable Kenyan law, you may have rights regarding access to, correction of, objection to, restriction of, or deletion of personal information, subject to lawful exceptions and our legal or contractual obligations. Contact us using the details below to make a privacy request.\n\n11. THIRD-PARTY SERVICES\n\nLinks to third-party websites and services are provided for convenience. Their privacy practices are governed by their own policies. Big Kahuna Car Hire is not responsible for the privacy practices of external websites.\n\n12. POLICY CHANGES\n\nWe may update this Privacy Policy when our services, systems, legal obligations or third-party integrations change. The current version will be published on this page with its latest update date.\n\n13. CONTACT\n\nFor privacy questions or requests, contact Big Kahuna Car Hire using the contact details published on this page and on our website.', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(98, 'legal', 'terms_title', 'Terms of Service', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(99, 'legal', 'terms_last_updated', '16 August 2026', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(100, 'legal', 'terms_meta_title', 'Terms of Service | Big Kahuna Car Hire', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(101, 'legal', 'terms_meta_description', 'Terms of Service governing the use of the Big Kahuna Car Hire website, accounts, bookings, payments and rental services.', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(102, 'legal', 'terms_of_service', 'These Terms of Service govern your use of the Big Kahuna Car Hire website and related online services. By using the website, creating an account, requesting a service or making a booking, you agree to these terms.\n\n1. ABOUT THE SERVICE\n\nBig Kahuna Car Hire provides vehicle rental and related transport services in Kenya. Vehicle availability, pricing, locations, rental periods and service options may change and are subject to confirmation.\n\n2. ELIGIBILITY\n\nCustomers must provide accurate information and meet the applicable age, identification, driving licence and rental requirements for the vehicle and service selected. Big Kahuna Car Hire may request additional documentation before handover.\n\n3. BOOKINGS\n\nA booking request is not necessarily a confirmed rental until Big Kahuna Car Hire confirms the reservation. Customers are responsible for reviewing booking dates, pickup and return locations, selected vehicle, driver option and total price before completing a booking.\n\n4. PAYMENTS\n\nWhere online payment is available, payments are processed through Paystack. A booking deposit or other amount may be requested according to the current payment settings and booking terms. Any remaining balance must be paid according to the rental agreement before or during vehicle handover as applicable.\n\n5. VEHICLE HANDOVER AND RETURN\n\nThe vehicle may be inspected by both the customer and Big Kahuna Car Hire before handover and on return. The customer is responsible for returning the vehicle at the agreed time, location, condition and fuel level, subject to the applicable rental agreement.\n\n6. DAMAGE, LOSS AND LIABILITY\n\nThe customer may be responsible for damage, loss, theft, unauthorized use, traffic offences, penalties or other charges arising during the rental period, subject to the rental agreement, insurance arrangements and applicable law.\n\n7. CANCELLATIONS AND CHANGES\n\nCancellation, rescheduling and refund terms depend on the booking and rental agreement. Customers should contact Big Kahuna Car Hire as soon as possible if they need to change or cancel a booking.\n\n8. ACCEPTABLE USE\n\nYou must not use the website or rental service for unlawful activity, fraud, abuse, unauthorized access, interference with our systems, or any activity that could endanger people or property.\n\n9. ACCOUNTS\n\nWhere customer accounts are available, you are responsible for keeping your login credentials confidential and for providing accurate account information. Notify us promptly if you suspect unauthorized access.\n\n10. THIRD-PARTY SERVICES\n\nThe website may use third-party services including payment providers, mapping services, analytics tools and review platforms. Their own terms may apply to your interaction with those services.\n\n11. WEBSITE CONTENT\n\nWe aim to keep vehicle information, prices, availability and other content accurate, but errors or changes may occur. We may correct information and update the website without prior notice.\n\n12. GOVERNING LAW\n\nThese Terms of Service are governed by the laws of Kenya, subject to any mandatory rights and protections that apply to customers.\n\n13. CONTACT\n\nIf you have questions about these Terms of Service or a booking, contact Big Kahuna Car Hire using the contact information published on the website.', '2026-08-25 07:58:58', '2026-08-25 07:58:58'),
	(103, 'notifications', 'whatsapp_provider', 'callmebot', '2026-08-25 07:58:59', '2026-08-25 07:58:59'),
	(104, 'notifications', 'whatsapp_customer_enabled', '0', '2026-08-25 07:58:59', '2026-08-25 07:58:59'),
	(105, 'notifications', 'whatsapp_template_booking_received', 'booking_received', '2026-08-25 07:58:59', '2026-08-25 07:58:59'),
	(106, 'notifications', 'whatsapp_template_booking_confirmed', 'booking_confirmed', '2026-08-25 07:58:59', '2026-08-25 07:58:59'),
	(107, 'notifications', 'whatsapp_template_payment_received', 'payment_received', '2026-08-25 07:58:59', '2026-08-25 07:58:59'),
	(108, 'notifications', 'whatsapp_template_pickup_reminder', 'pickup_reminder', '2026-08-25 07:58:59', '2026-08-25 07:58:59'),
	(109, 'notifications', 'whatsapp_template_admin_new_booking', 'admin_new_booking', '2026-08-25 07:58:59', '2026-08-25 07:58:59'),
	(110, 'notifications', 'whatsapp_template_admin_payment_received', 'admin_payment_received', '2026-08-25 07:58:59', '2026-08-25 07:58:59'),
	(111, 'notifications', 'whatsapp_template_admin_status_changed', 'admin_status_changed', '2026-08-25 07:58:59', '2026-08-25 07:58:59'),
	(112, 'notifications', 'whatsapp_inbox_enabled', '0', '2026-08-25 07:59:00', '2026-08-25 07:59:00'),
	(113, 'notifications', 'whatsapp_reminders_enabled', '0', '2026-08-25 07:59:00', '2026-08-25 07:59:00'),
	(114, 'notifications', 'whatsapp_reminder_hours', '24', '2026-08-25 07:59:00', '2026-08-25 07:59:00'),
	(116, 'notifications', 'whatsapp_template_payment_due', 'payment_due', '2026-08-25 07:59:01', '2026-08-25 07:59:01'),
	(117, 'notifications', 'whatsapp_template_return_reminder', 'return_reminder', '2026-08-25 07:59:01', '2026-08-25 07:59:01'),
	(118, 'notifications', 'whatsapp_template_rental_completed', 'rental_completed', '2026-08-25 07:59:01', '2026-08-25 07:59:01'),
	(119, 'notifications', 'whatsapp_template_review_request', 'review_request', '2026-08-25 07:59:01', '2026-08-25 07:59:01'),
	(120, 'notifications', 'whatsapp_template_admin_payment_due', 'admin_payment_due', '2026-08-25 07:59:01', '2026-08-25 07:59:01'),
	(121, 'notifications', 'whatsapp_payment_due_enabled', '1', '2026-08-25 07:59:01', '2026-08-25 07:59:01'),
	(122, 'notifications', 'whatsapp_return_reminders_enabled', '1', '2026-08-25 07:59:01', '2026-08-25 07:59:01'),
	(123, 'notifications', 'whatsapp_post_rental_enabled', '1', '2026-08-25 07:59:01', '2026-08-25 07:59:01'),
	(124, 'notifications', 'whatsapp_payment_due_hours', '2', '2026-08-25 07:59:01', '2026-08-25 07:59:01'),
	(125, 'notifications', 'whatsapp_return_reminder_hours', '4', '2026-08-25 07:59:01', '2026-08-25 07:59:01'),
	(126, 'notifications', 'whatsapp_review_delay_hours', '24', '2026-08-25 07:59:01', '2026-08-25 07:59:01');

-- Dumping structure for table bigkahuna_carhire_db.testimonials
CREATE TABLE IF NOT EXISTS `testimonials` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_name` varchar(150) NOT NULL,
  `client_role` varchar(150) DEFAULT NULL,
  `rating` tinyint(3) unsigned NOT NULL DEFAULT 5,
  `message` text NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT 1,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.testimonials: ~1 rows (approximately)

-- Dumping structure for table bigkahuna_carhire_db.users
CREATE TABLE IF NOT EXISTS `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `first_name` varchar(100) NOT NULL,
  `last_name` varchar(100) NOT NULL,
  `email` varchar(150) NOT NULL,
  `phone` varchar(30) DEFAULT NULL,
  `password_hash` varchar(255) NOT NULL,
  `role_id` int(11) NOT NULL,
  `status` enum('active','suspended') NOT NULL DEFAULT 'active',
  `last_login_at` timestamp NULL DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`),
  KEY `role_id` (`role_id`),
  CONSTRAINT `1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.users: ~1 rows (approximately)
INSERT IGNORE INTO `users` (`id`, `first_name`, `last_name`, `email`, `phone`, `password_hash`, `role_id`, `status`, `last_login_at`, `created_at`, `updated_at`) VALUES
	(1, 'Big', 'Kahuna', 'alumasinde@gmail.com', '0725034005', '$2a$12$WuzWfXcyB7JGpRWLjuNQ9O04xqOj6CkziFZoH6cIS2hms2w8OWCba', 1, 'active', '2026-08-25 17:06:08', '2026-08-25 07:58:39', '2026-08-25 17:06:08');

-- Dumping structure for table bigkahuna_carhire_db.vehicle_documents
CREATE TABLE IF NOT EXISTS `vehicle_documents` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `document_type` enum('logbook','insurance','inspection','roadworthy','permit','lease','other') NOT NULL DEFAULT 'other',
  `title` varchar(180) NOT NULL,
  `document_number` varchar(100) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `issued_date` date DEFAULT NULL,
  `expiry_date` date DEFAULT NULL,
  `notes` text DEFAULT NULL,
  `status` enum('active','expired','replaced') NOT NULL DEFAULT 'active',
  `uploaded_by` int(11) DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `uploaded_by` (`uploaded_by`),
  KEY `idx_documents_car_type` (`car_id`,`document_type`),
  KEY `idx_documents_expiry` (`status`,`expiry_date`),
  CONSTRAINT `1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`uploaded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.vehicle_documents: ~0 rows (approximately)

-- Dumping structure for table bigkahuna_carhire_db.vehicle_maintenance
CREATE TABLE IF NOT EXISTS `vehicle_maintenance` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `maintenance_type` enum('service','repair','inspection','tyres','brakes','oil_change','other') NOT NULL DEFAULT 'service',
  `title` varchar(180) NOT NULL,
  `description` text DEFAULT NULL,
  `service_date` date DEFAULT NULL,
  `due_date` date DEFAULT NULL,
  `odometer_km` decimal(10,1) DEFAULT NULL,
  `due_odometer_km` decimal(10,1) DEFAULT NULL,
  `cost` decimal(10,2) NOT NULL DEFAULT 0.00,
  `vendor` varchar(180) DEFAULT NULL,
  `status` enum('scheduled','in_progress','completed','cancelled') NOT NULL DEFAULT 'scheduled',
  `created_by` int(11) DEFAULT NULL,
  `completed_by` int(11) DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  KEY `created_by` (`created_by`),
  KEY `completed_by` (`completed_by`),
  KEY `idx_maintenance_car_status` (`car_id`,`status`),
  KEY `idx_maintenance_due` (`status`,`due_date`),
  KEY `idx_maintenance_odometer` (`status`,`due_odometer_km`),
  CONSTRAINT `1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`created_by`) REFERENCES `users` (`id`) ON DELETE SET NULL,
  CONSTRAINT `3` FOREIGN KEY (`completed_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.vehicle_maintenance: ~0 rows (approximately)

-- Dumping structure for table bigkahuna_carhire_db.vehicle_odometer_logs
CREATE TABLE IF NOT EXISTS `vehicle_odometer_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `car_id` int(11) NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `reading_km` decimal(10,1) NOT NULL,
  `reading_type` enum('manual','checkout','return','service') NOT NULL DEFAULT 'manual',
  `recorded_at` datetime NOT NULL DEFAULT current_timestamp(),
  `recorded_by` int(11) DEFAULT NULL,
  `notes` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `booking_id` (`booking_id`),
  KEY `recorded_by` (`recorded_by`),
  KEY `idx_odometer_car_date` (`car_id`,`recorded_at`),
  CONSTRAINT `1` FOREIGN KEY (`car_id`) REFERENCES `cars` (`id`) ON DELETE CASCADE,
  CONSTRAINT `2` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `3` FOREIGN KEY (`recorded_by`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- Dumping data for table bigkahuna_carhire_db.vehicle_odometer_logs: ~1 rows (approximately)
INSERT IGNORE INTO `vehicle_odometer_logs` (`id`, `car_id`, `booking_id`, `reading_km`, `reading_type`, `recorded_at`, `recorded_by`, `notes`) VALUES
	(1, 2, NULL, 2438.0, 'checkout', '2026-08-25 11:53:53', 1, 'Checkout inspection');

-- Dumping structure for table bigkahuna_carhire_db.whatsapp_conversations
CREATE TABLE IF NOT EXISTS `whatsapp_conversations` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `phone` varchar(30) NOT NULL,
  `customer_name` varchar(150) DEFAULT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `status` enum('open','closed') NOT NULL DEFAULT 'open',
  `last_inbound_at` datetime DEFAULT NULL,
  `last_outbound_at` datetime DEFAULT NULL,
  `unread_count` int(10) unsigned NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `phone` (`phone`),
  KEY `idx_wa_conv_status_updated` (`status`,`updated_at`),
  KEY `idx_wa_conv_booking` (`booking_id`),
  CONSTRAINT `fk_wa_conv_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bigkahuna_carhire_db.whatsapp_conversations: ~0 rows (approximately)

-- Dumping structure for table bigkahuna_carhire_db.whatsapp_messages
CREATE TABLE IF NOT EXISTS `whatsapp_messages` (
  `id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  `conversation_id` bigint(20) unsigned NOT NULL,
  `booking_id` int(11) DEFAULT NULL,
  `direction` enum('inbound','outbound') NOT NULL,
  `message_type` varchar(40) NOT NULL DEFAULT 'text',
  `body` text DEFAULT NULL,
  `provider_message_id` varchar(190) DEFAULT NULL,
  `provider_status` varchar(40) DEFAULT NULL,
  `media_url` varchar(500) DEFAULT NULL,
  `raw_payload` longtext DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_wa_provider_message` (`provider_message_id`),
  KEY `idx_wa_msg_conversation_created` (`conversation_id`,`created_at`),
  KEY `idx_wa_msg_provider` (`provider_message_id`),
  KEY `idx_wa_msg_booking_created` (`booking_id`,`created_at`),
  CONSTRAINT `fk_wa_msg_booking` FOREIGN KEY (`booking_id`) REFERENCES `bookings` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_wa_msg_conversation` FOREIGN KEY (`conversation_id`) REFERENCES `whatsapp_conversations` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dumping data for table bigkahuna_carhire_db.whatsapp_messages: ~0 rows (approximately)

/*!40103 SET TIME_ZONE=IFNULL(@OLD_TIME_ZONE, 'system') */;
/*!40101 SET SQL_MODE=IFNULL(@OLD_SQL_MODE, '') */;
/*!40014 SET FOREIGN_KEY_CHECKS=IFNULL(@OLD_FOREIGN_KEY_CHECKS, 1) */;
/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40111 SET SQL_NOTES=IFNULL(@OLD_SQL_NOTES, 1) */;
