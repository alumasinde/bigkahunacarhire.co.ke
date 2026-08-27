<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="card">
  <div class="card-header">
    <h2>Testimonials (<?= count($testimonials) ?>)</h2>
    <a href="<?= base_url('admin/testimonials/new') ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Testimonial</a>
  </div>

  <?php if (empty($testimonials)): ?>
    <p class="card-muted-text">No testimonials added yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Client</th><th>Rating</th><th>Message</th><th>Status</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($testimonials as $t): ?>
            <tr>
              <td><?= e($t['client_name']) ?><br><small class="table-thumb"><?= e($t['client_role'] ?? '') ?></small></td>
              <td><?php for ($i = 0; $i < (int) $t['rating']; $i++): ?><i class="fa-solid fa-star badge-star"></i><?php endfor; ?></td>
              <td><?= e(mb_strimwidth($t['message'], 0, 80, '…')) ?></td>
              <td><span class="badge badge-<?= $t['is_active'] ? 'active' : 'inactive' ?>"><?= $t['is_active'] ? 'Active' : 'Hidden' ?></span></td>
              <td>
                <a href="<?= base_url('admin/testimonials/' . (int) $t['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                <form action="<?= base_url('admin/testimonials/' . (int) $t['id'] . '/delete') ?>" method="post" style="display:inline;" onsubmit="return confirm('Delete this testimonial?');">
                  <?= csrf_field() ?>
                  <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php view('admin/layout-footer'); ?>
