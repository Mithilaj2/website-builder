/* Website admin: contact form enquiries */
(function ($) {
  'use strict';
  const labels = { new: 'New', read: 'Read', replied: 'Replied', spam: 'Spam' };
  const colours = { new: 'danger', read: 'secondary', replied: 'success', spam: 'dark' };
  const badge = s => '<span class="badge text-bg-' + (colours[s] || 'secondary') + '">' + WB.esc(labels[s] || s) + '</span>';
  let current = null;

  const table = new WB.Table({
    id: 'enquiries', el: '#enqTable', filters: '#enqFilters', url: WB.url('api/enquiries'), perPage: 20,
    empty: 'No enquiries yet. Messages sent through your website forms appear here.',
    columns: [
      { title: 'Received', render: r => '<span class="text-nowrap">' + WB.esc(WB.datetime(r.created_at)) + '</span>' },
      { title: 'From', render: r => WB.esc(r.name || '—') + '<div class="small text-muted">' + WB.esc([r.email, r.phone].filter(Boolean).join(' · ')) + '</div>' },
      { title: 'Message', render: r => {
          const kinds = { appointment: 'Appointment', admission: 'Admission', booking: 'Booking' };
          let extra = {};
          try { extra = r.extra_json ? JSON.parse(r.extra_json) : {}; } catch (e) { extra = {}; }
          const details = Object.keys(extra).map(k => k + ': ' + extra[k]).join(' · ');
          return (kinds[r.form_key] ? '<span class="badge text-bg-primary-subtle text-primary-emphasis border me-1">' + kinds[r.form_key] + '</span>' : '') +
            (r.subject ? '<span class="fw-semibold">' + WB.esc(r.subject) + '</span>' : '') +
            '<div class="small text-muted text-truncate" style="max-width:420px">' + WB.esc(details || r.excerpt || '') + '</div>';
        } },
      { title: 'Status', render: r => badge(r.status) + (Number(r.email_sent) ? '' : ' <i class="bi bi-envelope-slash text-muted" title="Email notification was not sent"></i>') },
      { title: '', cls: 'text-end', render: r => '<button class="btn btn-sm btn-light" data-view="' + r.id + '"><i class="bi bi-eye"></i> View</button>' }
    ]
  });
  $('#enqTable').on('wb:loaded', function (e, res) {
    (res.rows || []).forEach(r => { if (r.status === 'new') $(this).find('tr[data-id="' + r.id + '"]').addClass('enq-new'); });
  });

  function fields(d) {
    let extra = {};
    try { extra = d.extra_json ? JSON.parse(d.extra_json) : {}; } catch (e) { extra = {}; }
    const rows = [['Name', d.name], ['Email', d.email], ['Phone', d.phone], ['Subject', d.subject], ['Page', d.page_slug], ['Received', WB.datetime(d.created_at)]];
    Object.keys(extra || {}).forEach(k => rows.push([k.replace(/_/g, ' '), Array.isArray(extra[k]) ? extra[k].join(', ') : extra[k]]));
    let html = '<dl class="row enq-meta mb-3">';
    rows.filter(r => r[1]).forEach(r => { html += '<dt class="col-sm-3 text-capitalize">' + WB.esc(r[0]) + '</dt><dd class="col-sm-9">' + WB.esc(r[1]) + '</dd>'; });
    return html + '</dl><div class="enq-message">' + WB.esc(d.message || '') + '</div>';
  }

  $(document).on('click', '[data-view]', function () {
    $.getJSON(WB.url('api/enquiries/' + $(this).data('view'))).done(function (res) {
      current = res.enquiry;
      const $m = $('#enqModal');
      $m.find('.modal-title').text(current.subject || ('Enquiry from ' + (current.name || 'website visitor')));
      $m.find('.modal-body').html(fields(current));
      $('#enqStatus').val(current.status);
      const $r = $('#enqReply');
      if (current.email) {
        $r.removeClass('d-none').attr('href', 'mailto:' + encodeURIComponent(current.email) + '?subject=' + encodeURIComponent('Re: ' + (current.subject || 'Your enquiry')));
      } else {
        $r.addClass('d-none');
      }
      bootstrap.Modal.getOrCreateInstance($m[0]).show();
      table.load();
    });
  });
  $('#enqReply').on('click', function () {
    if (current && current.status !== 'replied') setStatus('replied', true);
  });
  $('#enqStatus').on('change', function () { setStatus($(this).val()); });
  function setStatus(s, quiet) {
    if (!current) return;
    WB.post(WB.url('api/enquiries/' + current.id + '/status'), { status: s }).done(res => {
      current.status = s; $('#enqStatus').val(s);
      if (!quiet) WB.toast(res.message);
      table.load();
    });
  }
  $('#enqDelete').on('click', async function () {
    if (!current) return;
    if (!await WB.confirm({ title: 'Delete this enquiry?', message: 'It will be removed permanently.', confirmText: 'Delete' })) return;
    WB.post(WB.url('api/enquiries/' + current.id + '/delete')).done(res => {
      WB.toast(res.message);
      bootstrap.Modal.getOrCreateInstance($('#enqModal')[0]).hide();
      current = null; table.load();
    });
  });
})(jQuery);
