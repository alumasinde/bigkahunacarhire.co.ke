-- Phase 4: WhatsApp inbox, conversation history and reminder support.
-- Additive migration only.
CREATE TABLE IF NOT EXISTS whatsapp_conversations (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    phone VARCHAR(30) NOT NULL UNIQUE,
    customer_name VARCHAR(150) NULL,
    booking_id INT NULL,
    status ENUM('open','closed') NOT NULL DEFAULT 'open',
    last_inbound_at DATETIME NULL,
    last_outbound_at DATETIME NULL,
    unread_count INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_wa_conv_status_updated (status, updated_at),
    INDEX idx_wa_conv_booking (booking_id),
    CONSTRAINT fk_wa_conv_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS whatsapp_messages (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id BIGINT UNSIGNED NOT NULL,
    booking_id INT NULL,
    direction ENUM('inbound','outbound') NOT NULL,
    message_type VARCHAR(40) NOT NULL DEFAULT 'text',
    body TEXT NULL,
    provider_message_id VARCHAR(190) NULL,
    provider_status VARCHAR(40) NULL,
    media_url VARCHAR(500) NULL,
    raw_payload LONGTEXT NULL,
    created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_wa_msg_conversation_created (conversation_id, created_at),
    INDEX idx_wa_msg_provider (provider_message_id),
    INDEX idx_wa_msg_booking_created (booking_id, created_at),
    CONSTRAINT fk_wa_msg_conversation FOREIGN KEY (conversation_id) REFERENCES whatsapp_conversations(id) ON DELETE CASCADE,
    CONSTRAINT fk_wa_msg_booking FOREIGN KEY (booking_id) REFERENCES bookings(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO role_permissions (role_id, permission_id)
SELECT r.id, p.id FROM roles r JOIN permissions p ON p.name='messages.view' WHERE r.name='staff'
  AND NOT EXISTS (SELECT 1 FROM role_permissions rp WHERE rp.role_id=r.id AND rp.permission_id=p.id);

INSERT INTO settings (setting_group, setting_key, setting_value) VALUES
('notifications','whatsapp_inbox_enabled','0'),
('notifications','whatsapp_reminders_enabled','0'),
('notifications','whatsapp_template_pickup_reminder','pickup_reminder'),
('notifications','whatsapp_reminder_hours','24')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

SELECT 'Phase 4 WhatsApp operations migration complete.' AS status;
