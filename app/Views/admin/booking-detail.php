<?php view('admin/layout-header', ['seo' => $seo]); ?>

<a href="<?= base_url('admin/bookings') ?>" class="booking-back-link">&larr; Back to Bookings</a>
<div data-scope="booking">
<div class="booking-command-bar">
  <div>
    <span class="section-eyebrow">BOOKING <?= e($booking['booking_ref']) ?></span>
    <h2><?= e($booking['car_name']) ?> <span class="badge badge-<?= e($booking['status']) ?>" data-role="status-badge"><?= e(ucfirst($booking['status'])) ?></span></h2>
    <p><?= e($booking['full_name']) ?> · <?= e($booking['phone']) ?> · <?= e($booking['driver_option'] === 'with_driver' ? 'Chauffeur' : 'Self-drive') ?></p>
  </div>
  <div class="booking-command-actions">
    <?php if (!empty($booking['phone'])): ?>
      <a href="https://wa.me/<?= e(preg_replace('/\D+/', '', $booking['phone'])) ?>" target="_blank" rel="noopener" class="btn btn-whatsapp"><i class="fa-brands fa-whatsapp"></i> WhatsApp</a>
    <?php endif; ?>
    <a href="tel:<?= e($booking['phone']) ?>" class="btn btn-outline"><i class="fa-solid fa-phone"></i> Call</a>
    <?php if (Auth::can('bookings.view') && in_array($booking['status'], ['confirmed','ongoing','completed'], true)): ?>
      <a href="<?= base_url('admin/bookings/' . (int)$booking['id'] . '/handover') ?>" class="btn btn-primary"><i class="fa-solid fa-key"></i> Handover</a>
    <?php endif; ?>
  </div>
</div>
<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
  <div class="stat-card"><i class="fa-solid fa-hashtag"></i><strong><?= e($booking['booking_ref']) ?></strong><span>Reference</span></div>
  <div class="stat-card"><i class="fa-solid fa-money-bill-wave"></i><strong data-role="total-price-stat"><?= money($booking['total_price']) ?></strong><span><?= (int) $booking['total_days'] ?> day(s)</span></div>
  <div class="stat-card"><i class="fa-solid fa-circle-check"></i><strong data-role="status-stat"><?= e(ucfirst($booking['status'])) ?></strong><span>Status</span></div>
  <div class="stat-card"><i class="fa-solid fa-credit-card"></i><strong data-role="payment-stat"><?= $payment ? e(ucfirst($payment['status'])) : 'None' ?></strong><span>Payment</span></div>
</div>

<div class="card">
  <div class="card-header"><h2>Customer Details</h2></div>
  <div class="form-row">
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Name</small><p><?= e($booking['full_name']) ?></p></div>
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Phone</small><p><?= e($booking['phone']) ?></p></div>
  </div>
  <div class="form-row" style="margin-top:14px;">
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Email</small><p><?= e($booking['email']) ?></p></div>
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">National ID / Passport</small><p><?= e($booking['id_number']) ?></p></div>
  </div>
  <div class="form-row" style="margin-top:14px;">
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Driving License</small><p><?= e($booking['driving_license_number']) ?></p></div>
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Terms Accepted</small><p><?= $booking['terms_accepted'] ? e(date('d M Y H:i', strtotime($booking['terms_accepted_at']))) : 'No' ?></p></div>
  </div>
  <div class="form-row" style="margin-top:14px;">
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">WhatsApp Updates</small><p><?= !empty($booking['whatsapp_opt_in']) ? '<span class="badge badge-confirmed">Opted in</span>' : '<span class="badge badge-cancelled">Not opted in</span>' ?></p></div>
  </div>
</div>

<div class="card">
  <div class="card-header"><h2>Booking Details</h2></div>
  <div class="form-row">
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Car</small><p><?= e($booking['car_name']) ?></p></div>
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Driver Option</small><p><?= e($booking['driver_option'] === 'with_driver' ? 'With Chauffeur' : 'Self-Drive') ?></p></div>
  </div>
  <div class="form-row" style="margin-top:14px;">
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Pickup</small><p><?= e($booking['pickup_location']) ?> &middot; <?= e(date('d M Y H:i', strtotime($booking['pickup_date']))) ?></p></div>
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Drop-off</small><p data-role="return-date-value"><?= e($booking['dropoff_location']) ?> &middot; <?= e(date('d M Y H:i', strtotime($booking['return_date']))) ?></p></div>
  </div>
  <?php if (!empty($booking['notes'])): ?>
    <div style="margin-top:14px;"><small style="color:var(--color-text-faint);text-transform:uppercase;">Notes</small><p><?= nl2br(e($booking['notes'])) ?></p></div>
  <?php endif; ?>
</div>

<?php if (in_array($booking['status'], ['confirmed', 'ongoing'], true) && Auth::can('bookings.manage')): ?>
<div class="card" data-role="extend-card">
  <div class="card-header"><h2>Extend Rental</h2></div>
  <p style="color:var(--color-text-faint);font-size:0.85rem;margin-bottom:14px;">
    Customer keeping the car longer than <?= e(date('d M Y, H:i', strtotime($booking['return_date']))) ?>?
    Push the return date out. As you pick a new date we check it live against this vehicle's schedule; the change
    is still blocked automatically on submit if it would overlap the next booking for this vehicle (with the
    configured turnaround buffer) — the total price is recalculated for the new duration and the customer is
    emailed/texted the new return date, updated total, and any balance now due.
  </p>
  <form action="<?= base_url('admin/bookings/' . (int)$booking['id'] . '/extend') ?>" method="post" data-ajax="extend" data-check-url="<?= base_url('admin/bookings/' . (int)$booking['id'] . '/extend-check') ?>" style="display:flex;gap:10px;align-items:flex-end;flex-wrap:wrap;">
    <?= csrf_field() ?>
    <div class="form-group" style="margin-bottom:0;">
      <label for="new_return_date">New return date &amp; time</label>
      <input type="datetime-local" id="new_return_date" name="new_return_date" required>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-clock-rotate-left"></i> Extend Booking</button>
  </form>
</div>
<?php endif; ?>

<?php if ($payment): ?>
<div class="card" data-scope="payment-row">
  <div class="card-header"><h2>Payment</h2></div>
  <?php
    $isPaystack = ($payment['gateway'] ?? '') === 'paystack';
    $methodLabel = $isPaystack
      ? 'Paystack' . (!empty($payment['channel']) ? ' (' . ucwords(str_replace('_',' ',$payment['channel'])) . ')' : '')
      : ($payment['payment_method'] === 'manual' ? 'Manual M-Pesa' : 'M-Pesa STK Push');
    $refLabel = $isPaystack ? 'Paystack Reference' : ($payment['payment_method'] === 'manual' ? 'Transaction Code' : 'M-Pesa Receipt');
    $refValue = $isPaystack ? ($payment['reference'] ?? '') : ($payment['mpesa_receipt_number'] ?? '');
  ?>
  <div class="form-row">
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Amount</small><p><?= money($payment['amount']) ?></p></div>
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Method</small><p><?= e($methodLabel) ?></p></div>
  </div>
  <div class="form-row" style="margin-top:14px;">
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Status</small><p><span class="badge badge-<?= $payment['status'] === 'completed' ? 'confirmed' : ($payment['status'] === 'failed' ? 'cancelled' : 'pending') ?>" data-role="payment-status-badge"><?= e(ucfirst($payment['status'])) ?></span></p></div>
    <div><small style="color:var(--color-text-faint);text-transform:uppercase;">Payer Phone</small><p><?= e($payment['phone']) ?></p></div>
  </div>
  <?php if ($payment['status'] === 'completed'): ?>
    <div style="margin-top:14px;"><small style="color:var(--color-text-faint);text-transform:uppercase;"><?= e($refLabel) ?></small><p><?= e($refValue ?: '—') ?></p></div>
    <?php if (Auth::can('bookings.view')): ?>
      <div style="margin-top:16px;"><a href="<?= base_url('admin/bookings/' . (int)$booking['id'] . '/receipt') ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fa-solid fa-receipt"></i> View / Print Receipt</a></div>
    <?php endif; ?>
  <?php endif; ?>
<?php if ($payment['payment_method'] === 'manual'): ?>
    <div style="margin-top:14px;"><small style="color:var(--color-text-faint);text-transform:uppercase;">Business Number</small><p><?= e($payment['manual_recipient'] ?: setting('mpesa','manual_recipient_phone')) ?></p></div>
    <div style="margin-top:14px;"><small style="color:var(--color-text-faint);text-transform:uppercase;">Transaction Code</small><p><strong><?= e($payment['mpesa_receipt_number'] ?: 'Not submitted') ?></strong></p></div>
    <?php if ($payment['status'] === 'pending' && !empty($payment['mpesa_receipt_number']) && Auth::can('bookings.manage')): ?>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:16px;" data-role="payment-actions">
        <form action="<?= base_url('admin/payments/' . (int)$payment['id'] . '/verify-manual') ?>" method="post" data-ajax="verify-payment">
          <?= csrf_field() ?><button class="btn btn-primary" type="submit"><i class="fa-solid fa-circle-check"></i> Verify &amp; Confirm</button>
        </form>
        <form action="<?= base_url('admin/payments/' . (int)$payment['id'] . '/reject-manual') ?>" method="post" data-ajax="reject-payment">
          <?= csrf_field() ?><button class="btn btn-outline btn-on-light" type="submit"><i class="fa-solid fa-xmark"></i> Reject</button>
        </form>
      </div>
    <?php endif; ?>
  <?php endif; ?>
</div>
<?php endif; ?>

<?php if (Auth::can('bookings.manage')): ?>
<div class="card">
  <div class="card-header"><h2>Update Status</h2></div>
  <form action="<?= base_url('admin/bookings/' . (int) $booking['id'] . '/status') ?>" method="post" data-ajax="booking-status" style="display:flex;gap:10px;align-items:flex-end;">
    <?= csrf_field() ?>
    <div class="form-group" style="margin-bottom:0;flex:1;">
      <label for="status">Status</label>
      <select id="status" name="status">
        <?php
          $statusOptions = match ($booking['status']) {
              'pending' => ['pending','confirmed','cancelled'],
              'confirmed' => ['confirmed','cancelled'],
              default => [$booking['status']],
          };
        ?>
        <?php foreach ($statusOptions as $st): ?>
          <option value="<?= $st ?>" <?= $booking['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <button type="submit" class="btn btn-primary">Update &amp; Notify Customer</button>
  </form>
</div>
<?php endif; ?>

<div class="card activity-card">
  <div class="card-header"><div><h2>Activity</h2><small>Everything important that happened to this booking.</small></div><a href="<?= base_url('admin/activity') ?>" class="btn btn-outline btn-sm">All Activity</a></div>
  <?php if (empty($activity)): ?>
    <div class="empty-state"><i class="fa-solid fa-clock-rotate-left"></i><strong>No activity recorded</strong></div>
  <?php else: ?>
    <div class="activity-list activity-list-compact">
      <?php foreach ($activity as $log): ?>
        <div class="activity-row">
          <div class="activity-icon"><i class="fa-solid fa-bolt"></i></div>
          <div class="activity-main"><strong><?= e($log['description']) ?></strong><small><?= e(trim((string)($log['actor_name'] ?? '')) ?: 'System') ?> · <?= e(date('d M Y, H:i', strtotime($log['created_at']))) ?></small></div>
          <span class="activity-action"><?= e($log['action']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>
</div>

<?php view('admin/layout-footer'); ?>
