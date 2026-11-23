<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'lgb-process lgb-process-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="lgb-steps" style="--lgb-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="lgb-step">
          <span class="lgb-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="lgb-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="lgb-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="lgb-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
