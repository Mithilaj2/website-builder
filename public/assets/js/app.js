/* Channel Softech Website Builder — shared admin JS (jQuery + Bootstrap 5).
 * No inline scripts are used anywhere (strict CSP); page scripts live in assets/js/pages/. */
(function ($, window) {
  'use strict';

  const WB = window.WB = {};
  WB.baseUrl = $('meta[name="base-url"]').attr('content') || '';
  WB.csrf = $('meta[name="csrf-token"]').attr('content') || '';
  WB.can = ($('meta[name="permissions"]').attr('content') || '').split(',');

  WB.url = function (path) { return WB.baseUrl.replace(/\/$/, '') + '/' + String(path || '').replace(/^\//, ''); };
  WB.asset = WB.url;

  WB.esc = function (v) {
    return String(v === null || v === undefined ? '' : v)
      .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&#039;');
  };

  WB.date = function (v) {
    if (!v) return '—';
    const d = new Date(String(v).replace(' ', 'T'));
    if (isNaN(d)) return WB.esc(v);
    return d.toLocaleDateString('en-IN', { day: '2-digit', month: 'short', year: 'numeric' });
  };
  WB.datetime = function (v) {
    if (!v) return '—';
    const d = new Date(String(v).replace(' ', 'T'));
    if (isNaN(d)) return WB.esc(v);
    return d.toLocaleString('en-IN', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' });
  };

  const badgeMap = {
    active: 'success', inactive: 'secondary', draft: 'secondary', config_pending: 'warning',
    generating: 'info', generated: 'primary', testing: 'info', ready: 'primary', deployed: 'success',
    archived: 'dark', failed: 'danger', super_admin: 'danger', admin: 'primary', designer: 'info', viewer: 'secondary'
  };
  WB.badge = function (status, label) {
    const text = label || String(status || '').replace(/_/g, ' ').replace(/\b\w/g, c => c.toUpperCase());
    return '<span class="badge text-bg-' + (badgeMap[status] || 'secondary') + '">' + WB.esc(text) + '</span>';
  };

  /* ---------- AJAX defaults ---------- */
  $.ajaxSetup({ headers: { 'X-CSRF-Token': WB.csrf, 'X-Requested-With': 'XMLHttpRequest' } });
  $(document).ajaxError(function (e, xhr) {
    if (xhr.status === 401) {
      const r = xhr.responseJSON && xhr.responseJSON.redirect;
      WB.toast('Your session has ended. Redirecting to login…', 'warning');
      setTimeout(() => { window.location.href = r || WB.url('login'); }, 1200);
    } else if (xhr.status === 403) {
      WB.toast((xhr.responseJSON && xhr.responseJSON.message) || 'You do not have permission to do this.', 'danger');
    } else if (xhr.status >= 500 || xhr.status === 0) {
      WB.toast((xhr.responseJSON && xhr.responseJSON.message) || 'Server error. Please try again.', 'danger');
    }
  });

  /* ---------- Toasts ---------- */
  WB.toast = function (message, type) {
    type = type || 'success';
    const icons = { success: 'check-circle-fill', danger: 'exclamation-octagon-fill', warning: 'exclamation-triangle-fill', info: 'info-circle-fill' };
    const $t = $('<div class="toast align-items-center text-bg-' + type + ' border-0" role="alert" aria-live="assertive" aria-atomic="true">' +
      '<div class="d-flex"><div class="toast-body"><i class="bi bi-' + (icons[type] || icons.info) + ' me-2"></i>' + WB.esc(message) + '</div>' +
      '<button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button></div></div>');
    $('#toastArea').append($t);
    const t = new bootstrap.Toast($t[0], { delay: type === 'danger' ? 6000 : 3500 });
    $t.on('hidden.bs.toast', () => $t.remove());
    t.show();
  };

  /* ---------- Confirm dialog (Bootstrap modal, returns a Promise) ---------- */
  WB.confirm = function (opts) {
    opts = $.extend({ title: 'Are you sure?', message: '', confirmText: 'Confirm', danger: true }, opts || {});
    return new Promise(function (resolve) {
      const $m = $('#confirmModal');
      $m.find('.modal-title').text(opts.title);
      $m.find('.modal-body').text(opts.message);
      const $btn = $m.find('[data-confirm-ok]').text(opts.confirmText)
        .removeClass('btn-danger btn-primary').addClass(opts.danger ? 'btn-danger' : 'btn-primary');
      let ok = false;
      $btn.off('click').on('click', function () { ok = true; modal.hide(); });
      $m.off('hidden.bs.modal').on('hidden.bs.modal', function () { resolve(ok); });
      const modal = bootstrap.Modal.getOrCreateInstance($m[0]);
      modal.show();
    });
  };

  /* ---------- Button loading state ---------- */
  WB.busy = function ($btn, on) {
    $btn = $($btn);
    if (on) {
      $btn.data('orig', $btn.html()).prop('disabled', true)
        .html('<span class="spinner-border spinner-border-sm me-1" aria-hidden="true"></span>' + WB.esc($btn.data('busy-text') || 'Please wait…'));
    } else {
      $btn.prop('disabled', false).html($btn.data('orig') || $btn.html());
    }
  };

  /* ---------- Generic POST with JSON response ---------- */
  WB.post = function (url, data) {
    return $.ajax({ url: url, method: 'POST', data: data || {}, dataType: 'json' });
  };

  /* ---------- Form validation display ---------- */
  WB.clearErrors = function ($form) {
    $form.find('.is-invalid').removeClass('is-invalid');
    $form.find('.invalid-feedback.wb-err').remove();
    $form.find('.form-alert').addClass('d-none').text('');
  };
  WB.showErrors = function ($form, errors, message) {
    let first = null;
    $.each(errors || {}, function (name, msg) {
      const $f = $form.find('[name="' + name + '"], [name="' + name + '[]"]').first();
      if ($f.length) {
        $f.addClass('is-invalid');
        const $fb = $('<div class="invalid-feedback wb-err"></div>').text(msg);
        const $grp = $f.closest('.input-group');
        ($grp.length ? $grp : $f).after($fb);
        if ($grp.length) $grp.addClass('has-validation');
        first = first || $f;
      }
    });
    if (message) {
      $form.find('.form-alert').removeClass('d-none').text(message);
    }
    if (first) first.trigger('focus');
  };

  /* ---------- AJAX forms: <form data-ajax action="..."> ---------- */
  $(document).on('submit', 'form[data-ajax]', function (e) {
    e.preventDefault();
    const $form = $(this);
    const $btn = $form.find('[type="submit"]').first();
    WB.clearErrors($form);
    WB.busy($btn, true);
    $.ajax({
      url: $form.attr('action'), method: 'POST', data: new FormData(this),
      processData: false, contentType: false, dataType: 'json'
    }).done(function (res) {
      if (res.ok) {
        WB.toast(res.message || 'Saved.');
        $form.trigger('wb:saved', [res]);
        const $modal = $form.closest('.modal');
        if ($modal.length) bootstrap.Modal.getOrCreateInstance($modal[0]).hide();
        if (res.redirect) { setTimeout(() => { window.location.href = res.redirect; }, 400); }
        if ($form.data('reload-table')) WB.tables[$form.data('reload-table')] && WB.tables[$form.data('reload-table')].load();
        if ($form.data('reset')) { $form[0].reset(); $form.find('.img-preview').removeClass('show').attr('src', ''); }
      } else {
        WB.showErrors($form, res.errors, res.message);
        WB.toast(res.message || 'Please correct the errors.', 'danger');
      }
    }).fail(function (xhr) {
      const r = xhr.responseJSON;
      if ((xhr.status === 422 || xhr.status === 429) && r) {
        WB.showErrors($form, r.errors, r.message);
        WB.toast(r.message || 'Please correct the errors.', 'danger');
      }
    }).always(function () { WB.busy($btn, false); });
  });

  /* ---------- Action buttons: <button data-post="url" data-confirm="..."> ---------- */
  $(document).on('click', '[data-post]', async function (e) {
    e.preventDefault();
    const $b = $(this);
    if ($b.data('confirm')) {
      const ok = await WB.confirm({
        title: $b.data('confirm-title') || 'Please confirm',
        message: $b.data('confirm'),
        confirmText: $b.data('confirm-text') || 'Yes, continue',
        danger: $b.data('danger') !== false
      });
      if (!ok) return;
    }
    WB.busy($b, true);
    WB.post($b.data('post')).done(function (res) {
      WB.toast(res.message || 'Done.', res.ok ? 'success' : 'danger');
      if (res.ok) {
        $b.trigger('wb:done', [res]);
        if (res.redirect) { setTimeout(() => { window.location.href = res.redirect; }, 400); return; }
        const t = $b.data('reload-table') || $b.closest('[data-table-id]').data('table-id');
        if (t && WB.tables[t]) WB.tables[t].load();
        if ($b.data('reload-page')) setTimeout(() => window.location.reload(), 500);
      }
    }).fail(function (xhr) {
      if (xhr.status === 422 && xhr.responseJSON) WB.toast(xhr.responseJSON.message, 'danger');
    }).always(function () { WB.busy($b, false); });
  });

  /* ---------- Image preview for file inputs: data-preview="#imgId" ---------- */
  $(document).on('change', 'input[type=file][data-preview]', function () {
    const $img = $($(this).data('preview'));
    const f = this.files && this.files[0];
    if (f && /^image\//.test(f.type)) {
      const r = new FileReader();
      r.onload = ev => $img.attr('src', ev.target.result).addClass('show');
      r.readAsDataURL(f);
    } else {
      $img.removeClass('show').attr('src', '');
    }
  });

  /* ---------- Data tables: search, filters, sort, pagination over JSON endpoints ---------- */
  WB.tables = {};
  WB.Table = function (opts) {
    this.opts = $.extend({ perPage: 15, sort: null, dir: 'asc' }, opts);
    this.$el = $(opts.el);
    this.$filters = $(opts.filters || []);
    this.state = { page: 1, sort: this.opts.sort, dir: this.opts.dir };
    this.id = opts.id;
    this.$el.attr('data-table-id', opts.id);
    WB.tables[opts.id] = this;
    this.build();
    this.bind();
    this.load();
  };
  WB.Table.prototype.build = function () {
    const cols = this.opts.columns;
    let head = '<thead><tr>';
    cols.forEach(c => {
      const sortable = c.sort ? ' role="button" data-sort="' + c.sort + '"' : '';
      head += '<th class="' + (c.cls || '') + '"' + sortable + '>' + WB.esc(c.title || '') + (c.sort ? ' <i class="bi bi-arrow-down-up small opacity-50"></i>' : '') + '</th>';
    });
    head += '</tr></thead>';
    this.$el.html('<div class="table-wrap"><div class="table-responsive"><table class="table table-hover mb-0">' + head + '<tbody></tbody></table></div></div>' +
      '<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 p-3 border-top table-footer"><small class="text-muted table-info"></small><nav><ul class="pagination pagination-sm mb-0"></ul></nav></div>');
  };
  WB.Table.prototype.bind = function () {
    const self = this;
    let timer = null;
    this.$filters.on('input change', 'input, select', function () {
      clearTimeout(timer);
      timer = setTimeout(() => { self.state.page = 1; self.load(); }, $(this).is('input[type=search],input[type=text]') ? 350 : 0);
    });
    this.$filters.on('submit', e => e.preventDefault());
    this.$el.on('click', '.page-link[data-page]', function (e) {
      e.preventDefault();
      self.state.page = parseInt($(this).data('page'), 10);
      self.load();
    });
    this.$el.on('click', 'th[data-sort]', function () {
      const s = $(this).data('sort');
      self.state.dir = (self.state.sort === s && self.state.dir === 'asc') ? 'desc' : 'asc';
      self.state.sort = s;
      self.load();
    });
  };
  WB.Table.prototype.params = function () {
    const p = { page: this.state.page, per_page: this.opts.perPage };
    if (this.state.sort) { p.sort = this.state.sort; p.dir = this.state.dir; }
    this.$filters.find('input, select').each(function () { if (this.name) p[this.name] = $(this).val(); });
    return p;
  };
  WB.Table.prototype.load = function () {
    const self = this;
    const $wrap = this.$el.find('.table-wrap');
    if (!$wrap.find('.table-loading').length) $wrap.append('<div class="table-loading"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div></div>');
    return $.getJSON(this.opts.url, this.params()).done(function (res) {
      const $tb = self.$el.find('tbody').empty();
      if (!res.rows || !res.rows.length) {
        $tb.html('<tr><td colspan="' + self.opts.columns.length + '"><div class="empty-state"><i class="bi bi-inbox"></i>' + WB.esc(self.opts.empty || 'No records found.') + '</div></td></tr>');
      } else {
        res.rows.forEach(row => {
          let tr = '<tr data-id="' + WB.esc(row.id) + '">';
          self.opts.columns.forEach(c => { tr += '<td class="' + (c.cls || '') + '">' + (c.render ? c.render(row) : WB.esc(row[c.key])) + '</td>'; });
          $tb.append(tr + '</tr>');
        });
      }
      self.renderPager(res);
      self.$el.trigger('wb:loaded', [res]);
    }).always(function () { $wrap.find('.table-loading').remove(); });
  };
  WB.Table.prototype.renderPager = function (res) {
    const from = res.total ? (res.page - 1) * res.per_page + 1 : 0;
    const to = Math.min(res.total, res.page * res.per_page);
    this.$el.find('.table-info').text(res.total ? ('Showing ' + from + '–' + to + ' of ' + res.total) : '');
    const $ul = this.$el.find('.pagination').empty();
    if (res.pages <= 1) return;
    const li = (p, label, disabled, active) => '<li class="page-item' + (disabled ? ' disabled' : '') + (active ? ' active' : '') + '"><a class="page-link" href="#" data-page="' + p + '">' + label + '</a></li>';
    $ul.append(li(res.page - 1, '&laquo;', res.page === 1));
    let start = Math.max(1, res.page - 2), end = Math.min(res.pages, start + 4);
    start = Math.max(1, end - 4);
    for (let p = start; p <= end; p++) $ul.append(li(p, p, false, p === res.page));
    $ul.append(li(res.page + 1, '&raquo;', res.page === res.pages));
  };

  /* ---------- Modal helpers ---------- */
  WB.openForm = function (modalSel, data, opts) {
    const $m = $(modalSel);
    const $f = $m.find('form');
    opts = opts || {};
    $f[0].reset();
    WB.clearErrors($f);
    $f.find('.img-preview').removeClass('show').attr('src', '');
    $f.attr('action', opts.action);
    $m.find('.modal-title').text(opts.title || '');
    if (data) {
      $.each(data, function (k, v) {
        const $i = $f.find('[name="' + k + '"]');
        if (!$i.length || $i.attr('type') === 'file' || $i.attr('type') === 'password') return;
        if ($i.attr('type') === 'checkbox') $i.prop('checked', !!Number(v) || v === 'active' || v === true);
        else $i.val(v === null ? '' : v);
      });
    }
    if (opts.image) $f.find('.img-preview').attr('src', opts.image).addClass('show');
    $f.trigger('wb:open', [data]);
    bootstrap.Modal.getOrCreateInstance($m[0]).show();
  };

  /* ---------- Layout: mobile sidebar ---------- */
  $(document).on('click', '[data-toggle-sidebar]', function () {
    $('.wb-sidebar').toggleClass('open');
    $('.wb-backdrop').toggleClass('show');
  });
  $(document).on('click', '.wb-backdrop', function () {
    $('.wb-sidebar').removeClass('open');
    $(this).removeClass('show');
  });

  /* ---------- Logout ---------- */
  $(document).on('click', '[data-logout]', function (e) {
    e.preventDefault();
    WB.post(WB.url('logout')).always(function () { window.location.href = WB.url('login'); });
  });

  /* ---------- Flash message passed from server via data attribute ---------- */
  $(function () {
    const $f = $('#flashData');
    if ($f.length && $f.data('message')) WB.toast($f.data('message'), $f.data('type') || 'success');
  });
})(jQuery, window);
