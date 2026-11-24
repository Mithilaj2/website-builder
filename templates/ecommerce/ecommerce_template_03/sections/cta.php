<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'vlt-cta vlt-cta-band') ?>>
  <?php if ($bg !== ''): ?><div class="vlt-cta-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="container position-relative text-center">
    <h2 class="vlt-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
    <?php if (($data['description'] ?? '') !== ''): ?><p class="vlt-cta-text mx-auto"><?= $view->text($data['description']) ?></p><?php endif; ?>
    <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'vlt-btn-light', 'extra' => 'justify-content-center']) ?>
  </div>
</section>
