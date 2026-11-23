<?php
/* One holiday package: used by the Listings section and the package search pages. */
$it = $item ?? [];
$cfg = $view->listingConfig();
$img = $view->img($it['image'] ?? '');
$a = (array) ($it['attrs'] ?? []);
$days = (int) ($a['duration'] ?? 0);
$nights = (int) ($a['nights'] ?? 0);
$chips = array_values(array_filter((array) ($it['facts'] ?? []), static fn ($f) => $f['card'] && $f['key'] !== 'duration'));
$url = $view->listingUrl($it);
?>
<article class="hzt-trip<?= !empty($it['is_closed']) ? ' is-closed' : '' ?>">
  <a class="hzt-trip-img" href="<?= $view->e($url) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?>
    <?php if ($days): ?><span class="hzt-trip-days"><strong><?= $days ?>D</strong><?= $nights ? ' / ' . $nights . 'N' : '' ?></span><?php endif; ?>
    <?php if (!empty($it['is_closed'])): ?><span class="hzt-trip-badge is-dark"><?= $view->e($cfg['closed_label'] ?? 'Sold out') ?></span>
    <?php elseif (($it['badge'] ?? '') !== ''): ?><span class="hzt-trip-badge"><?= $view->e($it['badge']) ?></span><?php endif; ?>
  </a>
  <div class="hzt-trip-body">
    <?php if (($it['category_name'] ?? '') !== ''): ?><span class="hzt-trip-dest"><i class="bi bi-geo-alt-fill"></i><?= $view->e($it['category_name']) ?></span><?php endif; ?>
    <h3 class="hzt-trip-title"><a href="<?= $view->e($url) ?>"><?= $view->e($it['title'] ?? '') ?></a></h3>
    <?php if (($it['location'] ?? '') !== ''): ?><p class="hzt-trip-route"><?= $view->e($it['location']) ?></p><?php endif; ?>
    <?php if ($chips): ?><ul class="hzt-trip-chips"><?php foreach (array_slice($chips, 0, 3) as $f): ?><li><?php if ($f['icon'] !== ''): ?><i class="bi <?= $view->e($f['icon']) ?>" aria-hidden="true"></i><?php endif; ?><?= $view->e($f['value']) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <div class="hzt-trip-foot">
      <span class="hzt-trip-price"><?php if ($it['price_text'] !== ''): ?><small>From</small><strong><?= $view->e($it['price_text']) ?></strong><?php if (($it['price_note'] ?? '') !== ''): ?><small><?= $view->e($it['price_note']) ?></small><?php endif; ?><?php else: ?><strong>Price on request</strong><?php endif; ?></span>
      <a class="hzt-trip-go" href="<?= $view->e($url) ?>" aria-label="View <?= $view->e($it['title'] ?? '') ?>"><i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</article>
