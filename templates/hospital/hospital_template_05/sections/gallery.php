<?php $n = (int) (['2' => 2, '3' => 3, '4' => 4][$data['columns'] ?? '3'] ?? 3); ?>
<section <?= $view->attrs($section, 'lhc-gallery lhc-gallery-masonry') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="lhc-masonry" style="--lhc-n:<?= $n ?>">
      <?php foreach ((array) ($data['images'] ?? []) as $i => $g): $src = $view->img($g['image'] ?? ''); if ($src === '') { continue; } ?>
        <figure class="lhc-shot<?= $i % 3 === 0 ? ' is-tall' : '' ?>"><img src="<?= $src ?>" alt="<?= $view->alt($g['image'] ?? '', (string) ($g['caption'] ?? '')) ?>" loading="lazy"><?php if (($g['caption'] ?? '') !== ''): ?><figcaption><?= $view->e($g['caption']) ?></figcaption><?php endif; ?></figure>
      <?php endforeach; ?>
    </div>
  </div>
</section>
