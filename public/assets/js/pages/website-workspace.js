/* Website workspace: pages, sections (dynamic editor), media library, theme, site details, preview. */
$(function () {
  'use strict';
  const cfg = $('#workspaceConfig').data('config');
  const base = 'websites/' + cfg.id;
  const U = p => WB.url(base + (p ? '/' + p : ''));
  const state = { pages: [], pageId: null, section: null };

  /* ------------------------------------------------------------------ helpers */
  WB.refUrl = function (ref) {
    if (!ref) return '';
    if (ref.indexOf('uploads/') === 0) return U('media/' + ref.slice(8));
    if (ref.indexOf('theme:') === 0) return WB.url('preview-assets/template/' + cfg.templateId + '/' + ref.slice(6));
    return '';
  };
  const previewUrl = (slug, hash) => U('preview') + (slug ? '?page=' + encodeURIComponent(slug) : '') + (hash ? '#' + hash : '');
  const errorList = function (errors) {
    return Object.keys(errors || {}).map(k => errors[k]);
  };

  /* ================================================================== PAGES */
  function loadPages(selectId) {
    return $.getJSON(U('pages')).done(function (res) {
      state.pages = res.pages;
      renderPages();
      const keep = selectId || state.pageId;
      const target = res.pages.find(p => p.id == keep) || res.pages.find(p => Number(p.is_home)) || res.pages[0];
      if (target) selectPage(target.id);
      refreshPageLinks();
    });
  }

  function renderPages() {
    const $ul = $('#pageList').empty();
    state.pages.forEach(function (p) {
      const badges = (Number(p.is_home) ? '<span class="badge text-bg-primary">Home</span> ' : '') +
        (p.status !== 'active' ? '<span class="badge text-bg-secondary">Inactive</span> ' : '') +
        (!Number(p.show_in_menu) ? '<span class="badge text-bg-light border" title="Not in main menu"><i class="bi bi-eye-slash"></i> menu</span>' : '');
      const actions = !cfg.editable ? '' :
        '<div class="dropdown ms-1"><button class="btn btn-sm btn-link text-muted px-1" data-bs-toggle="dropdown" aria-label="Page actions"><i class="bi bi-three-dots-vertical"></i></button><ul class="dropdown-menu dropdown-menu-end">' +
        '<li><a class="dropdown-item" href="#" data-page-edit="' + p.id + '"><i class="bi bi-pencil me-2"></i>Edit page &amp; SEO</a></li>' +
        (Number(p.is_home) ? '' : '<li><a class="dropdown-item" href="#" data-page-home="' + p.id + '"><i class="bi bi-house me-2"></i>Set as homepage</a></li>') +
        '<li><a class="dropdown-item" href="#" data-page-toggle="' + p.id + '"><i class="bi bi-toggle-' + (p.status === 'active' ? 'off' : 'on') + ' me-2"></i>' + (p.status === 'active' ? 'Deactivate' : 'Activate') + '</a></li>' +
        '<li><a class="dropdown-item" href="' + previewUrl(Number(p.is_home) ? '' : p.slug) + '" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-2"></i>Open preview</a></li>' +
        (Number(p.is_home) ? '' : '<li><hr class="dropdown-divider"></li><li><a class="dropdown-item text-danger" href="#" data-page-delete="' + p.id + '"><i class="bi bi-trash me-2"></i>Delete page</a></li>') +
        '</ul></div>';
      $ul.append('<li class="list-group-item page-item d-flex align-items-center gap-2' + (p.id == state.pageId ? ' active-page' : '') + '" data-id="' + p.id + '">' +
        (cfg.editable ? '<i class="bi bi-grip-vertical drag-handle"></i>' : '') +
        '<a href="#" class="flex-grow-1 text-reset text-decoration-none" data-page-select="' + p.id + '"><span class="fw-semibold d-block">' + WB.esc(p.title) + '</span>' +
        '<small class="text-muted">/' + WB.esc(p.slug) + ' · ' + Number(p.enabled_sections) + '/' + Number(p.sections) + ' sections</small><div class="mt-1">' + badges + '</div></a>' + actions + '</li>');
    });
    if (cfg.editable && !$ul.data('sortable')) {
      $ul.data('sortable', Sortable.create($ul[0], {
        handle: '.drag-handle', animation: 150,
        onEnd: function () {
          const ids = $ul.children('[data-id]').map(function () { return $(this).data('id'); }).get();
          WB.post(U('pages/reorder'), { ids: ids }).done(r => { WB.toast(r.message); loadPages(); });
        }
      }));
    }
    // preview page dropdown
    const $sel = $('#previewPage').empty();
    state.pages.filter(p => p.status === 'active').forEach(p => $sel.append('<option value="' + WB.esc(Number(p.is_home) ? '' : p.slug) + '">' + WB.esc(p.title) + '</option>'));
  }

  function refreshPageLinks() {
    const $dl = $('#pageLinkList').empty();
    state.pages.forEach(p => $dl.append('<option value="page:' + WB.esc(Number(p.is_home) ? '' : p.slug) + '">' + WB.esc(p.title) + '</option>'));
  }

  function selectPage(id) {
    state.pageId = id;
    $('#pageList .page-item').removeClass('active-page').filter('[data-id="' + id + '"]').addClass('active-page');
    loadSections();
  }

  $('#pageList').on('click', '[data-page-select]', function (e) { e.preventDefault(); selectPage($(this).data('page-select')); });

  $('#btnAddPage').on('click', function () {
    WB.openForm('#pageModal', { page_type: cfg.pageTypes.indexOf('generic') >= 0 ? 'generic' : cfg.pageTypes[0], show_in_menu: 1, show_in_footer: 0 }, { action: U('pages'), title: 'Add page' });
    $('#pageModal [name=page_type]').prop('disabled', false);
    $('#pageModal .slug-help').text('');
  });
  $('#pageList').on('click', '[data-page-edit]', function (e) {
    e.preventDefault();
    const p = state.pages.find(x => x.id == $(this).data('page-edit'));
    WB.openForm('#pageModal', p, { action: U('pages/' + p.id), title: 'Edit page' });
    $('#pageModal .slug-help').text(Number(p.is_home) ? 'The homepage opens at the website address; a URL is optional.' : '');
    updateCounters();
  });
  $('#pageModal form').on('wb:saved', function (e, res) { loadPages(res.id); });
  $('#pageModal').on('input', '[name=seo_title],[name=seo_description]', updateCounters);
  function updateCounters() {
    $('#pageModal [data-count-for]').each(function () {
      const n = ($('#pageModal [name=' + $(this).data('count-for') + ']').val() || '').length;
      $(this).text(n + ' characters');
    });
  }

  $('#pageList').on('click', '[data-page-home]', function (e) {
    e.preventDefault();
    WB.post(U('pages/' + $(this).data('page-home') + '/home')).done(r => { WB.toast(r.message, r.ok ? 'success' : 'danger'); loadPages(); })
      .fail(x => x.responseJSON && WB.toast(x.responseJSON.message, 'danger'));
  });
  $('#pageList').on('click', '[data-page-toggle]', function (e) {
    e.preventDefault();
    WB.post(U('pages/' + $(this).data('page-toggle') + '/toggle')).done(r => { WB.toast(r.message); loadPages(); })
      .fail(x => x.responseJSON && WB.toast(x.responseJSON.message, 'danger'));
  });
  $('#pageList').on('click', '[data-page-delete]', async function (e) {
    e.preventDefault();
    const p = state.pages.find(x => x.id == $(this).data('page-delete'));
    if (!await WB.confirm({ title: 'Delete page', message: 'Delete “' + p.title + '” and all of its sections?', confirmText: 'Delete' })) return;
    WB.post(U('pages/' + p.id + '/delete')).done(r => { WB.toast(r.message); state.pageId = null; loadPages(); })
      .fail(x => x.responseJSON && WB.toast(x.responseJSON.message, 'danger'));
  });

  /* ================================================================== SECTIONS */
  function currentPage() { return state.pages.find(p => p.id == state.pageId); }

  function loadSections() {
    const page = currentPage();
    if (!page) return;
    $('#sectionsTitle').html('Sections of <strong>' + WB.esc(page.title) + '</strong>');
    const $ul = $('#sectionList').html('<li class="list-group-item text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></li>');
    $.getJSON(U('pages/' + page.id + '/sections')).done(function (res) {
      $ul.empty();
      if (!res.sections.length) $ul.html('<li class="list-group-item text-muted small text-center py-4">This page has no sections yet. Use “Add section”.</li>');
      res.sections.forEach(function (s) {
        const enabled = Number(s.is_enabled) === 1;
        $ul.append('<li class="list-group-item section-item d-flex align-items-center gap-3' + (enabled ? '' : ' is-disabled') + '" data-id="' + s.id + '">' +
          (cfg.editable ? '<i class="bi bi-grip-vertical drag-handle"></i>' : '') +
          '<span class="section-type-icon"><i class="bi ' + WB.esc(s.icon) + '"></i></span>' +
          '<div class="flex-grow-1 min-w-0"><div class="fw-semibold">' + WB.esc(s.label || s.type_label) + (s.label ? ' <small class="text-muted fw-normal">(' + WB.esc(s.type_label) + ')</small>' : '') + (!s.supported ? ' <span class="badge text-bg-warning">Not supported by template</span>' : '') + '</div>' +
          '<small class="text-muted text-truncate d-block">' + WB.esc(s.summary || '') + '</small></div>' +
          (cfg.editable ? '<div class="form-check form-switch m-0" title="' + (enabled ? 'Visible' : 'Hidden') + '"><input class="form-check-input" type="checkbox" data-sec-toggle="' + s.id + '"' + (enabled ? ' checked' : '') + ' aria-label="Show section"></div>' : '') +
          '<div class="btn-group btn-group-sm"><button class="btn btn-light" data-sec-edit="' + s.id + '" title="' + (cfg.editable ? 'Edit' : 'View') + '"><i class="bi bi-' + (cfg.editable ? 'pencil' : 'eye') + '"></i></button>' +
          (cfg.editable ? '<button class="btn btn-light" data-sec-dup="' + s.id + '" title="Duplicate"><i class="bi bi-copy"></i></button><button class="btn btn-light text-danger" data-sec-del="' + s.id + '" title="Delete"><i class="bi bi-trash"></i></button>' : '') +
          '</div></li>');
      });
      if (cfg.editable && !$ul.data('sortable')) {
        $ul.data('sortable', Sortable.create($ul[0], {
          handle: '.drag-handle', animation: 150,
          onEnd: function () {
            const ids = $ul.children('[data-id]').map(function () { return $(this).data('id'); }).get();
            WB.post(U('pages/' + state.pageId + '/sections/reorder'), { ids: ids }).done(r => WB.toast(r.message));
          }
        }));
      }
    });
  }

  $('[data-add-section]').on('click', function (e) {
    e.preventDefault();
    const page = currentPage();
    if (!page) return;
    WB.post(U('pages/' + page.id + '/sections'), { section_type: $(this).data('add-section') }).done(function (r) {
      WB.toast(r.message);
      loadSections();
      loadPages();
      openSection(r.id);
    }).fail(x => x.responseJSON && WB.toast(x.responseJSON.message, 'danger'));
  });
  $('#sectionList').on('change', '[data-sec-toggle]', function () {
    WB.post(U('sections/' + $(this).data('sec-toggle') + '/toggle')).done(r => { WB.toast(r.message); loadSections(); loadPages(); });
  });
  $('#sectionList').on('click', '[data-sec-dup]', function () {
    WB.post(U('sections/' + $(this).data('sec-dup') + '/duplicate')).done(r => { WB.toast(r.message); loadSections(); loadPages(); });
  });
  $('#sectionList').on('click', '[data-sec-del]', async function () {
    if (!await WB.confirm({ title: 'Delete section', message: 'Delete this section and its content? To keep it for later, switch it off instead.', confirmText: 'Delete' })) return;
    WB.post(U('sections/' + $(this).data('sec-del') + '/delete')).done(r => { WB.toast(r.message); loadSections(); loadPages(); });
  });
  $('#sectionList').on('click', '[data-sec-edit]', function () { openSection($(this).data('sec-edit')); });
  $('#btnPreviewPage').on('click', function () {
    const p = currentPage();
    if (!p) return;
    $('#previewPage').val(Number(p.is_home) ? '' : p.slug);
    bootstrap.Tab.getOrCreateInstance($('#previewTabBtn')[0]).show();
  });

  /* ------------------------------------------------------------------ section editor (dynamic fields) */
  const $secModal = $('#sectionModal');
  const secModal = new bootstrap.Modal($secModal[0], { focus: false }); // focus:false lets TinyMCE dialogs receive focus

  function openSection(id) {
    $.getJSON(U('sections/' + id)).done(function (res) {
      if (!res.schema) { WB.toast('This section type is not supported by the template.', 'warning'); return; }
      state.section = res.section;
      $secModal.find('.section-title').text(res.schema.label);
      $secModal.find('.section-icon').attr('class', 'bi section-icon me-2 ' + (res.schema.icon || 'bi-square'));
      $secModal.find('.form-alert').addClass('d-none');
      $('#secLabel').val(res.section.label || '').prop('disabled', !cfg.editable);
      const $fields = $('#secFields').empty();
      if (res.schema.description) $fields.append('<p class="small text-muted">' + WB.esc(res.schema.description) + '</p>');
      res.schema.fields.forEach(f => $fields.append(renderField(f, res.section.data[f.key], f.key)));
      fillStyle(res.section.style || {});
      if (!cfg.editable) $secModal.find('.section-editor-form :input').prop('disabled', true);
      bootstrap.Tab.getOrCreateInstance($secModal.find('[data-bs-target="#secContent"]')[0]).show();
      secModal.show();
      refreshSectionPreview();
    });
  }

  function sectionAnchor() {
    const s = state.section;
    return (s.style && s.style.anchor) || ('section-' + s.id);
  }
  function refreshSectionPreview() {
    const page = currentPage();
    const slug = page && !Number(page.is_home) ? page.slug : '';
    $('#sectionPreview').attr('src', U('preview') + '?' + (slug ? 'page=' + encodeURIComponent(slug) + '&' : '') + 't=' + Date.now() + '#' + sectionAnchor());
  }
  $secModal.on('click', '[data-sec-device]', function () {
    $(this).addClass('active').siblings().removeClass('active');
    $('#sectionPreview').css('width', $(this).data('sec-device'));
  });

  $secModal.on('shown.bs.modal', initRichtext);
  $secModal.on('hidden.bs.modal', function () {
    if (window.tinymce) tinymce.remove('#secFields textarea.wb-richtext');
    loadSections();
    loadPages();
  });

  function fieldWrap(f, path, inner) {
    const help = f.help ? '<div class="form-text">' + WB.esc(f.help) + '</div>' : '';
    const req = f.required ? ' <span class="text-danger">*</span>' : '';
    return $('<div class="mb-3 wb-field" data-path="' + WB.esc(path) + '"><label class="form-label">' + WB.esc(f.label) + req + '</label></div>').append(inner).append(help);
  }

  function renderField(f, value, path) {
    const v = value === undefined || value === null ? '' : value;
    let $in;
    switch (f.type) {
      case 'text':
        $in = $('<input class="form-control" data-key>').attr('maxlength', f.max || 300).val(v); break;
      case 'textarea':
        $in = $('<textarea class="form-control" rows="3" data-key></textarea>').attr('maxlength', f.max || 2000).val(v); break;
      case 'richtext':
        $in = $('<textarea class="form-control wb-richtext" rows="6" data-key></textarea>').attr('id', 'rt_' + Math.random().toString(36).slice(2)).val(v); break;
      case 'number':
        $in = $('<input type="number" class="form-control" data-key>').val(v); break;
      case 'color':
        $in = $('<div class="input-group"><input type="color" class="form-control form-control-color"><input class="form-control" data-key maxlength="7" placeholder="#000000"></div>');
        $in.find('[data-key]').val(v); $in.find('[type=color]').val(/^#[0-9a-f]{6}$/i.test(v) ? v : '#000000');
        $in.on('input', '[type=color]', function () { $in.find('[data-key]').val(this.value); });
        break;
      case 'select':
        $in = $('<select class="form-select" data-key></select>');
        $.each(f.options || {}, (k, label) => $in.append($('<option>').val(k).text(label)));
        $in.val(String(v === '' ? (f.default || '') : v)); break;
      case 'toggle':
        $in = $('<div class="form-check form-switch"><input class="form-check-input" type="checkbox" data-key></div>');
        $in.find('input').prop('checked', v === true || v === 1 || v === '1');
        return $('<div class="mb-3 wb-field" data-path="' + WB.esc(path) + '"></div>').append($in.append('<label class="form-check-label">' + WB.esc(f.label) + '</label>'));
      case 'icon':
        $in = $('<div class="input-group"><span class="input-group-text icon-preview"><i class="bi ' + WB.esc(v) + '"></i></span><input class="form-control" data-key list="iconList" placeholder="bi-briefcase"></div>');
        $in.find('[data-key]').val(v).on('input', function () { $in.find('.icon-preview i').attr('class', 'bi ' + this.value); });
        break;
      case 'link':
        $in = $('<div class="row g-2"><div class="col-5"><input class="form-control" data-link="text" placeholder="Button text" maxlength="80"></div><div class="col-7"><input class="form-control" data-link="url" list="pageLinkList" placeholder="page:contact or https://…" maxlength="500"></div></div>');
        $in.find('[data-link=text]').val(v.text || ''); $in.find('[data-link=url]').val(v.url || '');
        $in.attr('data-key', 'link');
        break;
      case 'image':
        $in = $('<div class="wb-image-field"></div>');
        imageField($in, v);
        $in.attr('data-key', 'image');
        break;
      case 'repeater':
        return renderRepeater(f, Array.isArray(v) ? v : [], path);
    }
    return fieldWrap(f, path, $in);
  }

  function renderRepeater(f, items, path) {
    const $wrap = $('<div class="mb-3 wb-field wb-repeater" data-path="' + WB.esc(path) + '"></div>');
    $wrap.append('<div class="d-flex align-items-center mb-2"><label class="form-label m-0">' + WB.esc(f.label) + (f.required || f.min_items ? ' <span class="text-danger">*</span>' : '') + '</label><small class="text-muted ms-2 count"></small></div>');
    const $list = $('<div class="repeater-items"></div>').appendTo($wrap);
    const $add = $('<button type="button" class="btn btn-sm btn-outline-primary"><i class="bi bi-plus-lg"></i> Add ' + WB.esc((f.label || 'item').replace(/s$/, '').toLowerCase()) + '</button>').appendTo($wrap);
    const max = f.max_items || 24;

    function itemTitle(data, i) {
      const k = f.item_label;
      const t = k && data[k] ? (typeof data[k] === 'object' ? data[k].text : data[k]) : '';
      return (i + 1) + '. ' + (t ? String(t).slice(0, 60) : 'Item');
    }
    function addItem(data, open) {
      const i = $list.children().length;
      const $item = $('<div class="repeater-item card mb-2"><div class="card-header py-2 d-flex align-items-center gap-2"><a href="#" class="item-toggle text-reset text-decoration-none flex-grow-1 small fw-semibold"></a>' +
        '<div class="btn-group btn-group-sm"><button type="button" class="btn btn-light" data-move="-1" title="Move up"><i class="bi bi-arrow-up"></i></button><button type="button" class="btn btn-light" data-move="1" title="Move down"><i class="bi bi-arrow-down"></i></button><button type="button" class="btn btn-light text-danger" data-remove title="Remove"><i class="bi bi-x-lg"></i></button></div></div>' +
        '<div class="card-body item-body"' + (open ? '' : ' style="display:none"') + '></div></div>');
      $item.find('.item-toggle').text(itemTitle(data, i));
      (f.fields || []).forEach(sf => $item.find('.item-body').append(renderField(sf, data[sf.key], path + '.' + i + '.' + sf.key)));
      $list.append($item);
      renumber();
    }
    function renumber() {
      $list.children().each(function (i) {
        const $it = $(this);
        $it.find('.wb-field').each(function () {
          const p = $(this).attr('data-path').replace(new RegExp('^' + path.replace(/\./g, '\\.') + '\\.\\d+'), path + '.' + i);
          $(this).attr('data-path', p);
        });
        const label = f.item_label ? ($it.find('> .card-body > .wb-field[data-path$=".' + f.item_label + '"] [data-key], > .card-body > .wb-field[data-path$=".' + f.item_label + '"] [data-link=text]').first().val() || '') : '';
        $it.find('.item-toggle').text((i + 1) + '. ' + (label ? label.slice(0, 60) : 'Item'));
      });
      $wrap.find('> .d-flex .count').text($list.children().length + ' / ' + max);
      $add.prop('disabled', $list.children().length >= max);
    }
    items.forEach(d => addItem(d || {}, false));
    if (!items.length && f.min_items) addItem({}, true);
    $add.on('click', () => { addItem({}, true); initRichtext(); });
    $list.on('click', '> .repeater-item > .card-header .item-toggle', function (e) { e.preventDefault(); $(this).closest('.repeater-item').find('> .item-body').slideToggle(150); });
    $list.on('click', '> .repeater-item > .card-header [data-remove]', function () { $(this).closest('.repeater-item').remove(); renumber(); });
    $list.on('click', '> .repeater-item > .card-header [data-move]', function () {
      const $it = $(this).closest('.repeater-item');
      if ($(this).data('move') < 0) $it.prev().before($it); else $it.next().after($it);
      renumber();
    });
    $list.on('input', '[data-key],[data-link=text]', function () { renumber(); });
    return $wrap;
  }

  /** Read field values back into an object following the schema. */
  function collect($container, fields) {
    const out = {};
    fields.forEach(function (f) {
      const $f = $container.children('.wb-field').filter(function () { return $(this).attr('data-path').split('.').pop() === f.key; }).first();
      if (f.type === 'repeater') {
        out[f.key] = $f.find('> .repeater-items > .repeater-item').map(function () { return collect($(this).children('.item-body'), f.fields || []); }).get();
      } else if (f.type === 'link') {
        out[f.key] = { text: $f.find('[data-link=text]').first().val(), url: $f.find('[data-link=url]').first().val() };
      } else if (f.type === 'toggle') {
        out[f.key] = $f.find('[data-key]').first().is(':checked');
      } else if (f.type === 'image') {
        out[f.key] = $f.find('.wb-image-field').first().data('value') || '';
      } else if (f.type === 'richtext') {
        const id = $f.find('textarea').attr('id');
        out[f.key] = (window.tinymce && tinymce.get(id)) ? tinymce.get(id).getContent() : $f.find('textarea').val();
      } else {
        out[f.key] = $f.find('[data-key]').first().val();
      }
    });
    return out;
  }

  function initRichtext() {
    if (!window.tinymce) return;
    $('#secFields textarea.wb-richtext').each(function () {
      if (tinymce.get(this.id)) return;
      tinymce.init({
        target: this, base_url: WB.url('assets/vendor/tinymce'), suffix: '.min', menubar: false, branding: false, promotion: false,
        plugins: 'lists link autolink code table', height: 260, convert_urls: false, readonly: !cfg.editable,
        toolbar: 'blocks | bold italic underline | bullist numlist | link table | removeformat code',
        block_formats: 'Paragraph=p; Heading 2=h2; Heading 3=h3; Heading 4=h4',
        content_style: 'body{font-family:system-ui,sans-serif;font-size:15px}'
      });
    });
  }

  function fillStyle(s) {
    const $st = $('#secStyle');
    $st.find('[data-style=bg_color]').val(s.bg_color || '');
    $st.find('[data-style-color]').val(s.bg_color || '#ffffff');
    $st.find('[data-style=padding]').val(s.padding || 'md');
    $st.find('[data-style=overlay]').val(s.overlay || 0);
    $st.find('[data-overlay-value]').text(s.overlay || 0);
    $st.find('[data-style=tone]').val(s.tone || 'auto');
    $st.find('[data-style=anchor]').val(s.anchor || '');
    imageField($st.find('[data-style-image]'), s.bg_image || '');
  }
  $('#secStyle').on('input', '[data-style-color]', function () { $('#secStyle [data-style=bg_color]').val(this.value); });
  $('#secStyle').on('input', '[data-style=overlay]', function () { $('#secStyle [data-overlay-value]').text(this.value); });
  function collectStyle() {
    const $st = $('#secStyle');
    return {
      bg_color: $st.find('[data-style=bg_color]').val(), padding: $st.find('[data-style=padding]').val(),
      overlay: $st.find('[data-style=overlay]').val(), tone: $st.find('[data-style=tone]').val(),
      anchor: $st.find('[data-style=anchor]').val(), bg_image: $st.find('[data-style-image]').data('value') || ''
    };
  }

  function saveSection(close) {
    const schema = cfg.schemas[state.section.section_type];
    const $btn = close ? $('#secSaveClose') : $('#secSave');
    $secModal.find('.is-invalid').removeClass('is-invalid');
    $secModal.find('.wb-err').remove();
    $secModal.find('.form-alert').addClass('d-none');
    WB.busy($btn, true);
    WB.post(U('sections/' + state.section.id), {
      data: JSON.stringify(collect($('#secFields'), schema.fields)),
      style: JSON.stringify(collectStyle()),
      label: $('#secLabel').val()
    }).done(function (r) {
      WB.toast(r.message);
      state.section.style = collectStyle();
      if (close) secModal.hide(); else refreshSectionPreview();
    }).fail(function (x) {
      const r = x.responseJSON || {};
      if (r.errors) {
        $.each(r.errors, function (path, msg) {
          const $f = $secModal.find('.wb-field[data-path="' + path + '"]');
          if ($f.length) {
            $f.find('.form-control, .form-select').first().addClass('is-invalid');
            $f.append($('<div class="invalid-feedback d-block wb-err"></div>').text(msg));
            $f.parents('.item-body').show();
          }
        });
        $secModal.find('.form-alert').removeClass('d-none').html(WB.esc(r.message) + '<ul class="mb-0 mt-1">' + errorList(r.errors).slice(0, 6).map(e => '<li>' + WB.esc(e) + '</li>').join('') + '</ul>');
      }
      if (r.message) WB.toast(r.message, 'danger');
    }).always(() => WB.busy($btn, false));
  }
  $('#secSave').on('click', () => saveSection(false));
  $('#secSaveClose').on('click', () => saveSection(true));

  /* ================================================================== IMAGE FIELD + MEDIA LIBRARY */
  function imageField($el, value) {
    $el.data('value', value || '');
    const render = function () {
      const v = $el.data('value');
      const url = WB.refUrl(v);
      $el.html('<div class="d-flex align-items-center gap-2"><div class="wb-thumb">' + (url ? '<img src="' + WB.esc(url) + '" alt="">' : '<i class="bi bi-image"></i>') + '</div>' +
        '<div class="small text-muted flex-grow-1 text-truncate">' + (v ? (v.indexOf('theme:') === 0 ? 'Template demo image' : WB.esc(v.split('/').pop())) : 'No image (template default is used where available)') + '</div>' +
        (cfg.editable ? '<button type="button" class="btn btn-sm btn-outline-primary" data-pick>Choose</button>' + (v ? '<button type="button" class="btn btn-sm btn-light" data-clear title="Remove"><i class="bi bi-x-lg"></i></button>' : '') : '') + '</div>');
    };
    render();
    $el.off('.img').on('click.img', '[data-pick]', function () {
      MediaPicker.open(function (item) { $el.data('value', item.path); render(); });
    }).on('click.img', '[data-clear]', function () { $el.data('value', ''); render(); });
    $el.data('render', render);
  }

  /** Media browser used by the Media tab (manage) and the picker modal (pick). */
  function MediaBrowser($root, opts) {
    const st = { folder: '', q: '', page: 1, selected: null };
    $root.html(
      '<div class="d-flex flex-wrap gap-2 mb-3 align-items-center">' +
      '<select class="form-select form-select-sm w-auto" data-folder><option value="">All folders</option>' + Object.keys(cfg.mediaFolders).map(k => '<option value="' + k + '">' + WB.esc(cfg.mediaFolders[k]) + '</option>').join('') + '</select>' +
      '<input type="search" class="form-control form-control-sm w-auto" placeholder="Search name or alt text" data-q>' +
      (cfg.editable ? '<label class="btn btn-primary btn-sm ms-auto mb-0"><i class="bi bi-upload me-1"></i>Upload images<input type="file" multiple accept="image/jpeg,image/png,image/webp,image/gif,image/svg+xml" hidden data-upload></label>' : '') +
      '</div><div class="small text-muted mb-2" data-hint>JPG, PNG, WEBP, GIF or SVG · up to 8 MB each · SVGs are cleaned of scripts before saving. Uploads go to the selected folder.</div>' +
      '<div class="row g-3"><div class="' + (opts.mode === 'manage' ? 'col-lg-8' : 'col-12') + '"><div class="media-grid" data-grid></div><nav class="mt-3"><ul class="pagination pagination-sm mb-0" data-pager></ul></nav></div>' +
      (opts.mode === 'manage' ? '<div class="col-lg-4"><div class="card"><div class="card-body" data-detail><p class="text-muted small mb-0">Select an image to edit its alt text, replace it or delete it.</p></div></div></div>' : '') + '</div>');

    function load() {
      const $g = $root.find('[data-grid]').html('<div class="text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></div>');
      $.getJSON(U('media'), { folder: st.folder, q: st.q, page: st.page, per_page: 24 }).done(function (res) {
        $g.empty();
        if (!res.rows.length) $g.html('<div class="empty-state"><i class="bi bi-images"></i>No images yet.' + (cfg.editable ? ' Upload some to get started.' : '') + '</div>');
        res.rows.forEach(function (m) {
          const $t = $('<button type="button" class="media-tile" title="' + WB.esc(m.original_name || '') + '"><img src="' + WB.esc(m.url) + '" alt="' + WB.esc(m.alt_text || '') + '" loading="lazy"><span>' + WB.esc(m.original_name || '') + '</span></button>');
          $t.on('click', function () {
            if (opts.mode === 'pick') { opts.onPick(m); return; }
            $g.find('.media-tile').removeClass('selected'); $t.addClass('selected');
            showDetail(m);
          });
          $g.append($t);
        });
        const $p = $root.find('[data-pager]').empty();
        if (res.pages > 1) for (let i = 1; i <= res.pages; i++) $p.append('<li class="page-item' + (i === res.page ? ' active' : '') + '"><a class="page-link" href="#" data-p="' + i + '">' + i + '</a></li>');
      });
    }
    function showDetail(m) {
      const $d = $root.find('[data-detail]');
      $d.html('<img src="' + WB.esc(m.url) + '" class="img-fluid rounded border mb-2" alt="">' +
        '<div class="small text-muted mb-2">' + WB.esc(m.original_name || '') + '<br>' + (m.width ? m.width + '×' + m.height + ' px · ' : '') + Math.round(m.size / 1024) + ' KB</div>' +
        '<label class="form-label small">Alt text (describes the image for screen readers and SEO)</label><input class="form-control form-control-sm mb-2" data-alt maxlength="255">' +
        '<label class="form-label small">Folder</label><select class="form-select form-select-sm mb-3" data-mfolder>' + Object.keys(cfg.mediaFolders).map(k => '<option value="' + k + '">' + WB.esc(cfg.mediaFolders[k]) + '</option>').join('') + '</select>' +
        (cfg.editable ? '<div class="d-flex flex-wrap gap-2"><button class="btn btn-sm btn-primary" data-save>Save</button><label class="btn btn-sm btn-outline-primary mb-0">Replace<input type="file" hidden accept="image/*" data-replace></label><button class="btn btn-sm btn-outline-danger ms-auto" data-del>Delete</button></div>' : ''));
      $d.find('[data-alt]').val(m.alt_text || '');
      $d.find('[data-mfolder]').val(m.folder);
      $d.find('[data-save]').on('click', () => WB.post(U('media/' + m.id), { alt_text: $d.find('[data-alt]').val(), folder: $d.find('[data-mfolder]').val() }).done(r => { WB.toast(r.message); load(); }));
      $d.find('[data-replace]').on('change', function () {
        const fd = new FormData(); fd.append('file', this.files[0]);
        $.ajax({ url: U('media/' + m.id + '/replace'), method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
          .done(r => { WB.toast(r.message); load(); $d.find('img').attr('src', r.url); })
          .fail(x => x.responseJSON && WB.toast(x.responseJSON.message, 'danger'));
      });
      $d.find('[data-del]').on('click', async function () {
        if (!await WB.confirm({ title: 'Delete image', message: 'Delete this image permanently?', confirmText: 'Delete' })) return;
        const del = force => WB.post(U('media/' + m.id + '/delete'), force ? { force: 1 } : {});
        del(false).done(r => { WB.toast(r.message); $d.html('<p class="text-muted small mb-0">Image deleted.</p>'); load(); })
          .fail(async function (x) {
            const r = x.responseJSON || {};
            if (r.in_use && await WB.confirm({ title: 'Image in use', message: r.message, confirmText: 'Delete anyway' })) {
              del(true).done(r2 => { WB.toast(r2.message); $d.html('<p class="text-muted small mb-0">Image deleted.</p>'); load(); });
            }
          });
      });
    }
    $root.on('change', '[data-folder]', function () { st.folder = this.value; st.page = 1; load(); });
    let t = null;
    $root.on('input', '[data-q]', function () { clearTimeout(t); const v = this.value; t = setTimeout(() => { st.q = v; st.page = 1; load(); }, 300); });
    $root.on('click', '[data-p]', function (e) { e.preventDefault(); st.page = $(this).data('p'); load(); });
    $root.on('change', '[data-upload]', function () {
      if (!this.files.length) return;
      const fd = new FormData();
      Array.from(this.files).forEach(f => fd.append('files[]', f));
      fd.append('folder', st.folder || 'general');
      const $hint = $root.find('[data-hint]').html('<span class="spinner-border spinner-border-sm text-primary me-1"></span> Uploading ' + this.files.length + ' file(s)…');
      const input = this;
      $.ajax({ url: U('media'), method: 'POST', data: fd, processData: false, contentType: false, dataType: 'json' })
        .done(r => { WB.toast(r.message, r.skipped && r.skipped.length ? 'warning' : 'success'); st.page = 1; load(); if (opts.mode === 'pick' && r.items.length === 1) opts.onPick(r.items[0]); })
        .fail(x => x.responseJSON && WB.toast(x.responseJSON.message, 'danger'))
        .always(() => { input.value = ''; $hint.text('Uploaded files appear first.'); });
    });
    this.load = load;
    load();
  }

  const MediaPicker = {
    cb: null, browser: null,
    open: function (cb) {
      this.cb = cb;
      if (!this.browser) this.browser = new MediaBrowser($('#mediaPickerBody'), { mode: 'pick', onPick: item => { bootstrap.Modal.getOrCreateInstance($('#mediaPicker')[0]).hide(); this.cb && this.cb(item); } });
      else this.browser.load();
      bootstrap.Modal.getOrCreateInstance($('#mediaPicker')[0], { focus: false }).show();
    }
  };
  let mediaManager = null;
  $('#mediaTabBtn').on('shown.bs.tab', function () { if (!mediaManager) mediaManager = new MediaBrowser($('#mediaManager'), { mode: 'manage' }); else mediaManager.load(); });

  /* ================================================================== THEME + DETAILS */
  function loadSettings() {
    return $.getJSON(U('settings')).done(function (res) {
      const $t = $('#themeForm');
      ['primary', 'secondary', 'bg', 'text', 'heading_font', 'body_font', 'button_style', 'radius', 'header_style', 'footer_style'].forEach(k => $t.find('[name=' + k + ']').val(res.theme[k]));
      $t.find('[data-color-for]').each(function () { $(this).val($t.find('[name=' + $(this).data('color-for') + ']').val()); });
      imageField($t.find('[data-name=logo]'), res.theme.logo || '');
      imageField($t.find('[data-name=favicon]'), res.theme.favicon || '');

      const $d = $('#detailsForm');
      const s = res.site || {};
      ['name', 'tagline', 'email', 'phone', 'whatsapp', 'address', 'hours', 'footer_about', 'footer_text'].forEach(k => $d.find('[name=' + k + ']').val(s[k] || ''));
      $d.find('[name=header_button_text]').val((s.header_button || {}).text || '');
      $d.find('[name=header_button_url]').val((s.header_button || {}).url || '');
      ['facebook', 'instagram', 'linkedin', 'twitter', 'youtube'].forEach(k => $d.find('[name=social_' + k + ']').val((s.social || {})[k] || ''));
      $d.find('[name=seo_description]').val((res.seo || {}).description || '');
      $d.find('[name=seo_keywords]').val((res.seo || {}).keywords || '');
      if (!cfg.editable) $('#themeForm :input, #detailsForm :input').prop('disabled', true);
    });
  }
  $('#themeForm').on('input', '[data-color-for]', function () { $('#themeForm [name=' + $(this).data('color-for') + ']').val(this.value.toUpperCase()); });
  $('#themeForm').on('input', '[name=primary],[name=secondary],[name=bg],[name=text]', function () {
    if (/^#[0-9a-f]{6}$/i.test(this.value)) $('#themeForm [data-color-for=' + this.name + ']').val(this.value);
  });
  $('#btnThemeDefaults').on('click', function () {
    const d = cfg.themeDefaults;
    Object.keys(d).forEach(k => $('#themeForm [name=' + k + ']').val(d[k]));
    $('#themeForm [data-color-for]').each(function () { $(this).val($('#themeForm [name=' + $(this).data('color-for') + ']').val()); });
    WB.toast('Template defaults restored in the form. Click “Save theme” to apply.', 'info');
  });
  $('#themeForm').on('submit', function (e) {
    e.preventDefault();
    const $f = $(this);
    const $btn = $f.find('[type=submit]');
    const data = $f.serializeArray();
    data.push({ name: 'logo', value: $f.find('[data-name=logo]').data('value') || '' });
    data.push({ name: 'favicon', value: $f.find('[data-name=favicon]').data('value') || '' });
    WB.clearErrors($f);
    WB.busy($btn, true);
    WB.post($f.attr('action'), $.param(data)).done(r => WB.toast(r.message))
      .fail(x => { const r = x.responseJSON || {}; WB.showErrors($f, r.errors, r.message); if (r.message) WB.toast(r.message, 'danger'); })
      .always(() => WB.busy($btn, false));
  });

  /* ================================================================== PREVIEW TAB */
  function loadPreview() {
    const slug = $('#previewPage').val() || '';
    const url = previewUrl(slug);
    $('#previewFrame').attr('src', url + (url.indexOf('?') >= 0 ? '&' : '?') + 't=' + Date.now());
    $('#previewOpen').attr('href', url);
  }
  $('#previewTabBtn').on('shown.bs.tab', loadPreview);
  $('#previewPage').on('change', loadPreview);
  $('#previewReload').on('click', loadPreview);
  $('[data-device]').on('click', function () {
    $(this).addClass('active').siblings().removeClass('active');
    $('#previewFrame').css('width', $(this).data('device'));
  });

  /* ================================================================== start */
  loadPages();
  loadSettings();
});
