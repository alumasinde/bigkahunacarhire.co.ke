<?php
declare(strict_types=1);

/**
 * Database — thin PDO singleton wrapper.
 * Matches the PDO/MySQL pattern used across AlbaTech Solutions PHP projects.
 */
final class Database
{
    private static ?PDO $instance = null;

    public static function connection(): PDO
    {
        if (self::$instance === null) {
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                DB_HOST,
                DB_NAME,
                DB_CHARSET
            );

            try {
                self::$instance = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE  => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES    => false,
                ]);
            } catch (PDOException $e) {
                // Never leak raw DB errors to the browser in production
                error_log('[DB CONNECTION ERROR] ' . $e->getMessage());
                if (APP_ENV === 'production') {
                    http_response_code(500);
                    die('Service temporarily unavailable. Please try again shortly.');
                }
                die('DB connection failed: ' . $e->getMessage());
            }
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}
