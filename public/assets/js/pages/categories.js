$(function () {
  const canManage = WB.can.indexOf('categories.manage') !== -1;

  const table = new WB.Table({
    id: 'categories', el: '#catTable', filters: '#catFilters', url: WB.url('categories/list'), perPage: 50,
    sort: 'sort_order', empty: 'No categories found.',
    columns: [
      { title: '', cls: 'text-center', render: () => canManage ? '<i class="bi bi-grip-vertical drag-handle" title="Drag to reorder"></i>' : '' },
      { title: 'Image', render: r => r.image_url ? '<img class="thumb" src="' + WB.esc(r.image_url) + '" alt="">' : '<div class="thumb-placeholder"><i class="bi bi-image"></i></div>' },
      { title: 'Name', sort: 'name', render: r => '<div class="fw-semibold">' + WB.esc(r.name) + '</div><small class="text-muted">' + WB.esc(r.description || '').slice(0, 90) + '</small>' },
      { title: 'Slug', render: r => '<code>' + WB.esc(r.slug) + '</code>' },
      { title: 'Templates', sort: 'templates', render: r => '<a href="' + WB.url('templates') + '?category=' + r.id + '">' + Number(r.templates) + '</a>' },
      { title: 'Websites', render: r => Number(r.websites) },
      { title: 'Status', sort: 'status', render: r => WB.badge(r.status) },
      { title: '', cls: 'text-end text-nowrap', render: r => !canManage ? '' :
          '<button class="btn btn-sm btn-light" data-edit="' + r.id + '" title="Edit"><i class="bi bi-pencil"></i></button> ' +
          '<button class="btn btn-sm btn-light" data-post="' + WB.url('categories/' + r.id + '/status') + '" data-reload-table="categories" title="' + (r.status === 'active' ? 'Deactivate' : 'Activate') + '"><i class="bi bi-' + (r.status === 'active' ? 'toggle-on text-success' : 'toggle-off') + '"></i></button> ' +
          '<button class="btn btn-sm btn-light text-danger" data-post="' + WB.url('categories/' + r.id + '/delete') + '" data-reload-table="categories" data-confirm="Delete the category “' + WB.esc(r.name) + '”? This cannot be undone." data-confirm-text="Delete" title="Delete"><i class="bi bi-trash"></i></button>'
      }
    ]
  });

  if (!canManage) return;

  // Drag-and-drop reordering (only in the default, unfiltered view)
  Sortable.create($('#catTable tbody')[0], {
    handle: '.drag-handle', animation: 150,
    onStart: function (evt) {
      const p = table.params();
      if (p.q || p.status || table.state.sort !== 'sort_order' || table.state.dir !== 'asc') {
        WB.toast('Clear search/filters and sort by order to drag rows.', 'warning');
      }
    },
    onEnd: function () {
      const p = table.params();
      if (p.q || p.status || table.state.sort !== 'sort_order' || table.state.dir !== 'asc') { table.load(); return; }
      const ids = $('#catTable tbody tr[data-id]').map(function () { return $(this).data('id'); }).get();
      WB.post(WB.url('categories/reorder'), { ids: ids }).done(res => WB.toast(res.message));
    }
  });

  $('#btnAddCategory').on('click', function () {
    $('#categoryModal .remove-image-wrap').addClass('d-none');
    WB.openForm('#categoryModal', { status: 'active' }, { action: WB.url('categories'), title: 'Add category' });
  });

  $('#catTable').on('click', '[data-edit]', function () {
    const id = $(this).data('edit');
    $.getJSON(WB.url('categories/' + id)).done(function (res) {
      $('#categoryModal .remove-image-wrap').toggleClass('d-none', !res.data.image_url);
      WB.openForm('#categoryModal', res.data, { action: WB.url('categories/' + id), title: 'Edit category', image: res.data.image_url });
    });
  });
});
