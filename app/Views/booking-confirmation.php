<?php view('layouts/header', ['seo' => $seo]); ?>

<section>
  <div class="container text-center booking-success-page">
    <div class="booking-success-icon"><i class="fa-solid fa-check"></i></div>
    <?php $isConfirmed = $booking && ($booking['status'] ?? '') === 'confirmed'; ?>
    <h1><?= $isConfirmed ? 'Booking Confirmed' : 'Booking Request Received' ?><?= $booking ? ', ' . e($booking['first_name']) : '' ?>!</h1>
    <p class="booking-success-lead"><?= $isConfirmed ? 'Your reservation is confirmed. Use the payment option below to pay any amount still due.' : 'Your request is in our system. Use the payment option below to pay the required deposit.' ?></p>

    <?php if ($booking): ?>
      <div class="booking-reference-card">
        <span>Booking reference</span>
        <strong><?= e($booking['booking_ref']) ?></strong>
        <small><?= e($booking['car_name']) ?> &middot; <?= money($booking['total_price']) ?> total</small>
      </div>

      <?php if (!empty($publicToken)): ?>
        <div class="confirmation-account-note">
          <i class="fa-solid fa-link"></i>
          <span>Keep this private link to check your booking without logging in.</span>
          <a href="<?= base_url('booking/' . rawurlencode($booking['booking_ref']) . '/' . rawurlencode($publicToken)) ?>">Track Booking</a>
        </div>
      <?php endif; ?>

      <?php
        $paymentCompleted = $payment && (($payment['status'] ?? '') === 'completed');
        $paidAmount = 0.0;
        if (class_exists('PaymentService')) {
            try {
                $paidAmount = (float) PaymentService::make()->completedTotalForBooking((int)$booking['id']);
            } catch (Throwable $e) {
                $paidAmount = $paymentCompleted ? (float)($payment['amount'] ?? 0) : 0.0;
            }
        }
        $balance = max(0, round((float)$booking['total_price'] - $paidAmount, 2));
        $configuredDeposit = max(1, (int)round((float)$booking['total_price'] * $depositPct / 100));
        $depositRemaining = max(0, round($configuredDeposit - $paidAmount, 2));
        $paymentPurpose = $balance > 0 && $depositRemaining > 0 ? 'deposit' : ($balance > 0 ? 'balance' : 'paid');
        $nextPaymentAmount = $paymentPurpose === 'balance' ? $balance : min($balance, $depositRemaining);
        $paymentLabel = $paymentPurpose === 'balance' ? 'Pay remaining balance' : 'Pay deposit';
      ?>

      <?php if ($balance <= 0): ?>
        <div class="payment-state payment-state-success">
          <i class="fa-solid fa-circle-check"></i>
          <div>
            <strong>Payment complete</strong>
            <span>Your recorded payments cover the full booking total.</span>
          </div>
        </div>
        <?php if ($paymentCompleted && !empty($payment['reference'])): ?>
          <div class="payment-reference payment-reference-light">
            <span>Payment reference</span><strong><?= e($payment['reference']) ?></strong>
          </div>
        <?php endif; ?>
        <?php if ($paymentCompleted): ?>
          <a href="<?= base_url('book/' . (int)$booking['id'] . '/receipt') ?>" target="_blank" class="btn btn-outline btn-sm" style="margin-top:10px;">
            <i class="fa-solid fa-receipt"></i> View / Print Receipt
          </a>
        <?php endif; ?>

      <?php elseif ($paystackEnabled): ?>
        <?php if ($paidAmount > 0): ?>
          <div class="payment-state payment-state-success payment-state-partial">
            <i class="fa-solid fa-circle-check"></i>
            <div>
              <strong>Payment received: <?= money($paidAmount) ?></strong>
              <span>Your payment has been confirmed. <?= money($balance) ?> remains on this booking.</span>
            </div>
          </div>
        <?php endif; ?>
        <div class="payment-card payment-card-paystack">
          <div class="payment-card-header">
            <span class="payment-icon"><i class="fa-solid fa-credit-card"></i></span>
            <div>
              <span class="section-eyebrow">SECURE ONLINE PAYMENT</span>
              <h2><?= e($paymentLabel) ?> · <?= money($nextPaymentAmount) ?></h2>
              <p>Your booking is <?= e(ucfirst((string)$booking['status'])) ?>. Pay the amount below to secure/complete your rental. Current balance: <strong><?= money($balance) ?></strong>.</p>
            </div>
          </div>

          <div class="payment-paystack-intro">
            <div class="payment-paystack-brand">
              <span class="payment-paystack-lock"><i class="fa-solid fa-shield-halved"></i></span>
              <div><strong><?= e($paystackLabel) ?></strong><span>Powered by Paystack</span></div>
            </div>
            <p><?= e($paystackDescription) ?></p>
          </div>

          <button id="paystack-pay-btn" type="button" class="btn btn-primary btn-block payment-pay-btn">
            <i class="fa-solid fa-lock"></i> <?= e($paymentLabel) ?> <?= money($nextPaymentAmount) ?> securely
          </button>
          <p id="paystack-error" class="field-error" hidden></p>

          <div id="paystack-waiting" class="payment-waiting payment-inline-state" hidden aria-live="polite">
            <i class="fa-solid fa-spinner fa-spin"></i>
            <strong>Opening secure checkout…</strong>
            <span>The payment window will open automatically.</span>
          </div>

          <div class="payment-trust-row">
            <span><i class="fa-solid fa-shield-halved"></i> Secure</span>
            <span><i class="fa-solid fa-bolt"></i> Fast</span>
            <span><i class="fa-solid fa-credit-card"></i> Multiple methods</span>
          </div>
        </div>

      <?php else: ?>
        <div class="payment-state payment-state-pending">
          <i class="fa-solid fa-clock"></i>
          <div>
            <strong>Online payment is currently unavailable</strong>
            <span>Your booking is saved. Our team will contact you with payment instructions.</span>
          </div>
        </div>
        <?php if (!empty($publicToken)): ?>
          <a class="btn btn-primary btn-sm" style="margin-top:10px;" href="<?= base_url('booking/' . rawurlencode($booking['booking_ref']) . '/' . rawurlencode($publicToken)) ?>">
            <i class="fa-solid fa-credit-card"></i> View Payment Options
          </a>
        <?php endif; ?>
      <?php endif; ?>
    <?php endif; ?>

    <?php if (CustomerAuth::check()): ?>
      <div class="confirmation-account-note">
        <i class="fa-solid fa-circle-user"></i>
        <span>Track this rental anytime from your customer account.</span>
        <a href="<?= base_url('account/bookings/' . (int)$booking['id']) ?>">Open My Booking</a>
      </div>
    <?php endif; ?>

    <div class="hero-actions booking-actions">
      <a href="<?= base_url('fleet') ?>" class="btn btn-dark">Browse More Cars</a>
      <a href="<?= base_url('/') ?>" class="btn btn-outline" style="color:var(--color-primary-900);border-color:var(--color-primary-900);">Back to Home</a>
    </div>
  </div>
</section>

<?php if ($booking && $paystackEnabled): ?>
<script src="https://js.paystack.co/v2/inline.js"></script>
<script>
(function(){
  var base=<?= json_encode(base_url('')) ?>;
  var bookingId=<?= (int)$booking['id'] ?>;
  var csrf=<?= json_encode(csrf_token()) ?>;
  var btn=document.getElementById('paystack-pay-btn');
  if(!btn) return;
  var errorEl=document.getElementById('paystack-error');
  var waiting=document.getElementById('paystack-waiting');
  var amountLabel=<?= json_encode($paymentLabel . ' ' . money($nextPaymentAmount) . ' with Paystack') ?>;
  var original='<i class="fa-solid fa-lock"></i> '+amountLabel;

  function reset(){
    document.body.classList.remove('paystack-checkout-active');
    btn.disabled=false;
    btn.innerHTML=original;
    if(waiting) waiting.hidden=true;
  }
  function error(msg){
    reset();
    if(errorEl){ errorEl.textContent=msg; errorEl.hidden=false; }
  }
  function verify(reference, attempts){
    fetch(base+'payments/paystack/status/'+encodeURIComponent(reference)+'?public_token='+encodeURIComponent(<?= json_encode($publicToken ?? '') ?>), {headers:{'Accept':'application/json'}})
      .then(function(r){return r.json().then(function(d){return d;});})
      .then(function(d){
        if(d.status==='completed'){
          if(waiting){waiting.hidden=false;waiting.innerHTML='<i class="fa-solid fa-circle-check"></i><strong>Payment confirmed</strong><span>Updating your booking…</span>';}
          setTimeout(function(){window.location.reload();},500);
          return;
        }
        if(attempts<=0){
          document.body.classList.remove('paystack-checkout-active');
          btn.disabled=false;
          btn.innerHTML='<i class="fa-solid fa-rotate"></i> Try payment again';
          if(waiting){waiting.hidden=false;waiting.innerHTML='<i class="fa-solid fa-clock"></i><strong>Payment is still being confirmed</strong><span>You can check again shortly. Your booking will update automatically after confirmation.</span>';}
          return;
        }
        var delay = attempts >= 6 ? 750 : 1500;
        setTimeout(function(){verify(reference,attempts-1);},delay);
      })
      .catch(function(){
        if(attempts>0){setTimeout(function(){verify(reference,attempts-1);},2000);return;}
        btn.disabled=false;btn.innerHTML='<i class="fa-solid fa-rotate"></i> Try payment again';
        if(waiting){waiting.hidden=false;waiting.innerHTML='<i class="fa-solid fa-clock"></i><strong>Confirmation is taking longer</strong><span>Please check the payment status again in a moment.</span>';}
      });
  }
  function openCheckout(accessCode, reference){
    if(typeof PaystackPop==='undefined') throw new Error('Paystack checkout could not be loaded. Please check your internet connection.');
    var popup=new PaystackPop();
    document.body.classList.add('paystack-checkout-active');
    popup.resumeTransaction(accessCode,{
      onLoad:function(){
        // The real Paystack overlay is now visible. Do not keep a second
        // loader on the page that forces the customer to scroll.
        if(waiting) waiting.hidden=true;
        btn.disabled=true;
        btn.innerHTML='<i class="fa-solid fa-shield-halved"></i> Secure checkout open';
      },
      onSuccess:function(){
        document.body.classList.remove('paystack-checkout-active');
        if(waiting){waiting.hidden=false;waiting.innerHTML='<i class="fa-solid fa-circle-check"></i><strong>Payment received</strong><span>Confirming your transaction securely…</span>';}
        verify(reference,10);
      },
      onCancel:function(){reset();},
      onError:function(err){error((err&&err.message)||'Paystack could not load the checkout. Please try again.');}
    });
  }
  btn.addEventListener('click',function(){
    if(errorEl) errorEl.hidden=true;
    btn.disabled=true;
    btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Starting secure checkout…';
    if(waiting){waiting.hidden=false;waiting.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i><strong>Starting secure checkout…</strong><span>Paystack will appear automatically.</span>';}
    fetch(base+'book/'+bookingId+'/pay/paystack',{
      method:'POST',headers:{'Content-Type':'application/x-www-form-urlencoded'},
      body:new URLSearchParams({csrf_token:csrf,public_token:<?= json_encode($publicToken ?? '') ?>}).toString()
    }).then(function(r){return r.json().then(function(d){if(!r.ok && d.status!=='completed')throw new Error(d.message||'Could not start Paystack checkout.');return d;});})
      .then(function(d){
        if(d.status==='completed'){window.location.reload();return;}
        if(!d.access_code)throw new Error(d.message||'Paystack did not return a checkout access code.');
        openCheckout(d.access_code,d.reference);
      }).catch(function(e){error(e.message||'Unable to start payment.');});
  });
})();
</script>
<?php endif; ?>


<?php view('layouts/footer'); ?>
