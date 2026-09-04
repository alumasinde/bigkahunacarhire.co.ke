# Big Kahuna CSS Architecture

## Single public asset root

The only runtime asset directory is:

`public_html/assets/`

The repository-root `assets/` directory was removed because it duplicated the public files and was not served by the application.

## Versioned public styles

The active version is selected in:

`config/design/active.ini`

Current default:

`style_version = "v1"`

The canonical loader is:

`includes/StyleEngine.php`

Each version has one small, ordered manifest:

- `01-public.css` — public page presentation
- `02-components.css` — shared public components
- `03-booking.css` — booking UI refinements

The manifest is the source of truth for what the public site loads.

## Admin styles

Admin-specific styles remain under:

`components/10-admin-layout.css`
`components/11-admin-components.css`

plus the existing admin operation modules referenced directly by the admin layout.

Do not add duplicate root-level assets or a second style engine. New public visual versions should use the existing `config/design/active.ini` + `includes/StyleEngine.php` architecture.
