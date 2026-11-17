<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'kst-cta') ?>>
  <div class="container">
    <div class="kst-cta-box"<?= $bg !== '' ? ' style="background-image:url(\'' . $bg . '\')"' : '' ?>>
      <div class="kst-cta-text">
        <h2 class="kst-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p><?= $view->text($data['description']) ?></p><?php endif; ?>
      </div>
      <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'kst-btn-light']) ?>
    </div>
  </div>
</section>
