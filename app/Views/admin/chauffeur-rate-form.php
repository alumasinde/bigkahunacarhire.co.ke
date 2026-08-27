<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="card">
  <form action="<?= base_url('admin/chauffeur-rates/save') ?>" method="post">
    <?= csrf_field() ?>
    <?php if ($rate): ?>
      <input type="hidden" name="id" value="<?= (int) $rate['id'] ?>">
    <?php endif; ?>

    <div class="form-row">
      <div class="form-group">
        <label for="location">Location</label>
        <input type="text" id="location" name="location" value="<?= e($rate['location'] ?? '') ?>" placeholder="Must match a car's Location field exactly, e.g. Nairobi" required>
      </div>
      <div class="form-group">
        <label for="rate_per_day">Chauffeur Rate / Day (KES)</label>
        <input type="number" id="rate_per_day" name="rate_per_day" step="0.01" min="0.01" value="<?= e((string) ($rate['rate_per_day'] ?? '')) ?>" required>
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Rate</button>
    <a href="<?= base_url('admin/chauffeur-rates') ?>" class="btn btn-outline">Cancel</a>
  </form>
</div>

<?php view('admin/layout-footer'); ?>
