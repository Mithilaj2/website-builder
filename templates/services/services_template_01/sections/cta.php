<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'ldg-cta') ?>>
  <div class="container">
    <div class="ldg-cta-box"<?= $bg !== '' ? ' style="background-image:url(\'' . $bg . '\')"' : '' ?>>
      <div class="ldg-cta-text">
        <h2 class="ldg-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p><?= $view->text($data['description']) ?></p><?php endif; ?>
      </div>
      <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'ldg-btn-light']) ?>
    </div>
  </div>
</section>
