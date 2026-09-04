<?php
declare(strict_types=1);

/**
 * Big Kahuna public style engine.
 *
 * One config file chooses the active visual version. Shared foundations load
 * first, followed by the selected version. Booking-only styles are loaded
 * only for booking routes so unrelated pages do not carry that CSS.
 */
if (!function_exists('style_engine_links')) {
    function style_engine_links(): string
    {
        static $rendered = null;

        if ($rendered !== null) {
            return $rendered;
        }

        $configPath = __DIR__ . '/style-config.ini';
        $config = is_file($configPath)
            ? parse_ini_file($configPath, true, INI_SCANNER_TYPED)
            : [];

        $version = (string)($config['style']['active_version'] ?? 'v1');
        if (!in_array($version, ['v1', 'v2'], true)) {
            $version = 'v1';
        }

        $files = array_merge(
            (array)($config['common']['files'] ?? []),
            (array)($config[$version]['files'] ?? [])
        );

        $path = function_exists('current_path') ? trim((string) current_path(), '/') : '';
        $isBookingRoute = $path === 'book' || str_starts_with($path, 'book/');

        if ($isBookingRoute) {
            $files = array_merge($files, (array)($config[$version]['booking_files'] ?? []));
        }

        $seen = [];
        $links = [];

        foreach ($files as $file) {
            $file = ltrim((string)$file, '/');
            if ($file === '' || isset($seen[$file]) || !preg_match('#^[A-Za-z0-9._/-]+\\.css$#', $file)) {
                continue;
            }

            $seen[$file] = true;
            $href = function_exists('asset')
                ? asset('css/' . $file)
                : '/assets/css/' . $file;

            $links[] = '<link rel="stylesheet" href="' . htmlspecialchars($href, ENT_QUOTES, 'UTF-8') . '">';
        }

        return $rendered = implode("\n", $links);
    }
}
