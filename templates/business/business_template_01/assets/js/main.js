/* Meridian Corporate — small progressive enhancements (no dependencies besides Bootstrap). */
(function () {
  'use strict';

  // Header shadow once the page scrolls
  var header = document.querySelector('[data-mrd-header]');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 10); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!('IntersectionObserver' in window) || reduced) return;

  // Count-up numbers in the statistics band
  var counters = document.querySelectorAll('[data-mrd-count]');
  var countObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      var el = entry.target;
      var target = parseFloat(el.getAttribute('data-mrd-count'));
      countObserver.unobserve(el);
      if (isNaN(target) || target <= 0) return;
      var decimals = (String(target).split('.')[1] || '').length;
      var start = performance.now();
      var duration = 1400;
      var tick = function (now) {
        var p = Math.min(1, (now - start) / duration);
        var eased = 1 - Math.pow(1 - p, 3);
        el.textContent = (target * eased).toFixed(decimals);
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    });
  }, { threshold: 0.4 });
  counters.forEach(function (c) { countObserver.observe(c); });

  // Gentle reveal of sections
  var targets = document.querySelectorAll('.mrd-card, .mrd-feature, .mrd-step, .mrd-member, .mrd-about-media, .mrd-head');
  var revealObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.12 });
  targets.forEach(function (t) {
    t.classList.add('mrd-reveal');
    revealObserver.observe(t);
  });
})();
