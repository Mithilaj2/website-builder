<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'lyc-banner') ?>>
  <?php if ($bg !== ''): ?><div class="lyc-banner-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="container position-relative">
    <nav aria-label="Breadcrumb"><ol class="lyc-crumbs"><li><a href="<?= $view->e($view->url('')) ?>">Home</a></li><li aria-current="page"><?= $view->e($page['title'] ?? '') ?></li></ol></nav>
    <h1 class="lyc-banner-title"><?= $view->e($data['heading'] ?? ($page['title'] ?? '')) ?></h1>
    <?php if (($data['subheading'] ?? '') !== ''): ?><p class="lyc-banner-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
  </div>
  <svg class="lyc-banner-arch" viewBox="0 0 1440 60" preserveAspectRatio="none" aria-hidden="true"><path d="M0 60V40C240 10 480 0 720 0s480 10 720 40v20Z" fill="var(--wb-bg)"/></svg>
</section>
