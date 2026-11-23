<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'skl-banner') ?>>
  <?php if ($bg !== ''): ?><div class="skl-banner-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="container position-relative">
    <nav aria-label="Breadcrumb"><ol class="skl-crumbs"><li><a href="<?= $view->e($view->url('')) ?>">Home</a></li><li aria-current="page"><?= $view->e($page['title'] ?? '') ?></li></ol></nav>
    <h1 class="skl-banner-title"><?= $view->e($data['heading'] ?? ($page['title'] ?? '')) ?></h1>
    <?php if (($data['subheading'] ?? '') !== ''): ?><p class="skl-banner-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
  </div>
</section>
