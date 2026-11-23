<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'nbf-stats nbf-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="nbf-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="nbf-stat-grid" style="--nbf-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="nbf-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="nbf-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-nbf-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="nbf-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
