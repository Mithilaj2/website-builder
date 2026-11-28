<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'jts-process jts-process-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="jts-steps" style="--jts-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="jts-step">
          <span class="jts-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="jts-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="jts-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="jts-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
