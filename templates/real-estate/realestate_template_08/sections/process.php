<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, 'gft-process gft-process-timeline') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="gft-steps" style="--gft-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="gft-step">
          <span class="gft-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="gft-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="gft-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="gft-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
