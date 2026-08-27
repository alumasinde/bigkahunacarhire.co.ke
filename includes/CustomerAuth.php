<?php
declare(strict_types=1);

/**
 * CustomerAuth — session-based authentication for the self-service
 * customer portal (phone + password). Deliberately separate from Auth
 * (admin panel) — different table, different session keys, no RBAC.
 */
final class CustomerAuth
{
    public static function attempt(string $phone, string $password): bool
    {
        $normalizedPhone = (new MpesaService())->normalizePhone($phone);
        $customer = CustomerService::make()->findByPhone($normalizedPhone);

        if (!$customer || !password_verify($password, $customer['password_hash'])) {
            return false;
        }

        session_regenerate_id(true);
        $_SESSION['customer_id']         = (int) $customer['id'];
        $_SESSION['customer_first_name'] = $customer['first_name'];
        $_SESSION['customer_last_name']  = $customer['last_name'];

        CustomerService::make()->touchLogin((int) $customer['id']);

        return true;
    }

    public static function logout(): void
    {
        unset(
            $_SESSION['customer_id'],
            $_SESSION['customer_first_name'],
            $_SESSION['customer_last_name']
        );
    }

    public static function check(): bool
    {
        return !empty($_SESSION['customer_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['customer_id'] ?? null;
    }

    public static function fullName(): string
    {
        return trim(($_SESSION['customer_first_name'] ?? '') . ' ' . ($_SESSION['customer_last_name'] ?? ''));
    }

    public static function requireLogin(): void
    {
        if (!self::check()) {
            flash('error', 'Please sign in to view your account.');
            redirect('account/login');
        }
    }
}
