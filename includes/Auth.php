<?php
declare(strict_types=1);

/**
 * Auth — session-based authentication + RBAC permission checks against
 * the roles / permissions / role_permissions tables.
 */
final class Auth
{
    private static ?array $permissionCache = null;

    public static function attempt(string $email, string $password): bool
    {
        $stmt = Database::connection()->prepare(
            'SELECT u.*, r.name AS role_name
             FROM users u
             JOIN roles r ON r.id = u.role_id
             WHERE u.email = :email AND u.status = "active" LIMIT 1'
        );
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);

        $_SESSION['user_id']    = (int) $user['id'];
        $_SESSION['first_name'] = $user['first_name'];
        $_SESSION['last_name']  = $user['last_name'];
        $_SESSION['role']       = $user['role_name'];
        $_SESSION['role_id']    = (int) $user['role_id'];

        $update = Database::connection()->prepare('UPDATE users SET last_login_at = NOW() WHERE id = :id');
        $update->execute([':id' => $user['id']]);

        return true;
    }

    public static function logout(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
        }
        session_destroy();
    }

    public static function check(): bool
    {
        return !empty($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function user(): ?array
    {
        return self::check() ? [
            'id'         => $_SESSION['user_id'],
            'first_name' => $_SESSION['first_name'],
            'last_name'  => $_SESSION['last_name'],
            'role'       => $_SESSION['role'],
        ] : null;
    }

    public static function fullName(): string
    {
        return trim(($_SESSION['first_name'] ?? '') . ' ' . ($_SESSION['last_name'] ?? ''));
    }

    /**
     * @return string[] permission names granted to the current user's role
     */
    private static function permissions(): array
    {
        if (self::$permissionCache !== null) {
            return self::$permissionCache;
        }

        if (empty($_SESSION['role_id'])) {
            return self::$permissionCache = [];
        }

        $stmt = Database::connection()->prepare(
            'SELECT p.name FROM permissions p
             JOIN role_permissions rp ON rp.permission_id = p.id
             WHERE rp.role_id = :role_id'
        );
        $stmt->execute([':role_id' => $_SESSION['role_id']]);

        return self::$permissionCache = array_column($stmt->fetchAll(), 'name');
    }

    public static function can(string $permission): bool
    {
        return in_array($permission, self::permissions(), true);
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Please sign in to continue.');
            redirect('admin/login');
        }
    }

    public static function requirePermission(string $permission): void
    {
        self::requireLogin();
        if (!self::can($permission)) {
            http_response_code(403);
            die('403 — You do not have permission to access this page.');
        }
    }
}
