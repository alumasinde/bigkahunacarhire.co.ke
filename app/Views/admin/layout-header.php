<?php
/** @var array $seo */
$flashes = get_flashes();
$current = current_path();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($seo['title']) ?></title>
<meta name="robots" content="noindex, nofollow">
<link href="https://fonts.googleapis.com/css2?family=Anton&family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
<?php
/* Same shared tokens as the public site, plus admin-only layout/components. */
$adminCssComponents = [
    'css/components/00-tokens.css',
    'css/components/02-buttons.css',
    'css/components/03-forms.css',
    'css/components/06-sections.css',
    'css/components/10-admin-layout.css',
    'css/components/11-admin-components.css',
];
foreach ($adminCssComponents as $component): ?>
<link rel="stylesheet" href="<?= asset($component) ?>">
<?php endforeach; ?>
<link rel="stylesheet" href="<?= asset('css/15-operations-phase6.css') ?>">
<link rel="stylesheet" href="<?= asset('css/16-rental-operations-phase7.css') ?>">
<link rel="stylesheet" href="<?= asset('css/17-fleet-phase8.css') ?>">
<link rel="stylesheet" href="<?= asset('css/21-phase3-operations.css') ?>">
<link rel="stylesheet" href="<?= asset('css/22-phase4-whatsapp.css') ?>">
</head>
<body class="admin-body">

<aside class="admin-sidebar">
  <div class="admin-brand">BIG <span>KAHUNA</span><br><small style="font-family:'Inter';font-size:0.6rem;letter-spacing:1px;opacity:0.7;">ADMIN PANEL</small></div>
  <nav class="admin-nav">
    <a href="<?= base_url('admin/dashboard') ?>" class="<?= $current === 'admin/dashboard' ? 'active' : '' ?>"><i class="fa-solid fa-gauge"></i> Dashboard</a>
    <?php if (Auth::can('cars.view')): ?>
      <a href="<?= base_url('admin/cars') ?>" class="<?= str_starts_with($current, 'admin/cars') ? 'active' : '' ?>"><i class="fa-solid fa-car"></i> Fleet</a>
    <?php endif; ?>
    <?php if (Auth::can('cars.manage')): ?>
      <a href="<?= base_url('admin/categories') ?>" class="<?= str_starts_with($current, 'admin/categories') ? 'active' : '' ?>"><i class="fa-solid fa-layer-group"></i> Categories</a>
      <a href="<?= base_url('admin/chauffeur-rates') ?>" class="<?= str_starts_with($current, 'admin/chauffeur-rates') ? 'active' : '' ?>"><i class="fa-solid fa-id-badge"></i> Chauffeur Rates</a>
    <?php endif; ?>
    <?php if (Auth::can('bookings.view')): ?>
      <a href="<?= base_url('admin/bookings') ?>" class="<?= $current === 'admin/bookings' ? 'active' : '' ?>"><i class="fa-solid fa-calendar-check"></i> Bookings</a>
      <a href="<?= base_url('admin/bookings/calendar') ?>" class="<?= $current === 'admin/bookings/calendar' ? 'active' : '' ?>"><i class="fa-solid fa-calendar-days"></i> Calendar</a>
      <a href="<?= base_url('admin/payments') ?>" class="<?= $current === 'admin/payments' ? 'active' : '' ?>">
        <i class="fa-solid fa-credit-card"></i> Payments
        <?php $navPendingCount = PaymentService::make()->pendingManualVerificationCount(); ?>
        <?php if ($navPendingCount > 0): ?><span class="admin-nav-badge"><?= (int)$navPendingCount ?></span><?php endif; ?>
      </a>
    <?php endif; ?>
    <?php if (Auth::can('bookings.view')): ?>
      <a href="<?= base_url('admin/reports') ?>" class="<?= $current === 'admin/reports' ? 'active' : '' ?>"><i class="fa-solid fa-chart-line"></i> Reports</a>
      <a href="<?= base_url('admin/activity') ?>" class="<?= $current === 'admin/activity' ? 'active' : '' ?>"><i class="fa-solid fa-clock-rotate-left"></i> Activity</a>
    <?php endif; ?>
    <?php if (Auth::can('messages.view')): ?>
      <a href="<?= base_url('admin/messages') ?>" class="<?= $current === 'admin/messages' ? 'active' : '' ?>"><i class="fa-solid fa-envelope"></i> Messages</a>
      <a href="<?= base_url('admin/whatsapp') ?>" class="<?= str_starts_with($current, 'admin/whatsapp') ? 'active' : '' ?>"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
    <?php endif; ?>
    <?php if (Auth::can('seo.manage')): ?>
      <a href="<?= base_url('admin/seo-pages') ?>" class="<?= str_starts_with($current, 'admin/seo-pages') ? 'active' : '' ?>"><i class="fa-solid fa-magnifying-glass-chart"></i> SEO Pages</a>
    <?php endif; ?>
    <?php if ((Auth::user()['role'] ?? '') === 'super_admin'): ?>
      <a href="<?= base_url('admin/purge-data') ?>" class="<?= $current === 'admin/purge-data' ? 'active' : '' ?>"><i class="fa-solid fa-triangle-exclamation"></i> Purge Data</a>
    <?php endif; ?>
    <?php if (Auth::can('settings.manage')): ?>
      <a href="<?= base_url('admin/reviews') ?>" class="<?= str_starts_with($current, 'admin/reviews') ? 'active' : '' ?>"><i class="fa-solid fa-star-half-stroke"></i> Reviews</a>
      <a href="<?= base_url('admin/testimonials') ?>" class="<?= str_starts_with($current, 'admin/testimonials') ? 'active' : '' ?>"><i class="fa-solid fa-star"></i> Testimonials</a>
      <a href="<?= base_url('admin/settings') ?>" class="<?= $current === 'admin/settings' ? 'active' : '' ?>"><i class="fa-solid fa-gear"></i> Settings</a>
    <?php endif; ?>
    <a href="<?= base_url('/') ?>" target="_blank"><i class="fa-solid fa-arrow-up-right-from-square"></i> View Website</a>
  </nav>
  <div class="admin-user">
    Signed in as <strong><?= e(Auth::fullName()) ?></strong><br>
    <a href="<?= base_url('admin/logout') ?>"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
  </div>
</aside>

<div class="admin-main">
  <div class="admin-topbar">
    <div style="display:flex;align-items:center;gap:14px;">
      <button class="mobile-menu-toggle"><i class="fa-solid fa-bars"></i></button>
      <h1><?= e($seo['title']) ?></h1>
    </div>
  </div>
  <div class="admin-content">
    <?php foreach ($flashes as $type => $msg): ?>
      <div class="alert alert-<?= $type === 'error' ? 'error' : 'success' ?>">
        <i class="fa-solid <?= $type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i> <?= e($msg) ?>
      </div>
    <?php endforeach; ?>
