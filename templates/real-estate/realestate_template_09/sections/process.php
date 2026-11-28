<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'hcr-process hcr-process-line') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="hcr-steps" style="--hcr-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="hcr-step">
          <span class="hcr-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="hcr-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="hcr-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="hcr-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
