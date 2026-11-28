<?php
$items = (array) ($data['items'] ?? []);
if (!empty($data['hide_past']) && !$view->isPreview()) {
    $today = date('Y-m-d');
    $items = array_values(array_filter($items, static fn ($e) => (string) ($e['date'] ?? '') === '' || (string) $e['date'] >= $today));
}
usort($items, static fn ($a, $b) => strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? '')));
?>
<section <?= $view->attrs($section, 'atr-events atr-events-list') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$items): ?><p class="text-center atr-lead">New events will be announced soon.</p><?php endif; ?>
    <div class="atr-event-list">
      <?php foreach ($items as $ev): $t = strtotime((string) ($ev['date'] ?? '')); $img = $view->img($ev['image'] ?? ''); ?>
        <article class="atr-event">
          <time class="atr-event-date" datetime="<?= $view->e($t ? date('Y-m-d', $t) : '') ?>"><b><?= $t ? date('d', $t) : '–' ?></b><span><?= $t ? date('M Y', $t) : '' ?></span></time>
          <div class="atr-event-body">
            <h3 class="atr-card-title"><?= $view->e($ev['title'] ?? '') ?></h3>
            <?= $view->partial('event-meta', ['ev' => $ev, 't' => $t]) ?>
            <?php if (($ev['description'] ?? '') !== ''): ?><p><?= $view->text($ev['description']) ?></p><?php endif; ?>
            <?php if ($view->hasLink($ev['link'] ?? [])): ?><a class="atr-link" href="<?= $view->href($ev['link']) ?>"<?= $view->linkAttrs($ev['link']) ?>><?= $view->e($ev['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
          </div>
          <?php if ($img !== ''): ?><div class="atr-event-img"><img src="<?= $img ?>" alt="<?= $view->alt($ev['image'] ?? '', (string) ($ev['title'] ?? '')) ?>" loading="lazy"></div><?php endif; ?>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
