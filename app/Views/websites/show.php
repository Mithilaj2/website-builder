<?php
/** @var array $w */
$fontOptions = '';
foreach ($fonts as $k => $f) {
    $fontOptions .= '<option value="' . e($k) . '">' . e($f['name']) . '</option>';
}
$config = [
    'id' => (int) $w['id'],
    'templateId' => (int) $w['template_id'],
    'editable' => $editable,
    'schemas' => $schemas,
    'mediaFolders' => $mediaFolders,
    'themeDefaults' => $themeDefaults,
    'pageTypes' => $pageTypes,
    'siteCode' => $w['site_code'],
    'step' => $currentStep,
    'status' => $w['status'],
    'urls' => [
        'api' => url('websites/' . $w['id']),
        'media' => url('websites/' . $w['id'] . '/media/'),
        'theme' => url('preview-assets/template/' . $w['template_id'] . '/'),
        'preview_home' => url('websites/' . $w['id'] . '/preview'),
        'preview_page' => url('websites/' . $w['id'] . '/preview') . '?page={slug}',
        'tinymce' => url('assets/vendor/tinymce'),
    ],
];
$themeAction = url('websites/' . $w['id'] . '/settings/theme');
$detailsAction = url('websites/' . $w['id'] . '/settings/details');
?>
<span id="workspaceConfig" hidden data-config='<?= e(json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>'></span>

<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a href="<?= e(url('websites')) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>All websites</a>
  <span class="badge text-bg-light border">#<?= e($w['site_code']) ?></span>
  <?= status_badge($w['status']) ?>
  <span class="small text-muted"><?= e($w['client_name']) ?> · <?= e($w['category_name']) ?> · <?= e($w['template_name']) ?> v<?= e($w['template_version']) ?></span>
  <div class="ms-auto d-flex gap-2">
    <a class="btn btn-outline-primary btn-sm" href="<?= e(url('websites/' . $w['id'] . '/preview')) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>Open preview</a>
    <?php if ($editable): ?>
      <button class="btn btn-outline-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteSiteModal"><i class="bi bi-trash me-1"></i>Delete</button>
    <?php endif; ?>
  </div>
</div>
<?php if (!$editable && can('websites.manage')): ?>
  <div class="alert alert-info small">This website is <?= e(str_replace('_', ' ', $w['status'])) ?>. Its content is managed from the website's own admin panel and can no longer be changed here.</div>
<?php endif; ?>

<?php
$steps = [1 => ['Client & website', 'bi-person-vcard'], 2 => ['Template', 'bi-layout-text-window-reverse'], 3 => ['Pages', 'bi-files'], 4 => ['Sections', 'bi-layout-text-sidebar'],
    5 => ['Media', 'bi-images'], 6 => ['Theme & details', 'bi-palette'], 7 => ['Payments', 'bi-credit-card'], 8 => ['Review & generate', 'bi-rocket-takeoff']];
?>
<ol class="wb-stepper mb-3" role="tablist">
  <?php foreach ($steps as $n => [$label, $icon]): ?>
    <li class="<?= $n <= (int) $w['wizard_step'] ? 'visited' : '' ?>">
      <button type="button" data-bs-toggle="tab" data-bs-target="#step<?= $n ?>" data-step="<?= $n ?>"<?= $n === 5 ? ' data-panel="media"' : ($n === 8 ? ' data-panel="review"' : '') ?> role="tab" aria-controls="step<?= $n ?>">
        <span class="num"><?= $n ?></span><span class="lbl"><?= e($label) ?></span>
      </button>
    </li>
  <?php endforeach; ?>
</ol>

<div class="tab-content">
  <!-- ============================== 1 CLIENT & WEBSITE -->
  <div class="tab-pane fade" id="step1" role="tabpanel">
    <div class="row g-3">
      <div class="col-lg-5">
        <div class="card h-100"><div class="card-header d-flex align-items-center">Client<?php if (can('clients.manage')): ?><a class="btn btn-sm btn-light ms-auto" href="<?= e(url('clients/' . $w['client_id'])) ?>"><i class="bi bi-pencil"></i> Edit client</a><?php endif; ?></div>
          <div class="card-body"><dl class="mb-0">
            <dt class="small text-muted">Business name</dt><dd><?= e($client['business_name']) ?></dd>
            <dt class="small text-muted">Contact person</dt><dd><?= e($client['contact_person'] ?: '—') ?></dd>
            <dt class="small text-muted">Email</dt><dd><?= e($client['email'] ?: '—') ?></dd>
            <dt class="small text-muted">Phone</dt><dd><?= e($client['phone'] ?: '—') ?></dd>
            <dt class="small text-muted">Address</dt><dd class="mb-0" style="white-space:pre-line"><?= e($client['address'] ?: '—') ?></dd>
          </dl></div></div>
      </div>
      <div class="col-lg-7">
        <div class="card h-100"><div class="card-header">Website</div><div class="card-body">
          <form data-ajax action="<?= e(url('websites/' . $w['id'])) ?>" novalidate>
            <?= csrf_field() ?>
            <div class="alert alert-danger form-alert d-none py-2 small"></div>
            <div class="row g-3">
              <div class="col-md-6"><label class="form-label">Website name *</label><input class="form-control" name="name" value="<?= e($w['name']) ?>" maxlength="190" required <?= $editable ? '' : 'disabled' ?>></div>
              <div class="col-md-6"><label class="form-label">Domain (optional)</label><input class="form-control" name="domain" value="<?= e($w['domain']) ?>" maxlength="190" placeholder="example.com" <?= $editable ? '' : 'disabled' ?>></div>
              <div class="col-12"><label class="form-label">Description</label><textarea class="form-control" name="description" rows="3" maxlength="1000" <?= $editable ? '' : 'disabled' ?>><?= e($w['description']) ?></textarea></div>
              <div class="col-md-6"><label class="form-label">Category</label><input class="form-control" value="<?= e($w['category_name']) ?>" disabled></div>
              <div class="col-md-6"><label class="form-label">Website code</label><input class="form-control" value="<?= e($w['site_code']) ?>" disabled></div>
            </div>
            <?php if ($editable): ?><button class="btn btn-primary mt-3" type="submit">Save</button><?php endif; ?>
          </form>
          <dl class="row small text-muted mt-3 mb-0">
            <dt class="col-sm-3">Folder</dt><dd class="col-sm-9"><code><?= e($w['folder_path']) ?></code></dd>
            <dt class="col-sm-3">Database tables</dt><dd class="col-sm-9"><code><?= e($w['table_prefix']) ?>*</code> in the builder database (a separate database on the client's hosting)</dd>
          </dl>
        </div></div>
      </div>
    </div>
  </div>

  <!-- ============================== 2 TEMPLATE -->
  <div class="tab-pane fade" id="step2" role="tabpanel">
    <div class="row g-3" id="templateStep">
      <?php foreach ($templates as $t): $current = (int) $t['id'] === (int) $w['template_id']; ?>
        <div class="col-md-6 col-xl-4">
          <div class="card h-100 <?= $current ? 'border-primary border-2' : '' ?>">
            <?php if ($t['preview_image']): ?><img src="<?= e(asset($t['preview_image'])) ?>" class="template-preview" alt=""><?php else: ?><div class="template-preview d-grid" style="place-items:center"><i class="bi bi-image fs-2 text-muted"></i></div><?php endif; ?>
            <div class="card-body d-flex flex-column">
              <div class="d-flex align-items-center gap-2 mb-1"><span class="fw-semibold"><?= e($t['name']) ?></span><?= $current ? '<span class="badge text-bg-primary">Current</span>' : '' ?></div>
              <small class="text-muted mb-3"><?= e(mb_substr((string) $t['description'], 0, 160)) ?></small>
              <div class="mt-auto d-flex gap-2">
                <a class="btn btn-sm btn-light" href="<?= e(url('templates/' . $t['id'] . '/preview')) ?>" target="_blank" rel="noopener"><i class="bi bi-eye"></i> Live preview</a>
                <?php if (!$current && $editable): ?><button class="btn btn-sm btn-outline-primary ms-auto" data-switch-template="<?= (int) $t['id'] ?>" data-name="<?= e($t['name']) ?>">Switch to this template</button><?php endif; ?>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
      <?php if (count($templates) < 2): ?><div class="col-12 small text-muted">This category has one active template so far. More are added in the template-library phase.</div><?php endif; ?>
    </div>
  </div>

  <!-- ============================== 3 PAGES -->
  <div class="tab-pane fade" id="step3" role="tabpanel">
    <div class="row g-3"><div class="col-xl-8">
        <div class="card">
          <div class="card-header d-flex align-items-center">Pages
            <?php if ($editable): ?><button class="btn btn-primary btn-sm ms-auto" id="btnAddPage"><i class="bi bi-plus-lg"></i> Add page</button><?php endif; ?>
          </div>
          <ul class="list-group list-group-flush" id="pageList"><li class="list-group-item text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></li></ul>
          <?php if ($editable): ?><div class="card-footer bg-white small text-muted"><i class="bi bi-grip-vertical"></i> Drag pages to change the menu order.</div><?php endif; ?>
        </div>
    </div><div class="col-xl-4">
      <div class="card"><div class="card-body small text-muted">
        <p><strong>The template's default pages are already here.</strong> Add or remove pages, rename them, change their URL, choose which appear in the menu, set the homepage and drag to reorder: the menu follows this order.</p>
        <p class="mb-0">Use <em>Edit page &amp; SEO</em> for each page's search-engine title, description and keywords. Inactive pages are not published.</p>
      </div></div>
    </div></div>
  </div>

  <!-- ============================== 4 SECTIONS -->
  <div class="tab-pane fade" id="step4" role="tabpanel">
    <div class="d-flex align-items-center gap-2 mb-3"><label class="form-label m-0" for="sectionsPageSelect">Page</label><select class="form-select w-auto" id="sectionsPageSelect"></select></div>
        <div class="card">
          <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <span id="sectionsTitle">Sections</span>
            <div class="ms-auto d-flex gap-2">
              <button class="btn btn-light btn-sm" id="btnPreviewPage"><i class="bi bi-eye"></i> Preview page</button>
              <?php if ($editable): ?>
                <div class="dropdown">
                  <button class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" id="btnAddSection"><i class="bi bi-plus-lg"></i> Add section</button>
                  <ul class="dropdown-menu dropdown-menu-end" style="max-height:420px;overflow:auto">
                    <?php foreach ($schemas as $type => $s): ?>
                      <li><a class="dropdown-item d-flex gap-2 align-items-start py-2" href="#" data-add-section="<?= e($type) ?>"><i class="bi <?= e($s['icon'] ?? 'bi-square') ?> mt-1"></i><span><span class="fw-semibold d-block"><?= e($s['label']) ?></span><small class="text-muted"><?= e($s['description'] ?? '') ?></small></span></a></li>
                    <?php endforeach; ?>
                  </ul>
                </div>
              <?php endif; ?>
            </div>
          </div>
          <ul class="list-group list-group-flush" id="sectionList"><li class="list-group-item text-muted small py-4 text-center">Select a page.</li></ul>
        </div>
  </div>

  <!-- ============================== 5 MEDIA -->
  <div class="tab-pane fade" id="step5" role="tabpanel">
    <div class="card"><div class="card-body" id="mediaManager"></div></div>
  </div>

  <!-- ============================== 6 THEME & DETAILS -->
  <div class="tab-pane fade" id="step6" role="tabpanel">
    <ul class="nav nav-pills mb-3 gap-1"><li class="nav-item"><button class="nav-link active py-1" data-bs-toggle="pill" data-bs-target="#subTheme" type="button">Theme</button></li><li class="nav-item"><button class="nav-link py-1" data-bs-toggle="pill" data-bs-target="#subDetails" type="button">Site details</button></li></ul>
    <div class="tab-content">
      <div class="tab-pane fade show active" id="subTheme"><?php include RUNTIME_PATH . '/core/editor/theme-form.php'; ?></div>
      <div class="tab-pane fade" id="subDetails"><?php include RUNTIME_PATH . '/core/editor/details-form.php'; ?></div>
    </div>
  </div>

  <!-- ============================== 7 PAYMENTS -->
  <div class="tab-pane fade" id="step7" role="tabpanel">
    <div class="row g-3"><div class="col-xl-7">
      <div class="card"><div class="card-header">Online payment</div><div class="card-body">
        <form id="paymentForm" data-ajax action="<?= e(url('websites/' . $w['id'] . '/payments')) ?>" novalidate>
          <?= csrf_field() ?>
          <div class="alert alert-danger form-alert d-none py-2 small"></div>
          <label class="form-label d-block">Enable online payment?</label>
          <div class="btn-group mb-3" role="group">
            <input type="radio" class="btn-check" name="enabled" id="payNo" value="0" <?= empty($payments['enabled']) ? 'checked' : '' ?> <?= $editable ? '' : 'disabled' ?>><label class="btn btn-outline-primary" for="payNo">No</label>
            <input type="radio" class="btn-check" name="enabled" id="payYes" value="1" <?= !empty($payments['enabled']) ? 'checked' : '' ?> <?= $editable ? '' : 'disabled' ?>><label class="btn btn-outline-primary" for="payYes">Yes</label>
          </div>
          <div id="payFields" class="<?= empty($payments['enabled']) ? 'd-none' : '' ?>">
            <div class="row g-3">
              <?php foreach ($gateways as $k => $g): ?>
                <div class="col-sm-6"><input type="radio" class="btn-check" name="gateway" id="gw_<?= e($k) ?>" value="<?= e($k) ?>" <?= ($payments['gateway'] ?? '') === $k ? 'checked' : '' ?> <?= $editable ? '' : 'disabled' ?>>
                  <label class="btn btn-outline-secondary w-100 text-start gateway-choice" for="gw_<?= e($k) ?>"><span class="fw-semibold d-block"><?= e($g['name']) ?></span><small class="d-block">Needs: <?= e(implode(', ', $g['fields'])) ?></small><?php if ($g['note']): ?><small class="d-block opacity-75 mt-1"><?= e($g['note']) ?></small><?php endif; ?></label></div>
              <?php endforeach; ?>
            </div>
            <label class="form-label mt-3">Mode</label>
            <select class="form-select w-auto" name="mode" <?= $editable ? '' : 'disabled' ?>><option value="test" <?= ($payments['mode'] ?? 'test') === 'test' ? 'selected' : '' ?>>Test mode</option><option value="live" <?= ($payments['mode'] ?? '') === 'live' ? 'selected' : '' ?>>Live mode</option></select>
          </div>
          <?php if ($editable): ?><div class="mt-3"><button class="btn btn-primary" type="submit">Save payment settings</button></div><?php endif; ?>
        </form>
      </div></div>
    </div><div class="col-xl-5">
      <div class="card"><div class="card-body small">
        <p class="fw-semibold mb-2"><i class="bi bi-shield-lock me-1"></i>How gateway credentials are handled</p>
        <ul class="mb-0 text-muted">
          <li>The builder records the gateway and mode only. API keys and secrets are <strong>never</strong> written into website files or exports.</li>
          <li>After deployment, the website owner enters the keys in the website's own admin panel, where they are stored encrypted with that website's key.</li>
          <li>The payment flow itself (checkout, server-side verification, webhooks, success and failure pages) is built in <strong>Phase 5</strong>, starting with Razorpay. No gateway is marked as working until a real test payment has been verified.</li>
        </ul>
      </div></div>
    </div></div>
  </div>

  <!-- ============================== 8 REVIEW & GENERATE -->
  <div class="tab-pane fade" id="step8" role="tabpanel">
    <div class="row g-3">
      <div class="col-xl-5">
        <div class="card mb-3" id="generateCard">
          <div class="card-header d-flex align-items-center">Generate website <span class="ms-auto"><?= status_badge($w['status']) ?></span></div>
          <div class="card-body">
            <p class="small text-muted">Builds the complete, independent website in <code><?= e($w['folder_path']) ?></code>: website engine, template, fonts, configuration, then checks every page over HTTP. Uploads are never touched; on regeneration the previous version is kept as a backup and restored automatically if the checks fail.</p>
            <?php if (can('websites.manage') && in_array($w['status'], ['draft', 'config_pending', 'generated', 'testing', 'ready', 'failed'], true)): ?>
              <button class="btn btn-primary" id="btnGenerate" data-busy-text="Starting…"><i class="bi bi-rocket-takeoff me-1"></i><?= in_array($w['status'], ['generated', 'testing', 'ready'], true) ? 'Regenerate website' : 'Generate website' ?></button>
            <?php elseif ($w['status'] === 'deployed'): ?>
              <div class="alert alert-info small mb-0">This website is deployed. Code updates are delivered as update packages (Phase 6).</div>
            <?php endif; ?>
            <div id="genProgress" class="mt-3 d-none">
              <div class="progress mb-2" role="progressbar" aria-label="Generation progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div></div>
              <ol class="gen-steps list-unstyled small mb-0"></ol>
              <div class="gen-actions mt-2"></div>
            </div>
          </div>
        </div>
        <div class="card mb-3">
          <div class="card-header d-flex align-items-center">Checklist <button class="btn btn-sm btn-light ms-auto" id="checklistReload" title="Re-check"><i class="bi bi-arrow-clockwise"></i></button></div>
          <ul class="list-group list-group-flush" id="checklist"><li class="list-group-item text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></li></ul>
        </div>
        <div class="card mb-3">
          <div class="card-header">Status</div>
          <div class="card-body">
            <div class="small text-muted mb-2">After generation: test the website, mark it ready, then deployed. A deployed website's content is managed from its own admin panel.</div>
            <div class="d-flex flex-wrap gap-2">
              <?php
              $actions = [
                  'testing' => ['Mark as Testing', ['generated', 'ready'], 'outline-primary'],
                  'ready' => ['Ready for deployment', ['generated', 'testing'], 'outline-primary'],
                  'deployed' => ['Mark as Deployed', ['ready'], 'success'],
                  'archived' => ['Archive', ['draft', 'config_pending', 'generated', 'testing', 'ready', 'deployed', 'failed'], 'outline-secondary'],
                  'restore' => ['Restore from archive', ['archived'], 'outline-primary'],
              ];
              $shown = 0;
              foreach ($actions as $to => [$label, $from, $cls]):
                  if (!in_array($w['status'], $from, true) || !can('websites.manage')) { continue; } $shown++; ?>
                <button class="btn btn-sm btn-<?= $cls ?>" data-post="<?= e(url('websites/' . $w['id'] . '/status?status=' . $to)) ?>" data-confirm="<?= e($to === 'deployed' ? 'Mark this website as deployed? Its content will be locked in the builder.' : 'Change the status to “' . $label . '”?') ?>" data-danger="false" data-confirm-text="<?= e($label) ?>"><?= e($label) ?></button>
              <?php endforeach; ?>
              <?php if (!$shown): ?><span class="small text-muted">Generate the website first.</span><?php endif; ?>
            </div>
          </div>
        </div>
        <div class="card mb-3" id="adminAccountCard">
          <div class="card-header d-flex align-items-center"><i class="bi bi-person-lock me-2"></i>Website admin</div>
          <div class="card-body">
            <p class="small text-muted mb-2">The client manages pages, media, menus and enquiries in the website's own admin panel. Passwords are shown once and are never stored by the builder.</p>
            <div class="small mb-2" id="adminUrl"></div>
            <ul class="list-unstyled small mb-3" id="adminUsers"></ul>
            <?php if (can('websites.manage')): ?>
            <form id="adminAccountForm" action="<?= e(url('websites/' . $w['id'] . '/admin-account')) ?>" novalidate autocomplete="off">
              <div class="alert alert-danger form-alert d-none py-2 small"></div>
              <div class="row g-2">
                <div class="col-sm-5"><input class="form-control form-control-sm" name="name" maxlength="120" placeholder="Name (optional)" aria-label="Owner name"></div>
                <div class="col-sm-7"><input class="form-control form-control-sm" type="email" name="email" maxlength="190" placeholder="owner@client.com" aria-label="Owner email" required></div>
              </div>
              <button class="btn btn-outline-primary btn-sm mt-2" type="submit" data-busy-text="Creating…"><i class="bi bi-key me-1"></i><span>Create owner login / set new password</span></button>
            </form>
            <div class="alert alert-success small mt-3 mb-0 d-none" id="adminCredentials" role="status"></div>
            <?php endif; ?>
          </div>
        </div>
        <div class="card">
          <div class="card-header">Generation history</div>
          <div id="genHistory" class="small"></div>
        </div>
      </div>
      <div class="col-xl-7">
        <div class="card">
          <div class="card-header d-flex flex-wrap align-items-center gap-2">
            <select class="form-select form-select-sm w-auto" id="previewPage" aria-label="Page to preview"></select>
            <div class="btn-group btn-group-sm" role="group" aria-label="Device">
              <button class="btn btn-outline-secondary active" data-device="100%" title="Desktop"><i class="bi bi-display"></i></button>
              <button class="btn btn-outline-secondary" data-device="820px" title="Tablet"><i class="bi bi-tablet"></i></button>
              <button class="btn btn-outline-secondary" data-device="390px" title="Mobile"><i class="bi bi-phone"></i></button>
            </div>
            <button class="btn btn-light btn-sm" id="previewReload" title="Reload"><i class="bi bi-arrow-clockwise"></i></button>
            <a class="btn btn-light btn-sm ms-auto" id="previewOpen" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right"></i> Open</a>
          </div>
          <div class="preview-stage"><iframe id="previewFrame" title="Website preview" loading="lazy"></iframe></div>
          <div class="card-footer bg-white small" id="qaInfo"></div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="d-flex justify-content-between mt-3 wizard-nav">
  <button class="btn btn-light" id="wizPrev"><i class="bi bi-arrow-left me-1"></i>Back</button>
  <button class="btn btn-primary" id="wizNext">Next<i class="bi bi-arrow-right ms-1"></i></button>
</div>

<?php include RUNTIME_PATH . '/core/editor/editor-modals.php'; ?>

<?php if ($editable): ?>
<div class="modal fade" id="deleteSiteModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content">
  <form data-ajax action="<?= e(url('websites/' . $w['id'] . '/delete')) ?>" novalidate>
    <?= csrf_field() ?>
    <div class="modal-header"><h5 class="modal-title text-danger">Delete website</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <div class="alert alert-danger form-alert d-none py-2 small"></div>
      <p>This removes the website's pages, sections, media and settings. A full backup (data + files) is saved to <code>storage/backups/websites</code> first.</p>
      <label class="form-label">Type <strong><?= e($w['site_code']) ?></strong> to confirm</label>
      <input class="form-control" name="confirm" autocomplete="off" required>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-danger">Delete website</button></div>
  </form>
</div></div></div>
<?php endif; ?>
