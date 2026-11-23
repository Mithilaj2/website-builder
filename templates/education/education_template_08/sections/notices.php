<section <?= $view->attrs($section, 'wsm-notices wsm-notices-board') ?>>
  <div class="container">
    <div class="wsm-board">
      <div class="wsm-board-label">
        <span class="wsm-board-pin"><i class="bi bi-pin-angle-fill"></i></span>
        <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="wsm-eyebrow"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
        <h2 class="wsm-board-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p><?= $view->text($data['description']) ?></p><?php endif; ?>
        <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="wsm-link" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
      </div>
      <ul class="wsm-board-list"><?php foreach ((array) ($data['items'] ?? []) as $n): ?><?= $view->partial('notice-item', ['n' => $n]) ?><?php endforeach; ?></ul>
    </div>
  </div>
</section>
