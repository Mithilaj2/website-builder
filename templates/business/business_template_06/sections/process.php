<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'bpt-process bpt-process-line') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="bpt-steps" style="--bpt-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="bpt-step">
          <span class="bpt-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="bpt-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="bpt-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="bpt-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
