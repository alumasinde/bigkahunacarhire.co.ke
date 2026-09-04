<?php view('layouts/header', ['seo' => ['title' => 'Server Error | ' . setting('general', 'site_name'), 'description' => setting('website','error_500_text'), 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow']]); ?>
<?php $w = static fn(string $key, string $default = ''): string => setting('website', $key, $default); ?>

<section>
  <div class="container text-center" style="padding:80px 20px;">
    <h1 style="font-size:5rem;color:var(--color-accent-500);">500</h1>
    <h2 style="color:var(--color-primary-900);margin-bottom:16px;"><?= e($w('error_500_title')) ?></h2>
    <p style="color:var(--color-text-muted);margin-bottom:30px;"><?= e($w('error_500_text')) ?></p>
    <a href="<?= base_url('/') ?>" class="btn btn-primary"><?= e($w('back_home_label')) ?></a>
  </div>
</section>

<?php view('layouts/footer'); ?>
