<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="card">
  <div class="card-header">
    <h2>Fleet (<?= count($cars) ?>)</h2>
    <?php if (Auth::can('cars.manage')): ?>
      <a href="<?= base_url('admin/cars/new') ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Car</a>
    <?php endif; ?>
  </div>

  <?php if (empty($cars)): ?>
    <p style="color:var(--color-text-faint);">No cars added yet.</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Car</th><th>Category</th><th>Price/Day</th><th>Status</th><th>Featured</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($cars as $car): ?>
            <tr>
              <td><?= e($car['name']) ?><br><small style="color:var(--color-text-faint);"><?= e($car['plate_number'] ?? '') ?></small></td>
              <td><?= e($car['category_name'] ?? '—') ?></td>
              <td><?= money($car['price_per_day']) ?></td>
              <td><span class="badge badge-<?= e($car['status']) ?>"><?= e(ucfirst($car['status'])) ?></span></td>
              <td><?= $car['featured'] ? '<i class="fa-solid fa-star" style="color:var(--color-accent-500);"></i>' : '—' ?></td>
              <td>
                <?php if (Auth::can('cars.manage')): ?>
                  <a href="<?= base_url('admin/cars/' . (int) $car['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                  <form action="<?= base_url('admin/cars/' . (int) $car['id'] . '/delete') ?>" method="post" style="display:inline;" onsubmit="return confirm('Delete this car?');">
                    <?= csrf_field() ?>
                    <button type="submit" class="btn btn-danger btn-sm"><i class="fa-solid fa-trash"></i></button>
                  </form>
                <?php endif; ?>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>

<?php view('admin/layout-footer'); ?>
