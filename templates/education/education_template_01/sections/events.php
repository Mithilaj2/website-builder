<?php
$items = (array) ($data['items'] ?? []);
$today = date('Y-m-d');
if (!empty($data['hide_past']) && !$view->isPreview()) {
    $items = array_values(array_filter($items, static fn ($e) => (string) ($e['date'] ?? '') === '' || (string) $e['date'] >= $today));
}
usort($items, static fn ($a, $b) => strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? '')));
?>
<section <?= $view->attrs($section, 'lyc-events') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$items): ?>
      <p class="text-center lyc-muted">New events will be announced soon.</p>
    <?php endif; ?>
    <div class="lyc-event-list">
      <?php foreach ($items as $ev): $t = strtotime((string) ($ev['date'] ?? '')); $img = $view->img($ev['image'] ?? ''); ?>
        <article class="lyc-event">
          <time class="lyc-event-date" datetime="<?= $view->e($t ? date('Y-m-d', $t) : '') ?>"><b><?= $t ? date('d', $t) : '–' ?></b><span><?= $t ? date('M Y', $t) : '' ?></span></time>
          <div class="lyc-event-body">
            <h3 class="lyc-card-title"><?= $view->e($ev['title'] ?? '') ?></h3>
            <div class="lyc-event-meta">
              <?php if ($t): ?><span><i class="bi bi-calendar3"></i><?= date('l', $t) ?></span><?php endif; ?>
              <?php if (($ev['time'] ?? '') !== ''): ?><span><i class="bi bi-clock"></i><?= $view->e($ev['time']) ?></span><?php endif; ?>
              <?php if (($ev['location'] ?? '') !== ''): ?><span><i class="bi bi-geo-alt"></i><?= $view->e($ev['location']) ?></span><?php endif; ?>
            </div>
            <?php if (($ev['description'] ?? '') !== ''): ?><p><?= $view->text($ev['description']) ?></p><?php endif; ?>
            <?php if ($view->hasLink($ev['link'] ?? [])): ?><a class="lyc-link" href="<?= $view->href($ev['link']) ?>"<?= $view->linkAttrs($ev['link']) ?>><?= $view->e($ev['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
          </div>
          <?php if ($img !== ''): ?><div class="lyc-event-img"><img src="<?= $img ?>" alt="<?= $view->alt($ev['image'] ?? '', (string) ($ev['title'] ?? '')) ?>" loading="lazy"></div><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
