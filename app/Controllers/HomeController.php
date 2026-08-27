<?php
declare(strict_types=1);

final class HomeController
{
    public function index(): void
    {
        $cars = CarService::make();
        $settingsService = SettingsService::make();
        $bookings = BookingService::make();
        $seoService = SeoService::make();
        $reviewService = ReviewService::make();

        view('home', [
            'seo'          => seo_for('home'),
            'featuredCars' => $cars->featured(6),
            'categories'   => $cars->allCategories(),
            'testimonials' => $settingsService->testimonials(),
            'reviewsData'  => $reviewService->homepage((int)setting('reviews','home_limit','6')),
            'seoLocations' => array_values(array_filter(
                $seoService->all(false),
                fn(array $page): bool => ($page['page_type'] ?? '') === 'location'
            )),
            'seoAirports' => array_values(array_filter(
                $seoService->all(false),
                fn(array $page): bool => ($page['page_type'] ?? '') === 'airport'
            )), 
            'stats'        => [
                'car_count'     => $cars->activeCount(),
                'booking_count' => $bookings->totalCount(),
            ],
        ]);
    }

    public function about(): void
    {
        view('about', [
            'seo' => seo_for('about'),
        ]);
    }

    public function robotsTxt(): void
    {
        header('Content-Type: text/plain; charset=utf-8');

        // Absolute sitemap URL, built from APP_URL rather than hardcoded —
        // stays correct automatically if the domain ever changes, and
        // can't drift out of sync the way a static robots.txt file can.
        echo "User-agent: *\n";
        echo "Allow: /\n";
        echo "Disallow: /admin/\n";
        echo "Disallow: /account/\n";
        echo "Disallow: /book/confirmation\n";
        echo "Disallow: /payments/\n";
        echo "\n";
        echo 'Sitemap: ' . base_url('sitemap.xml') . "\n";
    }

    /**
     * llms.txt — an emerging convention (not a Google ranking signal, but
     * increasingly used by AI assistants like ChatGPT/Claude/Perplexity)
     * that gives AI crawlers a concise, structured summary of the site
     * instead of having them guess from raw HTML. Built from the same
     * settings/DB data as everything else — nothing hardcoded here.
     */
    public function llmsTxt(): void
    {
        header('Content-Type: text/markdown; charset=utf-8');

        $siteName = setting('general', 'site_name', 'Big Kahuna Car Hire');
        $tagline = setting('general', 'tagline');
        $description = setting('seo', 'default_meta_description');
        $phone = setting('general', 'phone_primary');
        $email = setting('general', 'email');
        $whatsapp = setting('general', 'whatsapp_number');
        $categories = CarService::make()->allCategories();
        $seoPages = SeoService::make()->all(false);

        echo "# {$siteName}\n\n";
        if ($tagline) {
            echo "> {$tagline}\n\n";
        }
        if ($description) {
            echo "{$description}\n\n";
        }

        echo "## Key pages\n\n";
        echo '- [Fleet — browse all cars](' . base_url('fleet') . ")\n";
        echo '- [Book a car](' . base_url('book') . ")\n";
        echo '- [Privacy Policy](' . base_url('privacy') . ")\n";
        echo '- [Terms of Service](' . base_url('terms') . ")\n";
        echo '- [About us](' . base_url('about') . ")\n";
        echo '- [Contact](' . base_url('contact') . ")\n";
        foreach ($seoPages as $page) {
            $key = $page['page_key'] ?? '';
            if ($key === '') continue;
            echo '- [' . ($page['name'] ?? $key) . '](' . base_url($key) . ")\n";
        }
        echo "\n";

        if (!empty($categories)) {
            echo "## Vehicle categories\n\n";
            foreach ($categories as $cat) {
                $desc = !empty($cat['description']) ? ' — ' . $cat['description'] : '';
                echo '- [' . $cat['name'] . '](' . base_url('fleet?category=' . $cat['slug']) . ')' . $desc . "\n";
            }
            echo "\n";
        }

        echo "## Contact\n\n";
        if ($phone) {
            echo "- Phone: {$phone}\n";
        }
        if ($email) {
            echo "- Email: {$email}\n";
        }
        if ($whatsapp) {
            echo "- WhatsApp: https://wa.me/{$whatsapp}\n";
        }
        echo "\n## Sitemap\n\n" . base_url('sitemap.xml') . "\n";
    }

    public function sitemap(): void
    {
        header('Content-Type: application/xml; charset=utf-8');

        $staticPages = ['', 'fleet', 'about', 'contact', 'faq', 'requirements', 'privacy', 'terms'];
        $seoPages = (new SeoController())->pageRegistry();
        $cars = CarService::make()->all();

        echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($staticPages as $page) {
            $priority = $page === '' ? '1.0' : ($page === 'fleet' ? '0.9' : '0.6');
            echo '  <url><loc>' . e(base_url($page)) . '</loc><changefreq>weekly</changefreq><priority>'.$priority.'</priority></url>' . "\n";
        }

        foreach ($seoPages as $page) {
            $pageKey = (string)($page['page_key'] ?? '');
            if ($pageKey === '') continue;

            $priority = in_array($pageKey, ['locations/nairobi','locations/mombasa','airports/jkia','airports/mombasa'], true)
                ? '0.9'
                : '0.7';

            echo '  <url><loc>' . e(base_url($pageKey)) . '</loc><changefreq>weekly</changefreq><priority>'.$priority.'</priority></url>' . "\n";
        }

        foreach ($cars as $car) {
            if (($car['status'] ?? '') === 'retired') continue;
            $lastmod = !empty($car['updated_at']) ? date('Y-m-d', strtotime($car['updated_at'])) : '';
            echo '  <url><loc>' . e(base_url('fleet/' . $car['slug'])) . '</loc><changefreq>weekly</changefreq>';
            if ($lastmod) echo '<lastmod>' . e($lastmod) . '</lastmod>';
            echo '<priority>0.8</priority></url>' . "\n";
        }

        echo '</urlset>';
    }
}
