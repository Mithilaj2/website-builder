<?php $items = array_slice((array) ($data['items'] ?? []), 0, 3); ?>
<section <?= $view->attrs($section, 'lgb-notices lgb-notices-strip') ?>>
  <div class="container">
    <div class="lgb-strip">
      <div class="lgb-strip-label"><i class="bi bi-megaphone-fill"></i><span><?= $view->e(($data['eyebrow'] ?? '') ?: ($data['heading'] ?? 'Notices')) ?></span></div>
      <ul class="lgb-board-list"><?php foreach ($items as $n): ?><?= $view->partial('notice-item', ['n' => $n]) ?><?php endforeach; ?></ul>
      <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="lgb-link lgb-strip-more" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
    </div>
  </div>
</section>
