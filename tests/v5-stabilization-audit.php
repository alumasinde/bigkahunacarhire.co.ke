<?php
declare(strict_types=1);

$root = dirname(__DIR__);
$failures = [];
$passes = [];

function pass(string $message): void { global $passes; $passes[] = $message; }
function fail(string $message): void { global $failures; $failures[] = $message; }
function assert_true(bool $condition, string $message): void { $condition ? pass($message) : fail($message); }

// 1. PHP syntax across the complete project.
$phpFiles = [];
$it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS));
foreach ($it as $file) {
    if ($file->isFile() && $file->getExtension() === 'php') $phpFiles[] = $file->getPathname();
}
foreach ($phpFiles as $file) {
    $output = [];
    $code = 0;
    exec('php -l ' . escapeshellarg($file) . ' 2>&1', $output, $code);
    if ($code !== 0) fail('PHP syntax: ' . str_replace($root . '/', '', $file) . ' — ' . implode(' ', $output));
}
if (!$failures) pass('All PHP files pass syntax validation (' . count($phpFiles) . ').');

// 2. Every front-controller route points at a real controller method.
$index = file_get_contents($root . '/public_html/index.php') ?: '';
preg_match_all("/'(?:GET|POST) ([^']+)'\s*=>\s*\['([^']+)',\s*'([^']+)'\]/", $index, $routes, PREG_SET_ORDER);
foreach ($routes as $route) {
    $class = $route[2]; $method = $route[3];
    $file = $root . '/app/Controllers/' . $class . '.php';
    if (!is_file($file)) { fail("Route {$route[1]} references missing controller {$class}"); continue; }
    $source = file_get_contents($file) ?: '';
    assert_true((bool)preg_match('/function\s+' . preg_quote($method, '/') . '\s*\(/', $source), "Route {$route[1]} → {$class}::{$method}");
}

// 3. RBAC permission names used in code must be seeded by migrations.
$permissionText = '';
foreach (glob($root . '/database/*.sql') as $sql) $permissionText .= "\n" . (file_get_contents($sql) ?: '');
$used = [];
foreach (glob($root . '/app/Controllers/*.php') as $file) {
    $src = file_get_contents($file) ?: '';
    preg_match_all("/requirePermission\(\'([^\']+)\'\)/", $src, $m);
    foreach ($m[1] as $name) $used[$name] = true;
}
foreach (array_keys($used) as $name) {
    assert_true(str_contains($permissionText, "'{$name}'"), "RBAC permission seeded: {$name}");
}

// 4. Booking lifecycle invariants.
$bookingService = file_get_contents($root . '/app/Services/BookingService.php') ?: '';
assert_true(str_contains($bookingService, "'pending'   => ['confirmed', 'cancelled']"), 'Booking transition: pending → confirmed/cancelled');
assert_true(str_contains($bookingService, "'confirmed' => ['cancelled']"), 'Booking transition: confirmed → cancelled');
assert_true(str_contains($bookingService, "if (\$booking['status'] !== 'confirmed')"), 'Checkout requires confirmed booking');
assert_true(str_contains($bookingService, "if(\$booking['status']!=='ongoing')"), 'Return requires ongoing booking');
assert_true(str_contains($bookingService, 'FOR UPDATE'), 'Booking and rental transitions lock rows');
assert_true(str_contains($bookingService, 'balance > 0.009'), 'Vehicle handover blocks unpaid balances');

// 5. Payment invariants.
$paymentController = file_get_contents($root . '/app/Controllers/PaymentController.php') ?: '';
$paymentService = file_get_contents($root . '/app/Services/PaymentService.php') ?: '';
assert_true(str_contains($paymentService, 'AND status = "pending"'), 'M-Pesa completion is idempotent');
assert_true(str_contains($paymentService, 'WHERE checkout_request_id = :cid AND status = "pending"'), 'Failed M-Pesa callback cannot overwrite a completed payment');
assert_true(str_contains($paymentController, 'callbackAmount'), 'M-Pesa callback validates callback amount');
assert_true(str_contains($paymentController, "payment_purpose"), 'Paystack payments retain deposit/balance purpose');
assert_true(str_contains($paymentController, "currency_mismatch"), 'Paystack currency is verified server-side');
assert_true(str_contains($paymentController, "amount_mismatch"), 'Paystack amount is verified server-side');

// 6. Guest/public token payment flow.
$bookingController = file_get_contents($root . '/app/Controllers/BookingController.php') ?: '';
$bookingStatus = file_get_contents($root . '/app/Views/booking-status.php') ?: '';
$bookingConfirmation = file_get_contents($root . '/app/Views/booking-confirmation.php') ?: '';
assert_true(str_contains($bookingService, 'verifyPublicTokenForBooking'), 'Secure guest token verification exists');
assert_true(str_contains($bookingController, "\$_GET['token']"), 'Confirmation accepts secure guest token');
assert_true(str_contains($bookingStatus, '&token='), 'Booking tracker carries token into payment options');
assert_true(str_contains($bookingConfirmation, 'public_token'), 'Payment initialization carries public token');
assert_true(str_contains($paymentController, 'bookingOwnedOrToken'), 'Payment endpoints accept secure guest token');

// 7. WhatsApp webhook and inbox reliability.
$waController = file_get_contents($root . '/app/Controllers/WhatsAppController.php') ?: '';
$waInbox = file_get_contents($root . '/app/Services/WhatsAppInboxService.php') ?: '';
assert_true(str_contains($waController, 'verifySignature'), 'WhatsApp webhook verifies signature');
assert_true(str_contains($waController, "http_response_code(500)"), 'WhatsApp webhook exposes processing failures for provider retry');
assert_true(str_contains($waInbox, 'provider_message_id=:pid'), 'WhatsApp inbound messages are deduplicated by provider ID');
assert_true(str_contains($waInbox, 'return (int)$existingId'), 'WhatsApp webhook retries do not duplicate inbox messages');

// 8. Migration safety.
$m022 = file_get_contents($root . '/database/022_phase5_customer_lifecycle.sql') ?: '';
$m023 = file_get_contents($root . '/database/023_v5_stabilization.sql') ?: '';
$m024 = file_get_contents($root . '/database/024_v51_notification_claims.sql') ?: '';
assert_true(str_contains($m022, 'information_schema.COLUMNS'), 'Phase 5 migration checks existing columns');
assert_true(str_contains($m022, 'information_schema.STATISTICS'), 'Phase 5 migration checks existing index');
assert_true(str_contains($m023, 'damage_accepted'), 'Stabilization migration persists damage acknowledgement');
assert_true(str_contains($m023, "seo.manage"), 'Stabilization migration seeds SEO permission');
assert_true(str_contains($m023, 'uq_wa_provider_message'), 'Stabilization migration adds WhatsApp dedupe index');
assert_true(str_contains($m024, 'notification_claims'), 'V5.1 migration adds notification claims');
assert_true(str_contains($m024, 'uq_notification_claim'), 'V5.1 migration makes notification claims unique');
$notificationService = file_get_contents($root . '/app/Services/NotificationService.php') ?: '';
assert_true(str_contains($notificationService, 'claimCustomerNotification'), 'Customer WhatsApp notifications use atomic claims');
$lifecycle = file_get_contents($root . '/bin/run-customer-lifecycle.php') ?: '';
assert_true(str_contains($lifecycle, 'GET_LOCK'), 'Lifecycle worker uses a MySQL advisory lock');
$bookingView = file_get_contents($root . '/app/Views/booking.php') ?: '';
$bookingCss = file_get_contents($root . '/public_html/assets/css/booking-v2.css') ?: '';
assert_true(str_contains($bookingCss, 'booking-v2-agreement input[type="checkbox"]'), 'Booking checkbox width is explicitly constrained');
assert_true(str_contains($bookingView, 'summary-pickup-location'), 'Booking summary includes pickup/return details');
assert_true(str_contains($bookingView, 'whatsapp_opt_in'), 'Booking UI exposes WhatsApp opt-in');
assert_true(str_contains($index, "'GET /book/availability'"), 'Public booking availability endpoint is routed');
assert_true(str_contains($bookingController, 'public function availability(): void'), 'Booking controller exposes availability action');
assert_true(str_contains($bookingService, 'availabilityForPeriod'), 'Booking service has date-range availability check');
assert_true(str_contains($bookingView, 'booking-availability-message'), 'Booking UI has customer-facing availability messaging');
assert_true(str_contains($bookingView, 'checkAvailability'), 'Booking UI actively checks availability when dates change');
assert_true(str_contains($bookingView, 'is-unavailable'), 'Booked vehicles are disabled in the booking UI');

// 9. Sensitive vehicle documents are private for new uploads.
$admin = file_get_contents($root . '/app/Controllers/AdminController.php') ?: '';
assert_true(str_contains($admin, "private://vehicle-documents/"), 'New vehicle documents are stored outside public_html');
assert_true(str_contains($admin, 'downloadVehicleDocument'), 'Vehicle document download route has a controller');
assert_true(str_contains($admin, "Auth::requirePermission('cars.view')"), 'Vehicle document downloads require fleet permission');

// 10. No production secret file shipped.
assert_true(!is_file($root . '/.env'), 'No real .env file is included in the deployment artifact');

foreach ($passes as $p) echo "PASS  {$p}\n";
foreach ($failures as $f) echo "FAIL  {$f}\n";
echo "\nResult: " . (count($failures) ? 'FAILED' : 'PASSED') . " — " . count($passes) . " checks passed, " . count($failures) . " failed.\n";
exit($failures ? 1 : 0);
