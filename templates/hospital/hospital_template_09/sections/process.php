<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'mnw-process mnw-process-timeline') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="mnw-steps" style="--mnw-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="mnw-step">
          <span class="mnw-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="mnw-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="mnw-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="mnw-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
