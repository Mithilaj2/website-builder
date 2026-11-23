/* CareLine Health — small progressive enhancements (no dependencies besides Bootstrap). */
(function () {
  'use strict';

  var header = document.querySelector('[data-crl-header]');
  if (header) {
    var onScroll = function () { header.classList.toggle('is-scrolled', window.scrollY > 10); };
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
  }

  var reduced = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
  if (!('IntersectionObserver' in window) || reduced) return;

  // Count-up numbers in the statistics band
  var counters = document.querySelectorAll('[data-crl-count]');
  var countObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (!entry.isIntersecting) return;
      var el = entry.target;
      var target = parseFloat(el.getAttribute('data-crl-count'));
      countObserver.unobserve(el);
      if (isNaN(target) || target <= 0) return;
      var decimals = (String(target).split('.')[1] || '').length;
      var start = performance.now();
      var tick = function (now) {
        var p = Math.min(1, (now - start) / 1400);
        el.textContent = (target * (1 - Math.pow(1 - p, 3))).toFixed(decimals);
        if (p < 1) requestAnimationFrame(tick);
      };
      requestAnimationFrame(tick);
    });
  }, { threshold: 0.4 });
  counters.forEach(function (c) { countObserver.observe(c); });

  // Gentle reveal of cards and headings
  var targets = document.querySelectorAll('.crl-card, .crl-dept, .crl-feature, .crl-step, .crl-member, .crl-doctor, .crl-head, .crl-timetable, .crl-shot');
  var revealObserver = new IntersectionObserver(function (entries) {
    entries.forEach(function (entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('is-visible');
        revealObserver.unobserve(entry.target);
      }
    });
  }, { threshold: 0.1 });
  targets.forEach(function (t) {
    t.classList.add('crl-reveal');
    revealObserver.observe(t);
  });
})();
