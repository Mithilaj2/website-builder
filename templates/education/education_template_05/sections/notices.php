<section <?= $view->attrs($section, 'cis-notices cis-notices-board') ?>>
  <div class="container">
    <div class="cis-board">
      <div class="cis-board-label">
        <span class="cis-board-pin"><i class="bi bi-pin-angle-fill"></i></span>
        <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="cis-eyebrow"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
        <h2 class="cis-board-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p><?= $view->text($data['description']) ?></p><?php endif; ?>
        <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="cis-link" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
      </div>
      <ul class="cis-board-list"><?php foreach ((array) ($data['items'] ?? []) as $n): ?><?= $view->partial('notice-item', ['n' => $n]) ?><?php endforeach; ?></ul>
    </div>
  </div>
</section>
