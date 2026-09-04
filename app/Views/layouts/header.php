<?php
/** @var array $seo */
$siteName = setting('general', 'site_name', 'Big Kahuna Car Hire');
$flashes = get_flashes();
$w = static fn(string $key, string $default = ''): string => setting('website', $key, $default);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= e($seo['title']) ?></title>
<meta name="description" content="<?= e($seo['description']) ?>">
<meta name="robots" content="<?= e($seo['robots'] ?? 'index, follow') ?>">
<link rel="canonical" href="<?= e(base_url(current_path())) ?>">

<!-- Open Graph -->
<meta property="og:site_name" content="<?= e($siteName) ?>">
<meta property="og:title" content="<?= e($seo['title']) ?>">
<meta property="og:description" content="<?= e($seo['description']) ?>">
<meta property="og:type" content="website">
<meta property="og:locale" content="en_KE">
<meta property="og:url" content="<?= e(base_url(current_path())) ?>">
<?php if (!empty($seo['og_image'])): ?><meta property="og:image" content="<?= e(base_url($seo['og_image'])) ?>"><meta property="og:image:alt" content="<?= e($seo['title']) ?>"><?php endif; ?>

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="<?= e($seo['title']) ?>">
<meta name="twitter:description" content="<?= e($seo['description']) ?>">
<?php if (!empty($seo['og_image'])): ?><meta name="twitter:image" content="<?= e(base_url($seo['og_image'])) ?>"><?php endif; ?>

<?php $gsv = setting('seo', 'google_site_verification'); if ($gsv): ?>
<meta name="google-site-verification" content="<?= e($gsv) ?>">
<?php endif; ?>

<link rel="icon" type="image/png" href="<?= asset('images/favicon.png') ?>">
<link rel="manifest" href="<?= base_url('site.webmanifest') ?>">

<!-- Font Awesome -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">

<?= style_links() ?>

<?php $gaId = setting('seo', 'google_analytics_id'); if ($gaId): ?>
<script async src="https://www.googletagmanager.com/gtag/js?id=<?= e($gaId) ?>"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());
  gtag('config', '<?= e($gaId) ?>');
</script>
<?php endif; ?>

<!-- Local business structured data -->
<script type="application/ld+json">
<?php
$businessSchema = [
  '@context'=>'https://schema.org',
  '@type'=>'AutoRental',
  '@id'=>base_url('').'#organization',
  'name'=>$siteName,
  'url'=>base_url(''),
  'telephone'=>setting('general','phone_primary'),
  'email'=>setting('general','email'),
  'description'=>setting('seo','default_meta_description'),
  'image'=>$seo['og_image'] ? base_url($seo['og_image']) : null,
  'address'=>[
    '@type'=>'PostalAddress',
    'streetAddress'=>setting('general','address'),
    'addressCountry'=>setting('general','address_country','KE')
  ],
  'priceRange'=>setting('general','price_range'),
  'openingHours'=>setting('general','opening_hours'),
  'sameAs'=>array_values(array_filter([
    setting('general','facebook_url'),
    setting('general','instagram_url'),
    setting('general','twitter_url')
  ]))
];
$lat = setting('general','latitude');
$lng = setting('general','longitude');
if ($lat !== '' && $lng !== '' && is_numeric($lat) && is_numeric($lng)) {
  $businessSchema['geo'] = ['@type'=>'GeoCoordinates','latitude'=>(float)$lat,'longitude'=>(float)$lng];
}
$businessSchema = array_filter($businessSchema, fn($v)=>$v!==null && $v!=='' && $v!==[]);
echo json_encode($businessSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
?>
</script>
<script type="application/ld+json">
<?= json_encode([
  '@context'=>'https://schema.org',
  '@type'=>'WebSite',
  '@id'=>base_url('').'#website',
  'url'=>base_url(''),
  'name'=>$siteName,
  'publisher'=>['@id'=>base_url('').'#organization'],
  'inLanguage'=>'en-KE'
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>
</script>
</head>
<body>

<div class="topbar">
  <div class="container">
    <div class="topbar-contact">
      <a href="tel:<?= e(setting('general', 'phone_primary')) ?>"><i class="fa-solid fa-phone"></i> <?= e(setting('general', 'phone_primary')) ?></a>
      <a href="mailto:<?= e(setting('general', 'email')) ?>"><i class="fa-solid fa-envelope"></i> <?= e(setting('general', 'email')) ?></a>
      <span><i class="fa-solid fa-clock"></i> <?= e(setting('general', 'working_hours')) ?></span>
    </div>
    <div class="topbar-social">
      <?php if ($u = setting('general', 'facebook_url')): ?><a href="<?= e($u) ?>" aria-label="Facebook" target="_blank" rel="noopener"><i class="fa-brands fa-facebook"></i></a><?php endif; ?>
      <?php if ($u = setting('general', 'instagram_url')): ?><a href="<?= e($u) ?>" aria-label="Instagram" target="_blank" rel="noopener"><i class="fa-brands fa-instagram"></i></a><?php endif; ?>
      <?php if ($u = setting('general', 'twitter_url')): ?><a href="<?= e($u) ?>" aria-label="X (Twitter)" target="_blank" rel="noopener"><i class="fa-brands fa-x-twitter"></i></a><?php endif; ?>
    </div>
  </div>
</div>

<header class="site-header">
  <div class="container">
    <a href="<?= base_url('/') ?>" class="brand">
      <span class="brand-mark"><i class="fa-solid fa-water"></i></span>
      <span class="brand-text"><?= e($siteName) ?></span>
    </a>
    <button class="nav-toggle" aria-label="Toggle navigation" aria-expanded="false"><i class="fa-solid fa-bars"></i></button>
    <nav class="main-nav">
      <ul>
        <li><a href="<?= base_url('/') ?>" class="<?= current_path() === '' ? 'active' : '' ?>"><?= e($w('nav_home_label', 'Home')) ?></a></li>
        <li><a href="<?= base_url('fleet') ?>" class="<?= str_starts_with(current_path(), 'fleet') ? 'active' : '' ?>"><?= e($w('nav_fleet_label', 'Fleet')) ?></a></li>
        <li><a href="<?= base_url('about') ?>" class="<?= current_path() === 'about' ? 'active' : '' ?>"><?= e($w('nav_about_label', 'About')) ?></a></li>
        <li><a href="<?= base_url('contact') ?>" class="<?= current_path() === 'contact' ? 'active' : '' ?>"><?= e($w('nav_contact_label', 'Contact')) ?></a></li>
        <li><a href="<?= base_url(CustomerAuth::check() ? 'account/dashboard' : 'account/login') ?>" class="<?= str_starts_with(current_path(), 'account') ? 'active' : '' ?>"><i class="fa-solid fa-user"></i> <?= e(CustomerAuth::check() ? $w('nav_bookings_label', 'My Bookings') : $w('nav_account_label', 'My Account')) ?></a></li>
        <li><a href="<?= base_url('book') ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-calendar-check"></i> <?= e($w('nav_book_now_label', 'Book Now')) ?></a></li>
      </ul>
    </nav>
  </div>
</header>

<main>
<?php if (!empty($flashes)): ?>
  <div class="container mt-2">
    <?php foreach ($flashes as $type => $msg): ?>
      <div class="alert alert-<?= e($type === 'error' ? 'error' : 'success') ?>">
        <i class="fa-solid <?= $type === 'error' ? 'fa-circle-exclamation' : 'fa-circle-check' ?>"></i> <?= e($msg) ?>
      </div>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
