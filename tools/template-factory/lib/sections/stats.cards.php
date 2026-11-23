<?php $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, '{{p}}-stats {{p}}-stats-cards') ?>>
  <div class="container">
    <?php if (($data['heading'] ?? '') !== ''): ?><h2 class="{{p}}-stats-heading"><?= $view->e($data['heading']) ?></h2><?php endif; ?>
    <div class="{{p}}-stat-grid" style="--{{p}}-n:<?= max(1, min(4, count($items))) ?>">
      <?php foreach ($items as $item): ?>
        <div class="{{p}}-stat"><?php if (($item['icon'] ?? '') !== ''): ?><span class="{{p}}-stat-icon"><?= $view->icon($item['icon']) ?></span><?php endif; ?><strong><span data-{{p}}-count="<?= $view->e(preg_replace('/[^0-9.]/', '', (string) ($item['number'] ?? ''))) ?>"><?= $view->e($item['number'] ?? '') ?></span><?= $view->e($item['suffix'] ?? '') ?></strong><span class="{{p}}-stat-label"><?= $view->e($item['label'] ?? '') ?></span></div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
