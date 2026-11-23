<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'lhc-process lhc-process-line') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="lhc-steps" style="--lhc-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="lhc-step">
          <span class="lhc-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="lhc-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="lhc-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="lhc-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
