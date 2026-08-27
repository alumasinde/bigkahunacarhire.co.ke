<?php
declare(strict_types=1);

final class FleetController
{
    public function index(): void
    {
        $carService = CarService::make();
        $filters = [
            'category' => is_string($_GET['category'] ?? null) ? trim($_GET['category']) : '',
            'transmission' => is_string($_GET['transmission'] ?? null) ? trim($_GET['transmission']) : '',
            'seats' => is_string($_GET['seats'] ?? null) ? trim($_GET['seats']) : '',
            'max_price' => is_string($_GET['max_price'] ?? null) ? trim($_GET['max_price']) : '',
        ];
        $hasFilters = implode('', $filters) !== '' || !empty(parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_QUERY));

        view('fleet', [
            'seo' => [
                'title' => $hasFilters ? 'Available Cars | Big Kahuna Car Hire Kenya' : (seo_for('fleet')['title'] ?? 'Car Hire Fleet in Kenya | Big Kahuna'),
                'description' => $hasFilters ? 'Browse filtered Big Kahuna car hire options in Kenya. Compare vehicle specifications and advertised daily rates.' : (seo_for('fleet')['description'] ?? 'Browse the Big Kahuna car hire fleet in Kenya.'),
                'keywords' => '',
                'og_image' => setting('seo', 'og_image'),
                'robots' => $hasFilters ? 'noindex, follow' : 'index, follow',
                'schema_type' => 'CollectionPage',
            ],
            'cars' => $carService->search($filters),
            'categories' => $carService->allCategories(),
            'filters' => $filters,
            'hasFilters' => $hasFilters,
        ]);
    }

    public function show(string $slug): void
    {
        $carService = CarService::make();
        $car = $carService->findBySlug($slug);

        if (!$car) {
            http_response_code(404);
            view('404', ['seo' => ['title' => 'Car Not Found | Big Kahuna Car Hire', 'description' => 'The requested vehicle could not be found.', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow']]);
            return;
        }

        $location = trim((string)($car['location'] ?? 'Kenya'));
        $name = trim((string)$car['name']);
        $category = trim((string)($car['category_name'] ?? ''));
        $defaultTitle = $name . ' Hire in ' . ($location ?: 'Kenya') . ' | Big Kahuna Car Hire';
        $defaultDescription = sprintf(
            '%s %s hire in %s. %d seats, %s transmission. From %s per day. View specifications and book with Big Kahuna Car Hire.',
            $name,
            $category,
            $location ?: 'Kenya',
            (int)$car['seats'],
            ucfirst((string)$car['transmission']),
            money($car['price_per_day'])
        );

        view('car-detail', [
            'seo' => [
                'title' => $car['meta_title'] ?: $defaultTitle,
                'description' => $car['meta_description'] ?: $defaultDescription,
                'keywords' => '',
                'og_image' => $car['image_path'] ?: setting('seo', 'og_image'),
                'robots' => $car['status'] === 'retired' ? 'noindex, nofollow' : 'index, follow',
                'schema_type' => 'Product',
            ],
            'car' => $car,
            'gallery' => $carService->gallery((int)$car['id']),
        ]);
    }
}
