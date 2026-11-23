<?php
$cats = array_slice($view->listingCategories(), 0, max(1, min(12, (int) ($data['limit'] ?? 4))));
$showCounts = !empty($data['show_counts']);
$cfg = $view->listingConfig();
?>
<section <?= $view->attrs($section, 'hzt-types') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$cats): ?>
      <p class="hzt-muted text-center">Destinations will appear here once they are added in the website admin.</p>
    <?php else: ?>
      <div class="hzt-type-grid" style="--hzt-types:<?= min(4, count($cats)) ?>">
        <?php foreach ($cats as $i => $c): $img = $view->img($c['image'] ?? ''); $n = (int) ($c['listing_count'] ?? 0); ?>
          <a class="hzt-type" href="<?= $view->e($view->listingsUrl(['c' => $c['slug']])) ?>">
            <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?>
            <span class="hzt-type-text"><strong><?= $view->e($c['name']) ?></strong><?php if ($showCounts): ?><small><?= $n ?> <?= $view->e(strtolower($n === 1 ? ($cfg['singular'] ?? 'listing') : ($cfg['plural'] ?? 'listings'))) ?></small><?php endif; ?></span>
            <i class="bi bi-arrow-up-right hzt-type-go" aria-hidden="true"></i>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
