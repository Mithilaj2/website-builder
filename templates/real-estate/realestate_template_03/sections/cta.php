<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'nvk-cta nvk-cta-split') ?>>
  <div class="container">
    <div class="nvk-cta-inner<?= $bg !== '' ? ' has-image' : '' ?>">
      <div class="nvk-cta-copy">
        <h2 class="nvk-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p class="nvk-cta-text"><?= $view->text($data['description']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? []]) ?>
      </div>
      <?php if ($bg !== ''): ?><div class="nvk-cta-media" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
    </div>
  </div>
</section>
