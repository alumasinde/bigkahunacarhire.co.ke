<?php
declare(strict_types=1);

final class CustomerService
{
    public function __construct(private PDO $db) {}

    public static function make(): self
    {
        return new self(Database::connection());
    }

    public function findByPhone(string $phone): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE phone = :phone LIMIT 1');
        $stmt->execute([':phone' => $phone]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM customers WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function updatePassword(int $id, string $passwordHash): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE customers SET password_hash = :hash, must_change_password = 0 WHERE id = :id'
        );
        return $stmt->execute([':hash' => $passwordHash, ':id' => $id]);
    }

    public function touchLogin(int $id): void
    {
        $stmt = $this->db->prepare('UPDATE customers SET last_login_at = NOW() WHERE id = :id');
        $stmt->execute([':id' => $id]);
    }
}
