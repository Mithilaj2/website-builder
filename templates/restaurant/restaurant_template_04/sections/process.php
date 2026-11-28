<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'fnl-process fnl-process-timeline') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="fnl-steps" style="--fnl-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="fnl-step">
          <span class="fnl-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="fnl-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="fnl-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="fnl-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
