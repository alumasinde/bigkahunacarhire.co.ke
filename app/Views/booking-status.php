<?php view('layouts/header',['seo'=>$seo]); ?>
<main class="container booking-status-page">
  <div class="booking-status-hero">
    <span class="section-eyebrow">BOOKING TRACKER</span>
    <h1><?= e($booking['booking_ref']) ?></h1>
    <p>Everything you need for your Big Kahuna rental in one place.</p>
  </div>
  <div class="booking-status-grid">
    <section class="card">
      <div class="card-header"><div><h2><?= e($booking['car_name']) ?></h2><small><?= e(ucfirst(str_replace('_',' ',$booking['driver_option']))) ?></small></div><span class="badge badge-<?= e($booking['status']) ?>"><?= e(ucfirst($booking['status'])) ?></span></div>
      <div class="booking-summary-grid">
        <div><small>Pickup</small><strong><?= e(date('d M Y, H:i',strtotime($booking['pickup_date']))) ?></strong><span><?= e($booking['pickup_location']) ?></span></div>
        <div><small>Return</small><strong><?= e(date('d M Y, H:i',strtotime($booking['return_date']))) ?></strong><span><?= e($booking['dropoff_location']) ?></span></div>
        <div><small>Total</small><strong><?= money($booking['total_price']) ?></strong><span><?= (int)$booking['total_days'] ?> day(s)</span></div>
        <div><small>Paid</small><strong><?= money($paid) ?></strong><span><?= $balance>0 ? money($balance).' balance' : 'Paid in full' ?></span></div>
      </div>
    </section>
    <section class="card">
      <div class="card-header"><h2>Payment</h2></div>
      <?php if($balance>0): ?>
        <p>Your current outstanding balance is <strong><?= money($balance) ?></strong>.</p>
        <?php if($paystackEnabled && $nextPayment > 0): ?>
          <small class="payment-next-step"><?= $nextPaymentPurpose === 'balance' ? 'Your deposit is covered. Pay the remaining balance to complete the rental.' : 'Pay the required deposit to secure your reservation.' ?></small>
        <?php endif; ?>
        <a class="btn btn-primary" href="<?= base_url('book/confirmation?id='.(int)$booking['id'].'&token='.rawurlencode($publicToken)) ?>"><?= $paystackEnabled ? ($nextPaymentPurpose === 'balance' ? 'Pay Balance' : 'Pay Deposit') : 'Open Payment Options' ?></a>
      <?php else: ?>
        <div class="empty-state"><i class="fa-solid fa-circle-check"></i><strong>Payment complete</strong><span>Your recorded payments cover the current booking total.</span></div>
      <?php endif; ?>
    </section>
  </div>
  <div class="card booking-status-help"><strong>Need help?</strong><span>Reply to the Big Kahuna WhatsApp message or contact our team with booking reference <strong><?= e($booking['booking_ref']) ?></strong>.</span></div>
</main>
<?php view('layouts/footer'); ?>
