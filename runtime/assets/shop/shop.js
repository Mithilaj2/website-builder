/* Website shop pages: no dependencies. */
(function () {
  'use strict';
  // Product gallery thumbnails
  document.querySelectorAll('[data-wbshop-gallery]').forEach(function (g) {
    var main = g.querySelector('[data-wbshop-main]');
    g.querySelectorAll('[data-wbshop-thumb]').forEach(function (b) {
      b.addEventListener('click', function () {
        if (main) main.src = b.getAttribute('data-wbshop-thumb');
        g.querySelectorAll('[data-wbshop-thumb]').forEach(function (x) { x.classList.toggle('is-active', x === b); });
      });
    });
  });
  // Quantity steppers
  document.querySelectorAll('[data-wbshop-qty]').forEach(function (box) {
    var input = box.querySelector('input');
    box.querySelectorAll('[data-step]').forEach(function (b) {
      b.addEventListener('click', function () {
        var min = parseInt(input.min || '0', 10), max = parseInt(input.max || '20', 10);
        var v = Math.max(min, Math.min(max, (parseInt(input.value, 10) || 0) + parseInt(b.getAttribute('data-step'), 10)));
        input.value = v;
        input.dispatchEvent(new Event('change', { bubbles: true }));
      });
    });
  });
  // Cart: quantity changes save the cart; × removes the line
  document.querySelectorAll('[data-wbshop-autosave]').forEach(function (input) {
    var t;
    input.addEventListener('change', function () {
      clearTimeout(t);
      t = setTimeout(function () { input.form.submit(); }, 500);
    });
  });
  // Variant price
  document.querySelectorAll('[data-wbshop-buy]').forEach(function (form) {
    var price = document.querySelector('[data-wbshop-price]');
    form.querySelectorAll('input[name=variant_id]').forEach(function (r) {
      r.addEventListener('change', function () { if (price && r.checked) price.textContent = r.getAttribute('data-price'); });
      if (r.checked && price) price.textContent = r.getAttribute('data-price');
    });
  });
  // Sorting submits straight away
  document.querySelectorAll('[data-wbshop-autosubmit]').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
  });
  // Checkout: totals follow the chosen payment method (cash on delivery may add a fee)
  document.querySelectorAll('input[name=payment]').forEach(function (r) {
    r.addEventListener('change', function () {
      document.querySelectorAll('[data-wbshop-for]').forEach(function (box) { box.hidden = box.getAttribute('data-wbshop-for') !== r.value; });
    });
  });
  // Checkout: avoid double orders
  document.querySelectorAll('[data-wbshop-checkout]').forEach(function (f) {
    f.addEventListener('submit', function () {
      var b = f.querySelector('button[type=submit]');
      if (b) { setTimeout(function () { b.disabled = true; b.textContent = b.getAttribute('data-busy') || 'Please wait…'; }, 0); }
    });
  });
})();
