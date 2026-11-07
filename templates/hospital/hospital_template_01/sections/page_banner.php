<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'crl-banner') ?>>
  <?php if ($bg !== ''): ?><div class="crl-banner-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <svg class="crl-banner-pulse" viewBox="0 0 600 80" aria-hidden="true"><path d="M0 40h250l16-28 20 56 18-44 12 16h284" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"/></svg>
  <div class="container position-relative">
    <nav aria-label="Breadcrumb"><ol class="crl-crumbs"><li><a href="<?= $view->e($view->url('')) ?>">Home</a></li><li aria-current="page"><?= $view->e($page['title'] ?? '') ?></li></ol></nav>
    <h1 class="crl-banner-title"><?= $view->e($data['heading'] ?? ($page['title'] ?? '')) ?></h1>
    <?php if (($data['subheading'] ?? '') !== ''): ?><p class="crl-banner-text"><?= $view->text($data['subheading']) ?></p><?php endif; ?>
  </div>
</section>
