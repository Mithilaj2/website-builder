<?php $steps = (array) ($data['steps'] ?? []); ?>
<section <?= $view->attrs($section, '{{p}}-process {{p}}-process-line') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="{{p}}-steps" style="--{{p}}-n:<?= max(1, min(5, count($steps))) ?>">
      <?php foreach ($steps as $i => $s): ?>
        <li class="{{p}}-step">
          <span class="{{p}}-step-num"><?= str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <div class="{{p}}-step-body">
            <?php if (($s['icon'] ?? '') !== ''): ?><span class="{{p}}-step-icon"><?= $view->icon($s['icon']) ?></span><?php endif; ?>
            <h3 class="{{p}}-feature-title"><?= $view->e($s['title'] ?? '') ?></h3>
            <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
