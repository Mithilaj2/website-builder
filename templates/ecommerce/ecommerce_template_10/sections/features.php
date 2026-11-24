<?php $img = $view->img($data['image'] ?? ''); $items = (array) ($data['items'] ?? []); ?>
<section <?= $view->attrs($section, 'ppl-features') ?>>
  <div class="container">
    <?php if ($img !== ''): ?>
      <div class="row g-4 g-lg-5 align-items-center">
        <div class="col-lg-7">
          <?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?>
          <div class="row g-4"><?php foreach ($items as $item): ?><div class="col-sm-6"><div class="ppl-feature"><span class="ppl-icon"><?= $view->icon(($item['icon'] ?? '') ?: 'bi-star') ?></span><div><h3 class="ppl-feature-title"><?= $view->e($item['title'] ?? '') ?></h3><?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?></div></div></div><?php endforeach; ?></div>
        </div>
        <div class="col-lg-5"><div class="ppl-features-img"><img src="<?= $img ?>" alt="<?= $view->alt($data['image'] ?? '', (string) ($data['heading'] ?? '')) ?>" loading="lazy"></div></div>
      </div>
    <?php else: ?>
      <?= $view->partial('section-head', ['data' => $data]) ?>
      <div class="ppl-feature-grid" style="--ppl-n:<?= max(1, min(4, count($items))) ?>">
        <?php foreach ($items as $item): ?><div class="ppl-card ppl-feature-card"><span class="ppl-icon"><?= $view->icon(($item['icon'] ?? '') ?: 'bi-star') ?></span><h3 class="ppl-feature-title"><?= $view->e($item['title'] ?? '') ?></h3><?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</section>
