<?php
$cols = ['2' => 'col-sm-6', '3' => 'col-sm-6 col-lg-4', '4' => 'col-6 col-lg-3'][$data['columns'] ?? '3'] ?? 'col-sm-6 col-lg-4';
$images = array_filter((array) ($data['images'] ?? []), static fn ($i) => ($i['image'] ?? '') !== '');
?>
<section <?= $view->attrs($section, 'mrd-gallery') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$images): ?>
      <p class="text-center mrd-muted">Gallery images will appear here.</p>
    <?php else: ?>
    <div class="row g-3">
      <?php foreach ($images as $img): $src = $view->img($img['image']); ?>
        <div class="<?= $cols ?>">
          <figure class="mrd-gallery-item">
            <a href="<?= $src ?>" target="_blank" rel="noopener"><img src="<?= $src ?>" alt="<?= $view->alt($img['image'], (string) ($img['caption'] ?? '')) ?>" loading="lazy"></a>
            <?php if (($img['caption'] ?? '') !== ''): ?><figcaption><?= $view->e($img['caption']) ?></figcaption><?php endif; ?>
          </figure>
        </div>
      <?php endforeach; ?>
    </div>
    <?php endif; ?>
  </div>
</section>
