# Payment flow fixes — albatechsolutions.co.ke

Drop these 5 files into the matching paths on the live server (they replace the
existing files, same locations). No DB migration needed.

## 1. resources/views/public/service-show.php
Main CTA on every service page now goes to `/orders/create?service={slug}`
(the real order → quote → pay pipeline) instead of `/quote` (a disconnected
lead-capture form). Kept a secondary "ask a question first" link to `/quote`
for people who aren't ready to commit.

## 2. app/Core/Request.php
Added `fullUrl()` (path + query string), used in fix #3 below.

## 3. app/Core/Middleware/AuthMiddleware.php
Was storing only the path for post-login redirect, dropping query strings.
A guest clicking "Request This Service" would get bounced to login, then
land back on `/orders/create` with no `?service=` and hit a dead end
("Please choose a service to request first" → redirected to /services).
Now stores the full URL so the service selection survives login/register.

## 4. .env and .env.example
- `PAYSTACK_CALLBACK_URL` and `MPESA_CALLBACK_URL` now use `www.` to match
  `APP_URL` and the host's forced www redirect — avoids a 301 hop that could
  drop the payment reference on the way back from Paystack.
- Added a TODO comment on the Paystack keys: still `pk_test_`/`sk_test_`
  while `APP_ENV=production`. Swap in live keys before this goes to real
  customers — I can't generate those for you, they come from your Paystack
  dashboard.

## 5. Daraja/M-Pesa removed for now
Deleted (unused, never had a route wired to them anyway):
- `app/Modules/Payments/Service/DarajaClient.php`
- `app/Modules/Payments/Controller/MpesaCallbackController.php`
- `config/mpesa.php`

**Delete these same 3 files on the live server too** — they're not in the zip
since there's nothing to replace them with, just remove them.

M-Pesa env vars in `.env`/`.env.example` are commented out, not deleted, with
a note on what to restore when you're ready to add Daraja back. The gateway
architecture (`PaymentGatewayInterface`) was already provider-agnostic, so
re-adding Daraja later is a self-contained new `DarajaClient` + a route —
it won't touch the Paystack integration.

Paystack already offers M-Pesa as a checkout channel to Kenyan customers, so
this doesn't remove M-Pesa support — just the redundant direct Safaricom
integration.

## Still on you
- Every service is still `price_type = quote`, so even with the CTA fixed,
  a customer's first order still waits on a staff-entered quote before they
  can pay. If you have firm prices for any services, set those to `fixed`
  (or `starting_from`) in the admin so they can go straight to Accept → Pay.
- Swap the live Paystack keys in before announcing this is open for business.

## V5 Stabilization / Audit Pass

- Fixed missing vehicle document download controller and protected downloads with `cars.view`.
- Moved new vehicle documents outside `public_html`.
- Added missing `seo.manage` RBAC permission.
- Made M-Pesa callbacks idempotent and validated callback amounts/receipts.
- Added public-token authorization to guest payment/confirmation flows.
- Added WhatsApp webhook deduplication and retry-safe failure responses.
- Fixed chauffeur bookings incorrectly requiring a driving licence.
- Persisted damage acknowledgement separately.
- Added explicit WhatsApp booking-update opt-in.
- Centralized booking status transition validation.
- Removed stale booking code from shared `main.js`.
- Made Phase 5 migration idempotent.
- Added `database/023_v5_stabilization.sql`.
- Added `tests/v5-stabilization-audit.php` and `V5_STABILIZATION_AUDIT.md`.

## V5.1 — Stabilization + Booking UI Hardening

- Added MySQL advisory locking to the customer lifecycle worker so overlapping cron runs skip safely.
- Added atomic notification claims with stale-claim recovery to prevent duplicate customer WhatsApp lifecycle messages during concurrent requests.
- Added a safe vehicle image fallback for missing/deleted car assets.
- Fixed the booking confirmation checkbox width/layout bug caused by the global form `width:100%` rule.
- Reworked the booking review/terms block and trip summary for clearer hierarchy and mobile use.
- Hardened booking step JavaScript validation and keyboard-accessible car selection.
- Added local development setup instructions and `.gitignore` protection for `.env`.
- Deployment artifacts no longer contain a real `.env` file.
