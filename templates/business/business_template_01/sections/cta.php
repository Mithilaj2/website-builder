<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'mrd-cta') ?>>
  <div class="container">
    <div class="mrd-cta-box"<?= $bg !== '' ? ' style="background-image:linear-gradient(120deg,rgba(var(--wb-primary-rgb),.94),rgba(var(--wb-primary-rgb),.78)),url(\'' . $bg . '\')"' : '' ?>>
      <div class="row align-items-center g-4">
        <div class="col-lg-8">
          <h2 class="mrd-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
          <?php if (($data['description'] ?? '') !== ''): ?><p class="mrd-cta-text"><?= $view->text($data['description']) ?></p><?php endif; ?>
        </div>
        <div class="col-lg-4 text-lg-end">
          <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'mrd-btn-light']) ?>
        </div>
      </div>
    </div>
  </div>
</section>
