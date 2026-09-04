</main>
<?php $w = static fn(string $key, string $default = ''): string => setting('website', $key, $default); ?>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="<?= base_url('/') ?>" class="brand">
          <span class="brand-mark"><i class="fa-solid fa-water"></i></span>
          <span class="brand-text" style="color:var(--color-white)"><?= e($w('footer_brand_name', setting('general', 'site_name'))) ?></span>
        </a>
        <p><?= e(setting('general', 'tagline')) ?></p>
        <div class="footer-social">
          <?php if ($u = setting('general', 'facebook_url')): ?><a href="<?= e($u) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a><?php endif; ?>
          <?php if ($u = setting('general', 'instagram_url')): ?><a href="<?= e($u) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a><?php endif; ?>
          <?php if ($u = setting('general', 'whatsapp_number')): ?><a href="https://wa.me/<?= e($u) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a><?php endif; ?>
        </div>
      </div>
      <div>
        <h4><?= e($w('footer_quick_links_title', 'Quick Links')) ?></h4>
        <ul>
          <li><a href="<?= base_url('/') ?>"><?= e($w('nav_home_label', 'Home')) ?></a></li>
          <li><a href="<?= base_url('fleet') ?>"><?= e($w('nav_fleet_label', 'Fleet')) ?></a></li>
          <li><a href="<?= base_url('about') ?>"><?= e($w('nav_about_label', 'About')) ?></a></li>
          <li><a href="<?= base_url('book') ?>"><?= e($w('hero_book_label', 'Book Your Car')) ?></a></li>
        </ul>
      </div>
      <div>
        <h4><?= e($w('footer_support_title', 'Support')) ?></h4>
        <ul>
          <li><a href="<?= base_url('contact') ?>">Contact Us</a></li>
          <li><a href="<?= base_url('privacy') ?>">Privacy Policy</a></li>
          <li><a href="<?= base_url('terms') ?>">Terms of Service</a></li>
          <li><a href="<?= base_url('fleet?category=economy') ?>">Economy Cars</a></li>
          <li><a href="<?= base_url('fleet?category=suv') ?>">SUVs</a></li>
          <li><a href="<?= base_url('fleet?category=luxury') ?>">Luxury Cars</a></li>
        </ul>
      </div>
      <div>
        <h4><?= e($w('footer_contact_title', 'Get In Touch')) ?></h4>
        <ul>
          <li><i class="fa-solid fa-location-dot"></i> <?= e(setting('general', 'address')) ?></li>
          <li><i class="fa-solid fa-phone"></i> <?= e(setting('general', 'phone_primary')) ?></li>
          <li><i class="fa-solid fa-envelope"></i> <?= e(setting('general', 'email')) ?></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      &copy; <?= date('Y') ?> <?= e(setting('general', 'site_name')) ?>. All rights reserved. <?= e($w('footer_credit', '')) ?>
    </div>
  </div>
</footer>

<script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
