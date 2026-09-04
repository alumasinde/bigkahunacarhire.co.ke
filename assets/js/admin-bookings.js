/**
 * Admin AJAX layer — booking status, manual payment verify/reject, and
 * extend-booking with a live conflict pre-check.
 *
 * Progressive enhancement: every form here still has a real action="" and
 * a real submit button. If JavaScript fails to load, the browser falls
 * back to the original full-page POST + redirect flow that the backend
 * still fully supports (see wants_json() in includes/functions.php).
 */
(function () {
  'use strict';

  // -----------------------------------------------------------------
  // Toasts
  // -----------------------------------------------------------------
  function toastContainer() {
    var c = document.getElementById('admin-toast-container');
    if (!c) {
      c = document.createElement('div');
      c.id = 'admin-toast-container';
      c.className = 'admin-toast-container';
      document.body.appendChild(c);
    }
    return c;
  }

  function toast(type, message) {
    if (!message) return;
    var box = document.createElement('div');
    box.className = 'admin-toast admin-toast-' + type;
    var icon = document.createElement('i');
    icon.className = 'fa-solid ' + (type === 'success' ? 'fa-circle-check' : 'fa-triangle-exclamation');
    var text = document.createElement('span');
    text.textContent = message;
    box.appendChild(icon);
    box.appendChild(text);
    toastContainer().appendChild(box);
    requestAnimationFrame(function () { box.classList.add('is-visible'); });
    var hide = function () {
      box.classList.remove('is-visible');
      setTimeout(function () { box.remove(); }, 250);
    };
    var timer = setTimeout(hide, 5500);
    box.addEventListener('click', function () { clearTimeout(timer); hide(); });
  }
  window.adminToast = toast;

  // -----------------------------------------------------------------
  // Shared helpers
  // -----------------------------------------------------------------
  function badgeClassFor(status) {
    if (status === 'confirmed' || status === 'completed') return 'badge-confirmed';
    if (status === 'cancelled' || status === 'failed') return 'badge-cancelled';
    return 'badge-pending';
  }

  function titleCase(s) {
    return String(s || '').charAt(0).toUpperCase() + String(s || '').slice(1);
  }

  function resetButton(btn) {
    if (btn && btn.dataset.originalHtml !== undefined) {
      btn.disabled = false;
      btn.innerHTML = btn.dataset.originalHtml;
    }
  }

  function busyButton(btn, busyLabel) {
    if (!btn) return;
    btn.dataset.originalHtml = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> ' + busyLabel;
  }

  /**
   * Submit a form via fetch, asking the backend for JSON (see wants_json()).
   * onSuccess/onError receive the parsed payload; the caller is responsible
   * for re-enabling its own button — this keeps the busy state visible for
   * exactly as long as the caller wants it.
   */
  function ajaxSubmit(form, busyLabel, onSuccess, onError) {
    var btn = form.querySelector('button[type="submit"]');
    busyButton(btn, busyLabel);
    fetch(form.getAttribute('action'), {
      method: 'POST',
      body: new FormData(form),
      headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
    })
      .then(function (r) { return r.json().then(function (data) { return { httpOk: r.ok, data: data }; }); })
      .then(function (result) {
        if (!result.data || !result.data.ok) {
          toast('error', (result.data && result.data.message) || 'Something went wrong. Please try again.');
          resetButton(btn);
          if (onError) onError(result.data, btn);
          return;
        }
        toast('success', result.data.message);
        if (onSuccess) onSuccess(result.data, btn);
      })
      .catch(function () {
        toast('error', 'Network error — please check your connection and try again.');
        resetButton(btn);
        if (onError) onError(null, btn);
      });
  }

  // -----------------------------------------------------------------
  // Booking status update (pending → confirmed/cancelled, confirmed → cancelled)
  // -----------------------------------------------------------------
  var STATUS_TRANSITIONS = {
    pending: ['pending', 'confirmed', 'cancelled'],
    confirmed: ['confirmed', 'cancelled'],
    ongoing: ['ongoing'],
    completed: ['completed'],
    cancelled: ['cancelled'],
  };

  function rebuildStatusSelect(select, status) {
    var options = STATUS_TRANSITIONS[status] || [status];
    select.innerHTML = '';
    options.forEach(function (st) {
      var opt = document.createElement('option');
      opt.value = st;
      opt.textContent = titleCase(st);
      if (st === status) opt.selected = true;
      select.appendChild(opt);
    });
  }

  document.querySelectorAll('form[data-ajax="booking-status"]').forEach(function (form) {
    var scope = form.closest('[data-scope="booking"]') || document;
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      ajaxSubmit(form, 'Updating…', function (data, btn) {
        var badge = scope.querySelector('[data-role="status-badge"]');
        if (badge) { badge.className = 'badge ' + badgeClassFor(data.status); badge.textContent = data.statusLabel; }
        var stat = scope.querySelector('[data-role="status-stat"]');
        if (stat) stat.textContent = data.statusLabel;
        var select = form.querySelector('select[name="status"]');
        if (select) rebuildStatusSelect(select, data.status);
        resetButton(btn);
        if (data.status === 'cancelled') {
          var extendCard = scope.querySelector('[data-role="extend-card"]');
          if (extendCard) extendCard.remove();
        }
      });
    });
  });

  // -----------------------------------------------------------------
  // Manual payment verify / reject — used on both the booking detail
  // page and the payments list (one row per payment).
  // -----------------------------------------------------------------
  document.querySelectorAll('form[data-ajax="verify-payment"], form[data-ajax="reject-payment"]').forEach(function (form) {
    var isVerify = form.dataset.ajax === 'verify-payment';
    // payment-row scopes the payment badge/actions (works on both the
    // booking detail page and each row of the payments list); booking
    // scope — if present, i.e. we're on the booking detail page — also
    // gets its status badge/stat refreshed, since verifying a deposit
    // auto-confirms the booking.
    var scope = form.closest('[data-scope="payment-row"]') || form.closest('tr') || document;
    var bookingScope = form.closest('[data-scope="booking"]');
    form.addEventListener('submit', function (e) {
      e.preventDefault();
      ajaxSubmit(form, isVerify ? 'Verifying…' : 'Rejecting…', function (data) {
        var payBadge = scope.querySelector('[data-role="payment-status-badge"]');
        if (payBadge && data.paymentStatus) { payBadge.className = 'badge ' + badgeClassFor(data.paymentStatus); payBadge.textContent = titleCase(data.paymentStatus); }
        var payStat = scope.querySelector('[data-role="payment-stat"]');
        if (payStat && data.paymentStatus) payStat.textContent = titleCase(data.paymentStatus);
        if (data.bookingStatus && bookingScope) {
          var statusBadge = bookingScope.querySelector('[data-role="status-badge"]');
          if (statusBadge) { statusBadge.className = 'badge ' + badgeClassFor(data.bookingStatus); statusBadge.textContent = titleCase(data.bookingStatus); }
          var statusStat = bookingScope.querySelector('[data-role="status-stat"]');
          if (statusStat) statusStat.textContent = titleCase(data.bookingStatus);
          var statusSelect = bookingScope.querySelector('select[name="status"]');
          if (statusSelect) rebuildStatusSelect(statusSelect, data.bookingStatus);
        }
        var flag = scope.querySelector('[data-role="needs-verify-flag"]');
        if (flag) flag.remove();
        var actions = scope.querySelector('[data-role="payment-actions"]');
        if (actions) actions.remove();
      });
      // Buttons are removed from the DOM on success along with the whole
      // actions block, so there's nothing to reset there; on error the
      // shared ajaxSubmit() already restores this button for a retry.
    });
  });

  // -----------------------------------------------------------------
  // Extend booking — live conflict pre-check as staff pick a date, plus
  // AJAX submit. This is the "car is booked during that period" alert.
  // -----------------------------------------------------------------
  document.querySelectorAll('form[data-ajax="extend"]').forEach(function (form) {
    var input = form.querySelector('input[name="new_return_date"]');
    var checkUrl = form.dataset.checkUrl;
    var submitBtn = form.querySelector('button[type="submit"]');
    if (!input || !checkUrl) return;

    var alertBox = document.createElement('div');
    alertBox.className = 'booking-availability-message';
    alertBox.setAttribute('role', 'status');
    alertBox.setAttribute('aria-live', 'polite');
    alertBox.hidden = true;
    var dateGroup = input.closest('.form-group') || input;
    form.insertBefore(alertBox, dateGroup);

    function showAlert(type, message) {
      alertBox.className = 'booking-availability-message' + (type ? ' ' + type : '');
      alertBox.textContent = message || '';
      alertBox.hidden = !message;
    }

    var timer = null;
    var controller = null;
    function checkConflict() {
      if (!input.value) { showAlert('', ''); if (submitBtn) submitBtn.disabled = false; return; }
      if (timer) clearTimeout(timer);
      timer = setTimeout(function () {
        if (controller && controller.abort) controller.abort();
        controller = window.AbortController ? new AbortController() : null;
        showAlert('loading', 'Checking this vehicle\u2019s schedule\u2026');
        var url = checkUrl + (checkUrl.indexOf('?') === -1 ? '?' : '&') + 'new_return_date=' + encodeURIComponent(input.value);
        fetch(url, { headers: { 'Accept': 'application/json' }, signal: controller ? controller.signal : undefined })
          .then(function (r) { return r.json(); })
          .then(function (data) {
            if (!data.ok) { showAlert('warning', data.message || 'Could not check this date.'); return; }
            if (data.conflict) {
              showAlert('error', data.message);
              if (submitBtn) submitBtn.disabled = true;
            } else if (data.warning) {
              showAlert('warning', data.warning);
              if (submitBtn) submitBtn.disabled = false;
            } else {
              showAlert('success', data.message || 'This vehicle is free through the new return date.');
              if (submitBtn) submitBtn.disabled = false;
            }
          })
          .catch(function (err) {
            if (err && err.name === 'AbortError') return;
            showAlert('warning', 'Could not check availability right now. You can still try to extend — it will be re-checked on submit.');
          });
      }, 300);
    }
    input.addEventListener('input', checkConflict);
    input.addEventListener('change', checkConflict);

    form.addEventListener('submit', function (e) {
      e.preventDefault();
      ajaxSubmit(form, 'Extending…', function (data, btn) {
        showAlert('success', data.message);
        if (data.newReturnDate) {
          var returnField = document.querySelector('[data-role="return-date-value"]');
          if (returnField) returnField.textContent = returnField.textContent.replace(/·.*/, '\u00b7 ' + data.newReturnDate);
        }
        if (data.totalPrice) {
          var priceField = document.querySelector('[data-role="total-price-stat"]');
          if (priceField) priceField.textContent = data.totalPrice;
        }
        input.value = '';
        resetButton(btn);
      });
    });
  });
})();
