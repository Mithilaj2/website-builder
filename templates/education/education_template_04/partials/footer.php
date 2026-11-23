<?php $menu = $view->menu('header'); $about = (string) $view->site('footer_about', $view->site('tagline')); ?>
<footer class="mit-footer mit-footer-columns">
  <div class="container">
    <div class="row g-4 g-lg-5">
      <div class="col-lg-4">
        <?= $view->partial('brand', ['class' => 'mit-brand-footer']) ?>
        <?php if ($about !== ''): ?><p class="mit-footer-about"><?= $view->e($about) ?></p><?php endif; ?>
        <?= $view->partial('social') ?>
      </div>
      <div class="col-sm-6 col-lg-3 offset-lg-1">
        <h2 class="mit-footer-title">Explore</h2>
        <ul class="mit-footer-links">
          <?php foreach ($menu as $item): ?><li><a href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?>><?= $view->e($item['title']) ?></a></li><?php endforeach; ?>
        </ul>
      </div>
      <div class="col-sm-6 col-lg-4">
        <h2 class="mit-footer-title">Contact</h2>
        <?= $view->partial('contact-lines') ?>
      </div>
    </div>
    <?= $view->partial('footer-bottom') ?>
  </div>
</footer>
