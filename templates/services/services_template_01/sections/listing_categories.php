<?php
$cats = array_slice($view->listingCategories(), 0, max(1, min(12, (int) ($data['limit'] ?? 3))));
$showCounts = !empty($data['show_counts']);
$cfg = $view->listingConfig();
?>
<section <?= $view->attrs($section, 'ldg-types') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$cats): ?>
      <p class="ldg-muted text-center">Service groups will appear here once they are added in the website admin.</p>
    <?php else: ?>
      <div class="row g-4 justify-content-center">
        <?php foreach ($cats as $c): $img = $view->img($c['image'] ?? ''); $n = (int) ($c['listing_count'] ?? 0); ?>
          <div class="col-md-6 col-lg-4">
            <a class="ldg-type" href="<?= $view->e($view->listingsUrl(['c' => $c['slug']])) ?>">
              <?php if ($img !== ''): ?><span class="ldg-type-img"><img src="<?= $img ?>" alt="" loading="lazy"></span><?php endif; ?>
              <span class="ldg-type-text">
                <strong><?= $view->e(($cfg['category_label'] ?? '') . ' ' . strtolower((string) $c['name'])) ?></strong>
                <?php if (($c['description'] ?? '') !== ''): ?><small><?= $view->e($c['description']) ?></small><?php endif; ?>
                <?php if ($showCounts): ?><em><?= $n ?> <?= $view->e(strtolower($n === 1 ? ($cfg['singular'] ?? 'service') : ($cfg['plural'] ?? 'services'))) ?> <i class="bi bi-arrow-right"></i></em><?php endif; ?>
              </span>
            </a>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
