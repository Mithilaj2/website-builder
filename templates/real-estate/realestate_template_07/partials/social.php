<?php $social = $view->social(); if (!$social) { return; } ?>
<div class="bwr-social<?= isset($class) ? ' ' . $view->e($class) : '' ?>">
  <?php foreach ($social as $s): ?><a href="<?= $view->e($s['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $view->e(ucfirst($s['network'])) ?>"><?= $view->icon($s['icon']) ?></a><?php endforeach; ?>
</div>
