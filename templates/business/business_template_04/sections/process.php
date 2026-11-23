<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'gfh-process gfh-process-timeline') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="gfh-steps" style="--gfh-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="gfh-step">
          <span class="gfh-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="gfh-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="gfh-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="gfh-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
