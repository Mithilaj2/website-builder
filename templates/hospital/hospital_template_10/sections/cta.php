<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'chi-cta chi-cta-band') ?>>
  <?php if ($bg !== ''): ?><div class="chi-cta-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="container position-relative text-center">
    <h2 class="chi-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
    <?php if (($data['description'] ?? '') !== ''): ?><p class="chi-cta-text mx-auto"><?= $view->text($data['description']) ?></p><?php endif; ?>
    <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'chi-btn-light', 'extra' => 'justify-content-center']) ?>
  </div>
</section>
