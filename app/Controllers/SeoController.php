<?php
declare(strict_types=1);

final class SeoController
{
    public function show(string $slug = 'requirements'): void
    {
        $slug = trim($slug, '/');
        $service = SeoService::make();

        $path = current_path();
        $segments = array_values(array_filter(explode('/', trim($path, '/'))));

        // Resolve the public route prefix to the database page_key.
        $prefix = count($segments) >= 2 ? $segments[0] : '';
        $routeSlug = count($segments) >= 2 ? $segments[1] : $slug;

        $page = $service->findByRoute($prefix, $routeSlug);

        // Direct pages such as /requirements and /faq use their page_key directly.
        if (!$page && $prefix === '') {
            $page = $service->findByKey($slug);
        }

        if (!$page) {
            http_response_code(404);
            view('404', ['seo' => seo_for('home')]);
            return;
        }

        $page['slug'] = $page['page_key'];

        // The view historically expects numeric FAQ/related arrays.
        $page['faqs'] = array_map(
            fn(array $row): array => [$row['question'], $row['answer']],
            $page['faqs']
        );

        $page['related'] = array_map(
            fn(array $row): array => [$row['label'], $this->targetUrl($row['target_key'])],
            $page['related']
        );

        $page['cars'] = CarService::make()->featured(6);

        $page['seo'] = [
            'title' => $page['title'],
            'description' => $page['meta_description'],
            'keywords' => '',
            'og_image' => setting('seo', 'og_image'),
            'robots' => ((int)$page['is_indexable'] === 1) ? 'index, follow' : 'noindex, nofollow',
            'schema_type' => $page['page_type'] === 'faq' ? 'FAQPage' : 'WebPage',
        ];

        view('seo/landing', $page);
    }

    public function faq(): void
    {
        $this->show('faq');
    }

    public function pageRegistry(): array
    {
        return SeoService::make()->all(false);
    }

    private function targetUrl(string $key): string
    {
        if ($key === '') return '/';

        // Existing internal pages.
        if (in_array($key, ['fleet', 'book', 'contact', 'about', 'faq', 'requirements'], true)) {
            return $key;
        }

        return $key;
    }

    // ---------------------------------------------------------------
    // Admin — dynamic SEO page management
    // ---------------------------------------------------------------

    public function adminPages(): void
    {
        Auth::requirePermission('seo.manage');

        view('admin/seo-pages', [
            'seo' => [
                'title' => 'SEO Pages | Admin',
                'description' => '',
                'keywords' => '',
                'og_image' => '',
                'robots' => 'noindex, nofollow',
            ],
            'pages' => SeoService::make()->all(),
        ]);
    }

    public function adminForm(?int $id = null): void
    {
        Auth::requirePermission('seo.manage');

        $page = $id ? SeoService::make()->findAdmin($id) : [
            'id' => null,
            'page_type' => 'location',
            'name' => '',
            'slug' => '',
            'title' => '',
            'meta_description' => '',
            'h1' => '',
            'intro' => '',
            'areas_text' => '',
            'is_active' => 1,
            'is_indexable' => 1,
            'sort_order' => 0,
        ];

        if (!$page) {
            flash('error', 'SEO page not found.');
            redirect('admin/seo-pages');
        }

        view('admin/seo-page-form', [
            'seo' => [
                'title' => ($id ? 'Edit SEO Page' : 'Add SEO Page') . ' | Admin',
                'description' => '',
                'keywords' => '',
                'og_image' => '',
                'robots' => 'noindex, nofollow',
            ],
            'page' => $page,
        ]);
    }

    public function saveAdminPage(): void
    {
        Auth::requirePermission('seo.manage');

        if (!verify_csrf()) {
            flash('error', 'Session expired.');
            redirect('admin/seo-pages');
        }

        $data = $_POST;
        $id = !empty($data['id']) ? (int)$data['id'] : null;

        if (trim((string)($data['name'] ?? '')) === '' ||
            trim((string)($data['title'] ?? '')) === '' ||
            trim((string)($data['h1'] ?? ''))) {
            flash('error', 'Name, SEO title and H1 are required.');
            redirect($id ? 'admin/seo-pages/' . $id . '/edit' : 'admin/seo-pages/new');
        }

        try {
            SeoService::make()->savePage($data, $id);
            flash('success', 'SEO page saved.');
            redirect('admin/seo-pages');
        } catch (Throwable $e) {
            error_log('[SEO SAVE] ' . $e->getMessage());
            flash('error', 'Could not save the SEO page. Check that the slug is unique.');
            redirect($id ? 'admin/seo-pages/' . $id . '/edit' : 'admin/seo-pages/new');
        }
    }

    public function deleteAdminPage(int $id): void
    {
        Auth::requirePermission('seo.manage');

        if (!verify_csrf()) {
            flash('error', 'Session expired.');
            redirect('admin/seo-pages');
        }

        SeoService::make()->deletePage($id);
        flash('success', 'SEO page removed.');
        redirect('admin/seo-pages');
    }
}
