-- =========================================================
-- Big Kahuna Car Hire — Paystack settings / direct M-Pesa UI off
-- Safe to run more than once.
-- =========================================================

INSERT INTO settings (setting_group, setting_key, setting_value)
VALUES
('paystack', 'enabled', '1'),
('paystack', 'deposit_percentage', '30'),
('paystack', 'display_label', 'Pay securely'),
('paystack', 'checkout_description', 'Pay your booking deposit securely using the payment methods available through Paystack.'),
('paystack', 'channels', 'card,mobile_money,bank_transfer')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- Direct M-Pesa is intentionally dormant for now. Paystack remains the
-- customer-facing payment gateway. Existing historical M-Pesa payment
-- records are preserved and are not deleted.
INSERT INTO settings (setting_group, setting_key, setting_value)
VALUES
('mpesa', 'enabled', '0'),
('mpesa', 'stk_enabled', '0'),
('mpesa', 'manual_enabled', '0')
ON DUPLICATE KEY UPDATE setting_value = '0';

SELECT 'Paystack settings installed; direct M-Pesa UI disabled.' AS status;
