<?php view('layouts/header', ['seo'=>$seo]); ?>
<section class="account-booking-page">
  <div class="container">
    <div class="account-booking-top">
      <div><span class="section-eyebrow">MY RENTAL</span><h1><?= e($booking['car_name']) ?></h1><p><?= e($booking['booking_ref']) ?> · <?= e(ucfirst($booking['status'])) ?></p></div>
      <div class="account-booking-actions"><a href="<?= base_url('account/dashboard') ?>" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> My Bookings</a><a href="<?= base_url('account/bookings/'.$booking['id'].'/agreement') ?>" target="_blank" class="btn btn-primary"><i class="fa-solid fa-file-contract"></i> Rental Agreement</a></div>
    </div>

    <div class="customer-status-track">
      <div class="<?= in_array($booking['status'],['pending','confirmed','ongoing','completed'],true)?'done':'' ?>"><span>1</span><strong>Request</strong><small>Received</small></div>
      <div class="<?= in_array($booking['status'],['confirmed','ongoing','completed'],true)?'done':'' ?>"><span>2</span><strong>Confirmed</strong><small>Reservation secured</small></div>
      <div class="<?= in_array($booking['status'],['ongoing','completed'],true)?'done':'' ?>"><span>3</span><strong>Pickup</strong><small>Vehicle handed over</small></div>
      <div class="<?= $booking['status']==='completed'?'done':'' ?>"><span>4</span><strong>Returned</strong><small>Rental completed</small></div>
    </div>

    <div class="account-booking-grid">
      <main>
        <div class="card customer-rental-card">
          <div class="customer-car-heading">
            <?php if(!empty($booking['image_path'])): ?><img src="<?= e(car_image_url($booking['image_path'])) ?>" alt="<?= e($booking['car_name']) ?>"><?php else: ?><div class="customer-car-placeholder"><i class="fa-solid fa-car"></i></div><?php endif; ?>
            <div><span class="section-eyebrow"><?= e($booking['driver_option']==='with_driver'?'WITH CHAUFFEUR':'SELF-DRIVE') ?></span><h2><?= e($booking['car_name']) ?></h2><p><?= e($booking['brand'].' '.$booking['model']) ?><?= $booking['plate_number']?' · '.e($booking['plate_number']):'' ?></p></div>
          </div>
          <div class="customer-trip-grid">
            <div><small>Pickup</small><strong><?= e(date('D, d M Y · H:i',strtotime($booking['pickup_date']))) ?></strong><span><?= e($booking['pickup_location']) ?></span></div>
            <div><small>Return</small><strong><?= e(date('D, d M Y · H:i',strtotime($booking['return_date']))) ?></strong><span><?= e($booking['dropoff_location']) ?></span></div>
            <div><small>Rental period</small><strong><?= (int)$booking['total_days'] ?> day<?= (int)$booking['total_days']===1?'':'s' ?></strong><span>Scheduled rental</span></div>
          </div>
        </div>

        <div class="card customer-instructions">
          <div class="card-header"><div><h2>Pickup instructions</h2><small>Keep these handy on rental day.</small></div><i class="fa-solid fa-location-dot"></i></div>
          <p><?= nl2br(e($pickupInstructions)) ?></p>
          <div class="instruction-box"><strong>Return</strong><p><?= nl2br(e($returnInstructions)) ?></p></div>
        </div>

        <?php if(!empty($snapshot['checkout']) || !empty($snapshot['return'])): ?>
        <div class="card">
          <div class="card-header"><h2>Rental inspection</h2></div>
          <div class="customer-inspection-grid">
            <?php foreach([['Pickup inspection',$snapshot['checkout']],['Return inspection',$snapshot['return']]] as [$label,$inspection]): if(!$inspection) continue; ?>
            <div><strong><?= $label ?></strong><span>Odometer: <?= $inspection['odometer_km']!==null?e(number_format((float)$inspection['odometer_km'],1).' km'):'—' ?></span><span>Fuel: <?= $inspection['fuel_level']!==null?e(number_format((float)$inspection['fuel_level'],0).'%'):'—' ?></span><p><?= nl2br(e($inspection['damage_notes']?:$inspection['condition_notes']?:'No issues recorded.')) ?></p></div>
            <?php endforeach; ?>
          </div>
        </div>
        <?php endif; ?>
      </main>

      <aside>
        <div class="card customer-payment-card payment-ui-card">
          <div class="payment-ui-heading">
            <div class="payment-ui-icon"><i class="fa-solid fa-shield-halved"></i></div>
            <div>
              <span class="section-eyebrow">SECURE PAYMENT</span>
              <h2>Booking payment</h2>
              <p>Track what you have paid and complete any amount still due.</p>
            </div>
          </div>

          <?php
            $accountPaid = PaymentService::make()->completedTotalForBooking((int)$booking['id']);
            $accountBalance = max(0, round((float)$booking['total_price'] - $accountPaid, 2));
            $accountDepositTarget = round((float)$booking['total_price'] * ((float)$depositPct / 100), 2);
            $accountDepositRemaining = max(0, round($accountDepositTarget - $accountPaid, 2));
            $accountNextPurpose = $accountBalance <= 0 ? 'paid' : ($accountDepositRemaining > 0 ? 'deposit' : 'balance');
            $accountNextAmount = $accountNextPurpose === 'balance' ? $accountBalance : $accountDepositRemaining;
          ?>
          <div class="payment-due-box">
            <span>Booking total</span>
            <strong><?= money($booking['total_price']) ?></strong>
            <div><span>Paid</span><b><?= money($accountPaid) ?></b></div>
            <div><span>Balance</span><b><?= money($accountBalance) ?></b></div>
          </div>

          <?php if($accountBalance <= 0): ?>
            <div class="payment-result payment-result-success">
              <span class="payment-result-icon"><i class="fa-solid fa-circle-check"></i></span>
              <div><strong>Payment complete</strong><span>Your full booking amount has been received.</span></div>
            </div>
            <?php if(!empty($payment['reference'])): ?><div class="payment-reference"><span>Payment reference</span><strong><?= e($payment['reference']) ?></strong></div><?php endif; ?>
            <a href="<?= base_url('book/' . (int)$booking['id'] . '/receipt') ?>" target="_blank" class="btn btn-outline btn-sm" style="margin-top:10px;"><i class="fa-solid fa-receipt"></i> View / Print Receipt</a>
                <?php $bookingPayments = PaymentService::make()->completedPaymentsForBooking((int)$booking['id']); ?>
                <?php if (count($bookingPayments) > 1): ?>
                  <div class="payment-history" style="margin-top:14px;">
                    <strong style="display:block;margin-bottom:8px;">Payment receipts</strong>
                    <?php foreach ($bookingPayments as $bp): ?>
                      <?php $bpPurpose = (($bp['payment_purpose'] ?? '') === 'balance' || str_starts_with((string)($bp['reference'] ?? ''), 'KAHUNA-BAL-')) ? 'Balance payment' : 'Booking deposit'; ?>
                      <a href="<?= base_url('book/' . (int)$booking['id'] . '/receipt/' . (int)$bp['id']) ?>" target="_blank" class="btn btn-outline btn-sm" style="margin:3px 4px 3px 0;">
                        <i class="fa-solid fa-download"></i> <?= e($bpPurpose) ?> · <?= money($bp['amount']) ?>
                      </a>
                    <?php endforeach; ?>
                  </div>
                <?php endif; ?>
          <?php else: ?>
            <?php if($payment && ($payment['status'] ?? '') === 'failed' && ($payment['result_code'] ?? '') !== 'superseded'): ?>
              <div class="payment-result payment-result-failed">
                <span class="payment-result-icon"><i class="fa-solid fa-circle-exclamation"></i></span>
                <div><strong>Payment not completed</strong><span><?= e($payment['result_desc'] ?: 'The previous payment attempt was not completed. You can try again.') ?></span></div>
              </div>
            <?php endif; ?>

            <?php $paymentBlocked = in_array($booking['status'], ['completed','cancelled'], true); ?>
            <?php if(!$paymentBlocked): ?>
              <div class="payment-action-panel">
                <div class="payment-action-copy">
                  <span><?= $accountNextPurpose === 'balance' ? 'Complete your rental payment' : 'Secure your reservation' ?></span>
                  <small><?= $accountNextPurpose === 'balance' ? 'Pay the remaining balance securely through Paystack.' : 'Pay the required deposit securely through Paystack.' ?></small>
                </div>
                <?php if($paystackEnabled): ?>
                  <button id="account-paystack-btn" type="button" class="btn btn-primary btn-block payment-pay-btn">
                    <i class="fa-solid fa-lock"></i> <?= $accountNextPurpose === 'balance' ? 'Pay balance' : 'Pay deposit' ?> <?= money($accountNextAmount) ?> with Paystack
                  </button>
                  <p id="account-paystack-error" class="field-error" hidden></p>
                  <div id="account-paystack-waiting" class="payment-waiting payment-inline-state" hidden aria-live="polite">
                    <i class="fa-solid fa-spinner fa-spin"></i>
                    <strong>Opening secure checkout…</strong>
                    <span>Paystack will appear over this page.</span>
                  </div>
                <?php endif; ?>
                <?php if (!$paystackEnabled): ?>
                  <div class="payment-result payment-result-pending">
                    <span class="payment-result-icon"><i class="fa-solid fa-clock"></i></span>
                    <div><strong>Online payment temporarily unavailable</strong><span>Please check again later or contact us for assistance.</span></div>
                  </div>
                <?php endif; ?>
              </div>
            <?php endif; ?>
          <?php endif; ?>
        </div>

        <?php if($booking['status']==='completed' && setting('reviews','request_enabled','1')==='1'): $reviewLinks=ReviewService::make()->reviewLinks(); if(!empty($reviewLinks['google'])||!empty($reviewLinks['tripadvisor'])): ?><div class="card customer-review-request-card"><span class="section-eyebrow">THANK YOU</span><h2>How was your Big Kahuna experience?</h2><p>Your feedback helps other travelers choose with confidence.</p><div class="customer-review-actions"><?php if(!empty($reviewLinks['google'])):?><a class="btn btn-primary btn-block" href="<?=e($reviewLinks['google'])?>" target="_blank" rel="noopener"><i class="fa-brands fa-google"></i> Review us on Google</a><?php endif;?><?php if(!empty($reviewLinks['tripadvisor'])):?><a class="btn btn-outline btn-block" href="<?=e($reviewLinks['tripadvisor'])?>" target="_blank" rel="noopener"><i class="fa-solid fa-plane"></i> Review us on Tripadvisor</a><?php endif;?></div></div><?php endif;endif; ?>

<div class="card customer-help-card">
          <h2>Need help?</h2>
          <p>Talk to Big Kahuna about your booking.</p>
          <?php if($whatsapp): ?><a class="btn btn-whatsapp btn-block" target="_blank" rel="noopener" href="https://wa.me/<?= e($whatsapp) ?>?text=Hi%20Big%20Kahuna%2C%20I%20need%20help%20with%20booking%20<?= rawurlencode($booking['booking_ref']) ?>."><i class="fa-brands fa-whatsapp"></i> WhatsApp Us</a><?php endif; ?>
          <?php if($sitePhone): ?><a class="customer-phone-link" href="tel:<?= e($sitePhone) ?>"><i class="fa-solid fa-phone"></i> <?= e($sitePhone) ?></a><?php endif; ?>
        </div>
      </aside>
    </div>
  </div>
</section>

<?php if($booking && $paystackEnabled && $accountBalance > 0 && !in_array($booking['status'], ['completed','cancelled'], true)): ?>
<script src="https://js.paystack.co/v2/inline.js"></script>
<script>
(function(){
  var btn=document.getElementById('account-paystack-btn');
  if(!btn) return;
  var errorEl=document.getElementById('account-paystack-error');
  var waiting=document.getElementById('account-paystack-waiting');
  var base=<?= json_encode(base_url('')) ?>;
  var bookingId=<?= (int)$booking['id'] ?>;
  var csrf=<?= json_encode(csrf_token()) ?>;
  var amountLabel=<?= json_encode(($accountNextPurpose === 'balance' ? 'Pay balance ' : 'Pay deposit ') . money($accountNextAmount) . ' with Paystack') ?>;
  var original='<i class="fa-solid fa-lock"></i> '+amountLabel;

  function resetButton(){
    btn.disabled=false;
    btn.innerHTML=original;
    document.body.classList.remove('paystack-checkout-active');
    if(waiting) waiting.hidden=true;
  }

  function showError(message){
    resetButton();
    if(errorEl){ errorEl.textContent=message; errorEl.hidden=false; }
  }

  function verify(reference, attempts){
    return fetch(base+'payments/paystack/status/'+encodeURIComponent(reference), {headers:{'Accept':'application/json'}})
      .then(function(r){ return r.json().then(function(d){ return {ok:r.ok || r.status===202, data:d}; }); })
      .then(function(result){
        var d=result.data || {};
        if(d.status==='completed'){
          if(waiting){ waiting.hidden=false; waiting.innerHTML='<i class="fa-solid fa-circle-check"></i><strong>Payment confirmed</strong><span>Your booking is being updated.</span>'; }
          setTimeout(function(){ window.location.reload(); }, 500);
          return true;
        }
        if((attempts||0) <= 0){
          if(waiting){ waiting.hidden=false; waiting.innerHTML='<i class="fa-solid fa-clock"></i><strong>Payment is still being confirmed</strong><span>You can leave this page. Your booking will update once Paystack confirms the transaction.</span>'; }
          btn.disabled=false;
          btn.innerHTML='<i class="fa-solid fa-rotate"></i> Try payment again';
          return false;
        }
        setTimeout(function(){verify(reference,(attempts||0)-1);},2000);
        return false;
      })
      .catch(function(){
        if((attempts||0)>0){ setTimeout(function(){verify(reference,(attempts||0)-1);},2000); return false; }
        if(waiting){ waiting.hidden=true; }
        btn.disabled=false;
        btn.innerHTML='<i class="fa-solid fa-rotate"></i> Try payment again';
        return false;
      });
  }

  function start(accessCode, reference){
    if(typeof PaystackPop==='undefined') throw new Error('Paystack checkout could not be loaded. Please check your connection and try again.');
    var popup=new PaystackPop();
    document.body.classList.add('paystack-checkout-active');
    popup.resumeTransaction(accessCode, {
      onLoad:function(){
        // Paystack owns the visible checkout. Remove the site's loader now.
        if(waiting) waiting.hidden=true;
        btn.disabled=true;
        btn.innerHTML='<i class="fa-solid fa-shield-halved"></i> Secure checkout open';
      },
      onSuccess:function(){
        document.body.classList.remove('paystack-checkout-active');
        if(waiting){ waiting.hidden=false; waiting.innerHTML='<i class="fa-solid fa-circle-check"></i><strong>Payment received</strong><span>Confirming your transaction securely…</span>'; }
        verify(reference,10);
      },
      onCancel:function(){ resetButton(); },
      onError:function(err){ showError((err&&err.message)||'Paystack could not load the checkout. Please try again.'); }
    });
  }

  btn.addEventListener('click',function(){
    if(errorEl) errorEl.hidden=true;
    btn.disabled=true;
    btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Starting secure checkout…';
    if(waiting){ waiting.hidden=false; waiting.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i><strong>Starting secure checkout…</strong><span>Paystack will appear automatically.</span>'; }

    fetch(base+'book/'+bookingId+'/pay/paystack',{
      method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:new URLSearchParams({csrf_token:csrf}).toString()
    }).then(function(r){return r.json().then(function(d){if(!r.ok && d.status!=='completed') throw new Error(d.message||'Could not start Paystack checkout.');return d;});})
      .then(function(d){
        if(d.status==='completed') { window.location.reload(); return; }
        if(!d.access_code) throw new Error(d.message||'Paystack did not return a checkout access code.');
        start(d.access_code, d.reference);
      })
      .catch(function(err){showError(err.message||'Unable to start payment.');});
  });
})();
</script>
<?php endif; ?>
<?php view('layouts/footer'); ?>
