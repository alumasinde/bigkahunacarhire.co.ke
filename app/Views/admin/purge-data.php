<?php view('admin/layout-header', ['seo' => $seo]); ?>

<style>
  .purge-hero {
    display: flex;
    gap: 18px;
    align-items: flex-start;
    padding: 22px;
    border: 1px solid rgba(224, 174, 40, .28);
    background: linear-gradient(135deg, rgba(224, 174, 40, .13), rgba(255, 255, 255, .72));
    border-radius: 18px;
    margin-bottom: 18px
  }

  .purge-hero-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: grid;
    place-items: center;
    background: #111;
    color: #e5b52d;
    font-size: 1.25rem;
    flex: 0 0 auto
  }

  .purge-grid {
    display: grid;
    grid-template-columns: repeat(2, minmax(0, 1fr));
    gap: 14px;
    margin: 18px 0
  }

  .purge-option {
    position: relative;
    border: 1px solid var(--color-border, #ddd);
    border-radius: 16px;
    padding: 18px;
    background: #fff;
    transition: .18s ease
  }

  .purge-option:has(input:checked) {
    border-color: #d7aa2c;
    box-shadow: 0 0 0 3px rgba(215, 170, 44, .12)
  }

  .purge-option input {
    position: absolute;
    top: 18px;
    right: 18px;
    width: 20px;
    height: 20px;
    accent-color: #d7aa2c
  }

  .purge-option h3 {
    margin: 0 32px 6px 0;
    font-size: 1rem
  }

  .purge-option p {
    margin: 0;
    color: var(--color-text-faint, #777);
    font-size: .88rem;
    line-height: 1.55
  }

  .purge-count {
    display: inline-block;
    margin-top: 10px;
    font-weight: 700;
    color: #111
  }

  .purge-protected {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-top: 12px
  }

  .purge-chip {
    padding: 7px 10px;
    border-radius: 999px;
    background: #f2f4f5;
    font-size: .78rem;
    color: #555
  }

  .purge-confirm {
    margin-top: 18px;
    padding: 18px;
    border: 1px solid #e2e2e2;
    border-radius: 16px;
    background: #fafafa
  }

  .purge-confirm label {
    display: block;
    font-weight: 700;
    margin-bottom: 8px
  }

  .purge-confirm input {
    width: 100%;
    box-sizing: border-box
  }

  .purge-actions {
    display: flex;
    justify-content: flex-end;
    gap: 10px;
    margin-top: 16px
  }

  .btn-danger {
    background: #b42318 !important;
    color: #fff !important;
    border-color: #b42318 !important
  }

  .purge-note {
    font-size: .82rem;
    color: #777;
    line-height: 1.55;
    margin-top: 10px
  }

  @media(max-width:700px) {
    .purge-grid {
      grid-template-columns: 1fr
    }

    .purge-hero {
      padding: 16px
    }

    .purge-actions {
      flex-direction: column
    }

    .purge-actions .btn {
      width: 100%
    }
  }
</style>

<div class="card">
  <div class="purge-hero">
    <div class="purge-hero-icon"><i class="fa-solid fa-triangle-exclamation"></i></div>
    <div>
      <h2 style="margin:0 0 6px">Purge test &amp; transactional data</h2>
      <p style="margin:0;color:var(--color-text-faint,#777);line-height:1.6">This tool is intentionally restricted to the <strong>super administrator</strong>. It removes selected operational records without touching your system configuration, staff accounts, roles, permissions or fleet master data.</p>
    </div>
  </div>

  <div class="alert alert-error">
    <i class="fa-solid fa-shield-halved"></i>
    <strong>Permanent action.</strong> Purged records cannot be recovered from the application. Take a database backup before using this page on production data.
  </div>

  <form action="<?= base_url('admin/purge-data') ?>" method="post" id="purge-form">
    <?= csrf_field() ?>
    <div class="purge-grid">
      <label class="purge-option">
        <input type="checkbox" name="datasets[]" value="bookings">
        <h3><i class="fa-solid fa-calendar-check"></i> Bookings &amp; related transactions</h3>
        <p>Deletes all bookings and their dependent payments, rental inspections and rental charges through the existing foreign-key relationships.</p>
        <span class="purge-count"><?= number_format((int)$counts['bookings']) ?> bookings</span>
      </label>

      <label class="purge-option">
        <input type="checkbox" name="datasets[]" value="payments">
        <h3><i class="fa-solid fa-credit-card"></i> Payments only</h3>
        <p>Deletes payment records while keeping the booking records. Useful when resetting Paystack/M-Pesa test transactions.</p>
        <span class="purge-count"><?= number_format((int)$counts['payments']) ?> payments</span>
      </label>

      <label class="purge-option">
        <input type="checkbox" name="datasets[]" value="rental_history">
        <h3><i class="fa-solid fa-car-side"></i> Rental history</h3>
        <p>Deletes rental inspections and rental charges. Fleet vehicles themselves are not deleted.</p>
        <span class="purge-count"><?= number_format((int)$counts['rental_inspections']) ?> inspections · <?= number_format((int)$counts['rental_charges']) ?> charges</span>
      </label>

      <label class="purge-option">
        <input type="checkbox" name="datasets[]" value="contact_messages">
        <h3><i class="fa-solid fa-envelope"></i> Contact messages</h3>
        <p>Deletes website contact enquiries and their reply history.</p>
        <span class="purge-count"><?= number_format((int)$counts['contact_messages']) ?> messages</span>
      </label>
    </div>

    <div class="card" style="background:#f8f9fa;margin-top:18px">
      <h3 style="margin-top:0"><i class="fa-solid fa-lock"></i> Always protected</h3>
      <p style="margin:0;color:var(--color-text-faint,#777)">These tables are deliberately not exposed as purge options:</p>
      <div class="purge-protected">
        <?php foreach (['users', 'customers', 'roles', 'permissions', 'role_permissions', 'sessions', 'settings', 'cars', 'car_categories', 'car_images', 'vehicle_documents', 'vehicle_maintenance', 'vehicle_odometer_logs', 'chauffeur_rates', 'seo_pages', 'seo_page_related', 'seo_page_faqs', 'seo_page_content', 'testimonials'] as $table): ?>
          <span class="purge-chip"><i class="fa-solid fa-lock"></i> <?= e($table) ?></span>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="purge-confirm">
      <label for="confirmation">Type <code>PURGE TRANSACTION DATA</code> to confirm</label>
      <input class="form-control" id="confirmation" name="confirmation" autocomplete="off" placeholder="PURGE TRANSACTION DATA" required>
      <p class="purge-note">Selecting <strong>Bookings</strong> also removes their dependent payment and rental transaction records. No protected table above is ever included in the purge query.</p>
    </div>

    <div class="purge-actions">
      <a href="<?= base_url('admin/dashboard') ?>" class="btn btn-secondary">Cancel</a>
      <button type="submit" class="btn btn-danger" id="purge-submit" disabled><i class="fa-solid fa-trash-can"></i> Purge selected data</button>
    </div>
  </form>
</div>

<script>
  (function() {
    const form = document.getElementById('purge-form');
    const confirmation = document.getElementById('confirmation');
    const submit = document.getElementById('purge-submit');
    const boxes = [...form.querySelectorAll('input[name="datasets[]"]')];

    function update() {
      submit.disabled = confirmation.value.trim() !== 'PURGE TRANSACTION DATA' || !boxes.some(b => b.checked)
    }
    confirmation.addEventListener('input', update);
    boxes.forEach(b => b.addEventListener('change', update));
    form.addEventListener('submit', function(e) {
      if (!confirm('This permanently deletes the selected data. Continue?')) e.preventDefault();
    });
  })();
</script>

<?php view('admin/layout-footer'); ?>