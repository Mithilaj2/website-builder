<?php
/** One listing in a grid. @var \SiteRuntime\View $view @var array $item */
$custom = $view->partial('listing-card', ['item' => $item]);
if ($custom !== '') {
    echo $custom;
    return;
}
$img = $view->img($item['image'] ?? '');
$cfg = $view->listingConfig();
?>
<article class="wblist-card<?= $item['is_closed'] ? ' is-closed' : '' ?>">
  <a class="wblist-card-img" href="<?= $view->e($view->listingUrl($item)) ?>">
    <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="<?= $view->e($item['title']) ?>" loading="lazy"><?php endif; ?>
    <?php if ($item['is_closed']): ?><span class="wblist-flag is-dark"><?= $view->e($cfg['closed_label']) ?></span><?php elseif (($item['badge'] ?? '') !== ''): ?><span class="wblist-flag"><?= $view->e($item['badge']) ?></span><?php endif; ?>
  </a>
  <div class="wblist-card-body">
    <?php if (($item['category_name'] ?? '') !== ''): ?><span class="wblist-cat"><?= $view->e($item['category_name']) ?></span><?php endif; ?>
    <h3 class="wblist-card-title"><a href="<?= $view->e($view->listingUrl($item)) ?>"><?= $view->e($item['title']) ?></a></h3>
    <?php if (($item['location'] ?? '') !== ''): ?><p class="wblist-loc"><i class="bi bi-geo-alt" aria-hidden="true"></i><?= $view->e($item['location']) ?></p><?php endif; ?>
    <?php $facts = array_values(array_filter($item['facts'], static fn ($f) => $f['card'])); if ($facts): ?>
      <ul class="wblist-facts"><?php foreach (array_slice($facts, 0, 4) as $f): ?><li><?php if ($f['icon']): ?><i class="bi <?= $view->e($f['icon']) ?>" aria-hidden="true"></i><?php endif; ?><?= $view->e($f['value']) ?><span class="visually-hidden"> <?= $view->e($f['label']) ?></span></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <div class="wblist-price"><?php if ($item['price_text'] !== ''): ?><strong><?= $view->e($item['price_text']) ?></strong><?php if (($item['price_note'] ?? '') !== ''): ?> <small><?= $view->e($item['price_note']) ?></small><?php endif; ?><?php else: ?><strong>Price on request</strong><?php endif; ?></div>
  </div>
</article>
