<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'erc-stats erc-stats-band') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="erc-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="erc-stat-grid" style="--erc-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="erc-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="erc-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-erc-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="erc-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
