/* Website admin: listings (properties, packages, rooms…) */
(function ($) {
  'use strict';
  const cfgEl = document.getElementById('listingConfig');
  const cfg = cfgEl ? JSON.parse(cfgEl.textContent) : {};
  const picker = window.WBMediaPicker || null;
  const thumb = url => url ? '<img src="' + WB.esc(url) + '" alt="" class="shop-thumb">' : '<span class="shop-thumb"><i class="bi bi-image"></i></span>';

  if ($('#listingTable').length) {
    const closed = $('#listingTable').data('closed-label'), one = $('#listingTable').data('singular');
    new WB.Table({
      id: 'listings', el: '#listingTable', filters: '#listingFilters', url: WB.url('api/listings'), perPage: 20, empty: 'Nothing here yet. Add your first ' + one + '.',
      columns: [
        { title: '', render: r => thumb(r.image_url) },
        { title: 'Title', render: r => '<a class="fw-semibold" href="' + WB.url('listings/' + r.id) + '">' + WB.esc(r.title) + '</a>' + (Number(r.is_featured) ? ' <i class="bi bi-star-fill text-warning small" title="Featured"></i>' : '') + '<div class="small text-muted">' + WB.esc([r.category_name, r.location].filter(Boolean).join(' · ')) + '</div>' },
        { title: 'Price', cls: 'text-nowrap', render: r => WB.esc(r.price_text) },
        { title: 'Status', render: r => r.status === 'active' ? '<span class="badge text-bg-success">Active</span>' : r.status === 'closed' ? '<span class="badge text-bg-dark">' + WB.esc(closed) + '</span>' : '<span class="badge text-bg-secondary">Draft</span>' },
        { title: 'Updated', cls: 'small text-muted text-nowrap', render: r => WB.esc(WB.datetime(r.updated_at)) },
        { title: '', cls: 'text-end text-nowrap', render: r => '<a class="btn btn-sm btn-light" href="' + WB.esc(r.url) + '" target="_blank" rel="noopener" aria-label="View"><i class="bi bi-box-arrow-up-right"></i></a> <a class="btn btn-sm btn-light" href="' + WB.url('listings/' + r.id) + '"><i class="bi bi-pencil"></i> Edit</a> ' +
          '<button class="btn btn-sm btn-light text-danger" data-post="' + WB.url('api/listings/' + r.id + '/delete') + '" data-confirm="Delete “' + WB.esc(r.title) + '”?" aria-label="Delete"><i class="bi bi-trash"></i></button>' }
      ]
    });
  }

  const $f = $('#listingForm');
  if ($f.length) {
    let images = (cfg.images || []).slice();
    const draw = function () {
      const $box = $('#listingImages').empty();
      images.forEach(function (im, i) {
        $box.append('<div class="prod-image' + (i === 0 ? ' is-main' : '') + '"><img src="' + WB.esc(im.url) + '" alt=""><input type="hidden" name="images[]" value="' + WB.esc(im.ref) + '">' +
          (i === 0 ? '<span class="badge text-bg-dark">Cover</span>' : '<button type="button" class="btn btn-sm btn-light" data-img-main="' + i + '">Make cover</button>') +
          '<button type="button" class="btn-close" data-img-remove="' + i + '" aria-label="Remove photo"></button></div>');
      });
      if (!images.length) $box.html('<p class="text-muted small mb-0">No photos yet.</p>');
    };
    draw();
    $('#listingAddImages').on('click', () => picker && picker.open({ onUse: list => { list.forEach(it => { if (!images.some(x => x.ref === it.ref) && images.length < 12) images.push(it); }); draw(); } }));
    $f.on('click', '[data-img-remove]', function () { images.splice(Number($(this).data('img-remove')), 1); draw(); });
    $f.on('click', '[data-img-main]', function () { images.unshift(images.splice(Number($(this).data('img-main')), 1)[0]); draw(); });
    if (window.tinymce && cfg.tinymce) {
      tinymce.init({ selector: '#listingDesc', base_url: cfg.tinymce, suffix: '.min', menubar: false, branding: false, promotion: false, license_key: 'gpl', plugins: 'lists link autolink table', height: 320, convert_urls: false,
        toolbar: 'blocks | bold italic | bullist numlist | link table | removeformat', block_formats: 'Paragraph=p; Heading 3=h3; Heading 4=h4', content_style: 'body{font-family:system-ui,sans-serif;font-size:15px}' });
    }
    $f[0].addEventListener('submit', function () { if (window.tinymce) tinymce.triggerSave(); }, true);
    $(document).ajaxComplete(function (e, xhr, opts) {
      if (opts.url !== $f.attr('action') || !xhr.responseJSON) return;
      $f.find('[data-err=image]').text((xhr.responseJSON.errors || {}).image || '');
    });
  }

  if ($('#lcatTable').length) {
    const cats = {};
    new WB.Table({
      id: 'lcats', el: '#lcatTable', url: WB.url('api/listing-categories'), perPage: 100, empty: 'Nothing here yet.',
      columns: [
        { title: '', render: r => { cats[r.id] = r; return thumb(r.image_url); } },
        { title: 'Name', render: r => '<span class="fw-semibold">' + WB.esc(r.name) + '</span><div class="small text-muted">' + WB.esc(r.description || '') + '</div>' },
        { title: 'Count', cls: 'text-center', render: r => WB.esc(r.listing_count) },
        { title: 'Shown', render: r => r.status === 'active' ? '<span class="badge text-bg-success">Shown</span>' : '<span class="badge text-bg-secondary">Hidden</span>' },
        { title: '', cls: 'text-end text-nowrap', render: r => '<button class="btn btn-sm btn-light" data-lcat-edit="' + r.id + '"><i class="bi bi-pencil"></i> Edit</button> <button class="btn btn-sm btn-light text-danger" data-post="' + WB.url('api/listing-categories/' + r.id + '/delete') + '" data-confirm="Delete “' + WB.esc(r.name) + '”?" aria-label="Delete"><i class="bi bi-trash"></i></button>' }
      ]
    });
    const setImg = (ref, url) => { $('#lcatForm [name=image]').val(ref || ''); $('#lcatThumb').attr('src', url || '').toggle(!!url); };
    $('#lcatAdd').on('click', () => { WB.openForm('#lcatModal', { status: 'active', sort_order: 0 }, { action: WB.url('api/listing-categories'), title: 'Add' }); setImg('', ''); });
    $(document).on('click', '[data-lcat-edit]', function () { const c = cats[$(this).data('lcat-edit')]; WB.openForm('#lcatModal', c, { action: WB.url('api/listing-categories/' + c.id), title: 'Edit' }); setImg(c.image, c.image_url); });
    $('#lcatPick').on('click', () => picker && picker.open({ single: true, onUse: list => list[0] && setImg(list[0].ref, list[0].url) }));
    $('#lcatClear').on('click', () => setImg('', ''));
  }
})(jQuery);
