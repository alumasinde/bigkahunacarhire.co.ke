<?php
declare(strict_types=1);

final class SeoService
{
    public function __construct(private PDO $db) {}

    public static function make(): self
    {
        return new self(Database::connection());
    }

    public function findByRoute(string $prefix, string $slug): ?array
    {
        $pageKey = $prefix === '' ? $slug : trim($prefix, '/') . '/' . trim($slug, '/');

        $stmt = $this->db->prepare(
            "SELECT * FROM seo_pages
             WHERE page_key = :page_key AND is_active = 1
             LIMIT 1"
        );
        $stmt->execute([':page_key' => $pageKey]);
        $page = $stmt->fetch();

        if (!$page) {
            return null;
        }

        $page['areas'] = $this->decodeAreas($page['areas_json'] ?? null);
        $page['related'] = $this->related((int)$page['id']);
        $page['faqs'] = $this->faqs((int)$page['id']);
        $page['content_sections'] = $this->content((int)$page['id']);

        return $page;
    }

    public function findByKey(string $key): ?array
    {
        $stmt = $this->db->prepare(
            "SELECT * FROM seo_pages
             WHERE page_key = :page_key AND is_active = 1
             LIMIT 1"
        );
        $stmt->execute([':page_key' => $key]);
        $page = $stmt->fetch();

        if (!$page) {
            return null;
        }

        $page['areas'] = $this->decodeAreas($page['areas_json'] ?? null);
        $page['related'] = $this->related((int)$page['id']);
        $page['faqs'] = $this->faqs((int)$page['id']);
        $page['content_sections'] = $this->content((int)$page['id']);

        return $page;
    }

    public function related(int $pageId): array
    {
        $stmt = $this->db->prepare(
            "SELECT label, target_key
             FROM seo_page_related
             WHERE page_id = :page_id
             ORDER BY sort_order, id"
        );
        $stmt->execute([':page_id' => $pageId]);
        return $stmt->fetchAll();
    }

    public function faqs(int $pageId): array
    {
        $stmt = $this->db->prepare(
            "SELECT question, answer
             FROM seo_page_faqs
             WHERE page_id = :page_id AND is_active = 1
             ORDER BY sort_order, id"
        );
        $stmt->execute([':page_id' => $pageId]);
        return $stmt->fetchAll();
    }

    public function content(int $pageId): array
    {
        $stmt = $this->db->prepare(
            "SELECT heading AS title, body
             FROM seo_page_content
             WHERE page_id = :page_id AND is_active = 1
             ORDER BY sort_order, id"
        );
        $stmt->execute([':page_id' => $pageId]);
        return $stmt->fetchAll();
    }

    public function all(bool $includeInactive = true): array
    {
        $sql = 'SELECT * FROM seo_pages';
        if (!$includeInactive) {
            $sql .= ' WHERE is_active = 1';
        }
        $sql .= ' ORDER BY sort_order, page_type, name';
        return $this->db->query($sql)->fetchAll();
    }

    public function savePage(array $data, ?int $id = null): int
    {
        $pageType = in_array($data['page_type'] ?? '', ['location','airport','service','guide','faq'], true)
            ? $data['page_type'] : 'location';

        $slug = slugify((string)($data['slug'] ?? $data['name'] ?? ''));
        $prefix = match ($pageType) {
            'location' => 'locations/',
            'airport' => 'airports/',
            'service' => 'services/',
            default => '',
        };

        $pageKey = $prefix . $slug;
        $areas = array_values(array_filter(array_map(
            'trim',
            preg_split('/[\r\n,]+/', (string)($data['areas'] ?? '')) ?: []
        )));

        if ($id) {
            $stmt = $this->db->prepare(
                "UPDATE seo_pages SET
                    page_key=:page_key, page_type=:page_type, name=:name, slug=:slug,
                    title=:title, meta_description=:meta_description, h1=:h1, intro=:intro,
                    areas_json=:areas_json, is_active=:is_active, is_indexable=:is_indexable,
                    sort_order=:sort_order
                 WHERE id=:id"
            );
            $stmt->execute([
                ':id'=>$id, ':page_key'=>$pageKey, ':page_type'=>$pageType, ':name'=>trim((string)$data['name']),
                ':slug'=>$slug, ':title'=>trim((string)$data['title']),
                ':meta_description'=>trim((string)$data['meta_description']),
                ':h1'=>trim((string)$data['h1']), ':intro'=>trim((string)$data['intro']),
                ':areas_json'=>json_encode($areas, JSON_UNESCAPED_UNICODE),
                ':is_active'=>(int)!empty($data['is_active']),
                ':is_indexable'=>(int)!empty($data['is_indexable']),
                ':sort_order'=>(int)($data['sort_order'] ?? 0),
            ]);
            return $id;
        }

        $stmt = $this->db->prepare(
            "INSERT INTO seo_pages
             (page_key,page_type,name,slug,title,meta_description,h1,intro,areas_json,is_active,is_indexable,sort_order)
             VALUES (:page_key,:page_type,:name,:slug,:title,:meta_description,:h1,:intro,:areas_json,:is_active,:is_indexable,:sort_order)"
        );
        $stmt->execute([
            ':page_key'=>$pageKey, ':page_type'=>$pageType, ':name'=>trim((string)$data['name']),
            ':slug'=>$slug, ':title'=>trim((string)$data['title']),
            ':meta_description'=>trim((string)$data['meta_description']),
            ':h1'=>trim((string)$data['h1']), ':intro'=>trim((string)$data['intro']),
            ':areas_json'=>json_encode($areas, JSON_UNESCAPED_UNICODE),
            ':is_active'=>(int)!empty($data['is_active']),
            ':is_indexable'=>(int)!empty($data['is_indexable']),
            ':sort_order'=>(int)($data['sort_order'] ?? 0),
        ]);
        return (int)$this->db->lastInsertId();
    }

    public function findAdmin(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM seo_pages WHERE id=:id LIMIT 1');
        $stmt->execute([':id'=>$id]);
        $row=$stmt->fetch();
        if (!$row) return null;
        $row['areas_text'] = implode("\n", $this->decodeAreas($row['areas_json'] ?? null));
        return $row;
    }

    public function deletePage(int $id): bool
    {
        $stmt=$this->db->prepare('DELETE FROM seo_pages WHERE id=:id');
        return $stmt->execute([':id'=>$id]);
    }

    private function decodeAreas(?string $json): array
    {
        if (!$json) return [];
        $areas=json_decode($json,true);
        return is_array($areas) ? array_values(array_filter(array_map('strval',$areas))) : [];
    }
}
