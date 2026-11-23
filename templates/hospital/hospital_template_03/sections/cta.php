<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'scd-cta scd-cta-band') ?>>
  <?php if ($bg !== ''): ?><div class="scd-cta-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="container position-relative text-center">
    <h2 class="scd-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
    <?php if (($data['description'] ?? '') !== ''): ?><p class="scd-cta-text mx-auto"><?= $view->text($data['description']) ?></p><?php endif; ?>
    <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'scd-btn-light', 'extra' => 'justify-content-center']) ?>
  </div>
</section>
