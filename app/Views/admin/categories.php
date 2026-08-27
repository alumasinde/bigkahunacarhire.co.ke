<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="card">
  <div class="card-header">
    <h2>Categories (<?= count($categories) ?>)</h2>
    <a href="<?= base_url('admin/categories/new') ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Category</a>
  </div>

  <?php if (empty($categories)): ?>
    <p class="card-muted-text">No categories added yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Name</th><th>Slug</th><th>Description</th><th>Cars</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($categories as $cat): ?>
            <tr>
              <td><?= e($cat['name']) ?></td>
              <td><?= e($cat['slug']) ?></td>
              <td><?= e($cat['description'] ?? '—') ?></td>
              <td><?= (int) ($carCounts[$cat['id']] ?? 0) ?></td>
              <td>
                <a href="<?= base_url('admin/categories/' . (int) $cat['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                <form action="<?= base_url('admin/categories/' . (int) $cat['id'] . '/delete') ?>" method="post" style="display:inline;" onsubmit="return confirm('Delete this category? Cars in it will become uncategorized, not deleted.');">
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
