<?php view('layouts/header', ['seo' => $seo]); ?>

<div class="page-header">
  <div class="container">
    <h1>My Bookings</h1>
    <div class="breadcrumb"><a href="<?= base_url('/') ?>">Home</a> / My Account</div>
  </div>
</div>

<section>
  <div class="container">
    <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:14px;margin-bottom:30px;">
      <div>
        <h2 style="text-transform:none;font-family:inherit;font-size:1.2rem;color:var(--color-primary-900);">
          Welcome back, <?= e($customer['first_name']) ?>
        </h2>
        <p style="color:var(--color-text-faint);font-size:0.9rem;"><?= e($customer['phone']) ?><?= $customer['email'] ? ' &middot; ' . e($customer['email']) : '' ?></p>
      </div>
      <div style="display:flex;gap:10px;">
        <a href="<?= base_url('account/change-password') ?>" class="btn btn-outline" style="color:var(--color-primary-900);border-color:var(--color-primary-900);"><i class="fa-solid fa-key"></i> Change Password</a>
        <a href="<?= base_url('account/logout') ?>" class="btn btn-dark"><i class="fa-solid fa-right-from-bracket"></i> Log Out</a>
      </div>
    </div>

    <?php if (empty($bookings)): ?>
      <div class="empty-state">
        <i class="fa-solid fa-calendar-xmark"></i>
        <p>You don't have any bookings yet.</p>
        <a href="<?= base_url('fleet') ?>" class="btn btn-primary mt-2">Browse Our Fleet</a>
      </div>
    <?php else: ?>
      <div class="car-grid">
        <?php foreach ($bookings as $b): ?>
          <div class="car-card">
            <div class="car-card-img">
              <?php if (!empty($b['image_path'])): ?>
                <img src="<?= e(car_image_url($b['image_path'])) ?>" alt="<?= e($b['car_name']) ?>" loading="lazy">
              <?php else: ?>
                <div style="display:flex;align-items:center;justify-content:center;height:100%;color:var(--color-text-faint);"><i class="fa-solid fa-car" style="font-size:2.5rem;"></i></div>
              <?php endif; ?>
              <span class="car-card-badge badge-<?= e($b['status']) ?>" style="background:var(--color-primary-900);color:var(--color-white);"><?= e(ucfirst($b['status'])) ?></span>
            </div>
            <div class="car-card-body">
              <p class="car-card-cat"><?= e($b['booking_ref']) ?></p>
              <h3><?= e($b['car_name']) ?></h3>
              <div class="car-specs">
                <span><i class="fa-solid fa-calendar-day"></i> <?= e(date('d M Y', strtotime($b['pickup_date']))) ?></span>
                <span><i class="fa-solid fa-arrow-right"></i></span>
                <span><i class="fa-solid fa-calendar-day"></i> <?= e(date('d M Y', strtotime($b['return_date']))) ?></span>
              </div>
              <div class="car-card-footer">
                <div class="car-price"><small>Total</small><?= money($b['total_price']) ?></div>
                <a href="<?= base_url('account/bookings/' . (int) $b['id']) ?>" class="btn btn-dark btn-sm">View Booking</a>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>

<?php view('layouts/footer'); ?>
