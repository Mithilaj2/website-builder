<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'kst-process') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="kst-steps" style="--kst-steps:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="kst-step">
          <span class="kst-step-num"><?= $i + 1 ?></span>
          <?php if (($s['icon'] ?? '') !== ''): ?><span class="kst-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
          <h3 class="kst-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
          <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
