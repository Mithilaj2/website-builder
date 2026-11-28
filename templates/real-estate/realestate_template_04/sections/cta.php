<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'mrs-cta mrs-cta-band') ?>>
  <?php if ($bg !== ''): ?><div class="mrs-cta-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="container position-relative text-center">
    <h2 class="mrs-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
    <?php if (($data['description'] ?? '') !== ''): ?><p class="mrs-cta-text mx-auto"><?= $view->text($data['description']) ?></p><?php endif; ?>
    <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'mrs-btn-light', 'extra' => 'justify-content-center']) ?>
  </div>
</section>
