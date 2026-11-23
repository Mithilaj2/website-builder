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
<section <?= $view->attrs($section, 'skl-listings') ?>>
  <div class="container">
    <div class="skl-row-head">
      <?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?>
      <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="skl-link d-none d-md-inline-flex" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
    </div>
    <?php if (!$items): ?>
      <p class="skl-muted">Projects will appear here once they are added in the website admin (Work).</p>
    <?php else: ?>
      <div class="row g-4">
        <?php foreach ($items as $item): ?><div class="col-md-6"><?= $view->partial('listing-card', ['item' => $item]) ?></div><?php endforeach; ?>
      </div>
    <?php endif; ?>
    <?php if ($view->hasLink($data['button'] ?? [])): ?><div class="text-center mt-4 d-md-none"><a class="btn skl-btn skl-btn-outline" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?></a></div><?php endif; ?>
  </div>
</section>
