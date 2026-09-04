<?php view('admin/layout-header', ['seo' => $seo]); ?>

<div class="card">
  <div class="card-header"><h2>General Settings</h2></div>
  <form action="<?= base_url('admin/settings/save') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="general">
    <div class="form-row">
      <?php foreach ($general as $item): ?>
        <div class="form-group">
          <label for="gen-<?= e($item['setting_key']) ?>"><?= e(label_from_key($item['setting_key'])) ?></label>
          <?php if (str_contains($item['setting_key'], 'hours') || str_contains($item['setting_key'], 'embed')): ?>
            <textarea id="gen-<?= e($item['setting_key']) ?>" name="settings[<?= e($item['setting_key']) ?>]"><?= e($item['setting_value']) ?></textarea>
          <?php else: ?>
            <input type="text" id="gen-<?= e($item['setting_key']) ?>" name="settings[<?= e($item['setting_key']) ?>]" value="<?= e($item['setting_value']) ?>">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save General Settings</button>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>Website Content</h2></div>
  <p class="settings-help">All public marketing copy and customer-facing business content is stored here. Update these values without editing PHP files.</p>
  <form action="<?= base_url('admin/settings/save') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="website">
    <div class="form-row">
      <?php foreach ($websiteItems as $item): ?>
        <?php $key = $item['setting_key']; $longText = str_ends_with($key, '_text') || str_contains($key, 'intro') || str_contains($key, 'lead') || str_contains($key, 'note'); ?>
        <div class="form-group">
          <label for="web-<?= e($key) ?>"><?= e(label_from_key($key)) ?></label>
          <?php if ($longText): ?>
            <textarea id="web-<?= e($key) ?>" name="settings[<?= e($key) ?>]" rows="3"><?= e($item['setting_value']) ?></textarea>
          <?php else: ?>
            <input type="text" id="web-<?= e($key) ?>" name="settings[<?= e($key) ?>]" value="<?= e($item['setting_value']) ?>">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Website Content</button>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>SEO Settings</h2></div>
  <p style="color:var(--color-text-faint);font-size:0.85rem;margin-bottom:16px;">Controls page titles, meta descriptions and other SEO metadata across the site. Per-page fields (e.g. <code>home_title</code>) override the site-wide defaults.</p>
  <form action="<?= base_url('admin/settings/save') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="seo">
    <div class="form-row">
      <?php foreach ($seoItems as $item): ?>
        <div class="form-group">
          <label for="seo-<?= e($item['setting_key']) ?>"><?= e(label_from_key($item['setting_key'])) ?></label>
          <?php if (str_contains($item['setting_key'], 'description')): ?>
            <textarea id="seo-<?= e($item['setting_key']) ?>" name="settings[<?= e($item['setting_key']) ?>]"><?= e($item['setting_value']) ?></textarea>
          <?php else: ?>
            <input type="text" id="seo-<?= e($item['setting_key']) ?>" name="settings[<?= e($item['setting_key']) ?>]" value="<?= e($item['setting_value']) ?>">
          <?php endif; ?>
        </div>
      <?php endforeach; ?>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save SEO Settings</button>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>Notification Settings</h2></div>
  <p class="settings-help">Control operational alerts. Email/SMS credentials remain in <code>.env</code>. WhatsApp is provider-based so booking logic does not need to know which provider you use.</p>
  <form action="<?= base_url('admin/settings/save') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="notifications">
    <div class="form-row">
      <div class="form-group"><label>Email Notifications</label><select name="settings[email_enabled]"><option value="1" <?= setting('notifications','email_enabled','1')==='1'?'selected':'' ?>>Enabled</option><option value="0" <?= setting('notifications','email_enabled','1')==='0'?'selected':'' ?>>Disabled</option></select></div>
      <div class="form-group"><label>SMS Notifications</label><select name="settings[sms_enabled]"><option value="1" <?= setting('notifications','sms_enabled','1')==='1'?'selected':'' ?>>Enabled</option><option value="0" <?= setting('notifications','sms_enabled','1')==='0'?'selected':'' ?>>Disabled</option></select></div>
      <div class="form-group"><label>WhatsApp Alerts</label><select name="settings[whatsapp_enabled]"><option value="1" <?= setting('notifications','whatsapp_enabled','0')==='1'?'selected':'' ?>>Enabled</option><option value="0" <?= setting('notifications','whatsapp_enabled','0')==='0'?'selected':'' ?>>Disabled</option></select></div>
      <div class="form-group"><label>WhatsApp Provider</label><select name="settings[whatsapp_provider]"><option value="cloud_api" <?= setting('notifications','whatsapp_provider','callmebot')==='cloud_api'?'selected':'' ?>>WhatsApp Cloud API</option><option value="callmebot" <?= setting('notifications','whatsapp_provider','callmebot')==='callmebot'?'selected':'' ?>>CallMeBot (temporary admin alerts)</option></select></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Admin WhatsApp Phone</label><input type="text" name="settings[admin_whatsapp_phone]" value="<?= e(setting('notifications','admin_whatsapp_phone','')) ?>" placeholder="2547XXXXXXXX"></div>
      <div class="form-group"><label>Customer WhatsApp</label><select name="settings[whatsapp_customer_enabled]"><option value="1" <?= setting('notifications','whatsapp_customer_enabled','0')==='1'?'selected':'' ?>>Enabled</option><option value="0" <?= setting('notifications','whatsapp_customer_enabled','0')==='0'?'selected':'' ?>>Disabled</option></select><small>Only works with Cloud API and approved templates.</small></div>
    </div>
    <div class="form-row">
      <div class="form-group"><label>Payment Due Alerts</label><select name="settings[whatsapp_payment_due_enabled]"><option value="1" <?= setting('notifications','whatsapp_payment_due_enabled','1')==='1'?'selected':'' ?>>Enabled</option><option value="0" <?= setting('notifications','whatsapp_payment_due_enabled','1')==='0'?'selected':'' ?>>Disabled</option></select><small>Notify opted-in customers when a balance is still outstanding before pickup.</small></div>
      <div class="form-group"><label>Return Reminders</label><select name="settings[whatsapp_return_reminders_enabled]"><option value="1" <?= setting('notifications','whatsapp_return_reminders_enabled','1')==='1'?'selected':'' ?>>Enabled</option><option value="0" <?= setting('notifications','whatsapp_return_reminders_enabled','1')==='0'?'selected':'' ?>>Disabled</option></select></div>
      <div class="form-group"><label>Post-rental Follow-up</label><select name="settings[whatsapp_post_rental_enabled]"><option value="1" <?= setting('notifications','whatsapp_post_rental_enabled','1')==='1'?'selected':'' ?>>Enabled</option><option value="0" <?= setting('notifications','whatsapp_post_rental_enabled','1')==='0'?'selected':'' ?>>Disabled</option></select></div>
      <div class="form-group"><label>Reminder Hours</label><input type="number" min="1" max="72" name="settings[whatsapp_reminder_hours]" value="<?= e(setting('notifications','whatsapp_reminder_hours','24')) ?>"><small>Pickup reminder lead time.</small></div>
    </div>
    <div class="whatsapp-template-grid">
      <div class="form-group"><label>Booking received template</label><input type="text" name="settings[whatsapp_template_booking_received]" value="<?= e(setting('notifications','whatsapp_template_booking_received','booking_received')) ?>"></div>
      <div class="form-group"><label>Booking confirmed template</label><input type="text" name="settings[whatsapp_template_booking_confirmed]" value="<?= e(setting('notifications','whatsapp_template_booking_confirmed','booking_confirmed')) ?>"></div>
      <div class="form-group"><label>Payment received template</label><input type="text" name="settings[whatsapp_template_payment_received]" value="<?= e(setting('notifications','whatsapp_template_payment_received','payment_received')) ?>"></div>
      <div class="form-group"><label>Pickup reminder template</label><input type="text" name="settings[whatsapp_template_pickup_reminder]" value="<?= e(setting('notifications','whatsapp_template_pickup_reminder','pickup_reminder')) ?>"></div>
      <div class="form-group"><label>Payment due template</label><input type="text" name="settings[whatsapp_template_payment_due]" value="<?= e(setting('notifications','whatsapp_template_payment_due','payment_due')) ?>"></div>
      <div class="form-group"><label>Return reminder template</label><input type="text" name="settings[whatsapp_template_return_reminder]" value="<?= e(setting('notifications','whatsapp_template_return_reminder','return_reminder')) ?>"></div>
      <div class="form-group"><label>Rental completed template</label><input type="text" name="settings[whatsapp_template_rental_completed]" value="<?= e(setting('notifications','whatsapp_template_rental_completed','rental_completed')) ?>"></div>
      <div class="form-group"><label>Review request template</label><input type="text" name="settings[whatsapp_template_review_request]" value="<?= e(setting('notifications','whatsapp_template_review_request','review_request')) ?>"></div>
      <div class="form-group"><label>Admin new booking template</label><input type="text" name="settings[whatsapp_template_admin_new_booking]" value="<?= e(setting('notifications','whatsapp_template_admin_new_booking','admin_new_booking')) ?>"></div>
      <div class="form-group"><label>Admin payment due template</label><input type="text" name="settings[whatsapp_template_admin_payment_due]" value="<?= e(setting('notifications','whatsapp_template_admin_payment_due','admin_payment_due')) ?>"></div>
      <div class="form-group"><label>Admin payment template</label><input type="text" name="settings[whatsapp_template_admin_payment_received]" value="<?= e(setting('notifications','whatsapp_template_admin_payment_received','admin_payment_received')) ?>"></div>
      <div class="form-group"><label>Admin status template</label><input type="text" name="settings[whatsapp_template_admin_status_changed]" value="<?= e(setting('notifications','whatsapp_template_admin_status_changed','admin_status_changed')) ?>"></div>
    </div>
    <div class="settings-security-note"><i class="fa-brands fa-whatsapp"></i><span><strong>Cloud API credentials:</strong> keep <code>WHATSAPP_CLOUD_ACCESS_TOKEN</code>, <code>WHATSAPP_CLOUD_PHONE_NUMBER_ID</code>, <code>WHATSAPP_CLOUD_VERIFY_TOKEN</code> and <code>WHATSAPP_CLOUD_APP_SECRET</code> in <code>.env</code>. Do not store them in MySQL.</span></div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Notification Settings</button>
  </form>
</div>

<div class="card">
  <div class="card-header"><h2>Legal Settings</h2></div>
  <p style="color:var(--color-text-faint);font-size:0.85rem;margin-bottom:16px;">
    Manage the public Privacy Policy and Terms of Service, as well as the booking terms and damage disclaimer.
    All public legal pages are database-driven — changes here are reflected on the website without code changes.
  </p>
  <form action="<?= base_url('admin/settings/save') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="legal">

    <?php foreach ($legalItems as $item): ?>
      <?php
        $key = $item['setting_key'];
        $longText = in_array($key, ['terms_and_conditions','terms_of_service','privacy_policy'], true);
        $meta = in_array($key, ['privacy_meta_title','privacy_meta_description','terms_meta_title','terms_meta_description'], true);
      ?>
      <div class="form-group">
        <label for="legal-<?= e($key) ?>"><?= e(label_from_key($key)) ?></label>
        <?php if ($longText): ?>
          <textarea id="legal-<?= e($key) ?>" name="settings[<?= e($key) ?>]" style="min-height:<?= $key === 'privacy_policy' || $key === 'terms_of_service' ? '420px' : '260px' ?>;"><?= e($item['setting_value']) ?></textarea>
        <?php elseif ($meta): ?>
          <input type="text" id="legal-<?= e($key) ?>" name="settings[<?= e($key) ?>]" value="<?= e($item['setting_value']) ?>" maxlength="500">
        <?php else: ?>
          <input type="text" id="legal-<?= e($key) ?>" name="settings[<?= e($key) ?>]" value="<?= e($item['setting_value']) ?>">
        <?php endif; ?>
      </div>
    <?php endforeach; ?>

    <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
      <a class="btn btn-outline" href="<?= base_url('privacy') ?>" target="_blank"><i class="fa-solid fa-shield-halved"></i> Preview Privacy Policy</a>
      <a class="btn btn-outline" href="<?= base_url('terms') ?>" target="_blank"><i class="fa-solid fa-file-contract"></i> Preview Terms of Service</a>
    </div>

    <button type="submit" class="btn btn-primary" style="margin-top:14px;"><i class="fa-solid fa-floppy-disk"></i> Save Legal Settings</button>
  </form>
</div>

<div class="card payment-settings-card">
  <div class="card-header"><h2>Paystack Settings</h2></div>
  <p style="color:var(--color-text-faint);font-size:0.85rem;margin-bottom:16px;">
    Configure the online booking deposit without touching code. API credentials remain on the server
    in <code>.env</code>. These settings control the customer-facing Paystack experience.
  </p>

  <form action="<?= base_url('admin/settings/save') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="paystack">

    <div class="form-row">
      <div class="form-group">
        <label for="paystack-enabled">Online Payments</label>
        <select id="paystack-enabled" name="settings[enabled]">
          <option value="1" <?= setting('paystack','enabled','1') === '1' ? 'selected' : '' ?>>Enabled</option>
          <option value="0" <?= setting('paystack','enabled','1') === '0' ? 'selected' : '' ?>>Disabled</option>
        </select>
        <small style="color:var(--color-text-faint);">Disable this to temporarily hide the Paystack payment action without removing the integration.</small>
      </div>

      <div class="form-group">
        <label for="paystack-deposit">Deposit Percentage</label>
        <input type="number" min="1" max="100" step="1" id="paystack-deposit" name="settings[deposit_percentage]" value="<?= e(setting('paystack','deposit_percentage','30')) ?>">
        <small style="color:var(--color-text-faint);">The percentage of the booking total requested at online checkout.</small>
      </div>

      <div class="form-group">
        <label for="paystack-label">Checkout Label</label>
        <input type="text" id="paystack-label" name="settings[display_label]" value="<?= e(setting('paystack','display_label','Pay securely')) ?>" maxlength="80">
        <small style="color:var(--color-text-faint);">Short heading shown above the payment action.</small>
      </div>

      <div class="form-group">
        <label for="paystack-channels">Payment Channels</label>
        <input type="text" id="paystack-channels" name="settings[channels]" value="<?= e(setting('paystack','channels','card,mobile_money,bank_transfer')) ?>" placeholder="card,mobile_money,bank_transfer">
        <small style="color:var(--color-text-faint);">
          Comma-separated: card, bank, ussd, qr, mobile_money, bank_transfer, eft.
          Leave blank to let Paystack decide the available channels.
        </small>
      </div>
    </div>

    <div class="form-group">
      <label for="paystack-description">Checkout Description</label>
      <textarea id="paystack-description" name="settings[checkout_description]" rows="3"><?= e(setting('paystack','checkout_description','Pay your booking deposit securely using the payment methods available through Paystack.')) ?></textarea>
    </div>

    <div class="settings-security-note">
      <i class="fa-solid fa-shield-halved"></i>
      <span>
        <strong>Credentials stay server-side.</strong>
        Configure <code>PAYSTACK_SECRET_KEY</code>, <code>PAYSTACK_PUBLIC_KEY</code>,
        <code>PAYSTACK_CALLBACK_URL</code> and <code>PAYSTACK_WEBHOOK_URL</code> in <code>.env</code>.
      </span>
    </div>

    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Paystack Settings</button>
  </form>
</div>

<div class="card payment-settings-card"><div class="card-header"><h2>Reviews Settings</h2></div><p style="color:var(--color-text-faint);font-size:.85rem;margin-bottom:16px;">Connect Google Business Profile and Tripadvisor so recent reviews can appear on the website. API secrets remain in .env.</p><form action="<?=base_url('admin/settings/save')?>" method="post"><?=csrf_field()?><input type="hidden" name="group" value="reviews"><div class="form-row"><div class="form-group"><label>Reviews on Website</label><select name="settings[enabled]"><option value="1" <?=setting('reviews','enabled','1')==='1'?'selected':''?>>Enabled</option><option value="0" <?=setting('reviews','enabled','1')==='0'?'selected':''?>>Disabled</option></select></div><div class="form-group"><label>Homepage Review Count</label><input type="number" min="1" max="12" name="settings[home_limit]" value="<?=e(setting('reviews','home_limit','6'))?>"></div><div class="form-group"><label>Google Reviews</label><select name="settings[google_enabled]"><option value="1" <?=setting('reviews','google_enabled','1')==='1'?'selected':''?>>Enabled</option><option value="0" <?=setting('reviews','google_enabled','1')==='0'?'selected':''?>>Disabled</option></select></div><div class="form-group"><label>Tripadvisor Reviews</label><select name="settings[tripadvisor_enabled]"><option value="1" <?=setting('reviews','tripadvisor_enabled','1')==='1'?'selected':''?>>Enabled</option><option value="0" <?=setting('reviews','tripadvisor_enabled','1')==='0'?'selected':''?>>Disabled</option></select></div></div><div class="form-row"><div class="form-group"><label>Google Business Account ID</label><input type="text" name="settings[google_account_id]" value="<?=e(setting('reviews','google_account_id',''))?>" placeholder="accounts/123... or numeric ID"><small>Used for review synchronization; .env remains the fallback.</small></div><div class="form-group"><label>Google Location ID</label><input type="text" name="settings[google_location_id]" value="<?=e(setting('reviews','google_location_id',''))?>" placeholder="123456789"><small>Business Profile location ID; .env remains the fallback.</small></div><div class="form-group"><label>Google Place ID</label><input type="text" name="settings[google_place_id]" value="<?=e(setting('reviews','google_place_id',''))?>" placeholder="ChIJ..."><small>Used for the Google write-a-review link if no custom URL is supplied.</small></div><div class="form-group"><label>Google Review URL</label><input type="url" name="settings[google_review_url]" value="<?=e(setting('reviews','google_review_url',''))?>"></div><div class="form-group"><label>Tripadvisor Location ID</label><input type="text" name="settings[tripadvisor_location_id]" value="<?=e(setting('reviews','tripadvisor_location_id',''))?>" placeholder="12345678"><small>Also set TRIPADVISOR_LOCATION_ID in .env for syncing.</small></div><div class="form-group"><label>Tripadvisor Review URL</label><input type="url" name="settings[tripadvisor_review_url]" value="<?=e(setting('reviews','tripadvisor_review_url',''))?>"></div></div><div class="form-group"><label>Review Request CTA</label><select name="settings[request_enabled]"><option value="1" <?=setting('reviews','request_enabled','1')==='1'?'selected':''?>>Show after completed rental</option><option value="0" <?=setting('reviews','request_enabled','1')==='0'?'selected':''?>>Disabled</option></select><small>Completed customers see direct links to leave a Google or Tripadvisor review.</small></div><div class="settings-security-note"><i class="fa-solid fa-key"></i><span><strong>Server credentials:</strong> GOOGLE_REVIEW_CLIENT_ID, GOOGLE_REVIEW_CLIENT_SECRET, GOOGLE_REVIEW_REFRESH_TOKEN, GOOGLE_REVIEW_ACCOUNT_ID, GOOGLE_REVIEW_LOCATION_ID, TRIPADVISOR_API_KEY and TRIPADVISOR_LOCATION_ID belong in .env.</span></div><button class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Review Settings</button></form></div>

<div class="card">
  <div class="card-header"><h2>Rental Policy</h2></div>
  <p style="color:var(--color-text-faint);font-size:0.85rem;margin-bottom:16px;">
    Controls vehicle turnaround between bookings and late-return charges. These were previously
    only settable directly in the database — now editable here.
  </p>
  <form action="<?= base_url('admin/settings/save') ?>" method="post">
    <?= csrf_field() ?>
    <input type="hidden" name="group" value="rental">
    <div class="form-row">
      <div class="form-group">
        <label for="rental-turnaround">Turnaround Buffer (hours)</label>
        <input type="number" min="0" step="1" id="rental-turnaround" name="settings[turnaround_buffer_hours]" value="<?= e(setting('rental', 'turnaround_buffer_hours', '3')) ?>">
        <small style="color:var(--color-text-faint);">Minimum gap enforced between one booking's return and the next booking's pickup for the same vehicle — time for cleaning/inspection. Use 24 for a full one-day gap.</small>
      </div>
      <div class="form-group">
        <label for="rental-grace">Late Return Grace (minutes)</label>
        <input type="number" min="0" step="1" id="rental-grace" name="settings[late_return_grace_minutes]" value="<?= e(setting('rental', 'late_return_grace_minutes', '30')) ?>">
        <small style="color:var(--color-text-faint);">How late a return can be before a late fee is charged at handover.</small>
      </div>
      <div class="form-group">
        <label for="rental-mileage">Extra Mileage Rate (per km)</label>
        <input type="text" id="rental-mileage" name="settings[extra_mileage_rate_per_km]" value="<?= e(setting('rental', 'extra_mileage_rate_per_km', '0')) ?>">
        <small style="color:var(--color-text-faint);">Charged automatically at return if the odometer exceeds the checkout reading. 0 disables mileage charges.</small>
      </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fa-solid fa-floppy-disk"></i> Save Rental Policy</button>
  </form>
</div>

<?php view('admin/layout-footer'); ?>
