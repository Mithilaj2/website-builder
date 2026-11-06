<?php
$cards = [
    ['label' => 'Total Clients', 'value' => $stats['clients'], 'icon' => 'people', 'cls' => ''],
    ['label' => 'Generated Websites', 'value' => $stats['websites'], 'icon' => 'globe2', 'cls' => 'cyan'],
    ['label' => 'Templates', 'value' => $stats['templates'], 'sub' => $stats['templates_active'] . ' active', 'icon' => 'layout-text-window-reverse', 'cls' => ''],
    ['label' => 'Categories', 'value' => $stats['categories'], 'sub' => $stats['categories_active'] . ' active', 'icon' => 'grid-3x3-gap', 'cls' => 'cyan'],
    ['label' => 'Active (Deployed)', 'value' => $stats['active_sites'], 'icon' => 'check2-circle', 'cls' => 'green'],
    ['label' => 'Inactive (Archived)', 'value' => $stats['inactive_sites'], 'icon' => 'archive', 'cls' => 'gray'],
    ['label' => 'Under Development', 'value' => $stats['dev_sites'], 'icon' => 'tools', 'cls' => 'amber'],
    ['label' => 'Website Generations', 'value' => $stats['generations'], 'icon' => 'gear-wide-connected', 'cls' => ''],
];
$actions = array_filter([
    can('websites.manage') ? ['url' => 'websites?new=1', 'icon' => 'plus-circle', 'label' => 'Create New Website'] : null,
    can('websites.view') ? ['url' => 'websites', 'icon' => 'globe2', 'label' => 'Manage Websites'] : null,
    can('templates.view') ? ['url' => 'templates', 'icon' => 'layout-text-window-reverse', 'label' => 'Manage Templates'] : null,
    can('categories.view') ? ['url' => 'categories', 'icon' => 'grid-3x3-gap', 'label' => 'Manage Categories'] : null,
    can('clients.view') ? ['url' => 'clients', 'icon' => 'people', 'label' => 'Manage Clients'] : null,
    can('users.manage') ? ['url' => 'users', 'icon' => 'person-gear', 'label' => 'Manage Users'] : null,
]);
?>
<div class="row g-3 mb-4">
  <?php foreach ($cards as $c): ?>
    <div class="col-6 col-xl-3">
      <div class="card h-100"><div class="stat-card">
        <div class="icon <?= e($c['cls']) ?>"><i class="bi bi-<?= e($c['icon']) ?>"></i></div>
        <div><div class="value"><?= (int) $c['value'] ?></div><div class="label"><?= e($c['label']) ?><?= isset($c['sub']) ? ' · ' . e($c['sub']) : '' ?></div></div>
      </div></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="row g-3">
  <div class="col-xl-8">
    <div class="card">
      <div class="card-header d-flex flex-wrap align-items-center gap-2">
        <span>Recently created websites</span>
        <form class="filters ms-auto d-flex gap-2" id="recentFilters" role="search">
          <input type="search" name="q" class="form-control form-control-sm" placeholder="Search name, client, domain" aria-label="Search websites">
          <select name="status" class="form-select form-select-sm" aria-label="Status">
            <option value="">All statuses</option>
            <?php foreach (['draft', 'config_pending', 'generating', 'generated', 'testing', 'ready', 'deployed', 'archived', 'failed'] as $s): ?>
              <option value="<?= e($s) ?>"><?= e(ucwords(str_replace('_', ' ', $s))) ?></option>
            <?php endforeach; ?>
          </select>
        </form>
      </div>
      <div id="recentTable"></div>
    </div>
  </div>
  <div class="col-xl-4">
    <div class="card mb-3">
      <div class="card-header">Quick actions</div>
      <div class="card-body d-grid gap-2">
        <?php foreach ($actions as $a): ?>
          <a class="quick-action" href="<?= e(url($a['url'])) ?>"><i class="bi bi-<?= e($a['icon']) ?>"></i><?= e($a['label']) ?><i class="bi bi-chevron-right ms-auto small text-muted"></i></a>
        <?php endforeach; ?>
      </div>
      <div class="card-footer bg-white small text-muted">The full creation wizard with generation arrives in Phase 3, export in Phase 6.</div>
    </div>
    <div class="card">
      <div class="card-header">Templates by category</div>
      <ul class="list-group list-group-flush">
        <?php foreach ($byCategory as $c): ?>
          <li class="list-group-item d-flex justify-content-between align-items-center">
            <span><?= e($c['name']) ?> <?= $c['status'] === 'inactive' ? '<span class="badge text-bg-secondary ms-1">Inactive</span>' : '' ?></span>
            <span class="small text-muted"><?= (int) $c['active_templates'] ?>/<?= (int) $c['templates'] ?> templates · <?= (int) $c['websites'] ?> sites</span>
          </li>
        <?php endforeach; ?>
      </ul>
    </div>
  </div>
</div>
