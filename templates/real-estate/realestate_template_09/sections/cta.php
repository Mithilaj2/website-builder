<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'hcr-cta hcr-cta-split') ?>>
  <div class="container">
    <div class="hcr-cta-inner<?= $bg !== '' ? ' has-image' : '' ?>">
      <div class="hcr-cta-copy">
        <h2 class="hcr-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p class="hcr-cta-text"><?= $view->text($data['description']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? []]) ?>
      </div>
      <?php if ($bg !== ''): ?><div class="hcr-cta-media" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
    </div>
  </div>
</section>
