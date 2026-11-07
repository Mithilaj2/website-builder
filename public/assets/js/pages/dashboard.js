$(function () {
  new WB.Table({
    id: 'recent', el: '#recentTable', filters: '#recentFilters', url: WB.url('dashboard/recent'), perPage: 10,
    empty: 'No websites yet.',
    columns: [
      { title: 'Website', render: r => '<a class="fw-semibold" href="' + WB.url('websites/' + r.id) + '">' + WB.esc(r.name) + '</a><br><small class="text-muted">' + WB.esc(r.domain || '') + '</small>' },
      { title: 'Client', key: 'client' },
      { title: 'Category / Template', render: r => WB.esc(r.category) + '<br><small class="text-muted">' + WB.esc(r.template || 'Not selected') + '</small>' },
      { title: 'Status', render: r => WB.badge(r.status) },
      { title: 'Created', render: r => WB.date(r.created_at) }
    ]
  });
});
