<?php view('admin/layout-header', ['seo' => $seo]); ?>
<?php
$start=new DateTime($month.'-01');
$daysInMonth=(int)$start->format('t');
$firstWeekday=(int)$start->format('N'); // 1 Mon ... 7 Sun
$prev=(clone $start)->modify('-1 month')->format('Y-m');
$next=(clone $start)->modify('+1 month')->format('Y-m');

$byDay=[];
foreach($calendarBookings as $b){
  $pickup=new DateTime($b['pickup_date']);
  $return=new DateTime($b['return_date']);
  for($d=1;$d<=$daysInMonth;$d++){
    $day=new DateTime($month.'-'.str_pad((string)$d,2,'0',STR_PAD_LEFT));
    $dayEnd=(clone $day)->modify('+1 day');
    if($pickup<$dayEnd && $return>$day){
      $byDay[$d][]=$b;
    }
  }
}
?>
<div class="ops-toolbar">
  <div><span class="section-eyebrow">FLEET AVAILABILITY</span><h2>Booking calendar</h2><p>Every non-cancelled reservation occupying a vehicle is shown on its rental dates.</p></div>
  <div class="ops-toolbar-actions"><a href="<?= base_url('admin/bookings') ?>" class="btn btn-outline"><i class="fa-solid fa-list"></i> List</a><a href="<?= base_url('admin/reports') ?>" class="btn btn-primary"><i class="fa-solid fa-chart-line"></i> Reports</a></div>
</div>

<div class="card calendar-card">
  <div class="calendar-header">
    <a class="calendar-nav" href="<?= base_url('admin/bookings/calendar?month='.$prev) ?>" aria-label="Previous month"><i class="fa-solid fa-chevron-left"></i></a>
    <div><h2><?= e($monthLabel) ?></h2><small><?= count($calendarBookings) ?> active reservation<?= count($calendarBookings)===1?'':'s' ?></small></div>
    <a class="calendar-nav" href="<?= base_url('admin/bookings/calendar?month='.$next) ?>" aria-label="Next month"><i class="fa-solid fa-chevron-right"></i></a>
    <a class="btn btn-outline btn-sm" href="<?= base_url('admin/bookings/calendar?month='.date('Y-m')) ?>">Today</a>
  </div>

  <div class="calendar-weekdays">
    <?php foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $wd): ?><span><?= e($wd) ?></span><?php endforeach; ?>
  </div>

  <div class="booking-calendar-grid">
    <?php for($blank=1;$blank<$firstWeekday;$blank++): ?><div class="calendar-day is-empty"></div><?php endfor; ?>
    <?php for($d=1;$d<=$daysInMonth;$d++): ?>
      <?php $date=$month.'-'.str_pad((string)$d,2,'0',STR_PAD_LEFT); $isToday=$date===date('Y-m-d'); ?>
      <div class="calendar-day <?= $isToday?'is-today':'' ?>">
        <div class="calendar-day-number"><?= $d ?></div>
        <div class="calendar-bookings">
        <?php foreach(($byDay[$d]??[]) as $b): ?>
          <a href="<?= base_url('admin/bookings/'.(int)$b['id']) ?>" class="calendar-booking status-<?= e($b['status']) ?>" title="<?= e($b['booking_ref'].' · '.$b['car_name']) ?>">
            <strong><?= e($b['car_name']) ?></strong>
            <span><?= e($b['booking_ref']) ?></span>
            <small><?= e(date('H:i',strtotime($b['pickup_date']))) ?>–<?= e(date('H:i',strtotime($b['return_date']))) ?></small>
          </a>
        <?php endforeach; ?>
        </div>
      </div>
    <?php endfor; ?>
  </div>

  <div class="calendar-legend">
    <span><i class="legend-dot pending"></i> Pending</span>
    <span><i class="legend-dot confirmed"></i> Confirmed</span>
    <span><i class="legend-dot ongoing"></i> Ongoing</span>
    <span><i class="legend-dot completed"></i> Completed</span>
    <span class="calendar-tip"><i class="fa-solid fa-circle-info"></i> Cancelled bookings are excluded.</span>
  </div>
</div>
<?php view('admin/layout-footer'); ?>
