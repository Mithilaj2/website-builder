<?php $items = array_slice((array) ($data['items'] ?? []), 0, 3); ?>
<section <?= $view->attrs($section, 'lac-notices lac-notices-strip') ?>>
  <div class="container">
    <div class="lac-strip">
      <div class="lac-strip-label"><i class="bi bi-megaphone-fill"></i><span><?= $view->e(($data['eyebrow'] ?? '') ?: ($data['heading'] ?? 'Notices')) ?></span></div>
      <ul class="lac-board-list"><?php foreach ($items as $n): ?><?= $view->partial('notice-item', ['n' => $n]) ?><?php endforeach; ?></ul>
      <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="lac-link lac-strip-more" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
    </div>
  </div>
</section>
