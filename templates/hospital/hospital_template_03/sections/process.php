<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'scd-process scd-process-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="scd-steps" style="--scd-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="scd-step">
          <span class="scd-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="scd-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="scd-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="scd-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
