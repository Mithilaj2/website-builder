<?php
$features = json_decode((string) $tpl['features'], true) ?: ['features' => [], 'optional' => []];
$manage = can('templates.manage');
?>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a href="<?= e(url('templates')) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>All templates</a>
  <code class="ms-1"><?= e($tpl['code']) ?></code>
  <span class="badge text-bg-light border">v<?= e($tpl['current_version']) ?></span>
  <?= status_badge($tpl['status']) ?>
  <?php if ($manage): ?>
    <div class="ms-auto d-flex flex-wrap gap-2">
      <button class="btn btn-outline-primary btn-sm" id="btnEditTpl" data-id="<?= (int) $tpl['id'] ?>"><i class="bi bi-pencil me-1"></i>Edit</button>
      <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#versionModal"><i class="bi bi-cloud-upload me-1"></i>New version</button>
      <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#duplicateModal"><i class="bi bi-copy me-1"></i>Duplicate</button>
      <button class="btn btn-outline-secondary btn-sm" data-post="<?= e(url('templates/' . $tpl['id'] . '/status')) ?>" data-reload-page="1" data-report-target="#validationCard"><?= $tpl['status'] === 'active' ? 'Deactivate' : 'Activate' ?></button>
      <?php if ($websites === 0): ?>
        <button class="btn btn-outline-danger btn-sm" data-post="<?= e(url('templates/' . $tpl['id'] . '/delete?from=show')) ?>" data-confirm="Delete this template? Its folder is archived to storage/backups before removal." data-confirm-text="Delete"><i class="bi bi-trash me-1"></i>Delete</button>
      <?php endif; ?>
    </div>
  <?php endif; ?>
</div>

<div class="row g-3">
  <div class="col-lg-5">
    <div class="card mb-3">
      <?php if ($tpl['preview_image']): ?>
        <img src="<?= e(asset($tpl['preview_image'])) ?>" class="template-preview" alt="<?= e($tpl['name']) ?> preview">
      <?php else: ?>
        <div class="template-preview d-grid" style="place-items:center"><span class="text-muted"><i class="bi bi-image fs-1"></i></span></div>
      <?php endif; ?>
      <div class="card-body">
        <h2 class="h5 mb-1"><?= e($tpl['name']) ?></h2>
        <p class="text-muted small mb-3"><?= e($tpl['category_name']) ?> · used by <?= (int) $websites ?> website(s)</p>
        <p style="white-space:pre-line"><?= e($tpl['description'] ?: 'No description.') ?></p>
        <dl class="row small mb-0">
          <dt class="col-5 text-muted">Folder</dt><dd class="col-7"><code><?= e($tpl['folder_path']) ?></code></dd>
          <dt class="col-5 text-muted">Features</dt><dd class="col-7"><?= e(implode(', ', $features['features'] ?? []) ?: '—') ?></dd>
          <dt class="col-5 text-muted">Optional features</dt><dd class="col-7"><?= e(implode(', ', $features['optional'] ?? []) ?: '—') ?></dd>
          <dt class="col-5 text-muted">Last updated</dt><dd class="col-7"><?= e(format_date($tpl['updated_at'], 'd M Y H:i')) ?></dd>
        </dl>
      </div>
    </div>

    <div class="card mb-3" id="validationCard">
      <div class="card-header d-flex align-items-center">Validation &amp; security scan
        <?php if ($manage && $folderExists): ?><button class="btn btn-sm btn-light ms-auto" id="btnRevalidate" data-url="<?= e(url('templates/' . $tpl['id'] . '/validate')) ?>"><i class="bi bi-arrow-repeat"></i> Re-run</button><?php endif; ?>
      </div>
      <div class="card-body report" data-initial='<?= e(json_encode($report)) ?>'></div>
    </div>

    <div class="card">
      <div class="card-header">Live preview</div>
      <div class="card-body small text-muted">Live rendering of the template with its demo content becomes available with the template engine in Phase 2. Until then, use the preview image and screenshots.</div>
    </div>
  </div>

  <div class="col-lg-7">
    <div class="card mb-3">
      <div class="card-header d-flex align-items-center">Screenshots (<?= count($screens) ?>)
        <?php if ($manage): ?><button class="btn btn-sm btn-primary ms-auto" data-bs-toggle="modal" data-bs-target="#screensModal"><i class="bi bi-plus-lg"></i> Add</button><?php endif; ?>
      </div>
      <div class="card-body">
        <?php if (!$screens): ?><p class="text-muted small mb-0">No screenshots yet.</p><?php endif; ?>
        <div class="row g-3 screens">
          <?php foreach ($screens as $s): ?>
            <div class="col-6 col-md-4">
              <a href="<?= e(asset($s['image'])) ?>" target="_blank" rel="noopener"><img src="<?= e(asset($s['image'])) ?>" alt="<?= e($s['caption'] ?: 'Screenshot') ?>"></a>
              <div class="d-flex align-items-center mt-1 small"><span class="text-truncate"><?= e($s['caption'] ?: '') ?></span>
                <?php if ($manage): ?><button class="btn btn-sm btn-link text-danger ms-auto p-0" data-post="<?= e(url('templates/' . $tpl['id'] . '/screens/' . $s['id'] . '/delete')) ?>" data-confirm="Remove this screenshot?" data-confirm-text="Remove" title="Remove"><i class="bi bi-x-circle"></i></button><?php endif; ?>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>

    <?php if ($manifest): ?>
    <div class="card mb-3">
      <div class="card-header">Pages and sections</div>
      <div class="card-body">
        <div class="row g-3 small">
          <div class="col-md-6">
            <div class="fw-semibold mb-1">Default pages</div>
            <ul class="mb-0 ps-3">
              <?php foreach ((array) ($manifest['default_pages'] ?? []) as $p): ?>
                <li><?= e($p['title'] ?? '') ?> <code>/<?= e($p['slug'] ?? '') ?></code> <?= !empty($p['is_home']) ? '<span class="badge text-bg-primary">Home</span>' : '' ?></li>
              <?php endforeach; ?>
            </ul>
          </div>
          <div class="col-md-6">
            <div class="fw-semibold mb-1">Section types (<?= count((array) ($manifest['section_types'] ?? [])) ?>)</div>
            <div class="d-flex flex-wrap gap-1"><?php foreach ((array) ($manifest['section_types'] ?? []) as $s): ?><span class="badge text-bg-light border"><?= e($s) ?></span><?php endforeach; ?></div>
          </div>
        </div>
        <details class="mt-3"><summary class="small">View manifest.json</summary>
          <pre class="manifest-pre mt-2"><?= e(json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?></pre></details>
      </div>
    </div>
    <?php endif; ?>

    <div class="card">
      <div class="card-header">Versions</div>
      <div class="table-responsive"><table class="table mb-0">
        <thead><tr><th>Version</th><th>Changelog</th><th>By</th><th>Date</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($versions as $v): ?>
          <tr>
            <td><strong>v<?= e($v['version']) ?></strong> <?= $v['version'] === $tpl['current_version'] ? '<span class="badge text-bg-success">Current</span>' : '' ?></td>
            <td class="small"><?= e($v['changelog'] ?: '—') ?></td>
            <td class="small"><?= e($v['user'] ?: '—') ?></td>
            <td class="small"><?= e(format_date($v['created_at'], 'd M Y H:i')) ?></td>
            <td class="text-end text-nowrap">
              <?php if ($manage): ?>
                <a class="btn btn-sm btn-light" href="<?= e(url('templates/' . $tpl['id'] . '/versions/' . $v['id'] . '/download')) ?>" title="Download ZIP"><i class="bi bi-download"></i></a>
                <?php if ($v['version'] !== $tpl['current_version']): ?>
                  <button class="btn btn-sm btn-light" data-post="<?= e(url('templates/' . $tpl['id'] . '/versions/' . $v['id'] . '/restore')) ?>" data-confirm="Restore version <?= e($v['version']) ?> as the current version? The current files are replaced (they remain available as their own version)." data-confirm-text="Restore" data-danger="false" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                <?php endif; ?>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$versions): ?><tr><td colspan="5" class="text-muted small">No versions recorded.</td></tr><?php endif; ?>
        </tbody>
      </table></div>
    </div>
  </div>
</div>

<?php if ($manage): ?>
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form data-ajax action="<?= e(url('templates/' . $tpl['id'])) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Edit template</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="alert alert-danger form-alert d-none py-2 small"></div>
        <div class="row g-3">
          <div class="col-md-7"><label class="form-label">Name *</label><input class="form-control" name="name" maxlength="150" required></div>
          <div class="col-md-5"><label class="form-label">Category</label><select class="form-select" name="category_id">
            <?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?></select>
            <div class="form-text">Changing it moves the template folder.</div></div>
          <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="4" maxlength="5000"></textarea></div>
          <div class="col-md-6"><label class="form-label">Preview image</label><input class="form-control" type="file" name="preview_image" accept="image/jpeg,image/png,image/webp" data-preview="#tplPrev"><div class="form-text">JPG, PNG or WEBP up to 8 MB. Recommended 1600×1000.</div></div>
          <div class="col-md-6"><img id="tplPrev" class="img-preview" alt="Preview"></div>
        </div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit">Save</button></div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="duplicateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <form data-ajax action="<?= e(url('templates/' . $tpl['id'] . '/duplicate')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Duplicate template</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="alert alert-danger form-alert d-none py-2 small"></div>
        <p class="small text-muted">Copies all files to a new folder in the same category and registers the copy as an Inactive template at version 1.0.0.</p>
        <div class="mb-3"><label class="form-label">New template id *</label><input class="form-control" name="code" maxlength="80" placeholder="<?= e(preg_replace('/\d+$/', '', $tpl['code'])) ?>02" required></div>
        <div class="mb-3"><label class="form-label">New name *</label><input class="form-control" name="name" maxlength="150" value="<?= e($tpl['name']) ?> (copy)" required></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit" data-busy-text="Copying…">Duplicate</button></div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="versionModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg"><div class="modal-content">
    <form id="versionForm" action="<?= e(url('templates/' . $tpl['id'] . '/new-version')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Upload a new version</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <p class="small text-muted">The ZIP must keep the id <code><?= e($tpl['code']) ?></code> and have a <code>version</code> higher than <strong><?= e($tpl['current_version']) ?></strong>. The current files stay available as a restorable version.</p>
        <div class="mb-3"><input class="form-control" type="file" name="package" accept=".zip,application/zip" required></div>
        <div class="mb-3"><label class="form-label">Changelog</label><textarea class="form-control" name="changelog" rows="3" maxlength="2000" placeholder="What changed in this version?"></textarea></div>
        <div class="report"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button><button class="btn btn-primary" type="submit" data-busy-text="Uploading and validating…">Upload version</button></div>
    </form>
  </div></div>
</div>

<div class="modal fade" id="screensModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content">
    <form data-ajax action="<?= e(url('templates/' . $tpl['id'] . '/screens')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="modal-header"><h5 class="modal-title">Add screenshots</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
      <div class="modal-body">
        <div class="alert alert-danger form-alert d-none py-2 small"></div>
        <div class="mb-3"><label class="form-label">Images</label><input class="form-control" type="file" name="screens[]" accept="image/jpeg,image/png,image/webp" multiple required><div class="form-text">Up to 20 at once; JPG, PNG or WEBP up to 8 MB each.</div></div>
        <div class="mb-3"><label class="form-label">Caption (optional)</label><input class="form-control" name="caption" maxlength="190" placeholder="e.g. Homepage – desktop"></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button class="btn btn-primary" type="submit" data-busy-text="Uploading…">Upload</button></div>
    </form>
  </div></div>
</div>
<?php endif; ?>
