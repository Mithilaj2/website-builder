<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'hhr-process hhr-process-line') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="hhr-steps" style="--hhr-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="hhr-step">
          <span class="hhr-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="hhr-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="hhr-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="hhr-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
