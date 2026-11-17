/* Listing pages: photo lightbox, auto-submit sort. No dependencies. */
(function () {
  'use strict';
  document.querySelectorAll('[data-wblist-autosubmit]').forEach(function (s) {
    s.addEventListener('change', function () { s.form.submit(); });
  });
  var gallery = document.querySelector('[data-wblist-gallery]');
  var lb = document.querySelector('[data-wblist-lightbox]');
  if (!gallery || !lb) return;
  var tpl = gallery.querySelector('[data-wblist-images]');
  var srcs = tpl ? Array.prototype.map.call(tpl.content.querySelectorAll('img'), function (i) { return i.getAttribute('src'); }) : [];
  var img = lb.querySelector('[data-wblist-lb-img]'), count = lb.querySelector('[data-wblist-lb-count]'), at = 0, opener = null;
  function show(i) { at = (i + srcs.length) % srcs.length; img.src = srcs[at]; count.textContent = (at + 1) + ' / ' + srcs.length; }
  function close() { lb.hidden = true; document.body.style.overflow = ''; if (opener) opener.focus(); }
  gallery.querySelectorAll('[data-wblist-open]').forEach(function (b) {
    b.addEventListener('click', function () { opener = b; show(parseInt(b.getAttribute('data-wblist-open'), 10)); lb.hidden = false; document.body.style.overflow = 'hidden'; lb.querySelector('[data-wblist-close]').focus(); });
  });
  lb.querySelectorAll('[data-wblist-step]').forEach(function (b) { b.addEventListener('click', function () { show(at + parseInt(b.getAttribute('data-wblist-step'), 10)); }); });
  lb.querySelector('[data-wblist-close]').addEventListener('click', close);
  lb.addEventListener('click', function (e) { if (e.target === lb) close(); });
  document.addEventListener('keydown', function (e) {
    if (lb.hidden) return;
    if (e.key === 'Escape') close();
    if (e.key === 'ArrowRight') show(at + 1);
    if (e.key === 'ArrowLeft') show(at - 1);
  });
})();
