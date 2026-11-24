<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'ppl-stats ppl-stats-inline') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="ppl-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="ppl-stat-grid" style="--ppl-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="ppl-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="ppl-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-ppl-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="ppl-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
