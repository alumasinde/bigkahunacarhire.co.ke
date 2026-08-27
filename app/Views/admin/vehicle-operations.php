<?php view('admin/layout-header', ['seo'=>$seo]); ?>
<div class="ops-toolbar">
  <div><span class="section-eyebrow">VEHICLE RECORD</span><h2><?= e($car['name']) ?></h2><p><?= e($car['plate_number']?:'Plate not set') ?> · <?= e($car['location']?:'Location not set') ?> · <?= e(ucfirst($car['status'])) ?></p></div>
  <div class="ops-toolbar-actions"><a href="<?= base_url('admin/fleet') ?>" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Fleet</a><a href="<?= base_url('admin/cars/'.$car['id'].'/edit') ?>" class="btn btn-primary"><i class="fa-solid fa-pen"></i> Edit Vehicle</a></div>
</div>

<div class="vehicle-hero">
  <div class="vehicle-cover">
    <?php if(!empty($car['image_path'])): ?><img src="<?= e(car_image_url($car['image_path'])) ?>" alt="<?= e($car['name']) ?>"><?php else: ?><i class="fa-solid fa-car"></i><?php endif; ?>
  </div>
  <div class="vehicle-summary">
    <span class="badge badge-<?= e($car['status']) ?>"><?= e(ucfirst($car['status'])) ?></span>
    <h3><?= e($car['brand'].' '.$car['model']) ?></h3>
    <div class="vehicle-specs"><span><i class="fa-solid fa-calendar"></i> <?= e($car['year']?:'—') ?></span><span><i class="fa-solid fa-gears"></i> <?= e(ucfirst($car['transmission'])) ?></span><span><i class="fa-solid fa-gas-pump"></i> <?= e(ucfirst($car['fuel_type'])) ?></span><span><i class="fa-solid fa-users"></i> <?= (int)$car['seats'] ?> seats</span></div>
    <div class="vehicle-kpi"><span>Latest odometer</span><strong><?= $latestOdometer ? e(number_format((float)$latestOdometer['reading_km'],1).' km') : 'Not recorded' ?></strong></div>
  </div>
</div>

<div class="vehicle-ops-grid">
  <div>
    <div class="card">
      <div class="card-header"><div><h2>Maintenance</h2><small>Service schedule, repairs and completed work.</small></div></div>
      <?php if($maintenance): ?>
      <div class="maintenance-list">
      <?php foreach($maintenance as $m): $due=$m['due_date'] && $m['due_date']<date('Y-m-d') && $m['status']!=='completed'; ?>
        <div class="maintenance-row">
          <div class="maintenance-icon <?= $due?'is-danger':'' ?>"><i class="fa-solid fa-screwdriver-wrench"></i></div>
          <div class="maintenance-main"><strong><?= e($m['title']) ?></strong><small><?= e(ucwords(str_replace('_',' ',$m['maintenance_type']))) ?><?= $m['vendor']?' · '.e($m['vendor']):'' ?></small><small><?= $m['due_date']?'Due '.e(date('d M Y',strtotime($m['due_date']))):'No due date' ?><?= $m['due_odometer_km']!==null?' · '.e(number_format((float)$m['due_odometer_km'],0)).' km':'' ?></small></div>
          <div class="maintenance-right"><strong><?= money($m['cost']) ?></strong><form method="post" action="<?= base_url('admin/maintenance/'.$m['id'].'/status') ?>"><?= csrf_field() ?><select name="status" onchange="this.form.submit()"><?php foreach(['scheduled','in_progress','completed','cancelled'] as $st): ?><option value="<?= $st ?>" <?= $m['status']===$st?'selected':'' ?>><?= ucfirst(str_replace('_',' ',$st)) ?></option><?php endforeach; ?></select></form></div>
        </div>
      <?php endforeach; ?>
      </div>
      <?php else: ?><div class="empty-state"><i class="fa-solid fa-screwdriver-wrench"></i><strong>No maintenance records</strong><span>Add the next service or repair below.</span></div><?php endif; ?>
      <?php if(Auth::can('cars.manage')): ?>
      <details class="ops-details"><summary><i class="fa-solid fa-plus"></i> Schedule maintenance</summary>
      <form action="<?= base_url('admin/fleet/'.$car['id'].'/maintenance') ?>" method="post" class="compact-form"><?= csrf_field() ?>
        <div class="form-row"><div class="form-group"><label>Type</label><select name="maintenance_type"><?php foreach(['service','repair','inspection','tyres','brakes','oil_change','other'] as $x): ?><option value="<?= $x ?>"><?= e(ucwords(str_replace('_',' ',$x))) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Title</label><input name="title" required placeholder="e.g. 10,000 km service"></div></div>
        <div class="form-row"><div class="form-group"><label>Due date</label><input type="date" name="due_date"></div><div class="form-group"><label>Due odometer</label><input type="number" min="0" step="0.1" name="due_odometer_km"></div></div>
        <div class="form-row"><div class="form-group"><label>Service date</label><input type="date" name="service_date"></div><div class="form-group"><label>Cost (KES)</label><input type="number" min="0" step="0.01" name="cost" value="0"></div></div>
        <div class="form-row"><div class="form-group"><label>Vendor / garage</label><input name="vendor"></div><div class="form-group"><label>Status</label><select name="status"><option value="scheduled">Scheduled</option><option value="in_progress">In progress</option><option value="completed">Completed</option></select></div></div>
        <div class="form-group"><label>Description</label><textarea name="description" rows="2"></textarea></div>
        <button class="btn btn-primary" type="submit">Save Maintenance</button>
      </form>
      </details>
      <?php endif; ?>
    </div>

    <div class="card">
      <div class="card-header"><div><h2>Vehicle documents</h2><small>Insurance, inspection, roadworthy and other compliance records.</small></div></div>
      <?php if($documents): ?>
      <div class="document-list">
      <?php foreach($documents as $d): $expired=$d['expiry_date'] && $d['expiry_date']<date('Y-m-d'); ?>
        <div class="document-row">
          <span class="document-icon <?= $expired?'is-danger':'' ?>"><i class="fa-solid fa-file-shield"></i></span>
          <div class="document-main"><strong><?= e($d['title']) ?></strong><small><?= e(ucwords(str_replace('_',' ',$d['document_type']))) ?><?= $d['document_number']?' · '.e($d['document_number']):'' ?></small><small><?= $d['expiry_date']?($expired?'Expired ':'Expires ').e(date('d M Y',strtotime($d['expiry_date']))):'No expiry date' ?></small></div>
          <div class="document-actions">
            <?php if($d['file_path']): ?><a class="btn btn-outline btn-sm" href="<?= base_url('admin/documents/'.$d['id'].'/download') ?>" target="_blank" rel="noopener"><i class="fa-solid fa-eye"></i></a><?php endif; ?>
            <?php if(Auth::can('cars.manage')): ?><form method="post" action="<?= base_url('admin/documents/'.$d['id'].'/status') ?>"><?= csrf_field() ?><select name="status" onchange="this.form.submit()"><option value="active" <?= $d['status']==='active'?'selected':'' ?>>Active</option><option value="expired" <?= $d['status']==='expired'?'selected':'' ?>>Expired</option><option value="replaced" <?= $d['status']==='replaced'?'selected':'' ?>>Replaced</option></select></form><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
      </div>
      <?php else: ?><div class="empty-state"><i class="fa-solid fa-file-circle-check"></i><strong>No documents</strong><span>Add insurance, inspection or other vehicle records.</span></div><?php endif; ?>
      <?php if(Auth::can('cars.manage')): ?>
      <details class="ops-details"><summary><i class="fa-solid fa-upload"></i> Add document</summary>
      <form action="<?= base_url('admin/fleet/'.$car['id'].'/documents') ?>" method="post" enctype="multipart/form-data" class="compact-form"><?= csrf_field() ?>
        <div class="form-row"><div class="form-group"><label>Type</label><select name="document_type"><?php foreach(['logbook','insurance','inspection','roadworthy','permit','lease','other'] as $x): ?><option value="<?= $x ?>"><?= e(ucwords(str_replace('_',' ',$x))) ?></option><?php endforeach; ?></select></div><div class="form-group"><label>Title</label><input name="title" required placeholder="e.g. Comprehensive Insurance 2026"></div></div>
        <div class="form-row"><div class="form-group"><label>Document number</label><input name="document_number"></div><div class="form-group"><label>File</label><input type="file" name="document" accept=".pdf,.jpg,.jpeg,.png,.webp"></div></div>
        <div class="form-row"><div class="form-group"><label>Issued date</label><input type="date" name="issued_date"></div><div class="form-group"><label>Expiry date</label><input type="date" name="expiry_date"></div></div>
        <div class="form-group"><label>Notes</label><textarea name="notes" rows="2"></textarea></div>
        <button class="btn btn-primary" type="submit">Save Document</button>
      </form>
      </details>
      <?php endif; ?>
    </div>
  </div>

  <aside>
    <div class="card">
      <div class="card-header"><div><h2>Odometer</h2><small>Immutable vehicle mileage history.</small></div></div>
      <?php if($latestOdometer): ?><div class="odometer-big"><?= e(number_format((float)$latestOdometer['reading_km'],1)) ?><small>km</small></div><?php endif; ?>
      <?php if(Auth::can('cars.manage')): ?>
      <form action="<?= base_url('admin/fleet/'.$car['id'].'/odometer') ?>" method="post" class="compact-form"><?= csrf_field() ?><div class="form-group"><label>New reading (km)</label><input type="number" name="reading_km" min="0" step="0.1" required></div><div class="form-group"><label>Note</label><input name="notes" placeholder="e.g. Garage inspection"></div><button class="btn btn-primary btn-block" type="submit">Record Reading</button></form>
      <?php endif; ?>
      <div class="odometer-list"><?php foreach($odometer as $o): ?><div class="odometer-row"><span><strong><?= e(number_format((float)$o['reading_km'],1)) ?> km</strong><small><?= e(ucfirst($o['reading_type'])) ?> · <?= e(date('d M Y H:i',strtotime($o['recorded_at']))) ?></small></span><span><?= e($o['recorded_by_name']?:'Staff') ?></span></div><?php endforeach; ?></div>
    </div>
  </aside>
</div>
<?php view('admin/layout-footer'); ?>
