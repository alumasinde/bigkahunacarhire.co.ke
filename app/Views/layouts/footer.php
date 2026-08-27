</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <div class="footer-brand">
        <a href="<?= base_url('/') ?>" class="brand">
          <span class="brand-mark"><i class="fa-solid fa-water"></i></span>
          <span class="brand-text" style="color:var(--color-white)">BIG <span>KAHUNA</span></span>
        </a>
        <p><?= e(setting('general', 'tagline')) ?></p>
        <div class="footer-social">
          <?php if ($u = setting('general', 'facebook_url')): ?><a href="<?= e($u) ?>" target="_blank" rel="noopener" aria-label="Facebook"><i class="fa-brands fa-facebook"></i></a><?php endif; ?>
          <?php if ($u = setting('general', 'instagram_url')): ?><a href="<?= e($u) ?>" target="_blank" rel="noopener" aria-label="Instagram"><i class="fa-brands fa-instagram"></i></a><?php endif; ?>
          <?php if ($u = setting('general', 'whatsapp_number')): ?><a href="https://wa.me/<?= e($u) ?>" target="_blank" rel="noopener" aria-label="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a><?php endif; ?>
        </div>
      </div>
      <div>
        <h4>Quick Links</h4>
        <ul>
          <li><a href="<?= base_url('/') ?>">Home</a></li>
          <li><a href="<?= base_url('fleet') ?>">Our Fleet</a></li>
          <li><a href="<?= base_url('about') ?>">About Us</a></li>
          <li><a href="<?= base_url('book') ?>">Book a Car</a></li>
        </ul>
      </div>
      <div>
        <h4>Support</h4>
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
        <h4>Get In Touch</h4>
        <ul>
          <li><i class="fa-solid fa-location-dot"></i> <?= e(setting('general', 'address')) ?></li>
          <li><i class="fa-solid fa-phone"></i> <?= e(setting('general', 'phone_primary')) ?></li>
          <li><i class="fa-solid fa-envelope"></i> <?= e(setting('general', 'email')) ?></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      &copy; <?= date('Y') ?> <?= e(setting('general', 'site_name')) ?>. All rights reserved. Built by AlbaTech Solutions.
    </div>
  </div>
</footer>

<script src="<?= asset('js/main.js') ?>"></script>
</body>
</html>
