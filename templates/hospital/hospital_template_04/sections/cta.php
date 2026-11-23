<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'cse-cta cse-cta-split') ?>>
  <div class="container">
    <div class="cse-cta-inner<?= $bg !== '' ? ' has-image' : '' ?>">
      <div class="cse-cta-copy">
        <h2 class="cse-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p class="cse-cta-text"><?= $view->text($data['description']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? []]) ?>
      </div>
      <?php if ($bg !== ''): ?><div class="cse-cta-media" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
    </div>
  </div>
</section>
