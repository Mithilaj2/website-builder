<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center gap-2">
    <form class="filters d-flex flex-wrap gap-2" id="siteFilters" role="search">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Search name, client, domain, code" aria-label="Search websites">
      <select name="status" class="form-select form-select-sm" aria-label="Status">
        <option value="">All statuses</option>
        <?php foreach ($statuses as $s): ?><option value="<?= e($s) ?>"><?= e(ucwords(str_replace('_', ' ', $s))) ?></option><?php endforeach; ?>
      </select>
      <?php if ($preselectClient): ?><input type="hidden" name="client" value="<?= (int) $preselectClient ?>"><?php endif; ?>
    </form>
    <?php if (can('websites.manage')): ?>
      <button class="btn btn-primary btn-sm ms-auto" id="btnNewSite"><i class="bi bi-plus-lg me-1"></i>New website</button>
    <?php endif; ?>
  </div>
  <div id="siteTable"></div>
</div>

<?php if (can('websites.manage')): ?>
<div class="modal fade" id="newSiteModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
    <form data-ajax action="<?= e(url('websites')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">New website</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="alert alert-danger form-alert d-none py-2 small"></div>
        <?php if (!$clients): ?>
          <div class="alert alert-warning">Add a client first. <a href="<?= e(url('clients')) ?>">Go to Clients</a></div>
        <?php endif; ?>
        <div class="row g-3">
          <div class="col-md-6"><label class="form-label">Client *</label><select class="form-select" name="client_id" required>
            <option value="">Choose a client…</option>
            <?php foreach ($clients as $c): ?><option value="<?= (int) $c['id'] ?>" <?= $preselectClient === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['business_name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-md-6"><label class="form-label">Website name *</label><input class="form-control" name="name" maxlength="190" required placeholder="e.g. Acme Industries"></div>
          <div class="col-md-6"><label class="form-label">Domain (optional)</label><input class="form-control" name="domain" maxlength="190" placeholder="acme.com"></div>
          <div class="col-md-6"><label class="form-label">Category *</label><select class="form-select" name="category_id" id="newSiteCategory" required>
            <option value="">Choose a category…</option>
            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select></div>
          <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="2" maxlength="1000" placeholder="What the business does (used as the default tagline)"></textarea></div>
          <div class="col-12">
            <label class="form-label">Template *</label>
            <input type="hidden" name="template_id" value="">
            <div id="templateChoices" class="row g-3"><div class="col-12 text-muted small">Choose a category to see its templates.</div></div>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <span class="small text-muted me-auto">The website starts with the template's pages and demo content, which you then customise.</span>
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-primary" data-busy-text="Setting up the website…">Create website</button>
      </div>
    </form>
  </div></div>
</div>
<?php endif; ?>
