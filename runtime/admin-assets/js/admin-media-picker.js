/* Website admin: image picker modal (media library + upload). Needs views/shop-media-modal.php on the page.
   WBMediaPicker.open({ single: bool, onUse: list => … }) with list items { ref, url }. */
(function ($) {
  'use strict';
  window.WBMediaPicker = (function () {
    const $m = $('#shopMediaModal');
    if (!$m.length) return null;
    let page = 1, selected = new Map(), onUse = null, single = false;
    function load(reset) {
      if (reset) { page = 1; $('#shopMediaGrid').empty(); }
      $.getJSON(WB.url('api/media'), { page: page, per_page: 30, q: $('#shopMediaQ').val() }).done(function (res) {
        res.rows.forEach(function (r) {
          const sel = selected.has(r.path);
          $('#shopMediaGrid').append('<button type="button" class="shop-media-item' + (sel ? ' is-selected' : '') + '" data-path="' + WB.esc(r.path) + '" data-url="' + WB.esc(r.url) + '" title="' + WB.esc(r.original_name || '') + '"><img src="' + WB.esc(r.url) + '" alt="' + WB.esc(r.alt_text || '') + '" loading="lazy"><i class="bi bi-check-circle-fill"></i></button>');
        });
        $('#shopMediaMore').prop('hidden', res.page >= res.pages);
        if (!res.total) $('#shopMediaGrid').html('<p class="text-muted">No images yet. Upload some.</p>');
      });
    }
    $m.on('click', '.shop-media-item', function () {
      const $b = $(this), p = $b.data('path');
      if (single) { selected.clear(); $('#shopMediaGrid .is-selected').removeClass('is-selected'); }
      if (selected.has(p)) { selected.delete(p); $b.removeClass('is-selected'); } else { selected.set(p, $b.data('url')); $b.addClass('is-selected'); }
    });
    $('#shopMediaMore').on('click', () => { page++; load(false); });
    let t;
    $('#shopMediaQ').on('input', () => { clearTimeout(t); t = setTimeout(() => load(true), 350); });
    $('#shopMediaUpload').on('change', function () {
      if (!this.files.length) return;
      const fd = new FormData();
      Array.from(this.files).forEach(f => fd.append('files[]', f));
      fd.append('folder', $m.data('folder') || 'products');
      const $lbl = $(this).closest('label').addClass('disabled');
      $.ajax({ url: WB.url('api/media'), method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
        .done(res => { (res.items || []).forEach(it => { if (single) selected.clear(); selected.set(it.path, it.url); }); WB.toast(res.message); load(true); })
        .fail(x => x.responseJSON && WB.toast(x.responseJSON.message, 'danger'))
        .always(() => { $lbl.removeClass('disabled'); this.value = ''; });
    });
    $('#shopMediaUse').on('click', function () {
      if (onUse) onUse(Array.from(selected, ([ref, url]) => ({ ref: ref, url: url })));
      bootstrap.Modal.getOrCreateInstance($m[0]).hide();
    });
    return {
      open: function (opts) { single = !!opts.single; onUse = opts.onUse; selected = new Map(); load(true); bootstrap.Modal.getOrCreateInstance($m[0]).show(); }
    };
  })();

})(jQuery);
