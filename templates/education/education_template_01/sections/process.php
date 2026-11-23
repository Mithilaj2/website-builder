<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'lyc-process') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="lyc-steps" style="--lyc-steps:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="lyc-step">
          <span class="lyc-step-num"><?= $i + 1 ?></span>
          <?php if (($s['icon'] ?? '') !== ''): ?><span class="lyc-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
          <h3 class="lyc-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
          <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
