$(function () {
  new WB.Table({
    id: 'recent', el: '#recentTable', filters: '#recentFilters', url: WB.url('dashboard/recent'), perPage: 10,
    empty: 'No websites yet. Websites appear here once the creation wizard (Phase 3) is used.',
    columns: [
      { title: 'Website', render: r => '<div class="fw-semibold">' + WB.esc(r.name) + '</div><small class="text-muted">' + WB.esc(r.domain || '') + '</small>' },
      { title: 'Client', key: 'client' },
      { title: 'Category / Template', render: r => WB.esc(r.category) + '<br><small class="text-muted">' + WB.esc(r.template || 'Not selected') + '</small>' },
      { title: 'Status', render: r => WB.badge(r.status) },
      { title: 'Created', render: r => WB.date(r.created_at) }
    ]
  });
});
