<?php
/**
 * Big Kahuna Car Hire — Core Configuration
 * Framework-free PHP 8.1+, no Composer dependencies.
 */

declare(strict_types=1);

error_reporting(E_ALL);
ini_set('display_errors', getenv('APP_ENV') === 'production' ? '0' : '1');

// ---------------------------------------------------------------
// Environment (.env-style loading without Composer)
// ---------------------------------------------------------------
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) {
            continue;
        }
        [$key, $value] = array_map('trim', explode('=', $line, 2));
        $value = trim($value, "\"' ");
        if (getenv($key) === false) {
            putenv("$key=$value");
            $_ENV[$key] = $value;
        }
    }
}

function env(string $key, mixed $default = null): mixed
{
    $value = getenv($key);
    return $value === false ? $default : $value;
}

// ---------------------------------------------------------------
// Database credentials
// ---------------------------------------------------------------
define('DB_HOST', env('DB_HOST', '127.0.0.1'));
define('DB_NAME', env('DB_NAME', 'bigkahuna_carhire'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_CHARSET', 'utf8mb4');

// ---------------------------------------------------------------
// App-level constants
// ---------------------------------------------------------------
define('APP_ENV', env('APP_ENV', 'development'));
define('APP_URL', rtrim(env('APP_URL', 'http://localhost'), '/'));
define('APP_ROOT', dirname(__DIR__));
define('UPLOADS_PATH', APP_ROOT . '/public_html/assets/images/cars');
define('SESSION_LIFETIME', 60 * 60 * 4); // 4 hours

// ---------------------------------------------------------------
// M-Pesa Daraja (STK Push) — credentials stay in .env, never in the DB
// ---------------------------------------------------------------
define('MPESA_ENV', env('MPESA_ENV', 'sandbox'));
define('MPESA_CONSUMER_KEY', env('MPESA_CONSUMER_KEY', ''));
define('MPESA_CONSUMER_SECRET', env('MPESA_CONSUMER_SECRET', ''));
define('MPESA_SHORTCODE', env('MPESA_SHORTCODE', '174379'));
define('MPESA_PASSKEY', env('MPESA_PASSKEY', ''));
define('MPESA_CALLBACK_URL', env('MPESA_CALLBACK_URL', ''));
define('MPESA_BASE_URL', MPESA_ENV === 'production'
    ? 'https://api.safaricom.co.ke'
    : 'https://sandbox.safaricom.co.ke');

// ---------------------------------------------------------------
// Paystack — online payments (M-PESA, cards and other enabled channels)
// Secret key stays in .env and is never exposed to the browser.
// ---------------------------------------------------------------
define('PAYSTACK_BASE_URL', env('PAYSTACK_BASE_URL', 'https://api.paystack.co'));
define('PAYSTACK_SECRET_KEY', env('PAYSTACK_SECRET_KEY', ''));
define('PAYSTACK_PUBLIC_KEY', env('PAYSTACK_PUBLIC_KEY', ''));
define('PAYSTACK_CURRENCY', env('PAYSTACK_CURRENCY', 'KES'));
define('PAYSTACK_CALLBACK_URL', env('PAYSTACK_CALLBACK_URL', APP_URL . '/payments/paystack/callback'));
define('PAYSTACK_WEBHOOK_URL', env('PAYSTACK_WEBHOOK_URL', APP_URL . '/payments/paystack/webhook'));
define('PAYSTACK_ENABLED', env('PAYSTACK_ENABLED', '1') === '1');

// ---------------------------------------------------------------
// External Reviews — Google Business Profile + Tripadvisor
// API secrets stay in .env. Public/profile identifiers live in MySQL settings.
// ---------------------------------------------------------------
define('GOOGLE_REVIEW_CLIENT_ID', env('GOOGLE_REVIEW_CLIENT_ID', ''));
define('GOOGLE_REVIEW_CLIENT_SECRET', env('GOOGLE_REVIEW_CLIENT_SECRET', ''));
define('GOOGLE_REVIEW_REFRESH_TOKEN', env('GOOGLE_REVIEW_REFRESH_TOKEN', ''));
define('GOOGLE_REVIEW_ACCOUNT_ID', env('GOOGLE_REVIEW_ACCOUNT_ID', ''));
define('GOOGLE_REVIEW_LOCATION_ID', env('GOOGLE_REVIEW_LOCATION_ID', ''));
define('GOOGLE_REVIEW_REDIRECT_URI', env('GOOGLE_REVIEW_REDIRECT_URI', APP_URL . '/admin/reviews/google/callback'));
define('TRIPADVISOR_API_KEY', env('TRIPADVISOR_API_KEY', ''));
define('TRIPADVISOR_LOCATION_ID', env('TRIPADVISOR_LOCATION_ID', ''));
define('TRIPADVISOR_BASE_URL', rtrim(env('TRIPADVISOR_BASE_URL', 'https://terra.tripadvisor.com/api/locations'), '/'));

// ---------------------------------------------------------------
// Outbound email — 'mail' uses PHP's built-in mail() (works out of the
// box on most cPanel hosting), 'smtp' uses a minimal built-in SMTP client
// for providers like Gmail/SendGrid that require real SMTP auth.
// ---------------------------------------------------------------
define('MAIL_DRIVER', env('MAIL_DRIVER', 'mail'));
define('MAIL_FROM_ADDRESS', env('MAIL_FROM_ADDRESS', 'no-reply@bigkahunacarhire.co.ke'));
define('MAIL_FROM_NAME', env('MAIL_FROM_NAME', 'Big Kahuna Car Hire'));
define('SMTP_HOST', env('SMTP_HOST', ''));
define('SMTP_PORT', (int) env('SMTP_PORT', 587));
define('SMTP_USER', env('SMTP_USER', ''));
define('SMTP_PASS', env('SMTP_PASS', ''));
define('SMTP_ENCRYPTION', env('SMTP_ENCRYPTION', 'tls')); // tls | ssl | none

// ---------------------------------------------------------------
// Outbound SMS — Africa's Talking
// ---------------------------------------------------------------
define('AT_ENV', env('AT_ENV', 'sandbox'));
define('AT_USERNAME', env('AT_USERNAME', ''));
define('AT_API_KEY', env('AT_API_KEY', ''));
define('AT_SENDER_ID', env('AT_SENDER_ID', ''));
define('AT_BASE_URL', AT_ENV === 'production'
    ? 'https://api.africastalking.com/version1/messaging'
    : 'https://api.sandbox.africastalking.com/version1/messaging');

// ---------------------------------------------------------------
// WhatsApp Business Cloud API + temporary CallMeBot fallback for admin alerts
// ---------------------------------------------------------------
define('CALLMEBOT_APIKEY', env('CALLMEBOT_APIKEY', ''));
define('WHATSAPP_CLOUD_API_VERSION', env('WHATSAPP_CLOUD_API_VERSION', 'v23.0'));
define('WHATSAPP_CLOUD_ACCESS_TOKEN', env('WHATSAPP_CLOUD_ACCESS_TOKEN', ''));
define('WHATSAPP_CLOUD_PHONE_NUMBER_ID', env('WHATSAPP_CLOUD_PHONE_NUMBER_ID', ''));
define('WHATSAPP_CLOUD_VERIFY_TOKEN', env('WHATSAPP_CLOUD_VERIFY_TOKEN', ''));
define('WHATSAPP_CLOUD_APP_SECRET', env('WHATSAPP_CLOUD_APP_SECRET', ''));

date_default_timezone_set('Africa/Nairobi');

// ---------------------------------------------------------------
// Bootstrap: DB connection, DB-backed sessions, helpers
// ---------------------------------------------------------------
require_once __DIR__ . '/database.php';
require_once APP_ROOT . '/includes/DbSessionHandler.php';
require_once APP_ROOT . '/includes/SmtpMailer.php';
require_once APP_ROOT . '/includes/functions.php';
require_once APP_ROOT . '/includes/StyleEngine.php';
require_once APP_ROOT . '/includes/Auth.php';
require_once APP_ROOT . '/includes/CustomerAuth.php';

// Start DB-backed session (unless already active, e.g. CLI seed scripts)
if (session_status() === PHP_SESSION_NONE && php_sapi_name() !== 'cli') {
    $handler = new DbSessionHandler(Database::connection());
    session_set_save_handler($handler, true);
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('bigkahuna_sid');
    session_start();
}
