<?php
declare(strict_types=1);

/**
 * Versioned public style loader.
 *
 * Public styles have two layers:
 * 1. Shared foundation: reset, tokens, controls, forms, header, hero,
 *    sections, cards, footer and utilities.
 * 2. Active version: V1 or V2 presentation overrides selected from
 *    config/design/active.ini.
 *
 * Keeping the foundation outside V1/V2 prevents both versions from having
 * to duplicate structural CSS while still allowing either version to be
 * switched from configuration.
 */
final class StyleEngine
{
    private array $config;
    private string $configPath;

    private const FOUNDATION_STYLES = [
        'components/00-tokens.css',
        'components/01-base.css',
        'components/02-buttons.css',
        'components/03-forms.css',
        'components/04-header.css',
        'components/05-hero.css',
        'components/06-sections.css',
        'components/07-cards.css',
        'components/08-footer.css',
        'components/09-utilities.css',
    ];

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

        foreach ($this->foundationStyles() as $style) {
            $href = asset('css/' . $style);
            $output .= '<link rel="stylesheet" href="' . e($href) . '">';
        }

        foreach ($this->versionStyles($version) as $style) {
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
    private function foundationStyles(): array
    {
        return array_values(array_filter(self::FOUNDATION_STYLES, function (string $style): bool {
            return is_file(APP_ROOT . '/public_html/assets/css/' . $style);
        }));
    }

    /**
     * @return list<string>
     */
    private function versionStyles(string $version): array
    {
        $manifest = APP_ROOT . '/public_html/assets/css/' . $version . '/manifest.php';

        if (!is_file($manifest)) {
            return [];
        }

        $styles = require $manifest;

        if (!is_array($styles)) {
            return [];
        }

        return array_values(array_filter($styles, static function (mixed $style): bool {
            return is_string($style)
                && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]*\.css$/', $style) === 1;
        }));
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
