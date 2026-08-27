<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="card">
  <form action="<?= base_url('admin/categories/save') ?>" method="post">
    <?= csrf_field() ?>
    <?php if ($category): ?>
      <input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
    <?php endif; ?>

    <div class="form-group">
      <label for="name">Category Name</label>
      <input type="text" id="name" name="name" value="<?= e($category['name'] ?? '') ?>" required>
    </div>

    <div class="form-group">
      <label for="slug">Slug</label>
      <input type="text" id="slug" name="slug" value="<?= e($category['slug'] ?? '') ?>" placeholder="Auto-generated from name if left blank">
    </div>

    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description" placeholder="Shown on the homepage category cards"><?= e($category['description'] ?? '') ?></textarea>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Category</button>
    <a href="<?= base_url('admin/categories') ?>" class="btn btn-outline">Cancel</a>
  </form>
</div>

<?php view('admin/layout-footer'); ?>
