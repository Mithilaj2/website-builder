<?php
$cats = array_slice($view->listingCategories(), 0, max(1, min(12, (int) ($data['limit'] ?? 4))));
$cfg = $view->listingConfig();
?>
<section <?= $view->attrs($section, 'smt-ltypes smt-ltypes-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$cats): ?>
      <p class="smt-lead text-center">Categories will appear here once they are added in the website admin.</p>
    <?php else: ?>
      <div class="smt-ltype-row" style="--smt-n:<?= min(4, count($cats)) ?>">
        <?php foreach ($cats as $c): $img = $view->img($c['image'] ?? ''); $n = (int) ($c['listing_count'] ?? 0); ?>
          <a class="smt-ltype smt-card" href="<?= $view->e($view->listingsUrl(['c' => $c['slug']])) ?>">
            <span class="smt-ltype-img"><?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php endif; ?></span>
            <span class="smt-ltype-body">
              <strong><?= $view->e($c['name']) ?></strong>
              <?php if (($c['description'] ?? '') !== ''): ?><span class="smt-ltype-desc"><?= $view->e($c['description']) ?></span><?php endif; ?>
              <span class="smt-ltype-more"><?php if (!empty($data['show_counts'])): ?><?= $n ?> <?= $view->e(strtolower($n === 1 ? ($cfg['singular'] ?? 'listing') : ($cfg['plural'] ?? 'listings'))) ?><?php else: ?>View all<?php endif; ?><i class="bi bi-arrow-right" aria-hidden="true"></i></span>
            </span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
