<?php
/** @var \SiteRuntime\View $view @var array $items @var int $total @var ?array $category @var array $categories @var array $locations @var array $filters @var array $query @var int $pageNo @var int $pages @var array $config */
$cfg = $config;
$f = $filters;
?>
<section class="wblist wblist-index">
  <div class="wblist-wrap">
    <nav class="wblist-crumbs" aria-label="Breadcrumb"><a href="<?= $view->e($view->url('')) ?>">Home</a><span>/</span><?php if ($category): ?><a href="<?= $view->e($view->listingsUrl()) ?>"><?= $view->e($cfg['menu_label']) ?></a><span>/</span><?php endif; ?><span aria-current="page"><?= $view->e($category['name'] ?? $cfg['menu_label']) ?></span></nav>
    <h1 class="wblist-title"><?= $view->e($category['name'] ?? $cfg['plural']) ?></h1>
    <?php if (($category['description'] ?? '') !== ''): ?><p class="wblist-muted"><?= $view->e($category['description']) ?></p><?php endif; ?>
    <form class="wblist-filters" method="get" action="<?= $view->e($view->listingsUrl()) ?>" role="search" aria-label="Filter <?= $view->e(strtolower($cfg['plural'])) ?>">
      <label class="wblist-f wblist-f-q"><span>Search</span><input type="search" name="q" value="<?= $view->e($f['q']) ?>" placeholder="<?= $view->e($cfg['location_filter'] ? 'Name or ' . strtolower($cfg['location_label']) : 'Search by name') ?>" maxlength="60"></label>
      <?php if ($categories): ?><label class="wblist-f"><span><?= $view->e($cfg['category_label']) ?></span><select name="c"><option value="">Any</option><?php foreach ($categories as $c): ?><option value="<?= $view->e($c['slug']) ?>"<?= $f['c'] === $c['slug'] ? ' selected' : '' ?>><?= $view->e($c['name']) ?></option><?php endforeach; ?></select></label><?php endif; ?>
      <?php if ($cfg['location_filter'] && count($locations) > 1): ?><label class="wblist-f"><span><?= $view->e($cfg['location_label']) ?></span><select name="location"><option value="">Any</option><?php foreach ($locations as $loc): ?><option<?= $f['location'] === $loc ? ' selected' : '' ?>><?= $view->e($loc) ?></option><?php endforeach; ?></select></label><?php endif; ?>
      <?php foreach ($cfg['attributes'] as $key => $a): if (!$a['filter']) { continue; } $cur = $f['a'][$key] ?? ''; ?>
        <?php if ($a['type'] === 'select'): ?><label class="wblist-f"><span><?= $view->e($a['label']) ?></span><select name="a[<?= $view->e($key) ?>]"><option value="">Any</option><?php foreach ($a['options'] as $o): ?><option<?= $cur === $o ? ' selected' : '' ?>><?= $view->e($o) ?></option><?php endforeach; ?></select></label>
        <?php elseif ($a['type'] === 'number'): ?><label class="wblist-f wblist-f-sm"><span><?= $view->e($a['label']) ?></span><select name="a[<?= $view->e($key) ?>]"><option value="">Any</option><?php foreach ([1, 2, 3, 4, 5] as $n): ?><option value="<?= $n ?>"<?= (string) $cur === (string) $n ? ' selected' : '' ?>><?= $n ?>+</option><?php endforeach; ?></select></label>
        <?php else: ?><label class="wblist-check"><input type="checkbox" name="a[<?= $view->e($key) ?>]" value="1"<?= $cur === '1' ? ' checked' : '' ?>> <?= $view->e($a['label']) ?></label><?php endif; ?>
      <?php endforeach; ?>
      <?php if ($cfg['price_format'] !== 'none'): ?>
      <label class="wblist-f wblist-f-sm"><span>Min ₹</span><input name="min" inputmode="numeric" value="<?= $f['min'] !== null ? (int) ($f['min'] / 100) : '' ?>" placeholder="Any"></label>
      <label class="wblist-f wblist-f-sm"><span>Max ₹</span><input name="max" inputmode="numeric" value="<?= $f['max'] !== null ? (int) ($f['max'] / 100) : '' ?>" placeholder="Any"></label>
      <?php endif; ?>
      <div class="wblist-f-actions"><button type="submit" class="wblist-btn"><i class="bi bi-search" aria-hidden="true"></i> Search</button><?php if ($query && array_diff_key($query, ['sort' => 1])): ?><a class="wblist-link" href="<?= $view->e($view->listingsUrl()) ?>">Clear</a><?php endif; ?></div>
    </form>
    <div class="wblist-bar">
      <p class="wblist-muted"><?= (int) $total ?> <?= $view->e(strtolower($total === 1 ? $cfg['singular'] : $cfg['plural'])) ?> found</p>
      <form method="get" action="<?= $view->e($view->listingsUrl()) ?>">
        <?php foreach (array_diff_key($query, ['sort' => 1, 'a' => 1]) as $k => $v): ?><input type="hidden" name="<?= $view->e($k) ?>" value="<?= $view->e($v) ?>"><?php endforeach; ?>
        <?php foreach ((array) ($query['a'] ?? []) as $k => $v): ?><input type="hidden" name="a[<?= $view->e($k) ?>]" value="<?= $view->e($v) ?>"><?php endforeach; ?>
        <label class="visually-hidden" for="wblist-sort">Sort by</label>
        <select id="wblist-sort" name="sort" data-wblist-autosubmit><?php foreach (array_merge(['' => 'Recommended', 'new' => 'Newest'], $cfg['price_format'] === 'none' ? [] : ['price_asc' => 'Price: low to high', 'price_desc' => 'Price: high to low']) as $k => $l): ?><option value="<?= $view->e($k) ?>"<?= $f['sort'] === $k ? ' selected' : '' ?>><?= $view->e($l) ?></option><?php endforeach; ?></select>
        <noscript><button type="submit" class="wblist-btn">Sort</button></noscript>
      </form>
    </div>
    <?php if (!$items): ?>
      <div class="wblist-empty"><p>Nothing matches your search. Try fewer filters.</p><a class="wblist-btn" href="<?= $view->e($view->listingsUrl()) ?>">See all <?= $view->e(strtolower($cfg['plural'])) ?></a></div>
    <?php else: ?>
      <div class="wblist-grid"><?php foreach ($items as $item): ?><?= $view->capture(__DIR__ . '/card.php', ['item' => $item]) ?><?php endforeach; ?></div>
      <?php if ($pages > 1): ?><nav class="wblist-pager" aria-label="Pages"><?php for ($i = 1; $i <= $pages; $i++): ?><a class="<?= $i === $pageNo ? 'is-active' : '' ?>" href="<?= $view->e($view->listingsUrl($query + ($i > 1 ? ['p' => $i] : []))) ?>"<?= $i === $pageNo ? ' aria-current="page"' : '' ?>><?= $i ?></a><?php endfor; ?></nav><?php endif; ?>
    <?php endif; ?>
  </div>
</section>
