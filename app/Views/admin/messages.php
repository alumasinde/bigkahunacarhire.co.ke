<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="card-header" style="margin-bottom:10px;">
  <h2>Contact Messages (<?= count($messages) ?>)</h2>
</div>

<?php if (empty($messages)): ?>
  <div class="card"><p style="color:var(--color-text-faint);">No messages yet.</p></div>
<?php else: ?>
  <?php foreach ($messages as $m): ?>
    <div class="card">
      <div class="card-header">
        <div>
          <h2 style="margin-bottom:4px;"><?= e($m['name']) ?> <span class="badge badge-<?= $m['status'] === 'new' ? 'new' : ($m['status'] === 'replied' ? 'confirmed' : 'pending') ?>"><?= e(ucfirst($m['status'])) ?></span></h2>
          <small style="color:var(--color-text-faint);"><?= e($m['email']) ?><?= $m['phone'] ? ' &middot; ' . e($m['phone']) : '' ?> &middot; <?= e(date('d M Y H:i', strtotime($m['created_at']))) ?></small>
        </div>
      </div>

      <?php if (!empty($m['subject'])): ?><p><strong>Subject:</strong> <?= e($m['subject']) ?></p><?php endif; ?>
      <p style="white-space:pre-wrap;background:var(--color-bg-subtle);border:1px solid var(--color-border);border-radius:8px;padding:14px;margin:10px 0;"><?= e($m['message']) ?></p>

      <?php if ($m['status'] === 'replied' && !empty($m['admin_reply'])): ?>
        <div style="background:var(--color-success-bg);border-radius:8px;padding:14px;margin-bottom:14px;">
          <small style="color:var(--color-success);font-weight:700;text-transform:uppercase;">Your Reply &middot; <?= e(date('d M Y H:i', strtotime($m['replied_at']))) ?></small>
          <p style="white-space:pre-wrap;margin-top:6px;"><?= e($m['admin_reply']) ?></p>
        </div>
      <?php endif; ?>

      <form action="<?= base_url('admin/messages/' . (int) $m['id'] . '/reply') ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-group">
          <label for="reply-<?= (int) $m['id'] ?>"><?= $m['status'] === 'replied' ? 'Send Another Reply' : 'Reply' ?></label>
          <textarea id="reply-<?= (int) $m['id'] ?>" name="reply" placeholder="Type your reply — this will be emailed to the customer..."></textarea>
        </div>
        <button type="submit" class="btn btn-primary btn-sm"><i class="fa-solid fa-reply"></i> Send Reply</button>
      </form>
    </div>
  <?php endforeach; ?>
<?php endif; ?>

<?php view('admin/layout-footer'); ?>
