<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'lac-process lac-process-line') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="lac-steps" style="--lac-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="lac-step">
          <span class="lac-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="lac-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="lac-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="lac-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
