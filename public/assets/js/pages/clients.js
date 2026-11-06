$(function () {
  const canManage = WB.can.indexOf('clients.manage') !== -1;

  if ($('#clientTable').length) {
    new WB.Table({
      id: 'clients', el: '#clientTable', filters: '#clientFilters', url: WB.url('clients/list'),
      sort: 'created_at', dir: 'desc', empty: 'No clients found.',
      columns: [
        { title: 'Client', sort: 'business_name', render: r => '<a class="fw-semibold" href="' + WB.url('clients/' + r.id) + '">' + WB.esc(r.business_name) + '</a>' },
        { title: 'Contact', sort: 'contact_person', render: r => WB.esc(r.contact_person || '—') + '<br><small class="text-muted">' + WB.esc(r.email || '') + (r.phone ? ' · ' + WB.esc(r.phone) : '') + '</small>' },
        { title: 'Websites', sort: 'websites', render: r => Number(r.websites) },
        { title: 'Status', sort: 'status', render: r => WB.badge(r.status) },
        { title: 'Added', sort: 'created_at', render: r => WB.date(r.created_at) },
        { title: '', cls: 'text-end text-nowrap', render: r =>
            '<a class="btn btn-sm btn-light" href="' + WB.url('clients/' + r.id) + '" title="View"><i class="bi bi-eye"></i></a> ' + (!canManage ? '' :
            '<button class="btn btn-sm btn-light" data-edit-client="' + r.id + '" title="Edit"><i class="bi bi-pencil"></i></button> ' +
            '<button class="btn btn-sm btn-light" data-post="' + WB.url('clients/' + r.id + '/status') + '" data-reload-table="clients" title="' + (r.status === 'active' ? 'Deactivate' : 'Activate') + '"><i class="bi bi-' + (r.status === 'active' ? 'toggle-on text-success' : 'toggle-off') + '"></i></button> ' +
            '<button class="btn btn-sm btn-light text-danger" data-post="' + WB.url('clients/' + r.id + '/delete') + '" data-reload-table="clients" data-confirm="Delete the client “' + WB.esc(r.business_name) + '”?" data-confirm-text="Delete" title="Delete"><i class="bi bi-trash"></i></button>')
        }
      ]
    });
  }

  if (!canManage) return;

  $('#btnAddClient').on('click', function () {
    WB.openForm('#clientModal', { status: 'active' }, { action: WB.url('clients'), title: 'Add client' });
  });

  $(document).on('click', '[data-edit-client]', function () {
    const id = $(this).data('edit-client');
    $.getJSON(WB.url('clients/' + id + '/json')).done(function (res) {
      WB.openForm('#clientModal', res.data, { action: WB.url('clients/' + id), title: 'Edit client' });
    });
  });

  // On the client detail page, reload after saving so the details panel updates.
  $('#clientModal form').on('wb:saved', function () {
    if (!$('#clientTable').length) setTimeout(() => window.location.reload(), 500);
  });
});
