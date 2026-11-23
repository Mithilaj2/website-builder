<section <?= $view->attrs($section, 'rsp-notices rsp-notices-board') ?>>
  <div class="container">
    <div class="rsp-board">
      <div class="rsp-board-label">
        <span class="rsp-board-pin"><i class="bi bi-pin-angle-fill"></i></span>
        <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="rsp-eyebrow"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
        <h2 class="rsp-board-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p><?= $view->text($data['description']) ?></p><?php endif; ?>
        <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="rsp-link" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
      </div>
      <ul class="rsp-board-list"><?php foreach ((array) ($data['items'] ?? []) as $n): ?><?= $view->partial('notice-item', ['n' => $n]) ?><?php endforeach; ?></ul>
    </div>
  </div>
</section>
