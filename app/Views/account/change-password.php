<?php view('layouts/header', ['seo' => $seo]); ?>

<div class="page-header">
  <div class="container">
    <h1>Change Password</h1>
    <div class="breadcrumb"><a href="<?= base_url('/') ?>">Home</a> / <a href="<?= base_url('account/dashboard') ?>">My Account</a> / Change Password</div>
  </div>
</div>

<section>
  <div class="container" style="max-width:440px;">
    <div class="contact-form-card">
      <form action="<?= base_url('account/change-password') ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="current_password">Current Password</label>
          <input type="password" id="current_password" name="current_password" required>
        </div>
        <div class="form-group">
          <label for="new_password">New Password</label>
          <input type="password" id="new_password" name="new_password" minlength="6" required>
        </div>
        <div class="form-group">
          <label for="confirm_password">Confirm New Password</label>
          <input type="password" id="confirm_password" name="confirm_password" minlength="6" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-key"></i> Update Password</button>
      </form>
      <p style="text-align:center;margin-top:16px;">
        <a href="<?= base_url('account/dashboard') ?>" style="color:var(--color-primary-900);text-decoration:underline;font-size:0.85rem;">&larr; Back to My Bookings</a>
      </p>
    </div>
  </div>
</section>

<?php view('layouts/footer'); ?>
