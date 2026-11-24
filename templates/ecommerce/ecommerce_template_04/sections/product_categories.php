<?php $cats = array_slice($view->productCategories(), 0, max(2, min(12, (int) ($data['limit'] ?? 6)))); ?>
<section <?= $view->attrs($section, 'gbo-cats gbo-cats-circles') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$cats): ?>
      <p class="gbo-lead text-center">Categories will appear here once they are added in the website admin (Products → Categories).</p>
    <?php else: ?>
      <div class="gbo-cat-row" style="--gbo-n:<?= count($cats) ?>">
        <?php foreach ($cats as $c): $img = $view->img($c['image'] ?? ''); ?>
          <a class="gbo-cat" href="<?= $view->e($view->categoryUrl($c)) ?>">
            <span class="gbo-cat-img"><?php if ($img !== ''): ?><img src="<?= $img ?>" alt="" loading="lazy"><?php else: ?><i class="bi bi-grid"></i><?php endif; ?></span>
            <span class="gbo-cat-text"><strong><?= $view->e($c['name']) ?></strong>
            <?php if (!empty($data['show_counts'])): ?><small><?= (int) $c['product_count'] ?> product<?= (int) $c['product_count'] === 1 ? '' : 's' ?></small><?php endif; ?></span>
          </a>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
