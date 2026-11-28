<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'hhr-cta hhr-cta-split') ?>>
  <div class="container">
    <div class="hhr-cta-inner<?= $bg !== '' ? ' has-image' : '' ?>">
      <div class="hhr-cta-copy">
        <h2 class="hhr-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p class="hhr-cta-text"><?= $view->text($data['description']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? []]) ?>
      </div>
      <?php if ($bg !== ''): ?><div class="hhr-cta-media" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
    </div>
  </div>
</section>
