<?php $bg = $view->img($data['background_image'] ?? '', 'theme:images/banner.svg'); ?>
<section <?= $view->attrs($section, 'vga-banner vga-banner-image') ?>>
  <div class="vga-banner-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div>
  <div class="container position-relative">
    <h1 class="vga-banner-title"><?= $view->e($data['heading'] ?? ($page['title'] ?? '')) ?></h1>
    <?php if (($data['subheading'] ?? '') !== ''): ?><p class="vga-banner-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
    <nav aria-label="Breadcrumb"><ol class="vga-crumbs"><li><a href="<?= $view->e($view->url('')) ?>">Home</a></li><li aria-current="page"><?= $view->e($page['title'] ?? '') ?></li></ol></nav>
  </div>
</section>
