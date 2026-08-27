-- =========================================================
-- Big Kahuna Car Hire — Database Schema
-- MySQL 8+ / PDO
-- =========================================================

CREATE DATABASE IF NOT EXISTS bigkahuna_carhire
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

USE bigkahuna_carhire;

-- ---------------------------------------------------------
-- RBAC: roles, permissions, role_permissions
-- ---------------------------------------------------------
CREATE TABLE roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,      -- e.g. 'cars.manage', 'bookings.view'
    description VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE role_permissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    role_id INT NOT NULL,
    permission_id INT NOT NULL,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE,
    UNIQUE KEY uniq_role_permission (role_id, permission_id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Users
-- ---------------------------------------------------------
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    phone VARCHAR(30) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    status ENUM('active','suspended') NOT NULL DEFAULT 'active',
    last_login_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (role_id) REFERENCES roles(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Sessions (DB-backed session handler)
-- ---------------------------------------------------------
CREATE TABLE sessions (
    id VARCHAR(128) PRIMARY KEY,       -- PHP session id
    user_id INT DEFAULT NULL,
    ip_address VARCHAR(45) DEFAULT NULL,
    user_agent VARCHAR(255) DEFAULT NULL,
    payload LONGTEXT,
    last_activity INT NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Settings (site-wide config + SEO + page titles, key/value, grouped)
-- ---------------------------------------------------------
CREATE TABLE settings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    setting_group VARCHAR(50) NOT NULL DEFAULT 'general',  -- general | seo | contact | social
    setting_key VARCHAR(150) NOT NULL,
    setting_value TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_group_key (setting_group, setting_key)
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Car categories
-- ---------------------------------------------------------
CREATE TABLE car_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL UNIQUE,
    slug VARCHAR(120) NOT NULL UNIQUE,
    description VARCHAR(255) DEFAULT NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Cars (fleet)
-- ---------------------------------------------------------
CREATE TABLE cars (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT DEFAULT NULL,
    name VARCHAR(150) NOT NULL,
    slug VARCHAR(180) NOT NULL UNIQUE,
    brand VARCHAR(80) NOT NULL,
    model VARCHAR(80) NOT NULL,
    year YEAR DEFAULT NULL,
    transmission ENUM('automatic','manual') NOT NULL DEFAULT 'automatic',
    fuel_type ENUM('petrol','diesel','hybrid','electric') NOT NULL DEFAULT 'petrol',
    seats TINYINT UNSIGNED NOT NULL DEFAULT 4,
    doors TINYINT UNSIGNED NOT NULL DEFAULT 4,
    price_per_day DECIMAL(10,2) NOT NULL,
    plate_number VARCHAR(20) DEFAULT NULL,
    location VARCHAR(120) DEFAULT 'Nairobi',
    description TEXT,
    image_path VARCHAR(255) DEFAULT NULL,
    status ENUM('available','booked','maintenance','retired') NOT NULL DEFAULT 'available',
    featured TINYINT(1) NOT NULL DEFAULT 0,
    meta_title VARCHAR(255) DEFAULT NULL,
    meta_description VARCHAR(500) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES car_categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE car_images (
    id INT AUTO_INCREMENT PRIMARY KEY,
    car_id INT NOT NULL,
    image_path VARCHAR(255) NOT NULL,
    sort_order INT NOT NULL DEFAULT 0,
    FOREIGN KEY (car_id) REFERENCES cars(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Customers (self-service portal — separate from admin `users`)
-- ---------------------------------------------------------
CREATE TABLE customers (
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

-- ---------------------------------------------------------
-- Bookings
-- ---------------------------------------------------------
CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_ref VARCHAR(20) NOT NULL UNIQUE,
    user_id INT DEFAULT NULL,             -- staff/admin user, if created from the admin panel
    customer_id INT DEFAULT NULL,         -- self-service customer account this booking belongs to
    car_id INT NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    id_number VARCHAR(30) NOT NULL,
    driving_license_number VARCHAR(30) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) NOT NULL,
    pickup_location VARCHAR(150) NOT NULL,
    dropoff_location VARCHAR(150) NOT NULL,
    pickup_date DATETIME NOT NULL,
    return_date DATETIME NOT NULL,
    driver_option ENUM('self_drive','with_driver') NOT NULL DEFAULT 'self_drive',
    total_days INT NOT NULL DEFAULT 1,
    total_price DECIMAL(10,2) NOT NULL,
    status ENUM('pending','confirmed','ongoing','completed','cancelled') NOT NULL DEFAULT 'pending',
    notes TEXT,
    terms_accepted TINYINT(1) NOT NULL DEFAULT 0,
    terms_accepted_at DATETIME DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (car_id) REFERENCES cars(id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Payments (M-Pesa Daraja STK Push)
-- ---------------------------------------------------------
CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    checkout_request_id VARCHAR(60) DEFAULT NULL UNIQUE,
    merchant_request_id VARCHAR(60) DEFAULT NULL,
    phone VARCHAR(20) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','completed','failed','cancelled') NOT NULL DEFAULT 'pending',
    mpesa_receipt_number VARCHAR(30) DEFAULT NULL,
    result_code VARCHAR(10) DEFAULT NULL,
    result_desc VARCHAR(255) DEFAULT NULL,
    raw_callback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Contact messages
-- ---------------------------------------------------------
CREATE TABLE contact_messages (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    subject VARCHAR(200) DEFAULT NULL,
    message TEXT NOT NULL,
    status ENUM('new','read','replied') NOT NULL DEFAULT 'new',
    admin_reply TEXT DEFAULT NULL,
    replied_by INT DEFAULT NULL,
    replied_at TIMESTAMP NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (replied_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ---------------------------------------------------------
-- Testimonials (used on home page)
-- ---------------------------------------------------------
CREATE TABLE testimonials (
    id INT AUTO_INCREMENT PRIMARY KEY,
    client_name VARCHAR(150) NOT NULL,
    client_role VARCHAR(150) DEFAULT NULL,
    rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
    message TEXT NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- =========================================================
-- Seed data
-- =========================================================

-- Roles
INSERT INTO roles (name, description) VALUES
('super_admin', 'Full system access'),
('manager', 'Manages fleet, bookings and content'),
('staff', 'Front-desk booking staff');

-- Permissions
INSERT INTO permissions (name, description) VALUES
('cars.view', 'View fleet'),
('cars.manage', 'Create/edit/delete cars'),
('bookings.view', 'View bookings'),
('bookings.manage', 'Update booking status'),
('users.manage', 'Manage admin users'),
('settings.manage', 'Manage site/SEO settings'),
('messages.view', 'View contact messages');

-- Role <-> Permission mapping
INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p WHERE r.name = 'super_admin';

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'manager' AND p.name IN ('cars.view','cars.manage','bookings.view','bookings.manage','messages.view');

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r, permissions p
WHERE r.name = 'staff' AND p.name IN ('cars.view','bookings.view','bookings.manage');

-- Default super admin user (password: KahunaAdmin#2026 — CHANGE AFTER FIRST LOGIN)
INSERT INTO users (first_name, last_name, email, phone, password_hash, role_id, status)
SELECT 'Big', 'Kahuna', 'admin@bigkahunacarhire.co.ke', '0700000000',
       '$2y$10$0hNfeIgS9QFZpnZqrVgypeMesImM0kRZir77w0lN2Zx7lgRCvjjie', -- bcrypt hash of: KahunaAdmin#2026
       r.id, 'active'
FROM roles r WHERE r.name = 'super_admin';

-- Car categories
INSERT INTO car_categories (name, slug, description) VALUES
('Economy', 'economy', 'Fuel-efficient cars for city driving'),
('SUV', 'suv', '4x4 and SUVs for family and off-road trips'),
('Luxury', 'luxury', 'Premium sedans for business and events'),
('Van & Minibus', 'van-minibus', 'Group and safari transport');

-- Settings — general
INSERT INTO settings (setting_group, setting_key, setting_value) VALUES
('general', 'site_name', 'Big Kahuna Car Hire'),
('general', 'tagline', 'Ride the Wave. Drive the Kahuna.'),
('general', 'phone_primary', '+254 700 000 000'),
('general', 'phone_secondary', '+254 733 000 000'),
('general', 'email', 'info@bigkahunacarhire.co.ke'),
('general', 'address', 'Mombasa Road, Nairobi, Kenya'),
('general', 'working_hours', 'Mon - Sun: 6:00 AM - 10:00 PM'),
('general', 'currency', 'KES'),
('general', 'facebook_url', 'https://facebook.com/bigkahunacarhire'),
('general', 'instagram_url', 'https://instagram.com/bigkahunacarhire'),
('general', 'twitter_url', 'https://x.com/bigkahunacarhire'),
('general', 'whatsapp_number', '254700000000'),
('general', 'google_maps_embed', '');

-- Settings — SEO (per-page titles/descriptions + sitewide defaults)
INSERT INTO settings (setting_group, setting_key, setting_value) VALUES
('seo', 'default_meta_title', 'Big Kahuna Car Hire | Self-Drive & Chauffeur Car Rental in Kenya'),
('seo', 'default_meta_description', 'Big Kahuna Car Hire offers affordable self-drive and chauffeur-driven car rentals across Kenya. Economy cars, SUVs, luxury sedans and vans — book online today.'),
('seo', 'default_meta_keywords', 'car hire kenya, car rental nairobi, self drive cars kenya, suv hire kenya, big kahuna car hire'),
('seo', 'og_image', '/assets/images/og-default.jpg'),
('seo', 'google_analytics_id', ''),
('seo', 'google_site_verification', ''),
('seo', 'robots', 'index, follow'),

('seo', 'home_title', 'Big Kahuna Car Hire | Self-Drive & Chauffeur Car Rental in Kenya'),
('seo', 'home_description', 'Book affordable, reliable self-drive and chauffeur car rentals in Kenya. Economy, SUV, luxury and van options with 24/7 support.'),

('seo', 'fleet_title', 'Our Fleet | Big Kahuna Car Hire'),
('seo', 'fleet_description', 'Browse our full fleet of economy cars, SUVs, luxury sedans and vans available for hire across Kenya.'),

('seo', 'about_title', 'About Us | Big Kahuna Car Hire'),
('seo', 'about_description', 'Learn about Big Kahuna Car Hire, a Kenyan-owned car rental company committed to safe, affordable and reliable transport.'),

('seo', 'contact_title', 'Contact Us | Big Kahuna Car Hire'),
('seo', 'contact_description', 'Get in touch with Big Kahuna Car Hire for bookings, quotes and support. Call, WhatsApp or visit us in Nairobi.'),

('seo', 'booking_title', 'Book a Car | Big Kahuna Car Hire'),
('seo', 'booking_description', 'Reserve your car online in minutes with Big Kahuna Car Hire. Fast confirmation, flexible pickup and drop-off.');

-- Settings — Notifications (email/SMS on/off + admin contact for alerts)
INSERT INTO settings (setting_group, setting_key, setting_value) VALUES
('notifications', 'email_enabled', '1'),
('notifications', 'sms_enabled', '1'),
('notifications', 'admin_notification_email', 'admin@bigkahunacarhire.co.ke'),
('notifications', 'admin_notification_phone', '254700000000');

-- Settings — Legal (Terms & Conditions + damage disclaimer, editable in admin)
INSERT INTO settings (setting_group, setting_key, setting_value) VALUES
('legal', 'terms_and_conditions', 'By booking a vehicle with Big Kahuna Car Hire, you agree to the following terms:\n\n1. ELIGIBILITY: The renter must be at least 23 years old, hold a valid national ID or passport, and hold a valid driving license (minimum 2 years driving experience) for the vehicle class booked.\n\n2. DEPOSIT & PAYMENT: A non-refundable booking deposit is required to confirm a reservation. The balance is payable at pickup. Accepted payment methods include M-Pesa and cash.\n\n3. VEHICLE CONDITION: The vehicle will be inspected jointly by the renter and Big Kahuna Car Hire staff before handover and upon return. Both parties will sign off on the vehicle''s condition and fuel level at handover.\n\n4. DAMAGE & LIABILITY: The renter is fully responsible for any damage, loss, or theft of the vehicle occurring during the rental period, including but not limited to collision damage, tyre damage, windscreen damage, interior damage, and loss of accessories. The renter agrees to cover the full cost of repair or replacement, and any applicable insurance excess, for damage not covered by insurance or arising from a breach of these terms (e.g. reckless driving, driving under the influence, use outside agreed geographic limits, or use by an unauthorized driver).\n\n5. TRAFFIC OFFENSES: The renter is liable for all traffic fines, tolls, and penalties incurred during the rental period.\n\n6. LATE RETURNS: Returning the vehicle later than the agreed return date/time without prior arrangement will incur additional daily charges at the standard rate.\n\n7. FUEL POLICY: The vehicle is provided with a set fuel level and must be returned at the same level, or a refueling charge will apply.\n\n8. CANCELLATIONS: Cancellations made less than 24 hours before pickup may forfeit the booking deposit.\n\n9. GOVERNING LAW: These terms are governed by the laws of Kenya.\n\nPlease read these terms carefully. By ticking the box and proceeding, you confirm that you understand and accept full responsibility for the vehicle for the duration of your rental.'),
('legal', 'damage_disclaimer', 'I understand that I am fully responsible for any damage, loss, or theft of the vehicle during my rental period, and I agree to cover the full repair, replacement, or insurance excess cost for any such damage.');

-- Settings — Paystack (API credentials live in .env, never in the database)
INSERT INTO settings (setting_group, setting_key, setting_value) VALUES
('paystack', 'enabled', '1'),
('paystack', 'deposit_percentage', '30'),
('paystack', 'display_label', 'Pay securely'),
('paystack', 'checkout_description', 'Pay your booking deposit securely using the payment methods available through Paystack.'),
('paystack', 'channels', 'card,mobile_money,bank_transfer');

-- Direct M-Pesa is dormant for now. Historical payment records remain supported.
INSERT INTO settings (setting_group, setting_key, setting_value) VALUES
('mpesa', 'enabled', '0'),
('mpesa', 'stk_enabled', '0'),
('mpesa', 'manual_enabled', '0');

-- Sample cars
INSERT INTO cars (category_id, name, slug, brand, model, year, transmission, fuel_type, seats, doors, price_per_day, plate_number, location, description, image_path, status, featured, meta_title, meta_description)
VALUES
(1, 'Toyota Vitz', 'toyota-vitz', 'Toyota', 'Vitz', 2019, 'automatic', 'petrol', 4, 4, 3500.00, 'KDA 101A', 'Nairobi', 'Compact and fuel-efficient, perfect for city driving and errands.', '/assets/images/cars/vitz.jpg', 'available', 1, 'Hire a Toyota Vitz in Nairobi | Big Kahuna Car Hire', 'Affordable Toyota Vitz self-drive hire in Nairobi from KES 3,500/day.'),
(2, 'Toyota Land Cruiser Prado', 'toyota-prado', 'Toyota', 'Prado', 2021, 'automatic', 'diesel', 7, 5, 12000.00, 'KDB 202B', 'Nairobi', 'Rugged and comfortable 4x4, ideal for safaris and family trips.', '/assets/images/cars/prado.jpg', 'available', 1, 'Toyota Prado Hire Kenya | Big Kahuna Car Hire', 'Hire a Toyota Land Cruiser Prado for safaris and off-road travel in Kenya.'),
(3, 'Mercedes-Benz E-Class', 'mercedes-e-class', 'Mercedes-Benz', 'E-Class', 2020, 'automatic', 'petrol', 5, 4, 18000.00, 'KDC 303C', 'Nairobi', 'Premium sedan for executive travel, weddings and events.', '/assets/images/cars/e-class.jpg', 'available', 1, 'Mercedes E-Class Hire Nairobi | Big Kahuna Car Hire', 'Book a Mercedes-Benz E-Class with chauffeur for executive travel in Nairobi.'),
(4, 'Toyota Hiace', 'toyota-hiace', 'Toyota', 'Hiace', 2018, 'manual', 'diesel', 14, 4, 9000.00, 'KDD 404D', 'Nairobi', 'Spacious minibus for group travel, tours and airport transfers.', '/assets/images/cars/hiace.jpg', 'available', 0, 'Toyota Hiace Van Hire Kenya | Big Kahuna Car Hire', 'Hire a 14-seater Toyota Hiace for group travel and tours in Kenya.'),
(1, 'Nissan Note', 'nissan-note', 'Nissan', 'Note', 2020, 'automatic', 'petrol', 4, 4, 3800.00, 'KDE 505E', 'Mombasa', 'Reliable hatchback with great fuel economy for daily use.', '/assets/images/cars/note.jpg', 'available', 0, 'Nissan Note Hire Mombasa | Big Kahuna Car Hire', 'Affordable Nissan Note self-drive hire available in Mombasa.'),
(2, 'Subaru Forester', 'subaru-forester', 'Subaru', 'Forester', 2019, 'automatic', 'petrol', 5, 5, 8000.00, 'KDF 606F', 'Nairobi', 'All-wheel-drive SUV, confident on tarmac and rough roads alike.', '/assets/images/cars/forester.jpg', 'available', 1, 'Subaru Forester Hire Nairobi | Big Kahuna Car Hire', 'Hire a Subaru Forester SUV for city and upcountry travel in Kenya.');

-- Sample testimonials
INSERT INTO testimonials (client_name, client_role, rating, message) VALUES
('Wanjiru K.', 'Nairobi', 5, 'Booked a Prado for a Maasai Mara trip — clean car, on-time pickup, and the driver option made it stress-free.'),
('James O.', 'Mombasa', 5, 'Affordable rates and the online booking was quick. Will hire again for my next upcountry trip.'),
('Amina S.', 'Kisumu', 4, 'Good experience overall. Support team responded fast on WhatsApp when I needed to extend my booking.');
