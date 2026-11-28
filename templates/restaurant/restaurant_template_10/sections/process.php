<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'pdd-process pdd-process-timeline') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="pdd-steps" style="--pdd-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="pdd-step">
          <span class="pdd-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="pdd-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="pdd-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="pdd-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
