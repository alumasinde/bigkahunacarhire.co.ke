<?php view('layouts/header', ['seo' => ['title' => '404 Not Found | ' . setting('general', 'site_name'), 'description' => 'Page not found.', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow']]); ?>

<section>
  <div class="container text-center" style="padding:80px 20px;">
    <h1 style="font-size:5rem;color:var(--color-accent-500);">404</h1>
    <h2 style="color:var(--color-primary-900);margin-bottom:16px;">Looks like this road doesn't exist</h2>
    <p style="color:var(--color-text-muted);margin-bottom:30px;">The page you're looking for may have moved or never existed.</p>
    <a href="<?= base_url('/') ?>" class="btn btn-primary">Back to Home</a>
  </div>
</section>

<?php view('layouts/footer'); ?>
