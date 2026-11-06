$(function () {
  new WB.Table({
    id: 'websites', el: '#siteTable', filters: '#siteFilters', url: WB.url('websites/list'), sort: 'updated_at', dir: 'desc',
    empty: 'No websites yet. Click “New website” to create one.',
    columns: [
      { title: 'Website', sort: 'name', render: r => '<a class="fw-semibold" href="' + WB.url('websites/' + r.id) + '">' + WB.esc(r.name) + '</a><br><small class="text-muted">' + (r.site_code ? '#' + WB.esc(r.site_code) : '') + (r.domain ? ' · ' + WB.esc(r.domain) : '') + '</small>' },
      { title: 'Client', sort: 'client', key: 'client' },
      { title: 'Category / Template', render: r => WB.esc(r.category) + '<br><small class="text-muted">' + WB.esc(r.template || '—') + '</small>' },
      { title: 'Status', sort: 'status', render: r => WB.badge(r.status) },
      { title: 'Last change', sort: 'updated_at', render: r => WB.datetime(r.updated_at) },
      { title: '', cls: 'text-end', render: r => '<a class="btn btn-sm btn-light" href="' + WB.url('websites/' + r.id) + '" title="Open"><i class="bi bi-pencil-square"></i></a> <a class="btn btn-sm btn-light" href="' + WB.url('websites/' + r.id + '/preview') + '" target="_blank" rel="noopener" title="Preview"><i class="bi bi-box-arrow-up-right"></i></a>' }
    ]
  });

  const $modal = $('#newSiteModal');
  if (!$modal.length) return;
  const $form = $modal.find('form');

  $('#btnNewSite').on('click', function () {
    $form[0].reset();
    WB.clearErrors($form);
    $form.find('.new-client').addClass('d-none');
    $form.find('[name=client_mode]').val('existing');
    $form.find('[name=template_id]').val('');
    $('#templateChoices').html('<div class="col-12 text-muted small">Choose a category to see its templates.</div>');
    bootstrap.Modal.getOrCreateInstance($modal[0]).show();
  });
  if (new URLSearchParams(location.search).get('new') === '1') $('#btnNewSite').trigger('click');

  $form.find('[name=client_id]').on('change', function () {
    const isNew = this.value === '__new';
    $form.find('.new-client').toggleClass('d-none', !isNew);
    $form.find('[name=client_mode]').val(isNew ? 'new' : 'existing');
    if (isNew) $form.find('[name=new_business_name]').trigger('focus');
  });

  $('#newSiteCategory').on('change', function () {
    const cat = $(this).val();
    const $box = $('#templateChoices');
    $form.find('[name=template_id]').val('');
    if (!cat) { $box.html('<div class="col-12 text-muted small">Choose a category to see its templates.</div>'); return; }
    $box.html('<div class="col-12"><div class="spinner-border spinner-border-sm text-primary"></div></div>');
    $.getJSON(WB.url('websites/templates'), { category: cat }).done(function (res) {
      if (!res.templates.length) { $box.html('<div class="col-12"><div class="alert alert-warning mb-0">This category has no active templates yet.</div></div>'); return; }
      $box.empty();
      res.templates.forEach(function (t) {
        const img = t.preview_url ? '<img src="' + WB.esc(t.preview_url) + '" class="template-preview" alt="">' : '<div class="template-preview d-grid" style="place-items:center"><i class="bi bi-image fs-2 text-muted"></i></div>';
        const $c = $('<div class="col-sm-6 col-lg-4"><div class="card h-100 template-choice" role="button" tabindex="0">' + img +
          '<div class="card-body"><div class="fw-semibold">' + WB.esc(t.name) + '</div><small class="text-muted d-block mb-2">' + WB.esc((t.description || '').slice(0, 120)) + '</small>' +
          '<a class="small" href="' + WB.esc(t.preview_link) + '" target="_blank" rel="noopener"><i class="bi bi-eye"></i> Live preview</a></div></div></div>');
        $c.find('.template-choice').on('click keypress', function (e) {
          if ($(e.target).is('a')) return;
          $box.find('.template-choice').removeClass('border-primary shadow-sm selected');
          $(this).addClass('border-primary shadow-sm selected');
          $form.find('[name=template_id]').val(t.id).removeClass('is-invalid');
          $form.find('[name=template_id]').siblings('.wb-err').remove();
        });
        $box.append($c);
      });
      if (res.templates.length === 1) $box.find('.template-choice').first().trigger('click');
    });
  });
});
