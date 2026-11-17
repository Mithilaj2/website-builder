<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'hzt-cta') ?>>
  <div class="container">
    <div class="hzt-cta-box"<?= $bg !== '' ? ' style="background-image:url(\'' . $bg . '\')"' : '' ?>>
      <div class="hzt-cta-text">
        <h2 class="hzt-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p><?= $view->text($data['description']) ?></p><?php endif; ?>
      </div>
      <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'hzt-btn-light']) ?>
    </div>
  </div>
</section>
