$(function () {
  const canManage = WB.can.indexOf('templates.manage') !== -1;

  /* Render a validation report {ok, errors[], warnings[]} into a container */
  WB.renderReport = function ($el, report) {
    if (!report) { $el.empty(); return; }
    let html = report.ok
      ? '<div class="alert alert-success py-2 mb-2"><i class="bi bi-shield-check me-1"></i>Passed validation and security scan.</div>'
      : '<div class="alert alert-danger py-2 mb-2"><i class="bi bi-shield-exclamation me-1"></i>' + report.errors.length + ' error(s) must be fixed.</div>';
    if (report.errors && report.errors.length) {
      html += '<ul class="small text-danger mb-2">' + report.errors.map(e => '<li>' + WB.esc(e) + '</li>').join('') + '</ul>';
    }
    if (report.warnings && report.warnings.length) {
      html += '<div class="small fw-semibold text-warning-emphasis">Warnings</div><ul class="small text-warning-emphasis mb-0">' + report.warnings.map(e => '<li>' + WB.esc(e) + '</li>').join('') + '</ul>';
    }
    $el.html(html);
  };

  /* ---------- Index page ---------- */
  if ($('#tplTable').length) {
    new WB.Table({
      id: 'templates', el: '#tplTable', filters: '#tplFilters', url: WB.url('templates/list'), sort: 'updated_at', dir: 'desc',
      empty: 'No templates yet. Upload a template ZIP or scan the templates folder.',
      columns: [
        { title: 'Preview', render: r => r.preview_url ? '<img class="thumb" src="' + WB.esc(r.preview_url) + '" alt="">' : '<div class="thumb-placeholder"><i class="bi bi-image"></i></div>' },
        { title: 'Template', sort: 'name', render: r => '<a class="fw-semibold" href="' + WB.url('templates/' + r.id) + '">' + WB.esc(r.name) + '</a><br><code class="small">' + WB.esc(r.code) + '</code>' },
        { title: 'Category', sort: 'category', key: 'category' },
        { title: 'Version', render: r => 'v' + WB.esc(r.current_version) + ' <small class="text-muted">(' + Number(r.versions) + ')</small>' },
        { title: 'Websites', sort: 'websites', render: r => Number(r.websites) },
        { title: 'Status', sort: 'status', render: r => WB.badge(r.status) },
        { title: 'Updated', sort: 'updated_at', render: r => WB.date(r.updated_at) },
        { title: '', cls: 'text-end text-nowrap', render: r =>
            '<a class="btn btn-sm btn-light" href="' + WB.url('templates/' + r.id) + '" title="Open"><i class="bi bi-eye"></i></a> ' + (!canManage ? '' :
            '<button class="btn btn-sm btn-light" data-post="' + WB.url('templates/' + r.id + '/status') + '" data-reload-table="templates" title="' + (r.status === 'active' ? 'Deactivate' : 'Activate') + '"><i class="bi bi-' + (r.status === 'active' ? 'toggle-on text-success' : 'toggle-off') + '"></i></button>')
        }
      ]
    });
  }

  if (!canManage) return;

  /* ZIP uploads (new template / new version) — show the validation report on failure */
  function zipUpload($form) {
    $form.on('submit', function (e) {
      e.preventDefault();
      const $btn = $form.find('[type=submit]');
      const $report = $form.find('.report').empty();
      WB.busy($btn, true);
      $.ajax({ url: $form.attr('action'), method: 'POST', data: new FormData(this), processData: false, contentType: false, dataType: 'json' })
        .done(function (res) {
          WB.toast(res.message);
          if (res.report) WB.renderReport($report, res.report);
          setTimeout(() => { window.location.href = res.redirect || window.location.href; }, 900);
        })
        .fail(function (xhr) {
          const r = xhr.responseJSON || {};
          if (r.report) WB.renderReport($report, r.report);
          else if (r.message) $report.html('<div class="alert alert-danger py-2">' + WB.esc(r.message) + '</div>');
          if (r.message) WB.toast(r.message, 'danger');
        })
        .always(() => WB.busy($btn, false));
    });
  }
  zipUpload($('#uploadForm'));
  zipUpload($('#versionForm'));

  $('#btnUpload').on('click', function () {
    $('#uploadForm')[0].reset();
    $('#uploadForm .report').empty();
    bootstrap.Modal.getOrCreateInstance($('#uploadModal')[0]).show();
  });

  /* Scan for unregistered folders */
  $('#btnScan').on('click', function () {
    const $res = $('#scanModal .scan-results').html('<div class="text-center p-4"><div class="spinner-border text-primary"></div></div>');
    bootstrap.Modal.getOrCreateInstance($('#scanModal')[0]).show();
    $.getJSON(WB.url('templates/scan')).done(function (res) {
      if (!res.items.length) { $res.html('<div class="empty-state"><i class="bi bi-check2-circle"></i>All template folders are registered.</div>'); return; }
      $res.empty();
      res.items.forEach(function (it, i) {
        const $card = $('<div class="border rounded p-3 mb-2"></div>');
        $card.append('<div class="d-flex align-items-center gap-2"><div><div class="fw-semibold">' + WB.esc(it.name) + ' <small class="text-muted">v' + WB.esc(it.version) + '</small></div><code class="small">' + WB.esc(it.path) + '</code></div>' +
          (it.report.ok ? '<button class="btn btn-sm btn-primary ms-auto" data-register="' + i + '">Register</button>' : '<span class="badge text-bg-danger ms-auto">Invalid</span>') + '</div><div class="rep mt-2"></div>');
        WB.renderReport($card.find('.rep'), it.report);
        $card.find('[data-register]').on('click', function () {
          const $b = $(this);
          WB.busy($b, true);
          WB.post(WB.url('templates/register'), { category: it.category, folder: it.folder })
            .done(r => { WB.toast(r.message); if (r.redirect) window.location.href = r.redirect; })
            .fail(x => { const r = x.responseJSON || {}; if (r.report) WB.renderReport($card.find('.rep'), r.report); if (r.message) WB.toast(r.message, 'danger'); })
            .always(() => WB.busy($b, false));
        });
        $res.append($card);
      });
    });
  });

  /* ---------- Show page ---------- */
  const $rep = $('#validationCard .report');
  if ($rep.length) WB.renderReport($rep, $rep.data('initial'));

  $('#btnRevalidate').on('click', function () {
    const $b = $(this);
    WB.busy($b, true);
    WB.post($b.data('url')).done(r => { WB.renderReport($rep, r.report); WB.toast(r.message, r.report.ok ? 'success' : 'warning'); }).always(() => WB.busy($b, false));
  });

  // Activation failures return a validation report: show it on the page.
  $(document).ajaxComplete(function (e, xhr, settings) {
    if (xhr.status === 422 && xhr.responseJSON && xhr.responseJSON.report && /\/status$/.test(settings.url) && $rep.length) {
      WB.renderReport($rep, xhr.responseJSON.report);
    }
  });

  $('#btnEditTpl').on('click', function () {
    const id = $(this).data('id');
    $.getJSON(WB.url('templates/' + id + '/json')).done(function (res) {
      const $f = $('#editModal form');
      WB.clearErrors($f);
      $f.find('[name=name]').val(res.data.name);
      $f.find('[name=description]').val(res.data.description || '');
      $f.find('[name=category_id]').val(res.data.category_id);
      $f.find('[name=preview_image]').val('');
      if (res.data.preview_url) $('#tplPrev').attr('src', res.data.preview_url).addClass('show'); else $('#tplPrev').removeClass('show');
      bootstrap.Modal.getOrCreateInstance($('#editModal')[0]).show();
    });
  });

  // Reload the show page after edits / duplicate / screenshots
  $('#editModal form, #screensModal form').on('wb:saved', () => setTimeout(() => window.location.reload(), 500));
});
