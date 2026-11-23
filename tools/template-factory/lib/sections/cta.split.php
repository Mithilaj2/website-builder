<?php $bg = $view->img($data['background_image'] ?? ''); ?>
<section <?= $view->attrs($section, '{{p}}-cta {{p}}-cta-split') ?>>
  <div class="container">
    <div class="{{p}}-cta-inner<?= $bg !== '' ? ' has-image' : '' ?>">
      <div class="{{p}}-cta-copy">
        <h2 class="{{p}}-cta-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p class="{{p}}-cta-text"><?= $view->text($data['description']) ?></p><?php endif; ?>
        <?= $view->partial('buttons', ['primary' => $data['button'] ?? [], 'secondary' => $data['secondary_button'] ?? []]) ?>
      </div>
      <?php if ($bg !== ''): ?><div class="{{p}}-cta-media" style="background-image:url('<?= $bg ?>')" aria-hidden="true"></div><?php endif; ?>
    </div>
  </div>
</section>
