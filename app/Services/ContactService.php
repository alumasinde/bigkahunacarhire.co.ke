<?php
declare(strict_types=1);

final class ContactService
{
    public function __construct(private PDO $db) {}

    public static function make(): self
    {
        return new self(Database::connection());
    }

    public function create(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO contact_messages (name, email, phone, subject, message)
             VALUES (:name, :email, :phone, :subject, :message)'
        );
        $stmt->execute([
            ':name'    => $data['name'],
            ':email'   => $data['email'],
            ':phone'   => $data['phone'] ?? null,
            ':subject' => $data['subject'] ?? null,
            ':message' => $data['message'],
        ]);
        return (int) $this->db->lastInsertId();
    }

    public function all(): array
    {
        return $this->db->query('SELECT * FROM contact_messages ORDER BY created_at DESC')->fetchAll();
    }

    public function find(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM contact_messages WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function markReplied(int $id, string $replyText, ?int $adminUserId): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE contact_messages SET status = "replied", admin_reply = :reply, replied_by = :by, replied_at = NOW()
             WHERE id = :id'
        );
        return $stmt->execute([':reply' => $replyText, ':by' => $adminUserId, ':id' => $id]);
    }

    public function markRead(int $id): bool
    {
        $stmt = $this->db->prepare('UPDATE contact_messages SET status = "read" WHERE id = :id AND status = "new"');
        return $stmt->execute([':id' => $id]);
    }
}
