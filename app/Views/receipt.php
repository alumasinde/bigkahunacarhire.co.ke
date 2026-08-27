<?php
/**
 * Standalone payment receipt — deliberately does NOT use layouts/header or
 * layouts/footer. It is meant to be printed or saved as a PDF via the
 * browser's print dialog, so it ships its own minimal, self-contained markup.
 *
 * Expected variables:
 *   $booking   array   from BookingService::find()
 *   $payment   array   the completed payment row (payments table)
 *   $isAdmin   bool    true when rendered from the admin area (adds a back link)
 *   $backUrl   string  where the "back" link should point
 */

$siteName    = setting('general', 'site_name', 'Big Kahuna Car Hire');
$sitePhone   = setting('general', 'phone_primary');
$siteEmail   = setting('general', 'email');
$siteAddress = setting('general', 'address');

$gateway = strtolower((string)($payment['gateway'] ?? ''));
$method  = (string)($payment['payment_method'] ?? '');

if ($gateway === 'paystack') {
    $methodLabel = 'Paystack (' . ucwords(str_replace('_', ' ', (string)($payment['channel'] ?? 'online'))) . ')';
    $refLabel    = 'Paystack Reference';
    $refValue    = (string)($payment['reference'] ?: '—');
} elseif ($method === 'manual') {
    $methodLabel = 'Manual M-Pesa';
    $refLabel    = 'M-Pesa Transaction Code';
    $refValue    = (string)($payment['mpesa_receipt_number'] ?: '—');
} else {
    $methodLabel = 'M-Pesa STK Push';
    $refLabel    = 'M-Pesa Receipt Number';
    $refValue    = (string)($payment['mpesa_receipt_number'] ?: '—');
}

$paidAt = $payment['manual_verified_at'] ?? $payment['updated_at'] ?? $payment['created_at'] ?? null;
$totalPrice   = (float)$booking['total_price'];
$amountPaid   = (float)$payment['amount'];
$purpose      = (($payment['payment_purpose'] ?? '') === 'balance'
    || str_starts_with((string)($payment['reference'] ?? ''), 'KAHUNA-BAL-'))
    ? 'balance'
    : ((($payment['payment_purpose'] ?? '') === 'deposit'
        || str_starts_with((string)($payment['reference'] ?? ''), 'KAHUNA-BK-')) ? 'deposit' : 'other');
$purposeLabel = $purpose === 'balance' ? 'Rental balance payment' : ($purpose === 'deposit' ? 'Booking deposit' : 'Payment');
$completedPayments = PaymentService::make()->completedPaymentsForBooking((int)$booking['id']);
$paidBefore = 0.0;
foreach ($completedPayments as $cp) {
    if ((int)$cp['id'] === (int)$payment['id']) break;
    $paidBefore += (float)$cp['amount'];
}
$paidAfter = min($totalPrice, $paidBefore + $amountPaid);
$balance = max(0, $totalPrice - $paidAfter);
$receiptNo = 'RCT-' . strtoupper((string)$booking['booking_ref']) . '-' . str_pad((string)$payment['id'], 4, '0', STR_PAD_LEFT);
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Receipt <?= e($receiptNo) ?> — <?= e($siteName) ?></title>
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<style>
  :root{--navy:#0b2e3d;--navy-dark:#071f2a;--amber:#e8502e;--muted:#667579;--border:#e5e9e8;}
  *{box-sizing:border-box;}
  body{font-family:Arial,Helvetica,sans-serif;color:#1B2426;background:#eef1f0;margin:0;padding:32px 16px;}
  .receipt{max-width:680px;margin:0 auto;background:#fff;border-radius:14px;overflow:hidden;box-shadow:0 12px 35px rgba(7,31,42,.10);}
  .receipt-head{background:linear-gradient(135deg,var(--navy-dark),var(--navy));color:#fff;padding:28px 32px;display:flex;justify-content:space-between;align-items:flex-start;gap:16px;flex-wrap:wrap;}
  .receipt-head h1{margin:0;font-size:1.3rem;letter-spacing:.03em;}
  .receipt-head p{margin:4px 0 0;font-size:.82rem;color:#cfe0e6;}
  .receipt-stamp{text-align:right;}
  .receipt-stamp span{display:block;font-size:.7rem;text-transform:uppercase;letter-spacing:.08em;color:#9fc1cb;}
  .receipt-stamp strong{font-size:1.05rem;}
  .status-pill{display:inline-block;margin-top:8px;padding:4px 12px;border-radius:999px;background:#1fae6033;color:#1fae60;font-size:.72rem;font-weight:700;text-transform:uppercase;letter-spacing:.05em;}
  .receipt-body{padding:28px 32px;}
  .grid{display:grid;grid-template-columns:1fr 1fr;gap:18px 24px;margin-bottom:22px;}
  .grid div span{display:block;font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);margin-bottom:3px;}
  .grid div strong{font-size:.94rem;color:var(--navy);word-break:break-word;}
  table.charges{width:100%;border-collapse:collapse;margin:18px 0;font-size:.92rem;}
  table.charges th{text-align:left;font-size:.68rem;text-transform:uppercase;letter-spacing:.06em;color:var(--muted);padding:8px 0;border-bottom:1px solid var(--border);}
  table.charges td{padding:10px 0;border-bottom:1px solid var(--border);}
  table.charges tr:last-child td{border-bottom:none;}
  .amount-col{text-align:right;}
  .totals{margin-top:6px;padding-top:12px;border-top:2px solid var(--navy);}
  .totals-row{display:flex;justify-content:space-between;padding:5px 0;font-size:.92rem;}
  .totals-row.grand{font-size:1.1rem;font-weight:800;color:var(--navy);padding-top:10px;}
  .totals-row.balance{color:var(--amber);font-weight:700;}
  .note-box{margin-top:22px;padding:14px 16px;background:#f8faf9;border:1px solid var(--border);border-radius:10px;font-size:.82rem;color:var(--muted);}
  .receipt-foot{padding:18px 32px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;font-size:.78rem;color:var(--muted);}
  .actions{max-width:680px;margin:16px auto 0;display:flex;gap:10px;justify-content:flex-end;flex-wrap:wrap;}
  .btn{display:inline-flex;align-items:center;gap:6px;padding:10px 18px;border-radius:8px;font-size:.85rem;font-weight:600;text-decoration:none;cursor:pointer;border:1px solid transparent;}
  .btn-primary{background:var(--navy);color:#fff;}
  .btn-outline{background:#fff;color:var(--navy);border-color:var(--navy);}
  @media print{
    body{background:#fff;padding:0;}
    .receipt{box-shadow:none;border-radius:0;max-width:100%;}
    .actions{display:none;}
  }
  @media (max-width:560px){
    .grid{grid-template-columns:1fr;}
    .receipt-head,.receipt-body,.receipt-foot{padding-left:20px;padding-right:20px;}
  }
</style>
</head>
<body>

<div class="receipt">
  <div class="receipt-head">
    <div>
      <h1><?= e($siteName) ?></h1>
      <p><?= e($siteAddress) ?></p>
      <p><?= e($sitePhone) ?><?= $sitePhone && $siteEmail ? ' &middot; ' : '' ?><?= e($siteEmail) ?></p>
    </div>
    <div class="receipt-stamp">
      <span>Receipt No.</span>
      <strong><?= e($receiptNo) ?></strong>
      <div class="status-pill">Paid</div>
    </div>
  </div>

  <div class="receipt-body">
    <div class="grid">
      <div><span>Billed To</span><strong><?= e($booking['full_name']) ?></strong></div>
      <div><span>Booking Reference</span><strong><?= e($booking['booking_ref']) ?></strong></div>
      <div><span>Phone</span><strong><?= e($booking['phone']) ?></strong></div>
      <div><span>Email</span><strong><?= e($booking['email']) ?></strong></div>
      <div><span>Vehicle</span><strong><?= e($booking['car_name']) ?></strong></div>
      <div><span>Rental Period</span><strong><?= e(date('d M Y', strtotime($booking['pickup_date']))) ?> &ndash; <?= e(date('d M Y', strtotime($booking['return_date']))) ?></strong></div>
    </div>

    <table class="charges">
      <thead><tr><th>Description</th><th class="amount-col">Amount</th></tr></thead>
      <tbody>
        <tr>
          <td><?= e($purposeLabel) ?> &mdash; <?= e($booking['car_name']) ?> (<?= (int)$booking['total_days'] ?> day<?= (int)$booking['total_days'] === 1 ? '' : 's' ?>)</td>
          <td class="amount-col"><?= money($amountPaid) ?></td>
        </tr>
      </tbody>
    </table>

    <div class="totals">
      <div class="totals-row"><span>Total booking value</span><span><?= money($totalPrice) ?></span></div>
      <div class="totals-row grand"><span>This payment</span><span><?= money($amountPaid) ?></span></div>
      <div class="totals-row"><span>Total paid after this payment</span><span><?= money($paidAfter) ?></span></div>
      <?php if ($balance > 0): ?>
        <div class="totals-row balance"><span>Balance remaining</span><span><?= money($balance) ?></span></div>
      <?php else: ?>
        <div class="totals-row" style="color:#187442;font-weight:700;"><span>Balance remaining</span><span>KES 0</span></div>
      <?php endif; ?>
    </div>

    <div class="grid" style="margin-top:24px;">
      <div><span>Payment Method</span><strong><?= e($methodLabel) ?></strong></div>
      <div><span>Date Paid</span><strong><?= $paidAt ? e(date('d M Y, H:i', strtotime($paidAt))) : '—' ?></strong></div>
      <div><span><?= e($refLabel) ?></span><strong><?= e($refValue) ?></strong></div>
      <div><span>Payer Phone</span><strong><?= e($payment['phone'] ?: '—') ?></strong></div>
    </div>

    <div class="note-box">
      This receipt confirms the <?= e(strtolower($purposeLabel)) ?> shown above. The balance remaining is calculated from all completed payments recorded for this booking.
      For questions about this payment, quote booking reference <strong><?= e($booking['booking_ref']) ?></strong> when contacting us.
    </div>
  </div>

  <div class="receipt-foot">
    <span>Generated <?= e(date('d M Y, H:i')) ?></span>
    <span><?= e($siteName) ?> &middot; This is a system-generated receipt.</span>
  </div>
</div>

<div class="actions">
  <a href="<?= e($backUrl) ?>" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Back</a>
  <button type="button" class="btn btn-primary" id="download-receipt"><i class="fa-solid fa-download"></i> Download PDF</button>
  <button type="button" class="btn btn-outline" onclick="window.print()"><i class="fa-solid fa-print"></i> Print</button>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
<script>
(function(){
  var btn=document.getElementById('download-receipt');
  if(!btn) return;
  btn.addEventListener('click',function(){
    if(typeof html2pdf==='undefined'){ window.print(); return; }
    var old=btn.innerHTML;
    btn.disabled=true; btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Preparing PDF…';
    html2pdf().set({
      margin:8,
      filename:<?= json_encode($receiptNo.'.pdf') ?>,
      image:{type:'jpeg',quality:0.96},
      html2canvas:{scale:2,useCORS:true,backgroundColor:'#ffffff'},
      jsPDF:{unit:'mm',format:'a4',orientation:'portrait'}
    }).from(document.querySelector('.receipt')).save().finally(function(){
      btn.disabled=false; btn.innerHTML=old;
    });
  });
})();
</script>
</body>
</html>
