<?php view('layouts/header', ['seo' => $seo]); ?>

<div class="page-header">
  <div class="container">
    <h1>My Account</h1>
    <div class="breadcrumb"><a href="<?= base_url('/') ?>">Home</a> / My Account</div>
  </div>
</div>

<section class="auth-section">
  <div class="container auth-container" style="max-width:440px;">
    <div class="contact-form-card auth-card">
      <h2 style="text-transform:none;font-family:inherit;font-size:1.2rem;color:var(--color-primary-900);margin-bottom:6px;">Log In</h2>
      <p style="color:var(--color-text-faint);font-size:0.85rem;margin-bottom:20px;">
        Use the phone number you booked with, and the password we sent you by SMS/email.
      </p>
      <form action="<?= base_url('account/login') ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="phone">Phone Number</label>
          <input type="tel" id="phone" name="phone" placeholder="07XX XXX XXX" required autofocus>
        </div>
        <div class="form-group">
          <label for="password">Password</label>
          <input type="password" id="password" name="password" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-right-to-bracket"></i> Log In</button>
      </form>
      <p style="color:var(--color-text-faint);font-size:0.85rem;margin-top:18px;text-align:center;">
        Don't have an account yet? <a href="<?= base_url('book') ?>" style="color:var(--color-primary-900);text-decoration:underline;">Book a car</a> and we'll create one for you automatically.
      </p>
    </div>
  </div>
</section>

<?php view('layouts/footer'); ?>
