<span id="workspaceConfig" hidden data-config='<?= e(json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>'></span>
<div class="row g-3">
  <div class="col-lg-4">
    <div class="card">
      <div class="card-header d-flex align-items-center">Pages<button class="btn btn-primary btn-sm ms-auto" id="btnAddPage"><i class="bi bi-plus-lg"></i> Add page</button></div>
      <ul class="list-group list-group-flush" id="pageList"><li class="list-group-item text-center py-4"><div class="spinner-border spinner-border-sm text-primary"></div></li></ul>
      <div class="card-footer bg-white small text-muted"><i class="bi bi-grip-vertical"></i> Drag pages to change their order.</div>
    </div>
  </div>
  <div class="col-lg-8">
    <div class="card">
      <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <span id="sectionsTitle">Sections</span>
        <select class="form-select form-select-sm w-auto d-none" id="sectionsPageSelect"></select>
        <div class="ms-auto d-flex gap-2">
          <button class="btn btn-light btn-sm" id="btnPreviewPage"><i class="bi bi-eye"></i> View page</button>
          <div class="dropdown">
            <button class="btn btn-primary btn-sm dropdown-toggle" data-bs-toggle="dropdown" id="btnAddSection"><i class="bi bi-plus-lg"></i> Add section</button>
            <ul class="dropdown-menu dropdown-menu-end" style="max-height:420px;overflow:auto">
              <?php foreach ($schemas as $type => $s): ?>
                <li><a class="dropdown-item d-flex gap-2 align-items-start py-2" href="#" data-add-section="<?= e($type) ?>"><i class="bi <?= e($s['icon'] ?? 'bi-square') ?> mt-1"></i><span><span class="fw-semibold d-block"><?= e($s['label']) ?></span><small class="text-muted"><?= e($s['description'] ?? '') ?></small></span></a></li>
              <?php endforeach; ?>
            </ul>
          </div>
        </div>
      </div>
      <ul class="list-group list-group-flush" id="sectionList"><li class="list-group-item text-muted small py-4 text-center">Select a page.</li></ul>
    </div>
  </div>
</div>
<?php include dirname(__DIR__, 2) . '/editor/editor-modals.php'; ?>
