<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'psd-process psd-process-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="psd-steps" style="--psd-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="psd-step">
          <span class="psd-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="psd-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="psd-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="psd-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
