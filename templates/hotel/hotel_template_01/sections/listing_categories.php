<?php
$cats = array_slice($view->listingCategories(), 0, max(1, min(12, (int) ($data['limit'] ?? 4))));
$showCounts = !empty($data['show_counts']);
$cfg = $view->listingConfig();
?>
<section <?= $view->attrs($section, 'lvh-types') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$cats): ?>
      <p class="lvh-muted text-center">Room types will appear here once they are added in the website admin.</p>
    <?php else: ?>
      <div class="lvh-type-grid" style="--lvh-types:<?= min(4, count($cats)) ?>">
        <?php foreach ($cats as $i => $c): $img = $view->img($c['image'] ?? ''); $n = (int) ($c['listing_count'] ?? 0); ?>
          <a class="lvh-type" href="<?= $view->e($view->listingsUrl(['c' => $c['slug']])) ?>">
            <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?>
            <span class="lvh-type-text"><strong><?= $view->e($c['name']) ?></strong><?php if ($showCounts): ?><small><?= $n ?> <?= $view->e(strtolower($n === 1 ? ($cfg['singular'] ?? 'listing') : ($cfg['plural'] ?? 'listings'))) ?></small><?php endif; ?></span>
            
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
