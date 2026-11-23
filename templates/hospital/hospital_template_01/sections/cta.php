<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'crl-cta') ?>>
  <div class="container">
    <div class="crl-cta-box"<?= $bg !== '' ? ' style="background-image:linear-gradient(rgba(var(--wb-primary-rgb),.88),rgba(var(--wb-primary-rgb),.88)),url(\'' . $bg . '\')"' : '' ?>>
      <div>
        <h2 class="crl-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p><?= $view->text($data['description']) ?></p><?php endif; ?>
      </div>
      <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'crl-btn-outline-light']) ?>
    </div>
  </div>
</section>
