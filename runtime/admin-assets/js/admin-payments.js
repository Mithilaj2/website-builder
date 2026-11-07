/* Website admin: payments (transactions list and settings) */
(function ($) {
  'use strict';
  const statusColour = { paid: 'success', pending: 'warning', failed: 'danger', cancelled: 'secondary', created: 'light' };
  const badge = s => '<span class="badge text-bg-' + (statusColour[s] || 'secondary') + (s === 'created' ? ' border' : '') + '">' + WB.esc(s === 'created' ? 'Not started' : s.charAt(0).toUpperCase() + s.slice(1)) + '</span>';

  /* ---------------- transactions ---------------- */
  if ($('#payTable').length) {
    let current = null;
    const table = new WB.Table({
      id: 'payments', el: '#payTable', filters: '#payFilters', url: WB.url('api/payments'), perPage: 20,
      empty: 'No payments yet. Payments made on your website’s “Pay online” page appear here.',
      columns: [
        { title: 'Date', render: r => '<span class="text-nowrap">' + WB.esc(WB.datetime(r.created_at)) + '</span><div class="small text-muted font-monospace">' + WB.esc(r.reference) + '</div>' },
        { title: 'Customer', render: r => WB.esc(r.customer_name) + '<div class="small text-muted">' + WB.esc(r.customer_email) + '</div>' },
        { title: 'For', render: r => '<div class="text-truncate" style="max-width:260px">' + WB.esc(r.purpose) + '</div>' },
        { title: 'Amount', cls: 'text-end text-nowrap fw-semibold', render: r => WB.esc(r.amount_text) },
        { title: 'Status', render: r => badge(r.status) + (r.mode === 'test' ? ' <span class="badge text-bg-warning-subtle text-warning-emphasis border">test</span>' : '') },
        { title: '', cls: 'text-end', render: r => '<button class="btn btn-sm btn-light" data-pay-view="' + r.id + '"><i class="bi bi-eye"></i> View</button>' }
      ]
    });
    $('#payTable').on('wb:loaded', function (e, res) {
      $('#payTotal').text(res.total ? 'Paid in this list: ' + res.paid_total_text : '');
      $('#payExport').attr('href', WB.url('api/payments/export') + '?' + $('#payFilters').serialize());
    });
    const row = (k, v) => v ? '<dt class="col-sm-4">' + WB.esc(k) + '</dt><dd class="col-sm-8">' + v + '</dd>' : '';
    function show(id) {
      $.getJSON(WB.url('api/payments/' + id)).done(function (res) {
        const p = current = res.payment;
        let html = '<div class="d-flex align-items-center gap-2 mb-3"><span class="fs-3 fw-bold">' + WB.esc(p.amount_text) + '</span>' + badge(p.status) +
          (p.mode === 'test' ? '<span class="badge text-bg-warning">Test mode</span>' : '') + '</div>';
        if (p.message) html += '<div class="alert alert-' + (p.status === 'paid' ? 'info' : 'warning') + ' py-2 small">' + WB.esc(p.message) + '</div>';
        html += '<dl class="row small mb-3">' + row('Reference', '<span class="font-monospace">' + WB.esc(p.reference) + '</span>') + row('For', WB.esc(p.purpose)) +
          row('Customer', WB.esc(p.customer_name) + '<br>' + WB.esc(p.customer_email) + ' · ' + WB.esc(p.customer_phone)) + row('Note', WB.esc(p.note || '')) +
          row('Gateway', WB.esc(p.gateway_name)) + row('Gateway order', '<span class="font-monospace">' + WB.esc(p.gateway_order_id || '') + '</span>') +
          row('Payment ID', p.gateway_payment_id ? '<span class="font-monospace">' + WB.esc(p.gateway_payment_id) + '</span>' : '') + row('Method', WB.esc(p.method || '')) +
          row('Started', WB.esc(WB.datetime(p.created_at))) + row('Paid', p.paid_at ? WB.esc(WB.datetime(p.paid_at)) : '') + '</dl>';
        if (res.events.length) {
          html += '<div class="small fw-semibold mb-1">History</div><ul class="list-group list-group-flush small">' + res.events.map(ev =>
            '<li class="list-group-item px-0 d-flex gap-2"><span class="text-muted text-nowrap">' + WB.esc(WB.datetime(ev.created_at)) + '</span><span class="badge text-bg-light border">' + WB.esc(ev.source) + '</span>' +
            '<span>' + WB.esc(ev.result || '') + (ev.detail ? ' — ' + WB.esc(ev.detail) : '') + (Number(ev.verified) ? '' : ' <span class="text-danger">(not verified)</span>') + '</span></li>').join('') + '</ul>';
        }
        $('#payModal .modal-body').html(html);
        $('#payModal .modal-title').text('Payment ' + p.reference);
        $('#payReceipt').attr('href', p.receipt_url);
        $('#payRecheck').toggle(p.status !== 'paid');
        bootstrap.Modal.getOrCreateInstance($('#payModal')[0]).show();
      });
    }
    $(document).on('click', '[data-pay-view]', function () { show($(this).data('pay-view')); });
    $('#payRecheck').on('click', function () {
      if (!current) return;
      const $b = $(this);
      WB.busy($b, true);
      WB.post(WB.url('api/payments/' + current.id + '/recheck')).done(res => { WB.toast(res.message, res.status === 'paid' ? 'success' : 'info'); show(current.id); table.load(); })
        .fail(x => { if (x.responseJSON) WB.toast(x.responseJSON.message, 'danger'); })
        .always(() => WB.busy($b, false));
    });
  }

  /* ---------------- settings ---------------- */
  const $form = $('#paySettingsForm');
  if ($form.length) {
    const sync = function () {
      const gw = $('#payGateway').val();
      const mode = $form.find('[name=mode]:checked').val();
      $('.gw-keys').each(function () { this.hidden = $(this).data('gateway') !== gw; });
      $('.mode-keys').each(function () { $(this).toggleClass('border-primary', $(this).data('mode') === mode); });
      $('#modeHelp').text(mode === 'live'
        ? 'LIVE mode: customers pay real money into your account. Make sure a test payment worked first.'
        : 'Test mode uses the gateway’s sandbox: no real money moves. Switch to Live only after a successful test payment.');
    };
    $('#payGateway, [name=mode]').on('change', sync);
    sync();
    $form.on('wb:saved', () => $form.find('input[type=password]').val(''));
    $(document).on('click', '[data-copy]', function () {
      const t = $(this).data('copy');
      if (navigator.clipboard) navigator.clipboard.writeText(t).then(() => WB.toast('Copied.'));
    });
    $(document).on('click', '[data-test-connection]', function () {
      const $b = $(this);
      WB.clearErrors($form);
      WB.busy($b, true);
      $.ajax({ url: $form.attr('action'), method: 'POST', data: new FormData($form[0]), processData: false, contentType: false, dataType: 'json' })
        .then(function () {
          $form.find('input[type=password]').val('');
          return WB.post(WB.url('payments/test'), { gateway: $b.data('gateway'), mode: $b.data('mode') });
        })
        .done(res => WB.toast(res.message, 'success'))
        .fail(x => {
          const r = x.responseJSON;
          if (r) { if (r.errors && Object.keys(r.errors).length) WB.showErrors($form, r.errors, r.message); WB.toast(r.message, 'danger'); }
        })
        .always(() => WB.busy($b, false));
    });
  }
})(jQuery);
