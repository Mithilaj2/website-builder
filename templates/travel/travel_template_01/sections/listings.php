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
<section <?= $view->attrs($section, 'hzt-listings') ?>>
  <div class="container">
    <div class="hzt-row-head">
      <?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?>
      <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="hzt-link d-none d-md-inline-flex" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
    </div>
    <?php if (!$items): ?>
      <p class="hzt-muted">Packages will appear here once they are added in the website admin (Packages).</p>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($items as $item): ?><div class="col-md-6 col-lg-4"><?= $view->partial('listing-card', ['item' => $item]) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($view->hasLink($data['button'] ?? [])): ?><div class="text-center mt-4 d-md-none"><a class="btn hzt-btn hzt-btn-outline" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?></a></div><?php endif; ?>
  </div>
</section>
