<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'vga-process vga-process-line') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="vga-steps" style="--vga-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="vga-step">
          <span class="vga-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="vga-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="vga-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="vga-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
