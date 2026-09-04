# Big Kahuna CSS Architecture

The public website uses one versioned style engine.

## Shared foundation

`components/` contains CSS shared by every public style version:

- `00-tokens.css`
- `01-base.css`
- `02-buttons.css`
- `03-forms.css`
- `04-header.css`
- `05-hero.css`
- `06-sections.css`
- `07-cards.css`
- `08-footer.css`
- `09-utilities.css`

## V1 — default

`v1/` is the production default:

- `01-public.css` — public page presentation
- `02-components.css` — V1 component refinements
- `03-booking.css` — booking experience

## V2 — alternative

`v2/` mirrors the same naming convention for safe visual experimentation.

## Switching versions

Edit:

`app/Views/layouts/style-config.ini`

and change:

`active_version = "v1"`

to:

`active_version = "v2"`

The public header loads CSS through `style-engine.php`. Old phase and duplicate stylesheets are no longer selected by the public style loader.
