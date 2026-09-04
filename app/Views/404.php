<?php view('layouts/header', ['seo' => ['title' => '404 Not Found | ' . setting('general', 'site_name'), 'description' => setting('website','error_404_text'), 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow']]); ?>
<?php $w = static fn(string $key, string $default = ''): string => setting('website', $key, $default); ?>

<section>
  <div class="container text-center" style="padding:80px 20px;">
    <h1 style="font-size:5rem;color:var(--color-accent-500);">404</h1>
    <h2 style="color:var(--color-primary-900);margin-bottom:16px;"><?= e($w('error_404_title')) ?></h2>
    <p style="color:var(--color-text-muted);margin-bottom:30px;"><?= e($w('error_404_text')) ?></p>
    <a href="<?= base_url('/') ?>" class="btn btn-primary"><?= e($w('back_home_label')) ?></a>
  </div>
</section>

<?php view('layouts/footer'); ?>
