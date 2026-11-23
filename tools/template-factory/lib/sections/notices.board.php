<section <?= $view->attrs($section, '{{p}}-notices {{p}}-notices-board') ?>>
  <div class="container">
    <div class="{{p}}-board">
      <div class="{{p}}-board-label">
        <span class="{{p}}-board-pin"><i class="bi bi-pin-angle-fill"></i></span>
        <?php if (($data['eyebrow'] ?? '') !== ''): ?><span class="{{p}}-eyebrow"><?= $view->e($data['eyebrow']) ?></span><?php endif; ?>
        <h2 class="{{p}}-board-title"><?= $view->e($data['heading'] ?? '') ?></h2>
        <?php if (($data['description'] ?? '') !== ''): ?><p><?= $view->text($data['description']) ?></p><?php endif; ?>
        <?php if ($view->hasLink($data['button'] ?? [])): ?><a class="{{p}}-link" href="<?= $view->href($data['button']) ?>"<?= $view->linkAttrs($data['button']) ?>><?= $view->e($data['button']['text']) ?><i class="bi bi-arrow-right"></i></a><?php endif; ?>
      </div>
      <ul class="{{p}}-board-list"><?php foreach ((array) ($data['items'] ?? []) as $n): ?><?= $view->partial('notice-item', ['n' => $n]) ?><?php endforeach; ?></ul>
    </div>
  </div>
</section>
