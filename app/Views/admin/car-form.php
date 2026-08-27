<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="card">
  <form action="<?= base_url('admin/cars/save') ?>" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <?php if ($car): ?>
      <input type="hidden" name="id" value="<?= (int) $car['id'] ?>">
      <input type="hidden" name="existing_image" value="<?= e($car['image_path'] ?? '') ?>">
    <?php endif; ?>

    <div class="form-row">
      <div class="form-group">
        <label for="name">Car Name</label>
        <input type="text" id="name" name="name" value="<?= e($car['name'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="category_id">Category</label>
        <select id="category_id" name="category_id">
          <option value="">-- None --</option>
          <?php foreach ($categories as $cat): ?>
            <option value="<?= (int) $cat['id'] ?>" <?= ($car['category_id'] ?? null) == $cat['id'] ? 'selected' : '' ?>><?= e($cat['name']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="brand">Brand</label>
        <input type="text" id="brand" name="brand" value="<?= e($car['brand'] ?? '') ?>" required>
      </div>
      <div class="form-group">
        <label for="model">Model</label>
        <input type="text" id="model" name="model" value="<?= e($car['model'] ?? '') ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="year">Year</label>
        <input type="number" id="year" name="year" min="1990" max="2030" value="<?= e((string) ($car['year'] ?? '')) ?>">
      </div>
      <div class="form-group">
        <label for="price_per_day">Price / Day (KES)</label>
        <input type="number" id="price_per_day" name="price_per_day" step="0.01" value="<?= e((string) ($car['price_per_day'] ?? '')) ?>" required>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="chauffeur_fee_per_day">Chauffeur Fee / Day (KES)</label>
        <input type="number" id="chauffeur_fee_per_day" name="chauffeur_fee_per_day" step="0.01" min="0" value="<?= e((string) ($car['chauffeur_fee_per_day'] ?? '')) ?>" placeholder="Leave blank to use the location rate / sitewide default">
        <small style="color:var(--color-text-faint);">Only applies when a customer books "With Chauffeur." Leave blank to fall back to this car's <a href="<?= base_url('admin/chauffeur-rates') ?>">location rate</a>, or the sitewide default.</small>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="transmission">Transmission</label>
        <select id="transmission" name="transmission">
          <option value="automatic" <?= ($car['transmission'] ?? '') === 'automatic' ? 'selected' : '' ?>>Automatic</option>
          <option value="manual" <?= ($car['transmission'] ?? '') === 'manual' ? 'selected' : '' ?>>Manual</option>
        </select>
      </div>
      <div class="form-group">
        <label for="fuel_type">Fuel Type</label>
        <select id="fuel_type" name="fuel_type">
          <?php foreach (['petrol','diesel','hybrid','electric'] as $fuel): ?>
            <option value="<?= $fuel ?>" <?= ($car['fuel_type'] ?? '') === $fuel ? 'selected' : '' ?>><?= ucfirst($fuel) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="seats">Seats</label>
        <input type="number" id="seats" name="seats" min="1" max="30" value="<?= e((string) ($car['seats'] ?? 4)) ?>" required>
      </div>
      <div class="form-group">
        <label for="doors">Doors</label>
        <input type="number" id="doors" name="doors" min="1" max="6" value="<?= e((string) ($car['doors'] ?? 4)) ?>">
      </div>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="plate_number">Plate Number</label>
        <input type="text" id="plate_number" name="plate_number" value="<?= e($car['plate_number'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="location">Location</label>
        <input type="text" id="location" name="location" value="<?= e($car['location'] ?? 'Nairobi') ?>">
      </div>
    </div>

    <div class="form-group">
      <label for="status">Status</label>
      <select id="status" name="status">
        <?php foreach (['available','booked','maintenance','retired'] as $st): ?>
          <option value="<?= $st ?>" <?= ($car['status'] ?? '') === $st ? 'selected' : '' ?>><?= ucfirst($st) ?></option>
        <?php endforeach; ?>
      </select>
    </div>

    <div class="form-group">
      <label><input type="checkbox" name="featured" value="1" style="width:auto;" <?= !empty($car['featured']) ? 'checked' : '' ?>> Feature this car on the homepage</label>
    </div>

    <div class="form-group">
      <label for="description">Description</label>
      <textarea id="description" name="description"><?= e($car['description'] ?? '') ?></textarea>
    </div>

    <div class="form-group">
      <label for="image">Cover Photo</label>
      <input type="file" id="image" name="image" accept="image/*">
      <small style="color:var(--color-text-faint);">This is the main photo shown on fleet cards and listings.</small>
      <?php if (!empty($car['image_path'])): ?>
        <img src="<?= e(car_image_url($car['image_path'])) ?>" alt="" style="max-width:180px;border-radius:8px;margin-top:8px;">
      <?php endif; ?>
    </div>

    <div class="form-group">
      <label for="gallery_images">Additional Gallery Photos</label>
      <input type="file" id="gallery_images" name="gallery_images[]" accept="image/*" multiple <?= !$car ? 'disabled title="Save the car first, then add gallery photos"' : '' ?>>
      <small style="color:var(--color-text-faint);">Select multiple photos to add them to this car's gallery, shown on its detail page.</small>

      <?php if (!empty($gallery)): ?>
        <div style="display:flex;flex-wrap:wrap;gap:10px;margin-top:12px;">
          <?php foreach ($gallery as $img): ?>
            <div style="position:relative;">
              <img src="<?= e(car_image_url($img['image_path'])) ?>" alt="" style="width:110px;height:80px;object-fit:cover;border-radius:8px;">
              <form action="<?= base_url('admin/cars/images/' . (int) $img['id'] . '/delete') ?>" method="post" style="position:absolute;top:-8px;right:-8px;" onsubmit="return confirm('Remove this photo?');">
                <?= csrf_field() ?>
                <button type="submit" class="btn btn-danger btn-sm" style="padding:2px 8px;border-radius:50%;" aria-label="Remove photo"><i class="fa-solid fa-xmark"></i></button>
              </form>
            </div>
          <?php endforeach; ?>
        </div>
      <?php elseif ($car): ?>
        <p style="color:var(--color-text-faint);font-size:0.85rem;margin-top:8px;">No extra gallery photos yet.</p>
      <?php endif; ?>
    </div>

    <div class="form-row">
      <div class="form-group">
        <label for="meta_title">SEO Meta Title</label>
        <input type="text" id="meta_title" name="meta_title" value="<?= e($car['meta_title'] ?? '') ?>">
      </div>
      <div class="form-group">
        <label for="meta_description">SEO Meta Description</label>
        <input type="text" id="meta_description" name="meta_description" value="<?= e($car['meta_description'] ?? '') ?>">
      </div>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Car</button>
    <a href="<?= base_url('admin/cars') ?>" class="btn btn-outline">Cancel</a>
  </form>
</div>

<?php view('admin/layout-footer'); ?>
