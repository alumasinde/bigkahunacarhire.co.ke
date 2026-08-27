<?php
$type = $type ?? ($page_type ?? 'guide');
$name = $name ?? '';
$areas = $areas ?? [];
$intro = $intro ?? '';
$h1 = $h1 ?? ($name ?: 'Car Hire');
$content_sections = $content_sections ?? [];
$related = $related ?? [];
$faqs = $faqs ?? [];
$cars = $cars ?? [];
$slug = $slug ?? '';
?>
<?php view('layouts/header', ['seo' => $seo]); ?>
<section class="seo-hero">
  <div class="container">
    <nav class="breadcrumbs" aria-label="Breadcrumb"><a href="<?= base_url('/') ?>">Home</a><span>/</span><span><?= e($name) ?></span></nav>
    <span class="hero-eyebrow"><?= e(strtoupper($type)) ?></span>
    <h1><?= e($h1) ?></h1>
    <p class="lead"><?= e($intro) ?></p>
    <div class="hero-actions"><a href="<?= base_url('fleet') ?>" class="btn btn-primary">View Fleet</a><a href="<?= base_url('book') ?>" class="btn btn-outline">Book a Car</a></div>
  </div>
</section>
<main>
<section><div class="container seo-content-grid"><article>
  <span class="section-eyebrow">BIG KAHUNA CAR HIRE</span>
  <h2><?= e($h1) ?></h2>
  <p><?= e($intro) ?></p>
  <h2>Choose a vehicle for your trip</h2>
  <p>Compare available vehicles by type, seating, transmission and advertised daily rate. Confirm availability, rental requirements and the final price before booking.</p>
  <?php if (!empty($areas)): ?><h2>Areas and destinations</h2><div class="seo-chip-list"><?php foreach($areas as $area): ?><span><?= e($area) ?></span><?php endforeach; ?></div><?php endif; ?>
<?php if (!empty($content_sections)): ?>
  <div class="seo-content-sections">
    <?php foreach ($content_sections as $section): ?>
      <section class="seo-copy-section">
        <h2><?= e($section['title']) ?></h2>
        <p><?= e($section['body']) ?></p>
      </section>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
</article><aside class="seo-aside"><div class="seo-aside-card"><h2>Looking for a car?</h2><p>Browse current vehicles and start your booking.</p><a class="btn btn-primary btn-block" href="<?= base_url('fleet') ?>">Browse Fleet</a><a class="btn btn-dark btn-block" href="<?= base_url('book') ?>">Start Booking</a></div></aside></div></section>
<?php if (!empty($cars)): ?><section class="section-tinted"><div class="container"><div class="section-head section-head-left"><span class="section-eyebrow">AVAILABLE OPTIONS</span><h2>Popular cars</h2></div><div class="car-grid"><?php foreach($cars as $car): ?><div class="car-card"><div class="car-card-img"><?php if(!empty($car['image_path'])):?><img src="<?= e(car_image_url($car['image_path'])) ?>" alt="<?= e($car['name']) ?> car hire in Kenya" width="800" height="600" loading="lazy" decoding="async"><?php else:?><div class="car-card-placeholder"><i class="fa-solid fa-car"></i></div><?php endif;?></div><div class="car-card-body"><p class="car-card-cat"><?= e($car['category_name']??'') ?></p><h3><?= e($car['name']) ?></h3><div class="car-specs"><span><?= (int)$car['seats'] ?> seats</span><span><?= e(ucfirst($car['transmission'])) ?></span></div><div class="car-card-footer"><div class="car-price"><small>From</small><?= money($car['price_per_day']) ?>/day</div><a href="<?= base_url('fleet/'.e($car['slug'])) ?>" class="btn btn-dark btn-sm">Details</a></div></div></div><?php endforeach;?></div></div></section><?php endif;?>
<?php if(!empty($related)): ?><section><div class="container"><div class="section-head section-head-left"><span class="section-eyebrow">EXPLORE MORE</span><h2>Related pages</h2></div><div class="seo-link-grid"><?php foreach($related as $link): ?><a href="<?= base_url($link[1]) ?>" class="seo-link-card"><strong><?= e($link[0]) ?></strong><span>Explore →</span></a><?php endforeach;?></div></div></section><?php endif;?>
<?php if(!empty($faqs)): ?><section class="section-tinted"><div class="container"><div class="section-head section-head-left"><span class="section-eyebrow">FAQ</span><h2>Frequently asked questions</h2></div><div class="faq-list"><?php foreach($faqs as $faq): ?><details class="faq-item"><summary><?= e($faq[0]) ?></summary><p><?= e($faq[1]) ?></p></details><?php endforeach;?></div></div></section><?php endif;?>
<section class="cta-band"><div class="container"><h2>Ready to book?</h2><p>Choose your vehicle and send your trip details to Big Kahuna Car Hire.</p><a href="<?= base_url('book') ?>" class="btn btn-dark">Book Your Car</a></div></section>
</main>
<script type="application/ld+json">
<?php
$schema=['@context'=>'https://schema.org','@type'=>($seo['schema_type']??'WebPage'),'@id'=>base_url($slug).'#webpage','name'=>$seo['title'],'url'=>base_url($slug),'description'=>$seo['description'],'isPartOf'=>['@id'=>base_url('').'#website']];
if (($seo['schema_type']??'')==='FAQPage' && !empty($faqs)) {
  $schema['mainEntity']=array_map(fn($f)=>['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]],$faqs);
}
$breadcrumb=[
  '@type'=>'BreadcrumbList',
  'itemListElement'=>[
    ['@type'=>'ListItem','position'=>1,'name'=>'Home','item'=>base_url('')],
    ['@type'=>'ListItem','position'=>2,'name'=>$name,'item'=>base_url($slug)]
  ]
];
if (!empty($faqs) && ($seo['schema_type']??'') !== 'FAQPage') {
  $schema['mainEntity']=array_map(fn($f)=>['@type'=>'Question','name'=>$f[0],'acceptedAnswer'=>['@type'=>'Answer','text'=>$f[1]]],$faqs);
}
$schema['breadcrumb']=$breadcrumb;
echo json_encode(['@context'=>'https://schema.org','@graph'=>[$schema]],JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE);
?>
</script>
<?php view('layouts/footer'); ?>
