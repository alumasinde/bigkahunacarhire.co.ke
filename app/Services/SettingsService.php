<?php
declare(strict_types=1);

final class SettingsService
{
    public function __construct(private PDO $db) {}

    public static function make(): self
    {
        return new self(Database::connection());
    }

    public function group(string $group): array
    {
        $stmt = $this->db->prepare('SELECT * FROM settings WHERE setting_group = :group ORDER BY setting_key');
        $stmt->execute([':group' => $group]);
        return $stmt->fetchAll();
    }

    /**
     * Bulk upsert a group of key => value pairs.
     */
    public function saveGroup(string $group, array $keyValues): void
    {
        $stmt = $this->db->prepare(
            'INSERT INTO settings (setting_group, setting_key, setting_value)
             VALUES (:group, :key, :value)
             ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
        );

        foreach ($keyValues as $key => $value) {
            $stmt->execute([':group' => $group, ':key' => $key, ':value' => $value]);
        }
    }

    public function testimonials(): array
    {
        return $this->db->query('SELECT * FROM testimonials WHERE is_active = 1 ORDER BY created_at DESC')->fetchAll();
    }

    // ---------------------------------------------------------------
    // Testimonial management (admin — all rows, active or not)
    // ---------------------------------------------------------------
    public function allTestimonials(): array
    {
        return $this->db->query('SELECT * FROM testimonials ORDER BY created_at DESC')->fetchAll();
    }

    public function findTestimonial(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM testimonials WHERE id = :id LIMIT 1');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public function createTestimonial(array $data): int
    {
        $stmt = $this->db->prepare(
            'INSERT INTO testimonials (client_name, client_role, rating, message, is_active)
             VALUES (:client_name, :client_role, :rating, :message, :is_active)'
        );
        $stmt->execute($this->testimonialBindable($data));
        return (int) $this->db->lastInsertId();
    }

    public function updateTestimonial(int $id, array $data): bool
    {
        $stmt = $this->db->prepare(
            'UPDATE testimonials SET client_name = :client_name, client_role = :client_role,
                rating = :rating, message = :message, is_active = :is_active WHERE id = :id'
        );
        return $stmt->execute($this->testimonialBindable($data) + [':id' => $id]);
    }

    public function deleteTestimonial(int $id): bool
    {
        $stmt = $this->db->prepare('DELETE FROM testimonials WHERE id = :id');
        return $stmt->execute([':id' => $id]);
    }

    private function testimonialBindable(array $data): array
    {
        $rating = (int) ($data['rating'] ?? 5);
        $rating = max(1, min(5, $rating));

        return [
            ':client_name' => $data['client_name'],
            ':client_role' => $data['client_role'] ?: null,
            ':rating'      => $rating,
            ':message'     => $data['message'],
            ':is_active'   => !empty($data['is_active']) ? 1 : 0,
        ];
    }
}
