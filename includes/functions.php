<?php
declare(strict_types=1);

/**
 * Shared helper functions. All dynamic content (settings, SEO, page titles)
 * is fetched from MySQL — nothing here is hardcoded.
 */

/**
 * Load every setting from the DB once per request, grouped by setting_group.
 * @return array<string, array<string, string>>
 */
function all_settings(): array
{
    static $settings = null;

    if ($settings === null) {
        $settings = ['general' => [], 'seo' => [], 'contact' => [], 'social' => []];
        $stmt = Database::connection()->query('SELECT setting_group, setting_key, setting_value FROM settings');
        foreach ($stmt->fetchAll() as $row) {
            $settings[$row['setting_group']] ??= [];
            $settings[$row['setting_group']][$row['setting_key']] = $row['setting_value'];
        }
    }

    return $settings;
}

function setting(string $group, string $key, string $default = ''): string
{
    $settings = all_settings();
    return $settings[$group][$key] ?? $default;
}

/**
 * Resolve the SEO title/description/keywords for a given page key
 * (e.g. 'home', 'fleet', 'about'), falling back to sitewide defaults.
 */
function seo_for(string $page): array
{
    return [
        'title'       => setting('seo', "{$page}_title") ?: setting('seo', 'default_meta_title'),
        'description' => setting('seo', "{$page}_description") ?: setting('seo', 'default_meta_description'),
        'keywords'    => setting('seo', 'default_meta_keywords'),
        'og_image'    => setting('seo', 'og_image'),
        'robots'      => setting('seo', 'robots', 'index, follow'),
    ];
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES, 'UTF-8');
}

function base_url(string $path = ''): string
{
    return APP_URL . '/' . ltrim($path, '/');
}

function car_image_url(?string $path): string
{
    $path = trim((string)$path);
    if ($path !== '') {
        $relative = ltrim(parse_url($path, PHP_URL_PATH) ?: $path, '/');
        $absolute = APP_ROOT . '/public_html/' . $relative;
        if (is_file($absolute)) {
            return base_url($relative);
        }
    }
    return asset('images/cars/car-placeholder.svg');
}

function asset(string $path): string
{
    $relative = 'assets/' . ltrim($path, '/');
    $url = base_url($relative);

    // Cache-busting: append the file's last-modified time as a version
    // query string so that CSS/JS fixes reach browsers immediately
    // instead of waiting out the 7-day Cache-Control on .htaccess.
    $absolute = rtrim(dirname(__DIR__), '/') . '/public_html/' . $relative;
    if (is_file($absolute)) {
        $url .= (str_contains($url, '?') ? '&' : '?') . 'v=' . filemtime($absolute);
    }

    return $url;
}

function redirect(string $path): never
{
    header('Location: ' . base_url($path));
    exit;
}

function current_path(): string
{
    return trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/', '/');
}

// ---------------------------------------------------------------
// CSRF protection
// ---------------------------------------------------------------
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function verify_csrf(): bool
{
    $token = $_POST['csrf_token'] ?? '';
    return is_string($token) && hash_equals($_SESSION['csrf_token'] ?? '', $token);
}

// ---------------------------------------------------------------
// AJAX / JSON helpers
// ---------------------------------------------------------------

/**
 * True when the request was made via fetch()/XHR with our JS admin layer
 * (X-Requested-With header) or explicitly asked for JSON. Used by admin
 * controllers to answer in-place instead of doing a full-page redirect,
 * while still falling back to the classic flash+redirect flow for any
 * client that submits the same form without JavaScript.
 */
function wants_json(): bool
{
    $requestedWith = $_SERVER['HTTP_X_REQUESTED_WITH'] ?? '';
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    return strtolower($requestedWith) === 'xmlhttprequest' || str_contains($accept, 'application/json');
}

/**
 * Send a JSON response and stop execution. Always used instead of a
 * redirect() when wants_json() is true.
 * @param array<string,mixed> $data
 */
function json_out(array $data, int $status = 200): never
{
    http_response_code($status);
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode($data, JSON_UNESCAPED_SLASHES);
    exit;
}

// ---------------------------------------------------------------
// Flash messages (one-time, survives a redirect via session)
// ---------------------------------------------------------------
function flash(string $type, string $message): void
{
    $_SESSION['flash'][$type] = $message;
}

function get_flashes(): array
{
    $flashes = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $flashes;
}

// ---------------------------------------------------------------
// Formatting
// ---------------------------------------------------------------
function money(float|string $amount): string
{
    return setting('general', 'currency', 'KES') . ' ' . number_format((float) $amount, 0);
}

function label_from_key(string $key): string
{
    return ucwords(str_replace('_', ' ', $key));
}

function slugify(string $text): string
{
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9]+/', '-', $text);
    return trim((string) $text, '-');
}



function booking_reference(): string
{
    return 'BK-' . strtoupper(bin2hex(random_bytes(4)));
}

/**
 * Render a view file with an isolated variable scope.
 */
function json_response(array $data, int $statusCode = 200): never
{
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function view(string $viewPath, array $data = []): void
{
    extract($data, EXTR_SKIP);
    require APP_ROOT . '/app/Views/' . $viewPath . '.php';
}
