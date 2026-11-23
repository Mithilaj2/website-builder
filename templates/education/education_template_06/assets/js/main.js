/* Forge Terminal — small progressive enhancements (no dependencies besides Bootstrap). */
(function () {
  'use strict';

  var header = document.querySelector('[data-cfa-header]');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 10); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!('IntersectionObserver' in window) || reduced) return;

  var counters = document.querySelectorAll('[data-cfa-count]');
  var countObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      var el = entry.target;
      var raw = el.getAttribute('data-cfa-count');
      var target = parseFloat(raw);
      countObserver.unobserve(el);
      if (isNaN(target) || target <= 0 || String(target) !== raw.replace(/^0+(?=\d)/, '')) return;
      var original = el.textContent;
      var decimals = (raw.split('.')[1] || '').length;
      var start = performance.now();
      var tick = function (now) {
        var p = Math.min(1, (now - start) / 1400);
        el.textContent = (target * (1 - Math.pow(1 - p, 3))).toFixed(decimals);
        if (p < 1) requestAnimationFrame(tick); else el.textContent = original;
      };
      requestAnimationFrame(tick);
    });
  }, { threshold: 0.4 });
  counters.forEach(function (c) { countObserver.observe(c); });

  var targets = document.querySelectorAll('.cfa-card, .cfa-feature, .cfa-step, .cfa-member, .cfa-about-media, .cfa-about-collage, .cfa-head, .cfa-row-item, .cfa-shot');
  var revealObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });
  targets.forEach(function (t) {
    t.classList.add('cfa-reveal');
    revealObserver.observe(t);
  });
})();
