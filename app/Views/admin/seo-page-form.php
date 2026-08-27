<?php view('admin/layout-header', ['seo'=>$seo]); ?>
<div class="admin-page-head">
  <div><span class="section-eyebrow">SEO CONTENT</span><h2><?= !empty($page['id']) ? 'Edit SEO Page' : 'Add SEO Page' ?></h2><p>Core page content is stored in MySQL and rendered dynamically.</p></div>
  <a href="<?= base_url('admin/seo-pages') ?>" class="btn btn-outline">Back</a>
</div>

<div class="admin-card">
<form method="post" action="<?= base_url('admin/seo-pages/save') ?>" class="admin-form">
<?= csrf_field() ?>
<input type="hidden" name="id" value="<?= e((string)($page['id'] ?? '')) ?>">

<div class="form-grid">
  <div class="form-group"><label>Page type</label>
    <select name="page_type" required>
      <?php foreach(['location','airport','service','guide','faq'] as $type): ?>
        <option value="<?= $type ?>" <?= ($page['page_type'] ?? '') === $type ? 'selected' : '' ?>><?= ucfirst($type) ?></option>
      <?php endforeach; ?>
    </select>
  </div>
  <div class="form-group"><label>Slug</label><input name="slug" value="<?= e($page['slug'] ?? '') ?>" placeholder="nairobi" required><small>Location → /locations/slug, airport → /airports/slug, service → /services/slug.</small></div>
</div>

<div class="form-grid">
  <div class="form-group"><label>Display name</label><input name="name" value="<?= e($page['name'] ?? '') ?>" required></div>
  <div class="form-group"><label>Sort order</label><input type="number" name="sort_order" value="<?= e((string)($page['sort_order'] ?? 0)) ?>"></div>
</div>

<div class="form-group"><label>SEO title</label><input name="title" maxlength="255" value="<?= e($page['title'] ?? '') ?>" required></div>
<div class="form-group"><label>Meta description</label><textarea name="meta_description" rows="3" maxlength="500" required><?= e($page['meta_description'] ?? '') ?></textarea></div>
<div class="form-group"><label>H1</label><input name="h1" value="<?= e($page['h1'] ?? '') ?>" required></div>
<div class="form-group"><label>Introduction</label><textarea name="intro" rows="5" required><?= e($page['intro'] ?? '') ?></textarea></div>
<div class="form-group"><label>Areas / destinations</label><textarea name="areas" rows="4" placeholder="One per line"><?= e($page['areas_text'] ?? '') ?></textarea></div>

<div class="form-check-row">
  <label><input type="checkbox" name="is_active" value="1" <?= !empty($page['is_active']) ? 'checked' : '' ?>> Active</label>
  <label><input type="checkbox" name="is_indexable" value="1" <?= !empty($page['is_indexable']) ? 'checked' : '' ?>> Allow search indexing</label>
</div>

<div style="display:flex;gap:.6rem;margin-top:1rem"><button class="btn btn-primary" type="submit"><i class="fa-solid fa-save"></i> Save Page</button><a class="btn btn-outline" href="<?= base_url('admin/seo-pages') ?>">Cancel</a></div>
</form>
</div>
<?php view('admin/layout-footer'); ?>
