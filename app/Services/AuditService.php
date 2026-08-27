<?php
declare(strict_types=1);

final class AuditService
{
    public static function make(): self { return new self(); }

    public function log(string $action, string $description, ?int $bookingId = null, ?string $entityType = null, ?int $entityId = null, array $metadata = []): void
    {
        try {
            $db = Database::connection();
            $stmt = $db->prepare(
                'INSERT INTO audit_logs (user_id, booking_id, action, entity_type, entity_id, description, metadata, ip_address)
                 VALUES (:user_id, :booking_id, :action, :entity_type, :entity_id, :description, :metadata, :ip)'
            );
            $stmt->execute([
                ':user_id' => class_exists('Auth') ? Auth::id() : null,
                ':booking_id' => $bookingId,
                ':action' => $action,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':description' => mb_substr($description, 0, 500),
                ':metadata' => $metadata ? json_encode($metadata, JSON_UNESCAPED_SLASHES) : null,
                ':ip' => $_SERVER['REMOTE_ADDR'] ?? null,
            ]);
        } catch (Throwable $e) {
            error_log('[AUDIT ERROR] '.$e->getMessage());
        }
    }

    public function forBooking(int $bookingId, int $limit = 40): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, CONCAT(u.first_name, " ", u.last_name) AS actor_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             WHERE a.booking_id = :booking_id
             ORDER BY a.created_at DESC, a.id DESC LIMIT '.max(1, min(100, $limit))
        );
        $stmt->execute([':booking_id' => $bookingId]);
        return $stmt->fetchAll();
    }

    public function recent(int $limit = 50): array
    {
        $stmt = Database::connection()->prepare(
            'SELECT a.*, CONCAT(u.first_name, " ", u.last_name) AS actor_name,
                    b.booking_ref, b.first_name AS customer_first_name, b.last_name AS customer_last_name
             FROM audit_logs a
             LEFT JOIN users u ON u.id = a.user_id
             LEFT JOIN bookings b ON b.id = a.booking_id
             ORDER BY a.created_at DESC, a.id DESC LIMIT '.max(1, min(200, $limit))
        );
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public function countSince(string $since): int
    {
        $stmt = Database::connection()->prepare('SELECT COUNT(*) FROM audit_logs WHERE created_at >= :since');
        $stmt->execute([':since' => $since]);
        return (int)$stmt->fetchColumn();
    }
}
