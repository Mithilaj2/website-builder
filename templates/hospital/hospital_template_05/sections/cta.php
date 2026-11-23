<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, 'lhc-cta lhc-cta-box') ?>>
  <div class="container">
    <div class="lhc-cta-inner<?= $bg !== '' ? ' has-bg' : '' ?>"<?= $bg !== '' ? ' style="background-image:url(\'' . $bg . '\')"' : '' ?>>
      <div class="row align-items-center g-4">
        <div class="col-lg-7">
          <h2 class="lhc-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
          <?php if (($data['description'] ?? '') !== ''): ?><p class="lhc-cta-text"><?= $view->text($data['description']) ?></p><?php endif; ?>
        </div>
        <div class="col-lg-5"><?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? [], 'secondaryClass' => 'lhc-btn-light', 'extra' => 'lhc-cta-actions']) ?></div>
      </div>
    </div>
  </div>
</section>
