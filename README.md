# Big Kahuna Car Hire

A framework-free PHP 8.1+ / MySQL car hire website — public marketing site plus
an admin panel for managing the fleet, bookings, messages and SEO settings.
Architecture matches your other AlbaTech Solutions PHP projects: PDO/MySQL,
thin controllers, a service layer for data access, DB-backed sessions, and
RBAC via `roles` / `permissions` / `role_permissions`. No Composer, no
Bootstrap — custom CSS and Font Awesome icons throughout.

## Stack

- PHP 8.1+ (tested on 8.3), no Composer dependencies
- MySQL 8+ / MariaDB, accessed via PDO
- Custom CSS design system (no Bootstrap/Tailwind)
- Font Awesome 6 (via CDN)
- Vanilla JS for the mobile nav, flash-alert dismissal, and small UX touches

## Folder structure

```
app/Controllers/     Thin controllers (one per resource)
app/Services/         Data-access layer (CarService, BookingService, ...)
app/Views/            PHP view templates (+ app/Views/admin for the panel)
config/               config.php (bootstrap) and database.php (PDO singleton)
includes/             Auth.php, functions.php, DbSessionHandler.php
database/001_schema.sql   Full schema + seed data (categories, sample cars, settings)
database/002_..., 003_... Numbered follow-up migrations — run via bin/migrate.php
bin/migrate.php       Migration runner — tracks what's applied, runs what's new
public/               Web root — index.php front controller, assets/, .htaccess
```

## 1. Database setup

Create an empty database matching `DB_NAME` in your `.env` first (the
migration runner connects to it — it doesn't create it for you), then run
the migration runner. It applies `001_schema.sql` and every numbered
migration after it in order, and is safe to re-run any time
(already-applied files are skipped):

```bash
mysql -u root -p -e "CREATE DATABASE your_db_name CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
php bin/migrate.php
```

If you'd rather do the very first import by hand instead:

```bash
mysql -u root -p your_db_name < database/001_schema.sql
```

This creates these tables:

- `users` — `first_name`, `last_name`, `email`, `phone`, `password_hash`, `role_id`, `status`
- `roles`, `permissions`, `role_permissions` — RBAC (super_admin / manager / staff, seeded with sensible defaults)
- `sessions` — PHP sessions stored in the DB instead of disk (see `includes/DbSessionHandler.php`)
- `settings` — grouped key/value store (`general`, `seo`, ...) that drives page titles, meta descriptions, contact info, and social links. **Nothing is hardcoded in the views** — everything comes from this table via `setting()` / `seo_for()` in `includes/functions.php`.
- `cars`, `car_categories`, `car_images` — the fleet
- `bookings` — reservation requests
- `contact_messages`, `testimonials`

## 2. Configure environment

```bash
cp .env.example .env
```

Edit `.env` with your real DB credentials and site URL.

## 3. Set the admin password

The schema seeds one `super_admin` user
(`admin@bigkahunacarhire.co.ke`) with a working default password of
**`KahunaAdmin#2026`** — change it immediately after your first login, or
before going live, using the included CLI helper:

```bash
php database/set-admin-password.php admin@bigkahunacarhire.co.ke "YourNewStrongPassword!"
```

## 4. Point your web server at `public/`

The document root must be the `public/` folder, with `.htaccess` handling
clean URLs through the front controller (`public/index.php`). On Apache,
make sure `mod_rewrite` and `AllowOverride All` are enabled. For Nginx,
use an equivalent `try_files $uri $uri/ /index.php;` rule.

Uploaded car photos are written to `public/assets/images/cars/` — make sure
that folder is writable by the web server user.

## 5. Log in to the admin panel

Visit `/admin/login`. From there you can:

- **Fleet** — add/edit/delete cars, upload photos, set per-car SEO title/description, mark cars as featured
- **Bookings** — view requests and update status (pending → confirmed → ongoing → completed/cancelled)
- **Messages** — view contact form submissions
- **Settings** — edit general site info (phone, email, address, socials, WhatsApp) and **all SEO fields** (site-wide defaults + per-page titles/descriptions for Home, Fleet, About, Contact, Booking)

## Terms & Conditions gate, ID/license capture, damage disclaimer

Every booking now goes through a mandatory gate:

1. **`/book/terms`** — shows the Terms & Conditions (pulled from the
   `settings` table, group `legal`, key `terms_and_conditions` — fully
   editable in Admin → Settings → Legal Settings, no code changes needed).
   The customer must tick "I have read and agree..." before continuing.
   Acceptance is stamped with a timestamp in `$_SESSION['terms_accepted_at']`.
2. **`/book`** — redirects back to step 1 if terms haven't been accepted
   this session. The form now collects **First Name, Last Name, National
   ID/Passport Number, and Driving License Number** in addition to the
   original fields, and shows the damage/liability disclaimer (`legal` →
   `damage_disclaimer`) with its own required checkbox right above the
   submit button.
3. On submit, the booking row stores `terms_accepted` (1/0) and
   `terms_accepted_at` — an audit trail proving when and that the customer
   agreed, which matters for the damage-liability clause.

**If you already deployed the site before this update**, your live database
has the old schema (`full_name` instead of split first/last name, no
ID/license/terms columns). Run the migration once:

```bash
php bin/migrate.php
```

(or by hand: `mysql -u your_db_user -p your_db_name < database/002_migrate-terms-license.sql`)

This adds the new columns, splits any existing `full_name` values into
`first_name`/`last_name`, flags pre-existing bookings as `NOT COLLECTED` for
ID/license (since that data was never captured), drops the old `full_name`
column, and seeds the `legal` settings group with default Terms &
Conditions and disclaimer text (both editable afterwards in the admin
panel). Tested against a simulated copy of the old schema before shipping.

Admin → Bookings now shows each customer's ID number and driving license
number alongside their contact details, for verifying identity at pickup.

## M-Pesa Daraja (STK Push)

Customers can pay a deposit by M-Pesa right after booking — the confirmation
page prompts for their phone number, triggers an STK Push, and polls for the
result. A successful payment auto-confirms the booking.

**Setup:**

1. Get sandbox (or production) credentials from the
   [Safaricom Daraja portal](https://developer.safaricom.co.ke/): Consumer
   Key, Consumer Secret, your Paybill/Till shortcode, and the Lipa Na M-Pesa
   Online Passkey.
2. Add them to `.env` (never stored in the database):
   ```
   MPESA_ENV=sandbox            # or "production"
   MPESA_CONSUMER_KEY=...
   MPESA_CONSUMER_SECRET=...
   MPESA_SHORTCODE=174379
   MPESA_PASSKEY=...
   MPESA_CALLBACK_URL=          # leave blank to auto-use {APP_URL}/mpesa/callback
   ```
3. In **Admin → Settings → M-Pesa Settings**, set the deposit percentage,
   the paybill/till number shown to customers, and the account reference
   prefix. These are business-facing display values only — real API
   credentials always live in `.env`.
4. Your `MPESA_CALLBACK_URL` (or `{APP_URL}/mpesa/callback` by default) must
   be a **public HTTPS URL** Safaricom's servers can reach — this won't work
   on `localhost` or a private IP. For local development, tunnel it with
   ngrok or similar and set `MPESA_CALLBACK_URL` to the tunnel URL.

**How it works:**

- `app/Services/MpesaService.php` — OAuth token + STK Push request to Daraja.
- `app/Services/PaymentService.php` — reads/writes the `payments` table.
- `app/Controllers/PaymentController.php`:
  - `POST /book/{id}/pay` — customer submits their phone number, an STK Push
    prompt is sent, a `pending` row is created in `payments`.
  - `GET /payments/{checkoutId}/status` — polled by the confirmation page
    every few seconds while the customer completes the prompt.
  - `POST /mpesa/callback` — the public webhook Safaricom calls with the
    final result. Marks the payment `completed`/`failed` and, on success,
    automatically sets the booking's status to `confirmed`.
- **Admin → Bookings** shows each booking's payment status and M-Pesa
  receipt number once paid.
- If `mpesa.enabled` is set to `0` in Settings (or credentials are missing),
  the payment card is hidden/fails gracefully — bookings still work as
  request-only, exactly as before.

This was tested end-to-end against a local MySQL instance, including a
simulated Safaricom callback payload confirming the full
payment → booking-confirmed pipeline. The actual STK Push call to Safaricom's
servers requires real Daraja credentials and a public callback URL, so test
that leg against the sandbox once you have credentials.

## Roles & permissions (seeded)

| Role         | Permissions |
|--------------|-------------|
| super_admin  | everything |
| manager      | cars.view, cars.manage, bookings.view, bookings.manage, messages.view |
| staff        | cars.view, bookings.view, bookings.manage |

Adjust `role_permissions` in the database, or extend `AdminController` /
`Auth::requirePermission()` calls, to add more granular roles.

## SEO & mobile

- Every page pulls its `<title>`, meta description, keywords, robots tag,
  canonical URL, Open Graph and Twitter Card tags from the `settings` table
  (see `seo_for()` in `includes/functions.php`) or from the car's own
  `meta_title` / `meta_description` on car detail pages. All of it is
  editable from Admin → Settings / Admin → Fleet — nothing is hardcoded.
- `AutoRental` JSON-LD structured data is included sitewide.
- `/sitemap.xml` is a live route that now also emits `<lastmod>` per page
  (car pages use the car's own `updated_at`) so search engines can tell
  what's actually changed.
- Fully responsive: mobile nav drawer (now with a backdrop lock, auto-close
  on link tap, and 44px+ touch targets), fluid grids, and a mobile-friendly
  admin sidebar — tested down to small phone widths.

## Color theme — "Sunset Kahuna"

The old ocean-teal/amber palette has been reworked into a punchier
navy + sunset-coral palette that still fits the surf/wave branding but
gives "Book Now" CTAs much better contrast:

| Variable | Old | New |
|---|---|---|
| `--color-primary-900` (was `--color-teal-900`) | `#0E3A45` | `#0B2E3D` |
| `--color-primary-800` | `#124C59` | `#123C4E` |
| `--color-primary-700` | `#17606F` | `#1B5468` |
| `--color-accent-500` (was `--color-amber-500`) | `#F2A93B` | `#FF6B4A` |
| `--color-accent-600` | `#DD9126` | `#E8502E` |

All colors and sizes are CSS custom properties defined once in
`public_html/assets/css/components/00-tokens.css`, shared by both the
public site and the admin panel. Everything else — buttons, forms, header,
hero, cards, footer, admin layout — lives in its own file under
`components/` and reads from those tokens, so changing a brand color or a
spacing value in `00-tokens.css` propagates everywhere automatically.
There are no hardcoded hex codes or pixel values left inline in any view.

## Security notes

- Passwords hashed with `password_hash()` (bcrypt).
- CSRF tokens on every POST form (`csrf_field()` / `verify_csrf()`).
- Sessions are DB-backed, HttpOnly, SameSite=Lax cookies.
- Raw DB errors are never shown to visitors in production (`APP_ENV=production`).
- Prepared statements (PDO) everywhere — no raw SQL string interpolation.

## What's stubbed / next steps

- **M-Pesa is still in sandbox** (`MPESA_ENV=sandbox` in `.env`) even though
  the site is deployed with `APP_ENV=production` — real customers cannot
  pay yet. The admin dashboard now shows a **go-live checklist banner**
  that flags this (and other launch gaps) until it's fixed. Swap in real
  Safaricom Daraja production credentials to go live. Full payment
  (not just deposit) or M-Pesa B2C refunds still aren't implemented.
- **Multi-photo galleries are now implemented** — Admin → Fleet → Edit Car
  has an "Additional Gallery Photos" uploader (multi-select) backed by the
  existing `car_images` table, with per-photo delete. The car detail page
  shows a clickable thumbnail strip under the main photo.
- **Placeholder car/OG/favicon images have been generated** in the new
  theme (`public/assets/images/cars/*.jpg`, `og-default.jpg`,
  `favicon.png`) so the site no longer shows broken images — swap these
  for real photography whenever it's available (Admin → Fleet → Edit Car →
  Cover Photo / Gallery Photos).
- Real business details are still placeholders in the database
  (`+254 700 000 000`, generic social links, no Google Analytics ID, no
  Search Console verification) — update these under Admin → Settings, they
  directly affect how easily customers can find and trust the site.
- Email/SMS notifications on new bookings/payments aren't wired up —
  currently bookings and payments just land in the admin panel and DB.
## Paystack InlineJS Popup V2

The online booking deposit payment uses Paystack server-side transaction initialization plus InlineJS V2 `resumeTransaction(access_code)`. The secret key stays on the server; the browser receives only the transaction access code. Paystack webhook/callback verification remains responsible for confirming payment before a booking is marked confirmed.

### Migration

If migrations 001-013 are already applied, run `database/014_paystack_resume_transaction.sql`.

### Paystack URLs

- Callback: `https://bigkahunacarhire.co.ke/payments/paystack/callback`
- Webhook: `https://bigkahunacarhire.co.ke/payments/paystack/webhook`
- InlineJS V2 CDN: `https://js.paystack.co/v2/inline.js`



## Phase 4
See `PHASE4_WHATSAPP.md` for the WhatsApp inbox, webhook, reminder job and Meta setup.
