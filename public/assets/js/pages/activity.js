$(function () {
  new WB.Table({
    id: 'activity', el: '#activityTable', filters: '#activityFilters', url: WB.url('activity/list'), perPage: 25, empty: 'No activity recorded.',
    columns: [
      { title: 'When', render: r => WB.datetime(r.created_at) },
      { title: 'User', render: r => WB.esc(r.user || 'System') },
      { title: 'Action', render: r => '<code>' + WB.esc(r.action) + '</code>' },
      { title: 'Record', render: r => r.entity_type ? WB.esc(r.entity_type) + (r.entity_id ? ' #' + WB.esc(r.entity_id) : '') : '—' },
      { title: 'Details', render: r => '<small>' + WB.esc(r.details || '') + '</small>' },
      { title: 'IP', render: r => '<small class="text-muted">' + WB.esc(r.ip || '') + '</small>' }
    ]
  });
});
