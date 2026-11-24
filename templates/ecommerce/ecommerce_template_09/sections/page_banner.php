<?php $bg = $view->img($data['background_image'] ?? '', 'theme:images/banner.svg'); ?>
<section <?= $view->attrs($section, 'stz-banner stz-banner-image') ?>>
  <div class="stz-banner-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div>
  <div class="container position-relative">
    <h1 class="stz-banner-title"><?= $view->e($data['heading'] ?? ($page['title'] ?? '')) ?></h1>
    <?php if (($data['subheading'] ?? '') !== ''): ?><p class="stz-banner-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
    <nav aria-label="Breadcrumb"><ol class="stz-crumbs"><li><a href="<?= $view->e($view->url('')) ?>">Home</a></li><li aria-current="page"><?= $view->e($page['title'] ?? '') ?></li></ol></nav>
  </div>
</section>
