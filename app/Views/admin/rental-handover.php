<?php view('admin/layout-header', ['seo'=>$seo]); ?>
<?php $b=$snapshot['booking']; $checkout=$snapshot['checkout']; $return=$snapshot['return']; $charges=$snapshot['charges']; ?>
<?php $handoverReady = $b['status'] === 'confirmed' && $remainingBalance <= 0.009; ?>

<div class="ops-toolbar">
  <div>
    <span class="section-eyebrow">RENTAL OPERATIONS</span>
    <h2>Handover &amp; return</h2>
    <p><?= e($b['booking_ref']) ?> · <?= e($b['car_name']) ?> · <?= e($b['full_name']) ?></p>
  </div>
  <div class="ops-toolbar-actions">
    <a href="<?= base_url('admin/bookings/'.$b['id']) ?>" class="btn btn-outline"><i class="fa-solid fa-arrow-left"></i> Booking</a>
    <a href="<?= base_url('admin/bookings/calendar') ?>" class="btn btn-primary"><i class="fa-solid fa-calendar-days"></i> Calendar</a>
  </div>
</div>

<div class="rental-status-banner status-<?= e($b['status']) ?>">
  <div><strong><?= e(ucfirst($b['status'])) ?></strong><span><?= e($b['car_name']) ?> · <?= e($b['phone']) ?></span></div>
  <div class="rental-timeline">
    <span class="<?= in_array($b['status'],['confirmed','ongoing','completed'],true)?'done':'' ?>">Confirmed</span>
    <span class="<?= in_array($b['status'],['ongoing','completed'],true)?'done':'' ?>">Checked out</span>
    <span class="<?= $b['status']==='completed'?'done':'' ?>">Returned</span>
  </div>
</div>

<div class="rental-grid">
  <div>
    <?php if($b['status']==='confirmed'): ?>
    <div class="card rental-action-card">
      <div class="card-header">
        <div>
          <h2><i class="fa-solid fa-key"></i> Vehicle checkout</h2>
          <small>Payment must be fully verified before the keys can be released.</small>
        </div>
      </div>

      <div class="handover-payment-gate <?= $handoverReady ? 'is-ready' : 'is-locked' ?>">
        <div class="handover-payment-gate-header">
          <span class="handover-gate-icon"><i class="fa-solid <?= $handoverReady ? 'fa-circle-check' : 'fa-lock' ?>"></i></span>
          <div>
            <strong><?= $handoverReady ? 'Handover ready' : 'Payment required before handover' ?></strong>
            <span><?= $handoverReady ? 'The rental balance is fully paid and verified.' : 'Complete the outstanding rental balance before completing the checkout inspection and releasing the vehicle.' ?></span>
          </div>
        </div>
        <div class="handover-payment-amounts">
          <div><small>Rental total</small><strong><?= money($b['total_price']) ?></strong></div>
          <div><small>Paid &amp; verified</small><strong><?= money($paidAmount) ?></strong></div>
          <div class="<?= $remainingBalance > 0.009 ? 'amount-due' : 'amount-paid' ?>"><small>Balance due</small><strong><?= money($remainingBalance) ?></strong></div>
        </div>

        <?php if(!$handoverReady): ?>
          <?php if($paystackEnabled): ?>
            <button id="handover-pay-btn" type="button" class="btn btn-primary btn-block">
              <i class="fa-solid fa-lock"></i> Pay <?= money($remainingBalance) ?> balance with Paystack
            </button>
            <p id="handover-pay-error" class="field-error" hidden></p>
            <div id="handover-pay-status" class="handover-pay-status" hidden aria-live="polite"></div>
            <small class="handover-payment-help">The secure Paystack checkout will open on this device. Handover remains locked until the payment is verified.</small>
          <?php else: ?>
            <div class="payment-state payment-state-pending">
              <i class="fa-solid fa-circle-exclamation"></i>
              <div><strong>Paystack is unavailable</strong><span>Enable online payments before completing this handover.</span></div>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>

      <div class="handover-checkout-form <?= $handoverReady ? '' : 'is-disabled' ?>">
        <?php if(!$handoverReady): ?>
          <div class="handover-locked-message"><i class="fa-solid fa-lock"></i> Checkout inspection and key release unlock after the remaining balance is paid and verified.</div>
        <?php endif; ?>
        <form action="<?= base_url('admin/bookings/'.$b['id'].'/checkout') ?>" method="post">
          <?= csrf_field() ?>
          <fieldset <?= $handoverReady ? '' : 'disabled' ?>>
            <div class="form-row">
              <div class="form-group"><label>Odometer (km)</label><input type="number" name="odometer_km" min="0" step="0.1" required placeholder="e.g. 84231.5"></div>
              <div class="form-group"><label>Fuel level (%)</label><input type="number" name="fuel_level" min="0" max="100" step="1" placeholder="e.g. 75"></div>
            </div>
            <div class="form-group"><label>Customer name / acknowledgement</label><input name="customer_name" value="<?= e($b['full_name']) ?>" required></div>
            <div class="form-group"><label>Condition notes</label><textarea name="condition_notes" rows="3" placeholder="Overall vehicle condition, tyres, windscreen, interior, accessories..."></textarea></div>
            <div class="form-group"><label>Existing damage</label><textarea name="damage_notes" rows="3" placeholder="Record dents, scratches, cracked glass or other existing marks. Use photos in your operational process if available."></textarea></div>
            <label class="rental-ack"><input type="checkbox" name="customer_acknowledged" value="1" required> Customer has reviewed and acknowledged the vehicle condition and agrees to the rental handover.</label>
            <button class="btn btn-primary" type="submit"><i class="fa-solid fa-key"></i> Complete Handover &amp; Release Keys</button>
          </fieldset>
        </form>
      </div>
    </div>
    <?php elseif($b['status']==='ongoing'): ?>
    <div class="card rental-action-card">
      <div class="card-header"><div><h2><i class="fa-solid fa-rotate-left"></i> Vehicle return</h2><small>Record actual return condition, mileage and fuel.</small></div></div>
      <form action="<?= base_url('admin/bookings/'.$b['id'].'/return') ?>" method="post">
        <?= csrf_field() ?>
        <div class="form-row">
          <div class="form-group"><label>Return odometer (km)</label><input type="number" name="odometer_km" min="0" step="0.1" required placeholder="e.g. 85012.5"></div>
          <div class="form-group"><label>Fuel level (%)</label><input type="number" name="fuel_level" min="0" max="100" step="1" placeholder="e.g. 50"></div>
        </div>
        <div class="form-group"><label>Customer name / acknowledgement</label><input name="customer_name" value="<?= e($b['full_name']) ?>"></div>
        <div class="form-group"><label>Return condition</label><textarea name="condition_notes" rows="3" placeholder="Condition at return, cleanliness, tyres, accessories..."></textarea></div>
        <div class="form-group"><label>New damage / issues</label><textarea name="damage_notes" rows="3" placeholder="Document any new damage or mechanical issues found."></textarea></div>
        <label class="rental-check"><input type="checkbox" name="needs_maintenance" value="1"> Send vehicle to maintenance after return</label>
        <label class="rental-ack"><input type="checkbox" name="customer_acknowledged" value="1"> Customer has reviewed the return condition.</label>
        <button class="btn btn-primary" type="submit"><i class="fa-solid fa-square-check"></i> Complete Return</button>
      </form>
    </div>
    <?php else: ?>
      <div class="card">
        <div class="empty-state rental-closed"><i class="fa-solid fa-circle-check"></i><strong>Rental handover is closed</strong><span>This booking is <?= e($b['status']) ?>. Inspection history remains available below.</span></div>
      </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header"><h2>Inspection history</h2></div>
      <?php if(!$checkout && !$return): ?>
        <div class="empty-state"><i class="fa-solid fa-clipboard-check"></i><strong>No inspections yet</strong><span>The checkout inspection will appear here.</span></div>
      <?php else: ?>
        <div class="inspection-grid">
        <?php foreach([['Checkout',$checkout],['Return',$return]] as [$label,$inspection]): if(!$inspection) continue; ?>
          <div class="inspection-card">
            <div class="inspection-title"><strong><?= $label ?></strong><span><?= e(date('d M Y H:i',strtotime($inspection['inspected_at']))) ?></span></div>
            <div class="inspection-metrics"><span><small>Odometer</small><strong><?= $inspection['odometer_km']!==null ? e(number_format((float)$inspection['odometer_km'],1).' km') : '—' ?></strong></span><span><small>Fuel</small><strong><?= $inspection['fuel_level']!==null ? e(number_format((float)$inspection['fuel_level'],0).'%') : '—' ?></strong></span></div>
            <p><small>Condition</small><br><?= nl2br(e($inspection['condition_notes']?:'No notes')) ?></p>
            <p><small>Damage</small><br><?= nl2br(e($inspection['damage_notes']?:'No damage recorded')) ?></p>
            <small>Inspected by <?= e($inspection['inspector_name']?:'Staff') ?><?= $inspection['customer_acknowledged']?' · Customer acknowledged':'' ?></small>
          </div>
        <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <aside>
    <div class="card handover-financial-card">
      <div class="card-header"><h2>Payment status</h2></div>
      <div class="handover-financial-status <?= $handoverReady ? 'paid' : 'due' ?>">
        <i class="fa-solid <?= $handoverReady ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i>
        <div><strong><?= $handoverReady ? 'Fully paid' : 'Balance due before keys' ?></strong><span><?= $handoverReady ? 'Payment verified.' : money($remainingBalance).' remaining' ?></span></div>
      </div>
      <dl class="rental-dl">
        <div><dt>Rental total</dt><dd><?= money($b['total_price']) ?></dd></div>
        <div><dt>Paid</dt><dd><?= money($paidAmount) ?></dd></div>
        <div><dt>Balance</dt><dd><strong><?= money($remainingBalance) ?></strong></dd></div>
      </dl>
    </div>

    <div class="card rental-side-card">
      <div class="card-header"><h2>Rental summary</h2></div>
      <dl class="rental-dl">
        <div><dt>Vehicle</dt><dd><?= e($b['car_name']) ?></dd></div>
        <div><dt>Customer</dt><dd><?= e($b['full_name']) ?></dd></div>
        <div><dt>Scheduled pickup</dt><dd><?= e(date('d M Y H:i',strtotime($b['pickup_date']))) ?></dd></div>
        <div><dt>Scheduled return</dt><dd><?= e(date('d M Y H:i',strtotime($b['return_date']))) ?></dd></div>
        <div><dt>Actual checkout</dt><dd><?= $b['actual_pickup_at'] ? e(date('d M Y H:i',strtotime($b['actual_pickup_at']))) : '—' ?></dd></div>
        <div><dt>Actual return</dt><dd><?= $b['actual_return_at'] ? e(date('d M Y H:i',strtotime($b['actual_return_at']))) : '—' ?></dd></div>
        <div><dt>Rental total</dt><dd><?= money($b['total_price']) ?></dd></div>
      </dl>
    </div>

    <div class="card">
      <div class="card-header"><div><h2>Additional charges</h2><small>Late return, damage, fuel, mileage or cleaning.</small></div></div>
      <div class="charge-total"><span>Outstanding</span><strong><?= money($snapshot['pending_charges']) ?></strong></div>
      <?php if($charges): foreach($charges as $charge): ?>
        <div class="charge-row"><div><strong><?= e($charge['description']) ?></strong><small><?= e(ucfirst(str_replace('_',' ',$charge['charge_type']))) ?> · <?= e(date('d M Y',strtotime($charge['created_at']))) ?></small></div><div><strong><?= money($charge['amount']) ?></strong><form action="<?= base_url('admin/charges/'.$charge['id'].'/status') ?>" method="post" class="charge-status-form"><?= csrf_field() ?><input type="hidden" name="booking_id" value="<?= (int)$b['id'] ?>"><select name="status" onchange="this.form.submit()"><option value="pending" <?= $charge['status']==='pending'?'selected':'' ?>>Pending</option><option value="paid" <?= $charge['status']==='paid'?'selected':'' ?>>Paid</option><option value="waived" <?= $charge['status']==='waived'?'selected':'' ?>>Waived</option></select></form></div></div>
      <?php endforeach; else: ?><p class="muted-small">No additional charges.</p><?php endif; ?>

      <?php if($b['status']!=='cancelled'): ?>
      <form action="<?= base_url('admin/bookings/'.$b['id'].'/charges') ?>" method="post" class="add-charge-form">
        <?= csrf_field() ?>
        <div class="form-group"><label>Charge type</label><select name="charge_type"><?php foreach(['late_return','extra_mileage','fuel','damage','cleaning','other'] as $type): ?><option value="<?= $type ?>"><?= e(ucwords(str_replace('_',' ',$type))) ?></option><?php endforeach; ?></select></div>
        <div class="form-group"><label>Description</label><input name="description" required placeholder="e.g. Fuel top-up"></div>
        <div class="form-group"><label>Amount (KES)</label><input type="number" name="amount" min="1" step="0.01" required></div>
        <button class="btn btn-outline btn-on-light btn-block" type="submit"><i class="fa-solid fa-plus"></i> Add Charge</button>
      </form>
      <?php endif; ?>
    </div>
  </aside>
</div>


<?php if($b['status']==='confirmed' && !$handoverReady && $paystackEnabled): ?>
<script src="https://js.paystack.co/v2/inline.js"></script>
<script>
(function(){
  const button = document.getElementById('handover-pay-btn');
  const errorEl = document.getElementById('handover-pay-error');
  const statusEl = document.getElementById('handover-pay-status');
  const base = <?= json_encode(base_url('')) ?>;
  const csrf = <?= json_encode(csrf_token()) ?>;
  const bookingId = <?= (int)$b['id'] ?>;
  const publicKey = <?= json_encode($paystackPublicKey) ?>;

  function showError(message){
    if(errorEl){ errorEl.hidden=false; errorEl.textContent=message; }
    if(statusEl){ statusEl.hidden=true; }
    if(button){ button.disabled=false; }
  }

  function showStatus(message){
    if(statusEl){ statusEl.hidden=false; statusEl.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> '+message; }
    if(errorEl){ errorEl.hidden=true; }
  }

  function verify(reference, attempts){
    fetch(base+'admin/payments/paystack/status/'+encodeURIComponent(reference), {
      headers:{'Accept':'application/json'}
    }).then(function(r){ return r.json(); }).then(function(data){
      if(data.status === 'completed' || Number(data.remaining_balance) <= 0.009){
        showStatus('Payment verified. Unlocking handover…');
        window.setTimeout(function(){ window.location.reload(); }, 600);
        return;
      }
      if(attempts <= 0){
        showError('Payment was not confirmed yet. The payment may still be processing. Check the payment status before trying again.');
        return;
      }
      window.setTimeout(function(){ verify(reference, attempts-1); }, attempts > 6 ? 750 : 1500);
    }).catch(function(){
      if(attempts <= 0){ showError('We could not verify the payment yet. Please refresh and check the payment status.'); return; }
      window.setTimeout(function(){ verify(reference, attempts-1); }, 1500);
    });
  }

  function openCheckout(accessCode, reference){
    if(typeof PaystackPop === 'undefined'){
      showError('Secure Paystack checkout could not be loaded. Check the internet connection and try again.');
      return;
    }
    const popup = new PaystackPop();
    popup.resumeTransaction(accessCode, {
      onSuccess:function(){ showStatus('Payment received. Verifying with Paystack…'); verify(reference, 12); },
      onCancel:function(){ showError('Payment was cancelled. The handover remains locked.'); },
      onError:function(err){ showError((err && err.message) || 'Paystack could not open the checkout. Please try again.'); }
    });
  }

  button?.addEventListener('click', function(){
    button.disabled=true;
    showStatus('Starting secure balance payment…');
    const form = new URLSearchParams();
    form.set('csrf_token', csrf);
    fetch(base+'admin/bookings/'+bookingId+'/pay-balance', {
      method:'POST',
      headers:{'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8','Accept':'application/json'},
      body:form.toString()
    }).then(function(r){ return r.json().then(function(d){ if(!r.ok) throw new Error(d.message || 'Could not start payment.'); return d; }); })
      .then(function(data){
        if(!data.access_code) throw new Error(data.message || 'Paystack did not return a checkout access code.');
        openCheckout(data.access_code, data.reference);
      })
      .catch(function(err){ showError(err.message || 'Could not start the balance payment.'); });
  });
})();
</script>
<?php endif; ?>

<style>
.handover-payment-gate{border:1px solid #e8e5dc;border-radius:14px;padding:16px;margin-bottom:18px;background:#faf9f5}
.handover-payment-gate.is-locked{border-color:#ead9a9;background:#fffcf2}
.handover-payment-gate.is-ready{border-color:#cde7d5;background:#f5fbf7}
.handover-gate-icon{width:40px;height:40px;border-radius:50%;display:grid;place-items:center;background:#fff;border:1px solid #e5e1d7}
.handover-payment-gate-header{display:flex;gap:12px;align-items:center}
.handover-payment-gate-header strong,.handover-payment-gate-header span{display:block}
.handover-payment-gate-header span{font-size:.78rem;color:var(--color-text-faint);margin-top:3px}
.handover-payment-amounts{display:grid;grid-template-columns:repeat(3,1fr);gap:10px;margin:15px 0}
.handover-payment-amounts>div{padding:10px;border-radius:10px;background:#fff;border:1px solid #ebe8df}
.handover-payment-amounts small,.handover-payment-amounts strong{display:block}
.handover-payment-amounts small{font-size:.68rem;color:var(--color-text-faint)}
.handover-payment-amounts strong{font-size:.9rem;margin-top:4px}
.handover-payment-amounts .amount-due strong{color:#9a6b00}
.handover-payment-amounts .amount-paid strong{color:#187442}
.handover-payment-help{display:block;color:var(--color-text-faint);font-size:.7rem;line-height:1.5;margin-top:8px}
.handover-checkout-form.is-disabled{opacity:.68}
.handover-locked-message{display:flex;gap:8px;align-items:center;padding:11px 12px;margin-bottom:12px;border-radius:10px;background:#f3f2ee;color:var(--color-text-faint);font-size:.76rem}
.handover-pay-status{margin-top:10px;padding:10px;border-radius:9px;background:#f3f7fb;font-size:.76rem}
.handover-financial-status{display:flex;gap:10px;align-items:center;padding:12px;border-radius:10px;margin-bottom:12px}
.handover-financial-status.paid{background:#f1faf4}
.handover-financial-status.due{background:#fffbef}
.handover-financial-status strong,.handover-financial-status span{display:block}
.handover-financial-status span{font-size:.72rem;color:var(--color-text-faint);margin-top:2px}
@media(max-width:700px){.handover-payment-amounts{grid-template-columns:1fr}.handover-payment-gate{padding:13px}}
</style>

<?php view('admin/layout-footer'); ?>

