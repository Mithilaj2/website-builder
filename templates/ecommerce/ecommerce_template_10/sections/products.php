<?php
$source = (string) ($data['source'] ?? 'featured');
$limit = max(2, min(24, (int) ($data['limit'] ?? 8)));
$opts = ['limit' => $limit];
if ($source === 'featured') {
    $opts['featured'] = true;
} elseif ($source === 'category') {
    $opts['category'] = (string) ($data['category'] ?? '');
} else {
    $opts['sort'] = $source;
}
$items = ($source === 'category' && ($opts['category'] ?? '') === '') ? [] : $view->products($opts);
$cols = ($data['columns'] ?? '4') === '3' ? 'col-6 col-lg-4' : 'col-6 col-lg-3';
?>
<section <?= $view->attrs($section, 'ppl-products') ?>>
  <div class="container">
    <div class="ppl-products-head">
      <?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?>
      <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="ppl-link d-none d-md-inline-flex" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
    </div>
    <?php if (!$items): ?>
      <p class="ppl-lead">Products will appear here once they are added in the website admin (Products).</p>
    <?php else: ?>
      <div class="row g-3 g-lg-4">
        <?php foreach ($items as $product): ?><div class="<?= $cols ?>"><?= $view->partial('product-card', ['product' => $product]) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($view->hasLink($data['button'] ?? [])): ?><div class="text-center mt-4 d-md-none"><a class="btn ppl-btn ppl-btn-ghost" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?></a></div><?php endif; ?>
  </div>
</section>
