<?php
// Backwards-compatible cron entry point.
// Phase 5 consolidates pickup, payment, return and post-rental automation
// into one idempotent customer lifecycle runner.
require __DIR__ . '/run-customer-lifecycle.php';
