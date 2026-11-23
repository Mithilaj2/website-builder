<?php $menu = $view->menu('header'); $about = (string) $view->site('footer_about', $view->site('tagline')); ?>
<footer class="vga-footer vga-footer-centered">
  <div class="container text-center">
    <?= $view->partial('brand', ['class' => 'vga-brand-footer']) ?>
    <?php if ($about !== ''): ?><p class="vga-footer-about mx-auto"><?= $view->e($about) ?></p><?php endif; ?>
    <ul class="vga-footer-inline">
      <?php foreach ($menu as $item): ?><li><a href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
    </ul>
    <div class="vga-footer-meta">
      <?php if ($view->site('phone') !== ''): ?><a href="tel:<?= $view->e(preg_replace('/[^0-9+]/', '', (string) $view->site('phone'))) ?>"><i class="bi bi-telephone"></i><?= $view->e($view->site('phone')) ?></a><?php endif; ?>
      <?php if ($view->site('email') !== ''): ?><a href="mailto:<?= $view->e($view->site('email')) ?>"><i class="bi bi-envelope"></i><?= $view->e($view->site('email')) ?></a><?php endif; ?>
      <?php if ($view->site('address') !== ''): ?><span><i class="bi bi-geo-alt"></i><?= $view->e(str_replace("\n", ', ', (string) $view->site('address'))) ?></span><?php endif; ?>
    </div>
    <?= $view->partial('social', ['class' => 'justify-content-center']) ?>
    <?= $view->partial('footer-bottom') ?>
  </div>
</footer>
