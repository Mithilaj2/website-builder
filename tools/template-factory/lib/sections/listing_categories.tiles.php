<?php
$cats = array_slice($view->listingCategories(), 0, max(1, min(12, (int) ($data['limit'] ?? 4))));
$cfg = $view->listingConfig();
?>
<section <?= $view->attrs($section, '{{p}}-ltypes {{p}}-ltypes-tiles') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$cats): ?>
      <p class="{{p}}-lead text-center">Categories will appear here once they are added in the website admin.</p>
    <?php else: ?>
      <div class="{{p}}-ltype-row" style="--{{p}}-n:<?= min(4, count($cats)) ?>">
        <?php foreach ($cats as $c): $img = $view->img($c['image'] ?? ''); $n = (int) ($c['listing_count'] ?? 0); ?>
          <a class="{{p}}-ltype" href="<?= $view->e($view->listingsUrl(['c' => $c['slug']])) ?>">
            <?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?>
            <span class="{{p}}-ltype-text"><strong><?= $view->e($c['name']) ?></strong><?php if (!empty($data['show_counts'])): ?><small><?= $n ?> <?= $view->e(strtolower($n === 1 ? ($cfg['singular'] ?? 'listing') : ($cfg['plural'] ?? 'listings'))) ?></small><?php endif; ?></span>
            <i class="bi bi-arrow-up-right {{p}}-ltype-go" aria-hidden="true"></i>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
