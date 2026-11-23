<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'skl-process') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="skl-steps" style="--skl-steps:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="skl-step">
          <span class="skl-step-num"><?= $i + 1 ?></span>
          <?php if (($s['icon'] ?? '') !== ''): ?><span class="skl-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
          <h3 class="skl-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
          <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
