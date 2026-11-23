<?php
$source = (string) ($data['source'] ?? 'featured');
$limit = max(1, min(24, (int) ($data['limit'] ?? 6)));
$opts = ['limit' => $limit];
if ($source === 'featured') {
    $opts['featured'] = true;
} elseif ($source === 'category') {
    $opts['category'] = (string) ($data['category'] ?? '');
} else {
    $opts['sort'] = $source;
}
$items = ($source === 'category' && ($opts['category'] ?? '') === '') ? [] : $view->listings($opts);
?>
<section <?= $view->attrs($section, 'ldg-listings') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$items): ?>
      <p class="ldg-muted">Services will appear here once they are added in the website admin (Services).</p>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($items as $item): ?><div class="col-md-6 col-lg-4"><?= $view->partial('listing-card', ['item' => $item]) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($view->hasLink($data['button'] ?? [])): ?><div class="text-center mt-5"><a class="btn ldg-btn ldg-btn-outline" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?></a></div><?php endif; ?>
  </div>
</section>
