<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'hcr-banner hcr-banner-solid') ?>>
  <?php if ($bg !== ''): ?><div class="hcr-banner-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="container position-relative text-center">
    <nav aria-label="Breadcrumb"><ol class="hcr-crumbs justify-content-center"><li><a href="<?= $view->e($view->url('')) ?>">Home</a></li><li aria-current="page"><?= $view->e($page['title'] ?? '') ?></li></ol></nav>
    <h1 class="hcr-banner-title"><?= $view->e($data['heading'] ?? ($page['title'] ?? '')) ?></h1>
    <?php if (($data['subheading'] ?? '') !== ''): ?><p class="hcr-banner-text mx-auto"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
  </div>
</section>
