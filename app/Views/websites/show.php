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
];
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
      <button type="button" data-bs-toggle="tab" data-bs-target="#step<?= $n ?>" data-step="<?= $n ?>" role="tab" aria-controls="step<?= $n ?>">
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
      <div class="tab-pane fade show active" id="subTheme"><form id="themeForm" action="<?= e(url('websites/' . $w['id'] . '/settings/theme')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="row g-3">
        <div class="col-xl-7">
          <div class="card mb-3"><div class="card-header">Colours</div><div class="card-body"><div class="row g-3">
            <?php foreach (['primary' => 'Primary colour', 'secondary' => 'Accent colour', 'bg' => 'Page background', 'text' => 'Text colour'] as $k => $label): ?>
              <div class="col-sm-6"><label class="form-label"><?= e($label) ?></label>
                <div class="input-group"><input type="color" class="form-control form-control-color" data-color-for="<?= e($k) ?>" aria-label="<?= e($label) ?> picker"><input class="form-control" name="<?= e($k) ?>" maxlength="7" pattern="#[0-9A-Fa-f]{6}"></div></div>
            <?php endforeach; ?>
          </div></div></div>
          <div class="card mb-3"><div class="card-header">Typography &amp; shape</div><div class="card-body"><div class="row g-3">
            <div class="col-sm-6"><label class="form-label">Heading font</label><select class="form-select" name="heading_font"><?= $fontOptions ?></select></div>
            <div class="col-sm-6"><label class="form-label">Body font</label><select class="form-select" name="body_font"><?= $fontOptions ?></select></div>
            <div class="col-sm-6"><label class="form-label">Button style</label><select class="form-select" name="button_style"><?php foreach (\SiteRuntime\Theme::BUTTON_STYLES as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
            <div class="col-sm-6"><label class="form-label">Corner rounding</label><select class="form-select" name="radius"><?php foreach (\SiteRuntime\Theme::RADII as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
            <div class="col-sm-6"><label class="form-label">Header style</label><select class="form-select" name="header_style"><?php foreach ($headerStyles as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
            <div class="col-sm-6"><label class="form-label">Footer style</label><select class="form-select" name="footer_style"><?php foreach ($footerStyles as $k => $l): ?><option value="<?= e($k) ?>"><?= e($l) ?></option><?php endforeach; ?></select></div>
          </div></div></div>
        </div>
        <div class="col-xl-5">
          <div class="card mb-3"><div class="card-header">Logo &amp; favicon</div><div class="card-body">
            <div class="mb-3"><label class="form-label">Logo</label><div class="wb-image-field" data-name="logo"></div><div class="form-text">Shown in the header and footer. Without a logo, the website name is shown.</div></div>
            <div><label class="form-label">Favicon</label><div class="wb-image-field" data-name="favicon"></div><div class="form-text">Square image, at least 64×64 px (PNG recommended).</div></div>
          </div></div>
          <div class="card"><div class="card-body d-flex flex-wrap gap-2">
            <?php if ($editable): ?>
              <button type="submit" class="btn btn-primary">Save theme</button>
              <button type="button" class="btn btn-light" id="btnThemeDefaults">Reset to template defaults</button>
            <?php endif; ?>
            <div class="w-100 small text-muted mt-1">Changes appear in the Preview tab after saving.</div>
          </div></div>
        </div>
      </div>
    </form></div>
      <div class="tab-pane fade" id="subDetails"><form id="detailsForm" data-ajax action="<?= e(url('websites/' . $w['id'] . '/settings/details')) ?>" novalidate>
      <?= csrf_field() ?>
      <div class="alert alert-danger form-alert d-none py-2 small"></div>
      <div class="row g-3">
        <div class="col-xl-6">
          <div class="card mb-3"><div class="card-header">Business &amp; contact</div><div class="card-body"><div class="row g-3">
            <div class="col-md-6"><label class="form-label">Name shown on the website *</label><input class="form-control" name="name" maxlength="120" required></div>
            <div class="col-md-6"><label class="form-label">Tagline</label><input class="form-control" name="tagline" maxlength="150"></div>
            <div class="col-md-6"><label class="form-label">Email</label><input class="form-control" type="email" name="email" maxlength="190"></div>
            <div class="col-md-6"><label class="form-label">Phone</label><input class="form-control" name="phone" maxlength="20"></div>
            <div class="col-md-6"><label class="form-label">WhatsApp number</label><input class="form-control" name="whatsapp" maxlength="20" placeholder="+91…"></div>
            <div class="col-md-6"><label class="form-label">Working hours</label><input class="form-control" name="hours" maxlength="120" placeholder="Mon–Sat: 9 am – 6 pm"></div>
            <div class="col-12"><label class="form-label">Address</label><textarea class="form-control" name="address" rows="2" maxlength="400"></textarea></div>
          </div></div></div>
          <div class="card mb-3"><div class="card-header">Header button</div><div class="card-body"><div class="row g-3">
            <div class="col-md-5"><label class="form-label">Text</label><input class="form-control" name="header_button_text" maxlength="40" placeholder="Leave empty to hide"></div>
            <div class="col-md-7"><label class="form-label">Link</label><input class="form-control" name="header_button_url" maxlength="500" list="pageLinkList" placeholder="page:contact"></div>
          </div></div></div>
        </div>
        <div class="col-xl-6">
          <div class="card mb-3"><div class="card-header">Footer</div><div class="card-body"><div class="row g-3">
            <div class="col-12"><label class="form-label">About text</label><textarea class="form-control" name="footer_about" rows="2" maxlength="400"></textarea></div>
            <div class="col-12"><label class="form-label">Copyright text</label><input class="form-control" name="footer_text" maxlength="200" placeholder="All rights reserved."></div>
          </div></div></div>
          <div class="card mb-3"><div class="card-header">Social media</div><div class="card-body"><div class="row g-3">
            <?php foreach (['facebook' => 'Facebook', 'instagram' => 'Instagram', 'linkedin' => 'LinkedIn', 'twitter' => 'X (Twitter)', 'youtube' => 'YouTube'] as $k => $l): ?>
              <div class="col-md-6"><label class="form-label"><i class="bi bi-<?= $k === 'twitter' ? 'twitter-x' : e($k) ?> me-1"></i><?= e($l) ?></label><input class="form-control" name="social_<?= e($k) ?>" maxlength="200" placeholder="https://"></div>
            <?php endforeach; ?>
          </div></div></div>
          <div class="card mb-3"><div class="card-header">Default SEO</div><div class="card-body"><div class="row g-3">
            <div class="col-12"><label class="form-label">Default meta description</label><textarea class="form-control" name="seo_description" rows="2" maxlength="320"></textarea><div class="form-text">Used on pages without their own SEO description.</div></div>
            <div class="col-12"><label class="form-label">Default keywords</label><input class="form-control" name="seo_keywords" maxlength="255"></div>
          </div></div></div>
          <?php if ($editable): ?><button class="btn btn-primary" type="submit">Save site details</button><?php endif; ?>
        </div>
      </div>
    </form></div>
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

<datalist id="pageLinkList"></datalist>
<datalist id="iconList"><?php foreach (['bi-briefcase','bi-graph-up-arrow','bi-people','bi-compass','bi-gear-wide-connected','bi-globe2','bi-cpu','bi-award','bi-shield-check','bi-clock-history','bi-check2-circle','bi-star','bi-heart-pulse','bi-hospital','bi-mortarboard','bi-book','bi-house-door','bi-building','bi-cart3','bi-truck','bi-airplane','bi-cup-hot','bi-camera','bi-telephone','bi-envelope','bi-geo-alt','bi-lightning-charge','bi-tools','bi-bullseye','bi-receipt','bi-person-workspace','bi-shield-lock','bi-chat-dots','bi-hand-thumbs-up','bi-trophy','bi-calendar-check','bi-wallet2','bi-bar-chart','bi-lightbulb','bi-leaf'] as $i): ?><option value="<?= e($i) ?>"><?php endforeach; ?></datalist>

<!-- Page modal -->
<div class="modal fade" id="pageModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog modal-lg"><div class="modal-content">
  <form data-ajax novalidate>
    <?= csrf_field() ?>
    <div class="modal-header"><h5 class="modal-title">Page</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
    <div class="modal-body">
      <div class="alert alert-danger form-alert d-none py-2 small"></div>
      <div class="row g-3">
        <div class="col-md-6"><label class="form-label">Page title *</label><input class="form-control" name="title" maxlength="150" required></div>
        <div class="col-md-6"><label class="form-label">URL</label><div class="input-group"><span class="input-group-text">/</span><input class="form-control" name="slug" maxlength="120" placeholder="auto from title"></div><div class="form-text slug-help"></div></div>
        <div class="col-md-6"><label class="form-label">Page layout</label><select class="form-select" name="page_type"><?php foreach ($pageTypes as $t): ?><option value="<?= e($t) ?>"><?= e(ucwords(str_replace('_', ' ', $t))) ?></option><?php endforeach; ?></select><div class="form-text type-help">New pages start with this layout's default sections.</div></div>
        <div class="col-md-6 d-flex flex-column justify-content-end gap-1">
          <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_in_menu" value="1" id="pgMenu"><label class="form-check-label" for="pgMenu">Show in main menu</label></div>
          <div class="form-check form-switch"><input class="form-check-input" type="checkbox" name="show_in_footer" value="1" id="pgFooter"><label class="form-check-label" for="pgFooter">Show in footer links</label></div>
        </div>
        <div class="col-12"><hr class="my-1"><div class="fw-semibold">Search engine (SEO)</div></div>
        <div class="col-12"><label class="form-label d-flex">SEO title <small class="ms-auto text-muted" data-count-for="seo_title"></small></label><input class="form-control" name="seo_title" maxlength="190" placeholder="Defaults to “Page title | Website name”"></div>
        <div class="col-12"><label class="form-label d-flex">Meta description <small class="ms-auto text-muted" data-count-for="seo_description"></small></label><textarea class="form-control" name="seo_description" rows="2" maxlength="320" placeholder="150–160 characters works best"></textarea></div>
        <div class="col-12"><label class="form-label">Keywords</label><input class="form-control" name="seo_keywords" maxlength="255" placeholder="comma, separated, keywords"></div>
      </div>
    </div>
    <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button><button type="submit" class="btn btn-primary">Save page</button></div>
  </form>
</div></div></div>

<!-- Section editor -->
<div class="modal fade" id="sectionModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static"><div class="modal-dialog modal-fullscreen-lg-down" style="max-width:min(1500px,96vw)"><div class="modal-content">
  <div class="modal-header">
    <h5 class="modal-title"><i class="bi section-icon me-2"></i><span class="section-title">Edit section</span></h5>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
  </div>
  <div class="modal-body p-0">
    <div class="row g-0 section-editor">
      <div class="col-lg-5 border-end section-editor-form">
        <ul class="nav nav-pills px-3 pt-3 gap-1" role="tablist">
          <li class="nav-item"><button class="nav-link active py-1" data-bs-toggle="pill" data-bs-target="#secContent" type="button">Content</button></li>
          <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="pill" data-bs-target="#secStyle" type="button">Style</button></li>
        </ul>
        <div class="tab-content p-3">
          <div class="alert alert-danger form-alert d-none py-2 small"></div>
          <div class="tab-pane fade show active" id="secContent"><div class="mb-3"><label class="form-label">Internal label (optional)</label><input class="form-control form-control-sm" id="secLabel" maxlength="120" placeholder="Only shown in the builder"></div><div id="secFields"></div></div>
          <div class="tab-pane fade" id="secStyle">
            <div class="row g-3">
              <div class="col-6"><label class="form-label">Background colour</label><div class="input-group input-group-sm"><input type="color" class="form-control form-control-color" data-style-color><input class="form-control" data-style="bg_color" placeholder="Template default"></div></div>
              <div class="col-6"><label class="form-label">Spacing</label><select class="form-select form-select-sm" data-style="padding"><option value="md">Normal</option><option value="sm">Compact</option><option value="lg">Spacious</option><option value="none">None</option></select></div>
              <div class="col-12"><label class="form-label">Background image</label><div class="wb-image-field" data-style-image="bg_image"></div></div>
              <div class="col-6"><label class="form-label">Dark overlay on image</label><input type="range" class="form-range" min="0" max="90" step="5" data-style="overlay"><div class="form-text"><span data-overlay-value>0</span>%</div></div>
              <div class="col-6"><label class="form-label">Text colour</label><select class="form-select form-select-sm" data-style="tone"><option value="auto">Template default</option><option value="light">Light text (for dark backgrounds)</option><option value="dark">Dark text</option></select></div>
              <div class="col-12"><label class="form-label">Anchor name (for #links)</label><input class="form-control form-control-sm" data-style="anchor" maxlength="40" placeholder="e.g. services"><div class="form-text">Link to this section from a button with “#services”.</div></div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-lg-7 section-editor-preview d-none d-lg-flex flex-column">
        <div class="px-3 py-2 border-bottom small text-muted d-flex align-items-center"><i class="bi bi-eye me-2"></i>Live preview (updates when you save)
          <div class="btn-group btn-group-sm ms-auto"><button class="btn btn-outline-secondary active" data-sec-device="100%"><i class="bi bi-display"></i></button><button class="btn btn-outline-secondary" data-sec-device="390px"><i class="bi bi-phone"></i></button></div></div>
        <div class="preview-stage flex-grow-1"><iframe id="sectionPreview" title="Section preview"></iframe></div>
      </div>
    </div>
  </div>
  <div class="modal-footer">
    <?php if ($editable): ?>
      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
      <button type="button" class="btn btn-outline-primary" id="secSave" data-busy-text="Saving…">Save &amp; preview</button>
      <button type="button" class="btn btn-primary" id="secSaveClose" data-busy-text="Saving…">Save &amp; close</button>
    <?php else: ?>
      <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
    <?php endif; ?>
  </div>
</div></div></div>

<!-- Media picker -->
<div class="modal fade" id="mediaPicker" tabindex="-1" aria-hidden="true" style="z-index:1065"><div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
  <div class="modal-header"><h5 class="modal-title">Choose an image</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
  <div class="modal-body" id="mediaPickerBody"></div>
</div></div></div>

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
