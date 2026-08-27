<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="ops-toolbar">
  <div>
    <span class="section-eyebrow">FINANCE</span>
    <h2>Payments</h2>
    <p>Every payment attempt across the active Paystack gateway, with each payment's purpose clearly identified.</p>
  </div>
  <div class="ops-toolbar-actions">
    <a href="<?= base_url('admin/payments?needs_verification=1') ?>" class="btn <?= $filters['needs_verification'] ? 'btn-primary' : 'btn-outline' ?>">
      <i class="fa-solid fa-triangle-exclamation"></i> Needs Verification<?= $manualPaymentsPending > 0 ? ' (' . (int)$manualPaymentsPending . ')' : '' ?>
    </a>
    <a href="<?= base_url('admin/bookings') ?>" class="btn btn-outline"><i class="fa-solid fa-calendar-check"></i> Bookings</a>
  </div>
</div>

<div class="stat-grid" style="grid-template-columns:repeat(auto-fit,minmax(180px,1fr));">
  <?php foreach ($gatewayBreakdown as $g): ?>
    <div class="stat-card"><i class="fa-solid fa-money-check-dollar"></i><strong><?= money($g['total']) ?></strong><span><?= e($g['label']) ?> · <?= (int)$g['count'] ?> this month</span></div>
  <?php endforeach; ?>
</div>

<div class="card booking-filters-card">
  <form method="get" action="<?= base_url('admin/payments') ?>" class="booking-filter-grid">
    <div class="form-group"><label for="q">Search</label><input id="q" name="q" value="<?= e($filters['q']) ?>" placeholder="Ref, customer, phone, reference"></div>
    <div class="form-group"><label for="status">Status</label>
      <select id="status" name="status">
        <option value="">All statuses</option>
        <?php foreach (['pending','completed','failed','cancelled'] as $st): ?>
          <option value="<?= $st ?>" <?= $filters['status'] === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group"><label for="gateway">Gateway</label>
      <select id="gateway" name="gateway">
        <option value="">All gateways</option>
        <option value="paystack" <?= $filters['gateway'] === 'paystack' ? 'selected' : '' ?>>Paystack</option>
      </select>
    </div>
    <div class="form-group"><label for="from">From</label><input type="date" id="from" name="from" value="<?= e($filters['from']) ?>"></div>
    <div class="form-group"><label for="to">To</label><input type="date" id="to" name="to" value="<?= e($filters['to']) ?>"></div>
    <?php if ($filters['needs_verification']): ?><input type="hidden" name="needs_verification" value="1"><?php endif; ?>
    <div class="booking-filter-actions">
      <button class="btn btn-primary" type="submit"><i class="fa-solid fa-filter"></i> Apply</button>
      <a class="btn btn-outline btn-on-light" href="<?= base_url('admin/payments') ?>">Reset</a>
    </div>
  </form>
</div>

<div class="card">
  <div class="card-header">
    <h2><?= count($payments) ?> payment<?= count($payments) === 1 ? '' : 's' ?></h2>
    <span class="muted-small">Most recent first · showing up to 300</span>
  </div>

  <?php if (empty($payments)): ?>
    <div class="empty-state"><i class="fa-solid fa-receipt"></i><strong>No payments match your filters</strong><span>Try clearing a filter or selecting a wider date range.</span></div>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table ops-bookings-table">
        <thead><tr><th>Booking</th><th>Customer</th><th>Purpose</th><th>Gateway</th><th>Amount</th><th>Reference</th><th>Status</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($payments as $p): ?>
          <?php
            $isPaystack = ($p['gateway'] ?? '') === 'paystack';
            $isManual = ($p['payment_method'] ?? '') === 'manual';
            $purpose = ($p['payment_purpose'] ?? '') === 'balance'
              || str_starts_with((string)($p['reference'] ?? ''), 'KAHUNA-BAL-')
              ? 'Balance payment'
              : (($p['payment_purpose'] ?? '') === 'deposit'
                || str_starts_with((string)($p['reference'] ?? ''), 'KAHUNA-BK-')
                ? 'Booking deposit'
                : 'Payment');
            $gatewayLabel = $isPaystack
              ? 'Paystack' . (!empty($p['channel']) ? ' (' . ucwords(str_replace('_',' ',$p['channel'])) . ')' : '')
              : ($isManual ? 'Manual M-Pesa' : 'M-Pesa STK');
            $refValue = $isPaystack ? ($p['reference'] ?? '') : ($p['mpesa_receipt_number'] ?? '');
            $needsVerify = $isManual && $p['status'] === 'pending' && !empty($p['mpesa_receipt_number']);
          ?>
          <tr data-scope="payment-row">
            <td><strong><?= e($p['booking_ref']) ?></strong><br><small><?= e($p['car_name']) ?></small></td>
            <td><strong><?= e($p['full_name']) ?></strong><br><small><?= e($p['phone']) ?></small></td>
            <td><span class="payment-purpose-badge <?= $purpose === 'Balance payment' ? 'balance' : 'deposit' ?>"><?= e($purpose) ?></span></td>
            <td><?= e($gatewayLabel) ?></td>
            <td><strong><?= money($p['amount']) ?></strong></td>
            <td><small><?= e($refValue ?: '—') ?></small></td>
            <td>
              <span class="badge badge-<?= $p['status']==='completed'?'confirmed':($p['status']==='failed'?'cancelled':'pending') ?>" data-role="payment-status-badge"><?= e(ucfirst($p['status'])) ?></span>
              <?php if ($needsVerify): ?><br><small class="attention-text" data-role="needs-verify-flag"><i class="fa-solid fa-triangle-exclamation"></i> Needs verification</small><?php endif; ?>
            </td>
            <td><small><?= e(date('d M Y H:i', strtotime($p['created_at']))) ?></small></td>
            <td>
              <div style="display:flex;gap:6px;flex-wrap:wrap;">
                <a href="<?= base_url('admin/bookings/' . (int)$p['booking_id']) ?>" class="btn btn-outline btn-sm">Booking</a>
                <?php if ($p['status'] === 'completed'): ?>
                  <a href="<?= base_url('admin/payments/' . (int)$p['id'] . '/receipt') ?>" target="_blank" class="btn btn-outline btn-sm"><i class="fa-solid fa-receipt"></i></a>
                <?php endif; ?>
                <?php if ($needsVerify && Auth::can('bookings.manage')): ?>
                  <div style="display:flex;gap:6px;flex-wrap:wrap;" data-role="payment-actions">
                    <form action="<?= base_url('admin/payments/' . (int)$p['id'] . '/verify-manual') ?>" method="post" data-ajax="verify-payment" style="display:inline;">
                      <?= csrf_field() ?><input type="hidden" name="return" value="payments">
                      <button class="btn btn-primary btn-sm" type="submit"><i class="fa-solid fa-check"></i></button>
                    </form>
                    <form action="<?= base_url('admin/payments/' . (int)$p['id'] . '/reject-manual') ?>" method="post" data-ajax="reject-payment" style="display:inline;">
                      <?= csrf_field() ?><input type="hidden" name="return" value="payments">
                      <button class="btn btn-outline btn-sm" type="submit"><i class="fa-solid fa-xmark"></i></button>
                    </form>
                  </div>
                <?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php view('admin/layout-footer'); ?>
