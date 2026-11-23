<?php
/* One service: illustration, group, title, summary, fee and turnaround. */
$it = $item ?? [];
$cfg = $view->listingConfig();
$img = $view->img($it['image'] ?? '');
$facts = array_values(array_filter((array) ($it['facts'] ?? []), static fn ($f) => $f['card']));
$url = $view->listingUrl($it);
?>
<article class="ldg-svc<?= !empty($it['is_closed']) ? ' is-closed' : '' ?>">
  <a class="ldg-svc-img" href="<?= $view->e($url) ?>" tabindex="-1" aria-hidden="true">
    <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?>
    <?php if (!empty($it['is_closed'])): ?><span class="ldg-svc-badge is-muted"><?= $view->e($cfg['closed_label'] ?? 'Not available') ?></span>
    <?php elseif (($it['badge'] ?? '') !== ''): ?><span class="ldg-svc-badge"><?= $view->e($it['badge']) ?></span><?php endif; ?>
  </a>
  <div class="ldg-svc-body">
    <?php if (($it['category_name'] ?? '') !== ''): ?><span class="ldg-svc-group"><?= $view->e(($cfg['category_label'] ?? 'For') . ' ' . strtolower((string) $it['category_name'])) ?></span><?php endif; ?>
    <h3 class="ldg-svc-title"><a href="<?= $view->e($url) ?>"><?= $view->e($it['title'] ?? '') ?></a></h3>
    <?php if (($it['short_description'] ?? '') !== ''): ?><p class="ldg-svc-text"><?= $view->e($it['short_description']) ?></p><?php endif; ?>
    <?php if ($facts): ?><ul class="ldg-svc-facts"><?php foreach ($facts as $f): ?><li><?php if ($f['icon'] !== ''): ?><i class="bi <?= $view->e($f['icon']) ?>" aria-hidden="true"></i><?php endif; ?><?= $view->e($f['value']) ?></li><?php endforeach; ?></ul><?php endif; ?>
    <div class="ldg-svc-foot">
      <span class="ldg-svc-price"><?php if ($it['price_text'] !== ''): ?><small><?= $view->e($cfg['price_label'] ?? 'From') ?></small><strong><?= $view->e($it['price_text']) ?></strong><?php if (($it['price_note'] ?? '') !== ''): ?><small><?= $view->e($it['price_note']) ?></small><?php endif; ?><?php else: ?><strong class="is-quote">Fees on request</strong><?php endif; ?></span>
      <a class="ldg-svc-go" href="<?= $view->e($url) ?>">Details<span class="visually-hidden">: <?= $view->e($it['title'] ?? '') ?></span><i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</article>
