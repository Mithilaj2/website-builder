<?php $cols = ['2' => 'col-md-6', '3' => 'col-6 col-lg-4', '4' => 'col-6 col-lg-3'][$data['columns'] ?? '3'] ?? 'col-6 col-lg-4'; ?>
<section <?= $view->attrs($section, 'crl-gallery') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-3">
      <?php foreach ((array) ($data['images'] ?? []) as $i => $g): $src = $view->img($g['image'] ?? ''); if ($src === '') { continue; } ?>
        <div class="<?= $cols ?>">
          <figure class="crl-shot">
            <img src="<?= $src ?>" alt="<?= $view->alt($g['image'] ?? '', (string) ($g['caption'] ?? '')) ?>" loading="lazy">
            <?php if (($g['caption'] ?? '') !== ''): ?><figcaption><?= $view->e($g['caption']) ?></figcaption><?php endif; ?>
          </figure>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
