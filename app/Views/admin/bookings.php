<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="ops-toolbar">
  <div>
    <span class="section-eyebrow">OPERATIONS</span>
    <h2>Booking management</h2>
    <p>Search, filter and manage vehicle reservations without losing track of availability.</p>
  </div>
  <div class="ops-toolbar-actions">
    <a href="<?= base_url('admin/bookings/calendar') ?>" class="btn btn-outline"><i class="fa-solid fa-calendar-days"></i> Calendar</a>
    <a href="<?= base_url('admin/reports') ?>" class="btn btn-primary"><i class="fa-solid fa-chart-line"></i> Reports</a>
  </div>
</div>

<div class="card booking-filters-card">
  <form method="get" action="<?= base_url('admin/bookings') ?>" class="booking-filter-grid">
    <div class="form-group"><label for="q">Search</label><input id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Ref, customer, phone, car, plate"></div>
    <div class="form-group"><label for="status">Status</label>
      <select id="status" name="status">
        <option value="">All statuses</option>
        <?php foreach (['pending','confirmed','ongoing','completed','cancelled'] as $st): ?>
          <option value="<?= $st ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label for="car_id">Vehicle</label>
      <select id="car_id" name="car_id">
        <option value="">All vehicles</option>
        <?php foreach ($cars as $car): ?><option value="<?= (int)$car['id'] ?>" <?= (string)$filters['car_id'] === (string)$car['id'] ? 'selected' : '' ?>><?= e($car['name']) ?><?= $car['plate_number'] ? ' — '.e($car['plate_number']) : '' ?></option><?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label for="from">From</label><input type="date" id="from" name="from" value="<?= e($filters['from']) ?>"></div>
    <div class="form-group"><label for="to">To</label><input type="date" id="to" name="to" value="<?= e($filters['to']) ?>"></div>
    <div class="booking-filter-actions">
      <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Apply</button>
      <a class="btn btn-outline btn-on-light" href="<?= base_url('admin/bookings') ?>">Reset</a>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <h2><?= count($bookings) ?> booking<?= count($bookings) === 1 ? '' : 's' ?></h2>
    <span class="muted-small">Sorted by pickup date</span>
  </div>

  <?php if (empty($bookings)): ?>
    <div class="empty-state"><i class="fa-solid fa-calendar-xmark"></i><strong>No bookings match your filters</strong><span>Try clearing a filter or selecting a wider date range.</span></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table ops-bookings-table">
        <thead><tr><th>Booking</th><th>Customer</th><th>Vehicle</th><th>Rental period</th><th>Total</th><th>Payment</th><th>Status</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($bookings as $b): ?>
          <?php $payment=$payments[$b['id']]??null; ?>
          <tr>
            <td><strong><?= e($b['booking_ref']) ?></strong><br><small>#<?= (int)$b['id'] ?> · <?= e(date('d M Y H:i',strtotime($b['created_at']))) ?></small></td>
            <td><strong><?= e($b['full_name']) ?></strong><br><small><?= e($b['phone']) ?></small></td>
            <td><strong><?= e($b['car_name']) ?></strong><br><small><?= e($b['plate_number'] ?: 'Plate not set') ?></small></td>
            <td><strong><?= e(date('d M Y H:i',strtotime($b['pickup_date']))) ?></strong><br><small>to <?= e(date('d M Y H:i',strtotime($b['return_date']))) ?></small></td>
            <td><strong><?= money($b['total_price']) ?></strong><br><small><?= (int)$b['total_days'] ?> day(s)</small></td>
            <td>
              <?php if($payment): ?>
                <?php
                  $methodTag = ($payment['gateway'] ?? '') === 'paystack'
                    ? 'Paystack'
                    : ($payment['payment_method']==='manual' ? 'Manual' : 'STK');
                ?>
                <span class="badge badge-<?= $payment['status']==='completed'?'confirmed':($payment['status']==='failed'?'cancelled':'pending') ?>"><?= e(ucfirst($payment['status'])) ?></span>
                <br><small><?= e($methodTag) ?> · <?= money($payment['amount']) ?></small>
                <?php if($payment['payment_method']==='manual' && $payment['status']==='pending' && !empty($payment['mpesa_receipt_number'])): ?><br><small class="attention-text"><i class="fa-solid fa-triangle-exclamation"></i> Verify <?= e($payment['mpesa_receipt_number']) ?></small><?php endif; ?>
              <?php else: ?><span class="muted-small">No payment</span><?php endif; ?>
            </td>
            <td><span class="badge badge-<?= e($b['status']) ?>"><?= e(ucfirst($b['status'])) ?></span></td>
            <td><a href="<?= base_url('admin/bookings/'.(int)$b['id']) ?>" class="btn btn-outline btn-sm">Open</a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php view('admin/layout-footer'); ?>
