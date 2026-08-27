<?php view('layouts/header', ['seo' => $seo]); ?>

<div class="page-header">
  <div class="container">
    <h1>Contact Us</h1>
    <div class="breadcrumb"><a href="<?= base_url('/') ?>">Home</a> / Contact</div>
  </div>
</div>

<section>
  <div class="container contact-grid">
    <div class="contact-info-card">
      <h2 style="text-transform:none;font-family:inherit;font-size:1.3rem;margin-bottom:20px;">Get in Touch</h2>
      <div class="contact-info-item">
        <i class="fa-solid fa-location-dot"></i>
        <div><strong>Our Office</strong><p style="opacity:0.85;"><?= e(setting('general', 'address')) ?></p></div>
      </div>
      <div class="contact-info-item">
        <i class="fa-solid fa-phone"></i>
        <div><strong>Call Us</strong><p style="opacity:0.85;"><?= e(setting('general', 'phone_primary')) ?><br><?= e(setting('general', 'phone_secondary')) ?></p></div>
      </div>
      <div class="contact-info-item">
        <i class="fa-solid fa-envelope"></i>
        <div><strong>Email Us</strong><p style="opacity:0.85;"><?= e(setting('general', 'email')) ?></p></div>
      </div>
      <div class="contact-info-item">
        <i class="fa-solid fa-clock"></i>
        <div><strong>Working Hours</strong><p style="opacity:0.85;"><?= e(setting('general', 'working_hours')) ?></p></div>
      </div>
      <?php if ($embed = setting('general', 'google_maps_embed')): ?>
        <div class="map-embed"><?= $embed /* trusted admin-controlled embed code */ ?></div>
      <?php endif; ?>
    </div>

    <div class="contact-form-card">
      <h2 style="text-transform:none;font-family:inherit;font-size:1.3rem;margin-bottom:20px;color:var(--color-primary-900);">Send a Message</h2>
      <form action="<?= base_url('contact') ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-row">
          <div class="form-group">
            <label for="name">Full Name</label>
            <input type="text" id="name" name="name" required>
          </div>
          <div class="form-group">
            <label for="phone">Phone (optional)</label>
            <input type="tel" id="phone" name="phone">
          </div>
        </div>
        <div class="form-group">
          <label for="email">Email Address</label>
          <input type="email" id="email" name="email" required>
        </div>
        <div class="form-group">
          <label for="subject">Subject</label>
          <input type="text" id="subject" name="subject" placeholder="e.g. Booking enquiry">
        </div>
        <div class="form-group">
          <label for="message">Message</label>
          <textarea id="message" name="message" required></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-block"><i class="fa-solid fa-paper-plane"></i> Send Message</button>
      </form>
    </div>
  </div>
</section>

<?php view('layouts/footer'); ?>
