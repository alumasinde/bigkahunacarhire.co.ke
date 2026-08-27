<?php view('layouts/header', ['seo' => $seo]); ?>

<div class="page-header">
  <div class="container">
    <h1>About Big Kahuna Car Hire</h1>
    <div class="breadcrumb"><a href="<?= base_url('/') ?>">Home</a> / About</div>
  </div>
</div>

<section>
  <div class="container" style="max-width:820px;">
    <p style="color:var(--color-text-soft);margin-bottom:20px;font-size:1.05rem;">
      <?= e(setting('general', 'site_name')) ?> is a Kenyan-owned car rental company built on one idea: renting a car
      should feel as easy as catching a good wave. Whether you need a compact hatchback for the city, a rugged 4x4
      for a Maasai Mara safari, or a chauffeur-driven sedan for a corporate event, we match you with the right ride
      at a fair price.
    </p>
    <p style="color:var(--color-text-soft);margin-bottom:40px;font-size:1.05rem;">
      Every vehicle in our fleet is regularly serviced, fully insured, and inspected before handover. Our team is
      on call around the clock, so help is never far away — whether that's a question before you book or support
      while you're on the road.
    </p>

    <div class="steps" style="margin-bottom:20px;">
      <div class="step">
        <div class="step-icon" style="background:rgba(255, 107, 74,0.15);color:var(--color-accent-600);"><i class="fa-solid fa-shield-halved"></i></div>
        <h3 style="color:var(--color-primary-900);">Fully Insured</h3>
        <p style="color:var(--color-text-muted);">Every rental is covered, so you can drive with peace of mind.</p>
      </div>
      <div class="step">
        <div class="step-icon" style="background:rgba(255, 107, 74,0.15);color:var(--color-accent-600);"><i class="fa-solid fa-headset"></i></div>
        <h3 style="color:var(--color-primary-900);">24/7 Support</h3>
        <p style="color:var(--color-text-muted);">Call or WhatsApp anytime — our team is always reachable.</p>
      </div>
      <div class="step">
        <div class="step-icon" style="background:rgba(255, 107, 74,0.15);color:var(--color-accent-600);"><i class="fa-solid fa-tags"></i></div>
        <h3 style="color:var(--color-primary-900);">Transparent Pricing</h3>
        <p style="color:var(--color-text-muted);">No hidden fees — the price you see is the price you pay.</p>
      </div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container">
    <h2>Let's get you on the road</h2>
    <p>Browse our fleet or reach out and we'll help you find the perfect car for your trip.</p>
    <a href="<?= base_url('fleet') ?>" class="btn btn-dark">View Fleet <i class="fa-solid fa-arrow-right"></i></a>
  </div>
</section>

<?php view('layouts/footer'); ?>
