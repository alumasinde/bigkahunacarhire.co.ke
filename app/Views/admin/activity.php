<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="ops-toolbar">
  <div>
    <span class="section-eyebrow">AUDIT TRAIL</span>
    <h2>Activity</h2>
    <p>A single operational history for booking, payment, rental and WhatsApp events.</p>
  </div>
  <div class="ops-toolbar-actions">
    <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Dashboard</a>
  </div>
</div>

<div class="card activity-card">
  <?php if (empty($logs)): ?>
    <div class="empty-state"><i class="fa-solid fa-clock-rotate-left"></i><strong>No activity yet</strong><span>Important operational actions will appear here.</span></div>
  <?php else: ?>
    <div class="activity-list">
      <?php foreach ($logs as $log): ?>
        <?php $actor = trim((string)($log['actor_name'] ?? '')) ?: 'System'; ?>
        <div class="activity-row">
          <div class="activity-icon"><i class="fa-solid fa-bolt"></i></div>
          <div class="activity-main">
            <strong><?= e($log['description']) ?></strong>
            <small>
              <?= e($actor) ?> · <?= e(date('d M Y, H:i', strtotime($log['created_at']))) ?>
              <?php if (!empty($log['booking_ref'])): ?> · <a href="<?= base_url('admin/bookings/'.(int)$log['booking_id']) ?>"><?= e($log['booking_ref']) ?></a><?php endif; ?>
            </small>
          </div>
          <span class="activity-action"><?= e($log['action']) ?></span>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</div>

<?php view('admin/layout-footer'); ?>
