<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'jts-banner jts-banner-light') ?>>
  <div class="container position-relative">
    <div class="row align-items-end g-4">
      <div class="<?= $bg !== '' ? 'col-lg-7' : 'col-lg-9' ?>">
        <nav aria-label="Breadcrumb"><ol class="jts-crumbs"><li><a href="<?= $view->e($view->url('')) ?>">Home</a></li><li aria-current="page"><?= $view->e($page['title'] ?? '') ?></li></ol></nav>
        <h1 class="jts-banner-title"><?= $view->e($data['heading'] ?? ($page['title'] ?? '')) ?></h1>
        <?php if (($data['subheading'] ?? '') !== ''): ?><p class="jts-banner-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
      </div>
      <?php if ($bg !== ''): ?><div class="col-lg-5 d-none d-lg-block"><div class="jts-banner-thumb" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div></div><?php endif; ?>
    </div>
  </div>
</section>
