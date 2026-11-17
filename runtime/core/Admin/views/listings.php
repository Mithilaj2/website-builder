<?php /** @var array $cfg @var array $categories @var string $publicUrl */ ?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <a class="btn btn-primary" href="<?= e($admin->url('listings/new')) ?>"><i class="bi bi-plus-lg me-1"></i>Add <?= e(strtolower($cfg['singular'])) ?></a>
  <a class="btn btn-light" href="<?= e($admin->url('listing-categories')) ?>"><i class="bi bi-folder2 me-1"></i><?= e($cfg['category_label']) ?> list</a>
  <a class="btn btn-light ms-auto" href="<?= e($publicUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>View on website</a>
</div>
<div class="card">
  <div class="card-header">
    <form class="filters d-flex flex-wrap gap-2" id="listingFilters" role="search">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Title or <?= e(strtolower($cfg['location_label'])) ?>" aria-label="Search">
      <select name="category" class="form-select form-select-sm" aria-label="<?= e($cfg['category_label']) ?>"><option value="">All</option><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?><option value="0">None</option></select>
      <select name="status" class="form-select form-select-sm" aria-label="Status"><option value="">Any status</option><option value="active">Active</option><option value="closed"><?= e($cfg['closed_label']) ?></option><option value="draft">Draft</option></select>
    </form>
  </div>
  <div id="listingTable" data-closed-label="<?= e($cfg['closed_label']) ?>" data-singular="<?= e(strtolower($cfg['singular'])) ?>"></div>
</div>
