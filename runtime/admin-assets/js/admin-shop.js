/* Website admin: shop (orders, products, categories, coupons, settings) */
(function ($) {
  'use strict';
  const cfgEl = document.getElementById('shopConfig');
  const cfg = cfgEl ? JSON.parse(cfgEl.textContent) : {};
  const statusColour = { pending_payment: 'secondary', placed: 'primary', processing: 'info', shipped: 'warning', delivered: 'success', cancelled: 'danger' };
  const thumb = url => url ? '<img src="' + WB.esc(url) + '" alt="" class="shop-thumb">' : '<span class="shop-thumb shop-thumb-empty"><i class="bi bi-image"></i></span>';

  /* ---------------- orders ---------------- */
  if ($('#orderTable').length) {
    new WB.Table({
      id: 'orders', el: '#orderTable', filters: '#orderFilters', url: WB.url('api/orders'), perPage: 20,
      empty: 'No orders yet. Orders placed in your shop appear here.',
      columns: [
        { title: 'Order', render: r => '<a class="fw-semibold font-monospace" href="' + WB.url('orders/' + r.id) + '">' + WB.esc(r.order_no) + '</a><div class="small text-muted text-nowrap">' + WB.esc(WB.datetime(r.created_at)) + '</div>' },
        { title: 'Customer', render: r => WB.esc(r.customer_name) + '<div class="small text-muted">' + WB.esc(r.customer_phone) + ' · ' + WB.esc(r.city) + '</div>' },
        { title: 'Items', cls: 'text-center', render: r => WB.esc(r.items || 0) },
        { title: 'Total', cls: 'text-end text-nowrap fw-semibold', render: r => WB.esc(r.total_text) },
        { title: 'Payment', render: r => '<span class="small">' + (r.payment_method === 'cod' ? 'COD' : 'Online') + '</span> ' + (r.payment_status === 'paid' ? '<span class="badge text-bg-success">Paid</span>' : r.payment_status === 'failed' ? '<span class="badge text-bg-danger">Failed</span>' : '<span class="badge text-bg-light border">Unpaid</span>') },
        { title: 'Status', render: r => '<span class="badge text-bg-' + (statusColour[r.status] || 'secondary') + '">' + WB.esc(r.status_text) + '</span>' },
        { title: '', cls: 'text-end', render: r => '<a class="btn btn-sm btn-light" href="' + WB.url('orders/' + r.id) + '">Open</a>' }
      ]
    });
    $('#orderTable').on('wb:loaded', function (e, res) {
      $('#orderTotal').text(res.total ? 'Sales in this list: ' + res.sales_total_text : '');
      $('#orderExport').attr('href', WB.url('api/orders/export') + '?' + $('#orderFilters').serialize());
    });
  }
  $('#orderForm').on('wb:saved', () => setTimeout(() => window.location.reload(), 600));

  /* ---------------- products list ---------------- */
  if ($('#productTable').length) {
    new WB.Table({
      id: 'products', el: '#productTable', filters: '#productFilters', url: WB.url('api/products'), perPage: 20,
      empty: 'No products yet. Add your first product.',
      columns: [
        { title: '', render: r => thumb(r.image_url) },
        { title: 'Product', render: r => '<a class="fw-semibold" href="' + WB.url('products/' + r.id) + '">' + WB.esc(r.name) + '</a>' + (Number(r.is_featured) ? ' <i class="bi bi-star-fill text-warning small" title="Featured"></i>' : '') +
          '<div class="small text-muted">' + WB.esc([r.category_name, r.sku].filter(Boolean).join(' · ')) + '</div>' },
        { title: 'Price', cls: 'text-end text-nowrap', render: r => WB.esc(r.price_text) },
        { title: 'Stock', render: r => !Number(r.track_stock) ? '<span class="text-muted small">Not tracked</span>' : Number(r.variant_count) > 0
          ? WB.esc(r.variant_stock) + ' <span class="text-muted small">in ' + WB.esc(r.variant_count) + ' options</span>'
          : '<input class="form-control form-control-sm d-inline-block" style="width:80px" value="' + WB.esc(r.stock) + '" data-stock="' + r.id + '" aria-label="Stock of ' + WB.esc(r.name) + '">' },
        { title: 'Status', render: r => r.status === 'active' ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Draft</span>' },
        { title: '', cls: 'text-end text-nowrap', render: r => '<a class="btn btn-sm btn-light" href="' + WB.url('products/' + r.id) + '"><i class="bi bi-pencil"></i> Edit</a> ' +
          '<button class="btn btn-sm btn-light text-danger" data-post="' + WB.url('api/products/' + r.id + '/delete') + '" data-confirm="Delete “' + WB.esc(r.name) + '”? Past orders keep their details." aria-label="Delete"><i class="bi bi-trash"></i></button>' }
      ]
    });
    $(document).on('change', '[data-stock]', function () {
      const $i = $(this);
      WB.post(WB.url('api/products/' + $i.data('stock') + '/stock'), { stock: $i.val() })
        .done(res => WB.toast(res.message)).fail(x => x.responseJSON && WB.toast(x.responseJSON.message, 'danger'));
    });
  }

  /* ---------------- media picker (product images, category image) ---------------- */
  const picker = (function () {
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
      fd.append('folder', 'products');
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

  /* ---------------- product form ---------------- */
  const $pf = $('#productForm');
  if ($pf.length) {
    let images = (cfg.images || []).slice();
    function drawImages() {
      const $box = $('#prodImages').empty();
      images.forEach(function (im, i) {
        $box.append('<div class="prod-image' + (i === 0 ? ' is-main' : '') + '"><img src="' + WB.esc(im.url) + '" alt=""><input type="hidden" name="images[]" value="' + WB.esc(im.ref) + '">' +
          (i === 0 ? '<span class="badge text-bg-dark">Main</span>' : '<button type="button" class="btn btn-sm btn-light" data-img-main="' + i + '">Make main</button>') +
          '<button type="button" class="btn-close" data-img-remove="' + i + '" aria-label="Remove image"></button></div>');
      });
      if (!images.length) $box.html('<p class="text-muted small mb-0">No images yet.</p>');
    }
    drawImages();
    $('#prodAddImages').on('click', () => picker.open({ onUse: list => { list.forEach(it => { if (!images.some(x => x.ref === it.ref) && images.length < 9) images.push(it); }); drawImages(); } }));
    $pf.on('click', '[data-img-remove]', function () { images.splice(Number($(this).data('img-remove')), 1); drawImages(); });
    $pf.on('click', '[data-img-main]', function () { const i = Number($(this).data('img-main')); images.unshift(images.splice(i, 1)[0]); drawImages(); });

    let vIndex = $('#variantTable tbody tr').length + 1000;
    function syncVariants() {
      const has = $('#variantTable tbody tr').length > 0;
      $('#variantTable').prop('hidden', !has);
      $('[data-simple-stock]').toggle(!has);
    }
    $('#addVariant').on('click', function () {
      const i = vIndex++;
      $('#variantTable tbody').append('<tr><td><input type="hidden" name="variants[' + i + '][id]" value="0"><input class="form-control form-control-sm" name="variants[' + i + '][label]" maxlength="60" placeholder="e.g. Medium"></td>' +
        '<td><input class="form-control form-control-sm" name="variants[' + i + '][price]" inputmode="decimal"></td><td><input class="form-control form-control-sm" name="variants[' + i + '][stock]" value="0" inputmode="numeric" style="max-width:90px"></td>' +
        '<td><input class="form-control form-control-sm" name="variants[' + i + '][sku]" maxlength="60"></td><td class="text-end"><button type="button" class="btn btn-sm btn-light" data-remove-variant aria-label="Remove option"><i class="bi bi-x-lg"></i></button></td></tr>');
      syncVariants();
      $('#variantTable tbody tr:last input:visible:first').trigger('focus');
    });
    $pf.on('click', '[data-remove-variant]', function () { $(this).closest('tr').remove(); syncVariants(); });
    syncVariants();

    if (window.tinymce && cfg.tinymce) {
      tinymce.init({
        selector: '#prodDesc', base_url: cfg.tinymce, suffix: '.min', menubar: false, branding: false, promotion: false, license_key: 'gpl',
        plugins: 'lists link autolink table', height: 300, convert_urls: false,
        toolbar: 'blocks | bold italic | bullist numlist | link table | removeformat',
        block_formats: 'Paragraph=p; Heading 3=h3; Heading 4=h4', content_style: 'body{font-family:system-ui,sans-serif;font-size:15px}'
      });
    }
    $pf[0].addEventListener('submit', function () { if (window.tinymce) tinymce.triggerSave(); }, true);
    $pf.on('wb:saved', function (e, res) { if (!res.redirect) $pf.find('[data-err]').text(''); });
    $(document).ajaxComplete(function (e, xhr, opts) {
      if (opts.url !== $pf.attr('action') || !xhr.responseJSON || !xhr.responseJSON.errors) return;
      const er = xhr.responseJSON.errors;
      $pf.find('[data-err=image]').text(er.image || er.gallery || '');
      $pf.find('[data-err=variants]').text(er.variants || '');
    });
  }

  /* ---------------- categories ---------------- */
  if ($('#catTable').length) {
    const cats = {};
    new WB.Table({
      id: 'categories', el: '#catTable', url: WB.url('api/categories'), perPage: 100,
      empty: 'No categories yet.',
      columns: [
        { title: '', render: r => { cats[r.id] = r; return thumb(r.image_url); } },
        { title: 'Category', render: r => '<span class="fw-semibold">' + WB.esc(r.name) + '</span><div class="small text-muted">' + WB.esc(r.description || '') + '</div>' },
        { title: 'Products', cls: 'text-center', render: r => WB.esc(r.product_count) },
        { title: 'Shown', render: r => r.status === 'active' ? '<span class="badge text-bg-success">Shown</span>' : '<span class="badge text-bg-secondary">Hidden</span>' },
        { title: '', cls: 'text-end text-nowrap', render: r => '<a class="btn btn-sm btn-light" href="' + WB.esc(r.url) + '" target="_blank" rel="noopener" aria-label="View"><i class="bi bi-box-arrow-up-right"></i></a> <button class="btn btn-sm btn-light" data-cat-edit="' + r.id + '"><i class="bi bi-pencil"></i> Edit</button> ' +
          '<button class="btn btn-sm btn-light text-danger" data-post="' + WB.url('api/categories/' + r.id + '/delete') + '" data-confirm="Delete “' + WB.esc(r.name) + '”? Its products stay, without a category." aria-label="Delete"><i class="bi bi-trash"></i></button>' }
      ]
    });
    const setImg = (ref, url) => { $('#catForm [name=image]').val(ref || ''); $('#catThumb').attr('src', url || '').toggle(!!url); };
    $('#catAdd').on('click', () => { WB.openForm('#catModal', { status: 'active', sort_order: 0 }, { action: WB.url('api/categories'), title: 'Add category' }); setImg('', ''); });
    $(document).on('click', '[data-cat-edit]', function () {
      const c = cats[$(this).data('cat-edit')];
      WB.openForm('#catModal', c, { action: WB.url('api/categories/' + c.id), title: 'Edit category' });
      setImg(c.image, c.image_url);
    });
    $('#catPick').on('click', () => picker.open({ single: true, onUse: list => list[0] && setImg(list[0].ref, list[0].url) }));
    $('#catClear').on('click', () => setImg('', ''));
  }

  /* ---------------- coupons ---------------- */
  if ($('#couponTable').length) {
    const owner = $('#couponTable').data('owner') === 1 || $('#couponTable').data('owner') === '1';
    const rows = {};
    new WB.Table({
      id: 'coupons', el: '#couponTable', url: WB.url('api/coupons'), perPage: 100,
      empty: 'No coupons yet.',
      columns: [
        { title: 'Code', render: r => { rows[r.id] = r; return '<span class="font-monospace fw-semibold">' + WB.esc(r.code) + '</span><div class="small text-muted">' + WB.esc(r.description || '') + '</div>'; } },
        { title: 'Discount', render: r => WB.esc(r.value_text) + (r.min_order_text ? '<div class="small text-muted">Min. order ' + WB.esc(r.min_order_text) + '</div>' : '') },
        { title: 'Valid', render: r => '<span class="small">' + WB.esc(r.starts_at || '…') + ' – ' + WB.esc(r.ends_at || '…') + '</span>' },
        { title: 'Used', cls: 'text-center', render: r => WB.esc(r.used_count) + (r.usage_limit ? ' / ' + WB.esc(r.usage_limit) : '') },
        { title: 'Status', render: r => r.status === 'active' ? '<span class="badge text-bg-success">Active</span>' : '<span class="badge text-bg-secondary">Paused</span>' },
        { title: '', cls: 'text-end text-nowrap', render: r => owner ? '<button class="btn btn-sm btn-light" data-coupon-edit="' + r.id + '"><i class="bi bi-pencil"></i> Edit</button> <button class="btn btn-sm btn-light text-danger" data-post="' + WB.url('api/coupons/' + r.id + '/delete') + '" data-confirm="Delete coupon ' + WB.esc(r.code) + '?" aria-label="Delete"><i class="bi bi-trash"></i></button>' : '' }
      ]
    });
    const syncType = function () {
      const t = $('#couponType').val();
      $('[data-coupon-value]').toggle(t !== 'free_shipping');
      $('[data-coupon-max]').toggle(t === 'percent');
      $('#couponValueLabel').text(t === 'fixed' ? 'Amount off (₹)' : 'Percent off');
    };
    $('#couponType').on('change', syncType);
    $('#couponAdd').on('click', () => { WB.openForm('#couponModal', { type: 'percent', status: 'active' }, { action: WB.url('api/coupons'), title: 'Add coupon' }); syncType(); });
    $(document).on('click', '[data-coupon-edit]', function () {
      const c = rows[$(this).data('coupon-edit')];
      WB.openForm('#couponModal', $.extend({}, c, { value: c.value_input, min_order: c.min_order_input, max_discount: c.max_discount_input }), { action: WB.url('api/coupons/' + c.id), title: 'Edit coupon' });
      syncType();
    });
  }
})(jQuery);
