<?php view('layouts/header', ['seo' => $seo]); ?>
<?php $w = static fn(string $key, string $default = ''): string => setting('website', $key, $default); ?>

<div class="page-header">
  <div class="container">
    <h1><?= e($w('about_page_title', 'About Us')) ?></h1>
    <div class="breadcrumb"><a href="<?= base_url('/') ?>"><?= e($w('nav_home_label', 'Home')) ?></a> / <?= e($w('nav_about_label', 'About')) ?></div>
  </div>
</div>

<section>
  <div class="container" style="max-width:820px;">
    <p style="color:var(--color-text-soft);margin-bottom:20px;font-size:1.05rem;"><?= nl2br(e($w('about_intro_1'))) ?></p>
    <p style="color:var(--color-text-soft);margin-bottom:40px;font-size:1.05rem;"><?= nl2br(e($w('about_intro_2'))) ?></p>

    <div class="steps" style="margin-bottom:20px;">
      <div class="step">
        <div class="step-icon" style="background:rgba(255, 107, 74,0.15);color:var(--color-accent-600);"><i class="fa-solid fa-shield-halved"></i></div>
        <h3 style="color:var(--color-primary-900);"><?= e($w('about_value_1_title')) ?></h3>
        <p style="color:var(--color-text-muted);"><?= e($w('about_value_1_text')) ?></p>
      </div>
      <div class="step">
        <div class="step-icon" style="background:rgba(255, 107, 74,0.15);color:var(--color-accent-600);"><i class="fa-solid fa-headset"></i></div>
        <h3 style="color:var(--color-primary-900);"><?= e($w('about_value_2_title')) ?></h3>
        <p style="color:var(--color-text-muted);"><?= e($w('about_value_2_text')) ?></p>
      </div>
      <div class="step">
        <div class="step-icon" style="background:rgba(255, 107, 74,0.15);color:var(--color-accent-600);"><i class="fa-solid fa-tags"></i></div>
        <h3 style="color:var(--color-primary-900);"><?= e($w('about_value_3_title')) ?></h3>
        <p style="color:var(--color-text-muted);"><?= e($w('about_value_3_text')) ?></p>
      </div>
    </div>
  </div>
</section>

<section class="cta-band">
  <div class="container">
    <h2><?= e($w('about_cta_title')) ?></h2>
    <p><?= e($w('about_cta_text')) ?></p>
    <a href="<?= base_url('fleet') ?>" class="btn btn-dark"><?= e($w('about_cta_button_label', 'View Fleet')) ?> <i class="fa-solid fa-arrow-right"></i></a>
  </div>
</section>

<?php view('layouts/footer'); ?>
