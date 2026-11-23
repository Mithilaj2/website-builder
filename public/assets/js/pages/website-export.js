/* Builder: Export & deploy page */
(function ($) {
  'use strict';
  const cfg = $('#exportConfig').data('config');
  const U = p => cfg.api + '/' + p;
  const icons = { pending: 'circle text-muted', running: 'arrow-repeat text-primary', success: 'check-circle-fill text-success', failed: 'x-circle-fill text-danger' };
  let running = false;

  function syncType() {
    $('#fullOptions').toggle($('[name=type]:checked').val() === 'full');
  }
  $('[name=type]').on('change', syncType);
  syncType();

  function render(st) {
    $('#exportProgress').removeClass('d-none');
    $('#exportProgress .progress-bar').css('width', st.progress + '%').toggleClass('bg-danger', st.status === 'failed')
      .toggleClass('bg-success', st.status === 'done').toggleClass('progress-bar-animated', st.status === 'running');
    $('#exportProgress .gen-steps').html(st.steps.map(s => '<li><i class="bi bi-' + icons[s.status] + '"></i><div>' + WB.esc(s.label) +
      (s.status === 'running' && st.detail ? ' <span class="text-muted">(' + WB.esc(st.detail) + ')</span>' : '') + '</div></li>').join(''));
    $('#exportProgress .export-log').html((st.log || []).map(l => '<div class="' + (/^(Warning|ERROR)/.test(l) ? 'text-warning-emphasis' : '') + '">' + WB.esc(l) + '</div>').join(''));
    const $a = $('#exportProgress .export-actions').empty();
    if (st.status === 'failed') $a.html('<div class="alert alert-danger small py-2 mb-0">' + WB.esc(st.error || 'Export failed.') + '</div>');
    if (st.status === 'done') $a.html('<div class="alert alert-success small py-2 mb-0"><i class="bi bi-check-circle-fill me-1"></i>Package ready — download it from the list below.</div>');
  }

  function follow(id) {
    running = true;
    $('#btnExport').prop('disabled', true);
    const tick = function () {
      WB.post(U('exports/' + id + '/step')).done(function (r) {
        render(r.state);
        if (r.state.status === 'running') { setTimeout(tick, 100); return; }
        running = false;
        $('#btnExport').prop('disabled', false);
        WB.toast(r.state.status === 'done' ? 'Package ready.' : 'Export failed: ' + (r.state.error || ''), r.state.status === 'done' ? 'success' : 'danger');
        loadHistory();
      }).fail(function () { running = false; $('#btnExport').prop('disabled', false); WB.toast('Lost contact with the server. Start the export again.', 'danger'); });
    };
    tick();
  }

  $('#exportForm').on('submit', function (e) {
    e.preventDefault();
    if (running) return;
    const $f = $(this), $b = $('#btnExport');
    WB.clearErrors($f);
    WB.busy($b, true);
    WB.post(U('exports'), $f.serialize()).done(function (r) {
      WB.busy($b, false);
      render(r.state);
      follow(r.state.export_id);
    }).fail(function (x) {
      WB.busy($b, false);
      const r = x.responseJSON;
      if (r) { WB.showErrors($f, r.errors, r.message); WB.toast(r.message, 'danger'); }
    });
  });

  let showRemoved = false;
  function loadHistory() {
    $.getJSON(U('exports')).done(function (res) {
      const can = WB.can.indexOf('websites.manage') >= 0;
      const available = x => x.status !== 'done' || Number(x.has_zip);
      const hidden = res.exports.filter(x => !available(x)).length;
      const list = showRemoved ? res.exports : res.exports.filter(available);
      const rows = list.map(x => {
        const st = x.status === 'done' ? (Number(x.has_zip) ? '<span class="badge text-bg-success">Ready</span>' : '<span class="badge text-bg-light border">Removed</span>')
          : x.status === 'running' ? '<span class="badge text-bg-info">Running</span>'
          : '<span class="badge text-bg-danger" title="' + WB.esc(x.error_message || '') + '">Failed</span>';
        let dl = '';
        if (can && x.status === 'done' && Number(x.has_zip)) {
          dl = '<a class="btn btn-sm btn-primary" href="' + U('exports/' + x.id + '/download') + '"><i class="bi bi-file-earmark-zip me-1"></i>ZIP</a>' +
            (Number(x.has_sql) ? ' <a class="btn btn-sm btn-outline-secondary" href="' + U('exports/' + x.id + '/download') + '?file=sql" title="Database only (schema.sql + data.sql)"><i class="bi bi-database me-1"></i>SQL</a>' : '');
        }
        if (can && x.status !== 'running') dl += ' <button class="btn btn-sm btn-light text-danger" data-post="' + U('exports/' + x.id + '/delete') + '" data-confirm="Delete this package?" data-confirm-text="Delete" data-reload-history="1" aria-label="Delete package"><i class="bi bi-trash"></i></button>';
        const key = x.install_key && Number(x.has_zip)
          ? '<code class="user-select-all">' + WB.esc(x.install_key) + '</code> <button class="btn btn-link btn-sm p-0 align-baseline" data-copy-key="' + WB.esc(x.install_key) + '" aria-label="Copy installation key"><i class="bi bi-clipboard"></i></button>'
          : '<span class="text-muted">—</span>';
        return '<tr' + (available(x) ? '' : ' class="text-muted"') + '><td><span class="text-nowrap">' + WB.esc(WB.datetime(x.created_at)) + '</span>' + '<div class="text-muted">' + WB.esc(x.user || '') + '</div></td>' +
          '<td>' + (x.type === 'full' ? '<i class="bi bi-box-seam me-1"></i>Full' : '<i class="bi bi-arrow-up-circle me-1"></i>Update') +
            (x.size_text ? ' <span class="text-muted">· ' + WB.esc(x.size_text) + '</span>' : '') +
            '<div class="text-muted">engine ' + WB.esc(x.runtime_version || '—') + ' · template ' + WB.esc(x.template_version || '—') + '</div></td>' +
          '<td>' + key + '</td>' +
          '<td>' + st + '</td><td class="text-end">' + dl + '</td></tr>';
      });
      $('#exportHistory').html(rows.join('') || '<tr><td colspan="5"><div class="empty-state"><i class="bi bi-box-seam"></i>No packages yet.</div></td></tr>');
      $('#toggleRemoved').prop('hidden', !hidden).text(showRemoved ? 'Hide older packages' : 'Show ' + hidden + ' older package' + (hidden === 1 ? '' : 's') + ' (files removed)');
    });
  }
  $('#toggleRemoved').on('click', function () { showRemoved = !showRemoved; loadHistory(); });
  $(document).on('click', '[data-copy-key]', function () {
    const k = $(this).data('copy-key');
    if (navigator.clipboard) navigator.clipboard.writeText(k).then(() => WB.toast('Installation key copied.'));
  });
  $('#historyReload').on('click', loadHistory);
  $(document).on('wb:done', '[data-reload-history]', loadHistory);
  loadHistory();
})(jQuery);
