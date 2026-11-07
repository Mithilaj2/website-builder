/* Website admin: header & footer menu editor */
(function ($) {
  'use strict';
  let pages = [];

  function itemHtml(it) {
    const page = it.page_id ? pages.find(p => p.id === Number(it.page_id)) : null;
    const target = page ? ('Page: ' + page.title + (page.status !== 'active' ? ' (hidden)' : '')) : (it.url || '');
    return $('<li class="list-group-item">' +
      '<i class="bi bi-grip-vertical drag" aria-hidden="true"></i>' +
      '<div class="item-label"><input class="form-control form-control-sm label-input" maxlength="60" aria-label="Link label"><span class="item-target"></span></div>' +
      '<div class="form-check form-check-inline m-0" title="Open in a new tab"><input class="form-check-input new-tab" type="checkbox" aria-label="Open in a new tab"><i class="bi bi-box-arrow-up-right small text-muted"></i></div>' +
      '<button class="btn btn-sm btn-light text-danger" type="button" data-remove aria-label="Remove"><i class="bi bi-x-lg"></i></button></li>')
      .data('item', { page_id: it.page_id ? Number(it.page_id) : null, url: it.page_id ? '' : (it.url || '') })
      .find('.label-input').val(it.label || (page ? page.title : '')).end()
      .find('.item-target').text(target).end()
      .find('.new-tab').prop('checked', !!Number(it.new_tab)).end();
  }

  function render($card, items, automatic) {
    const $list = $card.find('.nav-items').empty();
    items.forEach(it => $list.append(itemHtml(it)));
    if (!items.length) $list.append('<li class="list-group-item text-muted small empty">No links yet. Add pages or custom links below.</li>');
    $card.find('.mode-badge').text(automatic ? 'Automatic' : 'Custom');
    $card.data('automatic', automatic);
  }

  function collect($card) {
    return $card.find('.nav-items li').not('.empty').map(function () {
      const d = $(this).data('item');
      return { page_id: d.page_id, url: d.url, label: $(this).find('.label-input').val().trim(), new_tab: $(this).find('.new-tab').is(':checked') ? 1 : 0 };
    }).get();
  }

  function automaticItems(loc) {
    const flag = loc === 'header' ? 'show_in_menu' : 'show_in_footer';
    return pages.filter(p => p.status === 'active' && p[flag]).map(p => ({ page_id: p.id, label: p.title, new_tab: 0 }));
  }

  function load() {
    $.getJSON(WB.url('api/navigation')).done(function (res) {
      pages = res.pages || [];
      $('.nav-editor').each(function () {
        const $c = $(this), loc = $c.data('location');
        const items = res[loc] || [];
        const $sel = $c.find('.page-select').empty();
        pages.forEach(p => $sel.append($('<option>').val(p.id).text(p.title + (p.status !== 'active' ? ' (hidden)' : ''))));
        if (res.payments) $sel.append($('<option>').val('pay').text('Pay online (payment page)'));
        render($c, items.length ? items : automaticItems(loc), !items.length);
        if (window.Sortable && !$c.data('sortable')) {
          $c.data('sortable', Sortable.create($c.find('.nav-items')[0], { handle: '.drag', animation: 150, filter: '.empty' }));
        }
      });
    });
  }

  $(document).on('click', '.nav-editor [data-add-page]', function () {
    const $c = $(this).closest('.nav-editor');
    if ($c.find('.page-select').val() === 'pay') {
      $c.find('.nav-items .empty').remove();
      $c.find('.nav-items').append(itemHtml({ url: 'page:pay', label: 'Pay online' }));
      return;
    }
    const id = Number($c.find('.page-select').val());
    const p = pages.find(x => x.id === id);
    if (!p) return;
    $c.find('.nav-items .empty').remove();
    $c.find('.nav-items').append(itemHtml({ page_id: p.id, label: p.title }));
  });
  $(document).on('click', '.nav-editor [data-add-link]', function () {
    const $c = $(this).closest('.nav-editor');
    const label = $c.find('.link-label').val().trim(), url = $c.find('.link-url').val().trim();
    if (!label || !url) { WB.toast('Enter a label and an address for the link.', 'warning'); return; }
    $c.find('.nav-items .empty').remove();
    $c.find('.nav-items').append(itemHtml({ url: url, label: label, new_tab: /^https?:/i.test(url) ? 1 : 0 }));
    $c.find('.link-label, .link-url').val('');
  });
  $(document).on('click', '.nav-items [data-remove]', function () { $(this).closest('li').remove(); });

  function save($c, items, $btn) {
    WB.busy($btn, true);
    WB.post(WB.url('api/navigation'), { location: $c.data('location'), items: JSON.stringify(items) })
      .done(res => { WB.toast(res.message); load(); })
      .fail(xhr => { if (xhr.status === 422 && xhr.responseJSON) WB.toast(xhr.responseJSON.message, 'danger'); })
      .always(() => WB.busy($btn, false));
  }
  $(document).on('click', '.nav-editor [data-save]', function () {
    const $c = $(this).closest('.nav-editor');
    const items = collect($c);
    if (!items.length) { WB.toast('Add at least one link, or use the automatic menu.', 'warning'); return; }
    save($c, items, $(this));
  });
  $(document).on('click', '.nav-editor [data-automatic]', async function () {
    const $c = $(this).closest('.nav-editor');
    if (!await WB.confirm({ title: 'Use the automatic menu?', message: 'Your custom links for this menu will be removed and the menu will be built from your pages.', confirmText: 'Use automatic', danger: false })) return;
    save($c, [], $(this));
  });

  $(load);
})(jQuery);
