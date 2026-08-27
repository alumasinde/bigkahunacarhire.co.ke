<?php
/**
 * One-off CLI helper: sets/resets the super_admin password.
 *
 * Usage (run on your server, in the project root):
 *   php database/set-admin-password.php admin@bigkahunacarhire.co.ke "YourNewPassword123!"
 *
 * The placeholder hash shipped in database/001_schema.sql will NOT match any real
 * password — always run this script once after importing the schema.
 */

declare(strict_types=1);

if (php_sapi_name() !== 'cli') {
    die("This script must be run from the command line.\n");
}

require_once __DIR__ . '/../config/config.php';

[$script, $email, $password] = array_pad($argv, 3, null);

if (!$email || !$password) {
    echo "Usage: php set-admin-password.php <email> <new_password>\n";
    exit(1);
}

$hash = password_hash($password, PASSWORD_BCRYPT);

$stmt = Database::connection()->prepare('UPDATE users SET password_hash = :hash WHERE email = :email');
$stmt->execute([':hash' => $hash, ':email' => $email]);

if ($stmt->rowCount() > 0) {
    echo "Password updated for {$email}.\n";
} else {
    echo "No user found with email {$email}. Check the users table.\n";
}
