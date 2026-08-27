-- Big Kahuna V2 cleanup: remove a customer WhatsApp toggle that was never used by runtime code.
-- Keep WhatsApp provider settings under notifications because admin operational alerts still use them.
DELETE FROM settings WHERE setting_group = 'customer' AND setting_key = 'whatsapp_booking_enabled';
