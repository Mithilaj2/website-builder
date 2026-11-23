<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'cse-process cse-process-timeline') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="cse-steps" style="--cse-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="cse-step">
          <span class="cse-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="cse-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="cse-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="cse-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
