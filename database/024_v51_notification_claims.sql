-- V5.1: concurrency-safe notification claims.
-- Production-safe additive migration. Does not modify existing data.

CREATE TABLE IF NOT EXISTS notification_claims (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    booking_id INT NOT NULL,
    channel ENUM('email','sms','whatsapp') NOT NULL,
    event_key VARCHAR(80) NOT NULL,
    claimed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_notification_claim (booking_id, channel, event_key),
    INDEX idx_notification_claimed_at (claimed_at),
    CONSTRAINT fk_notification_claim_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SELECT 'V5.1 notification claim migration complete.' AS status;
