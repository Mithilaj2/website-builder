<?php
/* Disciplines as a typographic index: big names, project counts, arrows. */
$cats = array_slice($view->listingCategories(), 0, max(1, min(12, (int) ($data['limit'] ?? 6))));
$cfg = $view->listingConfig();
?>
<section <?= $view->attrs($section, 'skl-discs') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?>
    <?php if (!$cats): ?>
      <p class="skl-muted">Disciplines will appear here once they are added in the website admin.</p>
    <?php else: ?>
      <ul class="skl-disc-list">
        <?php foreach ($cats as $i => $c): $n = (int) ($c['listing_count'] ?? 0); ?>
          <li class="skl-disc"><a href="<?= $view->e($view->listingsUrl(['c' => $c['slug']])) ?>">
            <span class="skl-disc-num"><?= sprintf('%02d', $i + 1) ?></span>
            <span class="skl-disc-name"><?= $view->e($c['name']) ?></span>
            <?php if (!empty($data['show_counts'])): ?><span class="skl-disc-count"><?= $n ?> <?= $view->e(strtolower($n === 1 ? ($cfg['singular'] ?? 'project') : 'projects')) ?></span><?php endif; ?>
            <i class="bi bi-arrow-up-right" aria-hidden="true"></i>
          </a></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  </div>
</section>
