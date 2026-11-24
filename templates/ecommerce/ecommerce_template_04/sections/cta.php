<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'gbo-cta gbo-cta-split') ?>>
  <div class="container">
    <div class="gbo-cta-inner<?= $bg !== '' ? ' has-image' : '' ?>">
      <div class="gbo-cta-copy">
        <h2 class="gbo-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p class="gbo-cta-text"><?= $view->text($data['description']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? []]) ?>
      </div>
      <?php if ($bg !== ''): ?><div class="gbo-cta-media" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
    </div>
  </div>
</section>
