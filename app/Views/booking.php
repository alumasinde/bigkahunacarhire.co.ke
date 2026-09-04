<?php
view('layouts/header', ['seo' => $seo]);
$old = $_SESSION['old_input'] ?? [];
$errors = $_SESSION['errors'] ?? [];
unset($_SESSION['old_input'], $_SESSION['errors']);
$selectedCarId = (int)($old['car_id'] ?? $carId ?? 0);
$carServiceForFees = CarService::make();
$depositPct = max(1, min(100, (float)setting('paystack', 'deposit_percentage', '30')));
$w = static fn(string $key, string $default = ''): string => setting('website', $key, $default);
?>

<section class="booking-v2-hero">
  <div class="container">
    <div class="booking-v2-eyebrow"><?= e($w('booking_eyebrow')) ?></div>
    <h1><?= e($w('booking_title')) ?></h1>
    <p><?= e($w('booking_intro')) ?></p>
  </div>
</section>

<section class="booking-v2-section">
  <div class="container booking-v2-container">
    <div class="booking-v2-progress" aria-label="Booking progress">
      <div class="booking-v2-progress-item is-active" data-step-indicator="1"><span>1</span><strong><?= e($w('booking_step_trip')) ?></strong></div>
      <i class="fa-solid fa-chevron-right"></i>
      <div class="booking-v2-progress-item" data-step-indicator="2"><span>2</span><strong><?= e($w('booking_step_details')) ?></strong></div>
      <i class="fa-solid fa-chevron-right"></i>
      <div class="booking-v2-progress-item" data-step-indicator="3"><span>3</span><strong><?= e($w('booking_step_confirm')) ?></strong></div>
    </div>

    <form id="booking-form" action="<?= base_url('book') ?>" method="post" novalidate>
      <?= csrf_field() ?>
      <input type="hidden" id="car_id" name="car_id" value="<?= $selectedCarId ?: '' ?>">

      <div class="booking-v2-layout">
        <main class="booking-v2-main">
          <section class="booking-v2-card booking-panel-v2 is-active" data-step="1">
            <div class="booking-v2-heading">
              <div class="booking-v2-step-number">01</div>
              <div><h2><?= e($w('booking_plan_title')) ?></h2><p><?= e($w('booking_plan_text')) ?></p></div>
            </div>

            <div class="booking-v2-fieldset">
              <div class="booking-v2-label-row"><label><?= e($w('booking_choose_car_label')) ?></label><span><?= e($w('booking_availability_hint')) ?></span></div>
              <div class="booking-availability-message" id="booking-availability-message" role="status" aria-live="polite" hidden></div>
              <div class="booking-car-grid" id="booking-car-grid">
                <?php foreach ($cars as $car):
                  $fee = (float)$carServiceForFees->effectiveChauffeurFee($car);
                  $isSelected = (int)$car['id'] === $selectedCarId;
                ?>
                  <button type="button" class="booking-car-option<?= $isSelected ? ' is-selected' : '' ?>" data-car-id="<?= (int)$car['id'] ?>" data-price="<?= e((string)$car['price_per_day']) ?>" data-chauffeur-fee="<?= e((string)$fee) ?>" data-name="<?= e($car['name']) ?>" data-location="<?= e($car['location']) ?>" data-fleet-status="<?= e((string)$car['status']) ?>" aria-pressed="<?= $isSelected ? 'true' : 'false' ?>">
                    <span class="booking-car-image">
                      <?php if (!empty($car['image_path'])): ?><img src="<?= e(car_image_url($car['image_path'])) ?>" alt="<?= e($car['name']) ?>" loading="lazy" decoding="async"><?php else: ?><i class="fa-solid fa-car"></i><?php endif; ?>
                    </span>
                    <span class="booking-car-content">
                      <strong><?= e($car['name']) ?></strong>
                      <span class="booking-car-specs"><span><i class="fa-solid fa-users"></i><?= (int)$car['seats'] ?></span><span><i class="fa-solid fa-gears"></i><?= e(ucfirst($car['transmission'])) ?></span></span>
                      <span class="booking-car-price"><b><?= money($car['price_per_day']) ?></b> / day</span>
                      <span class="booking-car-availability" data-availability-label>Checking dates…</span>
                    </span>
                    <span class="booking-car-check"><i class="fa-solid fa-check"></i></span>
                  </button>
                <?php endforeach; ?>
              </div>
              <?php if (!empty($errors['car_id'])): ?><span class="field-error"><?= e($errors['car_id']) ?></span><?php endif; ?>
            </div>

            <div class="booking-v2-fieldset">
              <div class="booking-v2-label-row"><label><?= e($w('booking_when_where_label')) ?></label><span><?= e($w('booking_when_where_hint')) ?></span></div>
              <div class="form-row">
                <div class="form-group"><label for="pickup_location">Pickup location</label><input type="text" id="pickup_location" name="pickup_location" placeholder="e.g. JKIA, Nairobi" value="<?= e($old['pickup_location'] ?? '') ?>" autocomplete="street-address" required></div>
                <div class="form-group"><label for="dropoff_location">Return location</label><input type="text" id="dropoff_location" name="dropoff_location" placeholder="e.g. Westlands, Nairobi" value="<?= e($old['dropoff_location'] ?? '') ?>" autocomplete="street-address" required></div>
              </div>
              <?php if (!empty($locationShortcuts)): ?>
                <div class="location-shortcuts" aria-label="<?= e($w('booking_popular_locations_label')) ?>">
                  <?php foreach ($locationShortcuts as $location): ?>
                    <button type="button" data-location-value="<?= e($location) ?>"><?= e($location) ?></button>
                  <?php endforeach; ?>
                </div>
              <?php endif; ?>
              <div class="form-row booking-v2-dates">
                <div class="form-group"><label for="pickup_date">Pickup</label><input type="datetime-local" id="pickup_date" name="pickup_date" value="<?= e($old['pickup_date'] ?? '') ?>" required></div>
                <div class="form-group"><label for="return_date">Return</label><input type="datetime-local" id="return_date" name="return_date" value="<?= e($old['return_date'] ?? '') ?>" required></div>
              </div>
              <?php if (!empty($errors['pickup_location'])): ?><span class="field-error"><?= e($errors['pickup_location']) ?></span><?php endif; ?>
              <?php if (!empty($errors['dropoff_location'])): ?><span class="field-error"><?= e($errors['dropoff_location']) ?></span><?php endif; ?>
              <?php if (!empty($errors['dates'])): ?><span class="field-error"><?= e($errors['dates']) ?></span><?php endif; ?>
            </div>

            <div class="booking-v2-fieldset">
              <div class="booking-v2-label-row"><label>How will you travel?</label><span>Choose one</span></div>
              <div class="driver-choice-grid">
                <label class="driver-option-card"><input type="radio" name="driver_option" value="self_drive" <?= ($old['driver_option'] ?? 'self_drive') === 'self_drive' ? 'checked' : '' ?>><span><i class="fa-solid fa-steering-wheel"></i><strong><?= e($w('booking_self_drive_title')) ?></strong><small><?= e($w('booking_self_drive_text')) ?></small></span></label>
                <label class="driver-option-card"><input type="radio" name="driver_option" value="with_driver" <?= ($old['driver_option'] ?? '') === 'with_driver' ? 'checked' : '' ?>><span><i class="fa-solid fa-user-tie"></i><strong><?= e($w('booking_chauffeur_title')) ?></strong><small><?= e($w('booking_chauffeur_text')) ?></small></span></label>
              </div>
              <span id="chauffeur-fee-note" class="price-note"></span>
              <?php if (!empty($errors['driver_option'])): ?><span class="field-error"><?= e($errors['driver_option']) ?></span><?php endif; ?>
            </div>

            <div class="booking-v2-actions"><span></span><button type="button" class="btn btn-primary" data-next><?= e($w('booking_continue_label')) ?> <i class="fa-solid fa-arrow-right"></i></button></div>
          </section>

          <section class="booking-v2-card booking-panel-v2" data-step="2" hidden>
            <div class="booking-v2-heading"><div class="booking-v2-step-number">02</div><div><h2><?= e($w('booking_details_title')) ?></h2><p><?= e($w('booking_details_text')) ?></p></div></div>
            <div class="form-row">
              <div class="form-group"><label for="first_name">First name</label><input type="text" id="first_name" name="first_name" value="<?= e($old['first_name'] ?? '') ?>" autocomplete="given-name" required><?php if (!empty($errors['first_name'])): ?><span class="field-error"><?= e($errors['first_name']) ?></span><?php endif; ?></div>
              <div class="form-group"><label for="last_name">Last name</label><input type="text" id="last_name" name="last_name" value="<?= e($old['last_name'] ?? '') ?>" autocomplete="family-name" required><?php if (!empty($errors['last_name'])): ?><span class="field-error"><?= e($errors['last_name']) ?></span><?php endif; ?></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label for="phone">WhatsApp / phone</label><input type="tel" id="phone" name="phone" placeholder="07XX XXX XXX" value="<?= e($old['phone'] ?? '') ?>" autocomplete="tel" required><small class="form-help">Use a number we can reach you on for booking updates.</small><?php if (!empty($errors['phone'])): ?><span class="field-error"><?= e($errors['phone']) ?></span><?php endif; ?></div>
              <div class="form-group"><label for="email">Email address</label><input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" autocomplete="email" required><?php if (!empty($errors['email'])): ?><span class="field-error"><?= e($errors['email']) ?></span><?php endif; ?></div>
            </div>
            <div class="form-row">
              <div class="form-group"><label for="id_number">National ID / Passport No.</label><input type="text" id="id_number" name="id_number" placeholder="e.g. 30123456" value="<?= e($old['id_number'] ?? '') ?>" required><?php if (!empty($errors['id_number'])): ?><span class="field-error"><?= e($errors['id_number']) ?></span><?php endif; ?></div>
              <div class="form-group" id="license-field"><label for="driving_license_number">Driving licence No. <span class="optional-label" id="license-optional">Required for self-drive</span></label><input type="text" id="driving_license_number" name="driving_license_number" placeholder="Driving licence number" value="<?= e($old['driving_license_number'] ?? '') ?>"><?php if (!empty($errors['driving_license_number'])): ?><span class="field-error"><?= e($errors['driving_license_number']) ?></span><?php endif; ?></div>
            </div>
            <div class="booking-v2-actions"><button type="button" class="btn btn-outline btn-on-light" data-prev><i class="fa-solid fa-arrow-left"></i> Back</button><button type="button" class="btn btn-primary" data-next><?= e($w('booking_review_button')) ?> <i class="fa-solid fa-arrow-right"></i></button></div>
          </section>

          <section class="booking-v2-card booking-panel-v2" data-step="3" hidden>
            <div class="booking-v2-heading"><div class="booking-v2-step-number">03</div><div><h2><?= e($w('booking_check_send_title')) ?></h2><p><?= e($w('booking_check_send_text')) ?></p></div></div>
            <div id="booking-review" class="booking-review"></div>
            <div class="form-group"><label for="notes">Anything else? <span class="optional-label">Optional</span></label><textarea id="notes" name="notes" placeholder="Flight number, special request, child seat, or anything we should know."><?= e($old['notes'] ?? '') ?></textarea></div>
            <div class="booking-v2-agreement">
              <div class="agreement-title"><i class="fa-solid fa-shield-check"></i><div><strong>Before you send your request</strong><span>Two confirmations are required. WhatsApp updates are optional.</span></div></div>
              <label class="agreement-row"><input type="checkbox" name="terms_agree" value="1" required><span>I agree to the <a href="<?= base_url('terms') ?>" target="_blank" rel="noopener">rental Terms &amp; Conditions</a>.</span></label>
              <label class="agreement-row"><input type="checkbox" name="damage_agree" value="1" required><span><?= e(setting('legal', 'damage_disclaimer')) ?></span></label>
              <label class="agreement-row whatsapp-opt-in-row"><input type="checkbox" name="whatsapp_opt_in" value="1" <?= !empty($old['whatsapp_opt_in']) ? 'checked' : '' ?>><span><strong>Send important updates on WhatsApp</strong><small>We'll use your WhatsApp number for booking, payment and rental reminders.</small></span></label>
              <?php if (!empty($errors['terms_agree'])): ?><span class="field-error"><?= e($errors['terms_agree']) ?></span><?php endif; ?>
              <?php if (!empty($errors['damage_agree'])): ?><span class="field-error"><?= e($errors['damage_agree']) ?></span><?php endif; ?>
            </div>
            <div class="booking-trust-row"><span><i class="fa-solid fa-lock"></i> Secure booking</span><span><i class="fa-brands fa-whatsapp"></i> Updates available</span><span><i class="fa-solid fa-headset"></i> Kenya support</span></div>
            <div class="booking-v2-actions"><button type="button" class="btn btn-outline btn-on-light" data-prev><i class="fa-solid fa-arrow-left"></i> Back</button><button type="submit" class="btn btn-primary" id="booking-submit"><i class="fa-solid fa-calendar-check"></i> Send booking request</button></div>
          </section>
        </main>

        <aside class="booking-v2-summary" aria-live="polite">
          <div class="booking-summary-card">
            <div class="booking-v2-summary-head"><span>YOUR TRIP</span><i class="fa-solid fa-car"></i></div>
            <div id="summary-empty" class="summary-empty"><span class="summary-empty-icon"><i class="fa-solid fa-car-side"></i></span><strong>Build your trip</strong><p>Choose a car to see your estimate.</p></div>
            <div id="summary-content" hidden>
              <div class="summary-car"><strong id="summary-car-name"></strong><span id="summary-car-location"></span></div>
              <div class="summary-trip-route"><div><span>Pickup</span><strong id="summary-pickup-location">—</strong><small id="summary-pickup-date">—</small></div><i class="fa-solid fa-arrow-right"></i><div><span>Return</span><strong id="summary-return-location">—</strong><small id="summary-return-date">—</small></div></div>
              <div class="summary-line"><span>Rental</span><strong id="summary-days">—</strong></div>
              <div class="summary-line"><span>Rate</span><strong id="summary-car-rate">—</strong></div>
              <div class="summary-line" id="summary-driver-row" hidden><span>Chauffeur</span><strong id="summary-driver-fee">—</strong></div>
              <div class="summary-total"><span>Estimated total</span><strong id="summary-total">—</strong></div>
              <div class="summary-deposit"><span>Estimated deposit</span><strong id="summary-deposit">—</strong></div>
              <small class="summary-note">Availability and final price are confirmed by our team before payment.</small>
            </div>
          </div>
          <div class="booking-v2-help"><i class="fa-brands fa-whatsapp"></i><div><strong>Need help?</strong><span>Use the WhatsApp number on your booking form if you'd like important updates there.</span></div></div>
        </aside>
      </div>
    </form>
  </div>
</section>

<script>
(function(){
  'use strict';
  var form=document.getElementById('booking-form');
  if(!form)return;
  var panels=[].slice.call(document.querySelectorAll('.booking-panel-v2'));
  var indicators=[].slice.call(document.querySelectorAll('[data-step-indicator]'));
  var carInput=document.getElementById('car_id');
  var cars=[].slice.call(document.querySelectorAll('.booking-car-option'));
  var current=1,submitting=false;
  var currency=<?= json_encode(setting('general','currency','KES')) ?>;
  var depositPct=<?= json_encode($depositPct) ?>;
  var availabilityUrl=<?= json_encode(base_url('book/availability')) ?>;
  var availabilityRequest=null,availabilityTimer=null;
  var money=function(n){return currency+' '+Math.round(Number(n)||0).toLocaleString('en-KE');};
  var el=function(id){return document.getElementById(id);};
  var selectedCar=function(){return cars.find(function(c){return c.dataset.carId===String(carInput.value);})||null;};
  function setAvailabilityMessage(type,message){
    var box=el('booking-availability-message');
    if(!box)return;
    box.className='booking-availability-message '+(type||'');
    box.textContent=message||'';
    box.hidden=!message;
  }
  function setCarAvailability(card,state){
    var label=card.querySelector('[data-availability-label]');
    var unavailable=state && state.available===false;
    card.classList.toggle('is-unavailable',unavailable);
    card.disabled=unavailable;
    card.setAttribute('aria-disabled',unavailable?'true':'false');
    if(unavailable){
      card.setAttribute('aria-pressed','false');
      if(label){label.textContent=state.reason||'Unavailable for these dates';}
    }else if(label){
      label.textContent='Available for your dates';
    }
  }
  function resetFleetAvailability(){
    cars.forEach(function(card){
      var unavailable=['maintenance','retired'].indexOf(card.dataset.fleetStatus)!==-1;
      setCarAvailability(card, unavailable ? {available:false,reason:'Temporarily unavailable'} : {available:true});
    });
  }
  function checkAvailability(){
    var pickup=el('pickup_date')?.value,ret=el('return_date')?.value;
    if(!pickup||!ret){
      resetFleetAvailability();
      setAvailabilityMessage('', '');
      return;
    }
    var p=parseDateValue(pickup),r=parseDateValue(ret);
    if(!p||!r||r<=p){
      resetFleetAvailability();
      setAvailabilityMessage('warning','Choose a valid return time to check which cars are available.');
      return;
    }
    if(availabilityTimer)window.clearTimeout(availabilityTimer);
    availabilityTimer=window.setTimeout(function(){
      if(availabilityRequest && availabilityRequest.abort)availabilityRequest.abort();
      if(window.AbortController)availabilityRequest=new AbortController();
      var params=new URLSearchParams({pickup:pickup,return:ret});
      setAvailabilityMessage('loading','Checking vehicle availability for your dates…');
      fetch(availabilityUrl+'?'+params.toString(),{headers:{'Accept':'application/json'},signal:availabilityRequest?availabilityRequest.signal:undefined})
        .then(function(response){return response.json().then(function(data){return {ok:response.ok,data:data};});})
        .then(function(result){
          if(!result.ok||!result.data.ok)throw new Error(result.data.message||'We could not check availability.');
          var map=result.data.cars||{};
          var availableCount=0;
          cars.forEach(function(card){
            var state=map[String(card.dataset.carId)]||{available:false,reason:'Unavailable for these dates'};
            setCarAvailability(card,state);
            if(state.available)availableCount++;
          });
          var selected=selectedCar();
          if(selected && selected.disabled){
            var name=selected.dataset.name||'That vehicle';
            carInput.value='';
            selected.classList.remove('is-selected');
            selected.setAttribute('aria-pressed','false');
            updateSummary();updateReview();
            setAvailabilityMessage('error',name+' is not available for the dates you selected. We removed it from your selection — please choose another available car or change your dates.');
          }else if(availableCount===0){
            setAvailabilityMessage('error','We do not have an available vehicle for those dates. Try different dates and we’ll check again.');
          }else{
            setAvailabilityMessage('success',availableCount+' vehicle'+(availableCount===1?' is':'s are')+' available for your dates.');
          }
        })
        .catch(function(error){
          if(error && error.name==='AbortError')return;
          resetFleetAvailability();
          setAvailabilityMessage('warning','We could not check availability right now. You can continue, and we’ll verify the vehicle again before confirming your booking.');
        });
    },250);
  }
  function parseDateValue(value){
    if(!value)return null;
    var d=new Date(value);
    return Number.isNaN(d.getTime())?null:d;
  }
  function days(){
    var a=parseDateValue(el('pickup_date').value),b=parseDateValue(el('return_date').value);
    if(!a||!b||b<=a)return 0;
    return Math.max(1,Math.ceil((b-a)/86400000));
  }
  function shortDate(value){
    var d=parseDateValue(value);
    if(!d)return '—';
    return d.toLocaleDateString('en-KE',{day:'2-digit',month:'short',year:'numeric'});
  }
  function shortDateTime(value){
    var d=parseDateValue(value);
    if(!d)return '—';
    return shortDate(value)+' · '+d.toLocaleTimeString('en-KE',{hour:'2-digit',minute:'2-digit'});
  }
  function updateDriver(){
    var withDriver=document.querySelector('input[name="driver_option"]:checked')?.value==='with_driver';
    var field=el('driving_license_number'),optional=el('license-optional');
    if(!field)return;
    field.required=!withDriver;
    optional.textContent=withDriver?'Optional with chauffeur':'Required for self-drive';
    var group=field.closest('.form-group');
    if(group)group.classList.toggle('is-optional',withDriver);
  }
  function updateSummary(){
    var car=selectedCar();
    if(!car){el('summary-empty').hidden=false;el('summary-content').hidden=true;return;}
    var price=Number(car.dataset.price||0),fee=Number(car.dataset.chauffeurFee||0),d=days();
    var withDriver=document.querySelector('input[name="driver_option"]:checked')?.value==='with_driver';
    el('summary-empty').hidden=true;el('summary-content').hidden=false;
    el('summary-car-name').textContent=car.dataset.name||'Selected vehicle';
    el('summary-car-location').textContent=car.dataset.location||'Kenya';
    el('summary-pickup-location').textContent=el('pickup_location').value.trim()||'Choose pickup';
    el('summary-return-location').textContent=el('dropoff_location').value.trim()||'Choose return';
    el('summary-pickup-date').textContent=shortDateTime(el('pickup_date').value);
    el('summary-return-date').textContent=shortDateTime(el('return_date').value);
    el('summary-days').textContent=d?d+' day'+(d===1?'':'s'):'Choose dates';
    el('summary-car-rate').textContent=money(price)+'/day';
    el('summary-driver-row').hidden=!withDriver;
    el('summary-driver-fee').textContent=money(fee)+'/day';
    var total=d?d*(price+(withDriver?fee:0)):0;
    el('summary-total').textContent=d?money(total):'—';
    el('summary-deposit').textContent=d?money(total*depositPct/100):'—';
    var note=el('chauffeur-fee-note');
    if(note)note.textContent=withDriver&&fee>0?'Chauffeur: +'+money(fee)+'/day':'';
  }
  function updateReview(){
    var car=selectedCar(),withDriver=document.querySelector('input[name="driver_option"]:checked')?.value==='with_driver';
    var d=days(),pickup=el('pickup_date').value,ret=el('return_date').value,total=el('summary-total').textContent;
    var safe=function(v){return String(v||'').replace(/[&<>"']/g,function(c){return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#039;'}[c];});};
    el('booking-review').innerHTML='<div class="review-grid">'+
      '<div><span>Vehicle</span><strong>'+safe(car?car.dataset.name:'Not selected')+'</strong></div>'+
      '<div><span>Driving</span><strong>'+ (withDriver?'With chauffeur':'Self-drive')+'</strong></div>'+
      '<div><span>Pickup</span><strong>'+safe(el('pickup_location').value)+'</strong><small>'+safe(shortDateTime(pickup))+'</small></div>'+
      '<div><span>Return</span><strong>'+safe(el('dropoff_location').value)+'</strong><small>'+safe(shortDateTime(ret))+'</small></div>'+
      '<div><span>Rental days</span><strong>'+ (d||'—')+'</strong></div>'+
      '<div><span>Estimated total</span><strong>'+safe(total)+'</strong></div>'+
      '<div><span>Customer</span><strong>'+safe((el('first_name').value+' '+el('last_name').value).trim())+'</strong></div>'+
      '<div><span>WhatsApp / phone</span><strong>'+safe(el('phone').value)+'</strong></div></div>';
  }
  function fields(panel){return [].slice.call(panel.querySelectorAll('input,select,textarea')).filter(function(x){return x.type!=='hidden'&&!x.disabled;});}
  function validCurrent(){
    var panel=panels[current-1];
    if(!panel)return false;
    for(var f of fields(panel)){
      if(!f.checkValidity()){f.reportValidity();return false;}
    }
    if(current===1 && !carInput.value){
      var first=cars[0];
      if(first)first.focus();
      alert('Please choose a car before continuing.');
      return false;
    }
    if(current===1){
      var p=parseDateValue(el('pickup_date').value),r=parseDateValue(el('return_date').value);
      if(!p||!r||r<=p){el('return_date').focus();alert('Return time must be after pickup time.');return false;}
    }
    return true;
  }
  function showStep(step){
    current=Math.max(1,Math.min(3,step));
    panels.forEach(function(p){var active=Number(p.dataset.step)===current;p.hidden=!active;p.classList.toggle('is-active',active);});
    indicators.forEach(function(i){var n=Number(i.dataset.stepIndicator);i.classList.toggle('is-active',n===current);i.classList.toggle('is-complete',n<current);});
    updateReview();updateSummary();
    var progress=document.querySelector('.booking-v2-progress');
    if(progress)window.scrollTo({top:progress.getBoundingClientRect().top+window.scrollY-18,behavior:'smooth'});
  }
  function selectCar(card){
    if(card.disabled || card.classList.contains('is-unavailable'))return;
    cars.forEach(function(c){c.classList.remove('is-selected');c.setAttribute('aria-pressed','false');});
    card.classList.add('is-selected');card.setAttribute('aria-pressed','true');carInput.value=card.dataset.carId;
    updateSummary();updateReview();
  }
  cars.forEach(function(card){
    card.addEventListener('click',function(){selectCar(card);});
    card.addEventListener('keydown',function(e){if(e.key==='Enter'||e.key===' '){e.preventDefault();selectCar(card);}});
  });
  document.querySelectorAll('[data-next]').forEach(function(b){b.addEventListener('click',function(){if(validCurrent())showStep(current+1);});});
  document.querySelectorAll('[data-prev]').forEach(function(b){b.addEventListener('click',function(){showStep(current-1);});});
  ['pickup_date','return_date','pickup_location','dropoff_location'].forEach(function(id){
    var field=el(id);if(!field)return;
    ['input','change'].forEach(function(event){field.addEventListener(event,function(){
      if(id==='pickup_date')el('return_date').min=field.value;
      if(id==='return_date' && el('pickup_date').value)field.min=el('pickup_date').value;
      updateSummary();updateReview();
    });});
  });
  ['pickup_date','return_date'].forEach(function(id){
    var field=el(id);
    if(!field)return;
    ['input','change'].forEach(function(event){field.addEventListener(event,checkAvailability);});
  });
  document.querySelectorAll('input[name="driver_option"]').forEach(function(x){x.addEventListener('change',function(){updateDriver();updateSummary();updateReview();});});
  ['first_name','last_name','phone','email'].forEach(function(id){var field=el(id);if(field)field.addEventListener('input',updateReview);});
  document.querySelectorAll('[data-location-value]').forEach(function(btn){btn.addEventListener('click',function(){el('pickup_location').value=btn.dataset.locationValue;el('pickup_location').dispatchEvent(new Event('input',{bubbles:true}));});});
  var now=new Date(),local=new Date(now.getTime()-now.getTimezoneOffset()*60000).toISOString().slice(0,16);
  el('pickup_date').min=local;el('return_date').min=el('pickup_date').value||local;
  form.addEventListener('submit',function(e){
    if(submitting){e.preventDefault();return;}
    e.preventDefault();
    if(current!==3){if(validCurrent())showStep(3);return;}
    if(!validCurrent())return;
    submitting=true;
    var btn=el('booking-submit');btn.disabled=true;btn.setAttribute('aria-busy','true');
    btn.innerHTML='<i class="fa-solid fa-spinner fa-spin"></i> Sending request…';
    form.submit();
  });
  if(carInput.value){var initial=selectedCar();if(initial){initial.classList.add('is-selected');initial.setAttribute('aria-pressed','true');}}
  updateDriver();updateSummary();updateReview();
  checkAvailability();
})();
</script>
<?php view('layouts/footer'); ?>
