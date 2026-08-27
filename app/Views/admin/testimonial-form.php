<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="card">
  <form action="<?= base_url('admin/testimonials/save') ?>" method="post">
    <?= csrf_field() ?>
    <?php if ($testimonial): ?>
      <input type="hidden" name="id" value="<?= (int) $testimonial['id'] ?>">
    <?php endif; ?>

    <div class="form-row">
      <div class="form-group">
        <label for="client_name">Client Name</label>
        <input type="text" id="client_name" name="client_name" value="<?= e($testimonial['client_name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="client_role">Client Role / Location</label>
        <input type="text" id="client_role" name="client_role" value="<?= e($testimonial['client_role'] ?? '') ?>" placeholder="e.g. Nairobi">
      </div>
    </div>

    <div class="form-group">
      <label for="rating">Rating</label>
      <select id="rating" name="rating">
        <?php for ($r = 5; $r >= 1; $r--): ?>
          <option value="<?= $r ?>" <?= (int) ($testimonial['rating'] ?? 5) === $r ? 'selected' : '' ?>><?= $r ?> star<?= $r > 1 ? 's' : '' ?></option>
        <?php endfor; ?>
      </select>
    </div>

    <div class="form-group">
      <label for="message">Message</label>
      <textarea id="message" name="message" required><?= e($testimonial['message'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label><input type="checkbox" name="is_active" value="1" style="width:auto;" <?= !$testimonial || !empty($testimonial['is_active']) ? 'checked' : '' ?>> Show on homepage</label>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Testimonial</button>
    <a href="<?= base_url('admin/testimonials') ?>" class="btn btn-outline">Cancel</a>
  </form>
</div>

<?php view('admin/layout-footer'); ?>
