<?php view('admin/layout-header', ['seo'=>$seo]); ?>
<div class="ops-toolbar">
  <div><span class="section-eyebrow">FLEET CONTROL</span><h2>Fleet operations</h2><p>Monitor vehicle availability, maintenance and compliance before they affect bookings.</p></div>
  <div class="ops-toolbar-actions"><a href="<?= base_url('admin/cars') ?>" class="btn btn-outline"><i class="fa-solid fa-car"></i> Fleet Setup</a><a href="<?= base_url('admin/cars/new') ?>" class="btn btn-primary"><i class="fa-solid fa-plus"></i> Add Vehicle</a></div>
</div>

<div class="stat-grid ops-stat-grid fleet-stat-grid">
  <div class="stat-card"><i class="fa-solid fa-car-side"></i><strong><?= (int)$stats['total'] ?></strong><span>Total Vehicles</span></div>
  <div class="stat-card"><i class="fa-solid fa-circle-check"></i><strong><?= (int)$stats['available'] ?></strong><span>Available</span></div>
  <div class="stat-card"><i class="fa-solid fa-road"></i><strong><?= (int)$stats['booked'] ?></strong><span>On Rental</span></div>
  <div class="stat-card"><i class="fa-solid fa-screwdriver-wrench"></i><strong><?= (int)$stats['maintenance'] ?></strong><span>Maintenance</span></div>
  <div class="stat-card"><i class="fa-solid fa-file-invoice-dollar"></i><strong><?= money($stats['maintenance_cost_month'] ?? 0) ?></strong><span>Maintenance Cost · Month</span></div>
</div>

<?php if($alerts): ?>
<div class="card fleet-alert-card">
  <div class="card-header"><div><h2><i class="fa-solid fa-triangle-exclamation"></i> Fleet attention</h2><small>Documents and maintenance due within the configured warning window.</small></div><span class="badge badge-pending"><?= count($alerts) ?> alert<?= count($alerts)===1?'':'s' ?></span></div>
  <div class="fleet-alert-list">
  <?php foreach($alerts as $a): $expired=!empty($a['due_date']) && $a['due_date'] < date('Y-m-d'); ?>
    <a class="fleet-alert-row" href="<?= base_url('admin/fleet/'.(int)$a['car_id']) ?>">
      <span class="fleet-alert-icon <?= $expired?'is-danger':'' ?>"><i class="fa-solid <?= $a['alert_type']==='document'?'fa-file-shield':'fa-screwdriver-wrench' ?>"></i></span>
      <span class="fleet-alert-main"><strong><?= e($a['item']) ?></strong><small><?= e($a['car_name']) ?><?= $a['plate_number']?' · '.e($a['plate_number']):'' ?> · <?= ucfirst(e($a['alert_type'])) ?></small></span>
      <span class="fleet-alert-date <?= $expired?'is-danger':'' ?>"><?= $expired?'Expired':e(date('d M Y',strtotime($a['due_date']))) ?></span>
    </a>
  <?php endforeach; ?>
  </div>
</div>
<?php else: ?>
<div class="alert alert-success fleet-clean-alert"><i class="fa-solid fa-circle-check"></i> No fleet documents or maintenance items are due within the current warning window.</div>
<?php endif; ?>

<div class="card">
  <div class="card-header"><div><h2>Vehicle register</h2><small>Open a vehicle to manage maintenance, compliance and odometer history.</small></div></div>
  <div class="table-wrap">
    <table class="data-table fleet-table">
      <thead><tr><th>Vehicle</th><th>Location</th><th>Status</th><th>Odometer</th><th>Actions</th></tr></thead>
      <tbody>
      <?php foreach($cars as $car): ?>
        <tr>
          <td><strong><?= e($car['name']) ?></strong><br><small><?= e($car['plate_number']?:'Plate not set') ?> · <?= e($car['year']?:'Year n/a') ?></small></td>
          <td><?= e($car['location']?:'—') ?></td>
          <td><span class="badge badge-<?= e($car['status']) ?>"><?= e(ucfirst($car['status'])) ?></span></td>
          <td><span class="muted-small">Open vehicle</span></td>
          <td><a href="<?= base_url('admin/fleet/'.(int)$car['id']) ?>" class="btn btn-primary btn-sm"><i class="fa-solid fa-gauge-high"></i> Manage</a></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php view('admin/layout-footer'); ?>
