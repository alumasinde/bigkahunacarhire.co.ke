<?php view('layouts/header', ['seo' => $seo]); ?>

<?php
$heroCar = !empty($featuredCars) ? $featuredCars[0] : null;
$heroImage = $heroCar && !empty($heroCar['image_path']) ? base_url($heroCar['image_path']) : null;
$icons = ['economy' => 'fa-car-side', 'suv' => 'fa-truck-monster', 'luxury' => 'fa-gem', 'van-minibus' => 'fa-van-shuttle'];
?>

<section class="bk-hero">
  <?php if ($heroImage): ?>
    <div class="bk-hero-bg" aria-hidden="true"><img src="<?= e($heroImage) ?>" alt=""></div>
  <?php endif; ?>
  <div class="container">
    <div class="bk-hero-grid">
      <div class="bk-hero-copy">
        <span class="bk-hero-eyebrow">CAR HIRE IN KENYA</span>
        <h1>Drive your journey.<br><em>Explore Kenya.</em></h1>
        <p class="bk-hero-lead"><?= e(setting('general', 'tagline')) ?> Self-drive or chauffeur-driven, from everyday city travel to long-distance adventures.</p>
        <div class="bk-hero-actions">
          <a href="<?= base_url('book') ?>" class="btn btn-primary"><i class="fa-solid fa-calendar-check"></i> Book Your Car</a>
          <a href="<?= base_url('fleet') ?>" class="btn btn-outline"><i class="fa-solid fa-car"></i> Explore Fleet</a>
        </div>
        <div class="bk-hero-proof">
          <span><i class="fa-solid fa-circle-check"></i> Well-maintained vehicles</span>
          <span><i class="fa-solid fa-circle-check"></i> Transparent rates</span>
          <span><i class="fa-solid fa-circle-check"></i> <?= e(setting('general', 'support_hours', 'Daily')) ?> support</span>
        </div>
      </div>

      <div class="bk-hero-search">
        <div class="bk-hero-search-head">
          <div>
            <span>Start your trip</span>
            <h2>Find your ride</h2>
          </div>
          <i class="fa-solid fa-car-side" style="color:var(--bk-gold-dark);font-size:1.3rem"></i>
        </div>
        <form action="<?= base_url('fleet') ?>" method="get" class="bk-search-form">
          <div class="form-group">
            <label for="qs-category">Vehicle category</label>
            <select id="qs-category" name="category">
              <option value="">Any category</option>
              <?php foreach ($categories as $cat): ?>
                <option value="<?= e($cat['slug']) ?>"><?= e($cat['name']) ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group">
            <label for="qs-transmission">Transmission</label>
            <select id="qs-transmission" name="transmission">
              <option value="">Any transmission</option>
              <option value="automatic">Automatic</option>
              <option value="manual">Manual</option>
            </select>
          </div>
          <div class="form-group">
            <label for="qs-seats">Minimum seats</label>
            <select id="qs-seats" name="seats">
              <option value="">Any number</option>
              <option value="4">4+</option>
              <option value="5">5+</option>
              <option value="7">7+</option>
            </select>
          </div>
          <div class="form-group">
            <label for="qs-price">Maximum price / day</label>
            <select id="qs-price" name="max_price">
              <option value="">Any price</option>
              <option value="5000">KES 5,000</option>
              <option value="10000">KES 10,000</option>
              <option value="20000">KES 20,000</option>
            </select>
          </div>
          <button type="submit" class="btn btn-primary bk-search-submit"><i class="fa-solid fa-magnifying-glass"></i> Search Cars</button>
        </form>
        <p class="bk-search-note">Use the filters to narrow down the live fleet.</p>
      </div>
    </div>
  </div>
</section>

<section class="bk-trust-strip" aria-label="Why book with Big Kahuna">
  <div class="container">
    <div class="bk-trust-grid">
      <div class="bk-trust-item"><span class="bk-trust-icon"><i class="fa-solid fa-shield-halved"></i></span><div><strong>Well-maintained cars</strong><span>Ready for your next trip</span></div></div>
      <div class="bk-trust-item"><span class="bk-trust-icon"><i class="fa-solid fa-tag"></i></span><div><strong>Competitive rates</strong><span>Clear daily pricing</span></div></div>
      <div class="bk-trust-item"><span class="bk-trust-icon"><i class="fa-solid fa-location-dot"></i></span><div><strong>Kenya-wide service</strong><span>Pickup options across Kenya</span></div></div>
      <div class="bk-trust-item"><span class="bk-trust-icon"><i class="fa-solid fa-headset"></i></span><div><strong><?= e(setting('general', 'support_hours', 'Daily')) ?> support</strong><span>Help when you need it</span></div></div>
    </div>
  </div>
</section>

<section class="bk-section">
  <div class="container">
    <div class="bk-section-head center">
      <span class="section-eyebrow">BROWSE BY TYPE</span>
      <h2>Choose the right vehicle for your trip</h2>
      <p>From efficient city cars to spacious SUVs and people movers, browse the categories already available in your fleet.</p>
    </div>
    <div class="bk-category-grid">
      <?php foreach ($categories as $cat): ?>
        <a href="<?= base_url('fleet?category=' . e($cat['slug'])) ?>" class="bk-category-card">
          <div class="bk-category-icon"><i class="fa-solid <?= $icons[$cat['slug']] ?? 'fa-car' ?>"></i></div>
          <h3><?= e($cat['name']) ?></h3>
          <?php if (!empty($cat['description'])): ?><p><?= e($cat['description']) ?></p><?php else: ?><p>Browse available <?= e(strtolower($cat['name'])) ?> vehicles.</p><?php endif; ?>
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="bk-section" style="background:var(--bk-cream)">
  <div class="container">
    <div class="bk-section-head">
      <span class="section-eyebrow">FEATURED FLEET</span>
      <h2>Popular cars, presented better</h2>
      <p>Your existing live fleet data, with a cleaner premium browsing experience.</p>
    </div>
    <div class="bk-fleet-grid">
      <?php foreach ($featuredCars as $car): ?>
        <article class="bk-car-card">
          <a href="<?= base_url('fleet/' . e($car['slug'])) ?>" class="bk-car-media">
            <?php if (!empty($car['image_path'])): ?>
              <img src="<?= e(car_image_url($car['image_path'])) ?>" alt="<?= e($car['name']) ?> car hire in Kenya" width="800" height="500" loading="lazy" decoding="async">
            <?php else: ?>
              <div class="car-card-placeholder"><i class="fa-solid fa-car"></i></div>
            <?php endif; ?>
            <span class="bk-car-badge">Featured</span>
          </a>
          <div class="bk-car-body">
            <p class="bk-car-cat"><?= e($car['category_name'] ?? '') ?></p>
            <h3><?= e($car['name']) ?></h3>
            <div class="bk-car-specs">
              <span><i class="fa-solid fa-users"></i><?= (int) $car['seats'] ?> seats</span>
              <span><i class="fa-solid fa-gears"></i><?= e(ucfirst($car['transmission'])) ?></span>
              <span><i class="fa-solid fa-gas-pump"></i><?= e(ucfirst($car['fuel_type'])) ?></span>
            </div>
            <div class="bk-car-footer">
              <div class="bk-car-price"><small>From</small><strong><?= money($car['price_per_day']) ?></strong> <span>/ day</span></div>
              <a href="<?= base_url('fleet/' . e($car['slug'])) ?>" class="btn btn-dark btn-sm">View Car</a>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
    <div class="text-center mt-2"><a href="<?= base_url('fleet') ?>" class="btn btn-primary">View Full Fleet <i class="fa-solid fa-arrow-right"></i></a></div>
  </div>
</section>

<section class="bk-section bk-section-dark">
  <div class="container">
    <div class="bk-section-head center">
      <span class="section-eyebrow">OUR SERVICES</span>
      <h2>More than just a rental car</h2>
      <p>Present the services you already offer in a clearer, more premium way.</p>
    </div>
    <div class="bk-service-grid">
      <div class="bk-service-card"><div class="bk-service-icon"><i class="fa-solid fa-car"></i></div><h3>Self-drive</h3><p>Choose your vehicle and enjoy the freedom to travel on your own schedule.</p></div>
      <div class="bk-service-card"><div class="bk-service-icon"><i class="fa-solid fa-user-tie"></i></div><h3>Chauffeur drive</h3><p>Travel comfortably with a professional driver when you prefer a hands-free journey.</p></div>
      <div class="bk-service-card"><div class="bk-service-icon"><i class="fa-solid fa-plane-arrival"></i></div><h3>Airport transfers</h3><p>Make airport arrivals and departures simple with convenient pickup arrangements.</p></div>
      <div class="bk-service-card"><div class="bk-service-icon"><i class="fa-solid fa-building"></i></div><h3>Corporate travel</h3><p>Practical vehicle options for meetings, projects, teams and business travel.</p></div>
    </div>
  </div>
</section>

<?php if ($heroImage): ?>
<section class="bk-section">
  <div class="container bk-why">
    <div class="bk-why-panel">
      <div class="bk-section-head">
        <span class="section-eyebrow">THE BIG KAHUNA DIFFERENCE</span>
        <h2>A better rental experience from search to handover.</h2>
        <p>The interface is new, but the information underneath remains powered by your existing dynamic rental platform.</p>
      </div>
      <div class="bk-why-list">
        <div class="bk-why-item"><span class="bk-why-check"><i class="fa-solid fa-check"></i></span><div><strong>Easy online discovery</strong><span>Find vehicles by the criteria that matter to you.</span></div></div>
        <div class="bk-why-item"><span class="bk-why-check"><i class="fa-solid fa-check"></i></span><div><strong>Clear vehicle information</strong><span>See pricing and essential specifications before booking.</span></div></div>
        <div class="bk-why-item"><span class="bk-why-check"><i class="fa-solid fa-check"></i></span><div><strong>Flexible rental journey</strong><span>Move naturally from vehicle selection into your existing booking flow.</span></div></div>
        <div class="bk-why-item"><span class="bk-why-check"><i class="fa-solid fa-check"></i></span><div><strong>Support when you need it</strong><span><?= e(setting('general', 'support_hours', 'Daily')) ?> customer support through your configured channels.</span></div></div>
      </div>
    </div>
    <div class="bk-why-visual">
      <img src="<?= e($heroImage) ?>" alt="<?= e($heroCar['name'] ?? 'Big Kahuna vehicle') ?>" loading="lazy" decoding="async">
      <div class="bk-why-stat"><strong><?= (int) $stats['car_count'] ?></strong><span>vehicles in the fleet</span></div>
    </div>
  </div>
</section>
<?php endif; ?>

<section class="bk-section" style="background:var(--bk-cream)">
  <div class="container">
    <div class="bk-section-head">
      <span class="section-eyebrow">CAR HIRE IN KENYA</span>
      <h2>Hire a car where you are travelling</h2>
      <p>These links continue to come directly from your existing SEO content system.</p>
    </div>
    <div class="bk-location-grid">
      <?php foreach ($seoLocations as $location): ?>
        <a href="<?= base_url($location['page_key']) ?>" class="bk-location-card"><span class="bk-location-icon"><i class="fa-solid fa-location-dot"></i></span><span><strong><?= e($location['name']) ?> Car Hire</strong><small><?= e($location['intro']) ?></small></span><i class="fa-solid fa-arrow-right"></i></a>
      <?php endforeach; ?>
      <?php foreach ($seoAirports as $airport): ?>
        <a href="<?= base_url($airport['page_key']) ?>" class="bk-location-card"><span class="bk-location-icon"><i class="fa-solid fa-plane-arrival"></i></span><span><strong><?= e($airport['name']) ?> Car Hire</strong><small><?= e($airport['intro']) ?></small></span><i class="fa-solid fa-arrow-right"></i></a>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="bk-section bk-section-dark">
  <div class="container">
    <div class="bk-section-head center">
      <span class="section-eyebrow">HOW IT WORKS</span>
      <h2>Book in three simple steps</h2>
      <p>A clearer visual explanation of the booking journey your existing system already supports.</p>
    </div>
    <div class="bk-steps">
      <div class="bk-step"><span class="bk-step-num">01</span><div class="bk-step-icon"><i class="fa-solid fa-magnifying-glass"></i></div><h3>Choose your car</h3><p>Browse the live fleet and narrow your options using the available filters.</p></div>
      <div class="bk-step"><span class="bk-step-num">02</span><div class="bk-step-icon"><i class="fa-solid fa-calendar-check"></i></div><h3>Book your dates</h3><p>Select your vehicle and continue through the existing booking process.</p></div>
      <div class="bk-step"><span class="bk-step-num">03</span><div class="bk-step-icon"><i class="fa-solid fa-key"></i></div><h3>Collect and ride</h3><p>Once confirmed, complete your pickup and enjoy the journey.</p></div>
    </div>
  </div>
</section>

<?php if (!empty($testimonials)): ?>
<section class="bk-section">
  <div class="container">
    <div class="bk-section-head center">
      <span class="section-eyebrow">WHAT CLIENTS SAY</span>
      <h2>Trusted across Kenya</h2>
    </div>
    <div class="bk-testimonial-grid">
      <?php foreach ($testimonials as $t): ?>
        <article class="bk-testimonial-card">
          <div class="bk-testimonial-stars"><?php for ($i = 0; $i < (int) $t['rating']; $i++): ?><i class="fa-solid fa-star"></i><?php endfor; ?></div>
          <p class="bk-testimonial-quote">&ldquo;<?= e($t['message']) ?>&rdquo;</p>
          <div class="bk-testimonial-author"><?= e($t['client_name']) ?></div>
          <?php if (!empty($t['client_role'])): ?><div class="bk-testimonial-role"><?= e($t['client_role']) ?></div><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if (!empty($reviewsData['enabled']) && (!empty($reviewsData['reviews']) || !empty($reviewsData['links']['google']) || !empty($reviewsData['links']['tripadvisor']))): ?>
<section class="bk-section bk-reviews-section"><div class="container">
<div class="bk-section-head center"><span class="section-eyebrow">REAL CUSTOMER FEEDBACK</span><h2>What our customers say</h2><p>Recent reviews from customers who have experienced Big Kahuna.</p></div>
<?php $overall=$reviewsData['summary']['overall']??['rating'=>0,'count'=>0]; ?>
<div class="bk-review-summary"><div class="bk-review-score"><strong><?= $overall['rating']>0?e(number_format((float)$overall['rating'],1)):'—' ?></strong><span>out of 5</span></div><div class="bk-review-summary-copy"><div class="bk-review-stars"><?php for($i=1;$i<=5;$i++): ?><i class="fa-solid fa-star <?= $i<=round((float)$overall['rating'])?'is-on':'' ?>"></i><?php endfor; ?></div><strong><?= (int)$overall['count'] ?> published review<?= (int)$overall['count']===1?'':'s' ?></strong><span>Across connected review sources</span></div><div class="bk-review-source-pills"><?php foreach(['google'=>'Google','tripadvisor'=>'Tripadvisor'] as $k=>$label):if(!empty($reviewsData['summary'][$k])):?><span><strong><?= e($label) ?></strong> <?= number_format((float)$reviewsData['summary'][$k]['rating'],1) ?> ★ · <?= (int)$reviewsData['summary'][$k]['count'] ?></span><?php endif;endforeach; ?></div></div>
<?php if(!empty($reviewsData['reviews'])):?><div class="bk-testimonial-grid bk-external-review-grid"><?php foreach($reviewsData['reviews'] as $r): ?><article class="bk-testimonial-card bk-external-review-card"><div class="bk-testimonial-stars"><?php for($i=0;$i<(int)$r['rating'];$i++):?><i class="fa-solid fa-star"></i><?php endfor;?></div><?php if($r['title']!==''):?><h3><?=e($r['title'])?></h3><?php endif;?><p class="bk-testimonial-quote">&ldquo;<?=e($r['comment'])?>&rdquo;</p><div class="bk-external-review-meta"><strong><?=e($r['reviewer_name'])?></strong><span><?=e(ucfirst($r['source']))?> · <?=e(date('d M Y',strtotime($r['review_date'])))?></span></div></article><?php endforeach;?></div><?php endif; ?>
<div class="bk-review-actions"><?php if(!empty($reviewsData['links']['google'])):?><a class="btn btn-outline" href="<?=e($reviewsData['links']['google'])?>" target="_blank" rel="noopener"><i class="fa-brands fa-google"></i> Review us on Google</a><?php endif;?><?php if(!empty($reviewsData['links']['tripadvisor'])):?><a class="btn btn-outline" href="<?=e($reviewsData['links']['tripadvisor'])?>" target="_blank" rel="noopener"><i class="fa-solid fa-plane"></i> Review us on Tripadvisor</a><?php endif;?><a class="btn btn-primary" href="<?=base_url('reviews')?>">Read all reviews <i class="fa-solid fa-arrow-right"></i></a></div>
</div></section>
<?php endif; ?>

<section class="bk-cta">
  <div class="container">
    <h2>Ready to ride the Kahuna?</h2>
    <p>Choose your vehicle and start your booking journey in minutes.</p>
    <a href="<?= base_url('book') ?>" class="btn btn-dark">Book Your Car <i class="fa-solid fa-arrow-right"></i></a>
  </div>
</section>

<?php view('layouts/footer'); ?>
