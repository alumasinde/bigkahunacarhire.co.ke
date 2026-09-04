<?php view('layouts/header', ['seo' => $seo]); ?>
<?php $w = static fn(string $key, string $default = ''): string => setting('website', $key, $default); ?>

<?php
// Per-car structured data — lets Google show price/availability directly
// in search results for this listing, not just the sitewide business info.
$availabilitySchema = $car['status'] === 'available' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock';
$carJsonLd = [
    '@context'               => 'https://schema.org',
    '@type'                  => 'Car',
    'name'                   => $car['name'],
    'brand'                  => ['@type' => 'Brand', 'name' => $car['brand']],
    'model'                  => $car['model'],
    'vehicleModelDate'       => (string) $car['year'],
    'vehicleTransmission'    => $car['transmission'],
    'fuelType'                => $car['fuel_type'],
    'vehicleSeatingCapacity' => (int) $car['seats'],
    'numberOfDoors'          => (int) $car['doors'],
    'description'            => $car['description'] ?: ($car['name'] . ' ' . $w('vehicle_default_description')),
    'offers'                 => [
        '@type'         => 'Offer',
        'priceCurrency' => setting('general', 'currency', 'KES'),
        'price'         => (string) $car['price_per_day'],
        'availability'  => $availabilitySchema,
        'url'           => base_url('fleet/' . $car['slug']),
    ],
];
if (!empty($car['image_path'])) {
    $carJsonLd['image'] = car_image_url($car['image_path']);
}
?>
<script type="application/ld+json"><?= json_encode($carJsonLd, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>

<div class="bk-detail-hero">
  <div class="container">
    <div class="breadcrumb"><a href="<?= base_url('/') ?>"><?= e($w('nav_home_label','Home')) ?></a> <span aria-hidden="true">/</span> <a href="<?= base_url('fleet') ?>"><?= e($w('nav_fleet_label','Fleet')) ?></a> <span aria-hidden="true">/</span> <span><?= e($car['name']) ?></span></div>
    <h1><?= e($car['name']) ?></h1>
  </div>
</div>

<section>
  <div class="container bk-detail-grid">
    <div class="bk-detail-copy">
      <div class="bk-detail-main-photo">
        <?php if (!empty($car['image_path'])): ?>
          <img id="car-main-photo" src="<?= e(car_image_url($car['image_path'])) ?>" alt="<?= e($car['name']) ?> car hire in <?= e($car['location'] ?? 'Kenya') ?>" width="1200" height="750" fetchpriority="high" decoding="async">
        <?php else: ?>
          <div id="car-main-photo-placeholder" class="car-detail-placeholder"><i class="fa-solid fa-car"></i></div>
        <?php endif; ?>
      </div>

      <?php if (!empty($gallery)): ?>
        <div class="bk-detail-gallery">
          <?php if (!empty($car['image_path'])): ?><img src="<?= e(car_image_url($car['image_path'])) ?>" alt="" class="car-gallery-thumb active" data-src="<?= e(car_image_url($car['image_path'])) ?>"><?php endif; ?>
          <?php foreach ($gallery as $img): ?><img src="<?= e(car_image_url($img['image_path'])) ?>" alt="" class="car-gallery-thumb" data-src="<?= e(car_image_url($img['image_path'])) ?>"><?php endforeach; ?>
        </div>
        <script>
        document.querySelectorAll('.car-gallery-thumb').forEach(function (thumb) {
          thumb.addEventListener('click', function () {
            var main = document.getElementById('car-main-photo');
            if (main) { main.src = thumb.dataset.src; }
            document.querySelectorAll('.car-gallery-thumb').forEach(function (t) { t.classList.remove('active'); });
            thumb.classList.add('active');
          });
        });
        </script>
      <?php endif; ?>

      <h2><?= e($w('vehicle_overview_title')) ?></h2>
      <p><?= nl2br(e($car['description'] ?: $w('vehicle_default_description'))) ?></p>

      <div class="bk-detail-specs">
        <div class="bk-detail-spec"><i class="fa-solid fa-users"></i><div><span>Seats</span><strong><?= (int) $car['seats'] ?></strong></div></div>
        <div class="bk-detail-spec"><i class="fa-solid fa-door-closed"></i><div><span>Doors</span><strong><?= (int) $car['doors'] ?></strong></div></div>
        <div class="bk-detail-spec"><i class="fa-solid fa-gears"></i><div><span>Transmission</span><strong><?= e(ucfirst($car['transmission'])) ?></strong></div></div>
        <div class="bk-detail-spec"><i class="fa-solid fa-gas-pump"></i><div><span>Fuel type</span><strong><?= e(ucfirst($car['fuel_type'])) ?></strong></div></div>
        <div class="bk-detail-spec"><i class="fa-solid fa-calendar"></i><div><span>Year</span><strong><?= e((string) $car['year']) ?></strong></div></div>
        <div class="bk-detail-spec"><i class="fa-solid fa-location-dot"></i><div><span>Location</span><strong><?= e($car['location']) ?></strong></div></div>
      </div>
    </div>

    <aside class="bk-detail-booking">
      <p class="bk-car-cat"><?= e($car['category_name'] ?? '') ?></p>
      <div class="bk-detail-price"><small><?= e($w('vehicle_daily_rate_label')) ?></small><?= money($car['price_per_day']) ?></div>
      <?php $chauffeurFee = CarService::make()->effectiveChauffeurFee($car); ?>
      <?php if ($chauffeurFee > 0): ?><p class="status-text"><i class="fa-solid fa-id-badge"></i> <?= e($w('vehicle_chauffeur_prefix')) ?> +<?= money($chauffeurFee) ?>/day</p><?php endif; ?>
      <div class="bk-status"><span><?= e($w('vehicle_availability_label')) ?></span><strong class="<?= $car['status'] === 'available' ? 'status-available' : 'status-unavailable' ?>"><?= e(ucfirst($car['status'])) ?></strong></div>
      <a href="<?= base_url('book?car=' . (int) $car['id']) ?>" class="btn btn-primary btn-block"><i class="fa-solid fa-calendar-check"></i> <?= e($w('vehicle_book_button')) ?></a>
      <a href="https://wa.me/<?= e(setting('general', 'whatsapp_number')) ?>?text=<?= urlencode($w('vehicle_whatsapp_interest') . ' ' . $car['name']) ?>" target="_blank" rel="noopener" class="btn btn-outline btn-on-light btn-block mt-2"><i class="fa-brands fa-whatsapp"></i> <?= e($w('vehicle_whatsapp_button')) ?></a>
    </aside>
  </div>
</section>

<?php
$vehicleUrl = base_url('fleet/' . $car['slug']);
$vehicleName = $car['name'];
$vehicleImage = !empty($car['image_path']) ? car_image_url($car['image_path']) : null;
$vehicleSchema = [
  '@context' => 'https://schema.org',
  '@type' => 'Product',
  '@id' => $vehicleUrl . '#product',
  'name' => $vehicleName,
  'description' => strip_tags((string)($car['description'] ?: $seo['description'])),
  'url' => $vehicleUrl,
  'image' => $vehicleImage,
  'brand' => ['@type'=>'Brand','name'=>setting('general','site_name')],
  'category' => $car['category_name'] ?? null,
  'offers' => [
    '@type' => 'Offer',
    'url' => $vehicleUrl,
    'priceCurrency' => setting('general','currency','KES'),
    'price' => (string)$car['price_per_day'],
    'availability' => $car['status'] === 'available' ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
    'itemCondition' => 'https://schema.org/UsedCondition',
  ],
  'additionalProperty' => array_values(array_filter([
    ['@type'=>'PropertyValue','name'=>'Seats','value'=>(string)$car['seats']],
    ['@type'=>'PropertyValue','name'=>'Transmission','value'=>(string)$car['transmission']],
    ['@type'=>'PropertyValue','name'=>'Fuel type','value'=>(string)$car['fuel_type']],
    ['@type'=>'PropertyValue','name'=>'Doors','value'=>(string)$car['doors']],
    !empty($car['year']) ? ['@type'=>'PropertyValue','name'=>'Year','value'=>(string)$car['year']] : null,
  ])),
];
if (!$vehicleImage) unset($vehicleSchema['image']);
if (!$vehicleSchema['category']) unset($vehicleSchema['category']);
?>
<script type="application/ld+json"><?= json_encode($vehicleSchema, JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?></script>

<script type="application/ld+json">
<?= json_encode([
  '@context'=>'https://schema.org',
  '@type'=>'BreadcrumbList',
  'itemListElement'=>[
    ['@type'=>'ListItem','position'=>1,'name'=>$w('nav_home_label','Home'),'item'=>base_url('')],
    ['@type'=>'ListItem','position'=>2,'name'=>$w('nav_fleet_label','Fleet'),'item'=>base_url('fleet')],
    ['@type'=>'ListItem','position'=>3,'name'=>$car['name'],'item'=>$vehicleUrl],
  ]
], JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE) ?>
</script>
<?php view('layouts/footer'); ?>
