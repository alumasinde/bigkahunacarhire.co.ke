<?php view('layouts/header', ['seo' => ['title' => 'Server Error | ' . setting('general', 'site_name'), 'description' => 'Something went wrong.', 'keywords' => '', 'og_image' => '', 'robots' => 'noindex, nofollow']]); ?>

<section>
  <div class="container text-center" style="padding:80px 20px;">
    <h1 style="font-size:5rem;color:var(--color-accent-500);">500</h1>
    <h2 style="color:var(--color-primary-900);margin-bottom:16px;">Something went wrong on our end</h2>
    <p style="color:var(--color-text-muted);margin-bottom:30px;">Please try again shortly, or contact us if the problem continues.</p>
    <a href="<?= base_url('/') ?>" class="btn btn-primary">Back to Home</a>
  </div>
</section>

<?php view('layouts/footer'); ?>
