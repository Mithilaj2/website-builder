<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'sfr-process') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="sfr-steps" style="--sfr-steps:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="sfr-step">
          <span class="sfr-step-num"><?= $i + 1 ?></span>
          <?php if (($s['icon'] ?? '') !== ''): ?><span class="sfr-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
          <h3 class="sfr-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
          <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
