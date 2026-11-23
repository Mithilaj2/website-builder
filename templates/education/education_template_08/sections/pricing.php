<section <?= $view->attrs($section, 'wsm-pricing') ?>>
  <div class="container">
    <?= $view->partial('section-head', ['data' => $data]) ?>
    <div class="row g-4 justify-content-center align-items-stretch">
      <?php foreach ((array) ($data['plans'] ?? []) as $p): $featured = !empty($p['featured']); ?>
        <div class="col-md-6 col-lg-4">
          <div class="wsm-card wsm-plan<?= $featured ? ' is-featured' : '' ?>">
            <?php if ($featured): ?><span class="wsm-plan-tag">Most popular</span><?php endif; ?>
            <h3 class="wsm-card-title"><?= $view->e($p['name'] ?? '') ?></h3>
            <div class="wsm-plan-price"><strong><?= $view->e($p['price'] ?? '') ?></strong><?php if (($p['period'] ?? '') !== ''): ?><span>/ <?= $view->e($p['period']) ?></span><?php endif; ?></div>
            <ul class="wsm-checks">
              <?php foreach (array_filter(array_map('trim', explode("\n", (string) ($p['features'] ?? '')))) as $f): ?><li><i class="bi bi-check-lg"></i><span><?= $view->e($f) ?></span></li><?php endforeach; ?>
            </ul>
            <?php if ($view->hasLink($p['button'] ?? [])): ?><a class="btn wsm-btn <?= $featured ? 'wsm-btn-primary' : 'wsm-btn-ghost' ?> w-100 justify-content-center" href="<?= $view->href($p['button']) ?>"<?= $view->linkAttrs($p['button']) ?>><?= $view->e($p['button']['text']) ?></a><?php endif; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  </div>
</section>
