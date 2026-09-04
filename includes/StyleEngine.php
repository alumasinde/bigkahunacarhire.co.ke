<?php
declare(strict_types=1);

/**
 * Versioned public style loader.
 *
 * The active design lives in config/design/active.ini. Switching
 * application.style_version from v1 to v2 changes the complete public
 * stylesheet manifest without touching views.
 */
final class StyleEngine
{
    private array $config;
    private string $configPath;

    public function __construct(?string $configPath = null)
    {
        $this->configPath = $configPath ?? APP_ROOT . '/config/design/active.ini';
        $config = parse_ini_file($this->configPath, true, INI_SCANNER_TYPED);

        if ($config === false) {
            throw new RuntimeException('Unable to load design configuration.');
        }

        $this->config = $config;
    }

    public function render(): string
    {
        $version = $this->version();
        $output = $this->themeMeta() . $this->fontStyles() . $this->variables();

        foreach ($this->styles($version) as $style) {
            $href = asset('css/' . $version . '/' . $style);
            $output .= '<link rel="stylesheet" href="' . e($href) . '">';
        }

        return $output;
    }

    public function version(): string
    {
        $version = (string) ($this->config['application']['style_version'] ?? 'v1');

        if (!preg_match('/^v[0-9]+$/', $version)) {
            return 'v1';
        }

        $directory = APP_ROOT . '/public_html/assets/css/' . $version;

        return is_dir($directory) ? $version : 'v1';
    }

    private function themeMeta(): string
    {
        $color = trim((string) ($this->config['brand']['primary'] ?? ''));

        if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $color)) {
            return '';
        }

        return '<meta name="theme-color" content="' . e($color) . '">';
    }

    private function fontStyles(): string
    {
        $url = trim((string) ($this->config['font']['font_css_url'] ?? ''));

        if ($url === '' || !filter_var($url, FILTER_VALIDATE_URL) || parse_url($url, PHP_URL_SCHEME) !== 'https') {
            return '';
        }

        return '<link rel="preconnect" href="https://fonts.googleapis.com">'
            . '<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>'
            . '<link rel="stylesheet" href="' . e($url) . '">';
    }

    private function variables(): string
    {
        $variables = [];

        foreach (['brand', 'text', 'surface', 'layout', 'border', 'spacing', 'font'] as $section) {
            foreach ($this->config[$section] ?? [] as $key => $value) {
                if ($section === 'font' && $key === 'font_css_url') {
                    continue;
                }

                $name = '--' . $section . '-' . str_replace('_', '-', (string) $key);
                $variables[] = $name . ':' . e((string) $value);
            }
        }

        return '<style>:root{' . implode(';', $variables) . ';}</style>';
    }

    /**
     * @return list<string>
     */
    private function styles(string $version): array
    {
        $manifest = APP_ROOT . '/public_html/assets/css/' . $version . '/manifest.php';

        if (!is_file($manifest)) {
            return [];
        }

        $styles = require $manifest;

        if (!is_array($styles)) {
            return [];
        }

        $styles = array_values(array_filter($styles, static function (mixed $style): bool {
            return is_string($style)
                && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.css$/', $style) === 1;
        }));

        return $styles;
    }
}

function style_links(): string
{
    static $html = null;

    if ($html === null) {
        $html = (new StyleEngine())->render();
    }

    return $html;
}
