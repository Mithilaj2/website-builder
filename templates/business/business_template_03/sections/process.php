<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'vtx-process vtx-process-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="vtx-steps" style="--vtx-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="vtx-step">
          <span class="vtx-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="vtx-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="vtx-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="vtx-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
