<span id="workspaceConfig" hidden data-config='<?= e(json_encode($config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>'></span>
<ul class="nav nav-pills mb-3 gap-1">
  <li class="nav-item"><button class="nav-link active py-1" data-bs-toggle="pill" data-bs-target="#subTheme" type="button">Colours, fonts &amp; logo</button></li>
  <li class="nav-item"><button class="nav-link py-1" data-bs-toggle="pill" data-bs-target="#subDetails" type="button">Contact details, footer &amp; SEO</button></li>
</ul>
<div class="tab-content">
  <div class="tab-pane fade show active" id="subTheme"><?php include dirname(__DIR__, 2) . '/editor/theme-form.php'; ?></div>
  <div class="tab-pane fade" id="subDetails"><?php include dirname(__DIR__, 2) . '/editor/details-form.php'; ?></div>
</div>
<?php include dirname(__DIR__, 2) . '/editor/editor-modals.php'; ?>
