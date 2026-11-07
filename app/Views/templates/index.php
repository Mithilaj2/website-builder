<div class="card">
  <div class="card-header d-flex flex-wrap align-items-center gap-2">
    <form class="filters d-flex flex-wrap gap-2" id="tplFilters" role="search">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Search name, id, description" aria-label="Search templates">
      <select name="category" class="form-select form-select-sm" aria-label="Category">
        <option value="">All categories</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= (int) $c['id'] ?>" <?= $selectedCategory === (int) $c['id'] ? 'selected' : '' ?>><?= e($c['name']) ?></option>
        <?php endforeach; ?>
      </select>
      <select name="status" class="form-select form-select-sm" aria-label="Status">
        <option value="">All statuses</option><option value="active">Active</option><option value="inactive">Inactive</option>
      </select>
    </form>
    <?php if (can('templates.manage')): ?>
      <div class="ms-auto d-flex gap-2">
        <button class="btn btn-outline-primary btn-sm" id="btnScan"><i class="bi bi-folder2-open me-1"></i>Scan folders</button>
        <button class="btn btn-primary btn-sm" id="btnUpload"><i class="bi bi-upload me-1"></i>Upload template</button>
      </div>
    <?php endif; ?>
  </div>
  <div id="tplTable"></div>
</div>

<?php if (can('templates.manage')): ?>
<div class="modal fade" id="uploadModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form id="uploadForm" action="<?= e(url('templates/upload')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Upload a new template</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <p class="small text-muted">Upload a ZIP of the template folder. It must contain <code>manifest.json</code> and follow the template guide (layouts, partials, sections, assets). The template is validated and security-scanned, then registered as <strong>Inactive</strong>.</p>
        <input class="form-control" type="file" name="package" accept=".zip,application/zip" required>
        <div class="report mt-3"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button type="submit" class="btn btn-primary" data-busy-text="Uploading and validating…">Upload</button></div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="scanModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Unregistered template folders</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <p class="small text-muted">Template folders copied to <code>templates/&lt;category&gt;/&lt;template_id&gt;/</code> through cPanel File Manager or FTP appear here until registered.</p>
      <div class="scan-results"></div>
    </div>
  </div></div>
</div>
<?php endif; ?>
