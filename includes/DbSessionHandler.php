<?php
declare(strict_types=1);

/**
 * DbSessionHandler — stores PHP sessions in the `sessions` table instead of
 * disk, so admin sessions are inspectable/revokable from the database (and
 * survive across load-balanced app servers).
 */
final class DbSessionHandler implements SessionHandlerInterface
{
    public function __construct(private PDO $db) {}

    public function open(string $path, string $name): bool
    {
        return true;
    }

    public function close(): bool
    {
        return true;
    }

    public function read(string $id): string|false
    {
        $stmt = $this->db->prepare(
            'SELECT payload FROM sessions WHERE id = :id AND last_activity > :expiry LIMIT 1'
        );
        $stmt->execute([
            ':id'     => $id,
            ':expiry' => time() - SESSION_LIFETIME,
        ]);
        $row = $stmt->fetch();

        return $row ? (string) $row['payload'] : '';
    }

    public function write(string $id, string $data): bool
    {
        $userId = $_SESSION['user_id'] ?? null;

        $stmt = $this->db->prepare(
            'INSERT INTO sessions (id, user_id, ip_address, user_agent, payload, last_activity)
             VALUES (:id, :user_id, :ip, :ua, :payload, :time)
             ON DUPLICATE KEY UPDATE
                user_id = VALUES(user_id),
                ip_address = VALUES(ip_address),
                user_agent = VALUES(user_agent),
                payload = VALUES(payload),
                last_activity = VALUES(last_activity)'
        );

        return $stmt->execute([
            ':id'      => $id,
            ':user_id' => $userId,
            ':ip'      => $_SERVER['REMOTE_ADDR'] ?? null,
            ':ua'      => substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255),
            ':payload' => $data,
            ':time'    => time(),
        ]);
    }

    public function destroy(string $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM sessions WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    public function gc(int $max_lifetime): int|false
    {
        $stmt = $this->db->prepare('DELETE FROM sessions WHERE last_activity < :expiry');
        $stmt->execute([':expiry' => time() - $max_lifetime]);
        return $stmt->rowCount();
    }
}
