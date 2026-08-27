# Big Kahuna Car Hire — Local Setup

## PHP built-in server

From the project root:

```bash
php -S 127.0.0.1:8000 -t public_html
```

Open `http://localhost:8000`.

## Environment

Copy `.env.example` to `.env` and set local MySQL credentials:

```env
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:8000
DB_HOST=127.0.0.1
DB_NAME=bigkahuna_carhire
DB_USER=root
DB_PASS=
```

Do not commit or deploy `.env`.

## Database

Create the local database, then run:

```bash
php bin/migrate.php
```

V5.1 adds a notification claim table used to prevent duplicate customer WhatsApp lifecycle messages when cron/web workers overlap.

## Customer lifecycle job

```bash
php bin/run-customer-lifecycle.php
```

The worker uses a MySQL advisory lock, so overlapping cron executions safely skip instead of processing the same lifecycle window simultaneously.
