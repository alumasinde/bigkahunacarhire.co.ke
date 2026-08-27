<?php view('layouts/header', ['seo'=>$seo]); ?>
<div class="print-agreement container">
  <div class="agreement-actions no-print"><a href="<?= base_url('account/bookings/'.$booking['id']) ?>" class="btn btn-outline">Back</a><button onclick="window.print()" class="btn btn-primary"><i class="fa-solid fa-print"></i> Print / Save PDF</button></div>
  <header class="agreement-head"><span class="section-eyebrow">RENTAL AGREEMENT</span><h1><?= e(setting('general','site_name')) ?></h1><p>Booking <?= e($booking['booking_ref']) ?></p></header>
  <div class="agreement-grid"><div><small>Customer</small><strong><?= e($booking['full_name']) ?></strong><span><?= e($booking['phone']) ?></span></div><div><small>Vehicle</small><strong><?= e($booking['car_name']) ?></strong><span><?= e($booking['plate_number']?:'Plate to be confirmed') ?></span></div><div><small>Pickup</small><strong><?= e(date('d M Y H:i',strtotime($booking['pickup_date']))) ?></strong><span><?= e($booking['pickup_location']) ?></span></div><div><small>Return</small><strong><?= e(date('d M Y H:i',strtotime($booking['return_date']))) ?></strong><span><?= e($booking['dropoff_location']) ?></span></div></div>
  <section class="agreement-section"><h2>Rental summary</h2><p>Driving option: <strong><?= e($booking['driver_option']==='with_driver'?'With chauffeur':'Self-drive') ?></strong></p><p>Rental period: <strong><?= (int)$booking['total_days'] ?> day(s)</strong></p><p>Total rental price: <strong><?= money($booking['total_price']) ?></strong></p></section>
  <section class="agreement-section"><h2>Terms &amp; conditions</h2><div class="agreement-terms"><?= nl2br(e($termsText)) ?></div></section>
  <footer class="agreement-footer"><p>Generated for booking <?= e($booking['booking_ref']) ?>. Please retain this document for your rental.</p></footer>
</div>
<style>
@media print{.no-print{display:none!important}body{background:#fff!important}.print-agreement{max-width:850px!important;margin:auto!important}.agreement-section{break-inside:avoid}}
</style>
<?php view('layouts/footer'); ?>
