<?php $legal = $view->menu('footer'); ?>
<div class="vga-footer-bottom">
  <span>© <?= $view->year() ?> <?= $view->e($view->site('name', 'Company')) ?>. <?= $view->e($view->site('footer_text', 'All rights reserved.')) ?></span>
  <?php if ($legal): ?><ul class="vga-footer-legal"><?php foreach ($legal as $item): ?><li><a href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $view->e($item['title']) ?></a></li><?php endforeach; ?></ul><?php endif; ?>
</div>
