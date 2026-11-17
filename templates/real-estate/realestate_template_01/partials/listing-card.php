<?php
/* One property tile: used by the Listings section and the property search pages. */
$it = $item ?? [];
$cfg = $view->listingConfig();
$img = $view->img($it['image'] ?? '');
$for = (string) ($it['attrs']['listing_for'] ?? '');
$facts = array_values(array_filter((array) ($it['facts'] ?? []), static fn ($f) => $f['card']));
$url = $view->listingUrl($it);
?>
<article class="kst-prop<?= !empty($it['is_closed']) ? ' is-closed' : '' ?>">
  <a class="kst-prop-img" href="<?= $view->e($url) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?>
    <span class="kst-prop-tags">
      <?php if (!empty($it['is_closed'])): ?><span class="kst-tag is-dark"><?= $view->e($cfg['closed_label'] ?? 'Sold') ?></span>
      <?php else: ?>
        <?php if ($for !== ''): ?><span class="kst-tag">For <?= $view->e(strtolower($for)) ?></span><?php endif; ?>
        <?php if (($it['badge'] ?? '') !== ''): ?><span class="kst-tag is-gold"><?= $view->e($it['badge']) ?></span><?php endif; ?>
      <?php endif; ?>
    </span>
    <span class="kst-prop-price"><?= $view->e($it['price_text'] !== '' ? $it['price_text'] : 'Price on request') ?><?php if (($it['price_note'] ?? '') !== '' && $it['price_text'] !== ''): ?> <small><?= $view->e($it['price_note']) ?></small><?php endif; ?></span>
  </a>
  <div class="kst-prop-body">
    <?php if (($it['category_name'] ?? '') !== ''): ?><span class="kst-prop-type"><?= $view->e($it['category_name']) ?></span><?php endif; ?>
    <h3 class="kst-prop-title"><a href="<?= $view->e($url) ?>"><?= $view->e($it['title'] ?? '') ?></a></h3>
    <?php if (($it['location'] ?? '') !== ''): ?><p class="kst-prop-loc"><i class="bi bi-geo-alt"></i><?= $view->e($it['location']) ?></p><?php endif; ?>
    <?php if ($facts): ?>
      <ul class="kst-prop-facts"><?php foreach (array_slice($facts, 0, 3) as $f): ?><li><?php if ($f['icon'] !== ''): ?><i class="bi <?= $view->e($f['icon']) ?>" aria-hidden="true"></i><?php endif; ?><span><?= $view->e($f['value']) ?></span><span class="visually-hidden"> <?= $view->e($f['label']) ?></span></li><?php endforeach; ?></ul>
    <?php endif; ?>
  </div>
</article>
