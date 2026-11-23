<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, '{{p}}-cta {{p}}-cta-band') ?>>
  <?php if ($bg !== ''): ?><div class="{{p}}-cta-bg" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
  <div class="container position-relative text-center">
    <h2 class="{{p}}-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
    <?php if (($data['description'] ?? '') !== ''): ?><p class="{{p}}-cta-text mx-auto"><?= $view->text($data['description']) ?></p><?php endif; ?>
    <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => '{{p}}-btn-light', 'extra' => 'justify-content-center']) ?>
  </div>
</section>
