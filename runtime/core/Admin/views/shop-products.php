<?php /** @var array $categories */ $isOwner = ($user['role'] ?? '') === 'owner'; ?>
<?php if (!$shopEnabled): ?>
  <div class="alert alert-warning d-flex gap-2 align-items-start"><i class="bi bi-shop fs-5"></i><div>The shop is switched off: products are not shown to visitors.<?php if ($isOwner): ?> <a href="<?= e($admin->url('shop-settings')) ?>">Shop settings</a><?php endif; ?></div></div>
<?php endif; ?>
<div class="d-flex flex-wrap gap-2 mb-3">
  <a class="btn btn-primary" href="<?= e($admin->url('products/new')) ?>"><i class="bi bi-plus-lg me-1"></i>Add product</a>
  <a class="btn btn-light" href="<?= e($admin->url('categories')) ?>"><i class="bi bi-folder2 me-1"></i>Categories</a>
  <?php if ($isOwner): ?><a class="btn btn-light" href="<?= e($admin->url('shop-settings')) ?>"><i class="bi bi-gear me-1"></i>Shop settings</a><?php endif; ?>
  <a class="btn btn-light ms-auto" href="<?= e($shopUrl) ?>" target="_blank" rel="noopener"><i class="bi bi-box-arrow-up-right me-1"></i>View shop</a>
</div>
<div class="card">
  <div class="card-header">
    <form class="filters d-flex flex-wrap gap-2" id="productFilters" role="search">
      <input type="search" name="q" class="form-control form-control-sm" placeholder="Name or SKU" aria-label="Search products">
      <select name="category" class="form-select form-select-sm" aria-label="Category"><option value="">All categories</option><?php foreach ($categories as $c): ?><option value="<?= (int) $c['id'] ?>"><?= e($c['name']) ?></option><?php endforeach; ?><option value="0">No category</option></select>
      <select name="status" class="form-select form-select-sm" aria-label="Status"><option value="">Active and drafts</option><option value="active">Active</option><option value="draft">Drafts</option></select>
      <select name="stock" class="form-select form-select-sm" aria-label="Stock"><option value="">Any stock</option><option value="low">Low stock (≤ <?= (int) $lowStock ?>)</option></select>
    </form>
  </div>
  <div id="productTable"></div>
</div>
