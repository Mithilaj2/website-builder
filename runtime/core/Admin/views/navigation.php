<p class="text-muted small">Choose which links appear in your website's header menu and footer. While a menu is set to <strong>Automatic</strong>, it shows your active pages marked "Show in menu" / "Show in footer", in page order.</p>
<div class="row g-3">
  <?php foreach (['header' => ['Header menu', 'list-nested'], 'footer' => ['Footer links', 'layout-text-window']] as $loc => [$label, $icon]): ?>
  <div class="col-xl-6">
    <div class="card nav-editor" data-location="<?= $loc ?>">
      <div class="card-header d-flex align-items-center gap-2"><i class="bi bi-<?= $icon ?>"></i><?= e($label) ?><span class="badge text-bg-light border ms-1 mode-badge"></span></div>
      <ul class="list-group list-group-flush nav-items"></ul>
      <div class="card-body border-top">
        <div class="row g-2 align-items-end">
          <div class="col-sm-8"><label class="form-label small">Add a page</label><select class="form-select form-select-sm page-select"></select></div>
          <div class="col-sm-4"><button class="btn btn-sm btn-outline-primary w-100" type="button" data-add-page>Add page</button></div>
        </div>
        <div class="row g-2 align-items-end mt-1">
          <div class="col-sm-4"><label class="form-label small">Custom link label</label><input class="form-control form-control-sm link-label" maxlength="60" placeholder="e.g. Our blog"></div>
          <div class="col-sm-5"><label class="form-label small">Address</label><input class="form-control form-control-sm link-url" maxlength="500" placeholder="https://… / #section / tel:…"></div>
          <div class="col-sm-3"><button class="btn btn-sm btn-outline-primary w-100" type="button" data-add-link>Add link</button></div>
        </div>
      </div>
      <div class="card-footer bg-white d-flex flex-wrap gap-2">
        <button class="btn btn-primary btn-sm" type="button" data-save>Save menu</button>
        <button class="btn btn-light btn-sm" type="button" data-automatic>Use automatic menu</button>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>
