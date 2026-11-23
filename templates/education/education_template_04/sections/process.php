<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'mit-process mit-process-timeline') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="mit-steps" style="--mit-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="mit-step">
          <span class="mit-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="mit-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="mit-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="mit-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
