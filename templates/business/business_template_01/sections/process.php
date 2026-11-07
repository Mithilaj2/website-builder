<section <?= $view->attrs($section, 'mrd-process') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <ol class="mrd-steps">
      <?php foreach ((array) ($data['steps'] ?? []) as $i => $s): ?>
        <li class="mrd-step">
          <span class="mrd-step-num"><?= ($s['icon'] ?? '') !== '' ? $view->icon($s['icon']) : str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT) ?></span>
          <h3 class="mrd-card-title"><?= $view->e($s['title'] ?? '') ?></h3>
          <?php if (($s['description'] ?? '') !== ''): ?><p><?= $view->text($s['description']) ?></p><?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
