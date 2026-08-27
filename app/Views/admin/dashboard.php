<?php view('admin/layout-header', ['seo' => $seo]); ?>

<?php
$goLiveIssues=[];
if(APP_ENV==='production' && MPESA_ENV!=='production') $goLiveIssues[]='M-Pesa is still in <strong>sandbox</strong> mode while the site is live in production.';
if(APP_ENV==='production' && PAYSTACK_ENABLED && str_starts_with(PAYSTACK_SECRET_KEY,'sk_test_')) $goLiveIssues[]='Paystack is using a <strong>test</strong> secret key while the site is live in production. Switch to a live key before accepting real payments.';
if(str_contains(setting('general','phone_primary'),'000 000')) $goLiveIssues[]='The primary phone number is still a placeholder — update it under Settings → General.';
if(empty(setting('seo','google_analytics_id'))) $goLiveIssues[]='No Google Analytics ID is set yet.';
if(empty(setting('seo','google_site_verification'))) $goLiveIssues[]='Google Search Console verification is not configured yet.';
?>
<?php if($goLiveIssues): ?>
<div class="alert alert-error ops-alert"><strong><i class="fa-solid fa-triangle-exclamation"></i> Go-live checklist</strong><ul><?php foreach($goLiveIssues as $issue): ?><li><?= $issue ?></li><?php endforeach; ?></ul></div>
<?php endif; ?>

<div class="ops-toolbar">
  <div><span class="section-eyebrow">TODAY'S CONTROL ROOM</span><h2>Operations dashboard</h2><p>See what needs attention before the next customer arrives.</p></div>
  <div class="ops-toolbar-actions"><a href="<?= base_url('admin/bookings/calendar') ?>" class="btn btn-outline"><i class="fa-solid fa-calendar-days"></i> Open Calendar</a><a href="<?= base_url('admin/reports') ?>" class="btn btn-primary"><i class="fa-solid fa-chart-line"></i> Reports</a></div>
</div>

<div class="ops-attention-grid">
  <a class="attention-card attention-primary" href="<?= base_url('admin/bookings?status=pending') ?>">
    <span class="attention-icon"><i class="fa-solid fa-bell"></i></span>
    <span><strong><?= (int)($bookingCounts['pending'] ?? 0) ?> pending</strong><small>Bookings waiting for confirmation</small></span>
    <i class="fa-solid fa-arrow-right"></i>
  </a>
  <a class="attention-card" href="<?= base_url('admin/payments?needs_verification=1') ?>">
    <span class="attention-icon"><i class="fa-solid fa-money-bill-transfer"></i></span>
    <span><strong><?= (int)$manualPaymentsPending ?> payment<?= $manualPaymentsPending === 1 ? '' : 's' ?> to verify</strong><small>Manual M-Pesa needs attention</small></span>
    <i class="fa-solid fa-arrow-right"></i>
  </a>
  <a class="attention-card" href="<?= base_url('admin/bookings/calendar') ?>">
    <span class="attention-icon"><i class="fa-solid fa-key"></i></span>
    <span><strong><?= (int)$todayPickupCount ?> pickup<?= $todayPickupCount === 1 ? '' : 's' ?> today</strong><small>Open the calendar for today's handovers</small></span>
    <i class="fa-solid fa-arrow-right"></i>
  </a>
  <a class="attention-card" href="<?= base_url('admin/activity') ?>">
    <span class="attention-icon"><i class="fa-solid fa-clock-rotate-left"></i></span>
    <span><strong><?= (int)$activityCount ?> actions today</strong><small>Audit trail and operational changes</small></span>
    <i class="fa-solid fa-arrow-right"></i>
  </a>
</div>

<div class="stat-grid ops-stat-grid">
  <div class="stat-card"><i class="fa-solid fa-car"></i><strong><?= (int)$totalCars ?></strong><span>Total Cars</span></div>
  <div class="stat-card"><i class="fa-solid fa-hourglass-half"></i><strong><?= (int)($bookingCounts['pending']??0) ?></strong><span>Pending Bookings</span></div>
  <div class="stat-card"><i class="fa-solid fa-circle-check"></i><strong><?= (int)($bookingCounts['confirmed']??0) ?></strong><span>Confirmed</span></div>
  <div class="stat-card"><i class="fa-solid fa-road"></i><strong><?= (int)($bookingCounts['ongoing']??0) ?></strong><span>Ongoing Rentals</span></div>
  <div class="stat-card"><i class="fa-solid fa-money-bill-wave"></i><strong><?= money($operationalStats['confirmed_value']??0) ?></strong><span>Confirmed Value · This Month</span></div>
  <div class="stat-card"><i class="fa-solid fa-triangle-exclamation"></i><strong><?= (int)$manualPaymentsPending ?></strong><span>Manual Payments to Verify</span></div>
</div>

<div class="card fleet-dashboard-card">
  <div class="card-header"><div><h2>Payments this month</h2><small>Completed deposits by channel.</small></div></div>
  <div class="fleet-mini-grid">
    <?php foreach($gatewayBreakdown as $g): ?>
      <div><i class="fa-solid fa-money-check-dollar"></i><strong><?= money($g['total']) ?></strong><span><?= e($g['label']) ?> · <?= (int)$g['count'] ?> payment<?= (int)$g['count']===1?'':'s' ?></span></div>
    <?php endforeach; ?>
  </div>
</div>

<div class="card fleet-dashboard-card">
  <div class="card-header"><div><h2>Fleet health</h2><small>Live vehicle availability and compliance attention.</small></div><a href="<?= base_url('admin/fleet') ?>" class="btn btn-outline btn-sm">Manage Fleet</a></div>
  <div class="fleet-mini-grid">
    <div><i class="fa-solid fa-circle-check"></i><strong><?= (int)$fleetStats['available'] ?></strong><span>Available</span></div>
    <div><i class="fa-solid fa-road"></i><strong><?= (int)$fleetStats['booked'] ?></strong><span>On Rental</span></div>
    <div><i class="fa-solid fa-screwdriver-wrench"></i><strong><?= (int)$fleetStats['maintenance'] ?></strong><span>Maintenance</span></div>
    <div><i class="fa-solid fa-triangle-exclamation"></i><strong><?= count($fleetAlerts) ?></strong><span>Fleet Alerts</span></div>
  </div>
</div>

<div class="card fleet-dashboard-card" style="margin-bottom:18px;">
  <div class="card-header"><div><h2>Customer lifecycle</h2><small>Bookings that need a human or automated follow-up.</small></div><a href="<?= base_url('admin/whatsapp') ?>" class="btn btn-outline btn-sm"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a></div>
  <div class="fleet-mini-grid">
    <div><i class="fa-solid fa-comments"></i><strong><?= (int)($unreadWhatsAppCount ?? 0) ?></strong><span>Unread WhatsApp conversations</span></div>
    <div><i class="fa-solid fa-clock"></i><strong><?= (int)($todayPickupCount ?? 0) ?></strong><span>Pickups today</span></div>
    <div><i class="fa-solid fa-key"></i><strong><?= (int)count(array_filter($upcomingBookings, fn($b)=>($b['status']??'')==='ongoing')) ?></strong><span>Active rentals</span></div>
    <div><i class="fa-solid fa-bell"></i><strong><?= (int)($bookingCounts['pending'] ?? 0) ?></strong><span>Bookings awaiting action</span></div>
  </div>
</div>

<div class="ops-dashboard-grid">
  <div class="card">
    <div class="card-header"><h2>Upcoming rentals</h2><a href="<?= base_url('admin/bookings/calendar') ?>" class="btn btn-outline btn-sm">Calendar</a></div>
    <?php if(empty($upcomingBookings)): ?>
      <div class="empty-state"><i class="fa-solid fa-calendar-check"></i><strong>No upcoming rentals</strong><span>Confirmed and ongoing rentals will appear here.</span></div>
    <?php else: ?>
      <div class="upcoming-list">
      <?php foreach($upcomingBookings as $b): ?>
        <a href="<?= base_url('admin/bookings/'.(int)$b['id']) ?>" class="upcoming-item">
          <span class="upcoming-date"><?= e(date('d M',strtotime($b['pickup_date']))) ?><small><?= e(date('H:i',strtotime($b['pickup_date']))) ?></small></span>
          <span class="upcoming-main"><strong><?= e($b['car_name']) ?></strong><small><?= e($b['full_name']) ?> · <?= e($b['plate_number']?:'No plate') ?></small></span>
          <span class="badge badge-<?= e($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></span>
        </a>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="card">
    <div class="card-header"><h2>Recent bookings</h2><a href="<?= base_url('admin/bookings') ?>" class="btn btn-outline btn-sm">View all</a></div>
    <?php if(empty($recentBookings)): ?>
      <div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><strong>No bookings yet</strong></div>
    <?php else: ?>
      <div class="upcoming-list">
      <?php foreach($recentBookings as $b): ?>
        <a href="<?= base_url('admin/bookings/'.(int)$b['id']) ?>" class="upcoming-item"><span class="upcoming-main"><strong><?= e($b['booking_ref']) ?> · <?= e($b['car_name']) ?></strong><small><?= e($b['full_name']) ?> · <?= e(date('d M Y',strtotime($b['pickup_date']))) ?></small></span><span class="badge badge-<?= e($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></span></a>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php view('admin/layout-footer'); ?>
