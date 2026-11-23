<?php
/** @var \SiteRuntime\View $view @var array $products @var array $categories @var ?array $category @var string $q @var string $sort @var int $pageNo @var int $pages @var int $total */
$sorts = ['' => 'Featured', 'new' => 'Newest', 'price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low', 'name' => 'Name'];
$params = array_filter(['c' => $category['slug'] ?? '', 'q' => $q, 'sort' => $sort]);
?>
<section class="wbshop wbshop-list">
  <div class="wbshop-wrap">
    <nav class="wbshop-crumbs" aria-label="Breadcrumb"><a href="<?= $view->e($view->url('')) ?>">Home</a><span>/</span><?php if ($category || $q !== ''): ?><a href="<?= $view->e($view->shopUrl()) ?>">Shop</a><span>/</span><?php endif; ?><span aria-current="page"><?= $view->e($category['name'] ?? ($q !== '' ? 'Search' : 'Shop')) ?></span></nav>
    <div class="wbshop-head">
      <div>
        <h1 class="wbshop-title"><?= $view->e($category['name'] ?? ($q !== '' ? 'Results for “' . $q . '”' : 'All products')) ?></h1>
        <?php if (($category['description'] ?? '') !== ''): ?><p class="wbshop-muted"><?= $view->e($category['description']) ?></p><?php endif; ?>
      </div>
      <form class="wbshop-tools" method="get" action="<?= $view->e($view->shopUrl()) ?>" role="search">
        <?php if ($category): ?><input type="hidden" name="c" value="<?= $view->e($category['slug']) ?>"><?php endif; ?>
        <label class="visually-hidden" for="wbshop-q">Search products</label>
        <input id="wbshop-q" type="search" name="q" value="<?= $view->e($q) ?>" placeholder="Search products" maxlength="60">
        <label class="visually-hidden" for="wbshop-sort">Sort by</label>
        <select id="wbshop-sort" name="sort" data-wbshop-autosubmit><?php foreach ($sorts as $k => $label): ?><option value="<?= $view->e($k) ?>"<?= $k === $sort ? ' selected' : '' ?>><?= $view->e($label) ?></option><?php endforeach; ?></select>
        <button type="submit" class="wbshop-btn wbshop-btn-ghost">Go</button>
      </form>
    </div>
    <?php if ($categories): ?>
      <div class="wbshop-chips" role="list">
        <a role="listitem" class="wbshop-chip<?= !$category ? ' is-active' : '' ?>" href="<?= $view->e($view->shopUrl('', array_filter(['q' => $q, 'sort' => $sort]))) ?>">All</a>
        <?php foreach ($categories as $c): ?><a role="listitem" class="wbshop-chip<?= ($category['id'] ?? 0) == $c['id'] ? ' is-active' : '' ?>" href="<?= $view->e($view->shopUrl('', array_filter(['c' => $c['slug'], 'q' => $q, 'sort' => $sort]))) ?>"><?= $view->e($c['name']) ?> <small><?= (int) $c['product_count'] ?></small></a><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if (!$products): ?>
      <div class="wbshop-empty"><p>No products found<?= $q !== '' ? ' for “' . $view->e($q) . '”' : '' ?>.</p><a class="wbshop-btn" href="<?= $view->e($view->shopUrl()) ?>">See all products</a></div>
    <?php else: ?>
      <p class="wbshop-muted wbshop-count"><?= (int) $total ?> product<?= $total === 1 ? '' : 's' ?></p>
      <div class="wbshop-grid">
        <?php foreach ($products as $product): ?><?= $view->capture(__DIR__ . '/card.php', ['product' => $product]) ?><?php endforeach; ?>
      </div>
      <?php if ($pages > 1): ?>
        <nav class="wbshop-pager" aria-label="Pages">
          <?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $pageNo ? 'is-active' : '' ?>" href="<?= $view->e($view->shopUrl('', $params + ($i > 1 ? ['p' => $i] : []))) ?>"<?= $i === $pageNo ? ' aria-current="page"' : '' ?>><?= $i ?></a><?php endfor; ?>
        </nav>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</section>
