<?php
/** @var array $w @var array $counts @var array $payments @var array $checklist @var array $version */
$manage = can('websites.manage');
$isDeployed = $w['status'] === 'deployed';
$canFull = in_array($w['status'], ['generated', 'testing', 'ready'], true) && $generated;
$canUpdate = in_array($w['status'], ['generated', 'testing', 'ready', 'deployed'], true) && $generated;
$outdated = [];
if (($version['runtime'] ?? '') !== '' && version_compare($runtimeLatest, (string) $version['runtime'], '>')) {
    $outdated[] = 'website engine ' . e($version['runtime']) . ' → ' . e($runtimeLatest);
}
if (($version['template_version'] ?? '') !== '' && $templateLatest !== '' && version_compare($templateLatest, (string) $version['template_version'], '>')) {
    $outdated[] = 'template ' . e($version['template_version']) . ' → ' . e($templateLatest);
}
?>
<span id="exportConfig" hidden data-config='<?= e(json_encode(['id' => (int) $w['id'], 'api' => url('websites/' . $w['id'])], JSON_UNESCAPED_SLASHES)) ?>'></span>
<div class="d-flex flex-wrap align-items-center gap-2 mb-3">
  <a href="<?= e(url('websites/' . $w['id'] . '?step=8')) ?>" class="btn btn-light btn-sm"><i class="bi bi-arrow-left me-1"></i>Back to the website</a>
  <span class="badge text-bg-light border">#<?= e($w['site_code']) ?></span>
  <?= status_badge($w['status']) ?>
  <span class="small text-muted"><?= e($w['name']) ?> · engine <?= e($version['runtime'] ?? '—') ?> · template <?= e($version['template_version'] ?? '—') ?></span>
</div>

<?php if (!$generated): ?>
  <div class="alert alert-warning">Generate the website first (step 8), then export it here.</div>
<?php endif; ?>
<?php if ($outdated): ?>
  <div class="alert alert-warning small"><i class="bi bi-arrow-repeat me-1"></i>Newer versions are available (<?= implode(', ', $outdated) ?>). <a href="<?= e(url('websites/' . $w['id'] . '?step=8')) ?>">Regenerate the website</a> first if you want them in the package.</div>
<?php endif; ?>

<div class="row g-3">
  <div class="col-xxl-8">
    <div class="card mb-3"><div class="card-header">New package</div><div class="card-body">
      <?php if ($manage): ?>
      <form id="exportForm" novalidate>
        <div class="alert alert-danger form-alert d-none py-2 small"></div>
        <div class="row g-2 mb-3">
          <div class="col-md-6"><input type="radio" class="btn-check" name="type" id="typeFull" value="full" <?= $canFull ? 'checked' : 'disabled' ?>>
            <label class="btn btn-outline-primary w-100 text-start h-100" for="typeFull"><span class="fw-semibold d-block"><i class="bi bi-box-seam me-1"></i>Full package</span>
              <small class="d-block">Website + images + database + installer, for the first upload to the client’s hosting.</small>
              <?php if ($isDeployed): ?><small class="d-block text-danger mt-1">Not available: the website is deployed and its content now lives on the client’s hosting.</small><?php endif; ?></label></div>
          <div class="col-md-6"><input type="radio" class="btn-check" name="type" id="typeUpdate" value="update" <?= !$canFull && $canUpdate ? 'checked' : '' ?> <?= $canUpdate ? '' : 'disabled' ?>>
            <label class="btn btn-outline-primary w-100 text-start h-100" for="typeUpdate"><span class="fw-semibold d-block"><i class="bi bi-arrow-up-circle me-1"></i>Update package</span>
              <small class="d-block">Code only (engine, template, libraries). Never touches the client’s content, images, settings or logins.</small></label></div>
        </div>
        <div id="fullOptions">
          <div class="row g-3 align-items-start">
            <div class="col-md-5"><label class="form-label">Table prefix</label><input class="form-control font-monospace" name="prefix" value="<?= e($prefix) ?>" maxlength="16">
              <div class="form-text">The installer lets the client change it.</div></div>
            <div class="col-md-7 pt-md-4">
              <div class="form-check"><input class="form-check-input" type="checkbox" name="include_enquiries" value="1" id="incEnq"><label class="form-check-label" for="incEnq">Include enquiries (<?= (int) $counts['enquiries'] ?>)</label></div>
              <div class="form-check"><input class="form-check-input" type="checkbox" name="include_payments" value="1" id="incPay" <?= $counts['live_payments'] ? '' : 'disabled' ?>><label class="form-check-label" for="incPay">Include live payments (<?= (int) $counts['live_payments'] ?>)</label></div>
              <div class="form-text">Test payments (<?= (int) $counts['test_payments'] ?>), administrator logins and saved passwords/keys are never exported.</div>
            </div>
          </div>
        </div>
        <button class="btn btn-primary mt-3" type="submit" id="btnExport" data-busy-text="Starting…" <?= $canFull || $canUpdate ? '' : 'disabled' ?>><i class="bi bi-download me-1"></i>Create package</button>
      </form>
      <?php else: ?>
        <p class="text-muted small mb-0">Only users who can manage websites can export them.</p>
      <?php endif; ?>
      <div id="exportProgress" class="mt-3 d-none">
        <div class="progress mb-2" role="progressbar" aria-label="Export progress"><div class="progress-bar progress-bar-striped progress-bar-animated" style="width:0%"></div></div>
        <ol class="gen-steps list-unstyled small mb-2"></ol>
        <div class="export-log small text-muted"></div>
        <div class="export-actions mt-2"></div>
      </div>
    </div></div>
    <div class="card"><div class="card-header d-flex align-items-center">Packages<button class="btn btn-sm btn-light ms-auto" id="historyReload" title="Refresh" aria-label="Refresh"><i class="bi bi-arrow-clockwise"></i></button></div>
  <div class="table-responsive"><table class="table mb-0 align-middle small export-history"><thead><tr><th>Created</th><th>Package</th><th>Installation key</th><th>Status</th><th class="text-end">Download</th></tr></thead>
    <tbody id="exportHistory"><tr><td colspan="5" class="text-center py-3"><div class="spinner-border spinner-border-sm text-primary"></div></td></tr></tbody></table></div>
  <div class="card-footer bg-white small text-muted d-flex flex-wrap gap-2"><span>The 5 newest packages of each website are kept. Packages contain the client’s data: share them only with the client.</span>
    <button class="btn btn-link btn-sm p-0 ms-auto" id="toggleRemoved" hidden>Show older packages</button></div>
</div>
  </div>

  <div class="col-xxl-4">
    <div class="card mb-3"><div class="card-header"><i class="bi bi-hdd-network me-1"></i>Client hosting needs</div><div class="card-body small">
      <ul class="mb-0">
        <li><strong>PHP 8.1, 8.2 or 8.3</strong> with <code>pdo_mysql, mbstring, json, openssl, sodium, gd, fileinfo, curl</code></li>
        <li><strong>MySQL 5.7+ / MariaDB 10.3+</strong>: one database and user with ALL PRIVILEGES</li>
        <li>Apache/LiteSpeed with <code>.htaccess</code> and mod_rewrite; HTTPS before live payments</li>
        <li>No cron, SSH or Composer needed. Disk space: about 20 MB for the website<?= $counts['media'] ? ', plus ' . (int) $counts['media'] . ' uploaded image' . ($counts['media'] === 1 ? '' : 's') : '' ?>.</li>
      </ul>
    </div></div>
    <div class="card mb-3"><div class="card-header"><i class="bi bi-credit-card me-1"></i>Payments</div><div class="card-body small">
      <?php if (!empty($payments['enabled']) && $gatewayName): ?>
        <p class="mb-1"><?= e($gatewayName) ?> · <span class="badge text-bg-<?= ($payments['mode'] ?? 'test') === 'live' ? 'success' : 'warning' ?>"><?= e(strtoupper($payments['mode'] ?? 'test')) ?></span></p>
        <p class="text-muted mb-0">Gateway keys are <strong>not</strong> in the package. The client enters them after installation in the website admin → Payments → Settings.</p>
      <?php else: ?><p class="text-muted mb-0">Online payments are off. The client can turn them on later in the website admin.</p><?php endif; ?>
    </div></div>
    <div class="card mb-3"><div class="card-header"><i class="bi bi-list-check me-1"></i>Website checklist</div>
      <ul class="list-group list-group-flush small">
        <?php foreach ($checklist['items'] as $it): if ($it['status'] === 'ok') { continue; } ?>
          <li class="list-group-item d-flex gap-2"><i class="bi bi-<?= $it['status'] === 'error' ? 'x-circle-fill text-danger' : 'exclamation-triangle-fill text-warning' ?>"></i><div><?= e($it['label']) ?><?php if ($it['detail']): ?><div class="text-muted"><?= e($it['detail']) ?></div><?php endif; ?></div></li>
        <?php endforeach; ?>
        <?php if (!$checklist['errors'] && !$checklist['warnings']): ?><li class="list-group-item text-success"><i class="bi bi-check-circle-fill me-1"></i>No problems found.</li><?php endif; ?>
      </ul></div>
    <div class="card"><div class="card-header"><i class="bi bi-rocket-takeoff me-1"></i>Deploying</div><div class="card-body small">
      <ol class="mb-2 ps-3">
        <li>Download the full package and give the client (or upload yourself) to cPanel → File Manager → <code>public_html</code> → Extract.</li>
        <li>cPanel → MySQL Databases: create a database and user (ALL PRIVILEGES).</li>
        <li>Open <code>https://<?= e($w['domain'] ?: 'clientdomain.com') ?>/install.php</code> and enter the <strong>installation key</strong> shown with the package.</li>
        <li>Work through <code>DEPLOYMENT_CHECKLIST.md</code> (in the package), then mark the website <strong>Deployed</strong>.</li>
      </ol>
      <p class="text-muted mb-0">Later fixes reach the client as an <strong>update package</strong> (code only), with <code>UPDATE.md</code> instructions.</p>
    </div></div>
  </div>
</div>
