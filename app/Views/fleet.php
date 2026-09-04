<?php view('layouts/header', ['seo' => $seo]); ?>
<?php $w = static fn(string $key, string $default = ''): string => setting('website', $key, $default); ?>

<div class="bk-page-hero">
  <div class="container">
    <h1><?= e($w('fleet_title')) ?></h1>
    <p><?= e($w('fleet_intro')) ?></p>
    <div class="breadcrumb"><a href="<?= base_url('/') ?>">Home</a> / Fleet</div>
  </div>
</div>

<section style="padding-top:0">
  <div class="container">
    <form action="<?= base_url('fleet') ?>" method="get" class="bk-fleet-filter">
      <div class="form-group"><label for="f-category">Category</label><select id="f-category" name="category"><option value="">All categories</option><?php foreach ($categories as $cat): ?><option value="<?= e($cat['slug']) ?>" <?= $filters['category'] === $cat['slug'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option><?php endforeach; ?></select></div>
      <div class="form-group"><label for="f-transmission">Transmission</label><select id="f-transmission" name="transmission"><option value="">Any</option><option value="automatic" <?= $filters['transmission'] === 'automatic' ? 'selected' : '' ?>>Automatic</option><option value="manual" <?= $filters['transmission'] === 'manual' ? 'selected' : '' ?>>Manual</option></select></div>
      <div class="form-group"><label for="f-seats">Minimum seats</label><select id="f-seats" name="seats"><option value="">Any</option><?php foreach ([4,5,7,14] as $s): ?><option value="<?= $s ?>" <?= $filters['seats'] == $s ? 'selected' : '' ?>><?= $s ?>+</option><?php endforeach; ?></select></div>
      <div class="form-group"><label for="f-price">Maximum price / day</label><select id="f-price" name="max_price"><option value="">Any</option><?php foreach ([5000,10000,15000,20000] as $p): ?><option value="<?= $p ?>" <?= $filters['max_price'] == $p ? 'selected' : '' ?>>KES <?= number_format($p) ?></option><?php endforeach; ?></select></div>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-filter"></i> <?= e($w('fleet_filter_button')) ?></button>
    </form>

    <?php if (empty($cars)): ?>
      <div class="empty-state"><i class="fa-solid fa-car-side"></i><p><?= e($w('fleet_empty_message')) ?></p><a href="<?= base_url('fleet') ?>" class="btn btn-dark mt-2"><?= e($w('fleet_clear_filters_label')) ?></a></div>
    <?php else: ?>
      <div class="bk-fleet-grid" style="margin-top:42px">
        <?php foreach ($cars as $car): ?>
          <article class="bk-car-card">
            <a href="<?= base_url('fleet/' . e($car['slug'])) ?>" class="bk-car-media">
              <?php if (!empty($car['image_path'])): ?><img src="<?= e(car_image_url($car['image_path'])) ?>" alt="<?= e($car['name']) ?> car hire in Kenya" width="800" height="500" loading="lazy" decoding="async"><?php else: ?><div class="car-card-placeholder"><i class="fa-solid fa-car"></i></div><?php endif; ?>
              <?php if (!empty($car['featured'])): ?><span class="bk-car-badge">Featured</span><?php endif; ?>
            </a>
            <div class="bk-car-body">
              <p class="bk-car-cat"><?= e($car['category_name'] ?? '') ?></p>
              <h3><?= e($car['name']) ?></h3>
              <div class="bk-car-specs"><span><i class="fa-solid fa-users"></i><?= (int) $car['seats'] ?> seats</span><span><i class="fa-solid fa-gears"></i><?= e(ucfirst($car['transmission'])) ?></span><span><i class="fa-solid fa-location-dot"></i><?= e($car['location']) ?></span></div>
              <div class="bk-car-footer"><div class="bk-car-price"><small>From</small><strong><?= money($car['price_per_day']) ?></strong> <span>/ day</span></div><a href="<?= base_url('fleet/' . e($car['slug'])) ?>" class="btn btn-dark btn-sm">View Car</a></div>
            </div>
          </article>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php view('layouts/footer'); ?>
