<?php $menu = $view->menu('header'); ?>
<ul class="navbar-nav <?= $view->e($class ?? 'ms-auto') ?> align-items-lg-center">
  <?php foreach ($menu as $item): ?>
    <li class="nav-item"><a class="nav-link<?= $item['active'] ? ' active' : '' ?>" href="<?= $view->e($item['url']) ?>"<?= !empty($item['external']) ? ' target="_blank" rel="noopener"' : '' ?><?= $item['active'] ? ' aria-current="page"' : '' ?>><?= $view->e($item['title']) ?></a></li>
  <?php endforeach; ?>
</ul>
