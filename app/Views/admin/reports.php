<?php view('admin/layout-header', ['seo' => $seo]); ?>
<div class="ops-toolbar">
  <div><span class="section-eyebrow">BUSINESS INTELLIGENCE</span><h2>Booking reports</h2><p>Review booking volume and rental value for a selected period.</p></div>
  <div class="ops-toolbar-actions"><a href="<?= base_url('admin/bookings') ?>" class="btn btn-outline">Bookings</a></div>
</div>

<div class="card">
  <form method="get" action="<?= base_url('admin/reports') ?>" class="report-filter">
    <div class="form-group"><label for="from">From</label><input type="date" id="from" name="from" value="<?= e($from) ?>" required></div>
    <div class="form-group"><label for="to">To</label><input type="date" id="to" name="to" value="<?= e($to) ?>" required></div>
    <button class="btn btn-primary" type="submit"><i class="fa-solid fa-chart-column"></i> Generate</button>
    <a class="btn btn-outline btn-on-light" href="<?= base_url('admin/reports/bookings.csv?from='.urlencode($from).'&to='.urlencode($to)) ?>"><i class="fa-solid fa-file-csv"></i> Export CSV</a>
  </form>
</div>

<div class="stat-grid ops-stat-grid">
  <div class="stat-card"><i class="fa-solid fa-calendar-check"></i><strong><?= (int)($stats['bookings']??0) ?></strong><span>Bookings in period</span></div>
  <div class="stat-card"><i class="fa-solid fa-money-bill-wave"></i><strong><?= money($stats['gross_value']??0) ?></strong><span>Gross booking value</span></div>
  <div class="stat-card"><i class="fa-solid fa-circle-check"></i><strong><?= money($stats['confirmed_value']??0) ?></strong><span>Confirmed value</span></div>
  <div class="stat-card"><i class="fa-solid fa-flag-checkered"></i><strong><?= money($stats['completed_value']??0) ?></strong><span>Completed value</span></div>
</div>

<div class="card">
  <div class="card-header"><h2>Booking detail</h2><span class="muted-small"><?= e($from) ?> → <?= e($to) ?></span></div>
  <?php if(empty($bookings)): ?>
    <div class="empty-state"><i class="fa-solid fa-chart-line"></i><strong>No bookings in this period</strong></div>
  <?php else: ?>
    <div class="table-wrap"><table class="data-table">
      <thead><tr><th>Ref</th><th>Customer</th><th>Vehicle</th><th>Pickup</th><th>Return</th><th>Status</th><th>Value</th></tr></thead>
      <tbody><?php foreach($bookings as $b): ?>
        <tr><td><a href="<?= base_url('admin/bookings/'.(int)$b['id']) ?>"><?= e($b['booking_ref']) ?></a></td><td><?= e($b['full_name']) ?><br><small><?= e($b['phone']) ?></small></td><td><?= e($b['car_name']) ?><br><small><?= e($b['plate_number']?:'No plate') ?></small></td><td><?= e(date('d M Y H:i',strtotime($b['pickup_date']))) ?></td><td><?= e(date('d M Y H:i',strtotime($b['return_date']))) ?></td><td><span class="badge badge-<?= e($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></span></td><td><?= money($b['total_price']) ?></td></tr>
      <?php endforeach; ?></tbody>
    </table></div>
  <?php endif; ?>
</div>
<?php view('admin/layout-footer'); ?>
