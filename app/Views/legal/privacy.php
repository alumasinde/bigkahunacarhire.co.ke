<?php view('layouts/header', ['seo' => $seo]); ?>

<div class="page-header">
  <div class="container">
    <h1><?= e($title) ?></h1>
    <div class="breadcrumb"><a href="<?= base_url('/') ?>">Home</a> / <?= e($title) ?></div>
  </div>
</div>

<section class="legal-page">
  <div class="container">
    <article class="contact-form-card legal-document">
      <div class="legal-document__meta">
        <span><i class="fa-regular fa-calendar"></i> Last updated: <?= e($lastUpdated ?: 'Not specified') ?></span>
      </div>

      <div class="legal-document__content"><?= nl2br(e($content)) ?></div>

      <div class="legal-document__contact">
        <h2>Questions about this policy?</h2>
        <p>Contact <?= e($siteName) ?> using the details below.</p>
        <?php if ($email): ?><a href="mailto:<?= e($email) ?>"><?= e($email) ?></a><?php endif; ?>
        <?php if ($phone): ?><a href="tel:<?= e(preg_replace('/\s+/', '', $phone)) ?>"><?= e($phone) ?></a><?php endif; ?>
        <?php if ($address): ?><span><?= e($address) ?></span><?php endif; ?>
      </div>
    </article>
  </div>
</section>

<?php view('layouts/footer'); ?>
