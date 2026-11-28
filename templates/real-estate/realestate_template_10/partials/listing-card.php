<?php
/* One listing tile, price and a details link in the card footer: used by the Listings section and the listing pages. */
$it = $item ?? [];
$cfg = $view->listingConfig();
$img = $view->img($it['image'] ?? '');
$for = (string) ($it['attrs']['listing_for'] ?? '');
$facts = array_values(array_filter((array) ($it['facts'] ?? []), static fn ($f) => $f['card']));
$url = $view->listingUrl($it);
?>
<article class="hhr-listing hhr-listing-classic hhr-card<?= !empty($it['is_closed']) ? ' is-closed' : '' ?>">
  <a class="hhr-listing-img" href="<?= $view->e($url) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?>
    <?= $view->partial('listing-tags', ['item' => $it, 'for' => $for]) ?>
  </a>
  <div class="hhr-listing-body">
    <?php if (($it['category_name'] ?? '') !== ''): ?><span class="hhr-listing-type"><?= $view->e($it['category_name']) ?></span><?php endif; ?>
    <h3 class="hhr-listing-title"><a href="<?= $view->e($url) ?>"><?= $view->e($it['title'] ?? '') ?></a></h3>
    <?php if (($it['location'] ?? '') !== ''): ?><p class="hhr-listing-loc"><i class="bi bi-geo-alt" aria-hidden="true"></i><?= $view->e($it['location']) ?></p><?php endif; ?>
    <?php if ($facts): ?>
      <ul class="hhr-listing-chips"><?php foreach (array_slice($facts, 0, 3) as $f): ?><li><?php if ($f['icon'] !== ''): ?><i class="bi <?= $view->e($f['icon']) ?>" aria-hidden="true"></i><?php endif; ?><span><?= $view->e($f['value']) ?></span><span class="visually-hidden"> <?= $view->e($f['label']) ?></span></li><?php endforeach; ?></ul>
    <?php endif; ?>
    <div class="hhr-listing-foot">
      <span class="hhr-listing-amount"><strong><?= $view->e($it['price_text'] !== '' ? $it['price_text'] : 'Price on request') ?></strong><?php if (($it['price_note'] ?? '') !== '' && $it['price_text'] !== ''): ?> <small><?= $view->e($it['price_note']) ?></small><?php endif; ?></span>
      <a class="hhr-listing-go" href="<?= $view->e($url) ?>" aria-label="View <?= $view->e($it['title'] ?? '') ?>"><i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</article>
