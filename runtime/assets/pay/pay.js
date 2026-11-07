/* Website "Pay online" pages: no dependencies. */
(function () {
  'use strict';
  // Form: an option with a fixed amount fills and locks the amount; "Something else" shows the description field.
  var form = document.querySelector('[data-wbpay-form]');
  if (form) {
    var sel = form.querySelector('[data-wbpay-option]');
    var custom = form.querySelector('[data-wbpay-custom]');
    var amountBox = form.querySelector('[data-wbpay-amount]');
    var amount = form.querySelector('#wbpay-amount');
    var sync = function () {
      if (!sel) return;
      var opt = sel.options[sel.selectedIndex];
      var fixed = opt ? parseInt(opt.getAttribute('data-amount') || '0', 10) : 0;
      if (custom) custom.hidden = sel.value !== 'other';
      if (fixed > 0) {
        amount.value = (fixed / 100).toFixed(2);
        amount.readOnly = true;
        amountBox.classList.add('is-fixed');
      } else {
        if (amount.readOnly) amount.value = '';
        amount.readOnly = false;
        amountBox.classList.remove('is-fixed');
      }
    };
    if (sel) { sel.addEventListener('change', sync); sync(); }
    form.addEventListener('submit', function () {
      var b = form.querySelector('button[type=submit]');
      if (b) { b.disabled = true; b.textContent = 'Please wait…'; }
    });
  }

  // PayU: post the hashed form to PayU straight away.
  var auto = document.querySelector('[data-wbpay-autosubmit]');
  if (auto) setTimeout(function () { auto.submit(); }, 300);

  // Gateways with their own checkout script (Razorpay, Cashfree).
  var box = document.getElementById('wbPay');
  if (box) {
    var opts = JSON.parse(box.getAttribute('data-options') || '{}');
    var gw = box.getAttribute('data-gateway');
    var cancel = box.getAttribute('data-cancel');
    var open = function () {
      try {
        if (gw === 'razorpay' && window.Razorpay) {
          opts.modal = { ondismiss: function () { window.location.href = cancel; } };
          new window.Razorpay(opts).open();
        } else if (gw === 'cashfree' && window.Cashfree) {
          window.Cashfree({ mode: opts.mode }).checkout({ paymentSessionId: opts.paymentSessionId, redirectTarget: opts.redirectTarget || '_self' });
        } else {
          throw new Error('not loaded');
        }
      } catch (e) {
        var w = document.querySelector('[data-wbpay-wait]');
        if (w) w.textContent = 'The payment window could not be opened. Check your connection and press the button below.';
      }
    };
    var btn = document.querySelector('[data-wbpay-open]');
    if (btn) btn.addEventListener('click', open);
    if (document.readyState === 'complete') open(); else window.addEventListener('load', open);
  }

  var pr = document.querySelector('[data-wbpay-print]');
  if (pr) pr.addEventListener('click', function () { window.print(); });
})();
