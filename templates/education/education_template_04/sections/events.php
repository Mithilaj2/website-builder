<?php
$items = (array) ($data['items'] ?? []);
if (!empty($data['hide_past']) && !$view->isPreview()) {
    $today = date('Y-m-d');
    $items = array_values(array_filter($items, static fn ($e) => (string) ($e['date'] ?? '') === '' || (string) $e['date'] >= $today));
}
usort($items, static fn ($a, $b) => strcmp((string) ($a['date'] ?? ''), (string) ($b['date'] ?? '')));
?>
<section <?= $view->attrs($section, 'mit-events mit-events-cards') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <?php if (!$items): ?><p class="text-center mit-lead">New events will be announced soon.</p><?php endif; ?>
    <div class="row g-4 justify-content-center">
      <?php foreach ($items as $ev): $t = strtotime((string) ($ev['date'] ?? '')); $img = $view->img($ev['image'] ?? ''); ?>
        <div class="col-md-6 col-lg-4">
          <article class="mit-card mit-event-card">
            <div class="mit-event-card-img"><?php if ($img !== ''): ?><img src="<?= $img ?>" alt="<?= $view->alt($ev['image'] ?? '', (string) ($ev['title'] ?? '')) ?>" loading="lazy"><?php endif; ?>
              <time class="mit-event-date" datetime="<?= $view->e($t ? date('Y-m-d', $t) : '') ?>"><b><?= $t ? date('d', $t) : '–' ?></b><span><?= $t ? date('M', $t) : '' ?></span></time></div>
            <div class="mit-event-body">
              <h3 class="mit-card-title"><?= $view->e($ev['title'] ?? '') ?></h3>
              <?= $view->partial('event-meta', ['ev' => $ev, 't' => $t]) ?>
              <?php if (($ev['description'] ?? '') !== ''): ?><p><?= $view->text($ev['description']) ?></p><?php endif; ?>
              <?php if ($view->hasLink($ev['link'] ?? [])): ?><a class="mit-link" href="<?= $view->href($ev['link']) ?>"<?= $view->linkAttrs($ev['link']) ?>><?= $view->e($ev['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
            </div>
          </article>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
