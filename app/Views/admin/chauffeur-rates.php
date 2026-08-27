<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="card">
  <div class="card-header">
    <h2>Chauffeur Rates (<?= count($rates) ?>)</h2>
    <a href="<?= base_url('admin/chauffeur-rates/new') ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-plus"></i> Add Rate</a>
  </div>

  <p class="card-muted-text" style="margin-bottom:16px;">
    When a customer books "With Chauffeur", the fee charged is: the car's own
    override rate if it has one, otherwise the rate below for that car's
    location, otherwise the sitewide default in
    <a href="<?= base_url('admin/settings') ?>">Settings → General</a>.
  </p>

  <?php if (empty($rates)): ?>
    <p class="card-muted-text">No location rates set yet — every car currently uses the sitewide default (or its own override, if set).</p>
  <?php else: ?>
    <div class="table-wrap">
      <table class="data-table">
        <thead><tr><th>Location</th><th>Rate / Day</th><th>Actions</th></tr></thead>
        <tbody>
          <?php foreach ($rates as $r): ?>
            <tr>
              <td><?= e($r['location']) ?></td>
              <td><?= money($r['rate_per_day']) ?></td>
              <td>
                <a href="<?= base_url('admin/chauffeur-rates/' . (int) $r['id'] . '/edit') ?>" class="btn btn-outline btn-sm">Edit</a>
                <form action="<?= base_url('admin/chauffeur-rates/' . (int) $r['id'] . '/delete') ?>" method="post" style="display:inline;" onsubmit="return confirm('Remove this rate? Cars in this location will fall back to the sitewide default.');">
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
