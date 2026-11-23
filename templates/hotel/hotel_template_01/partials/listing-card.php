<?php
/* One room: used by the Listings section and the room pages. Arch-topped photo, facts, nightly rate. */
$it = $item ?? [];
$cfg = $view->listingConfig();
$img = $view->img($it['image'] ?? '');
$facts = array_values(array_filter((array) ($it['facts'] ?? []), static fn ($f) => $f['card']));
$url = $view->listingUrl($it);
?>
<article class="lvh-room<?= !empty($it['is_closed']) ? ' is-closed' : '' ?>">
  <a class="lvh-room-img" href="<?= $view->e($url) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?>
    <?php if (!empty($it['is_closed'])): ?><span class="lvh-room-badge is-dark"><?= $view->e($cfg['closed_label'] ?? 'Fully booked') ?></span>
    <?php elseif (($it['badge'] ?? '') !== ''): ?><span class="lvh-room-badge"><?= $view->e($it['badge']) ?></span><?php endif; ?>
  </a>
  <div class="lvh-room-body">
    <?php if (($it['category_name'] ?? '') !== ''): ?><span class="lvh-room-type"><?= $view->e($it['category_name']) ?></span><?php endif; ?>
    <h3 class="lvh-room-title"><a href="<?= $view->e($url) ?>"><?= $view->e($it['title'] ?? '') ?></a></h3>
    <?php if ($facts): ?><ul class="lvh-room-facts"><?php foreach (array_slice($facts, 0, 3) as $f): ?><li><?php if ($f['icon'] !== ''): ?><i class="bi <?= $view->e($f['icon']) ?>" aria-hidden="true"></i><?php endif; ?><?= $view->e($f['value']) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <div class="lvh-room-foot">
      <span class="lvh-room-rate"><?php if ($it['price_text'] !== ''): ?><small>From</small> <strong><?= $view->e($it['price_text']) ?></strong> <small><?= $view->e(($it['price_note'] ?? '') !== '' ? $it['price_note'] : 'per night') ?></small><?php else: ?><strong>Rate on request</strong><?php endif; ?></span>
      <a class="lvh-link" href="<?= $view->e($url) ?>">View <?= $view->e(strtolower($cfg['singular'] ?? 'room')) ?><span class="visually-hidden">: <?= $view->e($it['title'] ?? '') ?></span><i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</article>
