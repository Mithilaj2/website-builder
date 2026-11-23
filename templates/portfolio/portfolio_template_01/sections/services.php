<section <?= $view->attrs($section, 'skl-services') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data, 'align' => 'start']) ?>
    <ol class="skl-svc-list">
      <?php foreach ((array) ($data['items'] ?? []) as $i => $item): ?>
        <li class="skl-svc">
          <span class="skl-svc-num"><?= sprintf('%02d', $i + 1) ?></span>
          <h3 class="skl-svc-title"><?= $view->e($item['title'] ?? '') ?></h3>
          <div class="skl-svc-body">
            <?php if (($item['description'] ?? '') !== ''): ?><p><?= $view->text($item['description']) ?></p><?php endif; ?>
            <?php if ($view->hasLink($item['link'] ?? [])): ?><a class="skl-link" href="<?= $view->href($item['link']) ?>"<?= $view->linkAttrs($item['link']) ?>><?= $view->e($item['link']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
          </div>
        </li>
      <?php endforeach; ?>
    </ol>
  </div>
</section>
