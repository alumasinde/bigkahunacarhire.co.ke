CREATE TABLE IF NOT EXISTS reviews (
 id INT AUTO_INCREMENT PRIMARY KEY, source VARCHAR(30) NOT NULL, external_id VARCHAR(255) NOT NULL,
 reviewer_name VARCHAR(180) NOT NULL, reviewer_photo TEXT DEFAULT NULL, rating TINYINT UNSIGNED NOT NULL DEFAULT 5,
 title VARCHAR(500) DEFAULT NULL, comment TEXT DEFAULT NULL, review_date DATETIME NOT NULL, review_url TEXT DEFAULT NULL,
 owner_reply TEXT DEFAULT NULL, raw_json LONGTEXT DEFAULT NULL, is_visible TINYINT(1) NOT NULL DEFAULT 1,
 synced_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 UNIQUE KEY uniq_review_source_external(source,external_id), KEY idx_reviews_visible_date(is_visible,review_date), KEY idx_reviews_source(source)
) ENGINE=InnoDB;
INSERT INTO settings(setting_group,setting_key,setting_value) VALUES
('reviews','enabled','1'),('reviews','home_limit','6'),('reviews','google_enabled','1'),('reviews','google_account_id',''),('reviews','google_location_id',''),('reviews','google_place_id',''),('reviews','google_review_url',''),
('reviews','tripadvisor_enabled','1'),('reviews','tripadvisor_location_id',''),('reviews','tripadvisor_review_url',''),('reviews','request_enabled','1')
ON DUPLICATE KEY UPDATE setting_value=VALUES(setting_value);
