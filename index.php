<?php
declare(strict_types=1);

// Let PHP's built-in dev server (`php -S`) serve real static files directly,
// matching what .htaccess already does for Apache in production.
if (php_sapi_name() === 'cli-server') {
    $reqPath = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/');
    $filePath = __DIR__ . $reqPath;
    if ($reqPath !== '/' && is_file($filePath)) {
        return false;
    }
}

require_once dirname(__DIR__) . '/config/config.php';

// Autoload controllers & services (framework-free, no Composer)
spl_autoload_register(function (string $class): void {
    foreach ([APP_ROOT . '/app/Controllers/', APP_ROOT . '/app/Services/'] as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

$path = current_path();
$path = $path === '' ? '/' : '/' . $path;
$path = rtrim($path, '/') ?: '/';
$method = $_SERVER['REQUEST_METHOD'];

/**
 * Route table: 'METHOD /path' => [Controller, method]
 * Supports a single {param} segment per route.
 */
$routes = [
    'GET /'                          => ['HomeController', 'index'],
    'GET /sitemap.xml'                => ['HomeController', 'sitemap'],
    'GET /robots.txt'                => ['HomeController', 'robotsTxt'],
    'GET /llms.txt'                  => ['HomeController', 'llmsTxt'],
    'GET /about'                     => ['HomeController', 'about'],
    'GET /privacy'                   => ['LegalController', 'privacy'],
    'GET /terms'                     => ['LegalController', 'terms'],
    'GET /locations/{slug}'         => ['SeoController', 'show'],
    'GET /airports/{slug}'           => ['SeoController', 'show'],
    'GET /services/{slug}'           => ['SeoController', 'show'],
    'GET /requirements'              => ['SeoController', 'show'],
    'GET /faq'                       => ['SeoController', 'faq'],
    'GET /fleet'                     => ['FleetController', 'index'],
    'GET /reviews'                   => ['ReviewController', 'index'],
    'GET /fleet/{slug}'              => ['FleetController', 'show'],
    'GET /book'                      => ['BookingController', 'create'],
    'GET /book/availability'        => ['BookingController', 'availability'],
    'POST /book'                     => ['BookingController', 'store'],
    'GET /book/confirmation'         => ['BookingController', 'confirmation'],
    'GET /booking/{bookingRef}/{token}' => ['BookingController', 'publicAccess'],
    'GET /book/{id}/receipt'         => ['BookingController', 'receipt'],
    'GET /book/{id}/receipt/{paymentId}' => ['BookingController', 'receiptPayment'],
    'POST /book/{id}/pay'            => ['PaymentController', 'initiate'],
    'POST /book/{id}/pay/paystack'   => ['PaymentController', 'initiatePaystack'],
    'POST /admin/bookings/{id}/pay-balance' => ['PaymentController', 'initiateHandoverBalance'],
    'POST /book/{id}/manual-pay'     => ['PaymentController', 'manualPay'],
    'GET /payments/{checkoutId}/status' => ['PaymentController', 'status'],
    'POST /mpesa/callback'           => ['PaymentController', 'callback'],
    'GET /payments/paystack/callback' => ['PaymentController', 'paystackCallback'],
    'GET /payments/paystack/status/{reference}' => ['PaymentController', 'paystackStatus'],
    'GET /admin/payments/paystack/status/{reference}' => ['PaymentController', 'adminPaystackStatus'],
    'POST /payments/paystack/webhook' => ['PaymentController', 'paystackWebhook'],
    'GET /webhooks/whatsapp'       => ['WhatsAppController', 'webhook'],
    'POST /webhooks/whatsapp'      => ['WhatsAppController', 'webhook'],
    'GET /contact'                   => ['ContactController', 'index'],
    'POST /contact'                  => ['ContactController', 'store'],

    'GET /admin/login'               => ['AuthController', 'showLogin'],
    'POST /admin/login'              => ['AuthController', 'login'],
    'GET /admin/logout'              => ['AuthController', 'logout'],
    'GET /admin/dashboard'           => ['AdminController', 'dashboard'],
    'GET /admin/cars'                => ['AdminController', 'cars'],
    'GET /admin/cars/new'            => ['AdminController', 'carForm'],
    'GET /admin/cars/{id}/edit'      => ['AdminController', 'carForm'],
    'POST /admin/cars/save'          => ['AdminController', 'saveCar'],
    'POST /admin/cars/{id}/delete'   => ['AdminController', 'deleteCar'],
    'POST /admin/cars/images/{id}/delete' => ['AdminController', 'deleteCarImage'],
    'GET /admin/categories'          => ['AdminController', 'categories'],
    'GET /admin/categories/new'      => ['AdminController', 'categoryForm'],
    'GET /admin/categories/{id}/edit' => ['AdminController', 'categoryForm'],
    'POST /admin/categories/save'    => ['AdminController', 'saveCategory'],
    'POST /admin/categories/{id}/delete' => ['AdminController', 'deleteCategory'],
    'GET /admin/chauffeur-rates'          => ['AdminController', 'chauffeurRates'],
    'GET /admin/chauffeur-rates/new'      => ['AdminController', 'chauffeurRateForm'],
    'GET /admin/chauffeur-rates/{id}/edit' => ['AdminController', 'chauffeurRateForm'],
    'POST /admin/chauffeur-rates/save'    => ['AdminController', 'saveChauffeurRate'],
    'POST /admin/chauffeur-rates/{id}/delete' => ['AdminController', 'deleteChauffeurRate'],
    'GET /admin/testimonials'        => ['AdminController', 'testimonials'],
    'GET /admin/testimonials/new'    => ['AdminController', 'testimonialForm'],
    'GET /admin/testimonials/{id}/edit' => ['AdminController', 'testimonialForm'],
    'POST /admin/testimonials/save'  => ['AdminController', 'saveTestimonial'],
    'POST /admin/testimonials/{id}/delete' => ['AdminController', 'deleteTestimonial'],
    'GET /admin/bookings'            => ['AdminController', 'bookings'],
    'GET /admin/payments'            => ['AdminController', 'payments'],
    'GET /admin/bookings/calendar'   => ['AdminController', 'calendar'],
    'GET /admin/reports'              => ['AdminController', 'reports'],
    'GET /admin/reports/bookings.csv' => ['AdminController', 'exportBookingsCsv'],
    'GET /admin/fleet' => ['AdminController', 'fleet'],
    'GET /admin/fleet/{id}' => ['AdminController', 'vehicleOperations'],
    'POST /admin/fleet/{id}/maintenance' => ['AdminController', 'createMaintenance'],
    'POST /admin/maintenance/{id}/status' => ['AdminController', 'updateMaintenance'],
    'GET /admin/documents/{id}/download' => ['AdminController', 'downloadVehicleDocument'],
    'POST /admin/fleet/{id}/documents' => ['AdminController', 'uploadVehicleDocument'],
    'POST /admin/documents/{id}/status' => ['AdminController', 'updateVehicleDocument'],
    'POST /admin/documents/{id}/delete' => ['AdminController', 'deleteVehicleDocument'],
    'POST /admin/fleet/{id}/odometer' => ['AdminController', 'addOdometer'],
    'GET /admin/bookings/{id}/handover' => ['AdminController', 'handover'],
    'POST /admin/bookings/{id}/checkout' => ['AdminController', 'checkoutBooking'],
    'POST /admin/bookings/{id}/return' => ['AdminController', 'returnBooking'],
    'POST /admin/bookings/{id}/charges' => ['AdminController', 'addRentalCharge'],
    'POST /admin/charges/{id}/status' => ['AdminController', 'updateRentalCharge'],
    'GET /admin/bookings/{id}'       => ['AdminController', 'bookingDetail'],
    'GET /admin/bookings/{id}/receipt' => ['AdminController', 'bookingReceipt'],
    'GET /admin/payments/{id}/receipt' => ['AdminController', 'paymentReceipt'],
    'POST /admin/bookings/{id}/status' => ['AdminController', 'updateBookingStatus'],
    'POST /admin/bookings/{id}/extend' => ['AdminController', 'extendBooking'],
    'GET /admin/bookings/{id}/extend-check' => ['AdminController', 'extendCheck'],
    'POST /admin/payments/{id}/verify-manual' => ['AdminController', 'verifyManualPayment'],
    'POST /admin/payments/{id}/reject-manual' => ['AdminController', 'rejectManualPayment'],
    'GET /admin/messages'            => ['AdminController', 'messages'],
    'GET /admin/whatsapp'            => ['AdminController', 'whatsapp'],
    'POST /admin/whatsapp/{id}/reply' => ['AdminController', 'whatsappReply'],
    'POST /admin/whatsapp/{id}/status' => ['AdminController', 'whatsappStatus'],
    'GET /admin/activity'           => ['AdminController', 'activity'],
    'POST /admin/messages/{id}/reply' => ['AdminController', 'replyMessage'],
    'GET /admin/seo-pages'            => ['SeoController', 'adminPages'],
    'GET /admin/seo-pages/new'        => ['SeoController', 'adminForm'],
    'GET /admin/seo-pages/{id}/edit'  => ['SeoController', 'adminForm'],
    'POST /admin/seo-pages/save'      => ['SeoController', 'saveAdminPage'],
    'POST /admin/seo-pages/{id}/delete' => ['SeoController', 'deleteAdminPage'],
    'GET /admin/purge-data'          => ['AdminController', 'purgeData'],
    'POST /admin/purge-data'         => ['AdminController', 'purgeDataExecute'],
    'GET /admin/reviews'             => ['ReviewController', 'admin'],
    'POST /admin/reviews/sync'       => ['ReviewController', 'sync'],
    'POST /admin/reviews/{id}/visibility' => ['ReviewController', 'visibility'],
    'GET /admin/reviews/google/connect' => ['ReviewController', 'googleConnect'],
    'GET /admin/reviews/google/callback' => ['ReviewController', 'googleCallback'],
    'POST /admin/reviews/google/location' => ['ReviewController', 'googleLocation'],
    'GET /admin/settings'            => ['AdminController', 'settings'],
    'POST /admin/settings/save'      => ['AdminController', 'saveSettings'],

    'GET /account/login'             => ['CustomerController', 'showLogin'],
    'POST /account/login'            => ['CustomerController', 'login'],
    'GET /account/logout'            => ['CustomerController', 'logout'],
    'GET /account/dashboard'         => ['CustomerController', 'dashboard'],
    'GET /account/bookings/{id}'      => ['CustomerController', 'booking'],
    'GET /account/bookings/{id}/agreement' => ['CustomerController', 'agreement'],
    'GET /account/change-password'   => ['CustomerController', 'showChangePassword'],
    'POST /account/change-password'  => ['CustomerController', 'changePassword'],
];

function match_route(array $routes, string $method, string $path): ?array
{
    foreach ($routes as $signature => $handler) {
        [$routeMethod, $routePath] = explode(' ', $signature, 2);
        if ($routeMethod !== $method) {
            continue;
        }

        $pattern = '#^' . preg_replace('/\{[a-zA-Z_]+\}/', '([^/]+)', $routePath) . '$#';
        if (preg_match($pattern, $path, $matches)) {
            array_shift($matches);
            return [$handler[0], $handler[1], $matches];
        }
    }
    return null;
}

$match = match_route($routes, $method, $path);

if (!$match) {
    http_response_code(404);
    view('404', ['seo' => seo_for('home')]);
    exit;
}

[$controllerName, $action, $params] = $match;

// Cast numeric-looking route params (ids) to int; strict_types requires this
$params = array_map(
    fn(string $p) => ctype_digit($p) ? (int) $p : $p,
    $params
);

try {
    $controller = new $controllerName();
    $controller->$action(...$params);
} catch (Throwable $e) {
    error_log('[APP ERROR] ' . $e->getMessage());
    http_response_code(500);
    if (APP_ENV !== 'production') {
        echo '<pre>' . e($e->getMessage()) . "\n" . e($e->getTraceAsString()) . '</pre>';
    } else {
        view('500', ['seo' => seo_for('home')]);
    }
}
